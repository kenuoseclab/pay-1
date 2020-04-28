<?php
/**
 * Created by PhpStorm.
 * Date: 2018-12-26
 * Time: 15:06
 */

namespace Pay\Controller;
class IconController extends PayController
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
        $notifyurl = $this->_site . 'Pay_Icon_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Icon_callbackurl.html'; //返回通知

        $orderid = I("request.pay_orderid", '');

        $body = I('request.pay_productname', '');

        $parameter = [
            'code'         => 'Icon',
            'title'        => 'Icon支付宝H5',
            'exchange'     => 1, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => $orderid, //外部订单号
            'channel'      => $array,
            'body'         => $body,
        ];

        //支付金额
        $pay_amount = I("request.pay_amount", 0);

        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);
        //跳转页面，优先取数据库中的跳转页面
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_Icon_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_Icon_callbackurl.html';

        error_reporting(0);
        header("Content-type: text/html; charset=utf-8");
        $tjurl = "https://s.starfireotc.com/payLink/web.html"; //提交地址
        $md5Key="7e0e458e5af5366757259b60d3c1d41d";
        // $tjurl = $return['gateway']; //提交地址
        $APPKey="75f3a370bad04323bda0e2638805e57b";
        $customerAmountCny=number_format($return['amount'],2,'.',''); //交易金额
        $outOrderId=$return['orderid'];   //订单号，需要保证唯一，不可重复提交相同订单ID
        $pickupUrl=$return["callbackurl"];
        $receiveUrl=$return["notifyurl"];
        $signType="MD5";
        $sign=md5($outOrderId.$pickupUrl.$receiveUrl.$customerAmountCny.$signType.$md5Key);
        $payurl=$tjurl."?outOrderId={$outOrderId}&APPKey={$APPKey}&customerAmountCny={$customerAmountCny}&pickupUrl={$pickupUrl}&receiveUrl={$receiveUrl}&signType={$signType}&sign={$sign}";
        header("Location:".$payurl);

        
    }

    private function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }


    //同步通知
    public function callbackurl()
    {
        $Order = M("Order");
        $orderid=I('request.orderid/s');  
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_GET["orderid"], 'Jmalipay', 1);
        }else{
            exit("交易失败");
        }

    }

    //异步通知
    public function notifyurl()
    {
        $data=I('post.');
        file_put_contents('./Data/AliCode.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        $customerAmount=I('request.customerAmount/s');
        $customerAmountCny=I('request.customerAmountCny/s');
        $outOrderId=I('request.outOrderId/s');
        $orderId=I('request.orderId/s');
        $signType=I('request.signType/s');
        $status=I('request.status/s');
        $sign=I('request.sign/s');
        $md5Key="7e0e458e5af5366757259b60d3c1d41d";
        $s=md5($customerAmount.$customerAmountCny.$outOrderId.$orderId.$signType.$status.$md5Key);

        if ($sign == $s) {
            if ($status == "success") {
                   //$this->EditMoney($outOrderId, 'Icon', 0);
                   exit("success");
            }
        }

      }



   }