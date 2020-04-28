<?php

class authorize
{
    protected $appId;
    protected $charset;
    protected $scope;
    //私钥值
    protected $rsaPrivateKey;
    protected $auth_code;
    public function __construct()
    {
        $this->charset = 'GBK';
        $this->appId = "2019032263663021";
        $this->scope = "auth_base";
        $this->rsaPrivateKey = 'MIIEpAIBAAKCAQEAqvyIQJso5iL6F4nULpRoe5bwT6cBgWILvp7X1XBTKWpfNWWEOkgQfycTA984eXQmbLtUKmvH1rHTZL7tGeqpJMh2Zw0x+JolNEqfLtV9eJ2VGqAhbBlbiUjxFE/CTkj3SKEfjTOdn2N4Fg2P26bWyD5zmN8JguDRryoU5Eoa/gyjtev/Tg//v3ymCEIDtvFFWLP93v99rL+0/yeF4seyUaXZ2MAHYOmOm/F+GlAAu/lyAHHFaUnGIp4Yj1Pb6ueyCSO5ZwMTVyF7OlJYzVcxiK6H+mvPRVwU2i9Ugcn2LbwVaL4ZKuHbLn+JGznieurs3/Pd7D0hl4Zqf4fVpWMyDwIDAQABAoIBAQCOaEs2u8OhgOoYZqPAs7wdiwXU4TxAvR8ZAQSz8JxitwRa3ZT2UYTj0fBlXimUBifmkVK1DhVfe7Wbh+TDnAyJ5SewBm5jJkbsOWZAxHB+34gKp+mONcRmH8kh6JflSaIi7IbxvFaAJIEkpOD2yQrDtjfiz4gFaCdbo0nJJOs8QH+cYZfqfkhleUPF0qiJqDRAR8zqPfS5XmZW6PM8wcqPYMjFNo5krK5aLnspqT9NRoF1lJ2N6WT74Je3Tw3ZI/yiocgzQJpNR01k0WjjpP//40a6VLLBh0fS9vq6eEE6CNDC0nU7fxentHgqe2cJKSvzpuoS7+nlcMCbQRgP2LHhAoGBAN1tcmR2VjZJ+bDlsLYlWWggrMSTmNviuTu8rlMgGHBGqv3dAlAAU7sWJEwlsza0z084Db3T30z4oAQ7J48VNUKQVNr3v6D7Fg+2yKC2zup+RBNvSCoswH/TAIXaxr2wTq2nJZzA4UEqtWO0FTXnH/orzPjI3gNky/KQQAFA0Gd/AoGBAMWu7yDL3kMtgroBp7S4rogm84ipCOaAmxXwx6KJVES5jt5zS4YDShJRsfx5PME3r0QH0I7aar7tlf+eJX56hatgLt3ivoDKo56/enpFrYOP9LZDWu1AmGyOVXW3bZMtLL5oWe8N9R/NDjbWFJDdk5cY8C+wJlmGvTxFltxzY/1xAoGBAMl/LVfKcA2bJ8MYYcR/HGsrQMzp23JNUW7Q9nnifRq+1B+MHOycP5XCQmmg7QLdasGWKrsRsOSkY0k2Y3tLO1pFaVKRnSprEtpd5RiLAqRVHrHo1Gy8qLgpVRJ50d0QMajIr+uDgfgBW2tNlvekSW8oqK/EanQAJ6+mIPiC+KMVAoGALRnEJM9eXiU79gP6pxibeSSp2zv1c+FTgKX2Zfa+6w8KsWXMjT6i35sT6G4gllSGABdoVa8vO3ApELCcDUcWyAqhpq3cmLWirs0wvES5WZK2Wf7z970NCXdPuBOpRDLCSo60Nf4RNrgpzgj5mDN1QLvH0Jl3pmU/N0kJKjKJVYECgYBMlqpAUorvcZZg6HThEuwsn426S0dGG7hpm0cdoLONZRMHiwQCYVBHFm1f8KgMtoueivgcP0rQ1Ng33xtewnsUzuPppfeChFUzK01aR9hV4tes4aoxl7IY+WPzXP7eI0YF0sU06AngOJldqGusQsd/u3Tg17jQKJKVYfcq6meyeQ==';
    }
  public function geturl($url){
        return "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=".$this->appId."&scope=auth_base&redirect_uri=" . urlencode($url);
    }
    public function setAppid($appid)
    {
        $this->appId = $appid;
    }
    public function setScope($scope)
    {
        $this->scope = $scope;
    }
    public function setAuthCode($authCode)
    {
        $this->auth_code = $authCode;
    }
    public function setRsaPrivateKey($rsaPrivateKey)
    {
        $this->rsaPrivateKey = $rsaPrivateKey;
    }
    /**
     * 获取access_token和user_id
     * @return array
     */
    public function doAuth()
    {
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.system.oauth.token',//接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'grant_type'=>'authorization_code',
            'code'=>$this->auth_code,
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do',$commonConfigs);
        $result = iconv('GBK','UTF-8',$result);
        return json_decode($result,true);
    }
    /**
     * 获取access_token和user_id
     */
    public function getToken()
    {
        //通过code获得access_token和user_id
        if (!isset($_GET['auth_code'])){
            //触发微信返回code码
            $scheme = $_SERVER['HTTPS']=='on' ? 'https://' : 'http://';
            $baseUrl = urlencode($scheme.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
            if($_SERVER['QUERY_STRING']) $baseUrl = $baseUrl.'?'.$_SERVER['QUERY_STRING'];
            //$baseUrl = "http://new-dev.themeeting.cn/Pay_Gaoji_sk.html";
            $url = $this->__CreateOauthUrlForCode($baseUrl);
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $this->setAuthCode($_GET['auth_code']);
            return $this->doAuth();
        }
    }
    /**
     * 通过code获取access_token和user_id
     * @param string $code 支付宝跳转回来带上的auth_code
     * @return openid
     */
    public function getBaseinfoFromAlipay($code)
    {
        $this->setAuthCode($code);
        return $this->doAuth();
    }
    /**
     * 构造获取token的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["app_id"] = $this->appId;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["scope"] = $this->scope;
        $urlObj["state"] = 123456;
        $bizString = $this->ToUrlParams($urlObj);
        return "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?".$bizString;
    }
    /**
     * 拼接签名字符串
     * @param array $urlObj
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign") $buff .= $k . "=" . $v . "&";
        }
        $buff = trim($buff, "&");
        return $buff;
    }
    /**
     * 获取用户信息
     * @return array
     */
    public function doGetUserInfo($token)
    {
        $commonConfigs = array(
            //公共参数
            'app_id' => $this->appId,
            'method' => 'alipay.user.userinfo.share',//接口名称
            'format' => 'JSON',
            'charset'=>$this->charset,
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'auth_token'=>$token,
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        $result = $this->curlPost('https://openapi.alipay.com/gateway.do',$commonConfigs);
        $result = iconv('GBK','UTF-8',$result);
        return json_decode($result,true);
    }
    public function generateSign($params, $signType = "RSA") {
        return $this->sign($this->getSignContent($params), $signType);
    }
    protected function sign($data, $signType = "RSA") {
        $priKey=$this->rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }
    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, $this->charset);
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = $this->charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }
    public function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}