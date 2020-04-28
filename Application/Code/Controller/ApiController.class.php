<?php
namespace Code\Controller;
use Think\Controller;
use Think\Page;

class ApiController extends Controller {
    /*
     * 获取地区
     */
    public function getRegion(){
    	$info = [
    			'status' => 1,
    			'msg' => 'fail',
    			'data' => null,
    	];
    	if(IS_AJAX){
	        $parent_id = I('get.id/s');    
	        if($parent_id) {
	        	try{
		        	$data = M('region')->where(["parent_id"=>$parent_id])->select();
		        	$info = [
		        			'status' => 1,
		        			'msg' => 'ok',
		        			'data' => $data,
		        	];
	        	} catch(\Exception $e) {
	        		
	        	}
	        }
    	}
    	$this->ajaxReturn($info);
    }
    
    public function ajaxGetIndustry(){
    	$info = [
    			'status' => 1,
    			'msg' => 'fail',
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
    
    public function ajaxGetBank() {
    	$q = I('q', '');
    	if($q) {
    		$m = M('Bank');
    		$data = array();
    		$map['bank_name'] = array('like', "%$q%");
    		$count = $m->where($map)->count();
    		if($count>0) {
    			$page = new Page($count,10);
    			$data = $m->where($map)
	    			->field('id,bank_name')
	    			->limit($page->firstRow.','.$page->listRows)
	    			->select();
    		}
    		$info = array(
    			'code' => 200,
    			'data' => $data,
    			'msg' => 'success',
    			'total_count' => $count,
    		);
    	} else {
    		$info = array(
    				'code' => 200,
    				'data' => $data,
    				'msg' => 'fail',
    				'total_count' => 0,
    		);
    	}
    	$this->ajaxReturn($info, '', JSON_UNESCAPED_UNICODE);
    }
}