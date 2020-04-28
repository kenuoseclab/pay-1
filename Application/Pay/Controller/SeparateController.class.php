<?php

    namespace Pay\Controller;

    use Think\Controller;

    class SeparateController extends Controller{
      
      	protected $at;
        public function __construct()
        {
          parent::__construct();
          $this->at = C('ZFB');//获取支付宝的数组数据
        }

        public function index($data){

            Vendor("Alipay.aop.SignData");
            Vendor("Alipay.aop.AopClient");
            Vendor("Alipay.aop.request.AlipayTradeOrderSettleRequest");

            //得到异步通知定义的参数
            $orderid = $data['separate_orderid'];
            $trade_no = $data['separate_trade_no'];

            // 查询当前订单的信息
            $order_info = M("Order")->where(['pay_orderid' => $orderid])->field('account_id,pay_amount')->find();
            // 子账户信息
            $info = M("ChannelAccount")->where(['id'=>$order_info['account_id']])->field('title,appid,appsecret,zfb_pid,signkey')->find();

            $key = $info['signkey'];

            //如果子账户pid存在执行
            if($info['zfb_pid']){
                $trans_out = $info['zfb_pid'];
                
                // 子账户对应的分账的信息(按照费率正序)
                $separate_info = M("Separate")->where(['channel_account_id'=>$order_info['account_id']])->order("rate asc")->field("zfb_pid,rate")->select();

              	//分账账号存在
              	if(!empty($separate_info)){

                    $aop = new \AopClient();
                    $aop->gatewayUrl = $this->at['gatewayUrl'];
                    $aop->appId = $info['appid'];


                    $aop->rsaPrivateKey = $info['appsecret'];
                    $aop->alipayrsaPublicKey = $key;
                    $aop->signType = $this->at['sign_type'];

                    // 支付宝费率
                    $amount = $order_info['pay_amount'];
                    $zfb_amount = $amount*0.006;
                    $zfb_amount = ceil($zfb_amount*100);


                    // 计算费率
                    $fenzhang = 0;
                    foreach ($separate_info as $k => $v) {
                        $separate_info[$k]['trans_out'] = $trans_out;
                        $separate_info[$k]['trans_in'] = $separate_info[$k]['zfb_pid'];
                        $v['amount'] = floor($amount*$v['rate']*100);
                        $separate_info[$k]['amount'] = $v['amount']/100;
                        $fenzhang += $v['amount']/100;

                        unset($separate_info[$k]['rate']);
                        unset($separate_info[$k]['zfb_pid']);
                    }


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

    }

?>