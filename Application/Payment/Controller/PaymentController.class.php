<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Payment\Controller;

/**
 * 用户中心首页控制器
 * Class IndexController
 * @package User\Controller
 */
use Think\Controller;

class PaymentController extends Controller
{
	protected $verify_data_ = [
				'code'=>'请选择代付方式！',
				'id'=>'请选择代付订单！', 
				'opt' => '操作方式错误！',
			];



	public function __construct(){
	    parent::__construct();
	}

	protected function findPaymentType($code='default'){
		$where['status'] = 1;
		if($code == 'default'){
			$where['is_default'] = 1;
		}else{
			$where['id'] = $code;
		}
		$list = M('PayForAnother')->where($where)->find();
		$list || showError('支付方式错误');
		return $list;
	}

	protected function selectOrder($where){
		
		$lists = M('Wttklist')->where($where)->select();
		$lists || showError('无该代付订单或订单当前状态不允许该操作！');
		foreach($lists as $k => $v){
			$lists[$k]['additional'] = json_decode($v['additional'],true);
		}
		return $lists;
	}



	protected function checkMoney($uid,$money){
		$where = ['id' => $uid];
		$balance = M('Member')->where($where)->getField('balance');
		$balance < $money && showError('支付金额错误'); 
	}

	protected function handle($id, $status=1, $return){
	    
	    //处理成功返回的数据
        $data = array();
        if($status == 1){
           $data['status'] = 1;
           $data['memo'] = '申请成功！';
        }else if ($status == 2) {
           $data['status'] = 2;
           $data['cldatetime'] = date('Y-m-d H:i:s', time());
           $data['memo'] = '代付成功';
        }else if($status == 3){
            $data['status'] = 4;
			$data['memo'] = isset($return['memo'])?$return['memo']:'代付失败！';
        }
        if(in_array($status, [1,2,3])){
        	$data = array_merge($data, $return);
        	$where = ['id'=>$id];
        	M('Wttklist')->where($where)->save($data);
        }
   
	}

}