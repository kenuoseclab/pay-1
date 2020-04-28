<?php
namespace Pay\Controller;
use MoneyCheck;
require_once("redis_util.class.php");
class FxSmsController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }  
    public function Pay($array=null)
    {
		
        $orderid = I("request.pay_orderid");
        $body = I('request.pay_productname','vip');
        $notifyurl = $this->_site . 'Pay_FxSms_notifyurl.html'; //异步通知
        $callbackurl = $this->_site . 'Pay_FxSms_callbackurl.html'; //跳转通知
        $pay_amount = I("post.pay_amount", 0);
        $moneyCheck = new MoneyCheck();
        
        $parameter = array(
            'code' => 'FxSms',       // 通道代码
            'title' => '飞行模式宝转卡',   //通道名称
            'exchange' => 1,          // 金额比例
            'gateway' => '',            //网关地址
            'orderid' => '',            //平台订单号（有特殊需求的订单号接口使用）
            'out_trade_id'=>$orderid,   //外部商家订单号
            'body'=>$body,              //商品名称
            'channel'=>$array,          //通道信息
        );   
        $return = $this->orderadd($parameter);
        while (!$moneyCheck->checkAccountMoney($return['account_id'],$pay_amount)) {
           $pay_amount=$pay_amount-0.01;
           
        }
        $checkResult = $moneyCheck->setAccountKey($return['account_id'],$pay_amount);
        if($checkResult){
            if($pay_amount!=$return['amount']){
            	M('Order')->where(['pay_orderid'=>$return['orderid']])->setField(['pay_amount'=>$pay_amount]);
            }
        }else{
            $this->showmessage('账户:交易量过大，限制交易！');
        }
        $cardNo = $return['signkey'];
        $bankAccount = $return['mch_id']; 
        $money = $pay_amount;     //支付金额
        $bankMark = $return['appid'];
        $bankName = $return['appsecret'];
        $index = $return['zfb_pid']; //cardIndex
        $ppurl="alipays://platformapi/startapp?appId=20000200&actionType=toCard&sourceId=bill&ap_framework_sceneId=20000067&cardNo=请勿修改金额,3分钟内到账****&bankAccount={$bankAccount}&bankName={$bankName}&bankMark={$bankMark}&money={$money}&amount={$money}&REALLY_STARTAPP=true&startFromExternal=false&cardIndex={$index}&cardNoHidden=true&cardChannel=HISTORY_CARD&orderSource=from";
        $codeurl="https://d.alipay.com/i/index.htm?iframeSrc=".urlencode($ppurl);
        $codeurl=str_replace("%2A%2A%2A%2A","****",$codeurl); 
        $payurl = U('FxSms/detail',array('id'=>$return['orderid']),true,true);
        $newurl2="alipays://platformapi/startapp?appId=60000029&showLoading=YES&url=".urlencode($payurl);
        $this->assign('payurl',$newurl2);
        $this->assign('orderlasttime',300);
        // $this->assign('url_direct',$url1);
		$this->assign('url_direct',$newurl2);
     	import("Vendor.phpqrcode.phpqrcode", '', ".php");
        $QR = "Uploads/codepay/" . $return["orderid"] . ".png"; //已经生成的原始二维码图
        \QRcode::png($codeurl, $QR, "L", 20);
        $this->assign("imgurl", $this->_site . $QR);
        $this->assign('params', $return);
        $this->assign('orderid', $return['orderid']);
        $this->assign('money', $pay_amount);
        $url = "alipays://platformapi/startapp?appId=20000067&url=".urlencode($payurl);
        $this->assign("url",$url);
        if($this->isMobile2()){
            $this->display("WeiXin/alipayjump");
        }else{
            $this->display("WeiXin/bankpc");
        }
	
   }

   public function detail(){
        $id=I('request.id/s');
        if (empty($id)) {
            $this->showmessage("订单不存在!");
        }
        $order=M('Order')->where(['pay_orderid'=>$id])->find();
        if (!$order) {
            $this->showmessage("订单不存在!");
        }
        $tt=time()-$order['pay_applydate'];
        $lasttime=300-$tt;
        if($lasttime<0){
            $lasttime=0;
        }
        $payurl = U('FxSms/orderdetail',array('id'=>$order['pay_orderid']),true,true);
        $payurl3 = U('FxSms/detail',array('id'=>$order['pay_orderid']),true,true);
        $payurl2 = U('FxSms/orderdetail2',array('id'=>$order['pay_orderid']),true,true);
        $account=M('ChannelAccount')->where(['id'=>$order['account_id']])->find();
        $cardNo = $order['key'];
        //$cardNo = "请勿修改金额，2分钟到账***";
        $bankAccount = $order['memberid']; 
        $money = $order['pay_amount'];    //支付金额
        $bankMark = $order['account'];
        $bankName = $account['appsecret'];
        $index = $account['zfb_pid']; //cardIndex
         //写入订单实际待支付金额
        $gorul="alipays://platformapi/startapp?appId=09999988&actionType=toCard&sourceId=bill&cardNo={$cardNo}&bankAccount={$bankAccount}&money={$money}&amount={$money}&bankMark={$bankMark}&bankName=&cardIndex={$cardIndex}&cardNoHidden=true&cardChannel=HISTORY_CARD&orderSource=from";
    
        $this->assign('orderlasttime',$lasttime);
        $this->assign('orderid',$id);
        $this->assign('account',$bankAccount);
        $this->assign('carno',$cardNo);
        $this->assign('bankname',$bankName);
        $this->assign('bankmark',$bankMark);
        $this->assign('amount',$money);
        $this->assign('cardindex',$index);
        $this->display('WeiXin/bankfz');

    }

   
    public function callbackurl()
    {
        $this->display('WeiXin/success');
    }

    public function getstatus(){
        $orderid = I('request.id/s');
        $orderWhere['pay_orderid'] =$orderid;
        $order = M('Order')->where("pay_orderid={$orderid}")->find();
        if (!is_array($order)){
            $return['code']=-1;
            $return['msg']='交易号不存在！';
            $this->ajaxReturn($return,'JSON');
        } 
        
        if ($order['pay_status'] == 0) {
            if (($order['pay_applydate'] + 299) < time()) {
                 M('Order')->where(['pay_orderid'=>$orderid])->setField(['pay_status'=>3]);
                $return['code']=-1;
                $return['msg']='当前订单已经过期,请重新发起支付！';
                $this->ajaxReturn($return,'JSON');
            }
            if($order['key']){
                import("Vendor.phpqrcode.phpqrcode",'',".php");
                $QR = "Uploads/codepay/". $order['pay_orderid'] . ".png";
                \QRcode::png($order['key'], $QR, "L", 20);
                $return['code']=100;
                $return['msg']='请扫码支付';
                $return['data']['qrcode']='/'.$QR;
                $return['data']['name']=$order['pay_channel_account'];
                $this->ajaxReturn($return,'JSON'); 
            }else{
                $return['code']=300;
                $return['msg']='等待接单中';
                // $return['data']['qrcode']=$order['account'];
                $this->ajaxReturn($return,'JSON'); 
            }
                       
        }
        if ($order['status'] == 3){
                $return['code']=-1;
                $return['msg']='当前订单已经过期,请重新发起支付！';
                $this->ajaxReturn($return,'JSON');
        }
        if ($order['pay_status'] == 2 ||$order['pay_status'] == 1){
            $return['code']=200;
                $return['msg']='当前订单已经支付成功!';
                $this->ajaxReturn($return,'JSON');
            
        }
    }

     public function notify()
    {
        $res = I('post.');
        file_put_contents('./Data/FxSms.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($res)."\r\n\r\n",FILE_APPEND);
        $time = strtotime($res['time']);  //到账时间戳
        $machine = $res['machine_num']; //设备号
        $content = $res['content'];  
        $amount = $this->checkCard($content);
        $map['pay_applydate'] = ['GT',time()-300];  //查询条件1  当前时间到过去5分钟内的订单
        $map['pay_amount'] = ['EQ',$amount];    //查询条件2   金额一致     
        $map['pay_status'] = ['EQ','0'];     //查询条件3   状态为未支付
        $map['account_id'] = ['EQ',$machine]; //查询条件4 渠道账号 
        $query = M('Order')->where($map)->find();

        if($query){
            $moneyCheck = new moneyCheck();
            $isSystemOrder = $moneyCheck->checkAccountMoney($query['account_id'],$amount);
            if($isSystemOrder){
                //不是系统订单
                file_put_contents('./Data/FxSmssystemfail.txt', "【".date('Y-m-d H:i:s')."】回调结果：\r\n".json_encode($_REQUEST)."\r\n\r\n",FILE_APPEND);
                echo "非系统订单";die;
            }
            $this->EditMoney($query['pay_orderid'],'FxSms',0); 
            $moneyCheck->deletAccountKey($query['account_id'],$amount);
            echo 'success';
        }   
    }

    public function checkCard($content=null)
    {  
    	$bankType = [
    		'EMS'  => '邮储银行',
    		'ABC'  => '中国农业银行',
    		'CCB'  => '建设银行',
    		'CEB'  => '光大银行',
    		'ICBC' => '工商银行',
    		'CMB'  => '招商银行',
    		'CMBC' => '民生银行',
            'PingAn'=>'平安银行',
    		'BOC'=>'中国银行',
            'CIB'=>'兴业银行',
            'COMM'=>'交通银行',
            'HXBANK'=>'华夏银行',
            'SPDB'=>'浦发银行',
            'MTBANK'=>'民泰银行',
            'EGBANK'=>'恒丰银行'
    	];
    	$check = '';
    	foreach($bankType as $k => $v){
    	   if(strpos($content,$v)) {
    	     $check =  $k;
    	     break;
    	   }
    	}
    	$amount = '';
    	switch($check){
    		case 'CEB':
    			$amount =  strstr(substr(strstr($content,'存入'),6),'元，余额',true);
    			break;
    		case 'ABC':
    			$amount = strstr(substr(strstr($content,'交易人民币'),15),'，余额',true);
    			break;
    		case 'CCB':
    			$amount = substr(strstr($content,'收入人民币'),15,strpos(strstr($content,'收入人民币'),'元,活')-15);
    			break;
    		case 'EMS':
    			$amount = strstr(substr(strstr($content,'金额'),6),'元，余额',true);
    			break;
    		case 'ICBC':
    			//$amount = strstr(substr(strstr($content,')'),1),'元，余额',true);
    			$amount = strstr(substr(strstr($content,')'),1),'元。',true);
    			break;
    		case 'CMB':
    			$amount = strstr(substr(strstr($content,'人民币'),9),'元',true);
    			break;
    		case 'CMBC':
    		    $amount = strstr(substr(strstr($content,'￥'),3),'元，',true);
    		    break;
    		case 'PingAn':
    			$amount = strstr(substr(strstr($content,'入人民币'),12),'元',true);
    			break;
            case 'BOC':
                $amount = strstr(substr(strstr($content,'入人民币'),12),'元',true);
                break;
            case 'CIB':
                $amount = strstr(substr(strstr($content,'付款收入'),12),'元',true);
                break;
            case 'COMM':
                $amount = strstr(substr(strstr($content,'网银转入'),12),'元',true);
                break;
            case 'HXBANK':
                $amount = strstr(substr(strstr($content,'账人民币'),12),'元',true);
                break;
            case 'SPDB':
                $amount = strstr(substr(strstr($content,'存入'),6),'[',true);
                break;
            case 'MTBANK':
                $amount = strstr(substr(strstr($content,'转入'),6),'元',true);
                break;
            case 'EGBANK':
                $amount = strstr(substr(strstr($content,'账户转入'),12),'元',true);
                break;
    		default :
    			file_put_contents('./Data/FxSmsFail.txt',$content.PHP_EOL,FILE_APPEND);

    	}

    	return $amount;
    	
    }


    public function isMobile2(){
        $useragent=isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $useragent_commentsblock=preg_match('|\(.*?\)|',$useragent,$matches)>0?$matches[0]:'';
        function CheckSubstrs($substrs,$text){
            foreach($substrs as $substr)
                if(false!==strpos($text,$substr)){
                    return true;
                }
            return false;
        }
        $mobile_os_list=array('Google Wireless Transcoder','Windows CE','WindowsCE','Symbian','Android','armv6l','armv5','Mobile','CentOS','mowser','AvantGo','Opera Mobi','J2ME/MIDP','Smartphone','Go.Web','Palm','iPAQ');
        $mobile_token_list=array('Profile/MIDP','Configuration/CLDC-','160×160','176×220','240×240','240×320','320×240','UP.Browser','UP.Link','SymbianOS','PalmOS','PocketPC','SonyEricsson','Nokia','BlackBerry','Vodafone','BenQ','Novarra-Vision','Iris','NetFront','HTC_','Xda_','SAMSUNG-SGH','Wapaka','DoCoMo','iPhone','iPod');

        $found_mobile=CheckSubstrs($mobile_os_list,$useragent_commentsblock) ||
            CheckSubstrs($mobile_token_list,$useragent);

        if ($found_mobile){
            return true;
        }else{
            return false;
        }
    }

}
