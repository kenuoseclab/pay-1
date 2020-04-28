<?php
/**
 * Created by PhpStorm.
 * User: qiswl
 * Date: 2019-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

use MoneyCheck;
require_once("redis_util.class.php");
class FengSJController extends PayController
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
        $moneyCheck = new MoneyCheck();
        $parameter = array(
            'code' => 'FengSJ', // 通道名称
            'title' => '丰收家',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);
        while (!$moneyCheck->checkAccountMoney($return['account_id'],$pay_amount)) {
           $pay_amount=$pay_amount-0.01;
           
        }
        $checkResult = $moneyCheck->setAccountKey($return['account_id'],$pay_amount);
        if($checkResult){
            $_POST['pay_amount']=$pay_amount;
            if($pay_amount!=$return['amount']){
                M('Order')->where(['pay_orderid'=>$return['orderid']])->setField(['pay_amount'=>$pay_amount]);
            }
        }else{
            $this->showmessage('账户:交易量过大，限制交易！');
        }
        $oid=M('order')->where(['pay_orderid'=>$return['orderid']])->getField('id');
        // $this->senddd($return['amount'],$return['orderid'],$return['account_id']);
        // $this->senOrder("FengSJ",$return['account_id'],$pay_amount,$oid);
        $this->assign('orderid',$return['orderid']);
        $this->assign('oid',$oid);
        $this->assign('success_url',$return['callbackurl']);
        $this->assign('money',sprintf('%.2f',$pay_amount));
        $this->display("WeiXin/FengSJMobile");
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
            
            if($order['account']){
                $data['url'] =$order['account'];
                $data['h5'] = $order['account'];
                $data['qrcode'] =$order['account'];
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
            'money' => (string)$money,
            'remark' => $orderid,
            'account'=>$account,
        ));
        fwrite($client, $json."\n");

    }
    private function senOrder($channel,$acc,$money,$oid){
        $client = stream_socket_client('tcp://139.9.73.136:39800');
        if(!$client)exit("服务器链接失败");
        $json = json_encode(array(
            'cmd' => 'req',
            'account' => $acc,
            'type' =>$channel,
            'money' => (string)$money,
            'remark' => $oid,
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
        $urltemp = U('FengSJ/skf',array('id'=> $orderId),true,true);
        $sk = new \authorize();
        $url = $sk->geturl($urltemp);
        header("Location:".$url);
        // echo 111;
    }
    public function getnums(){
        $data=I('get.');
        $qrurl=I('get.qrCode/s');
        $paytime=time()-300;
        $orderWhere['pay_amount']=I('get.money/f');
        $orderWhere['account_id']=I('get.deveice/s');
        $orderWhere['pay_status'] = 0;
        $orderWhere['pay_applydate'] = array('gt',$paytime);
        $orderInfo = M('Order')->where($orderWhere)->select();
        $orderCount = count($orderInfo);
        if(empty($orderInfo)){
           $this->showmessage("无订单");
        }
        if($orderCount==1){
            //正常订单该订单则为正常的
            $orderData = $orderInfo[0];
             //更新实际成功时间
            M('Order')->where(['id'=>$orderData['id']])->setField('qrurl',$qrurl);
            

        }
        file_put_contents('./Data/FengSJ.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
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
       // {"money":"0.02","orderNo":"00005215601542452059553485","sign":"c04997fd816659e9a5740c6691228c9e","deviceId":"346"}
        $data = I('post.');
        $orderNo=I('post.orderNo/s');
        $cc=M('Order')->where(['attach'=>$orderNo])->count();
        if($cc>0){
            exit("oksuccess");
        }
        file_put_contents('./Data/FengSJ.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        if($data['sign'] != md5($data['orderNo'].$data['money'].$data['orderTime'].$data['uid']."123456")){
            exit("oksuccess");
        }
        $paytime=time()-300;
        $orderWhere['pay_amount'] = I('post.money/f');
        $orderWhere['pay_status'] = 0;
        $orderWhere['memberid'] = I('post.uid/s');
        $orderWhere['pay_applydate'] = array('gt',$paytime);
        $orderInfo = M('Order')->where($orderWhere)->select();
        $orderCount = count($orderInfo);
        if(empty($orderInfo)){
           $this->showmessage("success无订单");
        }
        if($orderCount==1){
            //正常订单该订单则为正常的
            $orderData = $orderInfo[0];
            
            $moneyCheck = new moneyCheck();
            $isSystemOrder = $moneyCheck->checkAccountMoney($orderData['account_id'],$data['money']);
            if($isSystemOrder){
                //不是系统订单
                file_put_contents('./Data/FengSJ_ZD.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
                echo "非系统订单";die;
            }
            $moneyCheck->deletAccountKey($orderData['account_id'],$data['money']);
            $this->EditMoney($orderData['pay_orderid'], 'FengSJ', 0);
            //写入订单号
            M('Order')->where(['id'=>$orderData['id']])->setField('attach',$orderNo);
            $this->showmessage("处理成功success");

        }

        if($orderCount>1){
            //匹配到多个订单
            file_put_contents('./Data/FengSJ_ZD.txt', "【".date('Y-m-d H:i:s')."】多个订单回调参数：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
            file_put_contents('./Data/FengSJ_ZD.txt', "【".date('Y-m-d H:i:s')."】多个订单列表：\r\n".json_encode($orderInfo)."\r\n\r\n",FILE_APPEND);
            $this->error("success紧急错误！请联系管理员！");
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
         file_put_contents('./Data/FengSJ.txt', "【".date('Y-m-d H:i:s')."】\r\n".$order."\r\n\r\n",FILE_APPEND);
        if($sign == md5($order.$money."123456")){
            $this->EditMoney($order, 'FengSJ', 0);
        }else{
            file_put_contents("YSFSIGN",json_encode($_GET));
        }
    }
}
