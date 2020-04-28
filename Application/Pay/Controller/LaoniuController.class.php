<?php
/**
 * Created by PhpStorm.
 * Date: 2018-12-26
 * Time: 15:06
 */

namespace Pay\Controller;
class LaoniuController extends PayController
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
        $notifyurl = $this->_site . 'Pay_Laoniu_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Laoniu_callbackurl.html'; //返回通知

        $orderid = I("request.pay_orderid", '');

        $body = I('request.pay_productname', '');

        $parameter = [
            'code'         => 'Laoniu',
            'title'        => 'Laoniu支付宝H5',
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
        $return["notifyurl"] || $return["notifyurl"] = $this->_site . 'Pay_Laoniu_notifyurl.html';
        $return['callbackurl'] || $return['callbackurl'] = $this->_site . 'Pay_Laoniu_callbackurl.html';

        error_reporting(0);
        header("Content-type: text/html; charset=utf-8");
        //请求支付地址
        $tjurl = $return['gateway']; //提交地址
        //商户appid->到平台首页自行复制粘贴
        $appid = $return["mch_id"];

        //商户密钥，到平台首页自行复制粘贴，该参数无需上传，用来做签名验证和回调验证，请勿泄露
        $app_key = $return['signkey']; //密钥 

        //订单号码->这个是四方网站发起订单时带的订单信息，一般为用户名，交易号，等字段信息
        $out_trade_no = $return['orderid']; 
        //支付类型alipay、wechat
        $pay_type = $return['appid'];
        //支付金额
        $amount = number_format($return['amount'],2,'.',''); //交易金额
        //异步通知接口url->用作于接收成功支付后回调请求
        $callback_url = $return["notifyurl"]; //服务端返回地址
        //支付成功后自动跳转url
        $success_url = $return['callbackurl']; //页面跳转返回地址
        //支付失败或者超时后跳转url
        $error_url = $return['callbackurl']; //页面跳转返回地址
        //版本号
        $version = 'v1.1';
        //用户网站的请求支付用户信息，可以是帐号也可以是数据库的ID:858887906
        $out_uid = '';

        $native = [
            'appid'        => $appid,
            'pay_type'     => $pay_type,
            'out_trade_no' => $out_trade_no,
            'amount'       => $amount,
            'callback_url' => $callback_url,
            'success_url'  => $success_url,
            'error_url'    => $error_url,
            'version'      => $version,
            'out_uid'      => $out_uid,
        ];

        //拿APPKEY与请求参数进行签名
        $sign = $this->getSign1($app_key, $native);
        $native["sign"] = $sign;
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

    private function getSign1($secret, $data)
    {

        // 去空
        $data = array_filter($data);

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = http_build_query($data);
        $string_a = urldecode($string_a);

        //签名步骤二：在string后加入mch_key
        $string_sign_temp = $string_a . "&key=" . $secret;

        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }

    private function verifySign1($data, $secret) {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign1($secret, $data);

        if ($sign != $sign2) {
            return false;
        }
        return true;
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
        //商户名称
        $appid  = $_POST['appid'];
        //支付时间戳
        $callbacks  = $_POST['callbacks'];
        //支付状态
        $pay_type  = $_POST['pay_type'];
        //支付金额
        $amount  = $_POST['amount'];
        //支付时提交的订单信息
        $success_url  = $_POST['success_url'];
        //平台订单交易流水号
        $error_url  = $_POST['error_url'];
        //该笔交易手续费用
        $out_trade_no  = $_POST['out_trade_no'];
        //实付金额
        $amount_true  = $_POST['amount_true'];
        //用户请求uid
        $out_uid  = $_POST['out_uid'];
        //回调时间戳
        $sign  = $_POST['sign'];

        $data = [
            'appid'        => $appid,
            'callbacks'     => $callbacks,
            'pay_type' => $pay_type,
            'amount'       => $amount,
            'callback_url' => $callback_url,
            'success_url'  => $success_url,
            'error_url'    => $error_url,
            'out_trade_no'      => $out_trade_no,
            'amount_true'      => $amount_true,
            'out_uid'      => $out_uid,
            'sign'      => $sign,
        ];
        $orderid=I('post.out_trade_no/s');
        $key=getKey($orderid);
        //第一步，检测商户appid否一致
        // if ($appid != '你的商户appid') exit('error:appid');
        //第二步，验证签名是否一致
        if ($this->verifySign1($data,$key) != $sign) exit('error:sign');
        $this->EditMoney($orderid, 'Laoniu', 0);
        echo 'success'; 

      }



   }