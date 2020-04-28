<?php
namespace Admin\Controller;

use Think\Page;

class ChannelController extends BaseController
{
    protected $at;
    public function __construct()
    {
        parent::__construct();
        $this->assign("Public", MODULE_NAME); // 模块名称
        $this->assign('paytypes', C('PAYTYPES'));
        $this->at = C('ZFB');//获取支付宝的数组数据
        //通道
        $channels = M('Channel')
            ->where(['status' => 1])
            ->field('id,code,title,paytype,status')
            ->select();
        $this->assign('channels', $channels);
        $this->assign('channellist', json_encode($channels));
    }

    //供应商接口列表
    public function index()
    {
        $count = M('Channel')->count();
        $size  = 15;
        $rows  = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Page = new Page($count, $rows);
        $data = M('Channel')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id DESC')
            ->select();
        $this->assign('rows', $rows);
        $this->assign('list', $data);
        $this->assign('page', $Page->show());
        $this->display();
    }

    /**
     * 保存编辑供应商
     */
    public function saveEditSupplier()
    {
        if (IS_POST) {
            $id                       = I('post.id', 0, 'intval');
            $papiacc                  = I('post.pa/a');
            $_request['code']         = trim($papiacc['code']);
            $_request['title']        = trim($papiacc['title']);
            $_request['mch_id']       = trim($papiacc['mch_id']);
            $_request['signkey']      = trim($papiacc['signkey']);
            $_request['appid']        = trim($papiacc['appid']);
            $_request['appsecret']    = trim($papiacc['appsecret']);
            $_request['gateway']      = trim($papiacc['gateway']);
            $_request['pagereturn']   = $papiacc['pagereturn'];
            $_request['serverreturn'] = $papiacc['serverreturn'];
            $_request['defaultrate']  = $papiacc['defaultrate'] ? $papiacc['defaultrate'] : 0;
            $_request['fengding']     = $papiacc['fengding'] ? $papiacc['fengding'] : 0;
            $_request['rate']         = $papiacc['rate'] ? $papiacc['rate'] : 0;
            $_request['t0defaultrate']  = $papiacc['t0defaultrate'] ? $papiacc['t0defaultrate'] : 0;
            $_request['t0fengding']     = $papiacc['t0fengding'] ? $papiacc['t0fengding'] : 0;
            $_request['t0rate']         = $papiacc['t0rate'] ? $papiacc['t0rate'] : 0;
            $_request['updatetime']   = time();
            $_request['unlockdomain'] = $papiacc['unlockdomain'];
            $_request['paytype']      = $papiacc['paytype'];
            $_request['status']       = $papiacc['status'];

            if ($id) {
                //更新
                $res = M('Channel')->where(array('id' => $id))->save($_request);
            } else {
                //添加
                $res = M('Channel')->add($_request);
            }
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //开启供应商接口
    public function editStatus()
    {
        if (IS_POST) {
            $pid    = intval(I('post.pid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('Channel')->where(['id' => $pid])->save(['status' => $isopen]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

     //开启供应商接口
    public function editIsMianqian()
    {
        if (IS_POST) {
            $pid    = intval(I('post.pid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('Channel')->where(['id' => $pid])->save(['is_mianqian' => $isopen]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

     //开启供应商接口
    public function AgentCanSh()
    {
        if (IS_POST) {
            $pid    = intval(I('post.pid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('Channel')->where(['id' => $pid])->save(['agent_can_sh' => $isopen]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

     //开启供应商接口
    public function AutoPaofen()
    {
        if (IS_POST) {
            $pid    = intval(I('post.pid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('Channel')->where(['id' => $pid])->save(['auto_paofen' => $isopen]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

     //开启供应商接口
    public function UnitSamemoneyStatus()
    {
        if (IS_POST) {
            $pid    = intval(I('post.pid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('Channel')->where(['id' => $pid])->save(['unit_samemoney_status' => $isopen]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //新增供应商接口
    public function addSupplier()
    {
        $this->display();
    }

    //编辑供应商接口
    public function editSupplier()
    {
        $pid = intval($_GET['pid']);
        if ($pid) {
            $pa = M('Channel')->where(['id' => $pid])->find();
        }
        $this->assign('pa', $pa);
        $this->display('addSupplier');
    }
    //删除供应商接口
    public function delSupplier()
    {
        $pid = I('post.pid', 0, 'intval');
        if ($pid) {
            // 删除子账号
            M('channel_account')->where(['channel_id' => $pid])->delete();
            $res = M('Channel')->where(['id' => $pid])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //编辑费率
    public function editRate()
    {
        if (IS_POST) {
            $pa = I('post.pa/a');
            $pid = I('post.pid', 0, 'intval');
            if ($pid) {
                $res       = M('Channel')->where(['id' => $pid])->save($pa);
                $pa['pid'] = $pid;
                $this->ajaxReturn(['status' => $res, 'data' => $pa]);
            }
        } else {
            $pid = intval(I('get.pid'));
            if ($pid) {
                $data = M('Channel')->where(['id' => $pid])->find();
            }

            $this->assign('pid', $pid);
            $this->assign('pa', $data);
            $this->display();
        }
    }

    //产品列表
    public function product()
    {
        $data = M('Product')->select();
        $this->assign('list', $data);
        $this->display();
    }

    //切换产品状态
    public function prodStatus()
    {
        if (IS_POST) {
            $id    = I('post.id', 0, 'intval');
            $colum = I('post.k');
            $value = I('post.v');
            $res   = M('Product')->where(['id' => $id])->save([$colum => $value]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //切换用户显示状态
    public function prodDisplay()
    {
        if (IS_POST) {
            $id    = I('post.id', 0, 'intval');
            $colum = I('post.k');
            $value = I('post.v');
            $res   = M('Product')->where(['id' => $id])->save([$colum => $value]);
            $this->ajaxReturn(['status' => $res]);
        }
    }
    //添加产品
    public function addProduct()
    {
        $this->display();
    }

    //编辑产品
    public function editProduct()
    {
        $id   = I('get.pid', 0, 'intval');
        $data = M('Product')->where(['id' => $id])->find();

        //权重
        $weights    = [];
        $weights    = explode('|', $data['weight']);
        $_tmpWeight = '';
        if (is_array($weights)) {
            foreach ($weights as $value) {
                list($pid, $weight) = explode(':', $value);
                if ($pid) {
                    $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
                }
            }
        } else {
            list($pid, $weight) = explode(':', $data['weight']);
            if ($pid) {
                $_tmpWeight[$pid] = ['pid' => $pid, 'weight' => $weight];
            }
        }
        $data['weight'] = $_tmpWeight;
        //通道
        $channels = M('Channel')->where(["paytype" => $data['paytype'], "status" => 1])->select();
        $this->assign('channels', $channels);
        $this->assign('pd', $data);
        $this->display('addProduct');
    }

    //保存更改
    public function saveProduct()
    {
        if (IS_POST) {
            $id     = intval(I('post.id'));
            $rows   = I('post.pd/a');
            $weight = I('post.w/a');
            //权重
            $weightStr = '';
            if (is_array($weight)) {
                foreach ($weight as $weigths) {
                    if ($weigths['pid']) {
                        $weightStr .= $weigths['pid'] . ':' . $weigths['weight'] . "|";
                    }
                }
            }
            $rows['weight'] = trim($weightStr, '|');
            //保存
            if ($id) {
                $res = M('Product')->where(['id' => $id])->save($rows);
            } else {
                $res = M('Product')->add($rows);
            }
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //删除产品
    public function delProduct()
    {
        if (IS_POST) {
            $id  = I('post.pid', 0, 'intval');
            $res = M('Product')->where(['id' => $id])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //接口模式
    public function selProduct()
    {
        if (IS_POST) {
            $paytyep = I('post.paytype', 0, 'intval');
            //通道
            $data = M('Channel')->where(["paytype" => $paytyep, "status" => 1])->select();
            $this->ajaxReturn(['status' => 0, 'data' => $data]);
        }
    }

    /**
     * 通道账户列表
     */
    public function account()
    {
        $channel_id = I('get.pid');
        $channel    = M('Channel')->where(['id' => $channel_id])->find();
        $accounts   = M('channel_account')->where(['channel_id' => $channel_id])->select();
        $this->assign('channel', $channel);
        $this->assign('accounts', $accounts);
        $this->display();
    }

    /**
     * 编辑账户
     */
    public function editAccountControl()
    {
        if (IS_POST) {
            $data = I('post.data', '');

            if ($data['start_time'] != 0 || $data['end_time'] != 0) {
                if ($data['start_time'] >= $data['end_time']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '交易结束时间不能小于开始时间！']);
                }
            }
            if ($data['max_money'] != 0 && $data['min_money'] != 0) {
                if ($data['min_money'] >= $data['max_money']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '最大交易金额不能小于或等于最小金额！']);
                }
            }
            if ($data['is_defined'] == 0) {
                $channel_id = M('ChannelAccount')->where(['id' => $data['id']])->getField('channel_id');
                $channelInfo = M('Channel')->where(['id' => $channel_id])->find();
                $data['offline_status'] = $channelInfo['offline_status'];
                $data['control_status'] = $channelInfo['control_status'];
            }
            $res = M('ChannelAccount')->where(['id' => $data['id']])->save($data);
            $this->ajaxReturn(['status' => $res]);
        } else {
            $aid  = I('get.aid', '', 'intval');
            $info = M('ChannelAccount')->where(['id' => $aid])->find();

            $this->assign('info', $info);
            $this->assign('aid', $aid);
            $this->display();
        }

    }

    /**
     * 编辑账户
     */
    public function editAccount()
    {
        $aid = intval($_GET['aid']);
        if ($aid) {
            $pa = M('channel_account')->where(['id' => $aid])->find();
        }
        $this->assign('pa', $pa);
        $this->assign('pid', $pa['channel_id']);
        $this->display('addAccount');
    }

    /**
     * 新增账户
     */
    public function addAccount()
    {
        $pid = intval($_GET['pid']);
        $this->assign('pid', $pid);
        $this->display('addAccount');
    }

    public function showEven()
    {
        // echo "<pre>";
        $channelList = M('Channel')->where(['control_status' => 1, 'status' => 1])->select();
        $accountList = M('ChannelAccount')->where(['control_status' => 1, 'status' => 1])->select();

        $list = [];
        foreach ($channelList as $k => $v) {
            $v['offline_status'] = $v['offline_status'] ? '上线' : '下线';
            $list[$k]            = $v;
            foreach ($accountList as $k1 => $v1) {
                if ($v1['channel_id'] == $v['id']) {
                    $v1['offline_status']  = $v1['offline_status'] ? '上线' : '下线';
                    $list[$k]['account'][] = $v1;
                }
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 保存账户
     */
    public function saveEditAccount()
    {
        if (IS_POST) {
            $id                     = I('post.id', 0, 'intval');
            $papiacc                = I('post.pa/a');
            $_request['title']      = trim($papiacc['title']);
            $_request['channel_id'] = trim($papiacc['pid']);
            $_request['mch_id']     = trim($papiacc['mch_id']);
            //自己添加支付宝分账使用 
            $_request['zfb_pid']     = trim($papiacc['zfb_pid']);
            $_request['signkey']    = trim($papiacc['signkey']);
            $_request['appid']      = trim($papiacc['appid']);
            $_request['appsecret']  = trim($papiacc['appsecret']);
            // 默认为1
            $weight                     = trim($papiacc['weight']);
            $_request['weight']         = $weight === '' ? 1 : $weight;
            $_request['custom_rate']    = $papiacc['custom_rate'];
            $_request['defaultrate']    = $papiacc['defaultrate'] ? $papiacc['defaultrate'] : 0;
            $_request['fengding']       = $papiacc['fengding'] ? $papiacc['fengding'] : 0;
            $_request['rate']           = $papiacc['rate'] ? $papiacc['rate'] : 0;
            $_request['t0defaultrate']    = $papiacc['t0defaultrate'] ? $papiacc['t0defaultrate'] : 0;
            $_request['t0fengding']       = $papiacc['t0fengding'] ? $papiacc['t0fengding'] : 0;
            $_request['t0rate']           = $papiacc['t0rate'] ? $papiacc['t0rate'] : 0;
            $_request['updatetime']     = time();
            $_request['status']         = $papiacc['status'];
            $_request['is_defined']     = $papiacc['is_defined'];
            $_request['all_money']      = $papiacc['all_money'] == '' ? 0:$papiacc['all_money'];
            $_request['min_money']      = $papiacc['min_money'] == '' ? 0:$papiacc['min_money'];
            $_request['max_money']      = $papiacc['max_money'] == '' ? 0:$papiacc['max_money'];
            $_request['start_time']     = $papiacc['start_time'];
            $_request['end_time']       = $papiacc['end_time'];
            $_request['offline_status'] = $papiacc['offline_status'];
            $_request['control_status'] = $papiacc['control_status'];
            $_request['unlockdomain'] = $papiacc['unlockdomain'];
            if ($id) {
                //更新
                $res = M('channel_account')->where(array('id' => $id))->save($_request);
            } else {
                //添加
                $res = M('channel_account')->add($_request);
            }
            $this->ajaxReturn(['status' => $res,'msg'=>M()->getDbError()]);
        }
    }

    //开启供应商接口
    public function editAccountStatus()
    {
        if (IS_POST) {
            $aid    = intval(I('post.aid'));
            $isopen = I('post.isopen') ? I('post.isopen') : 0;
            $res    = M('channel_account')->where(['id' => $aid])->save(['status' => $isopen]);
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //删除供应商接口
    public function delAccount()
    {
        $aid = I('post.aid', 0, 'intval');
        if ($aid) {
            $res = M('channel_account')->where(['id' => $aid])->delete();
            $separate = M('separate')->where(['channel_account_id'=> $aid])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }

    //编辑费率
    public function editAccountRate()
    {
        if (IS_POST) {
            $pa = I('post.pa');
            $accountId = I('post.aid');
            if ($accountId) {
                $res       = M('channel_account')->where(['id' => $accountId])->save($pa);
                $pa['aid'] = $accountId;
                $this->ajaxReturn(['status' => $res, 'data' => $pa]);
            }
        } else {
            $aid = intval(I('get.aid'));
            if ($aid) {
                $data = M('channel_account')->where(['id' => $aid])->find();
            }

            $this->assign('aid', $aid);
            $this->assign('pa', $data);
            $this->display();
        }
    }

    //编辑风控
    public function editControl()
    {
        if (IS_POST) {
            $data = I('post.data', '');
            if ($data['start_time'] != 0 || $data['end_time'] != 0) {
                if ($data['start_time'] >= $data['end_time']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '交易结束时间不能小于开始时间！']);
                }
            }
            if ($data['max_money'] != 0 && $data['min_money'] != 0) {
                if ($data['min_money'] >= $data['max_money']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '最大交易金额不能小于或等于最小金额！']);
                }
            }
            $res = M('Channel')->where(['id' => $data['id']])->save($data);
            $this->ajaxReturn(['status' => $res]);
        } else {
            $pid  = I('get.pid', '');
            $info = M('Channel')->where(['id' => $pid])->find();
            $this->assign('info', $info);
            $this->assign('pid', $pid);
            $this->display();
        }
    }
    //柒上网络分账
    public function qiswlSeparate(){
        $aid = intval($_GET['aid']);
        $todayBegin = date('Y-m-d').' 00:00:00';
        $time=strtotime($todayBegin);
        $orderWhere['pay_successdate'] = array('gt',$time);
        $orderWhere['account_id'] = $aid;
        $totalmoney=M('order')->where($orderWhere)->sum('pay_amount');
        $this->assign('totalmoney',$totalmoney);
        $this->assign('aid',$aid);
        $this->display();
    }

    public function saveQiswlSeparate(){
        $data['aid']=I('post.aid/s');
        $data['money']=I('post.money/s');
        $data['pid']=I('post.pid/s');
        $this->at = C('ZFB');

        Vendor("Alipay.aop.SignData");
        Vendor("Alipay.aop.AopClient");
        Vendor("Alipay.aop.request.AlipayTradeOrderSettleRequest");
        // $aid = $data['aid'];
        $aid = 343;
            $money = $data['money'];
            $zfb_pid = $data['pid'];
            $zfb_pid="2088432628402085";

            // 查询当前订单的信息
            $trade_no=time().rand(1000,9999);
            // 子账户信息
            $info = M("ChannelAccount")->where(['id'=>$aid])->field('title,mch_id,appid,appsecret,zfb_pid,signkey')->find();
            var_dump($info);
            $key = $info['signkey'];
            $trans_out =$info['zfb_pid'];

                $amount = $money;
                $zfb_amount = $amount*0.006;
                $zfb_amount = ceil($zfb_amount*100);
                // var_dump($zfb_amount);exit;
                $aop = new \AopClient();
                $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                $aop->appId = $info['mch_id'];
                $aop->rsaPrivateKey = $info['appsecret'];
                $aop->alipayrsaPublicKey= $info['signkey'];
                $aop->apiVersion = '1.0';
                $aop->signType = 'RSA2';
                $aop->postCharset='UTF-8';
                $aop->format='json';
                $request = new \AlipayTradeOrderSettleRequest ();
                $royalty_parameters[0]['trans_out']="2088232897922169";
                $royalty_parameters[0]['trans_in']="2088432628402085";
                // $royalty_parameters[0]['amount']="0.2";
                $royalty_parameters[0]['amount_percentage']="100";
                $royalty_parameters[0]['desc']="分账给2088432628402085";

                $datas['out_request_no']="20190525011213100525";
                $datas['trade_no']="2019052522001452321037531087";
                $datas['royalty_parameters']=$royalty_parameters;
                $param = json_encode($datas);
                 echo "<pre>";
                var_dump($param);
                echo "<br/>";
                $request->setBizContent($param);
                // $request->setBizContent("{" .
                // "\"out_request_no\":\"20190524192617575553\"," .
                // "\"trade_no\":\"2019052422001452321037521913\"," .
                // "      \"royalty_parameters\":[{" .
                // "        \"trans_out\":\"2088902113290717\"," .
                // "\"trans_in\":\"2088432628402085\"," .
                // "\"amount\":0.6," .

                // "\"desc\":\"分账给2088432628402085\"" .
                // "        }]," .
                // "\"operator_id\":\"A0001\"" .
                // "  }");
                // var_dump($request);exit;
                $result = $aop->execute($request);
                var_dump($result);exit;
                $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
                $resultCode = $result->$responseNode->code;
                if(!empty($resultCode)&&$resultCode == 10000){
                    echo "成功";
                } else {
                    echo "失败";
                }

    }

   // 自己添加支付宝分账使用
    public function editSeparate(){
        $aid = intval($_GET['aid']);
      
        $fzctrl = M("ChannelAccount")->where(['id'=>$aid])->field('fenzhuanzhang')->find();
        
        if($fzctrl['fenzhuanzhang']==1){
          $separate_info = M("Separate")->where(['channel_account_id'=>$aid])->select();
          $this->assign([
            'aid' => $aid,
            'separate_info' => $separate_info,
          ]);
        } else {
          $this->assign([
            'aid' => $aid
          ]);
        }
        
        // dump($separate_info);

        $this->display();
    }
  
    public function editTransfer(){
        $aid = intval($_GET['aid']);
        $fzctrl = M("ChannelAccount")->where(['id'=>$aid])->field('fenzhuanzhang')->find();
      
        if($fzctrl['fenzhuanzhang']==2){
          $separate_info = M("Separate")->where(['channel_account_id'=>$aid])->select();
          $this->assign([
            'aid' => $aid,
            'separate_info' => $separate_info,
          ]);
        } else {
          $this->assign([
            'aid' => $aid
          ]);
        }

        $this->assign([
            'aid' => $aid,
            'separate_info' => $separate_info,
        ]);
        $this->display();
    }


    public function saveSeparate(){
        $pa = I('post.pa');
        $aid = I('post.aid');
        // dump($pa);
        
        $fzctrl = I('post.fzctrl');
      
        foreach ($pa as $k => $p) {
            $now_date = time();
            $p['channel_account_id'] = $aid;
            $p['zfb_pid'] = trim($p['zfb_pid']);

            // 先判断是添加、修改还是删除
            if($p['id']==0){ //添加
                if(!empty($p['zfb_pid'])){
                    unset($p['id']);
                    $p['created_at'] = $now_date;
                    $res_i = M("Separate")->add($p);
                    $updateData['fenzhuanzhang']=$fzctrl;
                    $condition['id']=$aid;
                    $res = M("ChannelAccount")->where($condition)->save($updateData);
                    //dump($res);
                    //die;
                    if(!$res_i){
                        $this->ajaxReturn(['status' => 0]);
                    }
                }
            }else{ 

                if(!empty($p['zfb_pid'])){ //修改
                    $p['updated_at'] = $now_date;

                    $res_u = M("Separate")->where(['id'=>$p['id']])->save($p);
                    $updateData['fenzhuanzhang']=$fzctrl;
                    $condition['id']=$aid;

                    $res = M("ChannelAccount")->where($condition)->save($updateData);
                    //dump($res);
                    //die;
                    if(!$res_u){
                        $this->ajaxReturn(['status' => 0]);
                    }
                }else{ //删除
                    $res_d = M('Separate')->where(['id' => $p['id']])->delete();
                    $updateData['fenzhuanzhang']=0;
                    $condition['id']=$aid;

                    $res = M("ChannelAccount")->where($condition)->save($updateData);
                    //dump($res);
                    //die;
                    if(!$res_d){
                        
                        $this->ajaxReturn(['status' => 0]);
                    }
                }
            }
        }
        $this->ajaxReturn(['status' => 1]);
    }
    // 自己添加，固定子账户
    public function fixedaccopunt(){
        // 固定支付宝账号
            $zfb_pid = I('zfb_pid');

            // 得到参数
            $ids = I('ids');
            $seprates = I('seprates');
            $sepids = I('sepids');

            if(!empty($zfb_pid)){

                for($i=0;$i<count($sepids);$i++) {
                    if(empty($sepids[$i])){ //添加
                        $data['member_id'] = $ids[$i];
                        $data['channel_account_id'] = 0;
                        $data['zfb_pid'] = $zfb_pid;
                        $data['rate'] = $seprates[$i];
                        $data['created_at'] = time();
                        $res = M('Separate')->add($data);
                    }else{ //修改
                        $update_info = M("Separate")->where(['id'=>$sepids[$i]])->find();
    
                        $data['zfb_pid'] = $zfb_pid;
                        $data['rate'] = $seprates[$i];
                        $data['updated_at'] = time();
                        $res = M("Separate")->where(['id'=>$update_info['id']])->save($data);
                        $res_all = M("Separate")->where(['pid'=>$update_info['id'],'zfb_pid'=> $update_info['zfb_pid']])->save($data);
                    }
                }
                if($res){
                    $this->ajaxReturn(['status' => $res]);
                }else{
                    $this->ajaxReturn(['status' => 0]);
                }
            }

        // 查询固定子账户信息
        $fixed_info = M("Separate")->where(['channel_account_id'=>0])->field('id,zfb_pid')->find();

        // 查询高级代理
        $member_info = M("Member")->where(['groupid'=>7])->field('id,username')->order("username")->select();

        foreach ($member_info as $ke => $va) {
            // 查询每个高级代理固定的子账户
            $sep_info = M("Separate")->where(['channel_account_id'=>0,'member_id'=>$va['id']])->find();

            $member_info[$ke]['sep_id'] = $sep_info['id'];
            $member_info[$ke]['rate'] = $sep_info['rate'];
        }
        // dump($member_info);


        $this->assign([
            'fixed_info' => $fixed_info,
            'member_info' => $member_info,
        ]);
        $this->display();
    }

    
}
