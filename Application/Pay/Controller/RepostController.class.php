<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-09-12
 * Time: 14:20
 */
namespace Pay\Controller;

class RepostController extends PayController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        echo json_encode(['code'=>500,'msg'=>'走错道了哦.']);
    }

    /**
     *  补单机制
     */
    public function postUrl()
    {
        echo "ok";
        //缓存
        $configs = C('PLANNING');
        $nums = $configs['postnum'] ? $configs['postnum'] : 5;
        $maps['pay_status'] = array('eq',1);
        $maps['num'] = array('lt',$nums);
        $maps['last_reissue_time'] = array('lt', time()-10);//距离上次补发至少10秒
        $list = M('Order')->where($maps)->field('id,pay_orderid,pay_ytongdao')->order('id asc')->limit(50)->select();
        if($list){
            foreach ($list as $item){
                $this->EditMoney($item['pay_orderid'],$item['pay_ytongdao'],0);
                M('Order')->where(['id'=>$item['id']])->save(['num' => array('exp','num+1'), 'last_reissue_time' => time()]);
            }
        }
    }
}