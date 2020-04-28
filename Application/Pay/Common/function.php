<?php
function createForm($url, $data)
{
    $str = '<!doctype html>
            <html>
                <head>
                    <meta charset="utf8">
                    <title>正在跳转付款页</title>
                </head>
                <body onLoad="document.pay.submit()">
                <form method="post" action="' . $url . '" name="pay">';

    foreach ($data as $k => $vo) {
        $str .= '<input type="hidden" name="' . $k . '" value="' . $vo . '">';
    }

    $str .= '</form>
                <body>
            </html>';
    return $str;
}

function encryptDecrypt($string, $key = '', $decrypt = '0')
{
    if ($decrypt) {
        $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "12");
        return $decrypted;
    } else {
        $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
        return $encrypted;
    }
}

function getKey($orderid)
{
    $key = M('Order')->where(['pay_orderid' => $orderid])->getField('key');
    return $key;
}

function getLocalIP()
{
    $preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";
    //获取操作系统为win2000/xp、win7的本机IP真实地址
    exec("ipconfig", $out, $stats);
    if (!empty($out)) {
        foreach ($out as $row) {
            if (strstr($row, "IP") && strstr($row, ":") && !strstr($row, "IPv6")) {
                $tmpIp = explode(":", $row);
                if (preg_match($preg, trim($tmpIp[1]))) {
                    return trim($tmpIp[1]);
                }
            }
        }
    }
    //获取操作系统为linux类型的本机IP真实地址
    exec("ifconfig", $out, $stats);
    if (!empty($out)) {
        if (isset($out[1]) && strstr($out[1], 'addr:')) {
            $tmpArray = explode(":", $out[1]);
            $tmpIp    = explode(" ", $tmpArray[1]);
            if (preg_match($preg, trim($tmpIp[0]))) {
                return trim($tmpIp[0]);
            }
        }
    }
    return '127.0.0.1';
}
function getIP()
{
    if (getenv('HTTP_CLIENT_IP')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_X_FORWARDED')) {
        $ip = getenv('HTTP_X_FORWARDED');
    } elseif (getenv('HTTP_FORWARDED_FOR')) {
        $ip = getenv('HTTP_FORWARDED_FOR');
    } elseif (getenv('HTTP_FORWARDED')) {
        $ip = getenv('HTTP_FORWARDED');
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function log_separate($order,$orderid, $zfb_out_pid, $amount,$param, $code, $msg) {
    $filePath = './Data/separate/';
    if(mkdirs($filePath)) {
        $destination = $filePath.date('y_m_d').'.log';
        if(!file_exists($destination)) {
            fopen($destination,   'wb ');
        }
        file_put_contents($destination, "【".date('Y-m-d H:i:s')."】\r\n单号：".$order."\r\n订单号：".$orderid."\r\n支付宝入账账号：".$zfb_out_pid."\r\n金额：".$amount."\r\n提交信息：".$param."\r\n返回码：".$code."\r\n返回信息：".$msg."\r\n\r\n",FILE_APPEND);
        return true;
    }
    return false;
}

function separateAdd($data){
    $res = M("SeparateOrder")->add($data);
    if($res){
        return true;
    }else{
        return false;
    }
}

/**
 * 调用新浪接口将长链接转为短链接
 * @param  string        $source    申请应用的AppKey
 * @param  array|string  $url_long  长链接，支持多个转换（需要先执行urlencode)
 * @return array
 */
function getSinaShortUrl($source, $url_long){

    // 参数检查
    if(empty($source) || !$url_long){
        return false;
    }

    // 参数处理，字符串转为数组
    if(!is_array($url_long)){
        $url_long = array($url_long);
    }

    // 拼接url_long参数请求格式
    $url_param = array_map(function($value){
        return '&url_long='.urlencode($value);
    }, $url_long);

    $url_param = implode('', $url_param);

    // 新浪生成短链接接口
    $api = 'http://api.t.sina.com.cn/short_url/shorten.json';

    // 请求url
    $request_url = sprintf($api.'?source=%s%s', $source, $url_param);

    $result = array();

    // 执行请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $request_url);
    $data = curl_exec($ch);
    if($error=curl_errno($ch)){
        return false;
    }
    curl_close($ch);

    $result = json_decode($data, true);

    return $result;

}


