<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

class TaoController extends PayController
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
            'code' => 'Tao', // 通道名称
            'title' => 'Tao',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
       $notifyurl = $this->_site . 'Pay_Ding_notifyurl.html';
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);
        $url = U('Tao/skf',array('id'=>$return['orderid']),true,true);
        $encodeInfo = "https://ds.alipay.com/?from=mobilecodec&scheme=alipays%3a%2f%2fplatformapi%2fstartapp%3fsaId%3d10000007%26qrcode%3d".$url;
            $amount = sprintf('%.2f', $return['amount'])*100;
             $this->assign('tempurl',$url);
 //     var_dump($return['mch_id']);die;
        $this->senddd($amount,$return['orderid'],$return['mch_id'],$return['appid']);
        if($this->isMobile2()){
            if($contentType=="json"){
                $data = ['code'=>0,'msg'=>"生成订单成功",'pay_url'=>$location,'order_id'=>$return['orderid']];
                echo json_encode($data,JSON_UNESCAPED_SLASHES);die;
            }
             $this->display("WeiXin/alipaytao");
        }else{
            import("Vendor.phpqrcode.phpqrcode",'',".php");
            $QR = "Uploads/codepay/". $return['orderid'] . ".png";
            \QRcode::png($encodeInfo, $QR, "L", 20);
            $this->assign("imgurl", '/'.$QR);
            $this->assign('params',$return);
            $this->assign('orderid',$return['orderid']);
            $this->assign('money',sprintf('%.2f',$return['amount']));
                   $this->assign('zfbpayUrl',$encodeInfo);
                $this->display("WeiXin/alipaytaoori");
        }

    }
    public function check(){
        $orderid = I('request.orderid/s');
        $orderWhere['pay_orderid'] =$orderid;
        $orderInfo = M('Order')->where($orderWhere)->find();
    
      if(!empty($orderInfo['qrurl'])){
            echo json_encode(array('state' => 1, 'callback' =>$orderInfo['qrurl']));die;
      }
    }
  

    public function skf(){

        $orderId = I('get.id/s');
        $orderWhere['pay_orderid'] = $orderId ;
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
            $this->assign('account',$order['pay_channel_account']);
            $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
            $this->display('WeiXin/wangxin');die;
    

    }


  public function senddd($money,$orderid,$account,$notify){
    
    
        $client = stream_socket_client('tcp://139.9.73.136:39800');
        $json = json_encode(array(
            'cmd' => 'req',
            'paytype' => "wangxin",
            'type' =>"wangxin",
            'uid'=>"888",
            'money' => $money,
            'mark' => $orderid,
            'key_id'=>$account,
            'account'=>$account,
          'qunid'=>$notify
        ));
        fwrite($client, $json."\n");
    
    
    
    }
    public function getnum(){
         $data = I('request.');
         $orderWhere['pay_orderid'] =I('request.mark/s');
        file_put_contents('./Data/aa.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        if($data['type']=="wangxin"){
              M('Order')->where($orderWhere)->save(['qrurl'=>$data['payurl']]);
          die;
        }
        if(isset($data['mark'])&&!empty($data['payurl'])){
         
            M('Order')->where($orderWhere)->save(['memberid'=>$data['payurl']]);
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
        $this->display('WeiXin/zhudong');


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





    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");       
        $orderid=I('request.orderid/s');  
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        if ($pay_status <> 0) {
            $this->EditMoney($orderid, '', 1);
        } else {
            exit("交易成功！");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $data = I('post.');
        $key = "numdsffawaefaddshrthdwqwrdfdv";
        $signStr = $data['dt'].$key.$data['money'].$data['order'];
        $sign = md5($signStr);
        if($sign!=$data['key']){
            $this->showmessage("签名错误");
        }     
         $money = $data['money'];
            if($data['type']=="wangxin"){
                $money =sprintf('%.2f',$money/100);
            }
            $orderId   = I('post.order/s');
            $orderWhere['pay_orderid'] = $orderId;
            $orderInfo = M('Order')->where($orderWhere)->find();
            if(empty($orderInfo)){
               $this->showmessage("无订单");
            }
            if($orderInfo['pay_status']!=0){
                    $this->showmessage("已支付");
            }
            
            $oder_amount = sprintf('%.2f', $orderInfo['pay_amount']);

            if($money!=$oder_amount){
                file_put_contents('./Data/mmmmhbnotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
                exit("mo fail");
            }
            $this->EditMoney($orderId, 'Envelopes', 0);
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
