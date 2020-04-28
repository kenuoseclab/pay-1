<?php
/**
 * Created by PhpStorm.
 * User: qiswl
 * Date: 2017-10-30
 * Time: 21:24
 */
namespace Pay\Controller;

class TradeController extends PayController
{
    private $userid;
    private $apikey;
    public function __construct()
    {
        parent::__construct();
        $memberid = I("request.pay_memberid",0,'intval') - 10000;
        if (empty($memberid) || $memberid<=0) {
            $this->showmessage("不存在的商户编号!");
        }
        $this->userid = $memberid;
        $fans = M('Member')->where(['id'=>$this->userid])->find();
        if(!$fans){
            $this->showmessage('商户不存在');
        }
        $this->apikey = $fans['apikey'];
    }

    //订单查询
    public function query()
    {
        $data=I('request.');
        $out_trade_id = I('request.pay_orderid/s');
        file_put_contents('./Data/Trade.txt', "【".date('Y-m-d H:i:s')."】\r\n".json_encode($data)."\r\n\r\n",FILE_APPEND);
        $sign = I('request.pay_md5sign');
        if(empty($out_trade_id)){
            $this->showmessage("不存在的交易订单号.");
        }
        $request = [
            'pay_memberid'=>I("request.pay_memberid/s"),
            'pay_orderid'=>$out_trade_id
        ];
        $signature = $this->createSign($this->apikey,$request);
        if($signature != $sign){
            $this->showmessage('验签失败!');
        }
        $order = M('Order')->where(['pay_memberid'=>$request['pay_memberid'],
            'out_trade_id'=>$request['pay_orderid']])->find();
        if(!$order){
            $this->showmessage('不存在的交易订单.');
        }
        if($order['pay_status']==0){
            $msg = "NOTPAY";
        }elseif ($order['pay_status'] ==1 || $order['pay_status'] == 2){
            $msg = "SUCCESS";
        }
        $return = [
            'memberid'=>$order['pay_memberid'],
            'orderid'=>$order['out_trade_id'],
            'amount'=>$order['pay_amount'],
            'time_end'=>date('Y-m-d H:i:s',$order['pay_successdate']),
            'transaction_id'=>$order['pay_orderid'],
            'returncode'=>"00",
            'trade_state'=>$msg
        ];
        $return['sign'] = $this->createSign($this->apikey,$return);
        echo json_encode($return);
    }
}