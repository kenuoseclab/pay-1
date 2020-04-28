<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-01
 * Time: 22:15
 */
namespace Home\Controller;

use Think\Controller;

class BaseController extends Controller
{

    protected $customAssignParams = [

    ];

    public function __construct()
    {
        parent::__construct();

        if (C('LOGINNAME') != trim(__SELF__, '/')) {
            //查询默认的模板，修改前段的模板
            $theme       = M('Template')->where(['is_default' => 1])->getField('theme');
            $this->theme = $theme ? $theme : 'defalut';
            C('DEFAULT_THEME', $this->theme);
            $style = '/Public/theme/' . $this->theme . '/';

            //获取系统配置
            $this->siteconfig = M("Websiteconfig")->find();

            //将基本的参数插入模板中
            $this->assign([
                'style'                  => $style, //样式目录

                'siteconfig'             => $this->siteconfig, //系统配置
                'sitename'               => $this->siteconfig['websitename'],
                'logo'                   => $this->siteconfig['logo'],
				'qq'               => $this->siteconfig['qq'],

                'user_login'             => U('Home/Index/userLogin'), //用户登录页面
                'user_checklogin'        => '/User_Login_checklogin', //用户登录检测地址

                'agent_login'            => U('Home/Index/agentLogin'), //代理登录页面
                'agent_checklogin'       => '/Agent_Login_checklogin', //代理登录检测地址

                'code_login'            => U('Home/Index/codeLogin'), //代理登录页面
                'code_checklogin'       => '/Code_Login_checklogin', //代理登录检测地址

                'register'               => U('Home/Index/register'), //用户或代理注册检测地址
                'register_checkRegister' => U('Agent/Login/checkRegister'), //用户或代理注册检测地址
                'agentregister_checkRegister' => U('Agent/Login/checkRegister'), //代理注册检测地址
                'checkuser'              => U('User/Login/checkuser'), //检测是已存在商户
                'checkemail'             => U('User/Login/checkemail'), //邮箱检测地址
                'checkinvitecode'        => U('User/Login/checkinvitecode'), //邀请码检测地址

                'verifycode'             => U('Agent/Login/verifycode'), //验证码
				
				'products'               => U('Home/Index/products'), //产品介绍 
                'sdk'               => U('Home/Index/sdk'), //sdk下载页 
               'document'               => U('Home/Index/document'), //api开发文档 
                'demo'                   => U('Home/Index/demo'), //demo地址
                'introduce'              => U('Home/Index/introduce'), //产品介绍地址

                'faq'                    => U('Home/Index/faq'), //常见问题
                'safe'                   => U('Home/Index/safe'), //安全无忧
                'contact'                => U('Home/Index/contact'), //联系我们
                'about'                => U('Home/Index/about'), //关于我们
                'download_demo'          => 'Uploads/demo.zip',//demo下载地址
            ]);
            //将开发者自定义参数插入模板中
            $this->assign($this->customAssignParams);
        }
    }
}
