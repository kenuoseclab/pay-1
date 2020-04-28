<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-04-02
 * Time: 23:01
 */

namespace Admin\Controller;

use Think\Page;

/**
 * 统计控制器
 * Class StatisticsController
 * @package Admin\Controller
 */
class StatisticsController extends BaseController
{
    const TMT = 7776000; //三个月的总秒数
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 订单列表
     */
    public function index()
    {
        //通道
        $tongdaolist = M("Channel")->field('id,code,title')->select();
        $this->assign("tongdaolist", $tongdaolist);

        $where = array(
            'pay_status' => ['gt', 1],
        );
        $memberid = I("request.memberid");
        if ($memberid) {
            $where['O.pay_memberid'] = array('eq', $memberid);
            $profitMap['userid'] = $profitSumMap['userid']= $memberid-10000;
        }
        $orderid = I("request.orderid");
        if ($orderid) {
            $where['O.pay_orderid'] = $orderid;
            $profitMap['transid'] = $orderid;
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['O.pay_tongdao'] = array('eq', $tongdao);
        }

        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $where['O.pay_applydate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
            $profitMap['datetime'] = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d H:i:s')]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime, $setime)    = explode('|', $successtime);
            $where['O.pay_successdate'] = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
            $profitMap['datetime'] = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d H:i:s')]];
        } else if (!$successtime && !$createtime) {
            $_GET['successtime']      = date('Y-m-d H:i:s', strtotime(date('Y-m', time()))) . " | " . date('Y-m-d H:i:s', time());
            $where['O.pay_successdate'] = ['between', [strtotime(date('Y-m', time())), time()]];
            $profitMap['datetime'] = ['between', [strtotime(date('Y-m', time())), time()]];
        }

        $count = M('Order')->alias('as O')->where($where)->count();
        $page  = new Page($count, 15);
        $list  = M('Order')
            ->alias('as O')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();

        $amount = $rate = $realmoney = 0;
        foreach ($list as $item) {
            if ($item['pay_status'] >= 1) {
                $amount += $item['pay_amount'];
                $rate += $item['pay_poundage'];
                $realmoney += $item['pay_actualamount'];
            }
        }
        //查询支付成功的订单的手续费，入金费，总额总和
        $countWhere               = $where;
        $countWhere['O.pay_status'] = ['between', [1, 2]];
        $field                    = ['sum(`pay_amount`) pay_amount','sum(`cost`) cost', 'sum(`pay_poundage`) pay_poundage', 'sum(`pay_actualamount`) pay_actualamount', 'count(`id`) success_count'];
        $sum                      = M('Order')->alias('as O')->field($field)->where($countWhere)->find();
        foreach ($sum as $k => $v) {
            $sum[$k] += 0;
        }
        $countWhere['O.pay_status'] = 0;
        //失败笔数
        $sum['fail_count'] =  M('Order')->alias('as O')->where($countWhere)->count();
        //投诉保证金冻结金额
        $map = $where;
        $map['C.status'] = 0;
        $sum['complaints_deposit_freezed'] = M('complaints_deposit')->alias('as C')->join('LEFT JOIN __ORDER__ AS O ON C.pay_orderid=O.pay_orderid')
            ->where($map)
            ->sum('freeze_money');
        $sum['complaints_deposit_freezed'] += 0;
        $map['C.status'] = 1;
        $sum['complaints_deposit_unfreezed'] = M('complaints_deposit')->alias('as C')->join('LEFT JOIN __ORDER__ AS O ON C.pay_orderid=O.pay_orderid')
            ->where($map)
            ->sum('freeze_money');
        $sum['complaints_deposit_unfreezed'] += 0;
        $profitMap['lx'] = 9;
       // $sum['memberprofit1'] = M('moneychange')->where($profitMap)->sum('money');
      
       $sum['memberprofit1'] = M('moneychange')->where(['lx'=>9])->sum('money'); //修复bug 原bug不显示代理总分成
      
        $sum['pay_poundage1'] = $sum['pay_poundage']- $sum['cost'];

        $sum['memberprofit'] = M('moneychange')->where(['lx'=>9])->sum('money');
        $sum['pay_poundage'] = $sum['pay_poundage'] - $sum['cost'] - $sum['memberprofit1'];
        foreach($sum as $k => $v) {
            $sum[$k] +=0;
        }
        //统计订单信息
        $is_month = true;
        //下单时间
        if ($createtime) {
            $cstartTime = strtotime($cstime);
            $cendTime   = strtotime($cetime) ? strtotime($cetime) : time();
            $is_month   = $cendTime - $cstartTime > self::TMT ? true : false;
        }
        //支付时间
        $pstartTime = strtotime($sstime);
        $pendTime   = strtotime($setime) ? strtotime($setime) : time();
        $is_month   = $pendTime - $pstartTime > self::TMT ? true : false;

        $time       = $successtime ? 'pay_successdate' : 'pay_applydate';
        $dateFormat = $is_month ? '%Y年-%m月' : '%Y年-%m月-%d日';
        $field      = "FROM_UNIXTIME(" . $time . ",'" . $dateFormat . "') AS date,SUM(pay_amount) AS amount,SUM(pay_poundage) AS rate,SUM(pay_actualamount) AS total";
        $_mdata     = M('Order')->alias('as O')->field($field)->where($where)->group('date')->select();
        $mdata      = [];
        foreach ($_mdata as $item) {
            $mdata['amount'][] = $item['amount'] ? $item['amount'] : 0;
            $mdata['mdate'][]  = "'" . $item['date'] . "'";
            $mdata['total'][]  = $item['total'] ? $item['total'] : 0;
            $mdata['rate'][]   = $item['rate'] ? $item['rate'] : 0;
        }

        $this->assign("list", $list);
        $this->assign("mdata", $mdata);
        $this->assign('page', $page->show());
        $this->assign('stamount', $sum['pay_amount']);
        $this->assign('stamount1', $sum['pay_amount']);//新总入金
        $this->assign('strate', $sum['pay_poundage']);
        $this->assign('strate1', $sum['pay_poundage1']); //新平台总分成
        $this->assign('strealmoney', $sum['pay_actualamount']);
        $this->assign('success_count', $sum['success_count']);
        $this->assign('fail_count', $sum['fail_count']);
        $this->assign('memberprofit', $sum['memberprofit']);
        $this->assign('memberprofit1', $sum['memberprofit1']);//新代理分成
        $this->assign('complaints_deposit_freezed', $sum['complaints_deposit_freezed']);
        $this->assign('complaints_deposit_unfreezed', $sum['complaints_deposit_unfreezed']);
        $this->assign("isrootadmin", is_rootAdministrator());
        C('TOKEN_ON', false);
        $this->display();
    }
    /**
     * 导出交易订单
     * */
    public function exportorder()
    {

        //通道
        $tongdaolist = M("Channel")->field('id,code,title')->select();
        $this->assign("tongdaolist", $tongdaolist);

        $where = array(
            'pay_status' => ['eq', 2],
        );
        $memberid = I("request.memberid");
        if ($memberid) {
            $where['pay_memberid'] = array('eq', $memberid);
        }
        $orderid = I("request.orderid");
        if ($orderid) {
            $where['out_trade_id'] = $orderid;
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['pay_tongdao'] = array('eq', $tongdao);
        }

        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $where['pay_applydate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime, $setime)    = explode('|', $successtime);
            $where['pay_successdate'] = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
        }

        $title = array('订单号', '商户编号', '交易金额', '手续费', '实际金额', '提交时间', '成功时间', '通道', '状态');
        $data  = M('Order')->where($where)->select();

        foreach ($data as $item) {
            $list[] = array(
                'pay_orderid'      => $item['pay_orderid'],
                'pay_memberid'     => $item['pay_memberid'],
                'pay_amount'       => $item['pay_amount'],
                'pay_poundage'     => $item['pay_poundage'],
                'pay_actualamount' => $item['pay_actualamount'],
                'pay_applydate'    => date('Y-m-d H:i:s', $item['pay_applydate']),
                'pay_successdate'  => date('Y-m-d H:i:s', $item['pay_successdate']),
                'pay_zh_tongdao'   => $item['pay_zh_tongdao'],
                'pay_status'       => '成功，已返回',
            );
        }

        exportCsv($list, $title);
    }

    public function userFinance()
    {
        $groupid          = I('get.groupid', 'member');
        $where['groupid'] = $groupid == 'agent' ? ['gt', '4'] : ['eq', '4'];
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Member     = M('Member');
        $count      = $Member->where($where)->count();
        $Page       = new Page($count, $rows);
        $show       = $Page->show();
        $memberList = $Member
            ->field(['id', 'username','balance', 'blockedbalance'])
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($memberList) {
            $Order    = M('Order');
            $Wttklist = M('Wttklist');
            $Tklist   = M('Tklist');
            //计算商户号，ma de 订单表竟然没有用户id,没法使用关联模型,如果你是想用in查询请百度一下其速度,现在得查45条sql |~_~|
            foreach ($memberList as $k => $v) {
                $payMemberid = $v['id'] + 10000;

                $orderList = $Order
                    ->field(['sum(pay_amount) pay_amount', 'sum(pay_poundage) pay_poundage', 'sum(pay_actualamount) pay_actualamount'])
                    ->where(['pay_memberid' => $payMemberid, 'pay_status' => ['neq', 0]])
                    ->find();
                if (empty($orderList)) {
                    $orderList = ['pay_amount' => 0.00, 'pay_poundage' => 0.00, 'pay_actualamount' => 0.00];
                } else {
                    $orderList['pay_amount']+=0;
                    $orderList['pay_poundage']+=0;
                    $orderList['pay_actualamount']+=0;
                }

                $wttklistList = $Wttklist
                    ->field(['sum(tkmoney) tkmoney', 'sum(sxfmoney) sxfmoney', 'sum(money) money'])
                    ->where(['userid' => $v['id'], 'status' => 2])
                    ->find();

                $tklistList = $Tklist
                    ->field(['sum(tkmoney) tkmoney', 'sum(sxfmoney) sxfmoney', 'sum(money) money'])
                    ->where(['userid' => $v['id'], 'status' => 2])
                    ->find();

                //计算出金的总金额
                $tempCounts = ['tkmoney' => 0, 'sxfmoney' => 0, 'money' => 0];
                foreach ($tempCounts as $k1 => $v1) {
                    $tempCounts[$k1] = (float) ($tklistList[$k1] + $wttklistList[$k1]);
                }
                $memberList[$k]                 = array_merge($tempCounts, $orderList, $v);
                $memberList[$k]['pay_memberid'] = $payMemberid;
                //提交订单数
                $memberList[$k]['all_order_count'] =  $Order->where(['pay_memberid' => $payMemberid])->count();
                $memberList[$k]['all_order_count'] +=0;
                //已付订单数
                $memberList[$k]['paid_order_count'] =  $Order->where(['pay_memberid' => $payMemberid, 'pay_status'=>['in', '1,2']])->count();
                $memberList[$k]['paid_order_count'] +=0;
                //未付订单数
                $memberList[$k]['nopaid_order_count'] = $Order->where(['pay_memberid' => $payMemberid, 'pay_status'=>0])->count();
                $memberList[$k]['nopaid_order_count'] +=0;
                //提交金额
                $memberList[$k]['all_order_amount'] = $Order->where(['pay_memberid' => $payMemberid])->sum('pay_amount');
                $memberList[$k]['all_order_amount'] +=0;
                //实付金额
                $memberList[$k]['paid_order_amount'] = $Order->where(['pay_memberid' => $payMemberid, 'pay_status'=>['in', '1,2']])->sum('pay_amount');
                $memberList[$k]['paid_order_amount'] +=0;
                //入金手续费
                $memberList[$k]['pay_amount'] = $Order->where(['pay_memberid' => $payMemberid, 'pay_status'=>['in', '1,2']])->sum('pay_poundage');
                $memberList[$k]['pay_amount'] +=0;
                //商户收入
                $actualamount = M('Order')->where(['pay_memberid'=>$payMemberid, 'status' => ['in', '1,2']])->sum('pay_actualamount');
                $profitSum = M('moneychange')->where(['userid'=>$v['id'],'lx'=>9])->sum('money');
                $redoAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$v['id']])->sum('money');
                $redoReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$v['id']])->sum('money');
                $orderSum = $actualamount + $profitSum + $redoAddSum - $redoReduceSum;
                $memberList[$k]['member_income'] = $orderSum;
                $memberList[$k]['member_income'] +=0;
                //平台收入
                $income_profit = M('Order')->where(['pay_memberid'=>$payMemberid, 'status' => ['in', '1,2']])->sum('pay_poundage');
                $order_cost = M('Order')->where(['pay_memberid'=>$payMemberid, 'status' => ['in', '1,2']])->sum('cost');
                $pay_cost = M('wttklist')->where(['userid'=>$v['id'], 'status' => 2])->sum('cost');
                $agent_profit_cost = M('moneychange')->where(['tcuserid'=>$v['id'],'lx'=>9])->sum('money');
                $memberList[$k]['platform_income'] = $income_profit - $order_cost - $pay_cost - $agent_profit_cost;
                $memberList[$k]['platform_income'] += 0;
            }
        }
        $stat['member_count'] = count($memberList);
        $fields = ['tkmoney', 'sxfmoney', 'money' , 'all_order_count', 'paid_order_count', 'nopaid_order_count', 'all_order_amount', 'paid_order_amount', 'member_income', 'platform_income','balance','blockedbalance','pay_amount','tkmoney','sxfmoney'];
        foreach($fields as $field ) {
            foreach($memberList as $k => $v) {
                $stat[$field] += $v[$field];
            }
        }
        $this->assign('stat', $stat);
        $this->assign('page', $show);
        $this->assign('list', $memberList);
        $this->assign('rows',$rows);
        $this->display();

    }

    //导出商户交易统计
    public function exportUserFinance(){
        $groupid          = I('get.groupid', 'member');
        $where['groupid'] = $groupid == 'agent' ? ['gt', '4'] : ['eq', '4'];
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Member     = M('Member');
        $memberList = $Member
            ->field(['id', 'username','balance', 'blockedbalance'])
            ->where($where)
            ->select();
        if ($memberList) {
            $Order    = M('Order');
            $Wttklist = M('Wttklist');
            $Tklist   = M('Tklist');
            foreach ($memberList as $k => $v) {
                $payMemberid = $v['id'] + 10000;

                $orderList = $Order
                    ->field(['sum(pay_amount) pay_amount', 'sum(pay_poundage) pay_poundage', 'sum(pay_actualamount) pay_actualamount'])
                    ->where(['pay_memberid' => $payMemberid, 'pay_status' => ['neq', 0]])
                    ->find();
                if (empty($orderList)) {
                    $orderList = ['pay_amount' => 0.00, 'pay_poundage' => 0.00, 'pay_actualamount' => 0.00];
                } else {
                    $orderList['pay_amount']+=0;
                    $orderList['pay_poundage']+=0;
                    $orderList['pay_actualamount']+=0;
                }

                $wttklistList = $Wttklist
                    ->field(['sum(tkmoney) tkmoney', 'sum(sxfmoney) sxfmoney', 'sum(money) money'])
                    ->where(['userid' => $v['id'], 'status' => 2])
                    ->find();

                $tklistList = $Tklist
                    ->field(['sum(tkmoney) tkmoney', 'sum(sxfmoney) sxfmoney', 'sum(money) money'])
                    ->where(['userid' => $v['id'], 'status' => 2])
                    ->find();

                //计算出金的总金额
                $tempCounts = ['tkmoney' => 0, 'sxfmoney' => 0, 'money' => 0];
                foreach ($tempCounts as $k1 => $v1) {
                    $tempCounts[$k1] = (float) ($tklistList[$k1] + $wttklistList[$k1]);
                }
                $memberList[$k]                 = array_merge($tempCounts, $orderList, $v);
                $memberList[$k]['pay_memberid'] = $payMemberid;
                //提交订单数
                $memberList[$k]['all_order_count'] =  $Order->where(['pay_memberid' => $payMemberid])->count();
                $memberList[$k]['all_order_count'] +=0;
                //已付订单数
                $memberList[$k]['paid_order_count'] =  $Order->where(['pay_memberid' => $payMemberid, 'pay_status'=>['in', '1,2']])->count();
                $memberList[$k]['paid_order_count'] +=0;
                //未付订单数
                $memberList[$k]['nopaid_order_count'] = $Order->where(['pay_memberid' => $payMemberid, 'pay_status'=>0])->count();
                $memberList[$k]['nopaid_order_count'] +=0;
                //提交金额
                $memberList[$k]['all_order_amount'] = $Order->where(['pay_memberid' => $payMemberid])->sum('pay_amount');
                $memberList[$k]['all_order_amount'] +=0;
                //实付金额
                $memberList[$k]['paid_order_amount'] = $Order->where(['pay_memberid' => $payMemberid, 'pay_status'=>['in', '1,2']])->sum('pay_amount');
                $memberList[$k]['paid_order_amount'] +=0;
                //入金手续费
                $memberList[$k]['pay_amount'] = $Order->where(['pay_memberid' => $payMemberid, 'pay_status'=>['in', '1,2']])->sum('pay_poundage');
                $memberList[$k]['pay_amount'] +=0;
                //商户收入
                $actualamount = M('Order')->where(['pay_memberid'=>$payMemberid, 'status' => ['in', '1,2']])->sum('pay_actualamount');
                $profitSum = M('moneychange')->where(['userid'=>$v['id'],'lx'=>9])->sum('money');
                $redoAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$v['id']])->sum('money');
                $redoReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$v['id']])->sum('money');
                $orderSum = $actualamount + $profitSum + $redoAddSum - $redoReduceSum;
                $memberList[$k]['member_income'] = $orderSum;
                $memberList[$k]['member_income'] +=0;
                //平台收入
                $income_profit = M('Order')->where(['pay_memberid'=>$payMemberid, 'status' => ['in', '1,2']])->sum('pay_poundage');
                $order_cost = M('Order')->where(['pay_memberid'=>$payMemberid, 'status' => ['in', '1,2']])->sum('cost');
                $pay_cost = M('wttklist')->where(['userid'=>$v['id'], 'status' => 2])->sum('cost');
                $agent_profit_cost = M('moneychange')->where(['tcuserid'=>$v['id'],'lx'=>9])->sum('money');
                $memberList[$k]['platform_income'] = $income_profit - $order_cost - $pay_cost - $agent_profit_cost;
                $memberList[$k]['platform_income'] += 0;
            }
        }
        $stat['member_count'] = count($memberList);
        $fields = ['tkmoney', 'sxfmoney', 'money' , 'all_order_count', 'paid_order_count', 'nopaid_order_count', 'all_order_amount', 'paid_order_amount', 'member_income', 'platform_income','balance','blockedbalance','pay_amount','tkmoney','sxfmoney'];
        foreach($fields as $field ) {
            foreach($memberList as $k => $v) {
                $stat[$field] += $v[$field];
            }
        }
        foreach ($memberList as $m => $n){

            $list[] = array(
                'pay_memberid'=>$n['pay_memberid'],
                'username'=>$n['username'],
                'paid_order_count'=>$n['paid_order_count'],
                'nopaid_order_count'=>$n['nopaid_order_count'],
                'all_order_amount'=>$n['all_order_amount'],
                'paid_order_amount'=>$n['paid_order_amount'],
                'pay_poundage'=>$n['pay_poundage'],
                'balance'=>$n['balance'],
                'blockedbalance'=>$n['blockedbalance'],
                'pay_amount'=>$n['pay_amount'],
                'tkmoney'=>$n['tkmoney'],
                'sxfmoney'=>$n['sxfmoney'],
                'money'=>$n['money'],
                'member_income'=>$n['member_income'],
                'platform_income'=>$n['platform_income']
            );
        }
        $list[] = array(
            'pay_memberid'=>'统计：',
            'username'=>$stat['member_count'].'个商户',
            'paid_order_count'=>$stat['all_order_count'].'条订单',
            'nopaid_order_count'=>$stat['paid_order_count'].'条订单',
            'all_order_amount'=>$stat['nopaid_order_count'].'条订单',
            'paid_order_amount'=>$stat['all_order_amount'].'元',
            'pay_poundage'=>$stat['paid_order_amount'].'元',
            'balance'=>$stat['balance'].'元',
            'blockedbalance'=>$stat['blockedbalance'].'元',
            'pay_amount'=>$stat['pay_amount'].'元',
            'tkmoney'=>$stat['tkmoney'].'元',
            'sxfmoney'=>$stat['sxfmoney'].'元',
            'money'=>$stat['money'].'元',
            'member_income'=>$stat['member_income'].'元',
            'platform_income'=>$stat['platform_income'].'元'
        );
        $title = array(
            '商户编号',
            '商户名称',
            '提交订单',
            '已付订单',
            '未付订单',
            '提交金额',
            '实付金额',
            '入金手续费',
            '可用资金',
            '冻结金额',
            '入金总额',
            '出金总额',
            '出金手续费',
            '实际出金金额',
        );
        exportCsv($list, $title);
    }

    public function channelFinance()
    {

        $Product = M('Product');
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $count = $Product->count();
        $Page  = new Page($count, $rows);
        $show  = $Page->show();

        $productList = $Product
            ->field(['id', 'name', 'code'])
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();

        //注意因为很多客户的订单量是很大的，这里要分割查询，我是想不到好的方法 =。=
        $Order      = M('Order');
        $orderCount = $Order->count();
        $orderList  = [];
        $limit      = 100000;
        for ($i = 0; $i < $orderCount; $i += $limit) {

            $tempList = $Order
                ->field(['pay_bankcode', 'pay_amount', 'pay_poundage', 'pay_actualamount', 'pay_status'])
                ->limit($i, $limit)
                ->select();

            $orderList = array_merge($orderList, $tempList);
        }

        // dump($orderList);exit;
        //处理查询的数据
        foreach ($productList as $k => $v) {

            $productList[$k]['count']            = 0;
            $productList[$k]['fail_count']       = 0;
            $productList[$k]['success_count']    = 0;
            $productList[$k]['success_rate']     = 0;
            $productList[$k]['pay_amount']       = 0.00;
            $productList[$k]['pay_poundage']     = 0.00;
            $productList[$k]['pay_actualamount'] = 0.00;

            foreach ($orderList as $k1 => $v1) {
                if ($v['id'] == $v1['pay_bankcode']) {
                    $productList[$k]['count']++;
                    if ($v1['pay_status'] != 0) {
                        $productList[$k]['success_count']++;
                        $productList[$k]['pay_amount']       = bcadd($productList[$k]['pay_amount'], $v1['pay_amount'], 4);
                        $productList[$k]['pay_poundage']     = bcadd($productList[$k]['pay_poundage'], $v1['pay_poundage'], 4);
                        $productList[$k]['pay_actualamount'] = bcadd($productList[$k]['pay_actualamount'], $v1['pay_actualamount'], 4);
                    }
                }
            }
            $productList[$k]['fail_count']   = $productList[$k]['count'] - $productList[$k]['success_count'];
            $productList[$k]['success_rate'] = bcdiv($productList[$k]['success_count'], $productList[$k]['count'], 4) * 100;
        }

        $this->assign('list', $productList);
        $this->display();
    }

    public function productChannelFinance()
    {
        $id = I('id', 0, 'intval');
        if(!$id) {
            $this->error('缺少参数');
        }
        $product = M('Product')->where(['id' => $id])->find();
        if(empty($product)) {
            $this->error('支付产品不存在');
        }
        $list = M('channel')->where(['paytype' => $product['paytype']])->select();
        foreach($list as $k => $v) {
            $sum = M('Order')->field(['sum(`pay_amount`) pay_amount','sum(`cost`) cost', 'sum(`pay_poundage`) pay_poundage', 'sum(`pay_actualamount`) pay_actualamount', 'count(`id`) success_count'])
                ->where(['channel_id'=> $v['id'], 'pay_status' => ['in', '1,2']])->find();
            $list[$k]['pay_amount'] = $sum['pay_amount'];//交易笔数
            $list[$k]['pay_amount'] += 0;
            $list[$k]['pay_poundage'] = $sum['pay_poundage'];//手续费
            $list[$k]['pay_poundage'] += 0;
            $list[$k]['pay_actualamount'] = $sum['pay_actualamount'];//入金总额
            $list[$k]['pay_actualamount'] += 0;
            $list[$k]['count'] = M('Order')->where(['channel_id'=> $v['id']])->count();//交易笔数
            $list[$k]['count'] += 0;
            $list[$k]['success_count'] = $sum['success_count'];//成功笔数
            $list[$k]['success_count'] += 0;
            $list[$k]['fail_count'] = $list[$k]['count'] - $list[$k]['success_count'];//失败笔数

            $list[$k]['success_rate'] = $list[$k]['count']>0?bcdiv($list[$k]['success_count'],$list[$k]['count'], 4) * 100 : 0;//成功率
        }
        $this->assign('list', $list);
        $this->assign('data', $product);
        $this->display();
    }

    public function channelAccountFinance()
    {
        $id = I('id', 0, 'intval');
        if(!$id) {
            $this->error('缺少参数');
        }
        $channel = M('Channel')->where(['id' => $id])->find();
        if(empty($channel)) {
            $this->error('支付通道不存在');
        }
        $list = M('channelAccount')->where(['channel_id' => $id])->select();
        foreach($list as $k => $v) {
            $sum = M('Order')->field(['sum(`pay_amount`) pay_amount','sum(`cost`) cost', 'sum(`pay_poundage`) pay_poundage', 'sum(`pay_actualamount`) pay_actualamount', 'count(`id`) success_count'])
                ->where(['account_id'=> $v['id'], 'pay_status' => ['in', '1,2']])->find();
            $list[$k]['pay_amount'] = $sum['pay_amount'];//交易笔数
            $list[$k]['pay_amount'] += 0;
            $list[$k]['pay_poundage'] = $sum['pay_poundage'];//手续费
            $list[$k]['pay_poundage'] += 0;
            $list[$k]['pay_actualamount'] = $sum['pay_actualamount'];//入金总额
            $list[$k]['pay_actualamount'] += 0;
            $list[$k]['count'] = M('Order')->where(['account_id'=> $v['id']])->count();//交易笔数
            $list[$k]['count'] += 0;
            $list[$k]['success_count'] = $sum['success_count'];//成功笔数
            $list[$k]['success_count'] += 0;
            $list[$k]['fail_count'] = $list[$k]['count'] - $list[$k]['success_count'];//失败笔数
            $list[$k]['success_rate'] = $list[$k]['count']>0?bcdiv($list[$k]['success_count'],$list[$k]['count'], 4) * 100 : 0;//成功率
        }
        $this->assign('list', $list);
        $this->assign('data', $channel);
        $this->display();
    }
    // /**
    //  * 统计所有用户的订单表，入金表，代付表，提现表的数据
    //  * @param array $memberLists [用户表的数据]
    //  * @return array [$memberLists[处理好的数据]，$allSum[总的数据统计]]
    //  */
    // public function countData($memberLists)
    // {

    //     //所有用户的入金，手续费，代付+提现的总额
    //     $allSum = [
    //         'allPoundage'  => 0, //平台总收益
    //         'amount'       => 0, //订单总额
    //         'poundage'     => 0, //入金手续费
    //         'actualamount' => 0, //入金总额
    //         'tkmoney'      => 0, //代付+提现的总额
    //         'money'        => 0, //实际代付+提现的总额
    //         'sxfmoney'     => 0, //代付+提现的手续费总额
    //         'reward'       => 0, //奖励费
    //         'orderCost'    => 0, //成本费
    //         'netProfit'    => 0, //净利润
    //         'wlCost'       => 0, //代付上游成本费
    //     ];

    //     //获取认证用户的id和用户的商户id
    //     foreach ($memberLists as $k => $v) {
    //         $memberLists[$k]['groupid'] = $this->groupId[$v['groupid']];
    //         $userids[]                  = $v['id'];
    //         $memberids[]                = $v['id'] + 10000;
    //     }

    //     //查询 流水表，订单表，提现表，代付表的数据

    //     //--------代付表---------
    //     $wttkWhere = ['status' => 2, 'userid' => ['in', $userids]];
    //     $wttkTime  = I('request.wttk_time', '');
    //     if ($wttkTime) {
    //         $wttkTime                = explode('|', $wttkTime);
    //         $wttkWhere['cldatetime'] = ['between', $wttkTime];
    //     }
    //     $Wttklist  = M('Wttklist');
    //     $wttkLists = $Wttklist->where($wttkWhere)->select();
    //     $wttkField = ['sum(`tkmoney`) wl_tkmoney', 'sum(`sxfmoney`) wl_sxfmoney', 'sum(`money`) wl_money', 'sum(`cost`) wl_cost'];
    //     $wttkSum   = $Wttklist->field($wttkField)->where(['status' => 2])->find();

    //     //---------提现表---------
    //     $tkWhere = ['status' => 2, 'userid' => ['in', $userids]];
    //     $tkTime  = I('request.tk_time', '');
    //     if ($tkTime) {
    //         $tkTime                = explode('|', $tkTime);
    //         $tkWhere['cldatetime'] = ['between', $tkTime];
    //     }

    //     $Tklist  = M('Tklist');
    //     $tkLists = $Tklist->where($wttkWhere)->select();
    //     $tkField = ['sum(`tkmoney`) tl_tkmoney', 'sum(`sxfmoney`) tl_sxfmoney', 'sum(`money`) tl_money'];
    //     $tkSum   = $Tklist->field($tkField)->where(['status' => 2])->find();

    //     //-----------订单表-------------
    //     $orderWhere = ['pay_memberid' => ['in', $memberids], 'pay_status' => ['between', [1, 2]]];
    //     $orderTime  = I('request.order_time', '');
    //     if ($orderTime) {
    //         $orderTime                     = explode('|', $orderTime);
    //         $orderTime[0]                  = strtotime($orderTime[0]);
    //         $orderTime[1]                  = strtotime($orderTime[1]);
    //         $orderWhere['pay_successdate'] = ['between', $orderTime];
    //     }

    //     $Order      = M('Order');
    //     $orderLists = $Order->where($orderWhere)->select();
    //     $orderField = ['sum(`pay_amount`) amount, sum(`pay_poundage`) poundage, sum(`pay_actualamount`) actualamount, sum(`cost`) cost'];
    //     $orderSum   = $Order->field($orderField)->where(['pay_status' => ['between', [1, 2]]])->find();

    //     //----------流水表---------------
    //     $Moneychange = M('Moneychange');
    //     $moneyLists  = $Moneychange->where(['userid' => ['in', $userids]])->select();
    //     $reward      = $Moneychange->where(['lx' => 9])->sum('money');

    //     //-----------总计---------------
    //     $allSum['amount']       = bcadd($allSum['amount'], $orderSum['amount'], 4); //订单总额
    //     $allSum['poundage']     = bcadd($allSum['poundage'], $orderSum['poundage'], 4); //入金手续费
    //     $allSum['actualamount'] = bcadd($allSum['actualamount'], $orderSum['actualamount'], 4); //入金总额
    //     $allSum['tkmoney']      = bcadd($tkSum['tl_tkmoney'], $wttkSum['wl_tkmoney'], 4); //代付+提现的总额
    //     $allSum['money']        = bcadd($tkSum['tl_money'], $wttkSum['wl_money'], 4); //实际代付+提现的总额
    //     $allSum['sxfmoney']     = bcadd($tkSum['tl_sxfmoney'], $wttkSum['wl_sxfmoney'], 4); //代付+提现的手续费总额
    //     $allSum['allPoundage']  = bcadd($allSum['sxfmoney'], $allSum['poundage'],4); //平台总收益
    //     $allSum['orderCost']    += $orderSum['cost']; //上游成本费
    //     $allSum['wlCost']       += $wttkSum['wl_cost']; //代付成本费
    //     $allSum['reward']       = $reward; //奖励费

    //     //计算净利润
    //     $netProfit           = bcsub($allSum['allPoundage'], $allSum['reward'], 4);
    //     $netProfit           = bcsub($netProfit, $allSum['orderCost'], 4);
    //     $allSum['netProfit'] = bcsub($netProfit, $allSum['wlCost'], 4);

    //     //统计每个用户的流水，入金，提现，代付等数据
    //     foreach ($memberLists as $k => $v) {
    //         $memberid                       = $v['id'] + 10000;
    //         $memberLists[$k]['memberid']    = $memberid;
    //         $memberLists[$k]['all_balance'] = bcadd($v['balance'], $v['blockedbalance'], 4);

    //         $sum = [
    //             'wl_tkmoney'       => 0, //代付的金额
    //             'wl_sxfmoney'      => 0, //代付的手续费
    //             'wl_money'         => 0, //代付的实际金额
    //             'tl_tkmoney'       => 0, //提现的金额
    //             'tl_sxfmoney'      => 0, //提现的手续费
    //             'tl_money'         => 0, //提现的设计金额
    //             'tkmoney'          => 0, //代付+提现 金额
    //             'sxfmoney'         => 0, //代付+提现 手续费
    //             'money'            => 0, //代付+提现 实际金额
    //             'pay_amount'       => 0, //订单的金额
    //             'pay_poundage'     => 0, //订单的手续费
    //             'pay_actualamount' => 0, //订单入金总额
    //             'order_cost'       => 0, //成本费
    //             'wl_cost'          => 0, //代付成本费
    //             'lx1'              => 0, //用户的入金总额
    //             'lx3'              => 0, //手动增加的
    //             'lx4'              => 0, //手动减少的
    //             'lx9'              => 0, //奖励
    //             'pay_count'        => 0, //支付成功的笔数
    //             'wttk_count'       => 0, //代付笔数
    //             'tk_count'         => 0, //提现笔数
    //         ];

    //         //循环统计查询的代付的数据
    //         foreach ($wttkLists as $k1 => $v1) {
    //             if ($v1['userid'] == $v['id']) {
    //                 $sum['wl_tkmoney']  = bcadd($v1['tkmoney'], $sum['wl_tkmoney'], 4);
    //                 $sum['wl_money']    = bcadd($v1['money'], $sum['wl_money'], 4);
    //                 $sum['wl_sxfmoney'] = bcadd($v1['sxfmoney'], $sum['wl_sxfmoney'], 4);
    //                 $sum['wl_cost']     = bcadd($v1['cost'], $sum['wl_cost'], 4);
    //                 $sum['wttk_count']++;
    //             }
    //         }

    //         //统计提现的数据
    //         foreach ($tkLists as $k1 => $v1) {
    //             if ($v1['userid'] == $v['id']) {
    //                 $sum['tl_tkmoney']  = bcadd($v1['tkmoney'], $sum['tl_tkmoney'], 4);
    //                 $sum['tl_money']    = bcadd($v1['money'], $sum['tl_money'], 4);
    //                 $sum['tl_sxfmoney'] = bcadd($v1['sxfmoney'], $sum['tl_sxfmoney'], 4);
    //                 $sum['tk_count']++;
    //             }
    //         }

    //         //统计订单的数据
    //         foreach ($orderLists as $k1 => $v1) {
    //             if ($v1['pay_memberid'] == $memberid) {
    //                 $sum['pay_amount']       = bcadd($v1['pay_amount'], $sum['pay_amount'], 4);
    //                 $sum['pay_poundage']     = bcadd($v1['pay_poundage'], $sum['pay_poundage'], 4);
    //                 $sum['pay_actualamount'] = bcadd($v1['pay_actualamount'], $sum['pay_actualamount'], 4);
    //                 $sum['order_cost']       = bcadd($v1['cost'], $sum['order_cost'], 4);
    //                 $sum['pay_count']++;
    //             }
    //         }

    //         //统计流水账单的数据
    //         foreach ($moneyLists as $k1 => $v1) {
    //             if ($v1['userid'] == $v['id']) {
    //                 switch ($v1['lx']) {
    //                     case '1':
    //                         $sum['lx1'] = bcadd($v1['money'], $sum['lx1'], 4);
    //                         break;
    //                     case '3':
    //                         $sum['lx3'] = bcadd($v1['money'], $sum['lx3'], 4);
    //                         break;
    //                     case '4':
    //                         $sum['lx4'] = bcadd($v1['money'], $sum['lx4'], 4);
    //                         break;
    //                     case '9':
    //                         $sum['lx9'] = bcadd($v1['money'], $sum['lx9'], 4);
    //                         break;
    //                 }
    //             }
    //         }
    //         //计算每个用户的代付+提现的数据
    //         $sum['money']        = bcadd($sum['tl_money'], $sum['wl_money'], 4);
    //         $sum['sxfmoney']     = bcadd($sum['tl_sxfmoney'], $sum['wl_sxfmoney'], 4);
    //         $sum['tkmoney']      = bcadd($sum['tl_tkmoney'], $sum['wl_tkmoney'], 4);
    //         $sum['all_poundage'] = bcadd($sum['sxfmoney'], $sum['pay_poundage'], 4);

    //         $memberLists[$k] = array_merge($memberLists[$k], $sum);
    //     }

    //     return [$memberLists, $allSum];
    // }

    // public function userAnalysis()
    // {
    //     //查询所有的认证的用户
    //     $memberid = I('request.memberid', '');
    //     if ($memberid) {
    //         $where['id'] = $memberid - 10000;
    //     }
    //     $where['authorized'] = '1';
    //     $Member              = M('Member');
    //     $count               = $Member->where($where)->count();
    //     $Page                = new Page($count, 15);
    //     $memberLists         = $Member->field(['id', 'username', 'groupid', 'balance', 'blockedbalance'])->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
    //     $page                = $Page->show();
    //     $export              = U('Admin/Statistics/exportUserAnalysis') . '?memberid=' . $_GET['memberid'] . '&order_time=' . $_GET['order_time'] . '&wtkk_time=' . $_GET['wtkk_time'] . '&tk_time=' . $_GET['tk_time'];
    //     if ($memberLists) {
    //         list($memberLists, $allSum) = $this->countData($memberLists);
    //         $this->assign('export', $export);
    //         $this->assign($allSum);
    //         $this->assign('lists', $memberLists);
    //         $this->assign('page', $page);
    //     }
    //     $this->display();
    // }

    // public function details()
    // {
    //     /**
    //      *用户每一个入金渠道的总额，
    //      *代付渠道的总额
    //      */

    //     $id = I('request.id', '');

    //     if ($id) {

    //         //所有用户的入金，手续费，代付+提现的总额
    //         $allPoundage  = 0; //平台总收益
    //         $amount       = 0; //订单总额
    //         $poundage     = 0; //入金手续费
    //         $actualamount = 0; //入金总额
    //         $tkmoney      = 0; //代付+提现的总额
    //         $money        = 0; //实际代付+提现的总额
    //         $sxfmoney     = 0; //代付+提现的手续费总额
    //         $orderCost    = 0; //订单成本
    //         $wlCost       = 0; //代付成本

    //         $memberList               = M('Member')->where(['id' => $id])->find();
    //         $memberList['allbalance'] = bcadd($memberList['blockedbalance'], $memberList['balance'], 2);
    //         $memberList['groupid']    = $this->groupId[$memberList['groupid']];

    //         //查询订单表的数据
    //         $memberList['memberid'] = $memberid = $id + 10000;
    //         $Order                  = M('Order');
    //         $orderField             = [
    //             'sum(`pay_amount`) amount',
    //             'sum(`pay_poundage`) poundage',
    //             'sum(`pay_actualamount`) actualamount',
    //             'sum(`cost`) cost',
    //             'pay_zh_tongdao',
    //             'pay_tongdao',
    //         ];

    //         $orderWhere = ['pay_memberid' => $memberid, 'pay_status' => ['between', [1, 2]]];
    //         $orderTime  = I('request.order_time', '');
    //         if ($orderTime) {
    //             $orderTime                     = explode('|', $orderTime);
    //             $orderTime[0]                  = strtotime($orderTime[0]);
    //             $orderTime[1]                  = strtotime($orderTime[1]);
    //             $orderWhere['pay_successdate'] = ['between', $orderTime];
    //         }
    //         //获取总的订单数据
    //         $orderLists = $Order->field($orderField)->where($orderWhere)->group('pay_tongdao')->select();
    //         foreach ($orderLists as $k => $v) {
    //             $amount       = bcadd($amount, $v['amount'], 2);
    //             $poundage     = bcadd($poundage, $v['poundage'], 2);
    //             $actualamount = bcadd($actualamount, $v['actualamount'], 2);
    //             $orderCost    = bcadd($orderCost, $v['cost'], 2);
    //         }

    //         //查询代付表的数据
    //         $wttkWhere = ['status' => 2, 'userid' => $id];
    //         $wttkTime  = I('request.wttk_time', '');
    //         if ($wttkTime) {
    //             $wttkTime                = explode('|', $wttkTime);
    //             $wttkWhere['cldatetime'] = ['between', $wttkTime];
    //         }
    //         $Wttklist  = M('Wttklist');
    //         $wttkField = [
    //             'sum(`tkmoney`) wl_tkmoney',
    //             'sum(`sxfmoney`) wl_sxfmoney',
    //             'sum(`money`) wl_money',
    //             'sum(`cost`) wl_cost',
    //             'df_name',
    //             'code',
    //         ];
    //         $wttkLists = $Wttklist->field($wttkField)->where($wttkWhere)->group('df_id')->select();
    //         //获取代付表总的数据
    //         foreach ($wttkLists as $k => $v) {
    //             $tkmoney  = bcadd($tkmoney, $v['wl_tkmoney'], 2);
    //             $sxfmoney = bcadd($sxfmoney, $v['wl_sxfmoney'], 2);
    //             $money    = bcadd($money, $v['wl_money'], 2);
    //             $wlCost   = bcadd($wlCost, $v['wl_cost'], 2);
    //         }

    //         //查询提现表的数据
    //         $tkWhere = ['status' => 2, 'userid' => $id];
    //         $tkTime  = I('request.tk_time', '');
    //         if ($tkTime) {
    //             $tkTime                = explode('|', $tkTime);
    //             $tkWhere['cldatetime'] = ['between', $tkTime];
    //         }
    //         $Tklist                                 = M('Tklist');
    //         $tkField                                = ['sum(`tkmoney`) tl_tkmoney', 'sum(`sxfmoney`) tl_sxfmoney', 'sum(`money`) tl_money'];
    //         $tkList                                 = $Tklist->field($tkField)->where($tkWhere)->find();
    //         empty($tkList['tl_tkmoney']) && $tkList = null;

    //         //获取总提现+代付的数据
    //         $tkmoney  = bcadd($tkmoney, $tkList['tl_tkmoney'], 4);
    //         $sxfmoney = bcadd($sxfmoney, $tkList['tl_sxfmoney'], 4);
    //         $money    = bcadd($money, $tkList['tl_money'], 4);

    //         //查询流水表的数据
    //         $Moneychange = M('Moneychange');
    //         $moneyWhere  = ['userid' => $id];
    //         $moneyLists  = $Moneychange->where($moneyWhere)->select();
    //         //处理订单表的数据
    //         $lx = ['lx1' => 0, 'lx3' => 0, 'lx4' => 0, 'lx9' => 0];
    //         foreach ($moneyLists as $k => $v) {
    //             $keyname = 'lx' . $v['lx'];
    //             $lx[$keyname] += $v['money'];
    //         }
    //         $allPoundage = $sxfmoney + $poundage;
    //         $netProfit   = $allPoundage - $orderCost - $wlCost - $lx['lx9'];
    //         $this->assign('amount', $amount);
    //         $this->assign('allPoundage', $allPoundage);
    //         $this->assign('actualamount', $actualamount);
    //         $this->assign('tkmoney', $tkmoney);
    //         $this->assign('money', $money);
    //         $this->assign('sxfmoney', $sxfmoney);
    //         $this->assign('orderLists', $orderLists);
    //         $this->assign('wttkLists', $wttkLists);
    //         $this->assign('tkLists', $tkLists);
    //         $this->assign('netProfit', $netProfit);
    //         $this->assign('orderCost', $orderCost);
    //         $this->assign('wlCost', $wlCost);
    //         $this->assign('lx', $lx);
    //         $this->assign('memberList', $memberList);
    //     }
    //     $this->display();
    // }

    // //导出所有认证的用户的数据
    // public function exportUserAnalysis()
    // {

    //     //查询所有的认证的用户
    //     $memberid = I('request.memberid', '');
    //     if ($memberid) {
    //         $where['id'] = $memberid - 10000;
    //     }

    //     $where['authorized']        = 1;
    //     $memberLists                = M('Member')->where($where)->select();
    //     list($memberLists, $allSum) = $this->countData($memberLists);
    //     $title                      = ['商户号', '总资金', '订单总额', '订单入金总额', '提现总额', '实际提现总额', '代付总额', '实际代付总额', '代付+提现总额', '代付+提现实际总额', '平台总收益'];
    //     $lists                      = [];

    //     foreach ($memberLists as $k => $v) {
    //         $lists[] = [
    //             'memberid'         => $v['memberid'],
    //             'all_balance'      => $v['all_balance'],
    //             'pay_amount'       => $v['pay_amount'],
    //             'pay_actualamount' => $v['pay_actualamount'],
    //             'tl_tkmoney'       => $v['tl_tkmoney'],
    //             'tl_money'         => $v['tl_money'],
    //             'wl_tkmoney'       => $v['wl_tkmoney'],
    //             'wl_money'         => $v['wl_money'],
    //             'tkmoney'          => $v['tkmoney'],
    //             'money'            => $v['money'],
    //             'all_poundage'     => $v['all_poundage'],
    //         ];
    //     }

    //     exportCsv($lists, $title);

    // }

    //充值排名
    public function chargeRank() {

        $successtime = urldecode(I("request.successtime", ''));
        if(!$successtime) {//默认今日
            $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
            $successtime = $_GET['successtime'] = date('Y-m-d H:i:s', $beginToday). ' | '.date('Y-m-d H:i:s', $endToday);
        }
        list($cstime, $cetime)  = explode('|', $successtime);
        $count = M()->query("SELECT count('a.*') as count FROM(SELECT pay_member.id as userid FROM `pay_order` LEFT JOIN pay_member ON (pay_member.id + 10000) = pay_order.pay_memberid WHERE `pay_successdate` BETWEEN ".strtotime($cstime)." AND ".strtotime($cetime)." AND `pay_status` > 0 AND `ddlx` = 1 GROUP BY pay_memberid)  a");
        $count = $count[0]['count'];
        $size = 50;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $where['pay_successdate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        $where['pay_status'] = ['gt',0];
        //$where['ddlx'] = 1;
        $page = new Page($count, $rows);
        $list = M('Order')
            ->join('LEFT JOIN __MEMBER__ ON (__MEMBER__.id + 10000) = __ORDER__.pay_memberid')
            ->field('pay_member.id as userid,pay_member.username,pay_member.realname,sum(pay_amount) as total_charge')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->group('pay_memberid')
            ->order('total_charge desc')
            ->select();
        if(!$_GET['p']) {
            $_GET['p'] = 1;
        }
        foreach($list as $k => $v) {
            $list[$k]['rank'] = $rows*($_GET['p']-1)+$k+1;
        }
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign('page', $page->show());
        C('TOKEN_ON', false);
        $this->display();
    }

    /*
     * 投诉保证金统计
     */
    public function complaintsDeposit()
    {
        $groupid          = I('get.groupid', 'member');
        $where['groupid'] = $groupid == 'agent' ? ['gt', '4'] : ['eq', '4'];
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $map['create_at'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Member     = M('Member');
        $count      = $Member->where($where)->count();
        $Page       = new Page($count, $rows);
        $show       = $Page->show();
        $memberList = $Member
            ->field(['id,username'])
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($memberList) {
            foreach ($memberList as $k => $v) {
                if(isset($map['status'])) {
                    unset($map['status']);
                }
                $payMemberid = $v['id'] + 10000;
                $map['user_id'] = $v['id'];
                $map['username'] = $v['username'];
                $memberList[$k]['total'] = round(M('complaints_deposit')->where($map)->sum('freeze_money'),4);
                $map['status'] = 0;
                $memberList[$k]['freeze_money'] = round(M('complaints_deposit')->where($map)->sum('freeze_money'),4);
                $map['status'] = 1;
                $memberList[$k]['unfreeze_money'] = round(M('complaints_deposit')->where($map)->sum('freeze_money'),4);
                $memberList[$k]['pay_memberid'] = $payMemberid;
            }
        }
        $this->assign('show', $show);
        $this->assign('list', $memberList);
        $this->display();

    }

    /*
    * 投诉保证金统计
    */
    public function exportComplaintsDeposit() {
        $groupid          = I('get.groupid', 'member');
        $where['groupid'] = $groupid == 'agent' ? ['gt', '4'] : ['eq', '4'];
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $map['create_at'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        $Member     = M('Member');
        $memberList = $Member
            ->field(['id,username'])
            ->where($where)
            ->select();
        if ($memberList) {
            foreach ($memberList as $k => $v) {
                if(isset($map['status'])) {
                    unset($map['status']);
                }
                $payMemberid = $v['id'] + 10000;
                $map['user_id'] = $v['id'];
                $map['username'] = $v['username'];
                $total = round(M('complaints_deposit')->where($map)->sum('freeze_money'),4);
                $map['status'] = 0;
                $freeze_money = round(M('complaints_deposit')->where($map)->sum('freeze_money'),4);
                $map['status'] = 1;
                $unfreeze_money = round(M('complaints_deposit')->where($map)->sum('freeze_money'),4);
                $list[] = array(
                    'payMemberid' => $payMemberid,
                    'total' => $total,
                    'freeze_money' => $freeze_money,
                    'unfreeze_money' => $unfreeze_money
                );
            }
        }
        $title = array(
            '商户号',
            '总保证金',
            '待解冻保证金',
            '已解冻保证金',
        );
        exportCsv($list, $title);
    }

    /*
     * 平台报表
     */
    public function platformReport() {


        $date = urldecode(I("request.date", ''));
        if(!$date) {//默认今日
            $date = date('Y-m-d');
        }
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        if($date>date('Y-m-d')) {
            $this->error('日期错误');
        }
        $timestamp = strtotime($date);
        //开始时间戳
        $begin = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp), date('Y',$timestamp));
        //结束时间戳
        $end = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp) + 1, date('Y',$timestamp)) - 1;
        $beginDate = date('Y-m-d H:i:s', $begin);
        $endDate = date('Y-m-d H:i:s', $end);
        $Member     = M('Member');
        $count      = $Member->where($where)->count();
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Page       = new Page($count, $rows);
        $show       = $Page->show();
        $data = $Member
            ->field(['id,username,balance'])
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['memberid'] = $v['id'] +10000;
                $data[$k]['username'] = $v['username'];
                //冲正金额
                $redoAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                $redoReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                //期初余额
                $data[$k]['initial_money'] = $this->getDateBalance($v['id'],$beginDate);
                //入金金额
                $orderSum = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'pay_status' => ['in', '1,2']])->sum('pay_amount');
                $data[$k]['income_money'] = $orderSum + $redoAddSum - $redoReduceSum;
                //出金待审核金额
                $payWaitCheckedSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $payWaitCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $data[$k]['pay_wait_checked'] = $payWaitCheckedSum1 + $payWaitCheckedSum2;
                //出金成功金额
                $payCheckedSum1 = M('tklist')->where(['userid'=>$v['id'], 'cldatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $payCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'], 'cldatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $data[$k]['pay_success'] = $payCheckedSum1 + $payCheckedSum2;
                //出金失败金额
                $payFailSum1 = M('tklist')->where(['userid'=>$v['id'],'cldatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $payFailSum2 = M('wttklist')->where(['userid'=>$v['id'], 'cldatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $data[$k]['pay_fail'] = $payFailSum1 + $payFailSum2;
                //入金利润
                $actualamount = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'pay_status' => ['in', '1,2']])->sum('pay_actualamount');
                //$profitSum = M('moneychange')->where(['tcuserid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>9])->sum('money');
                $data[$k]['income_profit'] = $data[$k]['income_money'] - $actualamount - $redoAddSum + $redoReduceSum;
                //出金利润
                $tkmoney1 = M('tklist')->where(['userid'=>$v['id'], 'cldatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $tkmoney2 = M('wttklist')->where(['userid'=>$v['id'], 'cldatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $money1 = M('tklist')->where(['userid'=>$v['id'], 'cldatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('money');
                $money2 = M('wttklist')->where(['userid'=>$v['id'], 'cldatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('money');
                //出金手续费
                $sxf1 = M('moneychange')->where(['userid'=>$v['id'],'lx'=>14, 'datetime'=>['between', [$beginDate, $endDate]]])->sum('money');
                $sxf2 = M('moneychange')->where(['userid'=>$v['id'],'lx'=>16, 'datetime'=>['between', [$beginDate, $endDate]]])->sum('money');
                //退回出金手续费
                $qxsxf1 = M('moneychange')->where(['userid'=>$v['id'],'lx'=>15, 'datetime'=>['between', [$beginDate, $endDate]]])->sum('money');
                $qxsxf2 = M('moneychange')->where(['userid'=>$v['id'],'lx'=>17, 'datetime'=>['between', [$beginDate, $endDate]]])->sum('money');
                //出金实际手续费
                $data[$k]['cjsxf'] = $sxf1 + $sxf2 - $qxsxf1 - $qxsxf2;
                $data[$k]['pay_profit'] = $tkmoney1 + $tkmoney2 - $money1 - $money2 + $data[$k]['cjsxf'];
                //冻结金额
                $frozen_money1 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>1, 't'=>1])->sum('money');
                $frozen_money2 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>7])->sum('money');
                $frozen_money3= M('complaints_deposit')->where(['user_id'=>$v['id'],'create_at'=>['between', [$begin, $end]]])->sum('freeze_money');
                $data[$k]['frozen_money'] = $frozen_money1 + $frozen_money2 + $frozen_money3;
                //商户实际到账金额
                $merchantProfitSum = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>9])->sum('money');
                $data[$k]['merchant_money'] = $actualamount + $merchantProfitSum + $redoAddSum - $redoReduceSum;
                //平台成本
                $cost1 = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'pay_status' => ['in', '1,2']])->sum('cost');
                $cost2 = M('wttklist')->where(['userid'=>$v['id'],'cldatetime' => ['between', [$begin, $end]], 'status' => 2])->sum('cost');
                $cost3 = M('moneychange')->where(['tcuserid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>9])->sum('money');
                $data[$k]['platform_cost'] = $cost1 + $cost2 + $cost3;
                //平台利润
                $data[$k]['platform_profit'] = $data[$k]['income_profit'] +  $data[$k]['pay_profit'] - $data[$k]['platform_cost'] + $data['cjsxf'];
                //期末余额
                $data[$k]['end_profit'] = $this->getDateBalance($v['id'],$endDate);
                //当前余额
                $data[$k]['current_money'] = $v['balance'];
                foreach($data[$k] as $kk => $vv) {
                    if($kk !='memberid' && $kk != 'username' ) {
                        $data[$k][$kk] = number_format($vv, 4, '.', ',');
                    }
                }
            }
        }
        $platform_profit_all = $this->getPlatformProfit();
        $this->assign('platform_profit_all', $platform_profit_all);
        $this->assign('page', $show);
        $this->assign('date', $date);
        $this->assign('list', $data);
        $this->display();
    }

    /*
     * 商户报表
     */
    public function merchantReport() {

        $date = urldecode(I("request.date", ''));
        if(!$date) {//默认今日
            $date = date('Y-m-d');
        }
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        if($date>date('Y-m-d')) {
            $this->error('日期错误');
        }
        $timestamp = strtotime($date);
        //开始时间戳
        $begin = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp), date('Y',$timestamp));
        //结束时间戳
        $end = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp) + 1, date('Y',$timestamp)) - 1;
        $beginDate = date('Y-m-d H:i:s', $begin);
        $endDate = date('Y-m-d H:i:s', $end);
        $Member     = M('Member');
        $count      = $Member->where($where)->count();
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Page       = new Page($count, $rows);
        $show       = $Page->show();
        $where['groupid'] = 4;
        $data = $Member
            ->field(['id,parentid,username,balance'])
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['memberid'] = $v['id'] +10000;
                $data[$k]['username'] = $v['username'];
                //冲正金额
                $redoAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                $redoReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                //入金通道费率
                $data[$k]['channel_rate'] = M('ProductUser')
                    ->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id = __PRODUCT_USER__.pid')
                    ->where(['pay_product_user.userid'=>$v['id'],'pay_product_user.status'=>1,'pay_product.isdisplay'=>1])
                    ->field('pay_product.name,pay_product.id,pay_product.code,pay_product_user.status')
                    ->select();

                foreach ($data[$k]['channel_rate'] as $key=>$item){
                    $feilv = M('Userrate')->where(['userid'=>$v['id'],'payapiid'=>$item['id']])->getField('feilv');
                    $data[$k]['channel_rate'][$key]['feilv'] = $feilv;
                }
                //期初余额
                $data[$k]['initial_money'] = $this->getDateBalance($v['id'],$beginDate);
                //入金金额
                $orderSum = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'pay_status' => ['in', '1,2']])->sum('pay_amount');
                $profitSum = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>9])->sum('money');
                $data[$k]['income_money'] = $orderSum + $profitSum + $redoAddSum - $redoReduceSum;
                //出金待审核金额
                $payWaitCheckedSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $payWaitCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $data[$k]['pay_wait_checked'] = $payWaitCheckedSum1 + $payWaitCheckedSum2;
                //出金成功金额
                $payCheckedSum1 = M('tklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $payCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $data[$k]['pay_success'] = $payCheckedSum1 + $payCheckedSum2;
                //出金失败金额
                $payFailSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $payFailSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $data[$k]['pay_fail'] = $payFailSum1 + $payFailSum2;
                //出金手续费
                $tkConfig     = M('Tikuanconfig')->where(['userid' => $v['id'], 'tkzt' => 1])->find();
                if (!$tkConfig || $tkConfig['tkzt'] != 1) {
                    $tkConfig = M('Tikuanconfig')->where(['issystem' => 1])->find();
                }
                if($tkConfig['tktype'] == 1) {
                    $data[$k]['tksxf'] = $tkConfig['sxffixed'].'元/单笔';
                } else {
                    $data[$k]['tksxf'] = $tkConfig['sxfrate'].'%';
                }
                //冻结金额
                $frozen_money1 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>1, 't'=>1])->sum('money');
                $frozen_money2 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>7])->sum('money');
                $frozen_money3= M('complaints_deposit')->where(['user_id'=>$v['id'],'create_at'=>['between', [$begin, $end]]])->sum('freeze_money');
                $data[$k]['frozen_money'] = $frozen_money1 + $frozen_money2 + $frozen_money3;
                //商户实际到账金额
                $actualamount = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'status' => ['in', '1,2']])->sum('pay_actualamount');
                $orderSum = $actualamount + $profitSum + $redoAddSum - $redoReduceSum;
                $data[$k]['merchant_money'] = $orderSum;
                //期末余额
                $data[$k]['end_profit'] = $this->getDateBalance($v['id'],$endDate);
                //当前余额
                $data[$k]['current_money'] = $this->fans['balance'];
                foreach($data[$k] as $kk => $vv) {
                    if($kk !='memberid' && $kk != 'channel_rate' && $kk != 'username' && $kk != 'tksxf') {
                        $data[$k][$kk] = number_format($vv, 4, '.', ',');
                    }
                }
                if($v['parentid'] > 0) {
                    $data[$k]['parent'] = M('Member')->where(['id'=>$v['parentid']])->getField('username');
                } else {
                    $data[$k]['parent'] = '';
                }
            }
        }
        $this->assign('page', $show);
        $this->assign('date', $date);
        $this->assign('list', $data);
        $this->display();
    }

    /*
    * 代理报表
    */
    public function agentReport() {
        $date = urldecode(I("request.date", ''));
        if(!$date) {//默认今日
            $date = date('Y-m-d');
        }
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        if($date>date('Y-m-d')) {
            $this->error('日期错误');
        }
        $timestamp = strtotime($date);
        //开始时间戳
        $begin = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp), date('Y',$timestamp));
        //结束时间戳
        $end = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp) + 1, date('Y',$timestamp)) - 1;
        $beginDate = date('Y-m-d H:i:s', $begin);
        $endDate = date('Y-m-d H:i:s', $end);
        $Member     = M('Member');
        $count      = $Member->where($where)->count();
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Page       = new Page($count, $rows);
        $show       = $Page->show();
        $where['groupid'] = ['gt', 4];
        $data = $Member
            ->field(['id,parentid,username,balance'])
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['memberid'] = $v['id'] +10000;
                $data[$k]['username'] = $v['username'];
                //冲正金额
                $redoAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                $redoReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                //入金通道费率
                $data[$k]['channel_rate'] = M('ProductUser')
                    ->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id = __PRODUCT_USER__.pid')
                    ->where(['pay_product_user.userid'=>$v['id'],'pay_product_user.status'=>1,'pay_product.isdisplay'=>1])
                    ->field('pay_product.name,pay_product.id,pay_product.code,pay_product_user.status')
                    ->select();

                foreach ($data[$k]['channel_rate'] as $key=>$item){
                    $feilv = M('Userrate')->where(['userid'=>$v['id'],'payapiid'=>$item['id']])->getField('feilv');
                    $data[$k]['channel_rate'][$key]['feilv'] = $feilv;
                }
                //期初余额
                $data[$k]['initial_money'] = $this->getDateBalance($v['id'], $date);
                //代理利润
                $profit = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>9])->sum('money');
                $data[$k]['income_money'] =  $profit + $redoAddSum - $redoReduceSum;
                //出金待审核金额
                $payWaitCheckedSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $payWaitCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $data[$k]['pay_wait_checked'] = $payWaitCheckedSum1 + $payWaitCheckedSum2;
                //出金成功金额
                $payCheckedSum1 = M('tklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $payCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $data[$k]['pay_success'] = $payCheckedSum1 + $payCheckedSum2;
                //出金失败金额
                $payFailSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $payFailSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $data[$k]['pay_fail'] = $payFailSum1 + $payFailSum2;
                //出金手续费
                $tkConfig     = M('Tikuanconfig')->where(['userid' => $v['id'], 'tkzt' => 1])->find();
                if (!$tkConfig || $tkConfig['tkzt'] != 1) {
                    $tkConfig = M('Tikuanconfig')->where(['issystem' => 1])->find();
                }
                if($tkConfig['tktype'] == 1) {
                    $data[$k]['tksxf'] = $tkConfig['sxffixed'].'元/单笔';
                } else {
                    $data[$k]['tksxf'] = $tkConfig['sxfrate'].'%';
                }
                //冻结金额
                $frozen_money1 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>1, 't'=>1])->sum('money');
                $frozen_money2 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>7])->sum('money');
                $frozen_money3= M('complaints_deposit')->where(['user_id'=>$v['id'],'create_at'=>['between', [$begin, $end]]])->sum('freeze_money');
                $data[$k]['frozen_money'] = $frozen_money1 + $frozen_money2 + $frozen_money3;
                //代理实际到账金额
                $data[$k]['merchant_money'] = $data[$k]['income_money'];
                //期末余额
                $data[$k]['end_profit'] = $this->getDateBalance($v['id'],$endDate);
                //当前余额
                $data[$k]['current_money'] = $this->fans['balance'];
                foreach($data[$k] as $kk => $vv) {
                    if($kk !='memberid' && $kk != 'channel_rate' && $kk != 'username' && $kk != 'tksxf') {
                        $data[$k][$kk] = number_format($vv, 4, '.', ',');
                    }
                }
                if($v['parentid'] > 0) {
                    $data[$k]['parent'] = M('Member')->where(['id'=>$v['parentid']])->getField('username');
                } else {
                    $data[$k]['parent'] = '';
                }
            }
        }
        $this->assign('page', $show);
        $this->assign('date', $date);
        $this->assign('list', $data);
        $this->display();
    }

    /*
     * 获取初期余额
     */
    private function getAllMoney($date) {

        $money = 0;
        $lists = M('Member')->field('id')->select();
        foreach($lists as $v) {
            $money += $this->getUserBalance($v['id'], $date) ;
        }
        return $money;
    }


    /*
     * 根据日期获取用户余额
     */
    private function getUserBalance($userid, $date) {

        $money = M('Moneychange')->where(['userid'=>$userid, 'datetime'=>array('elt', $date), 't'=>['neq', 1], 'lx' => ['not in', '3,4']])->order('datetime DESC')->getField('gmoney');
        if(empty($money)) {
            $money = 0;
        }
        return $money;
    }

    /*
   * 根据日期获取用户期初余额
   */
    private function getDateBalance($userid, $date) {

        $log = M('Moneychange')->where(['userid'=>$userid, 'datetime'=>array('elt', $date), 't'=>['neq', 1], 'lx' => ['not in', '3,4']])->order('datetime DESC,id DESC')->find();
        if(empty($log)) {
            $money = 0;
        } else {
            $yesterdayTime = date("Y-m-d 00:00:00",strtotime($date)-1);
            $yesterdayRedAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$userid,'date'=>$yesterdayTime, 'ctime'=>['gt', strtotime($log['datetime'])]])->sum('money');
            $lastlog = M('Moneychange')->where(['userid'=>$userid, 'datetime'=>array('elt', $date), 't'=>['neq', 1]])->order('datetime DESC,id DESC')->find();
            if($lastlog['lx'] == 3 || $lastlog['lx'] == 4) {
                $money = $lastlog['gmoney'];
            } else {
                $yesterdayRedReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$userid,'date'=>$yesterdayTime, 'ctime'=>['gt', strtotime($log['datetime'])]])->sum('money');
                $money = $log['gmoney'] + $yesterdayRedAddSum - $yesterdayRedReduceSum + 0;
            }
        }
        return $money;
    }

    /*
     * 冻结资金统计
     */
    public function frozenMoney()
    {
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $map['create_at'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
        }
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }
        $Member     = M('Member');
        $count      = $Member->where($where)->count();
        $Page       = new Page($count, $rows);
        $show       = $Page->show();
        $memberList = $Member
            ->field(['id'])
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($memberList) {
            foreach ($memberList as $k => $v) {
                if(isset($map['status'])) {
                    unset($map['status']);
                }
                $payMemberid = $v['id'] + 10000;
                $memberList[$k]['pay_memberid'] = $payMemberid;
                //T+1金额待解冻
                $memberList[$k]['t1_freeze_money'] = M('blockedlog')->where(['userid'=>$v['id'], 'status'=>0])->sum('amount');
                //T+1金额已解冻
                $memberList[$k]['t1_unfreeze_money'] = M('blockedlog')->where(['userid'=>$v['id'], 'status'=>1])->sum('amount');
                //手动冻结金额待解冻
                $memberList[$k]['handle_frozen_money'] = M('auto_unfrozen_order')->where(['user_id'=>$v['id'], 'status'=>0])->sum('freeze_money');
                //手动冻结金额已解冻
                $memberList[$k]['handle_unfrozen_money'] = M('auto_unfrozen_order')->where(['user_id'=>$v['id'], 'status'=>0])->sum('freeze_money');
                //投诉保证金待解冻
                $memberList[$k]['complaints_deposit_freeze_money'] = M('complaints_deposit')->where(['user_id'=>$v['id'],'status'=>0])->sum('freeze_money');
                //投诉保证金已解冻
                $memberList[$k]['complaints_deposit_unfreeze_money'] = M('complaints_deposit')->where(['user_id'=>$v['id'],'status'=>1])->sum('freeze_money');
                //总待解冻金额
                $memberList[$k]['total_freeze_money'] = $memberList[$k]['t1_freeze_money'] + $memberList[$k]['handle_frozen_money'] + $memberList[$k]['complaints_deposit_freeze_money'];
                //总已解冻金额
                $memberList[$k]['total_unfreeze_money'] = $memberList[$k]['t1_unfreeze_money'] + $memberList[$k]['handle_unfrozen_money'] + $memberList[$k]['complaints_deposit_unfreeze_money'];

                foreach($memberList[$k] as $kk => $vv) {
                    $memberList[$k][$kk] += 0;
                }
            }
        }
        $this->assign('show', $show);
        $this->assign('list', $memberList);
        $this->display();

    }


    //导出平台报表
    public function exportPlatform(){
        $date = urldecode(I("request.date", ''));
        if(!$date) {//默认今日
            $date = date('Y-m-d');
        }
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        if($date>date('Y-m-d')) {
            $this->error('日期错误');
        }
        $timestamp = strtotime($date);
        //开始时间戳
        $begin = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp), date('Y',$timestamp));
        //结束时间戳
        $end = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp) + 1, date('Y',$timestamp)) - 1;
        $beginDate = date('Y-m-d H:i:s', $begin);
        $endDate = date('Y-m-d H:i:s', $end);
        $Member     = M('Member');
        $data = $Member
            ->field(['id,username,balance'])
            ->where($where)
            ->select();
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['memberid'] = $v['id'] +10000;
                $data[$k]['username'] = $v['username'];
                //冲正金额
                $redoAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                $redoReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                //期初余额
                $data[$k]['initial_money'] = $this->getDateBalance($v['id'],$beginDate);
                //入金金额
                $orderSum = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'pay_status' => ['in', '1,2']])->sum('pay_amount');
                $data[$k]['income_money'] = $orderSum + $redoAddSum - $redoReduceSum;
                //出金待审核金额
                $payWaitCheckedSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $payWaitCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $data[$k]['pay_wait_checked'] = $payWaitCheckedSum1 + $payWaitCheckedSum2;
                //出金成功金额
                $payCheckedSum1 = M('tklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $payCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $data[$k]['pay_success'] = $payCheckedSum1 + $payCheckedSum2;
                //出金失败金额
                $payFailSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $payFailSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $data[$k]['pay_fail'] = $payFailSum1 + $payFailSum2;
                //入金利润
                $actualamount = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'pay_status' => ['in', '1,2']])->sum('pay_actualamount');
                $profitSum = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>9])->sum('money');
                $data[$k]['income_profit'] = $data[$k]['income_money'] - $actualamount - $redoAddSum + $redoReduceSum - $profitSum;
                //出金利润
                $tkmoney1 = M('tklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $tkmoney2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $money1 = M('tklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('money');
                $money2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('money');
                //出金手续费
                $sxf1 = M('moneychange')->where(['userid'=>$v['id'],'lx'=>14, 'datetime'=>['between', [$beginDate, $endDate]]])->sum('money');
                $sxf2 = M('moneychange')->where(['userid'=>$v['id'],'lx'=>16, 'datetime'=>['between', [$beginDate, $endDate]]])->sum('money');
                //退回出金手续费
                $qxsxf1 = M('moneychange')->where(['userid'=>$v['id'],'lx'=>15, 'datetime'=>['between', [$beginDate, $endDate]]])->sum('money');
                $qxsxf2 = M('moneychange')->where(['userid'=>$v['id'],'lx'=>17, 'datetime'=>['between', [$beginDate, $endDate]]])->sum('money');
                //出金实际手续费
                $data[$k]['cjsxf'] = $sxf1 + $sxf2 - $qxsxf1 - $qxsxf2;
                $data[$k]['pay_profit'] = $tkmoney1 + $tkmoney2 - $money1 - $money2 + $data[$k]['cjsxf'];
                //冻结金额
                $frozen_money1 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>1, 't'=>1])->sum('money');
                $frozen_money2 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>7])->sum('money');
                $frozen_money3= M('complaints_deposit')->where(['user_id'=>$v['id'],'create_at'=>['between', [$begin, $end]]])->sum('freeze_money');
                $data[$k]['frozen_money'] = $frozen_money1 + $frozen_money2 + $frozen_money3;
                //商户实际到账金额
                $data[$k]['merchant_money'] = $actualamount + $profitSum + $redoAddSum - $redoReduceSum;
                //平台成本
                $cost1 = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'pay_status' => ['in', '1,2']])->sum('cost');
                $cost2 = M('wttklist')->where(['userid'=>$v['id'],'cldatetime' => ['between', [$begin, $end]], 'status' => 2])->sum('cost');
                $cost3 = M('moneychange')->where(['tcuserid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>9])->sum('money');
                $data[$k]['platform_cost'] = $cost1 + $cost2 + $cost3;
                //平台利润
                $data[$k]['platform_profit'] = $data[$k]['income_profit'] +  $data[$k]['pay_profit'] - $data[$k]['platform_cost'];
                //期末余额
                $data[$k]['end_profit'] = $this->getDateBalance($v['id'],$endDate);
                //当前余额
                $data[$k]['current_money'] = $v['balance'];
                foreach($data[$k] as $kk => $vv) {
                    if($kk !='memberid' && $kk != 'username' ) {
                        $data[$k][$kk] = number_format($vv, 4, '.', ',');
                    }
                }
            }
            foreach ($data as $m => $n){
                $list[] = array(
                    'memberid'=>$n['memberid'],
                    'username'=>$n['username'],
                    'initial_money'=>$n['initial_money'],
                    'income_money'=>$n['income_money'],
                    'pay_wait_checked'=>$n['pay_wait_checked'],
                    'pay_success'=>$n['pay_success'],
                    'pay_fail'=>$n['pay_fail'],
                    'cjsxf'=>$n['cjsxf'],
                    'income_profit'=>$n['income_profit'],
                    'pay_profit'=>$n['pay_profit'],
                    'frozen_money'=>$n['frozen_money'],
                    'merchant_money'=>$n['merchant_money'],
                    'platform_cost'=>$n['platform_cost'],
                    'platform_profit'=>$n['platform_profit'],
                    'end_profit'=>$n['end_profit'],
                    'current_money'=>$n['current_money'],
                );
            }
        }
        $title = array(
            '商户号',
            '用户名',
            '期初余额',
            '入金余额',
            '出金待审核金额',
            '出金成功金额',
            '出金失败金额',
            '入金利润',
            '出金利润',
            '冻结金额',
            '商户实际到账金额',
            '平台成本',
            '平台利润',
            '期末余额',
            '当前余额'
        );
        exportCsv($list, $title);
    }

    //导出商户报表
    public function exportMerchant(){
        $date = urldecode(I("request.date", ''));
        if(!$date) {//默认今日
            $date = date('Y-m-d');
        }
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        if($date>date('Y-m-d')) {
            $this->error('日期错误');
        }
        $timestamp = strtotime($date);
        //开始时间戳
        $begin = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp), date('Y',$timestamp));
        //结束时间戳
        $end = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp) + 1, date('Y',$timestamp)) - 1;
        $beginDate = date('Y-m-d H:i:s', $begin);
        $endDate = date('Y-m-d H:i:s', $end);
        $Member     = M('Member');
        $where['groupid'] = 4;
        $data = $Member
            ->field(['id,username,balance'])
            ->where($where)
            ->select();
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['memberid'] = $v['id'] +10000;
                $data[$k]['username'] = $v['username'];
                //冲正金额
                $redoAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                $redoReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                //入金通道费率
                $data[$k]['channel_rate'] = M('ProductUser')
                    ->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id = __PRODUCT_USER__.pid')
                    ->where(['pay_product_user.userid'=>$v['id'],'pay_product_user.status'=>1,'pay_product.isdisplay'=>1])
                    ->field('pay_product.name,pay_product.id,pay_product.code,pay_product_user.status')
                    ->select();

                foreach ($data[$k]['channel_rate'] as $key=>$item){
                    $feilv = M('Userrate')->where(['userid'=>$v['id'],'payapiid'=>$item['id']])->getField('feilv');
                    $data[$k]['channel_rate'][$key]['feilv'] = $feilv;
                }
                //期初余额
                $data[$k]['initial_money'] = $this->getDateBalance($v['id'],$beginDate);
                //入金金额
                $orderSum = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'pay_status' => ['in', '1,2']])->sum('pay_amount');
                $profitSum = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>9])->sum('money');
                $data[$k]['income_money'] = $orderSum + $profitSum + $redoAddSum - $redoReduceSum;
                //出金待审核金额
                $payWaitCheckedSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $payWaitCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $data[$k]['pay_wait_checked'] = $payWaitCheckedSum1 + $payWaitCheckedSum2;
                //出金成功金额
                $payCheckedSum1 = M('tklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $payCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $data[$k]['pay_success'] = $payCheckedSum1 + $payCheckedSum2;
                //出金失败金额
                $payFailSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $payFailSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $data[$k]['pay_fail'] = $payFailSum1 + $payFailSum2;
                //出金手续费
                $tkConfig     = M('Tikuanconfig')->where(['userid' => $v['id'], 'tkzt' => 1])->find();
                if (!$tkConfig || $tkConfig['tkzt'] != 1) {
                    $tkConfig = M('Tikuanconfig')->where(['issystem' => 1])->find();
                }
                if($tkConfig['tktype'] == 1) {
                    $data[$k]['tksxf'] = $tkConfig['sxffixed'].'元/单笔';
                } else {
                    $data[$k]['tksxf'] = $tkConfig['sxfrate'].'%';
                }
                //冻结金额
                $frozen_money1 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>1, 't'=>1])->sum('money');
                $frozen_money2 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>7])->sum('money');
                $frozen_money3= M('complaints_deposit')->where(['user_id'=>$v['id'],'create_at'=>['between', [$begin, $end]]])->sum('freeze_money');
                $data[$k]['frozen_money'] = $frozen_money1 + $frozen_money2 + $frozen_money3;
                //商户实际到账金额
                $actualamount = M('Order')->where(['pay_memberid'=>10000+$v['id'],'pay_successdate' => ['between', [$begin, $end]], 'status' => ['in', '1,2']])->sum('pay_actualamount');
                $orderSum = $actualamount + $profitSum + $redoAddSum - $redoReduceSum;
                $data[$k]['merchant_money'] = $orderSum;
                //期末余额
                $data[$k]['end_profit'] = $this->getDateBalance($v['id'],$endDate);
                //当前余额
                $data[$k]['current_money'] = $this->fans['balance'];
                foreach($data[$k] as $kk => $vv) {
                    if($kk !='memberid' && $kk != 'channel_rate' && $kk != 'username' && $kk != 'tksxf') {
                        $data[$k][$kk] = number_format($vv, 4, '.', ',');
                    }
                }
                if($v['parentid'] > 0) {
                    $data[$k]['parent'] = M('Member')->where(['id'=>$v['parentid']])->getField('username');
                } else {
                    $data[$k]['parent'] = '';
                }
            }
            foreach ($data as $m => $n){
                $channel_rate = '';
                if(empty($n['channel_rate'])){
                    $channel_rate .= '-';
                }else{
                    foreach ($n['channel_rate'] as $o => $p){
                        $feilv = $p['feilv']*1000;
                        $channel_rate .= $p['name'].':'.$feilv.'‰；';
                    }
                }
                $list[] = array(
                    'memberid'=>$n['memberid'],
                    'username'=>$n['username'],
                    'initial_money'=>$n['initial_money'],
                    'channel_rate'=>$channel_rate,
                    'income_money'=>$n['income_money'],
                    'tksxf'=>$n['tksxf'],
                    'pay_wait_checked'=>$n['pay_wait_checked'],
                    'pay_success'=>$n['pay_success'],
                    'pay_fail'=>$n['pay_fail'],
                    'frozen_money'=>$n['frozen_money'],
                    'merchant_money'=>$n['merchant_money'],
                    'end_profit'=>$n['end_profit'],
                    'current_money'=>$n['current_money'],
                    'partent'=>$n['partent'],
                );
            }
        }

        $title = array(
            '商户号',
            '用户名',
            '期初余额',
            '通道费率',
            '入金金额',
            '出金手续费',
            '出金待审核金额',
            '出金成功金额',
            '出金失败金额',
            '冻结金额',
            '商户实际到账金额',
            '期末余额',
            '当前余额',
            '上级代理',
        );
        exportCsv($list, $title);
    }

    //导出代理报表
    public function exportAgent(){
        $date = urldecode(I("request.date", ''));
        if(!$date) {//默认今日
            $date = date('Y-m-d');
        }
        if ($memberid = I('get.memberid', '')) {
            $where['id'] = $memberid - 10000;
        }
        if($date>date('Y-m-d')) {
            $this->error('日期错误');
        }
        $timestamp = strtotime($date);
        //开始时间戳
        $begin = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp), date('Y',$timestamp));
        //结束时间戳
        $end = mktime(0, 0, 0, date('m',$timestamp), date('d',$timestamp) + 1, date('Y',$timestamp)) - 1;
        $beginDate = date('Y-m-d H:i:s', $begin);
        $endDate = date('Y-m-d H:i:s', $end);
        $Member     = M('Member');
        $where['groupid'] = ['gt', 4];
        $data = $Member
            ->field(['id,username,balance'])
            ->where($where)
            ->select();
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['memberid'] = $v['id'] +10000;
                $data[$k]['username'] = $v['username'];
                //冲正金额
                $redoAddSum = M('redo_order')->where(['type'=>1,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                $redoReduceSum = M('redo_order')->where(['type'=>2,'user_id'=>$v['id'],'date'=>['between', [$beginDate, $endDate]]])->sum('money');
                //入金通道费率
                $data[$k]['channel_rate'] = M('ProductUser')
                    ->join('LEFT JOIN __PRODUCT__ ON __PRODUCT__.id = __PRODUCT_USER__.pid')
                    ->where(['pay_product_user.userid'=>$v['id'],'pay_product_user.status'=>1,'pay_product.isdisplay'=>1])
                    ->field('pay_product.name,pay_product.id,pay_product.code,pay_product_user.status')
                    ->select();

                foreach ($data[$k]['channel_rate'] as $key=>$item){
                    $feilv = M('Userrate')->where(['userid'=>$v['id'],'payapiid'=>$item['id']])->getField('feilv');
                    $data[$k]['channel_rate'][$key]['feilv'] = $feilv;
                }
                //期初余额
                $data[$k]['initial_money'] = $this->getDateBalance($v['id'], $date);
                //代理利润
                $profit = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>9])->sum('money');
                $data[$k]['income_money'] =  $profit + $redoAddSum - $redoReduceSum;
                //出金待审核金额
                $payWaitCheckedSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $payWaitCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','0,1']])->sum('tkmoney');
                $data[$k]['pay_wait_checked'] = $payWaitCheckedSum1 + $payWaitCheckedSum2;
                //出金成功金额
                $payCheckedSum1 = M('tklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $payCheckedSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>2])->sum('tkmoney');
                $data[$k]['pay_success'] = $payCheckedSum1 + $payCheckedSum2;
                //出金失败金额
                $payFailSum1 = M('tklist')->where(['userid'=>$v['id'],'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $payFailSum2 = M('wttklist')->where(['userid'=>$v['id'], 'sqdatetime'=>['between', [$beginDate, $endDate]],'status'=>['in','3,4']])->sum('tkmoney');
                $data[$k]['pay_fail'] = $payFailSum1 + $payFailSum2;
                //出金手续费
                $tkConfig     = M('Tikuanconfig')->where(['userid' => $v['id'], 'tkzt' => 1])->find();
                if (!$tkConfig || $tkConfig['tkzt'] != 1) {
                    $tkConfig = M('Tikuanconfig')->where(['issystem' => 1])->find();
                }
                if($tkConfig['tktype'] == 1) {
                    $data[$k]['tksxf'] = $tkConfig['sxffixed'].'元/单笔';
                } else {
                    $data[$k]['tksxf'] = $tkConfig['sxfrate'].'%';
                }
                //冻结金额
                $frozen_money1 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>1, 't'=>1])->sum('money');
                $frozen_money2 = M('moneychange')->where(['userid'=>$v['id'],'datetime'=>['between', [$beginDate, $endDate]],'lx'=>7])->sum('money');
                $frozen_money3= M('complaints_deposit')->where(['user_id'=>$v['id'],'create_at'=>['between', [$begin, $end]]])->sum('freeze_money');
                $data[$k]['frozen_money'] = $frozen_money1 + $frozen_money2 + $frozen_money3;
                //代理实际到账金额
                $data[$k]['merchant_money'] = $data[$k]['income_money'];
                //期末余额
                $data[$k]['end_profit'] = $this->getDateBalance($v['id'],$endDate);
                //当前余额
                $data[$k]['current_money'] = $this->fans['balance'];
                foreach($data[$k] as $kk => $vv) {
                    if($kk !='memberid' && $kk != 'channel_rate' && $kk != 'username' && $kk != 'tksxf') {
                        $data[$k][$kk] = number_format($vv, 4, '.', ',');
                    }
                }
                if($v['parentid'] > 0) {
                    $data[$k]['parent'] = M('Member')->where(['id'=>$v['parentid']])->getField('username');
                } else {
                    $data[$k]['parent'] = '';
                }
            }

            foreach ($data as $m => $n){
                $channel_rate = '';
                if(empty($n['channel_rate'])){
                    $channel_rate .= '-';
                }else{
                    foreach ($n['channel_rate'] as $o => $p){
                        $feilv = $p['feilv']*1000;
                        $channel_rate .= $p['name'].':'.$feilv.'‰；';
                    }
                }
                $list[] = array(
                    'memberid'=>$n['memberid'],
                    'username'=>$n['username'],
                    'initial_money'=>$n['initial_money'],
                    'channel_rate'=>$channel_rate,
                    'income_money'=>$n['income_money'],
                    'tksxf'=>$n['tksxf'],
                    'pay_wait_checked'=>$n['pay_wait_checked'],
                    'pay_success'=>$n['pay_success'],
                    'pay_fail'=>$n['pay_fail'],
                    'frozen_money'=>$n['frozen_money'],
                    'merchant_money'=>$n['merchant_money'],
                    'end_profit'=>$n['end_profit'],
                    'current_money'=>$n['current_money'],
                    'partent'=>$n['partent'],
                );
            }
        }

        $title = array(
            '代理号',
            '用户名',
            '期初余额',
            '通道费率',
            '代理利润',
            '出金手续费',
            '出金待审核金额',
            '出金成功金额',
            '出金失败金额',
            '冻结金额',
            '商户实际到账金额',
            '期末余额',
            '当前余额',
            '上级代理',
        );
        exportCsv($list, $title);
    }

    //获取平台利润
    public function getPlatformProfit() {
        //冲正金额
        $redoAddSum = M('redo_order')->where(['type'=>1])->sum('money');
        $redoReduceSum = M('redo_order')->where(['type'=>2])->sum('money');
        //入金金额
        $orderSum = M('Order')->where(['pay_status' => ['in', '1,2']])->sum('pay_amount');
        $data['income_money'] = $orderSum + $redoAddSum - $redoReduceSum;

        //入金利润
        $actualamount = M('Order')->where(['pay_status' => ['in', '1,2']])->sum('pay_actualamount');
        $data['income_profit'] = $data['income_money'] - $actualamount - $redoAddSum + $redoReduceSum;
        //出金利润
        $tkmoney1 = M('tklist')->where(['status'=>2])->sum('tkmoney');
        $tkmoney2 = M('wttklist')->where(['status'=>2])->sum('tkmoney');
        $money1 = M('tklist')->where(['status'=>2])->sum('money');
        $money2 = M('wttklist')->where(['status'=>2])->sum('money');
        $data['pay_profit'] = $tkmoney1 + $tkmoney2 - $money1 - $money2;
        //平台成本
        $cost1 = M('Order')->where(['pay_status' => ['in', '1,2']])->sum('cost');
        $cost2 = M('wttklist')->where([ 'status' => 2])->sum('cost');
        $cost3 = M('moneychange')->where(['lx'=>9])->sum('money');
        $data['platform_cost'] = $cost1 + $cost2 + $cost3;
        //平台利润
        $platform_profit = $data['income_profit'] +  $data['pay_profit'] - $data['platform_cost'];
        return number_format($platform_profit, 4, '.', ',');
    }
}
