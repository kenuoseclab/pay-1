<?php

namespace Cli\Controller;

use \think\Controller;
use Think\Log;

/**
 * @author mapeijian
 * @date   2018-06-07
 */
class UnfreezeMoneyController extends Controller
{
    public function index()
    {
        echo "[" . date('Y-m-d H:i:s'). "] 自动解冻任务触发\n";
        Log::record("自动解冻任务触发", Log::INFO);
        $time = $_SERVER['REQUEST_TIME'];
        $this->doUnfreeze($time);
        Log::record("自动解冻任务结束", Log::INFO);
        echo "[" . date('Y-m-d H:i:s'). "] 自动解冻任务结束\n";
    }

    private function doUnfreeze($time)
    {
        $lists = M('autoUnfrozenOrder')->where(['status' => 0, 'is_pause' => 0, 'unfreeze_time' => ['ELT', $time]])->select();
        foreach ($lists as $row) {
            if(!$row['unfreeze_time']) {
                continue;
            }
            try {
                M()->startTrans();
                $order = M('autoUnfrozenOrder')->where(['id' => $row['id'], 'status' => 0, 'is_pause' => 0, 'unfreeze_time' => ['ELT', $time]])->lock(true)->limit(1)->find();
                if (empty($order)) {
                    throw new \Exception("记录不存在，id: " . $row['id']);
                }

                $userId = $order['user_id'];
                $user = M('Member')->where(['id' => $userId])->lock(true)->find();
                if (empty($user)) {
                    throw new \Exception("用户不存在，userId: " . $userId);
                }

                //更新用户余额
                $memberRes = M('Member')->where(['id' => $userId, 'blockedbalance'=>array('egt', $order['freeze_money'])])->save([
                    'balance' => ['exp', '`balance`+' . $order['freeze_money']],
                    'blockedbalance' => ['exp', '`blockedbalance`-' . $order['freeze_money']]
                ]);
                //更新自动解冻队列
                $orderRes = M('autoUnfrozenOrder')->where(['id' => $order['id']])->save([
                    'real_unfreeze_time' => time(),
                    'status' => 1,
                    'update_at' => time(),
                ]);
                //更新资金变动记录
                $logRes = M('Moneychange')->add([
                    'userid' => $userId,
                    'ymoney'     => $user['balance'],
                    'money' => $order['freeze_money'],
                    "gmoney"     => $user['balance']+$order['freeze_money'],
                    'datetime' => date('Y-m-d H:i:s'),
                    'tongdao' => 0,
                    'transid' => $order['id'],
                    'orderid' => '',
                    'lx' => 8,
                    'contentstr' => '自动解冻',
                ]);

                if (!($memberRes && $orderRes && $logRes)) {
                    throw new \Exception("数据库更新失败：{$memberRes}-{$orderRes}-{$logRes}");
                }

                M()->commit();

            } catch (\Exception $e) {
                M()->rollback();
                Log::record("自动解冻出错：" . $e->getMessage());
            }

        }
    }
}