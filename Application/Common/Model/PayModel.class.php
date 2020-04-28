<?php
// +----------------------------------------------------------------------
// | 支付模型
// +----------------------------------------------------------------------
namespace Common\Model;

use Think\Model;

class PayModel
{
    /**
     * 完成订单
     * @param $TransID
     * @param $PayName
     * @param int $returntype
     */
    public function completeOrder($trans_id, $pay_name = '', $returntype = 1, $transaction_id = '')
    {
        $m_Order    = M("Order");
        $order_info = $m_Order->where(['pay_orderid' => $trans_id])->find(); //获取订单信息
        $userid     = intval($order_info["pay_memberid"] - 10000); // 商户ID
        $time       = time(); //当前时间

        //********************************************订单支付成功上游回调处理********************************************//
        if ($order_info["pay_status"] == 0 ||$order_info["pay_status"] == 3||$order_info["pay_status"] == 4) {
            //开启事物
            M()->startTrans();
            //查询用户信息
            $m_Member    = M('Member');
            $member_info = $m_Member->where(['id' => $userid])->lock(true)->find();
            //更新订单状态 1 已成功未返回 2 已成功已返回
            $res = $m_Order->where(['pay_orderid' => $trans_id, 'pay_status' => ['in', '0,3,4']])
                ->save(['pay_status' => 1,'is_budan'=>1, 'pay_successdate' => $time]);
            if (!$res) {
                M()->rollback();
                //file_put_contents('./ces.txt', "【".date('Y-m-d H:i:s')."】补单测试：1\r\n",FILE_APPEND);
                return false;
            }
            //-----------------------------------------修改用户数据 商户余额、冻结余额start-----------------------------------
            //要给用户增加的实际金额（扣除投诉保证金）
            $actualAmount          = $order_info['pay_actualamount'];
            $complaintsDepositRule = $this->getComplaintsDepositRule($userid);
            if (isset($complaintsDepositRule['status']) && $complaintsDepositRule['status'] == 1) {
                if ($complaintsDepositRule['ratio'] > 100) {
                    $complaintsDepositRule['ratio'] = 100;
                }
                $depositAmount = round($complaintsDepositRule['ratio'] / 100 * $actualAmount, 4);
                $actualAmount -= $depositAmount;
            }

            //创建修改用户修改信息
            $member_data = [
                'last_paying_time'   => $time,
                'unit_paying_number' => ['exp', 'unit_paying_number+1'],
                'unit_paying_amount' => ['exp', 'unit_paying_amount+' . $actualAmount],
                'paying_money'       => ['exp', 'paying_money+' . $actualAmount],
            ];

            //判断用结算方式
            switch ($order_info['t']) {
                case '0':
                //t+0结算
                case '7':
                //t+7 只限制提款和代付时间，每周一允许提款
                case '30':
                    //t+30 只限制提款和代付时间，每月第一天允许提款
                    //判断通道是免签还是官方
                    if($order_info['account_type']!=1){
                        $ymoney                 = $member_info['balance']; //改动前的金额
                        $gmoney                 = bcadd($member_info['balance'], $actualAmount, 4); //改动后的金额
                        $member_data['balance'] = ['exp', 'balance+' . $actualAmount]; //防止数据库并发脏读
                    }
                    
                    break;
                case '1':
                    //t+1结算，记录冻结资金
                    $blockedlog_data = [
                        'userid'     => $userid,
                        'orderid'    => $order_info['pay_orderid'],
                        'amount'     => $actualAmount,
                        'thawtime'   => (strtotime('tomorrow') + rand(0, 7200)),
                        'pid'        => $order_info['pay_bankcode'],
                        'createtime' => $time,
                        'status'     => 0,
                        'ag_uid'     => $order_info['owner_id'],
                    ];
                    $blockedlog_result = M('Blockedlog')->add($blockedlog_data);
                    if (!$blockedlog_result) {
                        //file_put_contents('./ces.txt', "【".date('Y-m-d H:i:s')."】补单测试：2\r\n",FILE_APPEND);
                        M()->rollback();
                        return false;
                    }
                    $ymoney                        = $member_info['blockedbalance']; //原冻结资金
                    $gmoney                        = bcadd($member_info['blockedbalance'], $actualAmount, 4); //改动后的冻结资金
                    $member_data['blockedbalance'] = ['exp', 'blockedbalance+' . $actualAmount]; //防止数据库并发脏读

                    break;
                default:
                    # code...
                    break;
            }
            if($order_info['account_type']!=1){
                //如果是码商订单，先扣除冻结金额
                if($order_info['account_type']==2 || $order_info['account_type']==3){
                    $codeinfo=$m_Member->getById($order_info['owner_id']);
                    if(!$codeinfo){
                        M()->rollback();
                        return false;
                    }
                    if($codeinfo['codeblockedbalance']<$order_info['pay_amount']){
                        M()->rollback();
                        return false;
                    }
                    $blockedlog_data = [
                        'userid'     => $order_info['owner_id'],
                        'orderid'    => $order_info['pay_orderid'],
                        'amount'     => $order_info['pay_amount'],
                        'thawtime'   => (strtotime('tomorrow') + rand(0, 7200)),
                        'pid'        => $order_info['pay_bankcode'],
                        'createtime' => $time,
                        'status'     => 0,
                        'ag_uid'     => $order_info['pay_memberid'],
                    ];
                    $blockedlog_result = M('Blockedlog')->add($blockedlog_data);
                    if (!$blockedlog_result) {
                        M()->rollback();
                        return false;
                    }
                    $member_blockdata['codeblockedbalance'] = ['exp', 'codeblockedbalance-' . $order_info['pay_amount']]; 
                    //解冻保证金记录
                    $depositRes = M('ComplaintsCodedeposit')->where(['pay_orderid' => $order_info['pay_orderid'],'user_id'=>$order_info['owner_id']])->save([
                        'real_unfreeze_time' => time(),
                        'status' => 1,
                        'update_at' => time(),
                    ]);
                    if(!$depositRes){
                        M()->rollback();
                        return false;
                    }

                    //加上码商佣金start
                    if($userid!=$order_info['owner_id']){
                        $codeflstatus=$this->huoqufeilv($order_info['owner_id'], $order_info['pay_bankcode'], $trans_id);
                        $codefl=$codeflstatus["feilv"];
                        $addcodemoney=$codefl*$order_info['pay_amount'];
                        if($addcodemoney>0){
                            $member_blockdata['balance'] = ['exp', 'balance+' . $addcodemoney];
                            $res = $m_Order->where(['pay_orderid' => $trans_id])
                                ->save(['code_rate_money' => $addcodemoney]);
                            if (!$res) {
                                M()->rollback();
                                return false;
                            }

                        }
                       
                        if($addcodemoney>0){
                            // 码商金额变动
                            $moneychange_data6 = [
                                'userid'     => $codeinfo['id'],
                                'ymoney'     => $codeinfo['balance'], //原金额或原冻结资金
                                'money'      => $addcodemoney,
                                'gmoney'     => $codeinfo['balance']+$addcodemoney, //改动后的金额或冻结资金
                                'datetime'   => date('Y-m-d H:i:s'),
                                'tongdao'    => $order_info['pay_bankcode'],
                                'transid'    => $trans_id,
                                'orderid'    => $order_info['out_trade_id'],
                                'contentstr' => '跑分结算',
                                'lx'         => 9,
                                't'          => $order_info['t'],
                            ];

                            $moneychange_result6 = $this->MoenyChange($moneychange_data6); // 资金变动记录

                            if ($moneychange_result6 == false) {
                                M()->rollback();
                                return false;
                            }
                        }
                        //获取码商的分销上级
                        //判断剩余费率
                        $syfl=$order_info['pay_poundage']/$order_info['pay_amount']-$codefl;
                        if($syfl>=0){
                            $code_fenxiaodata['userid']=$codeinfo['id'];
                            $code_fenxiaodata['tongdao']=$order_info['pay_bankcode'];
                            $code_fenxiaodata['transid']=$trans_id;
                            $code_fenxiaodata['money']=$actualAmount;
                            $code_fenxiaodata['syrate']=sprintf('%.4f',$syfl);
                            $this->qswlcodefenxiao($code_fenxiaodata);                        
                        }
                    }

                    $member_blockresult = $m_Member->where(['id' => $order_info['owner_id']])->save($member_blockdata);
                    if ($member_blockresult != 1) {
                        M()->rollback();
                        return false;
                    }
                    
                    //加上码商佣金end                    
                    
                }
                $member_result = $m_Member->where(['id' => $userid])->save($member_data);
                if ($member_result != 1) {
                    //file_put_contents('./ces.txt', "【".date('Y-m-d H:i:s')."】补单测试：11\r\n",FILE_APPEND);
                    M()->rollback();
                    return false;
                }

                // 商户充值金额变动
                $moneychange_data = [
                    'userid'     => $userid,
                    'ymoney'     => $ymoney, //原金额或原冻结资金
                    'money'      => $actualAmount,
                    'gmoney'     => $gmoney, //改动后的金额或冻结资金
                    'datetime'   => date('Y-m-d H:i:s'),
                    'tongdao'    => $order_info['pay_bankcode'],
                    'transid'    => $trans_id,
                    'orderid'    => $order_info['out_trade_id'],
                    'contentstr' => $order_info['out_trade_id'] . '订单充值,结算方式：t+' . $order_info['t'],
                    'lx'         => 1,
                    't'          => $order_info['t'],
                ];

                $moneychange_result = $this->MoenyChange($moneychange_data); // 资金变动记录

                if ($moneychange_result == false) {
                    //file_put_contents('./ces.txt', "【".date('Y-m-d H:i:s')."】补单测试：33\r\n",FILE_APPEND);
                    M()->rollback();
                    return false;
                }
            }
            

            //预存款扣除费率
            if($member_info['has_yck']==1 || $order_info['account_type']==1){   
                $ratemoney=$order_info['pay_poundage'];
                $ratemoney=sprintf('%.4f',$ratemoney);
                if($ratemoney>0){
                    $agmoneychange_data = [
                            'userid'     => $userid,
                            'ymoney'     => $member_info['yckbalance'], //原金额或原冻结资金
                            'money'      => $ratemoney,
                            'gmoney'     => $member_info['yckbalance']-$ratemoney, //改动后的金额或冻结资金
                            'datetime'   => date('Y-m-d H:i:s'),
                            'tongdao'    => $order_info['pay_bankcode'],
                            'transid'    => $trans_id,
                            'orderid'    => $order_info['out_trade_id'],
                            'contentstr' => '预存款扣费',
                            'lx'         => 18,
                            't'          => $order_info['t'],
                    ];
                    $member_data2['yckbalance'] = ['exp', 'yckbalance-' . $ratemoney]; //防止数据库并发脏读
                    $member_result = $m_Member->where(['id' => $userid])->save($member_data2);
                    if ($member_result != 1) {
                        //file_put_contents('./ces.txt', "【".date('Y-m-d H:i:s')."】补单测试：44\r\n",FILE_APPEND);
                        M()->rollback();
                        return false;
                    }
                    $moneychange_result = $this->MoenyChange($agmoneychange_data); // 资金变动记录
                    if ($moneychange_result == false) {
                        //file_put_contents('./ces.txt', "【".date('Y-m-d H:i:s')."】补单测试：51\r\n",FILE_APPEND);
                        M()->rollback();
                        return false;
                    }
                }
            }

            // 记录投诉保证金
            if (isset($depositAmount) && $depositAmount > 0) {
                $depositResult = M('ComplaintsDeposit')->add([
                    'user_id'       => $userid,
                    'pay_orderid'   => $trans_id,
                    'out_trade_id'  => $order_info['out_trade_id'],
                    'freeze_money'  => $depositAmount,
                    'unfreeze_time' => time() + $complaintsDepositRule['freeze_time'],
                    'status'        => 0,
                    'create_at'     => time(),
                    'update_at'     => time(),
                ]);
                if ($depositResult == false) {
                    //file_put_contents('./ces.txt', "【".date('Y-m-d H:i:s')."】补单测试：61\r\n",FILE_APPEND);
                    M()->rollback();
                    return false;
                }
            }

            // 通道ID
            $bianliticheng_data = [
                "userid"  => $userid, // 用户ID
                "transid" => $trans_id, // 订单号
                "money"   => $order_info["pay_amount"], // 金额
                "tongdao" => $order_info['pay_bankcode'],
            ];
            $this->qiswlticheng($bianliticheng_data); // 提成处理

            //码商提成处理
            
            M()->commit();

            //-----------------------------------------修改用户数据 商户余额、冻结余额end-----------------------------------

            //-----------------------------------------修改通道风控支付数据start----------------------------------------------
            $m_Channel     = M('Channel');
            $channel_where = ['id' => $order_info['channel_id']];
            $channel_info  = $m_Channel->where($channel_where)->find();
            //判断当天交易金额并修改支付状态
            $channel_res = $this->saveOfflineStatus(
                $m_Channel,
                $order_info['channel_id'],
                $order_info['pay_amount'],
                $channel_info
            );

            //-----------------------------------------修改通道风控支付数据end------------------------------------------------

            //-----------------------------------------修改子账号风控支付数据start--------------------------------------------
            $m_ChannelAccount      = M('ChannelAccount');
            $channel_account_where = ['id' => $order_info['account_id']];
            $channel_account_info  = $m_ChannelAccount->where($channel_account_where)->find();
            if ($channel_account_info['is_defined'] == 0) {
                //继承自定义风控规则
                $channel_info['paying_money'] = $channel_account_info['paying_money']; //当天已交易金额应该为子账号的交易金额
                $channel_account_info         = $channel_info;
            }
            //判断当天交易金额并修改支付状态
            $channel_account_res = $this->saveOfflineStatus(
                $m_ChannelAccount,
                $order_info['account_id'],
                $order_info['pay_amount'],
                $channel_account_info
            );
            if ($channel_account_info['unit_interval']) {
                $m_ChannelAccount->where([
                    'id' => $order_info['account_id'],
                ])->save([
                    'fail_nums' => 0,
                    'status' => 1,
                    'unit_paying_number' => ['exp', 'unit_paying_number+1'],
                    'unit_paying_amount' => ['exp', 'unit_paying_amount+' . $order_info['pay_actualamount']],
                ]);
            }else{
                $m_ChannelAccount->where([
                    'id' => $order_info['account_id'],
                ])->save([
                    'fail_nums' => 0,
                    'status' => 1,
                ]);
            }

            //-----------------------------------------修改子账号风控支付数据end----------------------------------------------

        }

        //************************************************回调，支付跳转*******************************************//
        $return_array = [ // 返回字段
            "memberid"       => $order_info["pay_memberid"], // 商户ID
            "orderid"        => $order_info['out_trade_id'], // 订单号
            'transaction_id' => $order_info["pay_orderid"], //支付流水号
            "amount"         => $order_info["pay_amount"], // 交易金额
            "datetime"       => date("YmdHis"), // 交易时间
            "returncode"     => "00", // 交易状态
        ];
        if (!isset($member_info)) {
            $member_info = M('Member')->where(['id' => $userid])->find();
        }
        $sign                   = $this->createSign($member_info['apikey'], $return_array);
        $return_array["sign"]   = $sign;
        $return_array["attach"] = $order_info["attach"];
        switch ($returntype) {
            case '0':
                $notifystr = "";
                foreach ($return_array as $key => $val) {
                    $notifystr = $notifystr . $key . "=" . $val . "&";
                }
                $notifystr = rtrim($notifystr, '&');
                $ch        = curl_init();
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_URL, $order_info["pay_notifyurl"]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $notifystr);
                $contents = curl_exec($ch);
                curl_close($ch);
                $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                log_server_notify($order_info["pay_orderid"], $order_info["pay_notifyurl"], $notifystr, $httpCode, $contents);
                if (strstr(strtolower($contents), "ok") != false) {
                    //更新交易状态
                    $order_where = [
                        'id'          => $order_info['id'],
                        'pay_orderid' => $order_info["pay_orderid"],
                    ];
                    $order_result = $m_Order->where($order_where)->setField("pay_status", 2);
                } else {
                    // $this->jiankong($order_info['pay_orderid']);
                }
                break;

            case '1':
                $this->setHtml($order_info["pay_callbackurl"], $return_array);
                break;

            default:
                # code...
                break;
        }
        
        return true;
    }


    //file_put_contents('./Data/budan.txt', "【".date('Y-m-d H:i:s')."】\r\n操作补单".json_encode($order_where)."\r\n\r\n",FILE_APPEND);


    //获取用户的费率
    public function getUserRate($userid,$pay_bankcode,$channel_id,$account_id,$pay_amount){
        //费率
        $m_Tikuanconfig     = M('Tikuanconfig');
        $tikuanconfig       = $m_Tikuanconfig->where(['userid' => $userid])->find();
        if (!$tikuanconfig || $tikuanconfig['tkzt'] != 1 || $tikuanconfig['systemxz'] != 1) {
            $tikuanconfig = $m_Tikuanconfig->where(['issystem' => 1])->find();
        }
        $_userrate = M('Userrate')
            ->where(["userid" => $userid, "payapiid" => $pay_bankcode])
            ->find();
        //银行通道费率
        $syschannel = M('Channel')
            ->where(['id' => $channel_id])
            ->find();

        //---------------------------子账号风控start------------------------------------
       $channel_account=M('ChannelAccount')->where(['id'=>$account_id])->find();
        //-------------------------子账号风控end-----------------------------------------

        $syschannel['mch_id']    = $channel_account['mch_id'];
        $syschannel['signkey']   = $channel_account['signkey'];
        $syschannel['appid']     = $channel_account['appid'];
        $syschannel['appsecret'] = $channel_account['appsecret'];
        $syschannel['account']   = $channel_account['title'];

        // 定制费率
        if ($channel_account['custom_rate']) {
            $syschannel['defaultrate'] = $channel_account['defaultrate'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['rate']        = $channel_account['rate'];
        }

        //用户优先通道
        if ($tikuanconfig['t1zt'] == 0) { //T+0费率
            $feilv    = $_userrate['t0feilv'] ? $_userrate['t0feilv'] : $syschannel['t0defaultrate']; // 交易费率
            $fengding = $_userrate['t0fengding'] ? $_userrate['t0fengding'] : $syschannel['t0fengding']; // 封顶手续费
        } else { //T+1费率
            $feilv    = $_userrate['feilv'] ? $_userrate['feilv'] : $syschannel['defaultrate']; // 交易费率
            $fengding = $_userrate['fengding'] ? $_userrate['fengding'] : $syschannel['fengding']; // 封顶手续费
        }
        $fengding = $fengding == 0 ? 9999999 : $fengding; //如果没有设置封顶手续费自动设置为一个足够大的数字

        //金额格式化

        if (!$pay_amount || !is_numeric($pay_amount) || $pay_amount <= 0) {
            $this->showmessage('金额错误');
        }
        $pay_sxfamount    = (($pay_amount * $feilv) > ($pay_amount * $fengding)) ? ($pay_amount * $fengding) :
        ($pay_amount * $feilv); // 手续费
        return $pay_sxfamount ;
    }


    //修改渠道跟账号风控状态
    protected function saveOfflineStatus($model, $id, $pay_amount, $info)
    {
        if ($info['offline_status'] && $info['control_status'] && $info['all_money'] > 0) {
            //通道是否开启风控和支付状态为上线
            $data['paying_money']     = bcadd($info['paying_money'], $pay_amount, 2);
            $data['last_paying_time'] = time();

            if ($data['paying_money'] >= $info['all_money']) {
                $data['offline_status'] = 0;
            }
            return $model->where(['id' => $id])->save($data);
        }
        return true;
    }

    /**
     *  验证签名
     * @return bool
     */
    protected function verify()
    {
        //POST参数
        $requestarray = array(
            'pay_memberid'    => I('request.pay_memberid', 0, 'intval'),
            'pay_orderid'     => I('request.pay_orderid', ''),
            'pay_amount'      => I('request.pay_amount', ''),
            'pay_applydate'   => I('request.pay_applydate', ''),
            'pay_bankcode'    => I('request.pay_bankcode', ''),
            'pay_notifyurl'   => I('request.pay_notifyurl', ''),
            'pay_callbackurl' => I('request.pay_callbackurl', ''),
        );
        $md5key        = $this->merchants['apikey'];
        $md5keysignstr = $this->createSign($md5key, $requestarray);
        $pay_md5sign   = I('request.pay_md5sign');
        if ($pay_md5sign == $md5keysignstr) {
            return true;
        } else {
            return false;
        }
    }

    public function setHtml($tjurl, $arraystr)
    {
        $str = '<form id="Form1" name="Form1" method="post" action="' . $tjurl . '">';
        foreach ($arraystr as $key => $val) {
            $str .= '<input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        $str .= '</form>';
        $str .= '<script>';
        $str .= 'document.Form1.submit();';
        $str .= '</script>';
        exit($str);
    }

    public function jiankong($orderid)
    {
        ignore_user_abort(true);
        set_time_limit(3600);
        $Order    = M("Order");
        $interval = 10;
        do {
            if ($orderid) {
                $_where['pay_status']  = 1;
                $_where['num']         = array('lt', 3);
                $_where['pay_orderid'] = $orderid;
                $find                  = $Order->where($_where)->find();
            } else {
                $find = $Order->where("pay_status = 1 and num < 3")->order("id desc")->find();
            }
            if ($find) {
                $this->EditMoney($find["pay_orderid"], $find["pay_tongdao"], 0);
                $Order->where(["id" => $find["id"]])->save(['num' => ['exp', 'num+1']]);
            }

            sleep($interval);
        } while (true);
    }

    /**
     * 资金变动记录
     * @param $arrayField
     * @return bool
     */
    protected function MoenyChange($arrayField)
    {
        // 资金变动
        $Moneychange = M("Moneychange");
        foreach ($arrayField as $key => $val) {
            $data[$key] = $val;
        }
        $result = $Moneychange->add($data);
        return $result ? true : false;
    }

    /**
     * 佣金处理qswl
     * @param $arrayStr
     * @param int $num
     * @param int $tcjb
     * @return bool
        $code_fenxiaodata['userid']=$codeinfo['id'];
        $code_fenxiaodata['tongdao']=$order_info['pay_bankcode'];
        $code_fenxiaodata['transid']=$trans_id;
        $code_fenxiaodata['money']=$actualAmount;
        $code_fenxiaodata['syrate']=sprintf('%.4f',$syfl);
     */
    private function qswlcodefenxiao($arrayStr, $num = 3, $tcjb = 1)
    {
        if ($num <= 0) {
            return false;
        }
        if($arrayStr["syrate"]<=0){
            return false;
        }
        $userid    = $arrayStr["userid"];
        $tongdaoid = $arrayStr["tongdao"];
        $trans_id  = $arrayStr["transid"];
        $feilvfind = $this->huoqufeilv($userid, $tongdaoid, $trans_id);

        if ($feilvfind["status"] == "error") {
            return false;
        } else {
            //商户费率（下级）
            $x_feilv    = $feilvfind["feilv"];
            $x_fengding = $feilvfind["fengding"];

            //代理商(上级)
            $parentid = M("Member")->where(["id" => $userid])->getField("parentid");
            if ($parentid <= 1) {
                return false;
            }
            $parentRate = $this->huoqufeilv($parentid, $tongdaoid, $trans_id);

            if ($parentRate["status"] == "error") {
                return false;
            } else {

                //代理商(上级）费率
                $s_feilv    = $parentRate["feilv"];
                $s_fengding = $parentRate["fengding"];

                //费率差
                $ratediff = (($s_feilv * 1000)-($x_feilv * 1000)) / 1000;
                if ($ratediff <= 0) {
                    return false;
                } else {
                    $parent = M('Member')->where(['id' => $parentid])->field('id,balance,groupid')->find();
                    if (empty($parent)) {
                        return false;
                    }
                    if($parent['groupid']!=8){
                        return false;
                    }
                    if($ratediff>=$arrayStr["syrate"]){
                        $ratediff=$arrayStr["syrate"];
                    }
                    $brokerage = $arrayStr['money'] * $ratediff;
                    //代理佣金
                    $rows = [
                        'balance' => array('exp', "balance+{$brokerage}"),
                    ];
                    M('Member')->where(['id' => $parentid])->save($rows);

                    //代理商资金变动记录
                    $arrayField = array(
                        "userid"   => $parentid,
                        "ymoney"   => $parent['balance'],
                        "money"    => $arrayStr["money"] * $ratediff,
                        "gmoney"   => $parent['balance'] + $brokerage,
                        "datetime" => date("Y-m-d H:i:s"),
                        "tongdao"  => $tongdaoid,
                        "transid"  => $arrayStr["transid"],
                        "orderid"  => "tx" . date("YmdHis"),
                        "tcuserid" => $userid,
                        "tcdengji" => $tcjb,
                        "lx"       => 9,
                    );
                    $this->MoenyChange($arrayField); // 资金变动记录
                    $num                = $num - 1;
                    $tcjb               = $tcjb + 1;
                    $arrayStr["userid"] = $parentid;
                    $arrayStr["syrate"] = $arrayStr["syrate"]-$ratediff;
                    $this->qswlcodefenxiao($arrayStr, $num, $tcjb);
                }
            }
        }
    }
    private function qiswlticheng($arrayStr, $num = 3, $tcjb = 1)
    {
        if ($num <= 0) {
            return false;
        }
        $userid    = $arrayStr["userid"];
        $tongdaoid = $arrayStr["tongdao"];
        $trans_id  = $arrayStr["transid"];
        $feilvfind = $this->huoqufeilv($userid, $tongdaoid, $trans_id);

        if ($feilvfind["status"] == "error") {
            return false;
        } else {
            //商户费率（下级）
            $x_feilv    = $feilvfind["feilv"];
            $x_fengding = $feilvfind["fengding"];

            //代理商(上级)
            $parentid = M("Member")->where(["id" => $userid])->getField("parentid");
            if ($parentid <= 1) {
                return false;
            }
            $parentRate = $this->huoqufeilv($parentid, $tongdaoid, $trans_id);

            if ($parentRate["status"] == "error") {
                return false;
            } else {

                //代理商(上级）费率
                $s_feilv    = $parentRate["feilv"];
                $s_fengding = $parentRate["fengding"];

                //费率差
                $ratediff = (($x_feilv * 1000) - ($s_feilv * 1000)) / 1000;
                if ($ratediff <= 0) {
                    return false;
                } else {
                    $parent = M('Member')->where(['id' => $parentid])->field('id,balance,groupid')->find();
                    if (empty($parent)) {
                        return false;
                    }
                    if($parent['groupid']==8){
                        return false;
                    }
                    $brokerage = $arrayStr['money'] * $ratediff;
                    //代理佣金
                    $rows = [
                        'balance' => array('exp', "balance+{$brokerage}"),
                    ];
                    M('Member')->where(['id' => $parentid])->save($rows);

                    //代理商资金变动记录
                    $arrayField = array(
                        "userid"   => $parentid,
                        "ymoney"   => $parent['balance'],
                        "money"    => $arrayStr["money"] * $ratediff,
                        "gmoney"   => $parent['balance'] + $brokerage,
                        "datetime" => date("Y-m-d H:i:s"),
                        "tongdao"  => $tongdaoid,
                        "transid"  => $arrayStr["transid"],
                        "orderid"  => "tx" . date("YmdHis"),
                        "tcuserid" => $userid,
                        "tcdengji" => $tcjb,
                        "lx"       => 9,
                    );
                    $this->MoenyChange($arrayField); // 资金变动记录
                    $num                = $num - 1;
                    $tcjb               = $tcjb + 1;
                    $arrayStr["userid"] = $parentid;
                    $this->qiswlticheng($arrayStr, $num, $tcjb);
                }
            }
        }
    }

    /**
     * 佣金处理
     * @param $arrayStr
     * @param int $num
     * @param int $tcjb
     * @return bool
     */
    private function bianliticheng($arrayStr, $num = 3, $tcjb = 1)
    {
        if ($num <= 0) {
            return false;
        }
        $userid    = $arrayStr["userid"];
        $tongdaoid = $arrayStr["tongdao"];
        $trans_id = $arrayStr["trans_id"];
        $feilvfind = $this->huoqufeilv($userid, $tongdaoid, $trans_id);

        if ($feilvfind["status"] == "error") {
            return false;
        } else {
            //商户费率（下级）
            $x_feilv    = $feilvfind["feilv"];
            $x_fengding = $feilvfind["fengding"];

            //代理商(上级)
            $parentid = M("Member")->where(["id" => $userid])->getField("parentid");
            if ($parentid <= 1) {
                return false;
            }
            $parentRate = $this->huoqufeilv($parentid, $tongdaoid, $trans_id);

            if ($parentRate["status"] == "error") {
                return false;
            } else {

                //代理商(上级）费率
                $s_feilv    = $parentRate["feilv"];
                $s_fengding = $parentRate["fengding"];

                //费率差
                $ratediff = (($x_feilv * 1000) - ($s_feilv * 1000)) / 1000;
                if ($ratediff <= 0) {
                    return false;
                } else {
                    $parent    = M('Member')->where(['id' => $parentid])->field('id,balance')->find();
                    if(empty($parent)) {
                        return false;
                    }
                    $brokerage = $arrayStr['money'] * $ratediff;
                    //代理佣金
                    $rows = [
                        'balance' => array('exp', "balance+{$brokerage}"),
                    ];
                    M('Member')->where(['id' => $parentid])->save($rows);

                    //代理商资金变动记录
                    $arrayField = array(
                        "userid"   => $parentid,
                        "ymoney"   => $parent['balance'],
                        "money"    => $arrayStr["money"] * $ratediff,
                        "gmoney"   => $parent['balance'] + $brokerage,
                        "datetime" => date("Y-m-d H:i:s"),
                        "tongdao"  => $tongdaoid,
                        "transid"  => $arrayStr["transid"],
                        "orderid"  => "tx" . date("YmdHis"),
                        "tcuserid" => $userid,
                        "tcdengji" => $tcjb,
                        "lx"       => 9,
                    );
                    $this->MoenyChange($arrayField); // 资金变动记录
                    $num                = $num - 1;
                    $tcjb               = $tcjb + 1;
                    $arrayStr["userid"] = $parentid;
                    $this->bianliticheng($arrayStr, $num, $tcjb);
                }
            }
        }
    }

    private function huoqufeilv($userid, $payapiid, $trans_id)
    {
        $return = array();
        $order = M('Order')->where(['pay_orderid' => $trans_id])->find();
        //用户费率
        $userrate = M("Userrate")->where(["userid" => $userid, "payapiid" => $payapiid])->find();
        //支付通道费率
        $syschannel = M('Channel')->where(['id' => $payapiid])->find();
        if($order['t'] == 0) {//T+0费率
            $feilv    = $userrate['t0feilv'] ? $userrate['t0feilv'] : $syschannel['t0defaultrate']; // 交易费率
            $fengding = $userrate['t0fengding'] ? $userrate['t0fengding'] : $syschannel['t0fengding']; // 封顶手续费
        } else {//T+1费率
            $feilv    = $userrate['feilv'] ? $userrate['feilv'] : $syschannel['defaultrate']; // 交易费率
            $fengding = $userrate['fengding'] ? $userrate['fengding'] : $syschannel['fengding']; // 封顶手续费
        }
        $return["status"]   = "ok";
        $return["feilv"]    = $feilv;
        $return["fengding"] = $fengding;
        return $return;
    }

    /**
     * 创建签名
     * @param $Md5key
     * @param $list
     * @return string
     */
    protected function createSign($Md5key, $list)
    {
        ksort($list);
        $md5str = "";
        foreach ($list as $key => $val) {
            if (!empty($val)) {
                $md5str = $md5str . $key . "=" . $val . "&";
            }
        }
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        return $sign;
    }

    /**
     * 获取投诉保证金金额
     * @param $userid
     * @return array
     */
    private function getComplaintsDepositRule($userid)
    {
        $complaintsDepositRule = M('ComplaintsDepositRule')->where(['user_id' => $userid])->find();
        if (!$complaintsDepositRule || $complaintsDepositRule['status'] != 1) {
            $complaintsDepositRule = M('ComplaintsDepositRule')->where(['is_system' => 1])->find();
        }
        return $complaintsDepositRule ? $complaintsDepositRule : [];
    }
}

?>