<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Home\Controller;
use Boris\Config;

/**
 * 网站入口控制器
 * Class IndexController
 * @package Home\Controller
 * @author 22691513@qq.com
 */
class IndexController extends BaseController
{


    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->display();
    }

    public function rate()
    {
        $this->display();
    }

    public function Jkxz()
    {
        $this->display();
    }


    public function download()
    {
        $this->display();
    }

    public function contact()
    {
        $this->display();
    }

    public function vhash()
    {
        echo C('vhash');
    }
	
	 /**
     * 生成二维码
     */
    public function generateQrcode()
    {
        $str     =html_entity_decode(urldecode(I('str','')));
        if(!$str){
            exit('请输入要生成二维码的字符串！');
        }
        import("Vendor.phpqrcode.phpqrcode",'',".php");
        header('Content-type: image/png');
        \QRcode::png($str, false, "L", 10, 1);
        die;
    }

    public function test(){

        $map['userid'] = 158;
        $map['datetime'] = ['between',['2018-06-18 00:00:00','2018-06-23 23:59:59']];
        $list = M('moneychange')->where($map)->order('datetime DESC')->select();
        $ymoney = '';
        foreach($list as $k => $v) {
            if($ymoney!='') {
                if ($ymoney != $v['gmoney'] && $v['lx'] == 6) {
                    echo 'ID：' . $v['id'] . '<br>';
                }
            }
            $ymoney = $v['ymoney'];

        }
        echo 'completed';
    }

    public function test2() {
        $map['pay_status'] = ['in','1,2'];
        //$map['pay_successdate'] = ['between',[1530460800,1530547199]];
        $list = M('Order')->where($map)->select();
		$count = 0;
        foreach($list as $k => $v) {
            $profit = $v['pay_poundage'] - $v['cost'];
            $yj = M('moneychange')->where(['lx'=>9, 'transid'=>$v['pay_orderid']])->sum('money');
            if($profit<0) {
				$count++;
                echo $v['pay_orderid'].'<br>';
            }
        }
        echo 'ok';die;
    }
public function test3(){
    echo $this->getDateBalance('158', '2018-07-14');
}
    /*
  * 根据日期获取用户期初余额
  */
    private function getDateBalance($userid, $date) {

        $log = M('Moneychange')->where(['userid'=>$userid, 'datetime'=>array('elt', $date), 't'=>['neq', 1], 'lx' => ['not in', '3,4']])->order('datetime DESC,id DESC')->find();
        if(empty($log)) {
            $money = 0;
        } else {
            $yesterdayTime = date("Y-m-d",strtotime($date)-1);
            $yesterdayRedAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$userid,'date'=>$yesterdayTime, 'ctime'=>['gt', strtotime($log['datetime'])]])->sum('money');
            $yesterdayRedReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$userid,'date'=>$yesterdayTime, 'ctime'=>['gt', strtotime($log['datetime'])]])->sum('money');
            $money = $log['gmoney'] + $yesterdayRedAddSum - $yesterdayRedReduceSum + 0;
        }
        return $money;
    }

    public function test4() {
        $map['pay_status'] = ['in','1,2'];
        $map['pay_memberid'] = 10020;
        $list = M('Order')->where($map)->select();
        foreach($list as $k => $v) {
            $log = M('Moneychange')->where(['lx'=>1,'userid'=>20,'transid'=>$v['pay_orderid']])->find();
            if(empty($log)) {
                echo '异常流水：'.$v['pay_orderid'].'<br>';
            } else {
                if($log['money'] != $v['pay_actualamount']) {
                    echo '金额异常：'.$v[pay_orderid].', 订单金额：'.$v['pay_actualamount'].',流水金额：'.$log['money'].'<br>';
                }
            }
        }
        echo 'compeleted';
    }
}
