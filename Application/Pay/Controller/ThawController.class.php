<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-09-12
 * Time: 14:43
 */
namespace Pay\Controller;

class ThawController extends PayController
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
     * 解冻T1资金计划
     */
    public function thawPlanning()
    {
        $hour = intval(date('H'));
        $configs = C('PLANNING');
       
        $allowstart = $configs['allowstart'] ? $configs['allowstart'] : 1;
        $allowend = $configs['allowend'] ? $configs['allowend'] : 5;
        //计划执行

       
        if($hour>=$allowstart && $hour<$allowend){
            $curtime = strtotime('today');
            $yesterday = strtotime('yesterday');
            $maps['thawtime'] = array('elt', $curtime + 7200);
            $maps['createtime'] = array('lt',$curtime);
            $maps['status'] = array('eq',0);
            $data = M('blockedlog')->where($maps)->limit(600)->order('id asc')->select();
            if($data){
                foreach ($data as $item){
                    try{
                        $rows = array();
                        $rows['balance'] = array('exp',"balance+{$item['amount']}");
                        $rows['blockedbalance'] = array('exp',"blockedbalance-{$item['amount']}");
                        //开启事务
                        $Model = M();
                        $Model->startTrans();
                        $user = $Model->table('pay_member')->where(['id'=>$item['userid']])->lock(true)->find();
                        //更新资金
                        $upRes = $Model->table('pay_member')->where(['id'=>$item['userid'],'blockedbalance'=>['egt', $item['amount']]])->save($rows);
                        //更新状态
                        $uplog = $Model->table('pay_blockedlog')->where(array('id'=>$item['id']))->save(array('status'=>1));
                        //增加记录
                        $data =array();
                        $data['userid'] = $item['userid'];
                        $data['ymoney'] = $user['balance'];
                        $data['money'] = $item['amount'];
                        $data['gmoney'] = $user['balance'] + $item['amount'];
                        $data['datetime'] = date("Y-m-d H:i:s");
                        $data['tongdao'] = $item['pid'];
                        $data['transid'] = $item['orderid'];//交易流水号
                        $data['orderid'] = $item['orderid'];
                        $data['lx'] = 8;//解冻
                        $data['contentstr'] = "订单金额解冻";
                        $change = $Model->table('pay_moneychange')->add($data);

                        //提交事务
                        if($upRes && $uplog && $change ){
                            $Model->commit();
                        }else{
                            $Model->rollback();
                        }
                    } catch (\Exception $e) {
                        $Model->rollback();
                        throw new \Exception("解冻队列ID：".$data['id']."解冻失败：" . $e);
                    }

                }
            }else{
                echo 'null';
            }
        }else{

        }
    }

}