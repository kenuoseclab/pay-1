<?php


namespace Pay\Controller;


class MowoolController extends PayController
{

   
    //商户appid->到平台首页自行复制粘贴
    private $appid = '1071185';

    //商户密钥，到平台首页自行复制粘贴，该参数无需上传，用来做签名验证和回调验证，请勿泄露
    private $app_key = '2hTJ0P5VTgAKSiWJvnqVOO24FC8r7AoD';

    /**
     *  发起支付
     */
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site ."Pay_Mowool_notifyurl.html"; //异步通知
        $callbackurl = $this->_site . 'Pay_Mowool_callbackurl.html'; //跳转通知
        $parameter = [
            'code'          => 'Mowool',
            'title'         => 'Mowool',
            'exchange'      => 1, // 金额比例
            'gateway'       => '',
            'orderid'       => '',  // 订单号，可以为空，如果为空，由系统统一的生成
            'out_trade_id'  => $orderid, //外部订单号
            'channel'       => $array,
            'body'          => $body
        ];

        
        $return = $this->orderadd($parameter);
        //配置你的信息
            
        //请求支付地址
        $api = 'http://api.mowool.com/index/unifiedorder';
        

        //订单号码->这个是四方网站发起订单时带的订单信息，一般为用户名，交易号，等字段信息
        $out_trade_no = $return['orderid'];
        //支付类型alipay、wechat
        $pay_type = $return['gateway'];
        //支付金额
        $amount = sprintf("%.2f",number_format($return['amount'],2));
        //异步通知接口url->用作于接收成功支付后回调请求
        $callback_url = $notifyurl;
        //支付成功后自动跳转url
        $success_url = $callbackurl;
        //支付失败或者超时后跳转url
        $error_url = '';//$this->_site . 'Pay_Mowool_callbackurl.html';
        //版本号
        $version = 'v2.0';
        //用户网站的请求支付用户信息，可以是帐号也可以是数据库的ID:858887906
        $out_uid = '';

        $data = [
            'return_type'  => 'app',
            'appid'        => $return['mch_id']?:$this->appid,
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
        $sign = $this->getSign($return['signkey']?:$this->app_key, $data);
        $data['sign'] = $sign;

        $this->assign('form',$data);
        $this->assign('formUrl',$api);
        $this->display('Mowool/pay');
    }

/**
     * @Note  生成签名
     * @param $secret   商户密钥
     * @param $data     参与签名的参数
     * @return string
     */
    function getSign($secret, $data)
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


    /**
     * @Note   验证签名
     * @param $data
     * @param $orderStatus
     * @return bool
     */
     function verifySign($data, $secret) {
        // 验证参数中是否有签名
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        // 要验证的签名串
        $sign = $data['sign'];
        unset($data['sign']);
        // 生成新的签名、验证传过来的签名
        $sign2 = $this->getSign($secret, $data);

        if ($sign != $sign2) {
            return false;
        }
        return true;
    }

    function curlPost($url = '', $postData = '', $options = array()){
        if (is_array($postData))  $postData = http_build_query($postData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        if (!empty($options)) curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    function outSign($arr,$key,$hach){
        $str ='app_id='.$arr['app_id'];
        $str.='&method='.$arr['method'];
        $str.='&pay_time='.$arr['pay_time'];
        $str.='&trade_amount='.$arr['trade_amount'];
        $str.='&trade_no='.$arr['trade_no'];
        $str.='&app_secrect='.$key;
        $sign = ($hach == 'sha1') ? strtoupper(sha1($str)) : strtoupper(md5($str));
        return substr($sign,1);
    }



    /**
     *  服务器通知
     */
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
            'success_url'  => $success_url,
            'error_url'    => $error_url,
            'out_trade_no'      => $out_trade_no,
            'amount_true'      => $amount_true,
            'out_uid'      => $out_uid,
            'sign'      => $sign,
        ];
        file_put_contents('Data/Mowool.txt', "Mowool【" . date('Y-m-d H:i:s') . "】notifyurl提交1结果：" . var_export($_POST,true) . "\r\n\r\n", FILE_APPEND);

        $order = M('Order')
            ->where(['pay_orderid' => preg_replace('/(\W+)/', '', $out_trade_no)])
            ->find();

            file_put_contents('Data/Mowool.txt', "Mowool【" . date('Y-m-d H:i:s') . "】notifyurl提交2结果：" . var_export($order,true) . "\r\n\r\n", FILE_APPEND);


        if(!$order)exit('error:oor');

        //检查金额
        if ($order['pay_amount'] != $data['amount_true']){
            file_put_contents('Data/Mowool.txt', "Mowool【" . date('Y-m-d H:i:s') . "】：error:amount:{$order['pay_amount']}-{$data['amount_true']}\r\n\r\n", FILE_APPEND);
             exit('error:amount');

        }

        //第一步，检测商户appid否一致
        if ($appid != ($order['memberid']?:$this->appid)){
            file_put_contents('Data/Mowool.txt', "Mowool【" . date('Y-m-d H:i:s') . "】notifyurl提交3结果：error:appid:$appid-" . $order['memberid']. "\r\n\r\n", FILE_APPEND);
             exit('error:appid');

        }
        //第二步，验证签名是否一致
        if ($this->verifySign($data,$order['key']?:$this->app_key) != $sign){
            file_put_contents('Data/Mowool.txt', "Mowool【" . date('Y-m-d H:i:s') . "】notifyurl提交4结果：order_key:" . $order['key']. "\r\n\r\n", FILE_APPEND);
            exit('error:sign');
        }
        
        
            $this->EditMoney($data['out_trade_no'], 'Mowool', 0);
            exit("success");
        
    }

    public function callbackurl()
    {
        //var_dump($_POST);
        //var_dump($_GET);
        $this->display('WeiXin/success');
    }
}
