<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-02
 * Time: 23:01
 */

namespace Admin\Controller;

use Think\Auth;
use Think\Controller;

/**
 * 后台入口控制器
 * Class BaseController
 * @package Admin\Controller
 */

class BaseController extends Controller{

    const LENGTH = 4; //验证码的长度
    const EXPIRE = 300; //过期时间

    /**
     * 初始化控制器
     * BaseController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // 获取当前用户ID
        if(defined('UID')) return ;
        define("UID",is_login());
        if( !UID ){// 还没登录 跳转到登录页面
            $this->redirect('Login/index');
        }
        // 是否是超级管理员
        define('IS_ROOT',   is_rootAdministrator());
        if(!IS_ROOT && C('ADMIN_ALLOW_IP')){
            // 检查IP地址访问
            if(!in_array(get_client_ip(),explode(',',C('ADMIN_ALLOW_IP')))){
                $this->error('403:禁止访问');
            }
        }
        $siteconfig = M("Websiteconfig")->find();
        if(session('admin_auth') && !session('google_auth') && $siteconfig['google_auth']) {
            if(!(CONTROLLER_NAME == 'Auth' && ACTION_NAME == 'google')
                &&!(CONTROLLER_NAME == 'Login' && ACTION_NAME == 'index')
                &&!(CONTROLLER_NAME == 'Login' && ACTION_NAME == 'loginout')
                &&!(CONTROLLER_NAME == 'Login' && ACTION_NAME == 'verifycode')
                &&!(CONTROLLER_NAME == 'Auth' && ACTION_NAME == 'unbindGoogle')
                &&!(CONTROLLER_NAME == 'Auth' && ACTION_NAME == 'unbindGoogleSend')
            ) {
                if(IS_AJAX){
                    $this->error('请进行谷歌身份验证', 'Auth/google');
                }else{
                    $this->redirect('Auth/google');
                }
            }
        }
        $user_info = session('admin_auth');
        if($siteconfig['admin_alone_login']) {//只允许同时一处登录
            $session_random = M('Admin')->where(['id' => $user_info['uid']])->getField('session_random');
            if($session_random && $session_random !=  $user_info['session_random']) {
                session('admin_auth', null);
                session('google_auth', null);
                session("admin_auth_sign", null);
                $this->error('您的账号在别处登录，如非本人操作，请立即修改登录密码！','/' . C("LOGINNAME"));
            }
        }
        //权限检查
        $name = CONTROLLER_NAME . '/' . ACTION_NAME;
        if(CONTROLLER_NAME != 'Login' && !IS_ROOT&&$name!="System/editPassword" && $name!="Auth/google"){
            $auth = new Auth();
            $auth_result = $auth->check($name, $user_info['uid']);
            if($auth_result === false){
                if(IS_AJAX){
                    $this->error('没有权限!');
                }else{
                    $this->error('没有权限!');
                }
            }
        }

        $groupIds = M('MemberAgentCate')->select();
        $tempGroupId = [];
        foreach ($groupIds as $k => $v) {
            
           $tempGroupId[$v['id']] = $v['cate_name'];
        }
        
        $this->groupId = $tempGroupId;
     
        //获取用户的代理等级信息
        $this->assign('groupId',$this->groupId);
        //左侧菜单栏
        $admin_auth_group_access_model = D('AdminAuthGroupAccess');
        $navmenus = $admin_auth_group_access_model->getUserRules($user_info['uid']);
        $this->assign('navmenus', $navmenus);
        $this->_site = ((is_https()) ? 'https' : 'http') . '://' . C("DOMAIN") . '/';
        $this->assign('siteurl',$this->_site);
        $this->assign('sitename',C('WEB_TITLE'));
        $this->assign('member',$user_info);
        $this->assign('installpwd',md5('adminadmin'.C('DATA_AUTH_KEY')));
        $this->assign('model',C('HOUTAINAME')?C('HOUTAINAME'):MODULE_NAME);
    }

    protected function checkSessionTime($callIndex, $randNum)
    {
        $timeSession = 'send.' . $callIndex . '|' . $randNum;
        $time        = session($timeSession);
        return time() - $time < self::EXPIRE;
    }

    /**
     * 发送验证码
     * @param  [type] $callInde 要调用的模板代码
     * @param  [type] $mobile 手机号码
     * @param  [type] $product 模板的$product参数
     * @return [type]          [description]
     */
    protected function send($callIndex, $mobile, $product)
    {

        //验证码的长度
        $length = self::LENGTH;
        //生成随机验证码
        $num = range(0, 9);
        shuffle($num);
        $randNum      = substr(implode('', $num), 0, $length);
        $templeData   = getSmsTemplateCode($callIndex);
        $templateCode = $templeData['template_code'];
        //记录验证码
        $sessionCode = 'send.' . $callIndex;
        $timeSession = 'send.' . $callIndex . '|' . $randNum;
        session($timeSession, time()); //存入当前生成验证码的时间
        session($sessionCode, $randNum);

        if ($callIndex == 'loginWarning') {
            $templeContent = ['time' => time()];
        } else {
            //查看模板变量的个数，如果是1个是新模板，2个是旧模板
            $count = substr_count($templeData['template_content'], '$');
            //模板参数
         $templeContent = $count >= 2 ? ['code' => $randNum, 'opration' => $product] : ['code' => $randNum];
        }

        //发送验证码
        $res = sendSMS($mobile, $templateCode, $templeContent);
        if ($res === true) {
            return ['code' => 1, 'message' => '发送成功', 'randNum'=>$randNum];
        } else {
            return ['code' => 0, 'message' => $res, 'randNum'=>$randNum];
        }
    }
}