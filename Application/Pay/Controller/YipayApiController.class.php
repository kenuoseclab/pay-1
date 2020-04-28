<?php
/**
 * Created by PhpStorm.
 * Date: 2018-12-26
 * Time: 15:06
 */

namespace Pay\Controller;
class YipayApiController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    //支付
    public function Pay($array)
    {
		$orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site . 'Pay_YipayApi_notifyurlsb.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_YipayApi_callbackurl.html'; //返回通知

        $orderid = I("request.pay_orderid", '');

        $body = I('request.pay_productname', '');

        $parameter = [
            'code'         => 'YipayApi',
            'title'        => 'YipayApi支付宝H5',
            'exchange'     => 1, // 金额比例
            'gateway'      => '',
            'orderid'      => '',
            'out_trade_id' => $orderid, //外部订单号
            'channel'      => $array,
            'body'         => $body,
        ];

        //支付金额
        $pay_amount = I("request.pay_amount", 0);
        // 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);
        //金额上浮
        $randm=mt_rand(10,199);
        $pay_amount=$pay_amount+$randm/100;
        M('Order')->where(['pay_orderid'=>$return['orderid']])->setField(['pay_amount'=>$pay_amount]);

        //金额上浮
        error_reporting(0);
        header("Content-type: text/html; charset=utf-8");
        $tjurl = "https://pay.sc.189.cn/haipay/qrcodepay";
        $appsecret=$return['appsecret'];
        
        $appid=$return['appid'];
        $total_amount=$pay_amount*100;
        $nonce_str=$this->randStr();
        $out_trade_no=$return['orderid']; 
        $version="V1.0";
        $return_url=$notifyurl;
        $openid=$return['signkey'];
        $native = array(
            "appid" => $appid,
            "total_amount" => $total_amount,
            "nonce_str" => $nonce_str,
            "out_trade_no" => $out_trade_no,
            "version" => $version,
            "return_url" => $return_url,
            "openid" => $openid,
        );
        ksort($native);
        $md5str = "";
        foreach ($native as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        //echo($md5str . "key=" . $Md5key);
        $sign = strtoupper(md5($md5str . "appsecret=" . $appsecret));
        $native["sign"] = $sign;
        $data=$md5str . "sign=" . $sign;
        $rs=$this->vpost($tjurl,$data);
        $result=json_decode($rs,true);
        $payurl=$result['qrcode_url'];
        M('order')->where(['pay_orderid'=>$return['orderid']])->setField(['qrurl'=>$payurl]);
        $this->assign('orderid',$return['orderid']);
        $this->assign('success_url',$return['callbackurl']);
        $this->assign('money',sprintf('%.2f',$pay_amount));
        $this->display("WeiXin/YipayApiMobile");

    }

    //curl请求
    private function vpost($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    private function randStr(){
       $chars= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
       $re = '';
       for ($i = 0; $i < 32; $i++) {
           $re .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
       return $re;
    }

    //订单查询
    public function automaticAlipayQuery(){
        $orderid = I('request.id/s');
        $orderWhere['pay_orderid'] =$orderid;
        $order = M('Order')->where($orderWhere)->find();
        if (!is_array($order)){
            $return['code']=-1;
            $return['msg']='交易号不存在！';
            $this->ajaxReturn($return,'JSON');
        } 
        
        if ($order['pay_status'] == 0) {
            
            if($order['qrurl']){
                $data['url'] =$order['qrurl'];
                $data['h5'] = $order['qrurl'];
                $data['qrcode'] =$order['qrurl'];
                $return['code']=100;
                $return['msg']='请扫码支付';
                $return['data']=$data;
                $this->ajaxReturn($return,'JSON');
            }
            
        }
        if ($order['status'] == 3){
            $return['code']=-2;
                $return['msg']='当前订单已经过期,请重新发起支付！';
                $this->ajaxReturn($return,'JSON');
        }
        if ($order['pay_status'] == 2){
            $return['code']=200;
                $return['msg']='当前订单已经支付成功!';
                $this->ajaxReturn($return,'JSON');
            
        }
    }


    //同步通知
    public function callbackurl()
    {
        $Order = M("Order");
        $orderid=I('request.orderid/s');  
        $pay_status = $Order->where(['pay_orderid' => $orderid])->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_GET["orderid"], 'Jmalipay', 1);
        }else{
            exit("交易成功");
        }

    }

    //异步通知
    public function notifyurlsb()
    {
        // array (
        //   'code' => '0',
        //   'out_trade_no' => '20190702225245100515',
        //   'trade_id' => '201907022252480069845790',
        //   'attach' => 'null',
        //   'type' => '0',
        //   'pay_type' => '0',
        //   'trade_state' => '1',
        //   'pay_time' => '2019-07-02 22:54:12',
        // )
        $data=I('request.');
        // $data2 = file_get_contents("php://input");
        file_put_contents('./Data/YipayApi.txt', "【".date('Y-m-d H:i:s')."】多个订单回调参数：\r\n".var_export($data,true)."\r\n\r\n",FILE_APPEND);

        

        $orderid=I('request.out_trade_no/s');
        if($data['code']!=0){
            exit("{'code':'faild'}");
        }
        $where['pay_ytongdao']="YipayApi";
        $where['pay_orderid']=$orderid;
        $order=M('order')->where($where)->find();
        if(!$order){
            exit("{'code':'faild'}");
        }
        $time=strtotime($data['pay_time']);
        if($time<=$order['pay_applydate']){
            exit("{'code':'faild'}");
        }
        
        if ($data["trade_state"] == "1") {
               //$this->EditMoney($orderid, 'YipayApi', 0);
               exit("{'code':'success'}");
        }else{
            exit("{'code':'faild'}");
        }
        

      }



   }