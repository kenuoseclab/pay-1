<?php

namespace Pay\Controller;

use Think\Controller;

class PayController extends Controller
{
    //商家信息
    protected $merchants;
    //网站地址
    protected $_site;
    //通道信息
    protected $channel;

    public function __construct()
    {
        parent::__construct();
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
    }
    /**
     * 创建订单
     * @param $parameter
     * @return array
     */
    public function orderadd($parameter)
    {

        $pay_amount = I("post.pay_amount", 0);

        //通道信息
        $this->channel = $parameter['channel'];
        if($this->channel['test']==1){
            $return=$this->addTestOrder($parameter);
            return $return;
        }
        //$this->merchants = $this->channel['userid'];
        //用户信息
        $usermodel       = D('Member');
        $this->merchants = $usermodel->get_Userinfo($this->channel['userid']);
        $return          = array();
        // 通道名称
        $PayName = $parameter["code"];
        // 交易金额比例
        $moneyratio = $parameter["exchange"];
        //商户编号
        $return["memberid"] = $userid = $this->merchants['id'] + 10000;
        $m_Tikuanconfig     = M('Tikuanconfig');
        $tikuanconfig       = $m_Tikuanconfig->where(['userid' => $this->merchants['id']])->find();
        if (!$tikuanconfig || $tikuanconfig['tkzt'] != 1 || $tikuanconfig['systemxz'] != 1) {
            $tikuanconfig = $m_Tikuanconfig->where(['issystem' => 1])->find();
        }
        //费率
        $_userrate = M('Userrate')
            ->where(["userid" => $this->channel['userid'], "payapiid" => $this->channel['pid']])
            ->find();
        //银行通道费率
        $syschannel = M('Channel')
            ->where(['id' => $this->channel['api']])
            ->find();

        //---------------------------子账号风控start------------------------------------
        //$channel_account_list        = M('channel_account')->where(['channel_id' => $syschannel['id'], 'status' => '1'])->select();
        $account_ids                 = M('UserChannelAccount')->where(['userid' => $this->channel['userid'], 'status' => 1])->getField('account_ids');
        $l_ChannelAccountRiskcontrol = new \Pay\Logic\ChannelAccountRiskcontrolLogic($pay_amount);
        $channel_account_item        = [];
        $error_msg                   = '没有可用账号';
        //验证走码商还是自己
        $UserChannelType=M('UserChannelType')->where(['uid'=>$this->channel['userid'],'channel_id'=> $this->channel['api']])->find();
        // var_dump($syschannel['unit_samemoney_status']);exit;
        if($UserChannelType){
            $ctype=$UserChannelType['type'];
        }else{
            $ctype=0;
        }
        if($ctype==1){
            //走自己通道
            $channel_account_list        = M('channel_account')->where(['channel_id' => $syschannel['id'], 'status' => '1','account_type'=>1,'add_user_id'=>$this->channel['userid']])->select();
            foreach ($channel_account_list as $k => $v) {
                //判断是自定义还是继承渠道的风控
                $temp_info               = $v['is_defined'] ? $v : $syschannel;
                if(($temp_info['offline_status'] && $temp_info['control_status']) ||$syschannel['unit_samemoney_status']==1) {                        
                    $temp_info['account_id'] = $v['id']; //用于子账号风控类继承渠道风控机制时修改数据的id
                    $temp_info['unit_samemoney_status'] = $syschannel['unit_samemoney_status']; 
                    $temp_info['area_status'] = $syschannel['area_status'];
                    $temp_info['city_id'] = $v['city_id'];
                    $temp_info['max_fail_nums'] = $syschannel['max_fail_nums'];
                    $temp_info['fail_nums'] = $v['fail_nums'];
                    //子账号风控
                    $l_ChannelAccountRiskcontrol->setConfigInfo($temp_info);
                    $error_msg = $l_ChannelAccountRiskcontrol->monitoringData();
                    if ($error_msg === true) {
                        $channel_account_item[] = $v;
                    }
                } else if ($temp_info['control_status'] == 0) {
                    $channel_account_item[] = $v;
                }
            }
            if (empty($channel_account_item)) {
                $this->showmessage('账户:' . $error_msg.'(100011)');
            }
            //var_dump($channel_account_list);exit;
        }else{
            //走码商的通道//是否有推荐人
            $path_id=$this->merchants['path_id'];
            $path_id = rtrim($path_id, ',');
            $path_id=explode(',',  $path_id );
            unset($path_id[count($path_id)-1]);
            unset($path_id[0]);
            $path_id=array_reverse($path_id);
            $path_r=array();
            foreach($path_id as $v){
                if(strstr($v,'C')){
                    $path_r[]=intval(trim($v,"C"));
                }
            }
            $mshsaccount=0;
            if(!empty($path_r)){
                //有上级码商
                foreach($path_r as $v){
                    $msbalance=M('member')->where(['id'=>$v])->getField('balance');
                    if($msbalance>=$pay_amount){
                        $channel_account_list        = M('channel_account')->where(['channel_id' => $syschannel['id'], 'status' => '1','account_type'=>2,'add_user_id'=>$v])->select();
                        if($account_ids){
                            $account_ids  = explode(',',  $account_ids );
                            foreach($channel_account_list as $k => $v){
                                //如果不在指定的子账号，将其删除
                                if(!in_array($v['id'], $account_ids )){
                                    unset($channel_account_list[$k]);
                                }
                            }
                        }
                        foreach ($channel_account_list as $k => $v) {
                            //判断是自定义还是继承渠道的风控
                            $temp_info               = $v['is_defined'] ? $v : $syschannel;
                            if(($temp_info['offline_status'] && $temp_info['control_status']) ||$syschannel['unit_samemoney_status']==1) {                        
                                $temp_info['account_id'] = $v['id']; //用于子账号风控类继承渠道风控机制时修改数据的id
                                $temp_info['unit_samemoney_status'] = $syschannel['unit_samemoney_status'];
                                $temp_info['area_status'] = $syschannel['area_status']; 
                                $temp_info['city_id'] = $v['city_id'];
                                $temp_info['max_fail_nums'] = $syschannel['max_fail_nums'];
                                $temp_info['fail_nums'] = $v['fail_nums'];
                                //子账号风控
                                $l_ChannelAccountRiskcontrol->setConfigInfo($temp_info);
                                $error_msg = $l_ChannelAccountRiskcontrol->monitoringData();
                                if ($error_msg === true) {
                                    $channel_account_item[] = $v;
                                }
                            } else if ($temp_info['control_status'] == 0) {
                                $channel_account_item[] = $v;
                            }
                        }
                        if (!empty($channel_account_item)) {
                            break;
                        }
                    }    
                }
                if(empty($channel_account_item)){
                    $mshsaccount=1;
                }
            }

            //没有上级码商 或者上级码商没有号
            if(empty($path_r) || $mshsaccount){
                $channel_account_list        = M('channel_account')->where(['channel_id' => $syschannel['id'], 'status' => '1','account_type'=>['in',[0,2]]])->select();
                $account_ids                 = M('UserChannelAccount')->where(['userid' => $this->channel['userid'], 'status' => 1])->getField('account_ids');
                if($account_ids){
                     $account_ids  = explode(',',  $account_ids );
                    foreach($channel_account_list as $k => $v){
                        //如果不在指定的子账号，将其删除
                        if(!in_array($v['id'], $account_ids )){
                            unset($channel_account_list[$k]);
                        }
                    }
                }
                $channel_account_list=$this->checkAuthCode($channel_account_list,$pay_amount);

                $channel_account_item        = [];
                $error_msg                   = '没有可用账号';
                foreach ($channel_account_list as $k => $v) {
                    //判断是自定义还是继承渠道的风控
                    $temp_info               = $v['is_defined'] ? $v : $syschannel;
                    if(($temp_info['offline_status'] && $temp_info['control_status']) ||$syschannel['unit_samemoney_status']==1) {                        
                        $temp_info['account_id'] = $v['id']; //用于子账号风控类继承渠道风控机制时修改数据的id
                        $temp_info['unit_samemoney_status'] = $syschannel['unit_samemoney_status']; 
                        $temp_info['area_status'] = $syschannel['area_status'];
                        $temp_info['city_id'] = $v['city_id'];
                        $temp_info['max_fail_nums'] = $syschannel['max_fail_nums'];
                        $temp_info['fail_nums'] = $v['fail_nums']; 
                        //子账号风控
                        $l_ChannelAccountRiskcontrol->setConfigInfo($temp_info);
                        $error_msg = $l_ChannelAccountRiskcontrol->monitoringData();
                        if ($error_msg === true) {
                            $channel_account_item[] = $v;
                        }
                    } else if ($temp_info['control_status'] == 0) {
                        $channel_account_item[] = $v;
                    }
                }
                if (empty($channel_account_item)) {
                    
                    if($syschannel['auto_paofen']==1){
                        $return=$this->addQdOrder($parameter);
                        return $return;
                    }else{
                       $this->showmessage('账户:' . $error_msg.'(100011)'); 
                    }
                    // $this->showmessage('账户:' . $error_msg);
                }

            }
        }
       
        //-------------------------子账号风控end-----------------------------------------

        // 计算权重
        if (count($channel_account_item) == 1) {
            $channel_account = current($channel_account_item);
        } else {
            $channel_account = getWeight($channel_account_item);
        }

        $syschannel['mch_id']    = $channel_account['mch_id'];
        $syschannel['signkey']   = $channel_account['signkey'];
        $syschannel['appid']     = $channel_account['appid'];
        $syschannel['appsecret'] = $channel_account['appsecret'];
        $syschannel['account']   = $channel_account['title'];
        $syschannel['zfb_pid']   = $channel_account['zfb_pid'];

        // 定制费率
        if ($channel_account['custom_rate']) {
            $syschannel['defaultrate'] = $channel_account['defaultrate'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['rate']        = $channel_account['rate'];
        }

        //平台通道
        $platform = M('Product')
            ->where(['id' => $this->channel['pid']])
            ->find();
        if ($channel_account['unlockdomain']) {
            $unlockdomain = $channel_account['unlockdomain'] ? $channel_account['unlockdomain'] : '';
        } else {
            $unlockdomain = $syschannel['unlockdomain'] ? $syschannel['unlockdomain'] : '';
        }

        //回调参数
        $return = [
            "mch_id"       => $syschannel["mch_id"], //商户号
            "signkey"      => $syschannel["signkey"], // 签名密钥
            "appid"        => $syschannel["appid"], // APPID
            "appsecret"    => $syschannel["appsecret"], // APPSECRET
            "zfb_pid"      => $syschannel["zfb_pid"], // 支付宝PID
            "gateway"      => $syschannel["gateway"] ? $syschannel["gateway"] : $parameter["gateway"], // 网关
            "notifyurl"    => $syschannel["serverreturn"] ? $syschannel["serverreturn"] : $this->_site . "Pay_" .
            $PayName . "_notifyurl.html",
            "callbackurl"  => $syschannel["pagereturn"] ? $syschannel["pagereturn"] : $this->_site . "Pay_" .
            $PayName . "_callbackurl.html",
            'unlockdomain' => $unlockdomain, //防封域名
        ];

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
        $return["amount"] = floatval($pay_amount) * $moneyratio; // 交易金额
        $pay_sxfamount    = (($pay_amount * $feilv) > ($pay_amount * $fengding)) ? ($pay_amount * $fengding) :
        ($pay_amount * $feilv); // 手续费
        if($this->merchants['has_yck']==1 || $channel_account['account_type']==1){
            $pay_shijiamount = $pay_amount; // 实际到账金额
        }else{
            $pay_shijiamount = $pay_amount - $pay_sxfamount; // 实际到账金额
        }
        if ($tikuanconfig['t1zt'] == 0) { //T+0费率
            $cost = bcmul($syschannel['t0rate'], $pay_amount, 2); //计算成本
        } else {
            $cost = bcmul($syschannel['rate'], $pay_amount, 2); //计算成本
        }

        //验证免签预扣款
        if($this->merchants['has_yck']==1 || $channel_account['account_type']==1){
            if($this->merchants['yckbalance']<$pay_sxfamount){
                $this->showmessage('商户预存款不足！');
            } 
        }



        //商户订单号
        $out_trade_id = $parameter['out_trade_id'];
        //生成系统订单号
        $pay_orderid = $parameter['orderid'] ? $parameter['orderid'] : get_requestord();
        //验签
        if ($this->verify()) {
            $Order                       = M("Order");
            $return['bankcode']          = $this->channel['pid'];
            $return['code']              = $platform['code']; //银行英文代码
            $return['orderid']           = $pay_orderid; // 系统订单号
            $return['out_trade_id']      = $out_trade_id; // 外部订单号
            $return['subject']           = $parameter['body']; // 商品标题
            $return['account_id']        = $channel_account['id'];
            $data['pay_memberid']        = $userid;
            $data['pay_orderid']         = $return["orderid"];
            $data['pay_amount']          = $pay_amount; // 交易金额
            $data['pay_poundage']        = $pay_sxfamount; // 手续费
            $data['pay_actualamount']    = $pay_shijiamount; // 到账金额
            $data['pay_applydate']       = time();
            $data['pay_bankcode']        = $this->channel['pid'];
            $data['pay_bankname']        = $platform['name'];
            $data['pay_notifyurl']       = I('post.pay_notifyurl', '');
            $data['pay_callbackurl']     = I('post.pay_callbackurl', '');
            $data['pay_status']          = 0;
            $data['pay_tongdao']         = $syschannel['code'];
            $data['pay_zh_tongdao']      = $syschannel['title'];
            $data['pay_channel_account'] = $syschannel['account'];
            $data['pay_ytongdao']        = $parameter["code"];
            $data['pay_yzh_tongdao']     = $parameter["title"];
            $data['pay_tjurl']           = $_SERVER['HTTP_REFERER'];
            $data['pay_productname']     = I("request.pay_productname");
            $data['attach']              = I("request.pay_attach");
            $data['out_trade_id']        = $out_trade_id;
            $data['ddlx']                = I("post.ddlx", 0);
            $data['memberid']            = $return["mch_id"];
            $data['key']                 = $return["signkey"];
            $data['account']             = $return["appid"];
            $data['cost']                = $cost;
            $data['cost_rate']           = $tikuanconfig['t1zt'] == 0 ? $syschannel['t0rate'] : $syschannel['rate'];
            $data['channel_id']          = $this->channel['api'];
            $data['account_id']          = $channel_account['id'];
            $data['t']                   = $tikuanconfig['t1zt'];
            $data['owner_id']            = $channel_account['add_user_id'];
            $data['account_type']        = $channel_account['account_type'];
            $data['is_mianqian']         = $syschannel['is_mianqian'];
            //开启事物
            M()->startTrans();
            //添加订单
            if ($Order->add($data)) {
                //通道增加失败次数
                $res=M('ChannelAccount')->where(['id'=>$data['account_id']])->setInc('fail_nums',1);
                if (!$res) {
                    M()->rollback();
                    $this->showmessage('系统错误10001');
                }
                $return['datetime'] = date('Y-m-d H:i:s', $data['pay_applydate']);
                $return["status"]   = "success";
                //码商的话吧余额转到冻结
                if($channel_account['account_type']==2){
                    $complaintsCodeDepositRule = $this->getComplaintsCodeDepositRule($channel_account['add_user_id']);
                    $codeinfo=M("Member")->where(['id'=>$channel_account['add_user_id']])->field('`balance`,`codeblockedbalance`,`id`')->find();
                    $agmoneychange_data = [
                            'userid'     => $channel_account['add_user_id'],
                            'ymoney'     => $codeinfo['balance'], //原金额或原冻结资金
                            'money'      => $pay_amount,
                            'gmoney'     => $codeinfo['balance']-$pay_amount, //改动后的金额或冻结资金
                            'datetime'   => date('Y-m-d H:i:s'),
                            'tongdao'    => $data['pay_bankcode'],
                            'transid'    => $trans_id,
                            'orderid'    => $data['out_trade_id'],
                            'contentstr' => '订单下单存款冻结',
                            'lx'         => 20,
                            't'          => 0,
                    ];
                    $depositResult = M('ComplaintsCodedeposit')->add([
                        'user_id'       => $channel_account['add_user_id'],
                        'pay_orderid'   => $data['pay_orderid'],
                        'out_trade_id'  => $data['out_trade_id'],
                        'freeze_money'  => $pay_amount,
                        'unfreeze_time' => time() + $complaintsCodeDepositRule['freeze_time'],
                        'status'        => 0,
                        'create_at'     => time(),
                        'update_at'     => time(),
                    ]);
                    if ($depositResult == false) {
                        M()->rollback();
                        $this->showmessage('系统错误10002');
                    }

                    $member_data2['balance'] = ['exp', 'balance-' . $pay_amount]; //防止数据库并发脏读
                    $member_data2['codeblockedbalance'] = ['exp', 'codeblockedbalance+' . $pay_amount]; //防止数据库并发脏读
                    $member_result = M("Member")->where(['id' => $channel_account['add_user_id']])->save($member_data2);
                    if ($member_result != 1) {
                        M()->rollback();
                        $this->showmessage('系统错误10003');
                    }else{
                        $moneychange_result = $this->MoenyChange($agmoneychange_data); // 资金变动记录
                        if ($moneychange_result == false) {
                            M()->rollback();
                            $this->showmessage('系统错误10004');
                        }
                    }
                    

                }
                M()->commit();
                return $return;
            } else {
                $err = $Order->getDbError();
                $sql = $Order->getLastSql();
                M()->rollback();
                $this->showmessage('系统错误10005'.$err.$sql);
            }
        } else {
            $this->showmessage('签名验证失败', $_POST);
        }
    }

    /**
     * 添加测试订单
     * @param $TransID
     * @param $PayName
     * @param int $returntype
     */

    protected function addQdOrder($parameter){
        $pay_amount = I("post.pay_amount", 0);

        //通道信息
        $this->channel = $parameter['channel'];
        
        //$this->merchants = $this->channel['userid'];
        //用户信息
        $usermodel       = D('Member');
        $this->merchants = $usermodel->get_Userinfo($this->channel['userid']);
        $return          = array();
        // 通道名称
        $PayName = $parameter["code"];
        // 交易金额比例
        $moneyratio = $parameter["exchange"];
        //商户编号
        $return["memberid"] = $userid = $this->merchants['id'] + 10000;
        $m_Tikuanconfig     = M('Tikuanconfig');
        $tikuanconfig       = $m_Tikuanconfig->where(['userid' => $this->merchants['id']])->find();
        if (!$tikuanconfig || $tikuanconfig['tkzt'] != 1 || $tikuanconfig['systemxz'] != 1) {
            $tikuanconfig = $m_Tikuanconfig->where(['issystem' => 1])->find();
        }
        //费率
        $_userrate = M('Userrate')
            ->where(["userid" => $this->channel['userid'], "payapiid" => $this->channel['pid']])
            ->find();
        //银行通道费率
        $syschannel = M('Channel')
            ->where(['id' => $this->channel['api']])
            ->find();

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
        $return["amount"] = floatval($pay_amount) * $moneyratio; // 交易金额
        $pay_sxfamount    = (($pay_amount * $feilv) > ($pay_amount * $fengding)) ? ($pay_amount * $fengding) :
        ($pay_amount * $feilv); // 手续费
        if($this->merchants['has_yck']==1 || $channel_account['account_type']==1){
            $pay_shijiamount = $pay_amount; // 实际到账金额
        }else{
            $pay_shijiamount = $pay_amount - $pay_sxfamount; // 实际到账金额
        }
        
        if ($tikuanconfig['t1zt'] == 0) { //T+0费率
            $cost = bcmul($syschannel['t0rate'], $pay_amount, 2); //计算成本
        } else {
            $cost = bcmul($syschannel['rate'], $pay_amount, 2); //计算成本
        }

        //验证免签预扣款
        if($this->merchants['has_yck']==1 || $channel_account['account_type']==1){
            if($this->merchants['yckbalance']<$pay_sxfamount){
                $this->showmessage('商户预存款不足！');
            } 
        }

        //商户订单号
        $out_trade_id = $parameter['out_trade_id'];
        //生成系统订单号
        $pay_orderid = $parameter['orderid'] ? $parameter['orderid'] : get_requestord();
        //验签        
            $Order                       = M("Order");
            $return['bankcode']          = $this->channel['pid'];
            $return['code']              = $platform['code']; //银行英文代码
            $return['orderid']           = $pay_orderid; // 系统订单号
            $return['out_trade_id']      = $out_trade_id; // 外部订单号
            $return['subject']           = $parameter['body']; // 商品标题
            
            $data['pay_memberid']        = $userid;
            $data['pay_orderid']         = $return["orderid"];
            $data['pay_amount']          = $pay_amount; // 交易金额
            $data['pay_poundage']        = $pay_sxfamount; // 手续费
            $data['pay_actualamount']    = $pay_shijiamount; // 到账金额
            $data['pay_applydate']       = time();
            $data['pay_bankcode']        = $this->channel['pid'];
            $data['pay_bankname']        = $parameter['title'];
            $data['pay_notifyurl']       = I('post.pay_notifyurl', '');
            $data['pay_callbackurl']     = I('post.pay_callbackurl', '');
            $data['pay_status']          = 0;
            $data['pay_tongdao']         = $syschannel['code'];
            $data['pay_zh_tongdao']      = $syschannel['title'];
            
            $data['pay_ytongdao']        = $parameter["code"];
            $data['pay_yzh_tongdao']     = $parameter["title"];
            $data['pay_tjurl']           = $_SERVER['HTTP_REFERER'];
            $data['pay_productname']     = I("request.pay_productname");
            $data['attach']              = I("request.pay_attach");
            $data['out_trade_id']        = $out_trade_id;
            $data['ddlx']                = I("post.ddlx", 0);
            $data['cost']                = $cost;
            $data['cost_rate']           = $tikuanconfig['t1zt'] == 0 ? $syschannel['t0rate'] : $syschannel['rate'];
            $data['channel_id']          = $this->channel['api'];
            $data['t']                   = $tikuanconfig['t1zt'];            
            $data['account_type']        = 3;
            $data['is_mianqian']         = $syschannel['is_mianqian'];
            //开启事物
            if ($Order->add($data)) {
                $return['datetime'] = date('Y-m-d H:i:s', $data['pay_applydate']);
                $return["status"]   = "success";
                return $return;
            } else {
                $this->showmessage('系统错误');
            }
       
    }


     /**
     * 添加测试订单
     * @param $TransID
     * @param $PayName
     * @param int $returntype
     */

    protected function addTestOrder($parameter){
        $pay_amount = I("post.pay_amount", 0);

        //通道信息
        $this->channel = $parameter['channel'];
        
        //$this->merchants = $this->channel['userid'];
        //用户信息
        $usermodel       = D('Member');
        $this->merchants = $usermodel->get_Userinfo($this->channel['userid']);
        $return          = array();
        // 通道名称
        $PayName = $parameter["code"];
        // 交易金额比例
        $moneyratio = $parameter["exchange"];
        //商户编号
        $return["memberid"] = $userid = $this->merchants['id'] + 10000;
        $m_Tikuanconfig     = M('Tikuanconfig');
        $tikuanconfig       = $m_Tikuanconfig->where(['userid' => $this->merchants['id']])->find();
        if (!$tikuanconfig || $tikuanconfig['tkzt'] != 1 || $tikuanconfig['systemxz'] != 1) {
            $tikuanconfig = $m_Tikuanconfig->where(['issystem' => 1])->find();
        }
        //费率
        $_userrate = M('Userrate')
            ->where(["userid" => $this->channel['userid'], "payapiid" => $this->channel['pid']])
            ->find();
        //银行通道费率
        $syschannel = M('Channel')
            ->where(['id' => $this->channel['api']])
            ->find();

        //---------------------------子账号风控start------------------------------------
       $channel_account=M('ChannelAccount')->where(['id'=>$this->channel['channel_account']])->find();
        //-------------------------子账号风控end-----------------------------------------

        $syschannel['mch_id']    = $channel_account['mch_id'];
        $syschannel['signkey']   = $channel_account['signkey'];
        $syschannel['appid']     = $channel_account['appid'];
        $syschannel['appsecret'] = $channel_account['appsecret'];
        $syschannel['account']   = $channel_account['title'];
        $syschannel['zfb_pid']   = $channel_account['zfb_pid'];

        // 定制费率
        if ($channel_account['custom_rate']) {
            $syschannel['defaultrate'] = $channel_account['defaultrate'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['fengding']    = $channel_account['fengding'];
            $syschannel['rate']        = $channel_account['rate'];
        }

        //平台通道
        $platform = M('Product')
            ->where(['id' => $this->channel['pid']])
            ->find();
        if ($channel_account['unlockdomain']) {
            $unlockdomain = $channel_account['unlockdomain'] ? $channel_account['unlockdomain'] : '';
        } else {
            $unlockdomain = $syschannel['unlockdomain'] ? $syschannel['unlockdomain'] : '';
        }

        //回调参数
        $return = [
            "mch_id"       => $syschannel["mch_id"], //商户号
            "signkey"      => $syschannel["signkey"], // 签名密钥
            "appid"        => $syschannel["appid"], // APPID
            "zfb_pid"        => $syschannel["zfb_pid"], // APPID
            "appsecret"    => $syschannel["appsecret"], // APPSECRET
            "gateway"      => $syschannel["gateway"] ? $syschannel["gateway"] : $parameter["gateway"], // 网关
            "notifyurl"    => $syschannel["serverreturn"] ? $syschannel["serverreturn"] : $this->_site . "Pay_" .
            $PayName . "_notifyurl.html",
            "callbackurl"  => $syschannel["pagereturn"] ? $syschannel["pagereturn"] : $this->_site . "Pay_" .
            $PayName . "_callbackurl.html",
            'unlockdomain' => $unlockdomain, //防封域名
        ];

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
        $return["amount"] = floatval($pay_amount) * $moneyratio; // 交易金额
        $pay_sxfamount    = (($pay_amount * $feilv) > ($pay_amount * $fengding)) ? ($pay_amount * $fengding) :
        ($pay_amount * $feilv); // 手续费
        if($this->merchants['has_yck']==1 || $channel_account['account_type']==1){
            $pay_shijiamount = $pay_amount; // 实际到账金额
        }else{
            $pay_shijiamount = $pay_amount - $pay_sxfamount; // 实际到账金额
        }
        
        if ($tikuanconfig['t1zt'] == 0) { //T+0费率
            $cost = bcmul($syschannel['t0rate'], $pay_amount, 2); //计算成本
        } else {
            $cost = bcmul($syschannel['rate'], $pay_amount, 2); //计算成本
        }

        //验证免签预扣款
        if($this->merchants['has_yck']==1 || $channel_account['account_type']==1){
            if($this->merchants['yckbalance']<$pay_sxfamount){
                $this->showmessage('商户预存款不足！');
            } 
        }
        if($channel_account['account_type']==2){
            $symoney=M('member')->where(['id'=>$channel_account['add_user_id']])->getField('balance');
            if($symoney<$pay_amount){
                $this->showmessage('码商额度不足！');
            }
        }

        //商户订单号
        $out_trade_id = $parameter['out_trade_id'];
        //生成系统订单号
        $pay_orderid = $parameter['orderid'] ? $parameter['orderid'] : get_requestord();
        //验签
        
            $Order                       = M("Order");
            $return['bankcode']          = $this->channel['pid'];
            $return['code']              = $platform['code']; //银行英文代码
            $return['orderid']           = $pay_orderid; // 系统订单号
            $return['out_trade_id']      = $out_trade_id; // 外部订单号
            $return['subject']           = $parameter['body']; // 商品标题
            $return['account_id']        = $channel_account['id'];
            $data['pay_memberid']        = $userid;
            $data['pay_orderid']         = $return["orderid"];
            $data['pay_amount']          = $pay_amount; // 交易金额
            $data['pay_poundage']        = $pay_sxfamount; // 手续费
            $data['pay_actualamount']    = $pay_shijiamount; // 到账金额
            $data['pay_applydate']       = time();
            $data['pay_bankcode']        = $this->channel['pid'];
            $data['pay_bankname']        = $platform['name'];
            $data['pay_notifyurl']       = I('post.pay_notifyurl', '');
            $data['pay_callbackurl']     = I('post.pay_callbackurl', '');
            $data['pay_status']          = 0;
            $data['pay_tongdao']         = $syschannel['code'];
            $data['pay_zh_tongdao']      = $syschannel['title'];
            $data['pay_channel_account'] = $syschannel['account'];
            $data['pay_ytongdao']        = $parameter["code"];
            $data['pay_yzh_tongdao']     = $parameter["title"];
            $data['pay_tjurl']           = $_SERVER['HTTP_REFERER'];
            $data['pay_productname']     = I("request.pay_productname");
            $data['attach']              = I("request.pay_attach");
            $data['out_trade_id']        = $out_trade_id;
            $data['ddlx']                = I("post.ddlx", 0);
            $data['memberid']            = $return["mch_id"];
            $data['key']                 = $return["signkey"];
            $data['account']             = $return["appid"];
            $data['cost']                = $cost;
            $data['cost_rate']           = $tikuanconfig['t1zt'] == 0 ? $syschannel['t0rate'] : $syschannel['rate'];
            $data['channel_id']          = $this->channel['api'];
            $data['account_id']          = $channel_account['id'];
            $data['t']                   = $tikuanconfig['t1zt'];
            $data['owner_id']            = $channel_account['add_user_id'];
            $data['account_type']        = $channel_account['account_type'];
            $data['is_mianqian']         = $syschannel['is_mianqian'];
            //开启事物
            M()->startTrans();
            //添加订单
            if ($Order->add($data)) {
                $return['datetime'] = date('Y-m-d H:i:s', $data['pay_applydate']);
                $return["status"]   = "success";
                //码商的话吧余额转到冻结
                if($channel_account['account_type']==2){
                    $complaintsCodeDepositRule = $this->getComplaintsCodeDepositRule($channel_account['add_user_id']);
                    $codeinfo=M("Member")->where(['id'=>$channel_account['add_user_id']])->field('`balance`,`codeblockedbalance`,`id`')->find();
                    $agmoneychange_data = [
                            'userid'     => $channel_account['add_user_id'],
                            'ymoney'     => $codeinfo['balance'], //原金额或原冻结资金
                            'money'      => $pay_amount,
                            'gmoney'     => $codeinfo['balance']-$pay_amount, //改动后的金额或冻结资金
                            'datetime'   => date('Y-m-d H:i:s'),
                            'tongdao'    => $data['pay_bankcode'],
                            'transid'    => $trans_id,
                            'orderid'    => $data['out_trade_id'],
                            'contentstr' => '订单下单存款冻结',
                            'lx'         => 20,
                            't'          => 0,
                    ];
                    $depositResult = M('ComplaintsCodedeposit')->add([
                        'user_id'       => $channel_account['add_user_id'],
                        'pay_orderid'   => $data['pay_orderid'],
                        'out_trade_id'  => $data['out_trade_id'],
                        'freeze_money'  => $pay_amount,
                        'unfreeze_time' => time() + $complaintsCodeDepositRule['freeze_time'],
                        'status'        => 0,
                        'create_at'     => time(),
                        'update_at'     => time(),
                    ]);
                    if ($depositResult == false) {
                        M()->rollback();
                        $this->showmessage('系统错误');
                    }

                    $member_data2['balance'] = ['exp', 'balance-' . $pay_amount]; //防止数据库并发脏读
                    $member_data2['codeblockedbalance'] = ['exp', 'codeblockedbalance+' . $pay_amount]; //防止数据库并发脏读
                    $member_result = M("Member")->where(['id' => $channel_account['add_user_id']])->save($member_data2);
                    if ($member_result != 1) {
                        M()->rollback();
                        $this->showmessage('系统错误');
                    }else{
                        $moneychange_result = $this->MoenyChange($agmoneychange_data); // 资金变动记录
                        if ($moneychange_result == false) {
                            M()->rollback();
                            $this->showmessage('系统错误');
                        }
                    }
                    

                }
                M()->commit();
                return $return;
            } else {
                M()->rollback();
                $this->showmessage('系统错误');
            }
       
    }

    /**
     * 回调处理订单
     * @param $TransID
     * @param $PayName
     * @param int $returntype
     */
    protected function EditMoney($trans_id, $pay_name = '', $returntype = 1, $transaction_id = '')
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
            $res = $m_Order->where(['pay_orderid' => $trans_id, 'pay_status' =>['in', '0,3,4']])
                ->save(['pay_status' => 1, 'pay_successdate' => $time]);
            if (!$res) {
                M()->rollback();
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
                if($order_info['account_type']==2){
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
                            $code_fenxiaodata['money']=$order_info["pay_amount"];
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
                        M()->rollback();
                        return false;
                    }
                    $moneychange_result = $this->MoenyChange($agmoneychange_data); // 资金变动记录
                    if ($moneychange_result == false) {
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
    }

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
            $data['paying_money']     = bcadd($info['paying_money'], $pay_amount, 4);
            $data['last_paying_time'] = time();

            if ($data['paying_money'] >= $info['all_money']) {
                $data['offline_status'] = 0;
            }
            return $model->where(['id' => $id])->save($data);
        }
        return true;
    }
    /**
     *  验证码商账户
     * @return bool
     */
    protected function checkAuthCode($channel_account_list,$amount){
        foreach($channel_account_list as $k=>$v){
            if($v['account_type']==2){
                $memberid=$v['add_user_id'];
                $balance=M('member')->where(['id'=>$memberid])->getField('balance');
                if($balance<$amount){
                    unset($channel_account_list[$k]);
                }

            }
        }
        return $channel_account_list;
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

    public function ceshiorder(){
        // echo 111;exit;
        $res=$this->EditMoney('20190426235615102571', 'AliZz', 0);
        var_dump($res);
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
                    $parent = M('Member')->where(['id' => $parentid])->field('id,balance')->find();
                    if (empty($parent)) {
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
        $order  = M('Order')->where(['pay_orderid' => $trans_id])->find();
        // var_dump($order);exit;
        //用户费率
        $userrate = M("Userrate")->where(["userid" => $userid, "payapiid" => $payapiid])->find();
        //支付通道费率
        $syschannel = M('Channel')->where(['id' => $order['channel_id']])->find();
        if ($order['t'] == 0) { //T+0费率
            $feilv    = $userrate['t0feilv'] ? $userrate['t0feilv'] : $syschannel['t0defaultrate']; // 交易费率
            $fengding = $userrate['t0fengding'] ? $userrate['t0fengding'] : $syschannel['t0fengding']; // 封顶手续费
        } else { //T+1费率
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

    public function bufa()
    {

        header('Content-type:text/html;charset=utf-8');
        $TransID    = I("get.TransID");
        $PayName    = I("get.tongdao");
        $m          = M("Order");
        $pay_status = $m->where(array("pay_orderid" => $TransID))->getField("pay_status");
        if (intval($pay_status) == 1) {
            echo ("订单号：" . $TransID . "|" . $PayName . "已补发服务器点对点通知，请稍后刷新查看结果！<a href='javascript:window.close();'>关闭</a>");
            $this->EditMoney($TransID, $PayName, 0);
        } else {
            echo "补发失败";
        }

    }

    /**
     * 扫码订单状态检查
     *
     */
    public function checkstatus()
    {
        $orderid = I("post.orderid/s");
        $Order   = M("Order");
        $order   = $Order->where(array('pay_orderid' => $orderid))->find();
        if ($order['pay_status'] == 1||$order['pay_status'] == 2) {
            echo json_encode(array('status' => 'ok', 'callback' => $this->_site . "Pay_" . $order['pay_tongdao'] . "_callbackurl.html?orderid="
                . $orderid . "&pay_memberid=" . $order['pay_memberid'] . '&bankcode=' . $order['pay_bankcode']));
            exit();
        } else {
            exit("no-$orderid");
        }
    }

    /**
     * 错误返回
     * @param string $msg
     * @param array $fields
     */
    protected function showmessage($msg = '', $fields = array())
    {
        header('Content-Type:application/json; charset=utf-8');
        $data = array('status' => 'error', 'msg' => $msg, 'data' => $fields);
        echo json_encode($data, 320);
        exit;
    }

    /**
     * 来路域名检查
     * @param $pay_memberid
     */
    protected function domaincheck($pay_memberid)
    {
        $referer      = $_SERVER["HTTP_REFERER"]; // 获取完整的来路URL
        $domain       = $_SERVER['HTTP_HOST'];
        $pay_memberid = intval($pay_memberid) - 10000;
        $User         = M("User");
        $num          = $User->where(["id" => $pay_memberid])->count();
        if ($num <= 0) {
            $this->showmessage("商户编号不存在");
        } else {
            $websiteid     = $User->where(["id" => $pay_memberid])->getField("websiteid");
            $Websiteconfig = M("Websiteconfig");
            $websitedomain = $Websiteconfig->where(["websiteid" => $websiteid])->getField("domain");

            if ($websitedomain != $domain) {
                $Userverifyinfo = M("Userverifyinfo");
                $domains        = $Userverifyinfo->where(["userid" => $pay_memberid])->getField("domain");
                if (!$domains) {
                    $this->showmessage("域名错误 ");
                } else {
                    $arraydomain = explode("|", $domains);
                    $checktrue   = true;
                    foreach ($arraydomain as $key => $val) {
                        if ($val == $domain) {
                            $checktrue = false;
                            break;
                        }
                    }
                    if ($checktrue) {
                        $this->showmessage("域名错误 ");
                    }
                }
            }
        }
    }

    protected function getParameter($title, $channel, $className, $exchange = 1)
    {
        if (substr_count($className, 'Controller')) {
            $length = strlen($className) - 25;
            $code   = substr($className, 15, $length);
        }
        $parameter = array(
            'code'         => $code, // 通道名称
            'title'        => $title, //通道名称
            'exchange'     => $exchange, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => I('request.pay_orderid', ''), //外部订单号
            'channel'      => $channel,
            'body'         => I('request.pay_productname', ''),
        );
        $return = $this->orderadd($parameter);
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);

        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"]     = $this->_site . 'Pay_' . $code . '_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_' . $code . '_callbackurl.html';
        return $return;
    }

    protected function showQRcode($url, $return, $view = 'weixin')
    {
        import("Vendor.phpqrcode.phpqrcode", '', ".php");
        $QR = "Uploads/codepay/" . $return["orderid"] . ".png"; //已经生成的原始二维码图
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", $this->_site . $QR);
        $this->assign('params', $return);
        $this->assign('orderid', $return['orderid']);
        $this->assign('money', $return['amount']);
        $this->display("WeiXin/" . $view);
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
    /**
     * 获取码商保证金设置
     * @param $userid
     * @return array
     */
    private function getComplaintsCodeDepositRule($userid)
    {
        $complaintsDepositRule = M('ComplaintsCodedepositRule')->where(['user_id' => $userid])->find();
        if (!$complaintsDepositRule || $complaintsDepositRule['status'] != 1) {
            $complaintsDepositRule = M('ComplaintsCodedepositRule')->where(['is_system' => 1])->find();
        }
        return $complaintsDepositRule ? $complaintsDepositRule : [];
    }
}
