<?php

namespace Cli\Controller;

use \think\Controller;
use Think\Log;

/**
 * @author zhangjianwei
 * @date   2018-05-30
 */
class UnfreezeController extends Controller
{
    public function index()
    {
        echo "[" . date('Y-m-d H:i:s'). "] 保证金自动解冻任务触发\n";
        Log::record("自动解冻任务触发", Log::INFO);
        $time = $_SERVER['REQUEST_TIME'];
        $this->doCodeUnfreeze($time);
        Log::record("自动解冻任务结束", Log::INFO);
        echo "[" . date('Y-m-d H:i:s'). "] 保证金自动解冻任务结束\n";
    }

    private function doCodeUnfreeze($time){
        $deposits = M('ComplaintsCodedeposit')->where(['status' => 0, 'is_pause' => 0, 'unfreeze_time' => ['ELT', $time]])->select();
        foreach ($deposits as $row) {
            try {
                M()->startTrans();
                $deposit = M('ComplaintsCodedeposit')->where(['id' => $row['id'], 'status' => 0, 'is_pause' => 0, 'unfreeze_time' => ['ELT', $time]])->lock(true)->limit(1)->find();
                if (empty($deposit)) {
                    throw new \Exception("记录不存在，id: " . $row['id']);
                }

                $userId = $deposit['user_id'];
                $user = M('Member')->where(['id' => $userId])->lock(true)->find();
                if (empty($user)) {
                    throw new \Exception("用户不存在，userId: " . $userId);
                }
                if($user['codeblockedbalance']<$deposit['freeze_money']){
                    throw new \Exception("用户冻结金额不足，userId: " . $userId);
                }

                //更新用户余额
                $memberRes = M('Member')->where(['id' => $userId])->save([
                    'balance' => ['exp', '`balance`+' . $deposit['freeze_money']],
                    'codeblockedbalance' => ['exp', '`codeblockedbalance`-' . $deposit['freeze_money']]
                ]);
                //更新保证金记录
                $depositRes = M('ComplaintsCodedeposit')->where(['id' => $deposit['id']])->save([
                    'real_unfreeze_time' => time(),
                    'status' => 1,
                    'update_at' => time(),
                ]);
                //更新资金变动记录
                $logRes = M('Moneychange')->add([
                    'userid' => $userId,
                    'ymoney'     => $user['balance'],
                    'money' => $deposit['freeze_money'],
                    "gmoney"     => $user['balance']+$deposit['freeze_money'],
                    'datetime' => date('Y-m-d H:i:s'),
                    'tongdao' => 0,
                    'transid' => $deposit['pay_orderid'],
                    'orderid' => '',
                    'lx' => 13, //投诉保证金解冻
                    'contentstr' => '保证金解冻',
                ]);

                if (!($memberRes && $depositRes && $logRes)) {
                    throw new \Exception("数据库更新失败：{$memberRes}-{$depositRes}-{$logRes}");
                }

                M()->commit();

            } catch (\Exception $e) {
                M()->rollback();
                Log::record("保证金自动解冻出错：" . $e->getMessage());
            }

        }
    }

    private function doUnfreeze($time)
    {
        $deposits = M('ComplaintsDeposit')->where(['status' => 0, 'is_pause' => 0, 'unfreeze_time' => ['ELT', $time]])->select();
        foreach ($deposits as $row) {
            try {
                M()->startTrans();
                $deposit = M('ComplaintsDeposit')->where(['id' => $row['id'], 'status' => 0, 'is_pause' => 0, 'unfreeze_time' => ['ELT', $time]])->lock(true)->limit(1)->find();
                if (empty($deposit)) {
                    throw new \Exception("记录不存在，id: " . $row['id']);
                }

                $userId = $deposit['user_id'];
                $user = M('Member')->where(['id' => $userId])->lock(true)->find();
                if (empty($user)) {
                    throw new \Exception("用户不存在，userId: " . $userId);
                }

                //更新用户余额
                $memberRes = M('Member')->where(['id' => $userId])->save([
                    'balance' => ['exp', '`balance`+' . $deposit['freeze_money']]
                ]);
                //更新保证金记录
                $depositRes = M('ComplaintsDeposit')->where(['id' => $deposit['id']])->save([
                    'real_unfreeze_time' => time(),
                    'status' => 1,
                    'update_at' => time(),
                ]);
                //更新资金变动记录
                $logRes = M('Moneychange')->add([
                    'userid' => $userId,
                    'ymoney'     => $user['balance'],
                    'money' => $deposit['freeze_money'],
                    "gmoney"     => $user['balance']+$deposit['freeze_money'],
                    'datetime' => date('Y-m-d H:i:s'),
                    'tongdao' => 0,
                    'transid' => $deposit['pay_orderid'],
                    'orderid' => '',
                    'lx' => 13, //投诉保证金解冻
                    'contentstr' => '投诉保证金解冻',
                ]);

                if (!($memberRes && $depositRes && $logRes)) {
                    throw new \Exception("数据库更新失败：{$memberRes}-{$depositRes}-{$logRes}");
                }

                M()->commit();

            } catch (\Exception $e) {
                M()->rollback();
                Log::record("保证金自动解冻出错：" . $e->getMessage());
            }

        }
    }
}