<?php
/**
 * Created by PhpStorm.
 * User: gaoxi
 * Date: 2017-03-21
 * Time: 11:18
 */
namespace Pay\Model;
use Think\Model;

class MemberModel extends Model{

    /**
     *  获取会员信息
     * @param $uid
     * @return mixed
     */
    public function get_Userinfo($uid){
        $return = $this->where(array('id'=>$uid))->find();
        return $return;
    }
}