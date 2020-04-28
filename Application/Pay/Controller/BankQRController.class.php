<?php
namespace Pay\Controller;
use Endroid\QrCode\QrCode;
class BankQRController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }
    public function Pay($array)
    {  
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname','vip');  
        $notifyurl = $this->_site . 'Pay_BankQR_notifyurl.html'; //异步通知
    
        $callbackurl = $this->_site . 'Pay_BankQR_callbackurl.html'; //跳转通知
   
        $parameter = array(
            'code' => 'BankQR',       // 通道代码
            'title' => '银行收款码',      //通道名称
            'exchange' => 1,          // 金额比例
            'gateway' => '',            //网关地址
            'orderid' => '',            //平台订单号（有特殊需求的订单号接口使用）
            'out_trade_id'=>$orderid,   //外部商家订单号
            'body'=>$body,              //商品名称
            'channel'=>$array,          //通道信息
        );   
        $return = $this->orderadd($parameter);
	$id = M('Order')->where(['pay_orderid'=>$return['orderid']])->find();
	$str=$this->moneyx($return['amount'],$id['pay_channel_account']);  //获取金额 ，时间段内唯一性 降低串单概率    

      //	$return['amount'] = $pay;//收银页面显示实际付款金额
	$payQR =urlencode( $return['unlockdomain']); //获取支付链接
//	M('Order')->where(['pay_orderid'=>$return['orderid']])->setField(['pay_money'=>$pay]); //写入订单实际待支付金额
	$url =  htmlspecialchars_decode($return['unlockdomain']);  //数据库读取的
        //$url = substr($url,0,-3).($pay*100);
	$this->assign('tradeTime',date('Y-m-d H:i:s'));
	$this->showQRcode($url,$return,'weixin');
    }

    public function moneyx($amount,$code)
    {
//	$rand = mt_rand(1,30);  //表示正负 1-50分钱随机  50 可以自己修改 范围
	$map['pay_status'] = ['EQ','0'];
	$map['pay_channel_account'] = ['EQ',$code];
	$map['pay_applydate'] = ['GT',time()-100];
	$map['pay_amount'] = ['EQ',$amount];
	$arr =  M('Order')->where($map)->select();  //状态未支付  时间5分钟内  入金渠道内的子账号相同  的金额 集合
/*
	   for($i=1;$i<20;$i++)
                {       $m = $amount;
                        $m =  ($m-$rand*0.01);
                        if(!in_array($m,$arr)){
                                return $m;
                                break;
                        }
                }
*/
//	dump($arr);
	if(count($arr)>1){
	    exit('系统繁忙，请稍后再试');
	}
    }


    public function money($amount,$code)
    {
	$rand = mt_rand(1,30);  //表示正负 1-50分钱随机  50 可以自己修改 范围
	$map['pay_status'] = ['EQ','0'];
	$map['pay_channel_account'] = ['EQ',$code];
	$map['pay_applydate'] = ['GT',time()-300];
	$arr =  M('Order')->where($map)->field('pay_money')->select();  //状态未支付  时间5分钟内  入金渠道内的子账号相同  的金额 集合

	   for($i=1;$i<20;$i++)
                {       $m = $amount;
                        $m =  ($m-$rand*0.01);
                        if(!in_array($m,$arr)){
                                return $m;
                                break;
                        }
                }
	    exit('系统繁忙，请稍后再试');
    }

    public function callbackurl()
    {

        $this->display('WeiXin/success');
    }

    public function notifyurl()
    {   
//file_put_contents('./log',json_encode($_POST,320).PHP_EOL,FILE_APPEND);	 
		$data = I('post.');
		$data = trim($data);	
	//	$money = $data['money'];
      $money = $_POST['money'];
      file_put_contents("myfile2.txt", $money."\r\n", FILE_APPEND);
		$money = rtrim($money,'元');
//		$map['pay_applydate'] = $data['sign'];
 	        $map['pay_applydate'] = ['GT',time()-100];  //查询条件1  当前时间到过去5分钟内的订单
                $map['pay_amount'] = ['EQ',floatval($money)];    //查询条件2   金额一致     
                $map['pay_status'] = ['EQ','0'];     //查询条件3   状态为未支付
                $map['pay_channel_account'] = ['EQ',$_POST['sign']]; //查询条件4 渠道账号     
                
                $query = M('Order')->where($map)->find();
      file_put_contents("myfile1.txt", $money."\r\n", FILE_APPEND);

        if($query){	
            $this->EditMoney($query['pay_orderid'],'BankQR',0); 
            echo 'success';
	}
    }

}
