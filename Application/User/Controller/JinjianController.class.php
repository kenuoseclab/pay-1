<?php

namespace User\Controller;

use Think\Upload;
use Think\Page;

class JinjianController extends UserController{

	protected $model;

	public function __construct(){
		parent::__construct();
		$this->model = D('Jinjian');
		define('FORM_BOUNDARY', md5(uniqid()));
		define('FORM_HYPHENS', '--');
		define('FORM_EOL', "\r\n");
	}
	
	public function index(){
		//进件管理
		$list = M('Jinjian')
		->where(['uid'=>$this->fans['uid']])
		->field('id,mchid,mch_name,mch_shortname,activate,status')
		->select();
		$this->assign("list", $list);
		$count = M('Jinjian')
		->where(['uid'=>$this->fans['uid']])
		->count();
		$page = new Page($count,15);
		$this->assign('page',$page->show());
		$this->display();
	}
	
	public function show() {
		$id = I('id', 0 ,'intval');
		if($id <=0) {
			$this->error('参数错误');
		}
		$data = M('Jinjian')->where(['id' => $id])->find();
		if(empty($data)) {
			$this->error('进件申请不存在');
		}
		if($data['uid'] != $this->fans['uid']) {
			$this->error('您没有权限查看该进件申请');
		}
		if($data['industry_no']) {
			$data['industry_category3'] = M('industry_category3')->where(array('industry_no'=>$data['industry_no']))->find();
			if(!empty($data['industry_category3'])) {
				$data['industry_category2'] = M('industry_category2')->where(array('id'=>$data['industry_category3']['pid']))->find();
			}
			if(!empty($data['industry_category2'])) {
				$data['industry_category1'] = M('industry_category1')->where(array('id'=>$data['industry_category2']['pid']))->find();
			}
		}
		//商户类型
		$data['mtype'] = M('shop_category')->where(['id'=>$data['industry_category1']['pid']])->find();
		$region = get_region_list();
		$this->assign('region', $region);
		$this->assign('data', $data);
		//支付类型
		$paylist = M('jinjian_pay')->where(array('jid'=>$data['id']))->select();
		$this->assign('paylist', $paylist);
		$this->display();
	}
	
	public function add(){
		$provinceList = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
		$shopCategoryList = M('ShopCategory')->select();

		$this->assign(array(
					'provinceList' =>  $provinceList,
					'shopCategoryList' => $shopCategoryList,
				));
		$this->display();
	}
	
	public function edit() {
		$id = I('id', 0 , 'intval');
		if(!$id) {
			$this->error('参数错误');
		}
		$data = $this->model->where(['id'=>$id])->find();
		if(empty($data)) {
			$this->error('进件申请不存在');
		}
		if($data['uid'] != $this->fans['uid']) {
			$this->error('您没有权限操作该进件申请');
		}
		if(IS_POST) {
			if($data['status'] == 1) {
				$this->error('审核中不能修改');
			}
			$data = I('post.');
			$data['license_start_date'] = strtotime($data['license_start_date']);
			if($data['license_period']) {
				unset($data['license_end_date']);
			} else {
				$data['license_period'] = 0;
				$data['license_end_date'] = strtotime($data['license_end_date']);
			}
			$paytype = C('PAY_TYPE');
			$fieldname = 'payment_type';
			$mpay = M('Jinjian_pay');
			$flag = false;
			$payment = array();
			foreach($paytype as $k => $v) {
				if(isset($data[$fieldname.$k]) && $data[$fieldname.$k]>0){
					$payment[$k] = $data[$fieldname.$k];
					$flag = true;
				}
			}
			if(FALSE === $flag) {
				$this->error('未配置支付渠道');
			}
			$data['status'] = 1;
			if(FALSE !== $this->model->where(['id'=>$id])->save($data)) {
				//修改支付渠道
				foreach($payment as $k => $v) {
					$pay = M('jinjian_pay')->where(array('pay_type'=>$k,'jid'=>$id))->find();
					if(empty($pay)) {
						$payData['pay_type'] = $k;
						$payData['pay_name'] = $paytype[$k];
						$payData['cycle'] = 'D1';
						$payData['rate'] = $v;
						$payData['jid'] = $id;
						$payData['mch_id'] = $data['mchid'] ? $data['mchid'] : '';
						$payData['status'] = 0;
						$payData['ctime'] = time();
						M('jinjian_pay')->add($payData);
					} else {
						if($v != $pay['rate']) {
							M('jinjian_pay')->where(array('pay_type'=>$k,'jid'=>$id))->save(array('rate'=>$v, 'status'=>2));
						}
					}
				}
				log_message($id, '审核中', 1);
				$this->success('提交成功！');
			} else {
				$this->error('提交失败');
			}
		} else {
			if(!$data['id_card_img_f']) {
				$data['id_card_img_f'] = '/Public/images/pic.png';
			}
			if(!$data['id_card_img_b']) {
				$data['id_card_img_b'] = '/Public/images/pic.png';
			}
			if(!$data['license_img']) {
				$data['license_img'] = '/Public/images/pic.png';
			}
			if($data['industry_no']) {
				$data['industry_category3'] = M('industry_category3')->where(array('industry_no'=>$data['industry_no']))->find();
				if(!empty($data['industry_category3'])) {
					$data['industry_category2'] = M('industry_category2')->where(array('id'=>$data['industry_category3']['pid']))->find();
				}
				if(!empty($data['industry_category2'])) {
					$data['industry_category1'] = M('industry_category1')->where(array('id'=>$data['industry_category2']['pid']))->find();
				}
				if($data['industry_category1']) {
					//商户类型
					$shopCategoryList = M('shop_category')->select();
					$this->assign('shopCategoryList', $shopCategoryList);
					$industry_category1 = M('industry_category1')->where(array('pid'=>$data['industry_category1']['pid']))->select();
					$this->assign('industry_category1', $industry_category1);
				}
				if($data['industry_category2']) {
					$industry_category2 = M('industry_category2')->where(array('pid'=>$data['industry_category2']['pid']))->select();
					$this->assign('industry_category2', $industry_category2);
				}
				if($data['industry_category3']) {
					$industry_category3 = M('industry_category3')->where(array('pid'=>$data['industry_category3']['pid']))->select();
					$this->assign('industry_category3', $industry_category3);
				}
			}
			//获取省份
			$p = M('region')->where(array('parent_id' => 0, 'level' => 1))->select();
			if($data['province']) {
				$c = M('region')->where(array('parent_id' => $data['province'], 'level' => 2))->select();
				$this->assign('citylist', $c);
			}
			if($data['city']) {
				$d = M('region')->where(array('parent_id' => $data['city'], 'level' => 3))->select();				
				$this->assign('districtlist', $d);
			}
			$this->assign('provincelist', $p);
			//结算银行
			if($data['bank_no']) {
				$data['bank_name'] = M('bank')->where(array('id'=>$data['bank_no']))->getField('bank_name');
			}
			//支付类型
			$paylist = M('jinjian_pay')->where(array('jid'=>$data['id']))->select();
			$payTypeWechat = $payTypeAlipay = 0;
			$choosed = array();
			foreach($paylist as $k => $v) {
				$choosed[$v['pay_type']] = $v['rate'];
				if(in_array($v['pay_type'],array(1,2,3,4))) {
					$payTypeWechat = 1;
				}
				if(in_array($v['pay_type'],array(7,8,9))) {
					$payTypeAlipay= 1;
				}
			}
			$this->assign('choosed', $choosed);
			$this->assign('payTypeWechat', $payTypeWechat);
			$this->assign('payTypeAlipay', $payTypeAlipay);
			$this->assign('paylist', $paylist);
			$this->assign('data', $data);
			$this->display();
		}		
	}

	public function toAdd(){
		$count = M('Jinjian')
			->where(['uid'=>$this->fans['uid']])
			->count();
		if($count>0) {
			$this->error('您已申请！');
		}
		$data = I('post.');
		$data['license_start_date'] = strtotime($data['license_start_date']);	
		if($data['license_period'])
			unset($data['license_end_date']);
		else
			$data['license_end_date'] = strtotime($data['license_end_date']);

		$paytype = C('PAY_TYPE');
		$fieldname = 'payment_type';
		$mpay = M('Jinjian_pay');
		$flag = false;
		foreach($paytype as $k => $v) {
			if(isset($data[$fieldname.$k]) && $data[$fieldname.$k]>0){
				$flag = true;
			}
		}
		if(FALSE === $flag) {
			$this->error('未配置支付渠道');
		}
		$data['status'] = 1;
		$data['uid'] = $this->fans['uid'];
		$data['createtime'] = time();
		$re = $this->model->add($data);
		if(FALSE !== $re) {
			foreach($paytype as $k => $v) {
				if(isset($data[$fieldname.$k]) && $data[$fieldname.$k]>0){
					$pdata['pay_type'] = $k;
					$pdata['pay_name'] = $v;
					$pdata['cycle'] = 'D1';
					$pdata['rate'] = $data[$fieldname.$k];
					$pdata['jid'] = $re;
					$pdata['mch_id'] = '';
					$pdata['status'] = 0;
					$pdata['ctime'] = time();
					$mpay->add($pdata);
				}
			}
			log_message($re, '审核中', 1);
			$this->success('提交成功！');
		} else {
			$this->error('提交失败');
		}
	}

	public function upload() {
		if(IS_POST) {
			if(!$this->fans['uid']) exit;
			$upload = new Upload();
			$upload->maxSize = 2097152;
			$upload->exts = array('jpg', 'gif', 'png');
			$upload->savePath = '/mch/'.$this->fans['uid'].'/';
			$info = $upload->uploadOne($_FILES['file']);
			if (! $info) { // 上传错误提示错误信息
				$this->error($upload->getError());
				$res = [
						'code' => 1,
						'msg' => 'fail',
						'data'=>['src'=>''],
				];
			} else {
				$res = [
						'code' => 0,
						'msg' => 'success',
						'data'=>['src'=>'Uploads'.$info['savepath'].$info['savename']],
				];
			}
			$this->ajaxReturn($res);
		}
	}

	//配置微信支付
	public function setting() {	
		if(IS_POST) {
			$post = I('post.');
			if(!$this->fans['uid']) {
				$msg['status'] = 0;
				$msg['msg'] = '页面已过期';
				$this->ajaxReturn($msg);
			}
			$cache = M('Jinjian')->where(['uid' => $this->fans['uid']])->find();
			if(empty($cache)) {
				$msg['status'] = 0;
				$msg['msg'] = '进件不存在';
				$this->ajaxReturn($msg);
			}
			$data['mch_type'] = trim($post['mch_type']);
			$data['config_channel'] = trim($post['config_channel']);
			$data['config_key'] = trim($post['config_key']);
			$data['config_value'] = trim($post['config_value']);
			if(!$data['mch_type'] || !$data['config_channel'] || !$data['config_key'] || !$data['config_value']) {
				$msg['status'] = 0;
				$msg['msg'] = '缺少参数';
				$this->ajaxReturn($msg);
			}
			/*
			if($data['config_key'] == 'jsapi_path') {
				if (!preg_match('/^(http|https|ftp)://([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?/?/i', $data['config_value'])) {
					$msg['status'] = 0;
					$msg['msg'] = '支付授权目录必须符合URI格式规范';
					$this->ajaxReturn($msg);
				}
				$last = substr($data['config_value'], -1);
				if($last != '/') {
					$msg['status'] = 0;
					$msg['msg'] = '支付授权目录必须以/结尾';
					$this->ajaxReturn($msg);
				}
			}
			*/
			
			$m = M('jinjian_mch_setting');
			$data['mch_id'] = $cache['mchid'];
			$re = $m->add($data);
			if(FALSE !== $re) {
				if(true === $this->syncMchSetting($data)) {
					$msg['status'] = 1;
					$msg['msg'] = '提交成功';
					$this->ajaxReturn($msg);
				} else {
					$msg['status'] = 0;
					$msg['msg'] = '提交失败';
					$this->ajaxReturn($msg);
				}
			} else {
				$msg['status'] = 0;
				$msg['msg'] = '提交失败';
				$this->ajaxReturn($msg);
			}
		} else {
		$m = M("jinjian_mch_setting");
        $jinJian = M('Jinjian')->where(['uid'=>$this->fans['uid']])->find();
		//线下通道推荐关注微信公众号appid
        $offline_subscribe_appid = $m->where(array("mch_id"=>$jinJian['mchid'],"config_channel"=>"reg_offline","config_key"=>'subscribe_appid'))->find();
        $this->assign('offline_subscribe_appid', $offline_subscribe_appid);
        $online_subscribe_appid = $m->where(array("mch_id"=>$jinJian['mchid'],"config_channel"=>"reg_online","config_key"=>'subscribe_appid'))->find();
        $this->assign('online_subscribe_appid', $online_subscribe_appid);
        $offlineList = $m->where(array("mch_id"=>$jinJian['mchid'],"config_channel"=>"reg_offline","config_key"=>'sub_appid'))->select();
        $this->assign("offlineList",$offlineList);
        $onlineList = $m->where(array("mch_id"=>$jinJian['mchid'],"config_channel"=>"reg_online","config_key"=>'sub_appid'))->select();
        $this->assign("onlineList",$onlineList);
		$this->display();
		}
	}
	//同步商户设置
	private function syncMchSetting($data){

		$curl_url = 'http://ulineapi.cms.cmbxm.mbcloud.com/v1/mchinlet/setwxconfig?mch_id='.$data['mch_id'];
		$url = '/v1/mchinlet/setwxconfig';
		$date = gmdate('D, d M Y H:i:s') . ' GMT';
		$mch_id = $data['mch_id'];
		unset($data['id']);
		unset($data['mch_id']);
		global $post_data;
		array_walk($data, 'text_form_data_splice');
		if (!empty($post_data)) {
			$post_data .= FORM_HYPHENS . FORM_BOUNDARY . FORM_HYPHENS;
		}
		$length = strlen($post_data);
		$key = C('ULINE_KEY');
		$signature = md5('POST&' . $url . '&' . $date . '&' . $length . '&' . $key) ;
		$req_headers= array(
				'Content-Type: multipart/form-data;boundary='.FORM_BOUNDARY,
				'Content-Length: ' . $length,
				'Date:' . $date,
				'Authorization:Uline 10000248968:' . $signature,
		);
		$result = curl_post($curl_url, $req_headers, $post_data);
		$res = json_decode($result['resp']);
		var_dump($res);die;
		if($res->code != 200) {
			return false;
		}
		if(isset($res->content->result) && $res->content->result == 'SUCCESS') {
			$map['mch_id'] = $mch_id;
			$map['mch_type'] = $data['mch_type'];
			$map['config_channel'] = $data['config_channel'];
			$map['config_key'] = 'jsapi_path';
			$jsapi_path = M('jinjian_mch_setting')->where($map)->find();
			if(empty($jsapi_path)) {
				$data['config_key'] = 'jsapi_path';
				$data['config_value'] = C('JSAPI_PATH');
				$res = M('jinjian_mch_setting')->add($data);
				if(FALSE !== $res) {
					$this->syncMchSetting($data);
				}
			}
			return true;
		} else {
			return false;
		}
	}

	public function addAppid(){
	    $config_channel = I("channel");
	    if($config_channel == "offline"){
	        $config_channel = "reg_offline";
        }else{
            $config_channel = "reg_online";
        }
	    $this->assign("config_channel",$config_channel);
        if(!$this->fans['uid']) exit;
        $cache = M('Jinjian')->where(['uid'=>$this->fans['uid']])->find();
        $m = M("jinjian_mch_setting");
        if(IS_POST) {
        	M()->startTrans();
            $data = I("post.");
            $data['mch_id'] = $cache['mchid'];
            $data["mch_type"] = "mch";
            $data["config_key"] = "sub_appid";
            $re = $m->add($data);
            if($re){
            	if(TRUE === $this->syncMchSetting($data)) {
            		M()->commit();
            		$this->success("添加成功！");
            	} else {
            		M()->rollback();
            		$this->error("添加失败！");
            	}          
            }else{
                $this->error("添加失败！");
            }
        } else {
        	$jinJian = M('Jinjian')->where(['uid'=>$this->fans['uid']])->find();
        	if($jinJian){
        		$this->assign("cache",$jinJian);
        	}
        	$this->display();
        }
    }
    public function delApp(){
        if (IS_POST){
            $id = I('post.id',0,'intval');
            if($id){
                $res = M('jinjian_mch_setting')->where(['id'=>$id])->delete();
                $this->ajaxReturn(['status'=>$res]);
            }
        }
    }
    //修改推荐关注微信公众号
    public function sappidEdit() {
    	$config_channel = I("channel");
    	if($config_channel == "offline"){
    		$config_channel = "reg_offline";
    	}else{
    		$config_channel = "reg_online";
    	}
    	$this->assign("config_channel",$config_channel);
    	if(!$this->fans['uid']) exit;
    	$cache = M('Jinjian')->where(['uid'=>$this->fans['uid']])->find();
    	$m = M("jinjian_mch_setting");
    	$map['mch_id'] = $cache['mchid'];
    	$map['mch_type'] = "mch";
    	$map['config_channel'] = $config_channel;
    	$map['config_key'] = "subscribe_appid";
    	$config = $m->where($map)->find();
    	if(IS_POST) {
    		$data = I("post.");
    		if($data['config_channel'] != 'reg_offline' && $data['config_channel'] != 'reg_online'){
    			$this->error("参数错误！");
    		}
    		M()->startTrans();
    		$data['mch_id'] = $cache['mchid'];
    		$data['mch_type'] = "mch";
    		$data['config_channel'] = $data['config_channel'];
    		$data['config_key'] = "subscribe_appid";
    		if(empty($config)) {
    			$re = $m->add($data);
    		} else {
    			$re = $m->where(['id' => $config['id']])->setField('config_value', $data['config_value']);
    		}
    		if(FALSE !== $re){
    			if(TRUE === $this->syncMchSetting($data)) {
    				M()->commit();
    				$this->success("修改成功！");
    			} else {
    				M()->rollback();
    				$this->error("修改失败！");
    			}
    		} else { 
    			$this->error("修改失败！");
    		}
    	} else {
    		$this->assign("config", $config);
    		$this->display();
    	}
    }
} 