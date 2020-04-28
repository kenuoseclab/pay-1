<?php
namespace Admin\Controller;

use Think\Page;

class TemplateController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $m_Template = M('Template');
        $count      = $m_Template->count();
        $Page       = new Page($count, 15);
        $list       = $m_Template->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->display();
    }

    public function editDefalut()
    {
        if (IS_AJAX && IS_POST) {
            $id        = I('post.id', '');
            $isDefault = I('post.is_default', '0');
            M()->startTrans();
            $m_Template = M('Template');
            $res        = $m_Template->where(['is_default' => 1])->save(['is_default' => 0]);
            if ($res !== false) {
                $where  = $isDefault ? ['id' => $id] : ['theme' => 'default'];
                $result = $m_Template->where($where)->save(['is_default' => 1]);
                if ($result !== false) {
                    M()->commit();
                    $this->ajaxReturn(['status' => 1]);
                }
            }
            M()->rollback();
            $this->ajaxReturn(['status' => 0]);
        }
    }

    public function addSave()
    {
        if (IS_POST && IS_AJAX) {
            M()->startTrans();
            $m_Template = M('Template');
            $data       = $m_Template->create();

            if ($data['is_default']) {
                $res = $m_Template->where(['is_default' => 1])->save(['is_default' => 0]);
            } else {
                if ($data['id']) {
                    $isDefault = $m_Template->where(['id' => $data['id']])->getField('is_default');
                    if ($isDefault) {
                        $res = $m_Template->where(['theme' => 'default'])->save(['is_default' => 1]);
                    }
                }
            }
            if ($res === false) {
                M()->rollback();
                $this->ajaxReturn(['status' => 0]);
            }

            $data['update_time'] = time();
            if ($data['id']) {
                $data['add_time'] = time();
                $result           = $m_Template->where(['id' => $data['id']])->save($data);
            } else {
                $result              = $m_Template->add($data);
            }
            if ($result === false) {
                M()->rollback();
                $this->ajaxReturn(['status' => 0]);
            }

            $this->ajaxReturn(['status' => 1]);
            M()->commit();
        } else {
            $id   = I('get.id', '0');
            $info = M('Template')->where(['id' => $id])->find();
            $this->assign('info', $info);
            $this->display();
        }
    }

    public function del()
    {
        if (IS_POST && IS_AJAX) {
            M()->startTrans();
            $id         = I('post.id', '0');
            $m_Template = M('Template');
            $info       = $m_Template->where(['id' => $id])->find();
            if ($info) {
                if ($info['is_default']) {
                    $res = $m_Template->where(['theme' => 'default'])->save(['is_default' => 1]);
                    if ($res === false) {
                        M()->rollback();
                        $this->ajaxReturn(['status' => 0]);
                    }
                }
                $result = $m_Template->where(['id' => $id])->delete();
                if ($result === false) {
                    M()->rollback();
                    $this->ajaxReturn(['status' => 0]);
                }
            }
            $this->ajaxReturn(['status' => 1]);
            M()->commit();

        }

    }

}
