<?php

    namespace Pay\Controller;

    use Think\Controller;

    class TransferController extends Controller{

        protected $at;
        public function __construct()
        {
          parent::__construct();
          $this->at = C('ZFB');//获取支付宝的数组数据
        }

        public function index($data){

            Vendor("AlipaySdk.aop.SignData");
            Vendor("AlipaySdk.aop.AopClient");
            Vendor("AlipaySdk.aop.request.AlipayFundTransToaccountTransferRequest");

            //得到异步通知定义的参数
            $orderid = $data['transfer_orderid'];
//          	log_separate("--------获取OrderId","","","","","",$orderid);

            // 查询当前订单的信息
            $order_info = M("Order")->where(['pay_orderid' => $orderid])->field('account_id,pay_amount,transfer_status')->find();

            // 子账户信息
            $info = M("ChannelAccount")->where(['id'=>$order_info['account_id']])->field('title,channel_id,appid,appsecret,zfb_pid,signkey')->find();
          
          	$transfer_info = M("Separate")->where(['channel_account_id'=>$order_info['account_id']])->field('zfb_pid')->find();
          
            // 通道信息得到下单地址
            $gateway = M("Channel")->where(['id'=>$info['channel_id']])->field("gateway")->find();


            //如果转账pid存在执行
            if($transfer_info['zfb_pid'] && $order_info['transfer_status']==0){


                M("Order")->where(['pay_orderid' => $orderid])->save(['transfer_status'=>1]);
            
                $aop = new \AopClient();
                $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
                $aop->appId = $info['appid'];


                $aop->rsaPrivateKey = $info['appsecret'];
                $aop->alipayrsaPublicKey = $info['signkey'];
                $aop->signType = $this->at['sign_type'];

                // 支付宝费率
                $amount = $order_info['pay_amount'];
                $zfb_amount = $amount*0.006;
                $zfb_amount = ceil($zfb_amount*100);

                $transfer_amount = number_format(($amount*100-$zfb_amount)/100,2);

                $request = new \AlipayFundTransToaccountTransferRequest ();

                $abc_order = date("YmdHis").rand(1000,9999);

                $data = [
                    'out_biz_no' => $abc_order,
                    'payee_type' => "ALIPAY_USERID",
                    'payee_account' => $transfer_info['zfb_pid'],
                    'amount' => $transfer_amount
                ];

                $param = json_encode($data);

                $request->setBizContent($param);



                $result = $aop->execute ( $request);

                $resultCode = $result->alipay_fund_trans_toaccount_transfer_response->code;
              
              	log_separate('￥￥￥￥￥￥￥向阿里发送后返回数据','','','','','',json_encode($result));
                
                //失败重复提交两次
                if(empty($resultCode) || $resultCode!=10000){
                    for($i=0;$i<2;$i++){
                        sleep(2);

                        $abc_order = date("YmdHis").rand(1000,9999);

                        $data = [
                            'out_biz_no' => $abc_order,
                            'payee_type' => "ALIPAY_USERID",
                            'payee_account' => $transfer_info['zfb_pid'],
                            'amount' => $transfer_amount
                        ];

                        $param = json_encode($data);

                        $request->setBizContent($param);
                        $result = $aop->execute ( $request);
                        $resultCode = $result->alipay_fund_trans_toaccount_transfer_response->code;
                        if($resultCode==10000){
                            $i = 2;
                        }
                    }
                }

                if(!empty($resultCode)&&$resultCode == 10000){
                    // 修改订单状态
                    M("Order")->where(['pay_orderid' => $orderid])->setField("pay_fenzhang","转账成功：".$transfer_amount);
                    M("Order")->where(['pay_orderid' => $orderid])->save(['transfer_status'=>2]);
                    log_separate('执行转账成功','','','','','',$orderid);
                    
                } else {
                    M("Order")->where(['pay_orderid' => $orderid])->setField("pay_fenzhang","转账失败：".$info['title']);
                    M("Order")->where(['pay_orderid' => $orderid])->save(['transfer_status'=>0]);
                    M("ChannelAccount")->where(['id' => $order_info['account_id']])->setField("status","0");
                    log_separate('执行转账失败','','','','','',$orderid);
                    
                }
            }
        }

    }

?>