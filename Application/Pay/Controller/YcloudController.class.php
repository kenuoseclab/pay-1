<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

use MoneyCheck;
require_once("authorize.class.php");
class YcloudController extends PayController
{
    public function __construct()
    {
        parent::__construct();

    }
    //Pay_Gaoji_notifyurl.html
    //支付
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $contentType = I("request.content_type");
        $returnType=I("request.return_type",1);
        $pay_amount = I("post.pay_amount", 0);
        $parameter = array(
            'code' => 'Ycloud', // 通道名称
            'title' => '云闪付扫码',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);
        $notifyurl = $this->_site . 'Pay_Ysf_notifyurlsd.html';
        $oid=M('order')->where(['pay_orderid'=>$return['orderid']])->getField('id');
        // $this->senddd($return['amount'],$return['orderid'],$return['account_id']);
        $this->senOrder("ysf",$return['mch_id'],$oid,$return['amount']*100,$notifyurl);
        $this->assign('orderid',$return['orderid']);
        $this->assign('oid',$oid);
        $this->assign('success_url',$return['callbackurl']);
        $this->assign('money',sprintf('%.2f',$pay_amount));
        $this->display("WeiXin/ysfMobile");
    }


    //订单查询
    public function automaticAlipayQuery(){
        $orderid = I('request.id/s');
        $orderWhere['pay_orderid'] =$orderid;
        $order = M('Order')->where($orderWhere)->find();
        if (!is_array($order)){
            $return['code']=-1;
            $return['msg']='交易号不存在！';
            $this->ajaxReturn($return,'JSON');
        } 
        
        if ($order['pay_status'] == 0) {
            
            if($order['qrurl']){
                $data['url'] =$order['qrurl'];
                $data['h5'] = $order['qrurl'];
                $data['qrcode'] =$data['url'];
                $return['code']=100;
                $return['msg']='请扫码支付';
                $return['data']=$data;
                $this->ajaxReturn($return,'JSON');
            }
            
        }
        if ($order['status'] == 3){
            $return['code']=-2;
                $return['msg']='当前订单已经过期,请重新发起支付！';
                $this->ajaxReturn($return,'JSON');
        }
        if ($order['pay_status'] == 2){
            $return['code']=200;
                $return['msg']='当前订单已经支付成功!';
                $this->ajaxReturn($return,'JSON');
            
        }
    }


    public function senddd($money,$orderid,$account){
    
    
        $client = stream_socket_client('tcp://139.9.73.136:39800');
        $json = json_encode(array(
            'cmd'=>"req",
            'type' =>"getQrCode",
            'price' => $money,
            'remark' => $orderid,
            'account'=>$account,
        ));
        fwrite($client, $json."\n");

    }
    private function senOrder($channel,$acc,$mark_sell,$money,$notify){
        $client = stream_socket_client('tcp://139.9.73.136:39800');
        if(!$client)exit("服务器链接失败");
        $json = json_encode(array(
            'cmd' => 'req',
            'account' => $acc,
            'type' =>$channel,
            'notifyurl'=>$notify,
            'money' => $money,
            'remark' => $mark_sell
        ));
        fwrite($client, $json."\n");
    }


    public function shortUrl($url)
    {
        $res = file_get_contents('https://soso.bz/api/?key=fKPN4dWHMeT3&url='.urlencode($url));
        //dump($res);
        return $res;
    }

    public function skf2(){
        $orderId = I('get.id/s');
        $urltemp = U('Ycloud/skf',array('id'=> $orderId),true,true);
        $sk = new \authorize();
        $url = $sk->geturl($urltemp);
        header("Location:".$url);
        // echo 111;
    }


    /**
     * 判断是否支付宝内置浏览器访问
     * @return bool
     */
    private function isAliClient() {
        $isAli = strpos($_SERVER['HTTP_USER_AGENT'], 'Alipay') !== false;
        //$isAli_1 = empty($_SERVER['HTTP_SPDY_H5_UUID']) !== true;
        $result = $isAli; // && $isAli_1;
        return $result;
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


    public function isMobile2(){
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
       // {"remark":"20190425231743551024","outOrderNo":"00250001000499922358190425231804","price":"1.0","loginId":"192238833341762","memberId":"330"}
        $data = I('post.');
        file_put_contents('./Data/Ycloud.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        if($data['sign'] != md5($data['orderid'].$data['money']."aabbccddefg")){
            exit("ok");
        }
        $where['id']=I('post.orderid/s');
        $where['pay_amount'] = $data['money']/100;
        //$where['pay_status']=0;
        $orderInfo = M('Order')->where($where)->order('id desc')->find();

        if(empty($orderInfo)){
            exit("ok");
        }
        if($orderinfo['pay_status']==1||$orderinfo['pay_status']==2){
            exit("ok");
        }
        if($data['sign'] == md5($data['orderid'].$data['money']."aabbccddefg")){
            $this->EditMoney($orderInfo['pay_orderid'], 'Ycloud', 0);
            $this->showmessage("处理成功");
        }else{
            file_put_contents("YSFSIGN",json_encode($_GET));
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

    /**
     *  服务器通知
     */
    public function notifyurlsd()
    {
        $order = I('get.orderid/s');
        $money = I('get.money/s');
        $sign = I('get.sign/s');
         file_put_contents('./Data/Ycloud.txt', "【".date('Y-m-d H:i:s')."】\r\n".$order."\r\n\r\n",FILE_APPEND);
        if($sign == md5($order.$money."123456")){
            $this->EditMoney($order, 'Ycloud', 0);
        }else{
            file_put_contents("YSFSIGN",json_encode($_GET));
        }
    }
}
