<?php
namespace Home\Controller;

class EmptyController extends BaseController
{

    public function index()
    {
        switch (strtolower(CONTROLLER_NAME)) {
            case strtolower(C("LOGINNAME")):
                $userid = session("admin_auth_sign");
                if (empty($userid)) {
                    $this->display("Admin@Login/index");
                } else {
                    $this->success('正在登录...!',strtolower(C('HOUTAINAME')),0);
                }
                break;
            default:
                $this->display("Index/sls");
        }
    }

    public function _empty()
    {
        $controllername = CONTROLLER_NAME;
        if ($controllername == "Activate") {
            $Activate = explode('_', $_SERVER['PATH_INFO'])[1];
            $User = M("Member");
            $_userdata = $User->where(["activate" => $Activate])->find();
            if ($_userdata['username']) {
                if($_userdata['status']) {
                    $this->success('您已激活！');
                }
                $activatedatetime = date("Y-m-d H:i:s");
                $User->where(['activate' => $Activate])->save(['status' => 1, 'activatedatetime' => $activatedatetime]);
                $this->success('激活成功!',U('User/Login'));
            } else {
                $this->error('账号有误，激活失败！','index.html');
            }

        } else {
            $this->error('非法操作!');
        }
    }
}
?>
