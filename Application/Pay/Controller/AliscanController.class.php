<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-05-18
 * Time: 11:33
 */
namespace Pay\Controller;
use Think\Log;
use Think\Exception;

class AliscanController extends PayController
{

    private $code = '';

    public function __construct()
    {
        parent::__construct();

        $matches = [];
        preg_match('/([\da-zA-Z\_]+)Controller$/', __CLASS__, $matches);
        $this->code = $matches[1];
    }

    //支付
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
//        $notifyurl = $this->_site . 'Pay_Aliscan_notifyurl.html'; //异步通知
//        $callbackurl = $this->_site . 'Pay_Aliscan_callbackurl.html'; //返回通知

        $parameter = array(
            'code' => 'Aliscan', // 通道名称
            'title' => '支付宝官方扫码',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        //201809
        $site        = trim($return['unlockdomain']) ? $return['unlockdomain'] . '/' : $this->_site;
        $gateway     = "{$site}Pay_{$this->code}_Rpay.html";
        $notifyUrl   = "{$site}Pay_{$this->code}_notifyurl.html";
        $callbackUrl = "{$site}Pay_{$this->code}_callbackurl.html";

        $return['notifyurl']   = $notifyUrl;
        $return['callbackurl'] = $callbackUrl;

        /* $this->EditMoney($return['orderid'], 'Aliscan', 0);
         die;*/
        $return['subject'] = $body;

        //---------------------引入支付宝第三方类-----------------
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradePrecreateRequest');
        //组装系统参数
        $data = [
            'out_trade_no'=>$return['orderid'],
            'total_amount'=>$return['amount'],
            'subject'=>$return['subject'] ? $return['subject'] : '购买商品',
            'disable_pay_channels'=>"credit_group"
        ];
        $sysParams = json_encode($data,JSON_UNESCAPED_UNICODE);
        $aop = new \AopClient();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $return['mch_id'];
        $aop->rsaPrivateKey = $return['appsecret'];
        $aop->alipayrsaPublicKey= $return['signkey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        // var_dump($aop);exit;
        $request = new \AlipayTradePrecreateRequest ();

        $request->setBizContent($sysParams);
        $request->setNotifyUrl($return['notifyurl']);
        $result = $aop->execute($request);
        // var_dump($result);exit;

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $resultCode = $result->$responseNode->code;

        //请求返回201809
        Log::record("支付宝扫码下单商户：".$return['mch_id']."订单号：".$return['orderid']."返回代码：".$resultCode."错误提示：".$result->$responseNode->msg, Log::ERR);
//      print_r($result);die("结束");
      
        if(!empty($resultCode)&&$resultCode == 10000){
      
          $url = urldecode($result->$responseNode->qr_code);
          $url="alipays://platformapi/startapp?appId=20000067&url=".$url;
          import("Vendor.phpqrcode.phpqrcode",'',".php");
        $QR = "Uploads/codepay/". $return['orderid'] . ".png";
        \QRcode::png($url, $QR, "L", 20);
        $this->assign("imgurl", '/'.$QR);
        $this->assign('params',$return);
        $this->assign('orderid',$return['orderid']);
        $this->assign('money',sprintf('%.2f',$return['amount']));
        $this->assign('zfbpayUrl',$url);
        $this->display("WeiXin/alipayori");


          die;
          echo createForm(
                $gateway,
                [
                    'data'    => encryptDecrypt(serialize([
                        'url'    => $url,
                        'return' => $this->filterData($return),
                        'view'   => 'alipay',
                    ]), 'lgbya!'),
                ]
            );

            return;
        } else {
            Log::record("支付宝扫码下单商户：".$return['mch_id']."订单号：".$return['orderid']."返回代码：".$resultCode."错误提示：".$result->$responseNode->msg, Log::ERR);
            echo "失败";
        }
        exit();
    }

    /**
     *  过滤敏感信息
     */
    private function filterData($return)
    {
        unset($return['mch_id']);//商户id
        unset($return['signkey']);
        unset($return['appid']);
        unset($return['appsecret']);

        return $return;
    }

    /**
     * 发起支付操作
     */
    public function Rpay()
    {
        //接收传输的数据
        $postData = I('post.', '');
        //将数据解密并反序列化
        $formData = unserialize(encryptDecrypt($postData['data'], 'lgbya!', 1));


        //判断是否手机端，如果手机端且是支付宝H5支付直接跳转支付
        if(isAliMobile()||$formData['return']['bankcode']==904)
        {
               $url="alipays://platformapi/startapp?appId=20000067&url=".$formData['url'];
               redirect($url,1, '跳转支付中...');
        }
        else{
            if(isMobile())
            {

                $this->showQRcode($formData['url'], $formData['return'], "alipay_wap");
            }
            else {
                //否则跳转收银台
                $this->showQRcode($formData['url'], $formData['return'], $formData['view']);
           }
        }
    }


    //同步通知
    public function callbackurl()
    {
        $Order = M("Order");
        $orderid=I('request.out_trade_no/s'); 
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["orderid"], 'Aliscan', 1);
        }else{
            exit("支付成功");
        }

    }

    //异步通知
    public function notifyurl()
    {
        $response = $_POST;
        $sign = $response['sign'];
        $sign_type = $response['sign_type'];
        unset($response['sign']);
        unset($response['sign_type']);
        $outno=I('post.out_trade_no/s');
        $publiKey = getKey($outno); // 密钥

        ksort($response);
        $signData = '';
        foreach ($response as $key=>$val){
            $signData .= $key .'='.$val."&";
        }
        $signData = trim($signData,'&');
        //$checkResult = $aop->verify($signData,$sign,$publiKey,$sign_type);
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($publiKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        $result = (bool)openssl_verify($signData, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);

        if($result){
            if($response['trade_status'] == 'TRADE_SUCCESS' || $response['trade_status'] == 'TRADE_FINISHED'){
                $this->EditMoney($response['out_trade_no'], 'Aliscan', 0);
                // 分账控制器
                $data = [
                    'separate_orderid'=>$response['out_trade_no'],
                    'separate_trade_no'=>$response['trade_no'],
                ];
                R("Separate/index",[$data]);
                exit("success");
            }
        }else{
            exit('error:check sign Fail!');
        }

    }

}