<?php

    namespace User\Controller;

    use Think\Page;

    class StatisticsController extends UserController{

        public function index()
        {
            
            $zfb_info = M('channel')->where(['is_mianqian'=>1,'agent_can_sh'=>1])->select();
            foreach ($zfb_info as $k => $v) {
                $id[] = $v['id'];
            }
            if(!$id) {
                $this->error('缺少参数');
            }
            $list = M('channelAccount')->where(['channel_id' => ['in',$id],'add_user_id'=>$this->fans['uid']])->select();
            $todayBegin = date('Y-m-d').' 00:00:00';
            $todyEnd = date('Y-m-d').' 23:59:59';
            $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));
            $endYesterday=mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
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
                //今日成功交易总额
                
                $todaysumMap['pay_successdate'] = ['between', [strtotime($todayBegin), strtotime($todyEnd)]];
                $todaysumMap['pay_status'] = ['in', '1,2'];
                $todaysumMap['account_id'] =  $v['id'];
                $list[$k]['todaysum'] = M('Order')->where($todaysumMap)->sum('pay_amount');
                if(!$list[$k]['todaysum']){
                    $list[$k]['todaysum']=0;
                }
                //昨日成功交易总额
                
                $todaysumMap['pay_successdate'] = ['between', [$beginYesterday, $endYesterday]];
                $list[$k]['yesterdaysum'] = M('Order')->where($todaysumMap)->sum('pay_amount');
                if(!$list[$k]['yesterdaysum']){
                    $list[$k]['yesterdaysum']=0;
                }
            }

            

            $this->assign('list', $list);
            $this->assign('data', $channel);
            $this->display();
        }

    }

?>