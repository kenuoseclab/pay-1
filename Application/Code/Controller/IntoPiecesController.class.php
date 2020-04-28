<?php

namespace Code\Controller;

use Think\Upload;

class IntoPiecesController extends UserController{

	protected $model;

	public function __construct(){
		parent::__construct();
		$this->model = M('IntoPieces');
	}

	public function means(){
		$provinceList = M('LocationProvince')->limit(1000)->select();
		$shopCategoryList = M('ShopCategory')->limit(1000)->select();
		$this->assign(array(
					'provinceList' =>  $provinceList,
					'shopCategoryList' => $shopCategoryList,
				));
		$this->display();
	}

	public function ajaxGetLocation(){
		$info = [
				'status' => 0,
				'msg' => 'faile',
				'data' => null,
			];
		if(IS_AJAX){

			$name = I('post.name','');
			$id = I('post.id','', 'intval');
			if($name && $id){
				try{
					$where = array(
								'pid'=>$id,
							);
					$tableName = 'Location' . ucfirst($name);

					$m = M($tableName);
					$data = $m->where($where)->select();
					$info = [
							'status' => 1,
							'msg' => 'ok',
							'data' => $data,
						];
				}catch(\Exception $e){

				}
			}
		}
		$this->ajaxReturn($info);
	}

	public function ajaxGetIndustry(){
		$info = [
				'status' => 0,
				'msg' => 'faile',
				'data' => null,
			];
		$id = I('post.id', '' , 'intval');
		$name = I('post.name', '');
		if($id&&$name){
			try{
				$where = array('pid' => $id);
				$tableName = 'Industry' . ucfirst($name);
				$m = M($tableName);
				$data = $m->where($where)->select();
				$info = [
					'status' => 1,
					'msg' => 'ok',
					'data' => $data,
				];
			}catch(\Execption $e){

			}
		}
		$this->ajaxReturn($info);
	}
	


	public function toAdd(){
		

		$data = $this->model->create();
		$data['uid'] = session('user_auth.uid');
		$array = array();
		foreach($_FILES as $k=>$v){
			$array[$k] = $this->upload($v);
		}

		$data = array_merge($array,$data);
		$data['license_start_date'] = strtotime($data['license_start_date']);
		
		if(!$data['license_period'])
			unset($data['license_end_date']);
		else
			$data['license_end_date'] = strtotime($data['license_end_date']);
		// echo "<pre>";
		// var_dump($array);
		// var_dump($data);
		// var_dump($_POST);
		// exit;
		if($this->model->add($data))
			$this->success('提交成功！');
		else
			$this->error('提交失败');
	
	}


	public function toSave(){

	}

	protected function upload($array){
		
        $upload = new Upload();
        $upload->maxSize = 2097152;
        $upload->exts = array('jpg', 'gif', 'png');
        $upload->savePath = '/verifyinfo/';

      
        $info = $upload->uploadOne($array);
        

        if (! $info) { // 上传错误提示错误信息
            $this->error($upload->getError());
        } else {
            $data = [
                'filename'=>$info['name'],
                'savepath'=>'Uploads'.$info['savepath'].$info['savename'],
            ];
            $re = M("IntoPiecesPics")->add($data);
        	return $re;
        }

	}

	public function auth(){


		date_default_timezone_set('PRC');
		$parse = array(
			"contact" => "联系人", 
			"mobile" => '13802612634',
			"email" => "email@gmail.com",
			"mch_shortname" => "测试简称",
			"province" => "广东省",
			"city" => '深圳市',
			"address" => '我是地址',
			"id_card_no" => '154375487698770',
			"license_num" => '125346457659760',
			"license_start_date" => "2016-11-11",
			"license_period" => '1',
			"license_scope" => '测试经营范围',
			"balance_type" => '1',
			"balance_name" => '名称',
			"balance_account" => '124532634436',
			"mch_name" => "测试用户名",
			"service_phone" => '7391541653754',
			"industry_no" => '161215010100001',
			"bank_no" => "001110002774",
			"id_card_img_b" => '@demo' ,
			"id_card_img_f" => '@demo',
			"license_img" => '@demo',
		);
		echo count($parse);
		$data = array();
		foreach($parse as $k=>$v){
			$data[] = $k . '=' . $v;
		}
		$data = implode('&', $data);	
		


// $data = 
// '--03ddacdd91c144d0a254ed8d3b0e9caa--
// Content-Disposition: form-data; name="license_period"

// 1
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="license_num"

// 125346457659760
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="balance_account"

// 124532634436
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="wx_use_parent"

// 1
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="balance_name"

// 名称
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="payment_type2"

// 60
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="payment_type3"

// 60
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="payment_type1"

// 60
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="id_card_no"

// 154375487698770
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="id_card_img_b"; filename="id_card_img_b.jpg"
// Content-Type: text/plain

// demo
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="service_phone"

// 7391541653754
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="id_card_img_f"; filename="id_card_img_f.jpg"
// Content-Type: text/plain

// demo
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="city"

// 深圳市
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="mch_shortname"

// 测试简称
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="email"

// email@gmail.com
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="bank_no"

// 001110002774
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="province"

// 广东省
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="license_img"; filename="license_img.jpg"
// Content-Type: text/plain

// demo
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="address"

// 我是地址
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="mobile"

// 77439665117
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="industry_no"

// 161215010100001
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="license_scope"

// 测试经营范围
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="mch_name"

// 测试用户名
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="balance_type"

// 1
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="license_start_date"

// 2016-11-11
// --03ddacdd91c144d0a254ed8d3b0e9caa
// Content-Disposition: form-data; name="contact"

// 联系人
// --03ddacdd91c144d0a254ed8d3b0e9caa--';
	
$url = '/v1/mchinlet/authtest';
$date = gmdate('D, d M Y H:i:s') . ' GMT';


$length = strlen($data);
$num = '0dc8b7027f92264895cde411cdbf33e6';


$signature = md5('POST&' . $url . '&' . $date . '&' . $length . '&' . $num) ;


		$header = array(
					
					'Content-Type: multipart/form-data;  boundary=03ddacdd91c144d0a254ed8d3b0e9caa',
					'Content-Length: ' . $length,
					'Date:' . $date,
					'Authorization: Uline 976773842@qq.com:' . $signature,
				);

		$ch = curl_init('http://pay.stage.uline.cc/v1/mchinlet/authtest');
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
		$str = curl_exec($ch);
		curl_close($ch);
		echo "<pre>";
		print_r($str);
		echo '<br>';
		print_r($header);
		$data = json_decode($str);
		var_dump($data);




	}
} 