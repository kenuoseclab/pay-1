<?php
/**
 * Created by PhpStorm.
 * User: qiswl
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;
use MoneyCheck;
require_once("redis_util.class.php");
require_once("authorize.class.php");
class CopyZzController extends PayController
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
        $pay_amount = I("post.pay_amount", 0);
        $contentType = I("request.content_type");
        $moneyCheck = new MoneyCheck();
        $parameter = array(
            'code' => 'CopyZz', // 通道名称
            'title' => '支付宝复制转账',
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
        
        $payurl = U('CopyZz/orderdetail',array('id'=>$return['orderid']),true,true);
        if($contentType=="json"){
            $data = ['code'=>200,'msg'=>"success",'codeurl'=>$return['appid'],'payurl'=>$payurl,'url'=>$this->_site.$QR,'realmoney'=>sprintf('%.2f',$pay_amount),'order_id'=>$return['orderid']];
            echo json_encode($data,JSON_UNESCAPED_SLASHES);die;
        }
        $successurl=U('CopyZz/callbackurl',array('orderid'=>$return['orderid']),true,true);
        $endtime=date("Y-m-d H:i:s",(time()+300));
        $this->assign('orderlasttime',$endtime);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$pay_amount));
        $this->assign('success_url',$successurl);
        if($this->isMobile2()){
            $this->display("WeiXin/CopyZz");  
        }else{
            $this->display("WeiXin/CopyZz");
        }
              
    }

    public function orderdetail(){
        $id=I('request.id/s');
        if (empty($id)) {
            $this->showmessage("订单不存在!");
        }
        $order=M('Order')->where(['pay_orderid'=>$id])->find();
        if (!$order) {
            $this->showmessage("订单不存在!");
        }
        $tt=time()-$order['pay_applydate'];
        $lasttime=300-$tt;
        if($lasttime<0){
            $lasttime=0;
        }
        $endtime=date("Y-m-d H:i:s",(time()+$lasttime));
        $this->assign('orderlasttime',$endtime);
        $this->assign('params',$order);
        $this->assign('orderid',$order['pay_orderid']);
        $this->assign('money',sprintf('%.2f',$order['pay_amount']));
        $this->assign('success_url',$order['pay_callbackurl']);
        if($this->isMobile2()){
            $this->display("WeiXin/CopyZz");  
        }else{
            $this->display("WeiXin/CopyZz");
        }


    }

    public function getqrcode(){
        $orderid = I('request.id/s');
        $orderWhere['pay_orderid'] =$orderid;
        $order = M('Order')->where("pay_orderid={$orderid}")->find();
        if (!is_array($order)){
            $return['code']=-1;
            $return['msg']='交易号不存在！';
            $this->ajaxReturn($return,'JSON');
        } 
        
        if ($order['pay_status'] == 0) {
            if (($order['pay_applydate'] + 299) < time()) {
                 M('Order')->where(['pay_orderid'=>$orderid])->setField(['pay_status'=>3]);
                $return['code']=-1;
                $return['msg']='当前订单已经过期,请重新发起支付！';
                $this->ajaxReturn($return,'JSON');
            }
            if($order['key']){
                import("Vendor.phpqrcode.phpqrcode",'',".php");
                $QR = "Uploads/codepay/". $order['pay_orderid'] . ".png";
                \QRcode::png($order['key'], $QR, "L", 20);
                $return['code']=100;
                $return['msg']='请扫码支付';
                $return['data']['qrcode']='/'.$QR;
                $return['data']['name']=$order['pay_channel_account'];
                $this->ajaxReturn($return,'JSON'); 
            }else{
                $return['code']=300;
                $return['msg']='等待接单中';
                // $return['data']['qrcode']=$order['account'];
                $this->ajaxReturn($return,'JSON'); 
            }
                       
        }
        if ($order['status'] == 3){
                $return['code']=-1;
                $return['msg']='当前订单已经过期,请重新发起支付！';
                $this->ajaxReturn($return,'JSON');
        }
        if ($order['pay_status'] == 2 ||$order['pay_status'] == 1){
            $return['code']=200;
                $return['msg']='当前订单已经支付成功!';
                $this->ajaxReturn($return,'JSON');
            
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

	public function xintiao(){
        $data = I('post.');
        if(empty($data['sign'])){
            $data['code']="3";
            $data['msg']="签名错误";
            echo json_encode($data);
        }
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

    public function notify(){
        $data = I('post.');
        if(empty($data['sign'])){
            exit("ok");
        }
        file_put_contents('./Data/CopyZz.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        $signtext=$data['dt'].$data['money'].$data['no']."cfepaynet123456";
        $key=md5($signtext);
        if($key!=$data['sign']){
            exit("签名错误");
        }
        if($data['key']!='cfepaynet123456'){
            exit("非法操作");
        }
        if(empty($data['userId'])){
            exit("success");
        }
        $no=I('post.no/s');
        $paytime=time()-300;
        $orderWhere['pay_amount'] = I('post.money/f');
        $orderWhere['pay_status'] = 0;
        $orderWhere['account'] = I('post.userId/s');
        $orderWhere['pay_applydate'] = array('gt',$paytime);
        $orderInfo = M('Order')->where($orderWhere)->select();
        $orderCount = count($orderInfo);
        if(empty($orderInfo)){
           $this->showmessage("无订单");
        }
        if($orderCount==1){
            //正常订单该订单则为正常的
            $orderData = $orderInfo[0];
            if($orderData['pay_ytongdao']=="CopyZz"){
               $moneyCheck = new moneyCheck();
                $isSystemOrder = $moneyCheck->checkAccountMoney($orderData['account_id'],$data['money']);
                if($isSystemOrder){
                    //不是系统订单
                    file_put_contents('./Data/CopyZz.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
                    $this->showmessage("非系统订单");
                }
                $moneyCheck->deletAccountKey($orderData['account_id'],$data['money']); 
            }else{
                $payuserid=I('post.payUserId/s');
                if($payuserid!=$orderData['key']){
                    file_put_contents('./Data/CopyZz.txt', "【".date('Y-m-d H:i:s')."】付款ID不同：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
                    $this->showmessage("付款ID不同");
                }
            }
            
            $this->EditMoney($orderData['pay_orderid'], 'CopyZz', 0);
            //更新实际成功时间
            M('Order')->where(['id'=>$orderData['id']])->setField('attach',$no);
            $this->showmessage("处理成功");

        }

        if($orderCount>1){
            //匹配到多个订单
            file_put_contents('./Data/CopyZz_ZD.txt', "【".date('Y-m-d H:i:s')."】多个订单回调参数：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
            file_put_contents('./Data/CopyZz_ZD.txt', "【".date('Y-m-d H:i:s')."】多个订单列表：\r\n".json_encode($orderInfo)."\r\n\r\n",FILE_APPEND);
            $this->showmessage("紧急错误！请联系管理员！");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $data = I('post.');
        file_put_contents('./Data/AliNewsZz.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
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
        $data = array('code' => '200', 'msg' => $msg, 'data' => $fields);
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
