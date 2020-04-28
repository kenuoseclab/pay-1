<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

class DDQunController extends PayController
{
    public function __construct()
    {
        parent::__construct();

    }
    //支付
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $contentType = I("request.content_type");
        $parameter = array(
            'code' => 'DDQun', // 通道名称
            'title' => '钉钉群红包',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        $notifyurl = $this->_site . 'Pay_DDQun_notifyurl.html';
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);
        $amount = (string)$return['amount'];
        //查询库里面有没有可用码
        $where['money']=sprintf('%.2f',$return['amount']);
        $where['creator']=$return['mch_id'];
        $where['status']=0;
        $ddqun=M('ddqun')->where($where)->find();
        // var_dump($ddqun);exit;
        if($ddqun){
            //开启事物
            M()->startTrans();
            $save['status']=1;
            $save['pay_orderid']=$return['orderid'];
            $save['uptime']=time();
            $res1=M('ddqun')->where("id=".$ddqun['id'])->save($save);
            $res2=M('order')->where("pay_orderid=".$return['orderid'])->setField('qrurl',$ddqun['qrcode']);
            // echo $res1."----".$res2;
            if($res1&&$res2){
                M()->commit();
            }else{
                M()->rollback();
                $this->showmessage('当前金额库存不足！');
            }

        }else{
            $this->senddd($amount,$return['mch_id']);
            sleep(2);
            $where['money']=sprintf('%.2f',$return['amount']);
            $where['creator']=$return['mch_id'];
            $where['status']=0;
            $ddqun=M('ddqun')->where($where)->find();
            if($ddqun){
                //开启事物
                M()->startTrans();
                $save['status']=1;
                $save['pay_orderid']=$return['orderid'];
                $save['uptime']=time();
                $res1=M('ddqun')->where("id=".$ddqun['id'])->save($save);
                $res2=M('order')->where("pay_orderid=".$return['orderid'])->setField('qrurl',$ddqun['qrcode']);
                if($res1&&$res2){
                    M()->commit();
                }else{
                    M()->rollback();
                    $this->showmessage('当前金额库存不足！');
                }

            }else{
                $this->showmessage('当前金额库存不足！');
            }
        }

        $url = U('DDQun/skf',array('id'=>$return['orderid']),true,true);
        $encodeInfo = "alipays://platformapi/startapp?appId=20000067&url=".$url;
        $this->assign('tempurl',$url);
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($encodeInfo, $QR, "L", 20);
        if($contentType=="json"){
            $urll=$this->site.'/'.$QR;
            $data = ['code'=>0,'msg'=>"生成订单成功",'pay_url'=>$urll,'order_id'=>$return['orderid']];
            echo json_encode($data,JSON_UNESCAPED_SLASHES);die;
        }
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('qrurl',$ddqun['qrcode']);
        $this->assign('money',sprintf('%.2f',$return['amount']));
        if($this->isMobile2()){
            $this->display("DDQun/dd2");
        }else{
            $this->display("DDQun/alipaytaoori");
        }
        
    }

    private function getdata($return){

    }
    public function check(){
        $orderid = I('request.orderid/s');
        $orderWhere['pay_orderid'] =$orderid;
        $orderInfo = M('Order')->where($orderWhere)->find();
        if(!empty($orderInfo['qrurl'])){
           echo json_encode(array('state' => 1, 'callback' =>$orderInfo['qrurl']));die;
        }else{

        }
    }

  

    public function skf(){

        $orderId = I('request.id/s');
        $orderWhere['pay_orderid'] = $orderId;
        $order = M('Order')->where($orderWhere)->find();
        $amount =  sprintf('%.2f', $order['pay_amount']);
        if(empty($order)){
            exit("订单失效");
        }
        if($order['pay_status']!=0){
            exit("订单已支付");
        }
        $this->assign('transferid',$transferId);
        $this->assign('orderid',$_GET['id']);
        $this->assign('url',$url);
        $this->assign('userid',$order['account']);
        $this->assign('qrurl',$order['qrurl']);
        $this->assign('account',$order['pay_channel_account']);
        $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
        $this->display('DDQun/dd2');die;
    }


  public function senddd($money,$account){
    
    
        $client = stream_socket_client('tcp://139.9.73.136:39800');
        $json = json_encode(array(
            'cmd'=>"req",
            'type' =>"getQrCode",
            'money' => $money,
            'mark' => time(),
            'account'=>$account,
        ));
        file_put_contents('./Data/DDQun.txt', "【".date('Y-m-d H:i:s')."】\r\n".$json."\r\n\r\n",FILE_APPEND);
        fwrite($client, $json."\n");

    }
    public function getnum(){
        $data = I('post.');
        $msg=I('post.data/s');
        $msg=urldecode($msg);
        $msg=json_decode($msg,true);
        $groupBillItem=$msg['data']['groupBillItem'];
        if(empty($groupBillItem)){
            $this->showmessage('数据为空！');
        }
        $result=array();
        foreach($groupBillItem as $v){
            $result[$v['uid']]=$v['payStatus'];
        }
        $groupBillModel=$msg['data']['groupBillModel'];
        $where['creator']=$msg['id'];
        $where['status']=1;
        $where['bizId']=$groupBillModel['groupBillId'];
        $ddqunlist=M('ddqun')->where($where)->select();
        if(empty($ddqunlist)){
            $this->showmessage('查无数据！');
        }
        foreach($ddqunlist as $val){
            if($result[$val['fuid']]==1){
                $save['status']=2;
                $save['uptime']=time();
                $res1=M('ddqun')->where("id=".$val['id'])->save($save);
                if($res1){
                    $this->EditMoney($val['pay_orderid'], 'DDQun', 0);
                }else{
                    $this->showmessage('回调失败！');
                }
            }
        }
        
    }

    public function xintiao(){
    }
    public function getPay(){
        $id = I('request.id/s');
        if(empty($id)){
            exit("订单号错误");
        }

        $where['pay_orderid'] = $id;
        $order = M('Order')->where($where)->find();
        if(!$order){
            exit("订单不存在");
        }
        if($order['pay_status']>0){
            exit ('已支付');exit;
        }
        $this->assign('orderid',$id);
        $this->assign('userid',$order['account']);
        $this->assign('account',$order['pay_channel_account']);
        $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
        $this->display('DDQun/zhudong');


    }


    public function isInAlipayClient()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            return true;
        }
        return false;
    }
    //检测是否手机访问
    static public function isMobile(){
        $useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $useragent_commentsblock=preg_match('|\(.*?\)|',$useragent,$matches)>0?$matches[0]:'';
        function CheckSubstrs($substrs,$text){
            foreach($substrs as $substr)
                if(false!==strpos($text,$substr)){
                    return true;
                }
            return false;
        }
        $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
        $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');

        $found_mobile=CheckSubstrs($mobile_os_list,$useragent_commentsblock) ||
            CheckSubstrs($mobile_token_list,$useragent);

        if ($found_mobile){
            return true;
        }else{
            return false;
        }
    }

    //检测是否手机访问
    private function isMobile2(){
        $useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $useragent_commentsblock=preg_match('|\(.*?\)|',$useragent,$matches)>0?$matches[0]:'';
        function CheckSubstrs($substrs,$text){
            foreach($substrs as $substr)
                if(false!==strpos($text,$substr)){
                    return true;
                }
            return false;
        }
        $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
        $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');

        $found_mobile=CheckSubstrs($mobile_os_list,$useragent_commentsblock) ||
            CheckSubstrs($mobile_token_list,$useragent);

        if ($found_mobile){
            return true;
        }else{
            return false;
        }
    }







    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");       
        $orderid=I('request.orderid/s');  
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        if ($pay_status <> 0) {
            $this->EditMoney($_REQUEST["orderid"], '', 1);
        } else {
            exit("交易成功！");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $data = I('post.');
        $msg=I('post.msg/s');
        $msg=urldecode($msg);
        $add['msg']=$msg;
        $msg=json_decode($msg,true);
        file_put_contents('./Data/DDQunN.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        $add['mark']=I('post.mark/s');
        $add['money']=I('post.money/f');
        $add['qrcode']=$msg['qrcode']['payUrl'];
        $add['creator']=$msg['creator'];
        $add['method']=$msg['method'];
        $add['cmd']=$msg['cmd'];
        $add['fuid']=$msg['id'];
        $add['groupBillName']=$msg['groupBillName'];
        $add['type']=$msg['type'];
        $add['bizId']=$msg['bizId'];
        $add['time']=time();
        file_put_contents('./Data/DDQunN.txt', "【".date('Y-m-d H:i:s')."】\r\n".var_export($add,true)."\r\n\r\n",FILE_APPEND);
        $res=M('ddqun')->add($add);
        if($res){
            echo 'success';
        }else{
            $this->showmessage('存储失败');
        }
        
    }
    protected function showmessage($msg = '', $fields = array())
    {
        header('Content-Type:application/json; charset=utf-8');
        $data = array('result' => '200', 'msg' => $msg, 'data' => $fields);
        echo json_encode($data, 320);
        exit;
    }


    function getIP() {
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv( "HTTP_X_FORWARDED_FOR");
            } elseif (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }
}
