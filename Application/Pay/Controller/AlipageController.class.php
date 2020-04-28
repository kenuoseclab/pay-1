<?php
/**
 * Created by PhpStorm.
 * Date: 2018-10-30
 * Time: 12:00
 */
namespace Pay\Controller;

class AlipageController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
        $orderid     = I("request.pay_orderid");
        $body        = I('request.pay_productname');
        $notifyurl   = $this->_site . 'Pay_Alipage_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Alipage_callbackurl.html'; //返回通知

        $parameter = array(
            'code'         => 'Alipage', // 通道名称
            'title'        => '支付宝官方扫码',
            'exchange'     => 1, // 金额比例
            'gateway'      => '',
            'orderid'      => $orderid,
            'out_trade_id' => $orderid,
            'body'         => $body,
            'channel'      => $array,
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $return['subject'] = $body;

        //---------------------引入支付宝第三方类-----------------
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradePagePayRequest');
        //组装系统参数
        $data = [
            'out_trade_no' => $return['orderid'],
            'total_amount' => $return['amount'],
            'subject'      => $return['subject'],
            'product_code' => "FAST_INSTANT_TRADE_PAY",
        ];

        $sysParams               = json_encode($data, JSON_UNESCAPED_UNICODE);
        $aop                     = new \AopClient();
        $aop->gatewayUrl         = 'https://openapi.alipay.com/gateway.do';
        $aop->appId              = $return['appid'];
        $aop->rsaPrivateKey      = $return['appsecret'];
        $aop->alipayrsaPublicKey = $return['signkey'];
        $aop->apiVersion         = '1.0';
        $aop->signType           = 'RSA2';
        $aop->postCharset        = 'UTF-8';
        $aop->format             = 'json';
        $aop->debugInfo          = true;
        $request                 = new \AlipayTradePagePayRequest();
        $request->setBizContent($sysParams);
        $request->setNotifyUrl($return['notifyurl']);
        $request->setReturnUrl($return['callbackurl']);
        $result = $aop->pageExecute($request,'post');
        echo $result;

    
    }


    //同步通知
    public function callbackurl()
    {
        $Order      = M("Order");
        $orderid=I('request.out_trade_no/s');  
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        if ($pay_status != 0) {
            $this->EditMoney($_REQUEST["out_trade_no"], '', 1); //第三个参数为1时，页面会跳转到订单信息里面的 pay_callbackurl
        } else {
            exit("交易成功！");
        }
    }

    //异步通知
    public function notifyurl()
    {
        $response  = $_POST;
        $sign      = $response['sign'];
        $sign_type = $response['sign_type'];
        $outno=I('post.out_trade_no/s');
        $publiKey = getKey($outno); // 密钥
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayTradeWapPayRequest');
        $aop = new \AopClient();
        $aop->alipayrsaPublicKey = $publiKey;
        $result = $aop->rsaCheckV1($response, $publiKey, $sign_type);
        if ($result) {
            if ($response['trade_status'] == 'TRADE_SUCCESS' || $response['trade_status'] == 'TRADE_FINISHED') {
                $this->EditMoney($response['out_trade_no'], '', 0);
                // 分账控制器
                $data = [
                    'separate_orderid'=>$response['out_trade_no'],
                    'separate_trade_no'=>$response['trade_no'],
                ];
                R("Separate/index",[$data]);
                exit("success");
            }
        } else {
            exit('error:check sign Fail!');
        }

    }

}
