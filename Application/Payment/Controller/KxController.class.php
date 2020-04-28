<?php
namespace Payment\Controller;
use Org\Util\Kxdf\Pay;

class KxController extends PaymentController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function PaymentExec($wttlList, $pfaList)
    {
        $pay = new pay();
        $md5key = $pfaList['signkey'];
        $customerNo = $pfaList['mch_id'];
        $additional = $wttlList['additional'];
        $data = [
            'transCode' => '006',
            'customerNo' => $customerNo,
            'requestId' => $wttlList['orderid'],
            'reqTime' => date('YmdHis'),
            'transAmt' => $wttlList['money'],
            'curType' => '1',
            'payType' => '1',
            'accountName' => $wttlList['bankfullname'],
            'accountNo' => $wttlList['banknumber'],
            'accountType' => 0,
            'mobile' => $additional[1],//收款人手机号码
            'openBankName' => $wttlList['bankname'].$wttlList['bankzhiname'],
            'bankCode' => $additional[2],//银行联行号
            'memo' => $wttlList['userid']
        ];
        $resultJson = $pay->payService(json_encode($data), $md5key, $customerNo);
        file_put_contents('./Data/kxDf.txt', "【".date('Y-m-d H:i:s')."】提交结果：\r\n".$resultJson."\r\n\r\n",FILE_APPEND);
        $result = json_decode($resultJson, true);
        if ($result && $result['retcode'] == '0000') {
            if($result['orderState'] == '2') {
                $return = ['status' => 1, 'msg' => $result['orderDesc']];
            } else {
                $return = ['status' => 3, 'msg' => $result['orderDesc']];
            }
        } else {
            $return = ['status' => 3, 'msg' => "错误：{$result['retmsg']}"];
        }
        return $return;
    }

    public function PaymentQuery($wttlList, $pfaList)
    {

        $pay = new pay();
        $md5key = $pfaList['signkey'];
        $customerNo = $pfaList['mch_id'];
        $result_data = array(
            'transCode' => '007',
            'requestId' => $wttlList['orderid'],
            'customerNo' => $customerNo,
            'reqTime' => date("YmdHis"),
        );
        file_put_contents('./Data/kxDf.txt', "【".date('Y-m-d H:i:s')."】查询提交数据：\r\n".json_encode($result_data)."\r\n\r\n",FILE_APPEND);
        $returnJson = $pay->queryResult(json_encode($result_data), $md5key, $customerNo);
        file_put_contents('./Data/kxDf.txt', "【".date('Y-m-d H:i:s')."】查询结果：\r\n".$returnJson."\r\n\r\n",FILE_APPEND);
        $returnData = json_decode($returnJson, true);
        if ($returnData['retcode'] == '0000') {
            switch ($returnData['orderState']) {
                case '2':
                    $result = ['status' => 1, 'msg' => '申请成功'];
                    break;
                case '1':
                    $result = ['status' => 2, 'msg' => '支付成功'];
                    break;
                case '3':
                    $result = ['status' => 3, 'msg' => '申请失败'];
                    break;
            }
        } else {
            $result = ['status' => 4, 'msg' => $returnData['retmsg']];
        }
        return $result;
    }

    public function queryBalance()
    {
        $id = I('post.id', '22');
        if (IS_AJAX) {
            $config = $this->findPaymentType($id);
            $md5key = $config['signkey'];
            $customerNo = $config['mch_id'];
            $balance_data = array(
                'transCode' => '008',
                'customerNo' => $customerNo,
                'reqTime' => date("YmdHis"),
            );
            $pay = new pay();
            $resultJson = $pay->queryBalance(json_encode($balance_data), $md5key, $customerNo);
            $result = json_decode($resultJson, true);
            if ($result['retcode'] == '0') {
                $result['balance'] = $result['balance'];
                $data = [
                    [
                        'key'   => '账户余额',
                        'value' => $result['balance'] . '元',
                    ],
                ];
                $this->assign('data', $data);
                $html = $this->fetch('Public/queryBalance');
                $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);

            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => $result['retmsg']]);
            }
        }
    }
}
