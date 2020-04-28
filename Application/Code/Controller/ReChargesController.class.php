<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Code\Controller;
use Think\Verify;
use Think\Page;

/**
 * 用户中心首页控制器
 * Class IndexController
 * @package User\Controller
 */

class ReChargesController extends UserController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 首页
     */
    public function index()
    {
        $module = strtolower(trim(__MODULE__, '/'));
        $module = trim($module, './');
        $loginout = U($module . "/Login/loginout");
        $this->assign('loginout', $loginout);
        $this->display();
    }

    
}