<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;

require_once("authorize.class.php");
class GaoController extends PayController
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
        $parameter = array(
            'code' => 'Gao', // 通道名称
            'title' => '主动收款',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一生成
        $return = $this->orderadd($parameter);
        $urltemp = U('Gao/skf',array('id'=>$return['orderid']),true,true);
		$sk = new \authorize();
        $url = $sk->geturl($urltemp);
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$return['amount']));
      
        $encodeInfo = "alipays://platformapi/startapp?appId=20000691&url=".urlencode($url);  
      //  $encodeInfo = "https://ds.alipay.com/?from=mobilecodec&scheme=alipays%3A%2F%2Fplatformapi%2Fstartapp%3FsaId%3D10000007%26clientVersion%3D3.7.0.0718%26qrcode%3D".$url;
        if($array['pid']==904){
           // $this->display("WeiXin/alipayori");die;
            $encodeInfo = "alipayqr://platformapi/startapp?saId=10000007&qrcode=".$url;
            $location ="https://ds.alipay.com/?from=mobilecodec&scheme=" . urlencode($encodeInfo);
            if($contentType=="json"){
                $data = ['code'=>0,'msg'=>"生成订单成功",'pay_url'=>$location,'order_id'=>$return['orderid']];
                echo json_encode($data,JSON_UNESCAPED_SLASHES);die;
            }
            header("Location:".$encodeInfo);
        }
        else{
            if($contentType=="json"){
                $data = ['code'=>0,'msg'=>"生成订单成功",'pay_url'=>$url,'order_id'=>$return['orderid']];
                echo json_encode($data,JSON_UNESCAPED_SLASHES);die;
            }
            $this->assign('zfbpayUrl',$encodeInfo);

            $this->display("WeiXin/alipayori");
        }

    }
    
    public function check(){

      $orderid = I('get.orderid/s');
        $orderWhere['pay_orderid'] =$orderid;
        $orderInfo = M('Order')->where($orderWhere)->find();
      $amount =  sprintf('%.2f', $orderInfo['pay_amount']);
    if($_REQUEST['type']==1){
    $this->send($orderInfo['key'],$amount,$orderid,$orderInfo['pay_channel_account'],$orderInfo['pay_channel_account']);die;}
      if(!empty($orderInfo['memberid'])){
         header('Content-type: application/json');
        exit(json_encode(array("state" => 1, "callback" => $orderInfo['memberid'])));
      }
      else{
        if($_REQUEST['type']%4==0&&$_REQUEST['type']>=4){
		   $this->send($orderInfo['key'],$amount,$orderid,$orderInfo['pay_channel_account'],$orderInfo['pay_channel_account']);}
      	echo "no";
      }
    }
  
    public function skf(){

        if (!$this->isAliClient()) {
            exit("订单号错误");
        }
        $orderId = I('get.id/s');
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
            $this->display('WeiXin/gao');

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
    public function sk(){
        $id = I('get.id/s');
        $orderWhere['pay_orderid'] = $id;
        $orderInfo = M('Order')->where($orderWhere)->find();
        if(empty($orderInfo)){
            exit("订单失效");
        }
        $sk = new \authorize();
        $data = $sk->getToken();
        file_put_contents('./Data/gaojia.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data).$_GET['id']."\r\n\r\n",FILE_APPEND);
		return $data;

    }

    public function send($uid,$money,$orderid,$key_id,$account){
        $client = stream_socket_client('tcp://139.9.73.136:39800');
        $json = json_encode(array(
            'cmd' => 'req',
            'paytype' => "alipaycheck",
            'type' =>"alipaycheck",
            'uid'=>$uid,
            'money' => $money,
            'mark' => $orderid,
            'key_id'=>$key_id,
            'account'=>$account,
        ));
        fwrite($client, $json."\n");
    }
    public function getnum(){
        $data = I('post.');
        file_put_contents('./Data/gao.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        if(isset($data['mark'])&&!empty($data['payurl'])){
            $orderWhere['pay_orderid'] =I('request.mark/s');
            $payurl=I('request.payurl/s');
            M('Order')->where($orderWhere)->save(['memberid'=>$payurl]);
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
      $key = "numdsffawaefaddshrthdwqwrdfdv";
        $data = I('post.');
        file_put_contents('./Data/gao.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        $signStr = $data['dt'].$key.$data['money'].$data['order'];
        $sign = md5($signStr);
        if($sign!=$data['key']){
            exit("签名错误");
        }

        $money = $data['money'];
        $orderId   = I('post.order/s');
        $orderWhere['pay_orderid'] = $orderId;
        $orderInfo = M('Order')->where($orderWhere)->find();
        if(empty($orderInfo)){
            exit("a");
        }
        if($orderInfo['pay_status']!=0){
            exit(200);
        }
        
        $oder_amount = sprintf('%.2f', $orderInfo['pay_amount']);

        if($money!=$oder_amount){
            file_put_contents('./Data/mmmmhbnotify.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
            exit("mo fail");
        }
        $this->EditMoney($orderId, 'Gao', 0);
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
