<?php

    namespace Pay\Controller;

    use Think\Controller;

    class QiswlSeparateController extends Controller{
      
      	protected $at;
        public function __construct()
        {
          parent::__construct();
          $this->at = C('ZFB');//获取支付宝的数组数据
        }

        public function index($data){

            Vendor("AlipaySdk.aop.SignData");
            Vendor("AlipaySdk.aop.AopClient");
            Vendor("AlipaySdk.aop.request.AlipayTradeOrderSettleRequest");

            //得到异步通知定义的参数
            $aid = $data['aid'];
            $money = $data['money'];
            $zfb_pid = $data['pid'];

            // 查询当前订单的信息
            $trade_no=time().rand(1000,9999);
            // 子账户信息
            $info = M("ChannelAccount")->where(['id'=>$aid])->field('title,appid,appsecret,zfb_pid,signkey')->find();

            $key = $info['signkey'];

            //如果子账户pid存在执行
            if($zfb_pid){
                $trans_out =$info['zfb_pid'];

              	//分账账号存在
                $aop = new \AopClient();
                $aop->gatewayUrl = $this->at['gatewayUrl'];
                $aop->appId = $info['appid'];


                $aop->rsaPrivateKey = $info['appsecret'];
                $aop->alipayrsaPublicKey = $key;
                $aop->signType = $this->at['sign_type'];

                // 支付宝费率
                $amount = $money;
                $zfb_amount = $amount*0.006;
                $zfb_amount = ceil($zfb_amount*100);

                $separate_info['trans_out'] = $trans_out;
                $separate_info['trans_in']= $zfb_pid;
                $separate_info['amount']= $zfb_amount;
                $separate_info['amount_percentage']= 100;
                
                $request = new \AlipayTradeOrderSettleRequest();

                $abc_order = date("YmdHis").rand(1000,9999);

                $data = [
                    'out_request_no' => $abc_order,
                    'trade_no' => $trade_no,
                    'royalty_parameters' => $separate_info
                ];


                $param = json_encode($data);

                $request->setBizContent($param);

                $result = $aop->execute ( $request);
                var_dump($result);exit;
                $resultCode = $result->alipay_trade_order_settle_response->code;
                  
                  	//失败重复提交两次
                  	if(empty($resultCode) || $resultCode!=10000){
                        for($i=0;$i<2;$i++){
                            sleep(2);

                            $abc_order = date("YmdHis").rand(1000,9999);

                            $data = [
                               'out_request_no' => $abc_order,
                               'trade_no' => $trade_no,
                               'royalty_parameters' => $separate_info
                            ];

                            $param = json_encode($data);

                            $request->setBizContent($param);
                            $result = $aop->execute ( $request);
                            $resultCode = $result->alipay_trade_order_settle_response->code;
                            log_separate("","","","",$param,$result->alipay_trade_order_settle_response->code,$result->alipay_trade_order_settle_response->msg);
                            if($resultCode==10000){
                                $i = 2;
                            }
                        }
                    }

                    if(!empty($resultCode)&&$resultCode == 10000){
                        // 修改订单状态
                        M("Order")->where(['pay_orderid' => $orderid])->setField("pay_fenzhang","分账成功：".$fenzhang);
                        // 写入分账订单信息表
                        foreach ($separate_info as $ke => $va) {
                            $data_info = [
                                'channel_account_id' => $order_info['account_id'],
                                'tag' => 1,
                                'order_id' => $orderid,
                                'sepa_order' => $abc_order,
                                'out_id' => $va['trans_out'],
                                'in_id' => $va['trans_in'],
                                'amount' => $va['amount'],
                                'created_at' => date("Y-m-d H:i:s"),
                            ];
                            separateAdd($data_info);
                        }
                        log_separate($orderid,$abc_order,$trans_out,$fenzhang,$param,$result->alipay_trade_order_settle_response->code,$result->alipay_trade_order_settle_response->msg);
                    } else {
                        M("Order")->where(['pay_orderid' => $orderid])->setField("pay_fenzhang","分账失败：".$info['title']);
                        M("ChannelAccount")->where(['id' => $order_info['account_id']])->setField("status","0");
                        foreach ($separate_info as $ke => $va) {
                            $data_info = [
                                'channel_account_id' => $order_info['account_id'],
                                'tag' => 0,
                                'order_id' => $orderid,
                                'sepa_order' => $abc_order,
                                'out_id' => $va['trans_out'],
                                'in_id' => $va['trans_in'],
                                'amount' => $va['amount'],
                                'created_at' => date("Y-m-d H:i:s"),
                            ];
                            separateAdd($data_info);
                        }
                        log_separate($orderid,$abc_order,$trans_out,$fenzhang,$param,$result->alipay_trade_order_settle_response->sub_code,$result->alipay_trade_order_settle_response->sub_msg);
                    }
               	
            }
        }

    }

?>