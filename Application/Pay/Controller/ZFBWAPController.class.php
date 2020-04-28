<?php

    namespace Pay\Controller;

    class ZFBWAPController extends PayController{

      	protected $at;
      
        public function __construct()
        {
          parent::__construct();
          $this->at = C('ZFB');//获取支付宝的数组数据
        }

        public function pay($array){

            Vendor("AlipaySdk.AopSdk");
            Vendor("AlipaySdk.aop.AopClient");
            Vendor("AlipaySdk.aop.request.AlipayTradeWapPayRequest");


            $gateWay = $this->at['gatewayUrl'];//获取网关 (可配置可从数据里面获取)
            $orderid     = I("request.pay_orderid");
            $body        = I('request.pay_productname');
            $parameter = array(
                'code'         => "ZFBWAP", // 通道名称
                'title'        => '支付宝WAP',
                'exchange'     => 1, // 金额比例
                'gateway'      => $gateWay,
                'orderid'      => $orderid,
                'out_trade_id' => $orderid,
                'body'         => $body,
                'channel'      => $array,
            );

            $return = $this->orderadd($parameter);//生成系统订单

            $aop = new \AopClient();
            $aop->gatewayUrl = $this->at['gatewayUrl'];
            $aop->appId = $return['appid'];
            $aop->rsaPrivateKey = $return['appsecret'];
            $aop->alipayrsaPublicKey = $return['signkey'];
            $aop->signType = $this->at['sign_type'];

            $request = new \AlipayTradeWapPayRequest ();

            $data['out_trade_no'] = $orderid;
            $data['total_amount'] = $return['amount'];
            $data['subject'] = $body;
            $data['subject'] = $body;
            $data['product_code'] = "QUICK_WAP_WAY";

            $param = json_encode($data);
            
            $request->setReturnUrl("http://www.star-pay.vip/Pay_ZFBWAP_callbackurl");
            $request->setNotifyUrl("http://www.star-pay.vip/Pay_ZFBWAP_notify");
            $request->setBizContent($param);
            $result = $aop->pageExecute ( $request); 
            echo $result;

        }

        // 异步通知
        public function notify(){

            $param = $_POST;

            Vendor("AlipaySdk.aop.AopClient");

            $aop = new \AopClient();

            $order_info = M("Order")->where(['pay_orderid' => $param['out_trade_no']])->field('key,account_id')->find();

            $account = M('ChannelAccount')->where(['id'=>$order_info['account_id']])->field('fenzhuanzhang')->find();
                  
            $aop->alipayrsaPublicKey = $order_info['key'];

            $verify = $aop->rsaCheckV1($param,null,$this->at['sign_type']);

            if ($verify)//签名正确
            {

                if($param['trade_status']=="TRADE_SUCCESS"){
                    //  判断支付返回结果
                    // 必须返回 success 字符 系统下发状态才显示正常
                    $this->EditMoney($param['out_trade_no'], '', 0);

//                    log_separate("获取分账状态：","","","","","",$account['fenzhuanzhang']);

                    if($account['fenzhuanzhang'] == 1) {
                        // 分账控制器
                        $data = [
                            'separate_orderid'=>$param['out_trade_no'],
                            'separate_trade_no'=>$param['trade_no'],
                        ];
                        R("Separate/index",[$data]);
                    }  elseif ($account['fenzhuanzhang']==2) {
                        $data=[
                            'transfer_orderid'=>$param['out_trade_no'],
                        ];
//                        log_separate("进入转账：","","","","","",$param['out_trade_no']);
                        R("Transfer/index",[$data]);

                    }
                    
                }
                echo "success";
            }else{
                exit("验证失败");
            }
        }


        public function callbackurl()
        {
            $out_trade_no = $_GET['out_trade_no'];
            $this->EditMoney($out_trade_no, '', 1);
        }
      

    }

?>

