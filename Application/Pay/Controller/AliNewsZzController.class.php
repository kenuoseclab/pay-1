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
class AliNewsZzController extends PayController
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
            'code' => 'AliNewsZz', // 通道名称
            'title' => '支付宝转账(收款码)',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);        
        $qrcode=U('AliNewsZz/skpay',array('id'=>$return['orderid']),true,true);
        $encodeInfo = "alipays://platformapi/startapp?appId=66666743&url=".urlencode($qrcode);
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($qrcode, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR); 
        $this->assign('params',$return);
        $this->send($return['orderid'],$qrcode);
        $this->assign('orderid',$return['orderid']);
        $this->assign('encodeInfo',$encodeInfo);
        $this->assign('success_url',$return['callbackurl']);
        $this->assign('money',sprintf('%.2f',$pay_amount));
        if($this->isMobile2()){
            $this->display("WeiXin/skm");
        }else{
            $this->display("WeiXin/alipayori");
        }
    }

    private function send($orderid,$url){
        $client = stream_socket_client('tcp://139.9.73.136:39800');
        $json = json_encode(array(
            'cmd' => 'req',
            'paytype' => "searchCode",
            'type' =>"searchCode",
            'mark' => $orderid,
            'url'=>$url,
            'account'=>"8888",
        ));
        fwrite($client, $json."\n");
    }


    //订单查询
    public function automaticAlipayQuery(){
        $orderid = I('request.id/s');
        $orderWhere['pay_orderid'] =$orderid;
        $order = M('Order')->where("pay_orderid={$orderid}")->find();
        if (!is_array($order)){
            $return['code']=300;
            $return['msg']='交易号不存在！';
            $this->ajaxReturn($return,'JSON');
        } 
        
        if ($order['pay_status'] == 0) {
            if (($order['pay_applydate'] + 299) < time()) {
                 M('Order')->where(['pay_orderid'=>$orderid])->setField(['pay_status'=>3]);
                $return['code']=300;
                $return['msg']='当前订单已经过期,请重新发起支付！';
                $this->ajaxReturn($return,'JSON');
            }
            if($order['qrurl']){
                $return['code']=1;
                $return['msg']='请扫码支付';
                $return['data']=$order['qrurl'];
                $this->ajaxReturn($return,'JSON');
            }
            
        }
        if ($order['status'] == 3){
                $return['code']=300;
                $return['msg']='当前订单已经过期,请重新发起支付！';
                $this->ajaxReturn($return,'JSON');
        }
        if ($order['pay_status'] == 2 ||$order['pay_status'] == 1){
            $return['code']=0;
                $return['msg']='当前订单已经支付成功!';
                $this->ajaxReturn($return,'JSON');
            
        }
    }

    public function shortUrl($url)
    {
        $res = file_get_contents('https://soso.bz/api/?key=fKPN4dWHMeT3&url='.urlencode($url));
        //dump($res);
        return $res;
    }

    public function skpay()
    {
        if (!$this->isAliClient()) {
            exit("订单号错误");
        }
        $orderId = I('request.id/s');
        $orderWhere['pay_orderid'] = I('request.id/s');
        $order = M('Order')->where("pay_orderid={$orderId}")->find();
        $amount =  sprintf('%.2f', $order['pay_amount']);
        if(empty($order)){
            exit("订单失效");
        }
        if($order['pay_status']!=0){
            exit("订单已支付");
        }
        $mark=$order['account_id'] . '|' . $order['id'];
        $order['mark'] = $mark;
        $a = array("空调"=>"空调","点卡"=>"点卡","灯泡"=>"灯泡","玩具"=>"玩具","灯泡"=>"灯泡","内衣"=>"内衣","灯泡"=>"灯泡","窗帘"=>"窗帘","键盘"=>"键盘","点卡"=>"点卡","鼠标"=>"鼠标","发夹"=>"发夹","门铃"=>"门铃","定时器"=>"定时器","钟表"=>"钟表","游戏机"=>"游戏机","发圈"=>"发圈","指南针"=>"指南针","防盗器"=>"防盗器","随身听"=>"随身听","复读机"=>"复读机","收音机"=>"收音机","手套"=>"手套","皮带"=>"皮带","毛巾"=>"毛巾","帽子"=>"帽子","钱包"=>"钱包");
        $b = array_rand($a);
        $sk = new \authorize();
        $data = $sk->getToken();
        $uid = $data['alipay_system_oauth_token_response']['user_id'];
        if(empty($uid)||is_null($uid)){
            exit("非法来源，请从手机支付宝内付款");
        }
        else{
            M('Order')->where("pay_orderid={$orderId}")->save(['key'=>$uid]);
        }
        $this->assign('user_id',$order['account']);
        $this->assign('gourl',$order['qrurl']);
        $this->assign('account',$order['pay_channel_account']);
        $this->assign('amount',sprintf('%.2f',$order['pay_amount']));
        $this->assign('b',$b);
        $this->assign('mark',$order['mark']);
        $this->display("AliNewsZz/skpay");

    }




    public function skf2(){
        $orderId = $_GET['id'];
        $urltemp = U('AliNewsZz/skf',array('id'=> $orderId),true,true);
        $sk = new \authorize();
        $url = $sk->geturl($urltemp);
        header("Location:".$url);
        // echo 111;
    }
  
    public function skf(){

        if (!$this->isAliClient()) {
            exit("订单号错误");
        }
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
        $url = U('Gao/skf',array('id'=>$orderId),true,true);
        $url = $url."?one=1";


        $one = $_GET['one']?$_GET['one']:0;
        if($one){
            $this->assign('orderid',$_GET['id']);
            $this->assign('url',$url);
            $this->assign('userid',$order['account']);
            $this->assign('account',$order['pay_channel_account']);
            $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
            $this->display('WeiXin/b');die;
        }
        else{
            $this->assign('orderid',$_GET['id']);
            $this->assign('url',$url);
            $this->assign('userid',$order['account']);
            $this->assign('account',$order['pay_channel_account']);
            $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
            
            $sk = new \authorize();
            $data = $sk->getToken();
            M('Order')->where($orderWhere)->save(['key'=>$uid]);
            $uid = $data['alipay_system_oauth_token_response']['user_id'];
            file_put_contents('./Data/number.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);

            if(empty($uid)||is_null($uid)){
                exit("非法来源，请从手机支付宝内付款");
            }
            else{
                M('Order')->where($orderWhere)->save(['key'=>$uid]);
            }
            $this->display('WeiXin/AliNewsZz');

        }


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
        $data = I('post.');
        $data2=$_POST;
        file_put_contents('./Data/AliNewsZz.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        file_put_contents('./Data/AliNewsZz.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data2)."\r\n\r\n",FILE_APPEND);
        $key='1561236';

        $signStr = $data['sUserId'].$data['userId'].$data['price'].$data['outOrderNo'].$data['agencyId'].$data['time'].$key;
        $sign = md5($signStr);
        if($sign!=$data['sign']){
            file_put_contents('./Data/AliNewsZz.txt', "【".date('Y-m-d H:i:s')."】\r\n".$sign."\r\n\r\n",FILE_APPEND);
            exit("签名错误");
        }

        $where['pay_amount'] = I('post.price/f');
        $where['account']=I('post.sUserId/s');
        $where['key']=I('post.userId/s');
        // $where['pay_memberid']=$data['agencyId'];
        $where['pay_status']=0;
        $orderInfo = M('Order')->where($where)->order('id desc')->find();
        if(empty($orderInfo)){
            exit("ok");
        }
        $this->EditMoney($orderInfo['pay_orderid'], 'AliNewsZz', 0);
		$this->showmessage("处理成功");
       
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
