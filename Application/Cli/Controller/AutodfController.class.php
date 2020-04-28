<?php

namespace Cli\Controller;

use \think\Controller;
use Think\Log;

/**
 * @author mapeijian
 * @date   2018-06-06
 */
class AutodfController extends Controller
{
    public function index()
    {
        $time = time();
        echo "[" . date('Y-m-d H:i:s'). "] 自动代付任务触发\n";
        Log::record("自动代付任务触发", Log::INFO);
        $config = M('tikuanconfig')->where(['issystem' => 1])->find();
        if(!$config['auto_df_switch']) {
            Log::record("自动代付已关闭", Log::INFO);
            exit;
        }
        $start_time = strtotime($config['auto_df_stime']);
        $end_time = strtotime($config['auto_df_etime'])+59;
        if($time < $start_time || $time > $end_time) {
            Log::record("不在自动代付时间", Log::INFO);
            exit;
        }
        $this->doDf($config);
        Log::record("自动代付任务结束", Log::INFO);
        echo "[" . date('Y-m-d H:i:s'). "] 自动代付任务结束\n";
    }

    //提交
    private function doDf($config)
    {
        //默认代付通道
        $channel = M('PayForAnother')->where(['status'=>1, 'is_default'=>1])->find();
        if(empty($channel)) {
            echo "[" . date('Y-m-d H:i:s'). "] 默认代付通道不存在\n";
            exit;
        }
        $file = APP_PATH .  'Payment/Controller/' . $channel['code'] . 'Controller.class.php';
        if( !file_exists($file) ) {
            echo "[" . date('Y-m-d H:i:s'). "] 默认代付通道文件不存在\n";
            exit;
        }
        $success = $fail = 0;
        //每次执行10条，尝试提交次数超过5次的不处理，优先处理申请时间较早的，尝试提交次数少的代付申请
        $map['status'] = 0;
        $map['auto_submit_try'] = ['lt',5];
        $map['df_lock'] = 0;
        if($config['auto_df_maxmoney']>0) {
            $map['money'] = ['elt', $config['auto_df_maxmoney']];//单笔最大金额限制
        }
        $lists = M('Wttklist')->where($map)->order('id ASC, auto_submit_try ASC')->limit(0,10)->select();
        Log::record("本次计划任务处理".count($lists).'个订单', Log::INFO);
        echo "本次计划任务处理".count($lists).'个订单';
        foreach($lists as $k => $v) {
            if($config['auto_df_max_count']>0) {//商户每天自动代付笔数限制
                $map['userid']     = $v['userid'];
                $map['sqdatetime'] = ['between', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59']];
                $map['is_auto'] = 1;
                $count = M('Wttklist')->where($map)->count();
                if($count>=$config['auto_df_max_count']) {
                    M('Wttklist')->where(['id'=>$v['id']])->save(['last_submit_time'=>time(),'auto_submit_try'=>['exp','auto_submit_try+1'],'df_lock'=>0]);
                    $this->logAutoDf($v['id'], 1, 0, '超过商户每天自动代付笔数限制');
                    $fail++;
                    continue;
                }
            }
            if($config['auto_df_max_sum']>0) {//自动代付商户每天最大总金额
                $map['userid']     = $v['userid'];
                $map['sqdatetime'] = ['between', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59']];
                $map['is_auto'] = 1;
                $sum = M('Wttklist')->where($map)->sum('tkmoney');
                if($sum>=$config['auto_df_max_sum']) {
                    M('Wttklist')->where(['id'=>$v['id']])->save(['last_submit_time'=>time(),'auto_submit_try'=>['exp','auto_submit_try+1'],'df_lock'=>0]);
                    $this->logAutoDf($v['id'], 1, 0, '商户每天自动代付最大总金额限制');
                    $fail++;
                    continue;
                }
            }
            $v['money'] = round($v['money'],2);
            //加锁防止重复提交
            $res = M('Wttklist')->where(['id'=>$v['id'], 'df_lock'=>0])->setField('df_lock',1);
            if(!$res) {
                continue;
            }
            try {
                $result = R('Payment/' . $channel['code'] . '/PaymentExec', [$v, $channel]);
                if (FALSE === $result) {
                    M('Wttklist')->where(['id' => $v['id']])->save(['last_submit_time' => time(), 'auto_submit_try' => ['exp', 'auto_submit_try+1'], 'df_lock' => 0]);
                    $this->logAutoDf($v['id'], 1, 0, '提交失败');
                    $fail++;
                    continue;
                } else {
                    if (is_array($result)) {
                        $cost = $channel['rate_type'] ? bcmul($v['tkmoney'], $channel['cost_rate'], 2) : $channel['cost_rate'];
                        $data = [
                            'memo' => $result['msg'],
                            'df_id' => $channel['id'],
                            'code' => $channel['code'],
                            'df_name' => $channel['title'],
                            'channel_mch_id' => $channel['mch_id'],
                            'cost_rate' => $channel['cost_rate'],
                            'cost' => $cost,
                            'rate_type' => $channel['rate_type'],
                        ];
                        $this->handle($v['id'], $result['status'], $data);
                        $this->logAutoDf($v['id'], 1, $result['status'], $result['msg']);
                        $success++;
                        M('Wttklist')->where(['id' => $v['id']])->save(['is_auto' => 1, 'last_submit_time' => time(), 'auto_submit_try' => ['exp', 'auto_submit_try+1'], 'df_lock' => 0]);
                    }
                }
            } catch (\Exception $e) {
                M('Wttklist')->where(['id' => $v['id']])->setField('df_lock', 0);
            }
        }
        Log::record("[" . date('Y-m-d H:i:s'). "] 成功提交：".$success."，失败：".$fail, Log::INFO);
        echo "成功提交：".$success."，失败：".$fail;
        exit;
    }

    public function dfQuery()
    {
        echo "[" . date('Y-m-d H:i:s'). "] 自动代付查询任务触发\n";
        Log::record("自动代付任务触发", Log::INFO);
        $time = $_SERVER['REQUEST_TIME'];
        $this->doQuery();
        Log::record("自动代付任务结束", Log::INFO);
        echo "[" . date('Y-m-d H:i:s'). "] 自动代付查询任务结束\n";

    }

    //查询
    private function doQuery() {

        $lists = M('Wttklist')->where(['status' => 1])->order('id asc, auto_query_num asc')->limit(0,10)->select();
        Log::record("本次计划任务查询".count($lists).'个订单', Log::INFO);
        echo "本次计划任务查询".count($lists).'个订单';
        $success = 0;
        foreach($lists as $k => $v){
            $file = APP_PATH . 'Payment/Controller/' . $v['code'] . 'Controller.class.php';
            if( file_exists($file) ) {
                $pfa_list = M('PayForAnother')->where(['id'=>$v['df_id']])->find();
                if(empty($pfa_list)) {
                    continue;
                }
                $result = R('Payment/'.$v['code'].'/PaymentQuery', [$v, $pfa_list]);
                if(FALSE === $result) {
                    $this->logAutoDf($v['id'], 2, 0, '查询失败');
                } else {
                    if(is_array($result)){
                        $success++;
                        $data = [
                            'memo'      => $result['msg'],
                            'df_id'     => $pfa_list['id'],
                            'code'      => $pfa_list['code'],
                            'df_name'   => $pfa_list['title'],
                        ];
                        $this->handle($v['id'], $result['status'], $data);
                        $this->logAutoDf($v['id'], 2, $result['status'], $result['msg']);
                    }
                }
                M('Wttklist')->where(['id'=>$v['id']])->setInc('auto_query_num');
            } else {
                $this->logAutoDf($v['id'], 2, 0, '代付通道文件不存在');
            }
        }
        Log::record("[" . date('Y-m-d H:i:s'). "] 成功查询：".$success."个订单", Log::INFO);
        echo "成功查询：".$success."个订单";
        exit;
    }

    protected function handle($id, $status=1, $return){

        //处理成功返回的数据
        $data = '';
        if($status == 1){
            $data['status'] = 1;
            $data['memo'] = '申请成功！';
        }else if ($status == 2) {
            $data['status'] = 2;
            $data['cldatetime'] = date('Y-m-d H:i:s', time());
            $data['memo'] = '代付成功';
        }else if($status == 3){
            $data['status'] = 4;
            $data['memo'] = isset($return['memo'])?$return['memo']:'代付失败！';
        }
        if(in_array($status, [1,2,3])){
            $data = array_merge($data, $return);
            $where = ['id'=>$id];
            M('Wttklist')->where($where)->save($data);
        }

    }
    //记录日志
    private function logAutoDf($df_id, $type, $status, $msg) {

        $log['status'] = $status;
        $log['msg'] = $msg;
        $log['df_id'] = $df_id;
        $log['type'] = $type;
        $log['ctime'] = time();
        $res = M('auto_df_log')->add($log);
        return $res;
    }

    public function query() {
        $id = I('id', 0, 'intval');
        if($id) {
            $data = M('Wttklist')->where(['id'=> $id])->find();
            $chance = M('PayForAnother')->where(['id'=>$data['df_id']])->find();
            $result = R('Payment/'.$data['code'].'/PaymentQuery', [$data, $chance]);
            var_dump($result);die;
        }
    }

}