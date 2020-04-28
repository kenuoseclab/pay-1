<?php


namespace Pay\Controller;

/**
 * 乐百付
 * 官网：http://pay.lebaifupay.com/
 * 备注：上游用的是我们自己的产品
 */
class NewZfbH5Controller extends PayController
{

  function sign($params = [], $secret = '')
{
    unset($params['sign']);
    ksort($params);
    $str = '';
    foreach ($params as $k => $v) {
	
        $str = $str . $k . $v;
    }
    $str = $secret . $str . $secret;
    return strtoupper(md5($str));
}

 

/**
 * CURL发送Request请求,含POST和REQUEST
 * @param string $url 请求的链接
 * @param mixed $params 传递的参数
 * @param string $method 请求的方法
 * @param mixed $options CURL的参数
 * @return array
 */
 
 
 
    /**
     *  发起支付
     */
    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site ."Pay_NewZfbH5_notifyurl.html"; //异步通知
        $callbackurl = $this->_site . 'Pay_NewZfbH5_callbackurl.html'; //跳转通知
     //   $payBankCode = I('request.pay_bankcode', '911');
 


        $parameter = array(
            'code' => 'NewZfbH5',
            'title' => 'zfbH5',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $orderid, //外部订单号
            'channel'=>$array,
            'body'=>$body
        );

        // 订单号，可以为空，如果为空，由系统统一的生成
	
        $return = $this->orderadd($parameter);
		
$Mid= $return['mch_id']; 																//必填
$myKey=$return['signkey']; 		
		
 
    list($t1, $t2) = explode(' ', microtime());

    $getMillisecond= (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
 
 
 
 $apiurl = 'http://pdd2.heropay.net//index/api/order'; // API下单地址
$signkey = $myKey; // 商户KEY  PDD平台获取
$data = array(
    'type' => 'alipay', // 通道代码 alipay/wechat
    'total' => $return['amount'], // 金额 单位 分
    'api_order_sn' => $return['orderid'], // 订单号
    'notify_url' => $notifyurl, // 异步回调地址
	'client_id' => $Mid,
    'timestamp' => $getMillisecond // 获取13位时间戳
);

    unset($data['sign']);
    ksort($data);
    $str = '';
    foreach ($data as $k => $v) {
	
        $str = $str . $k . $v;
    }
    $str = $signkey . $str .$signkey;
    $data['sign'] = strtoupper(md5($str));

//var_dump($data);

 
     $url =  'http://pdd2.heropay.net/index/api/order';
	 
	 
	 	$ch = curl_init();	
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$ret=curl_exec($ch);
	 

	 
    //var_dump($ret);
$res = json_decode($ret,true);


if( $res['code'] <>200 ){
    // 下单成功  自行处理
    echo($res["msg"]);
}else{
    // 下单失败
	
	$ress=$res['data'];
	
	echo "<script>location.href='".$ress["h5_url"]."';</script>";
}

exit;	 
	 
 

 
 
    }

/**
     *  服务器通知
     */
    public function notifyurl()
    {

       
        $outorderno = $_REQUEST["api_order_sn"];
        $status = $_REQUEST["callbacks"];
		
		$data = array(
			'type' => $_REQUEST["type"], 
			'total' =>  $_REQUEST["total"], 
			'callbacks' => $_REQUEST['callbacks'], 
			'api_order_sn' => $_REQUEST["api_order_sn"],
			'order_sn' => $_REQUEST["order_sn"]
		);
		$signkey = getKey($_REQUEST['api_order_sn']);
   
		ksort($data);
		$str = '';
		foreach ($data as $k => $v) {
		
			$str = $str . $k . $v;
		}
		$str = $signkey . $str .$signkey;
		$newsig = strtoupper(md5($str));
        file_put_contents('Data/tt.txt', "zf【" . date('Y-m-d H:i:s') . "】notifyurl提交1结果：" . $outorderno . "--" . $status . "\r\n\r\n", FILE_APPEND);
        file_put_contents('Data/tt.txt', "zf【" . date('Y-m-d H:i:s') . "】notifyurl提交1结果：" . var_export($_POST,true) .$newsig. "\r\n\r\n", FILE_APPEND);
        if ($status == "CODE_SUCCESS" && $newsig == $_REQUEST["sign"]) {
            $Order = M("Order");
            $pay_status = $Order->where("pay_orderid = '" . $outorderno . "'")->getField("pay_status");
            if ($pay_status == 0) {
                $this->EditMoney($outorderno, 'NewZfbH5', 0);
                file_put_contents('Data/tt.txt', "wx【" . date('Y-m-d H:i:s') . "】notifyurl提交2结果：" . $outorderno . "--" . $status . "\r\n\r\n", FILE_APPEND);
                exit("success");
            }
          
        } exit("success");
    }
}