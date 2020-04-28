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
class MsymtController extends PayController
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
            'code' => 'Msymt', // 通道名称
            'title' => '民生一码通扫码',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        $notifyurl = $this->_site . 'Pay_Msymt_notifyurl.html';
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
        

        $payurl = U('Msymt/orderdetail',array('id'=>$return['orderid']),true,true);

        //订单剩余的时间：
        $this->assign('orderlasttime',300);
        $this->assign("imgurl", $return['appid']);
        $this->assign("qrcode",$return['appid']);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$pay_amount));
        $this->display("WeiXin/msymt");

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
        $this->assign("imgurl", $order['account']);
        $this->assign("qrcode",$order['account']);
        $this->assign('params',$order);
        $this->assign('orderid',$order['pay_orderid']);
        $this->assign('money',sprintf('%.2f',$order['pay_amount']));
        $this->display("WeiXin/msymt");


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
        $this->display('Msymt/wangxin');die;
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
        $this->display('Msymt/zhudong');


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
              $this->EditMoney($orderInfo['pay_orderid'], 'Msymt', 0);
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
    public function notify()
    {
        // $data = I('request.');
        $data = file_get_contents("php://input");
        $datas=json_decode($data,true);
        foreach ($datas as $k => $v) {
            $sign=$this->getsign($v);
            if($sign==$v['sign']){
                $paytime=time()-300;
                $orderWhere['memberid']=$v['business_no'];
                $orderWhere['pay_amount']=$v['real_money'];
                $orderWhere['account_id']=$v['accu_id'];
                $orderWhere['pay_applydate'] = array('gt',$paytime);
                $orderWhere['pay_status'] = 0;

                $orderInfo = M('Order')->where($orderWhere)->select();
                $orderCount = count($orderInfo);
                if(empty($orderInfo)){
                   $this->showmessage("无订单");
                }
                if($orderCount==1){
                    //正常订单该订单则为正常的
                    $orderData = $orderInfo[0];
                    
                    $moneyCheck = new moneyCheck();
                    $isSystemOrder = $moneyCheck->checkAccountMoney($orderData['account_id'],$v['real_money']);
                    if($isSystemOrder){
                        //不是系统订单
                        $this->showmessage("非系统订单");
                    }
                    $moneyCheck->deletAccountKey($orderData['account_id'],$v['real_money']);
                    $this->EditMoney($orderData['pay_orderid'], 'Msymt', 0);
                    //更新实际成功时间
                    M('Order')->where(['id'=>$orderData['id']])->setField('attach',$v['order_no']);
                    $this->showmessage("处理成功");

                }

                if($orderCount>1){
                    //匹配到多个订单
                    file_put_contents('./Data/Msymt_ZD.txt', "【".date('Y-m-d H:i:s')."】多个订单回调参数：\r\n".var_export($datas,true)."\r\n\r\n",FILE_APPEND);
                    file_put_contents('./Data/smserror.txt', "【".date('Y-m-d H:i:s')."】多个订单列表：\r\n".json_encode($orderInfo)."\r\n\r\n",FILE_APPEND);
                    $this->error("紧急错误！请联系管理员！");
                }
            }else{
                $this->showmessage("签名错误");
            }
        }

             
    }

    protected function getsign($v){
        unset($v['sign']);
        ksort($v);
        $md5str = "";
        foreach ($v as $key => $val) {
            $md5str = $md5str . $key . "=\"" . $val . "\"&";
        }
        $sign=md5($md5str);
        return $sign;
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
