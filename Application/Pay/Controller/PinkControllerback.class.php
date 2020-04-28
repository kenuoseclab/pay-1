<?php
/**
 * Created by PhpStorm.
 * Date: 2018-12-26
 * Time: 15:06
 */

namespace Pay\Controller;
class PinkController extends PayController
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
        $notifyurl = $this->_site . 'Pay_Pink_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Pink_callbackurl.html'; //返回通知

        $orderid = I("request.pay_orderid", '');

        $body = I('request.pay_productname', '');

        $parameter = [
            'code'         => 'Pink',
            'title'        => 'Pink支付宝H5',
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
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_Pink_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_Pink_callbackurl.html';

        error_reporting(0);
        header("Content-type: text/html; charset=utf-8");
        $pay_memberid = $return["mch_id"];  //商户ID
        $pay_orderid = $return['orderid'];   //订单号，需要保证唯一，不可重复提交相同订单ID
        $pay_amount = number_format($return['amount'],2,'.',''); //交易金额
        $pay_applydate = date("Y-m-d H:i:s");  //订单时间
        $pay_notifyurl = $return["notifyurl"]; //服务端返回地址
        $pay_callbackurl = $return['callbackurl']; //页面跳转返回地址
        $Md5key = $return['signkey']; //密钥 
        $tjurl = $return['gateway']; //提交地址
        $pay_bankcode = $return['appid'];   //银行编码
        //扫码
        $native = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        //echo($md5str . "key=" . $Md5key);
        $sign = strtoupper(md5($md5str . "key=" . $Md5key));
        $native["pay_md5sign"] = $sign;
        $native['pay_attach'] = "1234|456";
        $native['pay_productname'] ='VIP基础服务';
        $native['pay_return_type'] = "html";//html：网页，json：为接口json方式输出

        $str = '<form id="Form1" name="Form1" method="post" action="' .$tjurl. '">';
        $str = $str . '<input type="text" name="authcode" value="">';
        foreach ($native as $key => $val) {
            $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '">';
        }
        //$str = $str . '<input type="submit" value="提交">';
        $str = $str . '</form>';
        $str = $str . '<script>';
        $str = $str . 'document.Form1.submit();';
        $str = $str . '</script>';
        echo $str;
        return;
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
         $returnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 支付流水号
            "returncode" => $_REQUEST["returncode"],
        );
        $orderid=I('request.orderid/s');
        $md5key = getKey($orderid);
        ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $md5key));
        if ($sign == $_REQUEST["sign"]) {
            if ($_REQUEST["returncode"] == "00") {
                   $this->EditMoney($orderid, 'Pink', 0);
                   exit("ok");
            }
        }

      }



   }