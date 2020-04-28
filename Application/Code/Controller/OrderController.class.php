<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-08-22
 * Time: 14:34
 */
namespace Code\Controller;

use Think\Page;

/**
 * 订单管理控制器
 * Class OrderController
 * @package User\Controller
 */
class OrderController extends UserController
{

    public function __construct()
    {
        parent::__construct();
        $this->assign("Public", MODULE_NAME); // 模块名称
    }

    public function index()
    {

        //银行
        $tongdaolist = M("Channel")->field('id,code,title')->select();
        $this->assign("tongdaolist", $tongdaolist);

        //通道
        $banklist = M("Product")->field('id,name,code')->select();
        $this->assign("banklist", $banklist);

        $where    = array();
        $where['O.owner_id'] = $this->fans['uid'];
        $memberid = I("request.memberid");
        if ($memberid) {
            $where['O.pay_memberid'] = array('eq', $memberid);
            $todaysumMap['pay_memberid'] =  $monthsumMap['pay_memberid'] = $nopaidsumMap['pay_memberid'] =  $monthNopaidsumMap['pay_memberid'] = array('eq', $memberid);
            $profitMap['userid'] = $profitSumMap['userid']= $memberid-10000;
        }
        $this->assign('memberid', $memberid);
        $orderid = I("request.orderid");
        if ($orderid) {
            $where['O.out_trade_id'] = $orderid;
        }
        $this->assign('orderid', $orderid);
        $ddlx = I("request.ddlx", "");
        if ($ddlx != "") {
            $where['O.ddlx'] = array('eq', $ddlx);
        }
        $this->assign('ddlx', $ddlx);
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['O.channel_id'] = array('eq', $tongdao);
        }
        $this->assign('tongdao', $tongdao);
        $bank = I("request.bank", '', 'strip_tags');
        if ($bank) {
            $where['O.pay_bankcode'] = array('eq', $bank);
        }
        $this->assign('bank', $bank);
        $payOrderid = I('get.payorderid', '');

        // exit;
        if ($payOrderid) {
            $where['O.pay_orderid'] = array('eq', $payOrderid);
            $profitMap['transid'] = $payOrderid;
        }
        $this->assign('payOrderid',$payOrderid);
        $body = I("request.body", '', 'strip_tags');
        if ($body) {
            $where['O.pay_productname'] = array('eq', $body);
        }
        $this->assign('body', $body);
        $account_id = I("request.account_id");
        if ($account_id) {
            $where['O.account_id'] = array('eq', $account_id);
        }
        $this->assign('account_id', $account_id);
        $status = I("request.status");
        if ($status != "") {
            if ($status == '1or2') {
                $where['O.pay_status'] = array('between', array('1', '2'));
            } else {
                $where['O.pay_status'] = array('eq', $status);
            }
        }
        $this->assign('status', $status);

        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime, $cetime)  = explode('|', $createtime);
            $where['O.pay_applydate'] = ['between', [strtotime($cstime), strtotime($cetime) ? strtotime($cetime) : time()]];
            $profitMap['datetime'] = ['between', [$cstime, $cetime ? $cetime : date('Y-m-d H:i:s')]];
        }
        $this->assign('createtime', $createtime);
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime, $setime)    = explode('|', $successtime);
            $where['O.pay_successdate'] = ['between', [strtotime($sstime), strtotime($setime) ? strtotime($setime) : time()]];
            $profitMap['datetime'] = ['between', [$sstime, $setime ? $setime : date('Y-m-d H:i:s')]];
        }
        $this->assign('successtime', $successtime);
        $count = M('Order')->alias('as O')->where($where)->count();

        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }

        $page = new Page($count, $rows);
        $list = M('Order')->alias('as O')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->order('id desc')
            ->select();

        //查询支付成功的订单的手续费，入金费，总额总和
        $countWhere               = $where;
        $countWhere['O.pay_status'] = ['between', [1, 2]];
        $field                    = ['sum(`pay_amount`) pay_amount','sum(`cost`) cost', 'sum(`pay_poundage`) pay_poundage', 'sum(`pay_actualamount`) pay_actualamount', 'count(`id`) success_count'];
        $sum                      = M('Order')->alias('as O')->field($field)->where($countWhere)->find();
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
        $sum['memberprofit'] = M('moneychange')->where($profitMap)->sum('money');
       
        $sum['pay_poundage'] = $sum['pay_poundage'] - $sum['cost'] - $sum['memberprofit'];//原始
        foreach ($sum as $k => $v) {
            $sum[$k] += 0;
           $sum[$k] = number_format($sum[$k],2,'.','');
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
        if ($successtime) {
            $pstartTime = strtotime($sstime);
            $pendTime   = strtotime($setime) ? strtotime($setime) : time();
            $is_month   = $pendTime - $pstartTime > self::TMT ? true : false;
        }

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
        if ($status == '1or2' || $status == 1 || $status == 2) {
            //今日成功交易总额
            $todayBegin = date('Y-m-d').' 00:00:00';
            $todyEnd = date('Y-m-d').' 23:59:59';
            $todaysumMap['pay_successdate'] = ['between', [strtotime($todayBegin), strtotime($todyEnd)]];
            $todaysumMap['pay_status'] = ['in', '1,2'];
            $todaysumMap['owner_id'] = $this->fans['uid'];
            $stat['todaysum'] = M('Order')->where($todaysumMap)->sum('pay_amount');
            $stat['todayrate'] = M('Order')->where($todaysumMap)->sum('code_rate_money');

            //昨日成功交易总额
            $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
            $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
            $todaysumMap['pay_successdate'] = ['between', [$beginYesterday, $endYesterday]];
            $todaysumMap['pay_status'] = ['in', '1,2'];
            $todaysumMap['owner_id'] = $this->fans['uid'];
            $stat['yestsum'] = M('Order')->where($todaysumMap)->sum('pay_amount');
            $stat['yestrate'] = M('Order')->where($todaysumMap)->sum('code_rate_money');

            //本月成功交易总额
            $monthBegin = date('Y-m-01').' 00:00:00';
            $monthsumMap['pay_successdate'] = ['egt', strtotime($monthBegin)];
            $monthsumMap['pay_status'] = ['in', '1,2'];
            $monthsumMap['owner_id'] = $this->fans['uid'];
            $stat['monthsum'] = M('Order')->where($monthsumMap)->sum('pay_amount');
            $stat['monthrate'] = M('Order')->where($monthsumMap)->sum('code_rate_money');

            //本月平台收入
            $pay_poundage = M('Order')->where($monthsumMap)->sum('pay_poundage');
            $profitSumMap['datetime'] = ['egt', $monthBegin];
            $profitSumMap['lx'] = 9;
            $profitSum = M('moneychange')->where($profitSumMap)->sum('money');
            $order_cost = M('Order')->where($monthsumMap)->sum('cost');
            $stat['monthPlatform'] = $pay_poundage - $order_cost - $profitSum;
            //代理收入
            $stat['monthAgentIncome'] = $profitSum;

            if($status == 1) {
                $nopaidsumMap['pay_applydate'] = ['between', [strtotime($todayBegin), strtotime($todyEnd)]];
                $nopaidsumMap['pay_status'] = 1;
                $nopaidsumMap['owner_id'] = $this->fans['uid'];
                //今日异常订单总额
                $stat['todaynopaidsum'] = M('Order')->where($nopaidsumMap)->sum('pay_amount');
                //今日异常订单笔数
                $stat['todaynopaidcount'] = M('Order')->where($nopaidsumMap)->count();

                $monthNopaidsumMap['pay_applydate'] = ['egt', strtotime($todayBegin)];
                $monthNopaidsumMap['pay_status'] = 1;
                 $monthNopaidsumMap['owner_id'] = $this->fans['uid'];
                //本月异常订单总额
                $stat['monthNopaidsum'] = M('Order')->where($monthNopaidsumMap)->sum('pay_amount');
                //本月异常订单笔数
                $stat['monthNopaidcount'] = M('Order')->where($monthNopaidsumMap)->count();
            }
        } elseif($status == 0) {
            //今日未支付订单总额
            $todayBegin = date('Y-m-d').' 00:00:00';
            $todyEnd = date('Y-m-d').' 23:59:59';
            $monthBegin = date('Y-m-01').' 00:00:00';
            $stat['todaynopaidsum'] = M('Order')->where(['pay_applydate'=>['between', [strtotime($todayBegin), strtotime($todyEnd)]], 'pay_status'=>0,'owner_id'=>$this->fans['uid']])->sum('pay_amount');
            $stat['monthNopaidsum'] = M('Order')->where(['pay_applydate'=>['egt', strtotime($monthBegin)], 'pay_status'=>0,'owner_id'=>$this->fans['uid']])->sum('pay_amount');
            $nopaidMap = $where;
            $nopaidMap['pay_status'] = 0;
            $stat['totalnopaidsum'] = M('Order')->alias('as O')->where($nopaidMap)->sum('pay_amount');
        }
        foreach($stat as $k => $v) {
            $stat[$k] = $v+0;
           $stat[$k] = number_format($stat[$k],2,'.','');
        }
        $this->assign('stat', $stat);
        $this->assign('rows', $rows);
        $this->assign("list", $list);
        $this->assign("mdata", $mdata);
        $this->assign('stamount',$sum['pay_amount']);
        $this->assign('page', $page->show());
        $this->assign('strate', $sum['pay_poundage']);
        $this->assign('strealmoney', $sum['pay_actualamount']);
        $this->assign('success_count', $sum['success_count']);
        $this->assign('fail_count', $sum['fail_count']);
        $this->assign('memberprofit', $sum['memberprofit']);
        $this->assign('complaints_deposit_freezed', $sum['complaints_deposit_freezed']);
        $this->assign('complaints_deposit_unfreezed', $sum['complaints_deposit_unfreezed']);
        C('TOKEN_ON', false);
        $this->display();
    }

    //抢单大厅
    public function qdindex(){
        $paytime=time()-38400;
        $orderWhere['pay_status']=0;
        $orderWhere['account_type']=3;
        $orderWhere['isdel']=0;
        // $orderWhere['pay_applydate'] = array('gt',$paytime);
        $orderList = M('Order')->where($orderWhere)->order('id desc')->select();
        $this->assign('list',$orderList);
        $this->display();
    }

    //抢单记录
    public function qdhistory(){
        $uid=$this->fans['uid'];
        $orderWhere['account_type']=3;
        $orderWhere['owner_id']=$uid;
        $orderWhere['isdel']=1;
        $orderList = M('Order')->where($orderWhere)->select();
        $this->assign('list',$orderList);
        $this->display();
    }

    public function qiangdan(){
        $uid=$this->fans['uid'];
        if($this->fans['groupid']!=8){
             $this->showmsg(0,"没有权限");
        }
        if(!$uid){
             $this->showmsg(0,"非法操作");
        }
        $orderId= I('orderid/d',0);
        if(!$orderId){
            $this->showmsg(0,"订单ID有误");
        }
        $order=M("order")->where(['id'=>$orderId])->find();
        if($order["pay_status"]!=0){
            $this->showmsg(0,"该订单状态异常");
        }
        $memberinfo=M('member')->getById($uid);
        if($memberinfo['balance']<$order['pay_amount']){
            $this->showmsg(0,"余额不足，不能抢该单");
        }
        $channel_account_list= M('channel_account')->where(['channel_id' => $order['channel_id'], 'status' => '1','account_type'=>3,'add_user_id'=>$uid])->select();
        if(!$channel_account_list){
            $this->showmsg(0,"该通道您没有添加跑分码");
        }
        // 计算权重
        if (count($channel_account_list) == 1) {
            $channel_account = current($channel_account_list);
        } else {
            $channel_account = getWeight($channel_account_list);
        }
        // $this->showmsg('0',$channel_account['id']);
        $syschannel['mch_id']    = $channel_account['mch_id'];
        $syschannel['signkey']   = $channel_account['signkey'];
        $syschannel['appid']     = $channel_account['appid'];
        $syschannel['appsecret'] = $channel_account['appsecret'];
        $syschannel['account']   = $channel_account['title'];
        $syschannel['zfb_pid']   = $channel_account['zfb_pid'];

         $data['memberid']            = $syschannel["mch_id"];
         $data['key']                 = $syschannel["signkey"];
         $data['account']             = $syschannel["appid"];
         $data['account_id']          = $channel_account['id'];
         $data['owner_id']            = $channel_account['add_user_id'];
         $data['pay_channel_account'] = $syschannel['account'];
         $data['isdel'] = 1;
         $pay_amount=$order['pay_amount'];
        //开启事物
        M()->startTrans();
        //添加订单
        if (M('Order')->where(["id"=>$order['id']])->save($data)) {
            //通道增加失败次数
            $res=M('ChannelAccount')->where(['id'=>$data['account_id']])->setInc('fail_nums',1);
            if (!$res) {
                M()->rollback();
                $this->showmsg(0,'系统错误10001');
            }
            $return['datetime'] = date('Y-m-d H:i:s', $data['pay_applydate']);
            $return["status"]   = "success";
            $complaintsCodeDepositRule = $this->getComplaintsCodeDepositRule($channel_account['add_user_id']);
            $codeinfo=$memberinfo;
            $agmoneychange_data = [
                    'userid'     => $channel_account['add_user_id'],
                    'ymoney'     => $codeinfo['balance'], //原金额或原冻结资金
                    'money'      => $order['pay_amount'],
                    'gmoney'     => $codeinfo['balance']-$pay_amount, //改动后的金额或冻结资金
                    'datetime'   => date('Y-m-d H:i:s'),
                    'tongdao'    => $order['pay_bankcode'],
                    'transid'    => $trans_id,
                    'orderid'    => $order['out_trade_id'],
                    'contentstr' => '订单下单存款冻结',
                    'lx'         => 20,
                    't'          => 0,
            ];
            $depositResult = M('ComplaintsCodedeposit')->add([
                'user_id'       => $channel_account['add_user_id'],
                'pay_orderid'   => $order['pay_orderid'],
                'out_trade_id'  => $order['out_trade_id'],
                'freeze_money'  => $order['pay_amount'],
                'unfreeze_time' => time() + $complaintsCodeDepositRule['freeze_time'],
                'status'        => 0,
                // 'is_pause'        => 1,
                'create_at'     => time(),
                'update_at'     => time(),
            ]);
            if ($depositResult == false) {
                M()->rollback();
                $this->showmsg(0,'系统错误');
            }

            $member_data2['balance'] = ['exp', 'balance-' . $pay_amount]; //防止数据库并发脏读
            $member_data2['codeblockedbalance'] = ['exp', 'codeblockedbalance+' . $pay_amount]; //防止数据库并发脏读
            $member_result = M("Member")->where(['id' => $channel_account['add_user_id']])->save($member_data2);
            if ($member_result != 1) {
                M()->rollback();
                $this->showmsg(0,'系统错误');
            }else{
                $moneychange_result = $this->MoenyChange($agmoneychange_data); // 资金变动记录
                if ($moneychange_result == false) {
                    M()->rollback();
                    $this->showmsg(0,'系统错误');
                }
            }
            M()->commit();
            $this->showmsg(1,'抢单成功');
        }else{
            M()->rollback();
            $this->showmsg(0,'系统错误2');
        }


    }

    private function showmsg($status,$msg){
        $data['status']=$status;
        $data['msg']=$msg;
        $this->ajaxReturn($data,'JSON');
    }

    /**
     * 获取码商保证金设置
     * @param $userid
     * @return array
     */
    private function getComplaintsCodeDepositRule($userid)
    {
        $complaintsDepositRule = M('ComplaintsCodedepositRule')->where(['user_id' => $userid])->find();
        if (!$complaintsDepositRule || $complaintsDepositRule['status'] != 1) {
            $complaintsDepositRule = M('ComplaintsCodedepositRule')->where(['is_system' => 1])->find();
        }
        return $complaintsDepositRule ? $complaintsDepositRule : [];
    }

    /**
     * 资金变动记录
     * @param $arrayField
     * @return bool
     */
    protected function MoenyChange($arrayField)
    {
        // 资金变动
        $Moneychange = M("Moneychange");
        foreach ($arrayField as $key => $val) {
            $data[$key] = $val;
        }
        $result = $Moneychange->add($data);
        return $result ? true : false;
    }

    /**
     * 导出交易订单
     * */
    public function exportorder()
    {
        $orderid = I("request.orderid");
        if ($orderid) {
            $where['out_trade_id'] = $orderid;
        }
        $ddlx = I("request.ddlx","");
        if($ddlx != ""){
            $where['ddlx'] = array('eq',$ddlx);
        }
        $tongdao = I("request.tongdao");
        if ($tongdao) {
            $where['pay_tongdao'] = array('eq',$tongdao);
        }
        $bank = I("request.bank",'','strip_tags');
        if ($bank) {
            $where['pay_bankname'] = array('eq',$bank);
        }

        $status = I("request.status",0,'intval');
        if ($status) {
            $where['pay_status'] = array('eq',$status);
        }
        $createtime = urldecode(I("request.createtime"));
        if ($createtime) {
            list($cstime,$cetime) = explode('|',$createtime);
            $where['pay_applydate'] = ['between',[strtotime($cstime),strtotime($cetime)?strtotime($cetime):time()]];
        }
        $successtime = urldecode(I("request.successtime"));
        if ($successtime) {
            list($sstime,$setime) = explode('|',$successtime);
            $where['pay_successdate'] = ['between',[strtotime($sstime),strtotime($setime)?strtotime($setime):time()]];
        }
        $where['isdel'] = 0;
        $where['pay_memberid'] = $this->fans['memberid'];

        $title = array('订单号','商户编号','交易金额','手续费','实际金额','提交时间','成功时间','通道','状态');
        $data = M('Order')->where($where)->select();
        foreach ($data as $item){

            switch ($item['pay_status']){
                case 0:
                    $status = '未处理';
                    break;
                case 1:
                    $status = '成功，未返回';
                    break;
                case 2:
                    $status = '成功，已返回';
                    break;
            }
            $list[] = array(
                'pay_orderid'=>$item['out_trade_id'] ? $item['out_trade_id']:$item['pay_orderid'],
                'pay_memberid'=>$item['pay_memberid'],
                'pay_amount'=>$item['pay_amount'],
                'pay_poundage'=>$item['pay_poundage'],
                'pay_actualamount'=>$item['pay_actualamount'],
                'pay_applydate'=>date('Y-m-d H:i:s',$item['pay_applydate']),
                'pay_successdate'=>date('Y-m-d H:i:s',$item['pay_successdate']),
                'pay_zh_tongdao'=>$item['pay_zh_tongdao'],
                'pay_status'=>$status,
            );
        }
        $numberField = ['pay_amount', 'pay_poundage', 'pay_actualamount'];
        exportexcel($list, $title, $numberField);
    }

    /**
     * 查看订单
     */
    public function show()
    {
        $id = I("get.oid",0,'intval');
        if($id){
            $order = M('Order')
                ->where(['id'=>$id])
                ->find();
        }

        if($order['pay_memberid'] != $this->fans['memberid']) {
            $parentId = M('Member')->where(['id'=>$order['pay_memberid']-10000])->getField('parentid');
            if($parentId != $this->fans['uid']) {
                $this->error('没有权限查看该订单');
            }
        }
        $this->assign('order',$order);
        $this->display();
    }

    /**
     *  伪删除订单
     */
    /*
    public function delOrder()
    {
        if(IS_POST){
            $id = I('post.id',0,'intval');
            if($id){
                $res = M('Order')->where(['id'=>$id,'pay_memberid'=>$this->fans['memberid']])->setField('isdel',1);
            }
            $this->ajaxReturn(['status'=>$res]);
        }
    }
    */
}
?>
