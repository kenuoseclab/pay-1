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

class IndexController extends UserController
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

    public function main()
    {
        $firstday = date('Y-m-01', time());
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));

        //成交金额
        $sql = "SELECT SUM( pay_actualamount ) AS total, FROM_UNIXTIME( pay_successdate,  '%Y-%m-%d' ) AS DATETIME
FROM pay_order WHERE pay_successdate >= UNIX_TIMESTAMP(  '".$firstday."' ) AND pay_successdate < UNIX_TIMESTAMP(  '".
            $lastday."' ) AND pay_status>=1 AND owner_id=".($this->fans['uid'])."  GROUP BY DATETIME";
        $ordertotal = M('Order')->query($sql);

        //成交订单数
        $sql = "SELECT COUNT( id ) AS num, FROM_UNIXTIME( pay_successdate,  '%Y-%m-%d' ) AS DATETIME
FROM pay_order WHERE pay_successdate >= UNIX_TIMESTAMP(  '".$firstday."' ) AND pay_successdate < UNIX_TIMESTAMP(  '".
            $lastday."' ) AND pay_status>=1 AND owner_id=".($this->fans['uid'])."  GROUP BY DATETIME";
        $ordernum = M('Order')->query($sql);
        foreach ($ordernum as $key=>$item){
            $category[] = date('Ymd',strtotime($item['datetime']));
            $dataone[] = $item['num'];
            $datatwo[] = $ordertotal[$key]['total'];
        }
   		$tkconfig = M('Tikuanconfig')->where(['userid' => $this->fans['uid']])->find();
		if (!$tkconfig || $tkconfig['tkzt'] != 1) {
            $tkconfig = M('Tikuanconfig')->where(['issystem' => 1])->find();
        }
        $this->assign('tkconfig',$tkconfig);
        $this->assign('category','['.implode(',',$category).']');
        $this->assign('dataone','['.implode(',',$dataone).']');
        $this->assign('datatwo','['.implode(',',$datatwo).']');
        //文章默认最新2条
        $Article = M("Article");
        if($this->fans['groupid'] == 4) {
            $gglist = $Article->where(['status'=> 1, 'groupid'=>['in','0,1']])->limit(2)->order("id desc")->select();
        } else {
            $gglist = $Article->where(['status'=> 1, 'groupid'=>['in','0,2']])->limit(2)->order("id desc")->select();
        }
        $this->assign("gglist", $gglist);
        //获取最近两次登录记录
        $loginlog = M("Loginrecord")->where(['userid' => $this->fans['uid']])->order('id desc')->limit(2)->select();
        $lastlogin = '';
        if(isset($loginlog[1])) {
            $lastlogin = $loginlog[1];
        }
        if (trim($this->fans['login_ip'])) {
            $ipItem = explode("\r\n", $this->fans['login_ip']);
        }
        //今日总订单数
        $todayBegin = date('Y-m-d').' 00:00:00';
        $todyEnd = date('Y-m-d').' 23:59:59';
        $stat['todayordercount'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]]])->count();
        //今日已付订单数
        $stat['todayorderpaidcount'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->count();
        //今日未付订单数
        $stat['todayordernopaidcount'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>0])->count();
        //今日提交金额
        $stat['todayordersum'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->sum('pay_amount');
        //码商佣金
        $stat['coderatemoney'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->sum('code_rate_money');
        //今日实付金额
        $stat['todayorderactualsum'] = M('Order')->where(['owner_id'=>$this->fans['uid'],'pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->sum('pay_actualamount');
        //投诉保证金
        $stat['complaints_deposit'] = M('complaints_deposit')->where(['user_id'=>$this->fans['uid'], 'status'=>0])->sum('freeze_money');
        //今日收入
        $yj = M('moneychange')->where(['userid'=>$this->fans['uid'],'datetime'=>['between', [$todayBegin, $todyEnd]],'lx'=>9])->sum('money');
        $stat['today_income'] = $stat['todayorderactualsum'] + $yj;
        foreach($stat as $k => $v) {
            $stat[$k] = $v+0;
        }
        $this->assign('stat', $stat);
        $this->assign('ipItem',$ipItem);
        $this->assign('lastlogin', $lastlogin);
        $this->assign('user', $this->fans);
        $this->display();
    }

    public function showcontent()
    {
        $id = I("get.id", 0, 'intval');
        if($id<=0) {
            $this->error('参数错误');
        }
        $Article = M("Article");
        if($this->fans['groupid'] == 4) {
            $find = $Article->where(['id'=>$id,'status'=> 1,'groupid'=>['in','0,1']])->find();
        } else {
            $find = $Article->where(['id'=>$id,'status'=> 1,'groupid'=>['in','0,2']])->find();
        }
        $this->assign("find", $find);
        $this->display();
    }

    public function gonggao()
    {
        $where['status'] = 1;
        if($this->fans['groupid'] == 4) {
            $where['groupid'] = ['in','0,1'];
            $count = M('Article')->where($where)->count();
            $page           = new Page($count, 5);
            $list = M('Article')->where($where)->limit($page->firstRow . ',' . $page->listRows)->order("id desc")->select();
        } else {
            $where['groupid'] = ['in','0,2'];
            $count = M('Article')->where($where)->count();
            $page           = new Page($count, 5);
            $list = M('Article')->where($where)->limit($page->firstRow . ',' . $page->listRows)->order("id desc")->select();
        }

        $this->assign("list", $list);
        $this->assign('page', $page->show());
        $this->display();
    }

    public function google()
    {
        if(IS_POST) {
            $google_secret_key = M('Member')->where(array('id'=> $this->fans['uid']))->getField('google_secret_key');
            if($google_secret_key == '') {
                $this->error("您未绑定谷歌身份验证器");
            }
            $res = check_auth_error($this->fans['uid'], 4);
            if(!$res['status']) {
                $this->ajaxReturn(['status' => 0, 'msg' => $res['msg']]);
            }
            $code = I('code');
            $ga = new \Org\Util\GoogleAuthenticator();
            if(false === $ga->verifyCode($google_secret_key, $code, C('google_discrepancy'))) {
                log_auth_error($this->fans['uid'],4);
                $this->error("谷歌安全码错误");
            } else {
                clear_auth_error($this->fans['uid'],4);
                session('code_google_auth', $code);
                $this->success("验证通过，正在进入商户中心...", U('Index/index'));
            }
        } else {
            $this->display();
        }
    }
}