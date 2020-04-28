<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 14-12-29
 * Time: 上午1:31
 */
function huoqutktype()
{
    $Tikuanconfig = M("Tikuanconfig");
    $tktype = $Tikuanconfig->where(["websiteid" => session("websiteid") , "userid" => 0])->getField("tktype");
    if ($tktype == 1) {
        $tktypestr = "单笔";
    } else {
        $tktypestr = "比例";
    }
    return $tktypestr;
}

function getinviteconfigzt($id)
{
    $Invitecode = M("Invitecode");
    $list = $Invitecode->where(["id" => $id])->find();
    $inviteconfigzt = $list["inviteconfigzt"];
    $yxdatetime = $list["yxdatetime"];
    switch ($inviteconfigzt) {
        case 0:
            return '<span style="color:#F00;">禁用</span>';
            break;
        case 1:
            if (time() < $yxdatetime) {
                return '可以使用';
            } else {
                return '<span style="color:#06C">已过期</span>';
            }
            
            break;
        case 2:
            return '<span style="color:#060;">已使用</span>';
            break;
    }
}

// 自己添加
function membertype($user_type)
{
    $title = M('MemberAgentCate')->where(['id'=>$user_type])->getField('cate_name');
    return $title;
}