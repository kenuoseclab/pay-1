<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-07-30
 * Time: 15:17
 */
namespace Admin\Model;

use Think\Model;

class PayapiaccountModel extends Model
{
    public function getAllsupplier()
    {
        $data = $this->join('LEFT JOIN __PAYAPI__ ON __PAYAPIACCOUNT__.payapiid = __PAYAPI__.id')
            ->select();
        return $data;
    }
}