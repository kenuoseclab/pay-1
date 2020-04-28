<?php

namespace Pay\Model;
use Think\Model;

class ComplaintsDepositModel extends Model{

    public static function getComplaintsDeposit($userId)
    {
        $userId = (int)$userId;
        $sql = "select sum(freeze_money) as freeze_money,is_pause from `pay_complaints_deposit` where user_id={$userId} and status=0 group by is_pause";
        $rows = M('ComplaintsDeposit')->query($sql);
        $result = [
            'complaintsDeposit' => 0,
            'complaintsDepositPaused' => 0,
        ];
        if (!empty($rows)) {
            foreach ($rows as $row) {
                if ($row['is_pause'] == 1) {
                    $result['complaintsDepositPaused'] = $row['freeze_money'];
                } else {
                    $result['complaintsDeposit'] = $row['freeze_money'];
                }
            }
        }
        return $result;
    }
}