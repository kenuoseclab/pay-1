<?php
/**
 * Created by PhpStorm.
 * User: win 10
 * Date: 2018/6/11
 * Time: 11:41
 */

namespace Payment\Controller;
/**
 * 支付宝代付处理控制器
 *
 * 单笔转账到支付宝账户接口
 * Class IndexController
 * @package User\Controller
 */
use think\Log;
class AliTransferController extends PaymentController
{
    //代付状态
    const PAYMENT_SUBMIT_SUCCESS = 1; //处理中
    const PAYMENT_PAY_SUCCESS = 2; //已打款
    const PAYMENT_PAY_FAILED = 3; //已驳回
    const PAYMENT_PAY_UNKNOWN = 4; //待确认

    public function __construct(){
        parent::__construct();
    }

    public function PaymentExec($data,$config){
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayFundTransToaccountTransferRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = $config['exec_gateway'];
        $aop->appId = $config['appid'];
        $aop->rsaPrivateKey = $config['private_key'];
        $aop->alipayrsaPublicKey = $config['public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayFundTransToaccountTransferRequest();
        $sysParams = json_encode(array('out_biz_no'=>$data['orderid'],'payee_type'=>'ALIPAY_LOGONID','payee_account'=>$data['banknumber'],'amount'=>$data['money']),JSON_UNESCAPED_UNICODE);
        $request->setBizContent($sysParams);
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if($result && $resultCode=='10000'){
            $return = ['status' => self::PAYMENT_SUBMIT_SUCCESS, 'msg' => '提交成功'];
        }else{
            $return = ['status' => self::PAYMENT_PAY_FAILED, 'msg' => "错误：{$result->$responseNode->sub_msg}"];
        }

        return $return;
    }

    public function PaymentQuery($data,$config){
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        vendor('Alipay.aop.request.AlipayFundTransOrderQueryRequest');
        $aop = new \AopClient();
        $aop->gatewayUrl = $config['query_gateway'];
        $aop->appId = $config['appid'];
        $aop->rsaPrivateKey = $config['private_key'];
        $aop->alipayrsaPublicKey = $config['public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayFundTransOrderQueryRequest();
        $sysParams = json_encode(array('out_biz_no'=>$data['orderid']),JSON_UNESCAPED_UNICODE);
        $request->setBizContent($sysParams);
        $result = $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if($result && $resultCode=='10000'){
            switch ($result->$responseNode->status){
                case 'SUCCESS'://成功
                    $return = ['status' => self::PAYMENT_PAY_SUCCESS, 'msg' => '代付成功'];
                    break;
                case 'FAIL'://失败
                    $return = ['status' => self::PAYMENT_PAY_FAILED, 'msg' => "错误：代付失败"];
                    break;
                case 'INIT'://等待处理
                    $return = ['status' => self::PAYMENT_SUBMIT_SUCCESS, 'msg' => '等待处理'];
                    break;
                case 'DEALING'://处理中
                    $return = ['status' => self::PAYMENT_SUBMIT_SUCCESS, 'msg' => '处理中'];
                    break;
                case 'REFUND'://退票
                    $return = ['status' => self::PAYMENT_PAY_FAILED, 'msg' => "错误：已退票"];
                    break;
                default://未知原因
                    $return = ['status' => self::PAYMENT_PAY_UNKNOWN, 'msg' => "错误：未知原因"];
                    break;
            }
        }else{
            $return = null;
        }

        return $return;
    }
}