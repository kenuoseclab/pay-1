<?php
namespace Admin\Controller;
use Think\Page;
class PayForAnotherController extends BaseController{



	public function index(){
		$pfa_model = D('PayForAnother');
		$count = $pfa_model->count();
		$page = new Page($count, 15);
		$where = [];
		$lists = $pfa_model->where($where)->limit($page->firstRow . ',' . $page->listRows)->select();
		foreach($lists as $k => $v){
			$lists[$k]['updatetime'] = date('Y-m-d H:i:s', $v['updatetime']);
		}
		$this->assign('lists', $lists);
		$this->assign('page',$page->show());
		$this->display();
	}

	public function operationSupplier(){
		$id = I('get.id',0,'intval');

		if($id){
			$pfa_model = D('PayForAnother');
			$where = ['id'=>$id];
			$list = $pfa_model->where($where)->find();
			$this->assign('list', $list);
		}
		$this->display();
	}

	public function saveEditSupplier(){
		if(IS_POST){
			
			$pfa_model = D('PayForAnother');
			$data = $pfa_model->create();
			$pfa_model->startTrans();

			//判断是修改数据还是添加数据
			if(!$data['id']){
				$result = $pfa_model->add();
				$data['id'] = $result;
			}else{
				$result = $pfa_model->save();
			}
			
			//如果该代付通道是默认选择的通道，将其他通道改为不默认
			if($result){
				if($data['is_default'] == 1){
					if($pfa_model->editAllDefault($data['id'])){
						$pfa_model->commit();
						$this->ajaxReturn(['status'=>1]);
					}
				}else{
					$pfa_model->commit();
					$this->ajaxReturn(['status'=>1]);
				}
			}

			$pfa_model->rollback();
			$this->ajaxReturn(['status'=>0]);
		}
	}

	public function delSupplier(){
		$id = I('post.id','intval');
		if($id){
			$pfa_model = D('PayForAnother');
            $res = $pfa_model->where(['id'=>$id])->delete();
            $this->ajaxReturn(['status'=>$res]);
        }
	}

	//修改单一字段
	public function editStatus(){
		$id = I('post.id',0,'intval');
		$isopen = I('post.isopen','intval');
		if($id && $isopen!='' ){
			$pfa_model = D('PayForAnother');
            $reslut = $pfa_model->where(['id'=>$id])->save(['status'=>$isopen]);
            $this->ajaxReturn(['status'=>$reslut]);
        }
	}

	public function editDefault(){
		$id = I('post.id',0,'intval');
		$isopen = I('post.isopen',0,'intval');

		$pfa_model = D('PayForAnother');
		$pfa_model->startTrans();

		if($id && $isopen==1 )
			$reslut = $pfa_model->editAllDefault($id);
       	else
        	$reslut = $pfa_model->where(['id'=>$id])->save(['is_default'=>0]);
        

        if($reslut){
			$pfa_model->commit();
	        $this->ajaxReturn(['status'=>1]);
	    }else{
			$pfa_model->rollback();
			$this->ajaxReturn(['status'=>0]);
		}
	}

    /**
     * 扩展字段列表
     */
	public function extendFields(){
        $channel_id = I('get.id',0,'intval');
        $channel    = M('pay_for_another')->where(['id' => $channel_id])->find();
        $data   = M('pay_channel_extend_fields')->where(['channel_id' => $channel_id])->select();
        $this->assign('channel', $channel);
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 编辑扩展字段
     */
    public function editExtendFields()
    {
        if(IS_POST) {
            $data = I('post.', '');
            if(!$data['name'] || !$data['alias']) {
                $this->ajaxReturn(['status' => 0, 'msg' => '扩展字段名和别名不能为空']);
            }
            $count = M('pay_channel_extend_fields')->where(array('name'=>$data['name'], 'channel_id' => $data['channel_id'], 'id'=>array('neq', $data['id'])))->count();
            if($count>0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '该扩展字段名已存在']);
            }
            $res = M('pay_channel_extend_fields')->where(['id' => $data['id']])->save($data);
            if(FALSE !== $res) {
                $this->ajaxReturn(['status' => 1, 'msg'=>'编辑成功']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg'=>'编辑失败']);
            }
        } else {
            $id = I('id', 0, 'intval');
            if ($id) {
                $data = M('pay_channel_extend_fields')->where(['id' => $id])->find();
            }
            $this->assign('data', $data);
            $this->display('extendFieldsForm');
        }
    }

    /**
     * 新增扩展字段
     */
    public function addExtendFields()
    {
        if(IS_POST) {
            $data = I('post.', '');
            if(!$data['name'] || !$data['alias']) {
                $this->ajaxReturn(['status' => 0, 'msg' => '扩展字段名和别名不能为空']);
            }
            $count = M('pay_channel_extend_fields')->where(array('name'=>$data['name'], 'channel_id' => $data['channel_id']))->count();
            if($count>0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '该扩展字段名已存在']);
            }
            $data['code']  = M('pay_for_another')->where(array('id' => $data['channel_id']))->getField('code');
            $data['ctime'] = $data['etime'] = time();
            $res = M('pay_channel_extend_fields')->add($data);
            if(FALSE !== $res) {
                $this->ajaxReturn(['status' => 1, 'msg'=>'添加成功']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg'=>'添加失败']);
            }
        } else {
            $id = I('id', 0, 'intval');
            $data['channel_id'] = $id;
            $this->assign('data', $data);
            $this->display('extendFieldsForm');
        }
    }

    //删除扩展字段
    public function delExtendFields()
    {
        $id = I('id', 0, 'intval');
        if ($id) {
            $res = M('pay_channel_extend_fields')->where(['id' => $id])->delete();
            $this->ajaxReturn(['status' => $res]);
        }
    }
}
