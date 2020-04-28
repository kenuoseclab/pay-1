<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-13
 * Time: 14:42
 */

namespace Admin\Controller;

class MenuController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $menu_model = D('AdminMenu');
        $this->menu_model = $menu_model;
    }

    //列表
    public function index()
    {
        $menu = $this->menu_model->selectAllMenu();
        $menu = get_column($menu);
        foreach ($menu as $key=>$item){
            list($controller,$action) = explode('/',$item['menu_name']);
            $menu[$key]['controller'] = $controller;
            $menu[$key]['action'] = $action;
        }
        $this->assign('menu',$menu);
        $this->display();
    }
    
    //添加菜单
    public function addMenu()
    {
        $pid = I('get.pid',0,'intval');
        if(IS_POST){
            $pid = I('post.pid',0,'intval');
            $rows = I('post.m/a');
            if($pid){
                //添加的菜单是否是三级菜单
                $rows['is_menu'] = $this->menu_model->isSecondaryMenu($pid) ? 0 : 1;
                $rows['pid'] = $pid;
            }else{
                $rows['is_menu'] = 1;
            }
            if($this->menu_model->isExistOpt($rows['controller'], $rows['action'])){
                $this->ajaxReturn(['status'=>0,'msg'=>"该菜单已存在"]);
            }
            $res = $this->menu_model->addAdminMenu($rows);
            $this->ajaxReturn(['status'=>$res]);
        }else{
            $this->assign('id',$pid);
            $this->display();
        }
    }

    /**
     * @description:更新菜单
     */
    public function editMenu()
    {
        if(IS_POST){
            $data = I('post.');
            if($this->menu_model->isExistOpt($data['controller'], $data['action'],$data['id'])){
                $this->ajaxerror("该菜单已存在");
            }
            $result = $this->menu_model->editAdminMenu($data);
            if($result !== false){
                $this->ajaxReturn(['status'=>1,'msg'=>'更新成功']);
            }else{
                $this->ajaxReturn(['status'=>1,'msg'=>'更新失败']);
            }
        }else{
            $id = I('get.id','','intval');

            $menu_info = $this->menu_model->selectMenuById($id);

            $this->assign('menu_info',$menu_info);
            $this->assign('opt',explode('/',$menu_info['menu_name']));
            $this->display();
        }
    }

    /**
     * @description:删除菜单
     */
    public function delMenu()
    {
        $id = I('post.id',0,'intval');
        if($this->menu_model->isExistSonMenu($id)){
            $this->ajaxReturn(['status'=>0,'msg'=>'存在子菜单未删除']);
        }
        $res = M('AuthRule')->where(['id'=>$id])->delete();
        $this->ajaxReturn(['status'=>$res]);
    }

    /**
     * @description:查看三级操作
     */
    public function viewOpt()
    {
        $id = I('get.id',0,'intval');

        $_opts = $this->menu_model->selectOpt($id);

        if(!count($_opts)){
            $this->ajaxError('该菜单还未添加任何操作');
        }

        $this->assign('opts',$_opts);
        $this->display();
    }
}