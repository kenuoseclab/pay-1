<?php
namespace User\Controller;

/**
 * 支付通道控制器
 * Class ChannelController
 * @package User\Controller
 */
class ChannelController extends UserController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 通道费率
     */
    public function index()
    {
        //已开通通道
        $list = M('ProductUser')
            ->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id = __PRODUCT_USER__.pid')
            ->where(['pay_product_user.userid'=>$this->fans['uid'],'pay_product_user.status'=>1,'pay_product.isdisplay'=>1])
            ->field('pay_product.name,pay_product.id,pay_product_user.status')
            ->select();

        foreach ($list as $key=>$item){
            $feilv = M('Userrate')->where(['userid'=>$this->fans['uid'],'payapiid'=>$item['id']])->field('feilv,t0feilv,fengding,t0fengding')->select();
            $list[$key]['feilv'] = $feilv[0]['feilv'];
            $list[$key]['t0feilv'] = $feilv[0]['t0feilv'];
            $list[$key]['fengding'] = $feilv[0]['fengding'];
            $list[$key]['t0fengding'] = $feilv[0]['t0fengding'];
        }

        //结算方式：
        $tkconfig = M('Tikuanconfig')->where(['userid'=>$this->fans['uid']])->find();
        if(!$tkconfig || $tkconfig['tkzt']!=1){
            $tkconfig = M('Tikuanconfig')->where(['issystem'=>1])->find();
        }

        $this->assign('tkconfig',$tkconfig);
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 开发文档
     */
    public function apidocumnet()
    {
        if($this->fans['groupid'] != 4) {
            $this->error('没有权限！');
        }
        $sms_is_open = smsStatus();//短信开启状态
        $info = M('Member')->where(['id'=>$this->fans['uid']])->find();
        $this->assign('sms_is_open',$sms_is_open);
        $this->assign('mobile', $this->fans['mobile']);
        $this->assign('info',$info);
        $this->display();
    }

    public function apikey()
    {
        $code = I('request.code');
        $res = check_auth_error($this->fans['uid'], 6);
        if(!$res['status']) {
            $this->ajaxReturn(['status' => 0, 'msg' => $res['msg']]);
        }
        $data = M('Member')->field('paypassword')->where(['id'=>$this->fans['uid']])->find();
        if(md5($code) != $data['paypassword']){
            log_auth_error($this->fans['uid'],6);
            $this->ajaxReturn(['status'=>0,'msg'=>'支付密码错误']);
        } else {
            clear_auth_error($this->fans['uid'],6);
        }
        $apikey = M('Member')->where(['id'=>$this->fans['uid']])->getField('apikey');
        $this->ajaxReturn(['status' => 1, 'apikey' => $apikey]);
    }
  
  
  	// 自己添加
    // 子账户展示
    public function channelAccount(){
        // echo "这是一个子账户添加";
        $account_code = I("request.id");
        $zfb = M("Channel")->where(['id'=>$account_code])->field("id")->find();
        $accounts = M("ChannelAccount")->where(['channel_id'=>$zfb['id'],'add_user_id'=>$this->fans['uid']])->select();
        $this->assign('channel',$zfb);
        $this->assign('uid',$this->fans['uid']);
        $this->assign('accounts',$accounts);
        $this->display();
    }

    //高级代理上号
    public function AgentAccount(){
        $productUserList = M('ProductUser')->where(['userid' => $this->fans['uid'], 'status' => 1])->select();
        //因为有些渠道是轮询的，要将所有渠道id处理出来
        $channelIds = [];
        foreach ($productUserList as $k => $v) {
            if ($v['polling']) {
                $channls = explode('|', $v['weight']);
                foreach ($channls as $k1 => $v1) {
                    $channelIds[] = rtrim($v1, ':');
                }
            } else {
                $channelIds[] = $v['channel'];
            }
        }
        $channelIds = $channelIds ? $channelIds : [0];
        $channelList = M('channel')->field('id,title')->where(['id' => ['in', $channelIds], 'status' => 1,'agent_can_sh'=>1])->select();
        //用户渠道选择
        $canneltype=M('UserChannelType')->where(['uid'=>$this->fans['uid']])->select();
        $ctype=array();
        if($canneltype){
            foreach($canneltype as $v){
                $ctype[$v['channel_id']]=$v['type'];
            }
        }
        foreach($channelList as $k=>$v){
            if($ctype[$v['id']]){
                $channelList[$k]['ctype']=$ctype[$v['id']];
            }else{
                $channelList[$k]['ctype']=0;
            }
        }
        $this->assign('list',$channelList);
        $this->display();
    }

    //
    public function changeCtype(){
        if (IS_POST) {
            $where['channel_id']   = intval(I('post.uid'));
            $where['uid']=$this->fans['uid'];
            $data=M('UserChannelType')->where($where)->find();
            if($data){
                $save['type']=I('post.isopen') ? I('post.isopen') : 0;
                $res=M('UserChannelType')->where($where)->save($save);
            }else{
                $where['type']=I('post.isopen') ? I('post.isopen') : 0;
                $res=M('UserChannelType')->add($where);
            }

            $this->ajaxReturn(['status' => $res]);
        }
    }

    // 修改
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

    // 添加
    public function addAccount()
    {
        $pid = intval($_GET['pid']); //231
        $this->assign('pid', $pid);
        $this->display('addAccount');
    }


    // 保存
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
            $_request['add_user_name'] = $this->fans['username'];
            $_request['add_user_id'] = $this->fans['uid'];
            $_request['account_type'] = 1;
            if ($id) {
                //更新
                $res = M('channel_account')->where(array('id' => $id))->save($_request);
            } else {
                //添加
                $res = M('channel_account')->add($_request);
                
                
                $fixed_info = M('separate')->where(['member_id'=>$this->fans['uid']])->find();
                if(!empty($fixed_info)){
                  $separate_data = [
                      'channel_account_id' => $res,
                      'zfb_pid' => $fixed_info['zfb_pid'],
                      'rate' => $fixed_info['rate'],
                      'pid' => $fixed_info['id'],
                      'created_at' => time()
                  ];
                  $separate = M('separate')->add($separate_data);
                }
            }
            $this->ajaxReturn(['status' => $res]);
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


    // 风控
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

    
    //分账
    public function editSeparate(){
        $aid = intval($_GET['aid']);
        $separate_info = M("Separate")->where(['channel_account_id'=>$aid])->order("id asc")->select();
        // dump($separate_info);

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
                    if(!$res_i){
                        $this->ajaxReturn(['status' => 0]);
                    }
                }
            }else{ 

                if(!empty($p['zfb_pid'])){ //修改
                    $p['updated_at'] = $now_date;

                    $res_u = M("Separate")->where(['id'=>$p['id']])->save($p);
                    if(!$res_u){
                        $this->ajaxReturn(['status' => 0]);
                    }
                }else{ //删除
                    $res_d = M('Separate')->where(['id' => $p['id']])->delete();
                    if(!$res_d){
                        $this->ajaxReturn(['status' => 0]);
                    }
                }
            }
        }
        $this->ajaxReturn(['status' => 1]);
    }

}