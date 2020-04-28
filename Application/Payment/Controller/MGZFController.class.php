<?php
namespace Payment\Controller;

use think\Log;

class MGZFController extends PaymentController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function PaymentExec($data, $config)
    {
        $params = [
            'merchant_no' => $config['mch_id'],
            'method'      => 'settle',

            'bank_card'      => $data['banknumber'], //银行卡号
            'bank_name'      => $data['bankname'], //银行名称
            'bank_user'      => $data['bankfullname'], //开户人
            'bank_province'  => $data['sheng'], //开户省份
            'bank_city'      => $data['shi'], //开户城市
            'bank_card_type' => 1, //银行卡类型：1私人 2公司
            'amount'         => $data['money'] * 100, //以分为单位
            'out_trade_no'   => $data['orderid'],
        ];

        $params['sign'] = $this->md5Sign($params, $config['signkey']);

        Log::record("蘑菇支付代付提交：key：" . $config['signkey'], Log::INFO);
        Log::record("蘑菇支付代付提交：签名：" . $params['sign'], Log::INFO);
        $resultstr = curlPost($config['exec_gateway'], http_build_query($params));
        Log::record("蘑菇支付代付提交：返回内容：" . $resultstr, Log::INFO);
        $result = json_decode($resultstr, true);
        if ($result && $result['code'] == '0000') {

            switch ($result['status']) {

                case '1':
                    $return = ['status' => 1, 'msg' => $result['msg']];
                    break;
                case '0':
                    $return = ['status' => 3, 'msg' => $result['msg']];
                    break;
                default:
                    $return = ['status' => 3, 'msg' => $result['msg']];
                    break;
            }

        } else {
            $return = ['status' => 3, 'msg' => "错误：{$result['msg']}"];
        }
        return $return;
    }

    public function PaymentQuery($data, $config)
    {
        $params = [
            'merchant_no'  => $config['mch_id'],
            'method'       => 'settlequery',
            'out_trade_no' => $data['orderid'],
        ];
        $params['sign'] = $this->md5Sign($params, $config['signkey']);
        $resultstr = curlPost($config['query_gateway'], http_build_query($params));
        Log::record("蘑菇支付代付查询：返回内容：" . $resultstr, Log::INFO);
        $result = json_decode($resultstr, true);
        if ($result) {

            switch ($result && $result['code'] == '0000') {

                case '1': //成功
                    $return = ['status' => 2, 'msg' => $result['msg']]; //代付成功
                    break;
                case '2': //失败
                    $return = ['status' => 3, 'msg' => $result['msg']]; //代付失败
                    break;
                case '0': //处理中
                    $return = ['status' => 1, 'msg' => $result['msg']]; //申请成功
                    break;
                default:
                    $return = ['status' => 3, 'msg' => $result['msg']]; //代付失败
                    break;
            }

        } else {
            $return = ['status' => 3, 'msg' => "错误：{$result['msg']}"];
        }

        return $return;
    }

    private function md5Sign($data, $key)
    {
        $signSrc = "";
        ksort($data);
        foreach ($data as $k => $v) {
            if ($k != 'sign' && !empty($v)) {
                $signSrc .= $k . "=" . $v . "&";
            }
        }
        $signSrc = rtrim($signSrc, '&');
        $signSrc .= $key;
        return md5($signSrc);  //MD5加密
    }

    public function encryptDecrypt($string, $key = '', $decrypt = '0')
    {
        if ($decrypt) {
            $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "12");
            return $decrypted;
        } else {
            $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
            return $encrypted;
        }
    }

    public function notifyurl()
    {

    }

}
