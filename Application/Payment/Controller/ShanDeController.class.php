<?php
namespace Payment\Controller;

use Org\Util\Shande\Handle;

class ShanDeController extends PaymentController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function PaymentExec($data, $config)
    {
        header("Content-type: text/html; charset=utf-8");
        $cert = [
            'pubPath' => $config['public_key'],
            'priPath' => $config['private_key'],
            'certPwd' => $config['signkey'],
        ];
        $handle = new handle($cert);
        $date   = date('YmdHis', time());
        $money  = $data['money'] * 100;
        $params = [
            'transCode' => 'RTPM', //实时代付
            'merId'     => $config['mch_id'],
            'url'       => 'https://caspay.sandpay.com.cn/agent-main/openapi/agentpay',
            'pt'        => [
                'tranTime'     => $date,
                'productId'    => '00000004',
                'accNo'        => $data['banknumber'],
                'tranAmt'      => str_pad($money, 12, '0', STR_PAD_LEFT),
                'accName'      => $data['bankfullname'],
                'reqReserved'  => '请求',
                'orderCode'    => $data['orderid'],
                'remark'       => 'pay',
                'accAttr'      => '0',
                'accType'      => '4',
                'version'      => '01',
                'currencyCode' => '156',
            ],
        ];

        M('Wttklist')->where(['id' => $data['id']])->save(['additional' => json_encode([$date])]);
        $result = $handle->execute($params);
		file_put_contents('./Data/shanDeDf.txt', "【".date('Y-m-d H:i:s')."】\r\n".$result."\r\n\r\n",FILE_APPEND);
        $result = json_decode($result, true);

        $return = ['status' => 3, 'msg' => '网络延迟，请稍后再试！'];
        if ($result) {
            if ($result['respCode'] == '0000' || $result['respCode'] == '0001' || $result['respCode'] == '0002') {
                $return = ['status' => 1, 'msg' => '处理中！'];
            } else {
                $return = ['status' => 3, 'msg' => $result['respDesc']];
            }
        }
        return $return;
    }

    public function PaymentQuery($data, $config)
    {
        $cert = [
            'pubPath' => $config['public_key'],
            'priPath' => $config['private_key'],
            'certPwd' => $config['signkey'],
        ];
		if(!is_array($data['additional'])) {
			$data['additional'] = json_decode($data['additional'], true);
		}
        $handle = new handle($cert);
        $params = [
            'transCode' => 'ODQU', //实时代付
            'merId'     => $config['mch_id'],
            'url'       => 'https://caspay.sandpay.com.cn/agent-main/openapi/queryOrder',
            'pt'        => [
                'orderCode' => $data['orderid'],
                'version'   => '01',
                'productId' => '00000004',
                'tranTime'  => $data['additional'][0],
            ],
        ];
        $result = $handle->execute($params);
        $result = json_decode($result, true);
        $return = ['status' => 3, 'msg' => '网络延迟，请稍后再试！'];

        if ($result) {
            if ($result['respCode'] == '0000' && $result['origRespCode'] == '0000' && $result['resultFlag'] == '0') {
                $return = ['status' => 2, 'msg' => '处理成功！'];
            } else if ($result['origRespCode'] == '0001' || $result['origRespCode'] == '0002' || $result['origRespCode'] == '2') {
                $return = ['status' => 1, 'msg' => '处理中！'];
            } else {
                $return = ['status' => 3, 'msg' => $result['respDesc']];
            }
        }
        return $return;
    }

    public function queryBalance()
    {
        $id = I('post.id', '11');
        if (IS_AJAX) {
            $config = $this->findPaymentType($id);

            $cert = [
                'pubPath' => $config['public_key'],
                'priPath' => $config['private_key'],
                'certPwd' => $config['signkey'],
            ];
            $handle = new handle($cert);
            $params = [
                'transCode' => 'MBQU', //实时代付
                'merId'     => $config['mch_id'],
                'url'       => 'https://caspay.sandpay.com.cn/agent-main/openapi/queryBalance',
                'pt'        => [
                    'orderCode' => date('YmdHis'),
                    'version'   => '01',
                    'productId' => '00000004',
                    'tranTime'  => date('YmdHis'),
                ],
            ];
            $result = $handle->execute($params);
            if ($result) {
                $result            = json_decode($result, true);
                $result['balance'] = trim($result['balance'], '+');
                $result['balance'] = bcdiv($result['balance'], 100, 2);

                $data = [
                    [
                        'key'   => '账户余额',
                        'value' => $result['balance'] . '元',
                    ],
                ];
                $this->assign('data', $data);
                $html = $this->fetch('Public/queryBalance');
                $this->ajaxReturn(['status' => 1, 'msg' => '成功', 'data' => $html]);

            }
            $this->ajaxReturn(['status' => 0, 'msg' => '网络延迟']);
        }

    }
}
