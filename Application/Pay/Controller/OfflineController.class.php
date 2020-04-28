<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-09-12
 * Time: 14:43
 */
namespace Pay\Controller;

class OfflineController extends PayController
{
    private $lockFileName;

    public function __construct()
    {
        parent::__construct();
        $this->lockFileName = './Data/risk_management.log';
    }

    public function index()
    {
        echo json_encode(['code' => 500, 'msg' => '走错道了哦.']);
    }

    /**
     * 风控计划
     */
    public function offlinePlanning()
    {

        $triggerTime = time();
        $this->doLog("风控计划任务开始执行");
        //获取上次触发时间
        $lockFileHandler = fopen($this->lockFileName, "a+");
        if (!flock($lockFileHandler, LOCK_EX | LOCK_NB)) {  // acquire an exclusive lock
            $this->doLog("风控计划任务，获取锁失败，自动退出");
            return;
        }
        rewind($lockFileHandler);
        $lastTriggerTime = fgets($lockFileHandler);
        //今天0时时间
        $todayTime = strtotime(date('Y-m-d'));
        if ((empty($lastTriggerTime) || $lastTriggerTime < $todayTime)) {
            // 处理通道的上线
            $Channel      = M('Channel');
            $channelCount = $Channel->count();
            for ($i = 0; $i < $channelCount; $i++) {
                $channelInfo = $Channel->where(['control_status' => 1])->getField('id', true);
                foreach ($channelInfo as $k => $v) {
                    $Channel->where(['id' => $v])->save(['pay_money' => 0, 'offline_status' => 1]);
                }
            }

            //处理通道子账号
            $ChannelAccount = M('ChannelAccount');
            $accountCount   = $ChannelAccount->count();
            for ($i = 0; $i < $accountCount; $i++) {
                $accountInfo = $ChannelAccount->where(['control_status' => 1])->getField('id', true);
                foreach ($accountInfo as $k => $v) {
                    $ChannelAccount->where(['id' => $v])->save(['paying_money' => 0, 'offline_status' => 1]);
                }
            }
            //处理用户
            $Member      = M('Member');
            $memberCount = $Member->count();
            for ($i = 0; $i < $memberCount; $i++) {
                $memberInfo = $Member->getField('id', true);
                foreach ($memberInfo as $k => $v) {
                    $memberData = [
                        'pay_money'           => 0,
                        'unit_pay_money'      => 0,
                        'unit_pay_number'     => 0,
                        'unit_frist_pay_time' => 0,
                    ];
                    $Member->where(['id' => $v])->save($memberData);
                }
            }
        } else {
            $this->doLog("风控任务已执行，自动退出" );
        }
        ftruncate($lockFileHandler, 0);
        fwrite($lockFileHandler, $triggerTime);
        fflush($lockFileHandler);            // flush output before releasing the lock
        flock($lockFileHandler, LOCK_UN);    // release the lock
        $timeUsed = time() - $triggerTime;
        $this->doLog("风控任务执行完成，用时：" . $timeUsed);
    }

    private function doLog($msg) {

        file_put_contents('./Data/risk_management_info.log', "【".date('Y-m-d H:i:s')."】\r\n".$msg."\r\n\r\n",FILE_APPEND);
    }

}
