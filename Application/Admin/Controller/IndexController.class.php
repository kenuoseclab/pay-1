<?php
namespace Admin\Controller;

class IndexController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    //首页
    public function index()
    {
        $Websiteconfig = D("Websiteconfig");
        $withdraw      = $Websiteconfig->getField("withdraw");
        $this->assign("withdraw", $withdraw);
        $this->display();
    }

    //main
     public function main()
    {
        //日报
        $_data['today'] = date('Y年m月d日');
        $_data['month'] = date('Y年m月');

        $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $endThismonth   = mktime(23, 59, 59, date('m'), date('t'), date('Y'));

        //实时统计
        $orderWhere = [
            'pay_status'      => ['between', [1,2]],
            'pay_successdate' => [
                'between',
                [
                    strtotime('today'),
                    strtotime('tomorrow'),
                ],
            ],
        ];
        $ddata = M('Order')
            ->field([
                'sum(`pay_amount`) amount',
                'sum(`pay_poundage`) rate',
                'sum(`pay_actualamount`) total',
            ])->where($orderWhere)
            ->find();

        $ddata['num'] = M('Order')->where($orderWhere)->count();

        //7天统计
        $lastweek = time() - 7 * 86400;
        $sql      = "select COUNT(id) as num,SUM(pay_amount) AS amount,SUM(pay_poundage) AS rate,SUM(pay_actualamount) AS total from pay_order where  1=1 and pay_status>=1 and DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= date(FROM_UNIXTIME(pay_successdate,'%Y-%m-%d')) and pay_successdate>=$lastweek; ";
        $wdata    = M('Order')->query($sql);

        //按月统计
        $lastyear = strtotime(date('Y-1-1'));
        $sql      = "select FROM_UNIXTIME(pay_successdate,'%Y年-%m月') AS month,SUM(pay_amount) AS amount,SUM(pay_poundage) AS rate,SUM(pay_actualamount) AS total from pay_order where  1=1 and pay_status>=1 and pay_successdate>=$lastyear GROUP BY month;  ";
        $_mdata   = M('Order')->query($sql);
        $mdata    = [];
        foreach ($_mdata as $item) {
            $mdata['amount'][] = $item['amount'] ? $item['amount'] : 0;
            $mdata['mdate'][]  = "'" . $item['month'] . "'";
            $mdata['total'][]  = $item['total'] ? $item['total'] : 0;
            $mdata['rate'][]   = $item['rate'] ? $item['rate'] : 0;
        }
        //平台总入金
        $stat['allordersum'] = M('Order')->where(['pay_status'=>['in', '1,2']])->sum('pay_amount');
        //商户总分成
        $stat['allmemberprofit'] = M('moneychange')->where(['lx'=>9])->sum('money');
        //平台总分成
        $all_income_profit = M('Order')->where(['pay_status' => ['in', '1,2']])->sum('pay_poundage');
        $tkmoney1 = M('tklist')->where(['status'=>2])->sum('tkmoney');
        $tkmoney2 = M('wttklist')->where(['status'=>2])->sum('tkmoney');
        $money1 = M('tklist')->where(['status'=>2])->sum('money');
        $money2 = M('wttklist')->where(['status'=>2])->sum('money');
        $pay_profit = $tkmoney1 + $tkmoney2 - $money1 - $money2; //出金利润
        $all_order_cost = M('Order')->where(['pay_status' => ['in', '1,2']])->sum('cost');
        $all_pay_cost = M('wttklist')->where(['status' => 2])->sum('cost');
        $stat['allplatformincome'] = $all_income_profit + $pay_profit - $all_order_cost - $all_pay_cost - $stat['allmemberprofit'];
        $todayBegin = date('Y-m-d').' 00:00:00';
        $todyEnd = date('Y-m-d').' 23:59:59';
        //今日平台总入金
        $stat['todayordersum'] = M('Order')->where(['pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>['in', '1,2']])->sum('pay_amount');
        //今日商户总分成
        $stat['todaymemberprofit'] = M('moneychange')->where(['datetime'=>['between', [$todayBegin, $todyEnd]],'lx'=>9])->sum('money');
        //今日平台总分成
        $income_profit = M('Order')->where(['pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],'pay_status' => ['in', '1,2']])->sum('pay_poundage');
        $tkmoney1 = M('tklist')->where(['sqdatetime'=>['between', [$todayBegin, $todyEnd]],'status'=>2])->sum('tkmoney');
        $tkmoney2 = M('wttklist')->where([ 'sqdatetime'=>['between', [$todayBegin, $todyEnd]],'status'=>2])->sum('tkmoney');
        $money1 = M('tklist')->where([ 'sqdatetime'=>['between', [$todayBegin, $todyEnd]],'status'=>2])->sum('money');
        $money2 = M('wttklist')->where([ 'sqdatetime'=>['between', [$todayBegin, $todyEnd]],'status'=>2])->sum('money');
        $pay_profit = $tkmoney1 + $tkmoney2 - $money1 - $money2; //出金利润
        $order_cost = M('Order')->where(['pay_successdate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]],'pay_status' => ['in', '1,2']])->sum('cost');
        $pay_cost = M('wttklist')->where(['sqdatetime'=>['between', [$todayBegin, $todyEnd]], 'status' => 2])->sum('cost');
        $stat['todayplatformincome'] = $income_profit + $pay_profit - $order_cost - $pay_cost - $stat['todaymemberprofit'];
        // $chan = M('moneychange')->sum('money');
        // $stat['ptzsy'] = ($stat['allordersum'] - $chan);
        foreach($stat as $k => $v) {
            $stat[$k] = $v+0;
           $stat[$k] = number_format($stat[$k],2,'.','');
        }
        $this->assign('stat', $stat);
        $this->assign('ddata', $ddata);
        $this->assign('wdata', $wdata[0]);
        $this->assign('mdata', $mdata);
        $this->display();
    }

    /**
     * 清除缓存
     */
    public function clearCache()
    {
        $groupid = session('admin_auth.groupid');
        if ($groupid == 1) {
            $dir = RUNTIME_PATH;
            $this->delCache($dir);
            $this->success('缓存清除成功！');
        } else {
            $this->error('只有总管理员能操作！');
        }
    }

    /**
     * 删除缓存目录
     * @param $dirname
     * @return bool
     */
    protected function delCache($dirname)
    {
        $result = false;
        if (!is_dir($dirname)) {
            echo " $dirname is not a dir!";
            exit(0);
        }
        $handle = opendir($dirname); //打开目录
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                //排除"."和"."
                $dir = $dirname . DIRECTORY_SEPARATOR . $file;
                is_dir($dir) ? self::delCache($dir) : unlink($dir);
            }
        }
        closedir($handle);
        $result = rmdir($dirname) ? true : false;
        return $result;
    }

}
