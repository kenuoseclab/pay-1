<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;
use MoneyCheck;
require_once("redis_util.class.php");
class DYTongController extends PayController
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
        $pay_amount = I("post.pay_amount", 0);
        $moneyCheck = new MoneyCheck();
        $parameter = array(
            'code' => 'DYTong', // 通道名称
            'title' => '店员通微信扫码',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        $notifyurl = $this->_site . 'Pay_DYTong_notifyurl.html';
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
        

        $url = U('DYTong/skf',array('id'=>$return['orderid']),true,true);
        $payurl = U('DYTong/orderdetail',array('id'=>$return['orderid']),true,true);
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($return['appid'], $QR, "L", 20);
        if($contentType=="json"){
            $data = ['code'=>200,'msg'=>"success",'codeurl'=>$return['appid'],'payurl'=>$payurl,'url'=>$this->_site.$QR,'realmoney'=>sprintf('%.2f',$pay_amount),'order_id'=>$return['orderid']];
            echo json_encode($data,JSON_UNESCAPED_SLASHES);die;
        }
        //订单剩余的时间：
        $this->assign('orderlasttime',300);
        $this->assign("imgurl", '/'.$QR);
        $this->assign("qrcode",$return['appid']);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$pay_amount));
        $this->assign('success_url',$return['callbackurl']);

        $this->display("WeiXin/weixinQ");

    }

    public function getQrCode(){
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
            if($order['account']){
                import("Vendor.phpqrcode.phpqrcode",'',".php");
                $QR = "Uploads/codepay/". $order['pay_orderid'] . ".png";
                \QRcode::png($order['account'], $QR, "L", 20);
                $return['code']=100;
                $return['msg']='请扫码支付';
                $return['data']['qrcode']="/".$QR ;
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
        $QR = "Uploads/codepay/". $order['pay_orderid'] . ".png";
        $this->assign('orderlasttime',$lasttime);
        $this->assign('success_url',$order['callbackurl']);

        $this->assign("imgurl", '/'.$QR);
        $this->assign("qrcode",$order['account']);
        $this->assign('params',$order);
        $this->assign('orderid',$order['pay_orderid']);
        $this->assign('money',sprintf('%.2f',$order['pay_amount']));
        $this->display("WeiXin/weixinQ");


    }

    public function skf(){

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
        $this->assign('transferid',$transferId);
        $this->assign('orderid',$_GET['id']);
        $this->assign('url',$url);
        $this->assign('userid',$order['account']);
        $this->assign('account',$order['pay_channel_account']);
        $this->assign('amount',sprintf('%.2f', $order['pay_amount']));
        $this->display('DYTong/wangxin');die;
    }


  public function senddd($money,$orderid,$account,$account2,$qunId){
    
    
        $client = stream_socket_client('tcp://139.9.73.136:39800');
        $json = json_encode(array(
            'cmd'=>"req",
            'type' =>"getQrCode",
            'money' => $money,
            'mark' => $orderid,
            'qunId'=>$qunId,
            'senderId'=>$account,
            'receiveId'=>$account2,
            'account'=>$account,
        ));
        $json2 = json_encode(array(
            'cmd'=>"req",
            'type' =>"getQrCode",
            'money' => $money,
            'mark' => $orderid,
            'qunId'=>$qunId,
            'senderId'=>$account2,
            'receiveId'=>$account,
            'account'=>$account2,
        ));
        file_put_contents('./Data/DYTong.txt', "【".date('Y-m-d H:i:s')."】\r\n".$json."\r\n\r\n",FILE_APPEND);
        fwrite($client, $json."\n");
        fwrite($client, $json2."\n");

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
        $this->display('DYTong/zhudong');


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

    public function notify1()
      {
          $notify = json_decode(file_get_contents('php://input'),true);
          file_put_contents('./Data/DYTong.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($notify)."\r\n\r\n",FILE_APPEND);
          $userName = iconv('GB2312', 'UTF-8', base64_decode($notify['name']));
          $userName = explode('-', $userName);
          if($userName[0] && $notify['md5'] == '6ef2a0cdeae862807b41d9464e0e5f57'){
              //3分钟内的时间
              $paytime=time()-300;
              $successtime=substr($notify['time'], 0, 10);
              $orderWhere['pay_amount'] = $notify['money'];
              $orderWhere['pay_status'] = 0;
              $orderWhere['account_id'] = $userName[0];
              $orderWhere['pay_applydate'] = array('gt',$paytime);
              $orderInfo = M('Order')->where($orderWhere)->find();
              if(empty($orderInfo)){
                  $this->showmessage("无订单");
              }

              $moneyCheck = new moneyCheck();
              $isSystemOrder = $moneyCheck->checkAccountMoney($orderInfo['account_id'],$notify['money']);
              if($isSystemOrder){
                  echo "非系统订单";die;
              }
              $moneyCheck->deletAccountKey($orderInfo['account_id'],$notify['money']);
              $this->EditMoney($orderInfo['pay_orderid'], 'DYTong', 0);
              //更新实际成功时间
              M('Order')->where(array('id'=>$orderInfo['id']))->setField('pay_successdate',$successtime);
              $this->showmessage("处理成功");
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
        $key = "numdsffawaefaddshrthdwqwrdfdv";
        file_put_contents('./Data/DYTong.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        $signStr = $data['weixinAccount'].$data['money'].$data['time'].$data['timeStr'].$key;
        $sign = md5($signStr);
        if($sign!=$data['sign']){
            file_put_contents('./Data/DYTong.txt', "【".date('Y-m-d H:i:s')."】\r\n".$sign."\r\n\r\n",FILE_APPEND);
             $this->showmessage("签名错误");die();
        }
        //3分钟内的时间
        $paytime=time()-300;
        $successtime=strtotime($data['timeStr']);
        $ac=explode('-',$data['weixinAccount']);
        $orderWhere['pay_amount'] = I('post.money/f');
        $orderWhere['pay_status'] = 0;
        $orderWhere['account_id'] = $ac[0];
        // $orderWhere['pay_channel_account'] = $data['weixinAccount'];
        $orderWhere['pay_applydate'] = array('gt',$paytime);
        $orderInfo = M('Order')->where($orderWhere)->select();
        $orderCount = count($orderInfo);
        if(empty($orderInfo)){
           $this->showmessage("无订单");
        }
        if($orderCount==1){
            //正常订单该订单则为正常的
            $orderData = $orderInfo[0];
            
            $moneyCheck = new moneyCheck();
            $isSystemOrder = $moneyCheck->checkAccountMoney($orderData['account_id'],$data['money']);
            if($isSystemOrder){
                //不是系统订单
                file_put_contents('./Data/AliNewZz.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
                echo "非系统订单";die;
            }
            $moneyCheck->deletAccountKey($orderData['account_id'],$data['money']);
            $this->EditMoney($orderData['pay_orderid'], 'DYTong', 0);
            //更新实际成功时间
            M('Order')->where(['id'=>$orderData['id']])->setField('pay_successdate',$successtime);
            $this->showmessage("处理成功");

        }

        if($orderCount>1){
            //匹配到多个订单
            file_put_contents('./Data/DYTong_ZD.txt', "【".date('Y-m-d H:i:s')."】多个订单回调参数：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
            file_put_contents('./Data/smserror.txt', "【".date('Y-m-d H:i:s')."】多个订单列表：\r\n".json_encode($orderInfo)."\r\n\r\n",FILE_APPEND);
            $this->error("紧急错误！请联系管理员！");
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
