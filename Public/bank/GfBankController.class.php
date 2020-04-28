<?php
namespace Pay\Controller;

use Org\Util\HttpClient;
use Org\Util\Ysenc;

class GfBankController extends PayController{
	
	protected $_bank_code = array(
			'招商银行' =>	'3001',	
			'工商银行' =>	'3002',	
			'建设银行' =>	'3003',	
			'浦发银行' =>	'3004',	
			'农业银行' =>	'3005',	
			'民生银行' =>	'3006',	
			'兴业银行' =>	'3009',	
			'交通银行' =>	'3020',	
			'光大银行' =>	'3022',	
			'中国银行' =>	'3026',	
			'北京银行' =>	'3032',	
			'平安银行' =>	'3035',	
			'广发银行' =>	'3036',	
			'中信银行' =>	'3039',	
		);
	
	public function __construct(){

		parent::__construct();
	}


	public function Pay($array){
		
		$orderid = I("request.pay_orderid");
        $body = I('request.pay_productname');
        $notifyurl = $this->_site ."Pay_GfBank_notifyurl.html"; //异步通知
        $callbackurl = $this->_site . 'Pay_GfBank_callbackurl.html'; //跳转通知

		$parameter = array(
			'code' => 'GfBank',
			'title' => '网银支付-国富',
			'exchange' => 1, // 金额比例
            'gateway' => '',
            'orderid'=>'',
            'out_trade_id' => $orderid, //外部订单号
            'channel'=>$array,
            'body'=>$body
		);

        
		//支付金额
		$pay_amount = I("request.pay_amount", 0);
		
		$pay_amount * 100 >= 1000 || $this->showmessage('金额不能少于10元');
		
		// 订单号，可以为空，如果为空，由系统统一的生成
        $return = $this->orderadd($parameter);

        //如果生成错误，自动跳转错误页面
        $return["status"] == "error" && $this->showmessage($return["errorcontent"]);
        
        //跳转页面，优先取数据库中的跳转页面
        
        $return["notifyurl"] || $return["notifyurl"] = $notifyurl;
        
        //获取请求的url地址
        $url=$return["gateway"];

		
		$encryp = $this->_encryptDecrypt(serialize($return), 'lgbya');
        $this->assign(array(
            'url' => $url, //接口地址
            'bank_array' => C('QFTBANKCODE'), //银行码数组 保存在配置文件中
            'orderid' => $return['orderid'],
            'money' =>  sprintf('%.2f', $return['amount']/100),
            'encryp' => $encryp,
            'rpay_url' => U('Pay/' . $parameter['code'] . '/Rpay'),
        ));

		
		$this->assign(array(
            'url' => $url, //接口地址
            'bank_array' => $this->_bank_code, //银行码数组 保存在配置文件中
            'orderid' => $return['orderid'],
            'money' =>  sprintf('%.2f', $return['amount']/100),
            'encryp' => $encryp,
            'rpay_url' => U('Pay/' . $parameter['code'] . '/Rpay'),
        ));

        //选择银行的视图，
        $this->display('QftBank/pay');
	}

	
	protected function _encryptDecrypt($string, $key='',  $decrypt='0'){ 
        if($decrypt){ 
            $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($string), MCRYPT_MODE_CBC, md5(md5($key))), "12");
            return $decrypted; 
        }else{ 
            $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key)))); 
            return $encrypted; 
        } 
    }
	
    public function Rpay(){

        //接收传输的数据
        $post_data = I('post.','');
        
        //将数据解密并反序列化
        $return = unserialize( encryptDecrypt($post_data['encryp'],'lgbya',1) );
        
     
        //检测数据是否正确
        $return || $this->error('传输数据不正确！');
        ($bank_code = $post_data['bankCode']) || $this->error('请选择银行！');
        // ($cart_type = $post_data['bankType']) || $this->error('请选择银行卡类型！');

        $post_data['url'] || $this->error('接口地址错误！');
		
        $arraystr = array(
			'merchno' => $return['mch_id'],
			'amount' => sprintf('%.2f',$return['amount']),
			'traceno' => $return['orderid'],
			'channel' => 2,
			'settleType' => 2,
			'bankCode' =>  $bank_code,
			'notifyUrl' => $return['notifyurl'],
			'returnUrl' => $return['callbackurl'],
        );

 
        // sign    签名信息    是   算法见 1.1.3

        $arraystr['signature'] = $this->_createSign($arraystr, $return['signkey']);

        echo $this->_buildRequestForm($return['gateway'], $arraystr); 
   
    }
	
	
	
        /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $params array 请求参数数组
     * @return string 提交表单HTML文本
     */
    private function _buildRequestForm($url, $params) {
        $sHtml = "<form id='Form' name='Form' action='".$url."' method='POST'>";
        reset($params);
        while (list ($key, $val) = each ($params)) {
            $val = str_replace("'","&apos;",$val);
            $sHtml .= "<input type='hidden' name='".$key."' value='".$val."'/>\n";
        }
        //submit按钮控件请不要含有name属性
        $sHtml .= "<input type='submit' style='display:none;'/></form>\n";
        $sHtml = $sHtml."<script>document.getElementById('Form').submit();</script>\n";
        return $sHtml;
    }


    protected function _createSign($data, $key){
        $sign = '';
        ksort($data);
        foreach( $data as $k => $vo ){
            $sign .= $k . '=' . $vo . '&';
        }
        return  strtoupper( md5($sign  .  $key) );
    }


	public function httpPostData($url, $data_string){

		$cacert = '';	//CA根证书  (目前暂不提供)
        $CA = false ; 	//HTTPS时是否进行严格认证
        $TIMEOUT = 30;	//超时时间(秒)
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        
        $ch = curl_init ();
        if ($SSL && $CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); 	// 	只信任CA颁布的证书
            curl_setopt($ch, CURLOPT_CAINFO, $cacert); 			// 	CA根证书（用来验证的网站证书是否是CA颁布）
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); 		//	检查证书中是否设置域名，并且是否与提供的主机名匹配
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 	// 	信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); 		// 	检查证书中是否设置域名
        }

        curl_setopt ( $ch, CURLOPT_TIMEOUT, $TIMEOUT);
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $TIMEOUT-2);
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data_string );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, array(
            // 'Content-Type: application/json;charset=utf-8'
        )  );

        ob_start();
        curl_exec($ch);
        $return_content = ob_get_contents();
        ob_end_clean();

        $return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
       
        curl_close($ch);
        return array (
            $return_code,
            $return_content
        );

	}



	public function callbackurl(){
        $Order = M("Order");
        $pay_status = $Order->where(["pay_orderid" =>$_REQUEST["traceno"]])->getField("pay_status");
        if($pay_status <> 0){
            $this->EditMoney($_REQUEST["traceno"], 'GfBank', 1);
        }else{
            exit("error");
        }
	}

	 // 服务器点对点返回
    public function notifyurl(){
        
        date_default_timezone_set('PRC');

        $data = file_get_contents('php://input');//接受post原数据



    
        if($_POST['status'] == '2'){


            $post_data = array();
            foreach ($_POST as $key=>$value){
                $post_data = array_merge($post_data,array(iconv('GBK//IGNORE','UTF-8',$key)=>iconv('GBK//IGNORE','UTF-8',$value)));
            }
            
            $temp='';
            ksort($post_data);//对数组进行排序
            //遍历数组进行字符串的拼接
            foreach ($post_data as $x=>$x_value){
                if ($x != 'signature'&& $x_value != null && $x_value != 'null'){
                    $temp = $temp.$x."=".$x_value."&";
                }
            }

            $channel_model = M('Channel');
            $channel_where = array('code'=>'GfBank');
            $key = $channel_model->where($channel_where)->getField('signkey');

            $md5=strtoupper(md5(iconv('UTF-8','GBK//IGNORE',$temp.$key)));

            if( $md5 == $_POST['signature'] ){
                $this->EditMoney($_POST['traceno'], 'GfBank', 0);
                echo "success";
                exit;
            }
        }  

        echo "fails"; 
    }


}