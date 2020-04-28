<?php
/**
 * Created by PhpStorm.
 * User: win 10
 * Date: 2018/6/11
 * Time: 11:41
 */

namespace Payment\Controller;

use think\Log;

/**
 * 易宝代付
 *
 * Class XunjiefuController
 * @package Payment\Controller
 */
class YibaoController extends PaymentController
{
    //代付状态
    const PAYMENT_SUBMIT_SUCCESS = 1; //处理中
    const PAYMENT_PAY_SUCCESS    = 2; //已打款
    const PAYMENT_PAY_FAILED     = 3; //已驳回
    const PAYMENT_PAY_UNKNOWN    = 4; //待确认


    public function __construct()
    {
        parent::__construct();
    }

    public function test()
    {
//        $config = M('pay_for_another')->where([
//            'id' => 21
//        ])->find();
//
//
//        $data = M('wttklist')->where([
//            'id' => 66,
//        ])->find();

//        $res = $this->PaymentQuery($data, $config);
//       $res = $this->PaymentExec($data, $config);
//        var_dump($res);exit;
    }

    public function PaymentExec($data, $config)
    {
        $execGateway = $config['exec_gateway'] . '/withdraw/create';
        $extends     = json_decode($data['extends'], true);
        $parameter   = [
            'merchantId' => $config['mch_id'],//代理商商户号
            'timestamp'  => time() . '000',
            'body'       => [
                'advPasswordMd5' => $config['appsecret'],//交易密码 md5
                'orderId'        => $data['orderid'],
                'flag'           => 0,

                'bankProvinceName' => trim($extends['bankProvinceName']),
                'bankProvinceCode' => trim($extends['bankProvinceCode']),
                'bankCityName'     => trim($extends['bankCityName']),
                'bankCityCode'     => trim($extends['bankCityCode']),
                'bankAreaName'     => trim($extends['bankAreaName']),
                'bankAreaCode'     => trim($extends['bankAreaCode']),
                'bankId'           => trim($extends['bankId']),

                'bankName'       => $data['bankname'],
                'bankBranchName' => $data['bankzhiname'],
                'bankCode'       => $data['banknumber'],
                'bankUser'       => $data['bankfullname'],

                'bankUserCert'  => trim($extends['bankUserCert']),
                'bankUserPhone' => trim($extends['bankUserPhone']),

                'amount'     => round(trim($data['money']), 2) + 3,
                'realAmount' => round(trim($data['money']), 2),
            ],
        ];

        $sign = $this->_createSign($parameter, $config['signkey']);

        $response = json_decode(curlPost(
            $execGateway,
            json_encode($parameter),
            [
                'Content-Type: application/json',
                "Api-Sign: {$sign}",
            ]
        ), true) ?: [];

        if(empty($response))
        {
            $return = ['status' => self::PAYMENT_PAY_FAILED, 'msg' => "错误：服务不可用"];
        }
        else
        {
            if($response['status'] === 0)
            {
                $return = ['status' => self::PAYMENT_SUBMIT_SUCCESS, 'msg' => '提交成功'];
            }
            else
            {
                $return = [
                    'status' => self::PAYMENT_PAY_FAILED,
                    'msg'    => "错误：{$response['status']}：{$response['message']}"
                ];
            }
        }

        return $return;
    }

    public function PaymentQuery($data, $config)
    {
        $execGateway = $config['query_gateway'] . '/withdraw/query';
        $parameter   = [
            'merchantId' => $config['mch_id'],//代理商商户号
            'timestamp'  => time() . '000',
            'body'       => [
                'orderId' => $data['orderid'],
            ],
        ];

        $sign = $this->_createSign($parameter, $config['signkey']);

        $response = json_decode(curlPost(
            $execGateway,
            json_encode($parameter),
            [
                'Content-Type: application/json',
                "Api-Sign: {$sign}",
            ]
        ), true) ?: [];

        if(empty($response))
        {
            $return = ['status' => self::PAYMENT_PAY_FAILED, 'msg' => "错误：服务不可用"];
        }
        else
        {
            if($response['status'] === 0)
            {
                if($response['body']['status'] == 1)
                {
                    $return = ['status' => self::PAYMENT_SUBMIT_SUCCESS, 'msg' => '付款成功'];
                }
                else
                {
                    $return =
                        ['status' => self::PAYMENT_PAY_FAILED, 'msg' => "{$response['body']['status']}：{$response['body']['message']}"];
                }

            }
            else
            {
                $return = [
                    'status' => self::PAYMENT_PAY_FAILED,
                    'msg'    => "错误：{$response['status']}：{$response['message']}"
                ];
            }
        }

        return $return;
    }

    public function notifyurl()
    {
        exit('ok');
    }


    /**
     * 规则是:按参数名称a-z排序,遇到空值的参数不参加签名。
     */
    private function _createSign($data, $key)
    {
        $jsonData = json_encode($data);

        return md5($jsonData . '|' . $key);
    }
}