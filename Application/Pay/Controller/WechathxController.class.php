<?php
namespace Pay\Controller;

use Org\Util\HttpClient;

class WechathxController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function Pay($array)
    {
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_Wechathx_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_Wechathx_callbackurl.html'; //返回通知
        $parameter = array(
            'code' => 'Wechathx', // 通道名称
            'title' => '微信扫码H5',
            'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid' => '',
            'out_trade_id' => $orderid,
            'body'=>$body,
            'channel'=>$array
        );
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        $this->jsapi($return);
    }

    public function jsapi($return)
    {
        $code = I('get.code');
        $Order = M("Order");
        $client = new HttpClient();
        $arraystr = array(
            "appid" => $return["appid"],
            'mch_id' => $return["mch_id"],
            "nonce_str" => $return['orderid'],
            "body" => $return["subject"],
            "out_trade_no" => $return["orderid"],
            "total_fee" => $return['amount'] * 100,
            "spbill_create_ip" => get_client_ip(),
            "notify_url" => $return['notifyurl'],
            "trade_type" => 'MWEB',
        );
        ksort($arraystr);
        $buff = "";
        foreach ($arraystr as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        //签名步骤二：在string后加入KEY
        $string = $buff . "&key=" . $return["signkey"];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $sign = strtoupper($string);
        $arraystr["sign"] = $sign;
        $xml = arrayToXml($arraystr);
        $result = $client->post('https://api.mch.weixin.qq.com/pay/unifiedorder',$xml);
        $arr = xmlToArray($result);
        if ($arr["result_code"] == "SUCCESS") {
            header("location:".$arr['mweb_url']);
        } else {
            $this->showmessage($arr['return_msg']);
        }
    }

    public function callbackurl()
    {
        $Order = M("Order");
        $orderid=I('request.orderid/s');
        $find_data = $Order->where(['pay_orderid' => $orderid])->find();
        if($find_data['pay_status'] <> 0){
            $this->EditMoney($_REQUEST['orderid'], 'Wechathx', 1);
            header("location:".$find_data['pay_callbackurl']);
            exit('交易成功！');
        }else{
            exit("error");
        }
    }

    // 服务器点对点返回
    public function notifyurl()
    {
        $testxml  = file_get_contents("php://input");
        $jsonxml = json_encode(simplexml_load_string($testxml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $return_info = json_decode($jsonxml, true);//转成数组，
        $Order = M("Order");
        $key = $Order->where(['pay_orderid' => $return_info["out_trade_no"]])->getField("key");
        if($return_info){
            //如果成功返回了
            $out_trade_no = $return_info['out_trade_no'];
            if($return_info['return_code'] == 'SUCCESS' && $return_info['result_code'] == 'SUCCESS'){
                ksort($return_info);
                $buff = "";
                foreach ($return_info as $k => $v) {
                    if ($k != "sign" && $v != "" && !is_array($v)) {
                        $buff .= $k . "=" . $v . "&";
                    }
                }
                $buff = trim($buff, "&");
                //签名步骤二：在string后加入KEY
                $string = $buff . "&key=" . $key;
                //签名步骤三：MD5加密
                $string = md5($string);
                //签名步骤四：所有字符转为大写
                $my_sign = strtoupper($string);
                if ($my_sign == $return_info['sign']) {
                    $this->EditMoney($return_info['out_trade_no'], 'Wechathx', 0);
                    exit('SUCCESS');
                }else{
                    exit('FAIL');
                }
            }
        }else{
            exit('FAIL');
        }
    }
}

?>
