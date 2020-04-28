<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-07-25
 * Time: 11:16
 */
namespace User\Controller;

/**
 * 商户进件申请控制器
 * Class MerchantController
 * @package User\Controller
 */
class MerchantController extends UserController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        //初始商户号
        $mch_id = $this->fans['memberid'].'-'.date('YmdHis');
        $this->assign('mch_id',$mch_id);
        $this->display();
    }
}