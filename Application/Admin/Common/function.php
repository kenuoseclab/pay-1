<?php

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 */
function is_login(){
    $user = session('admin_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('admin_auth_sign') == data_auth_sign($user) ? $user['uid'] : 0;
    }
}

/**
 * 检测当前用户是否为管理员
 * @return boolean true-管理员，false-非管理员
 */
function is_administrator($uid = null){
    $uid = is_null($uid) ? is_login() : $uid;
    return $uid && (intval($uid) === C('USER_ADMINISTRATOR'));
}
/**
 * 检测是否超级管理员
 * author: feng
 * create: 2017/10/23 10:21
 */
function is_rootAdministrator($uid = null){
    $uid = is_null($uid) ? is_login() : $uid;

    return $uid && (intval(M("Admin")->where(array("id"=>$uid))->getField("groupid")) ===1);
}

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param  string $str  要分割的字符串
 * @param  string $glue 分割符
 * @return array
 */
function str2arr($str, $glue = ','){
    return explode($glue, $str);
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 * @param  array  $arr  要连接的数组
 * @param  string $glue 分割符
 * @return string
 */
function arr2str($arr, $glue = ','){
    return implode($glue, $arr);
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
    if(function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        $slice = iconv_substr($str,$start,$length,$charset);
        if(false === $slice) {
            $slice = '';
        }
    }else{
        $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice.'...' : $slice;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key  加密密钥
 * @param int $expire  过期时间 单位 秒
 * @return string
 */
function think_encrypt($data, $key = '', $expire = 0) {
    $key  = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x    = 0;
    $len  = strlen($data);
    $l    = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time():0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
    }
    return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key  加密密钥
 * @return string
 */
function think_decrypt($data, $key = ''){
    $key    = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data   = str_replace(array('-','_'),array('+','/'),$data);
    $mod4   = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data   = base64_decode($data);
    $expire = substr($data,0,10);
    $data   = substr($data,10);

    if($expire > 0 && $expire < time()) {
        return '';
    }
    $x      = 0;
    $len    = strlen($data);
    $l      = strlen($key);
    $char   = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }else{
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 */
function data_auth_sign($data) {
    //数据类型检测
    if(!is_array($data)){
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

/**
 * 对查询结果集进行排序
 * @access public
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list,$field, $sortby='asc') {
    if(is_array($list)){
        $refer = $resultSet = array();
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc':// 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ( $refer as $key=> $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }
    return false;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 */
function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0) {
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId =  $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree  原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array  $list  过渡用的中间数组，
 * @return array        返回排过序的列表数组
 */
function tree_to_list($tree, $child = '_child', $order='id', &$list = array()){
    if(is_array($tree)) {
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if(isset($reffer[$child])){
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby='asc');
    }
    return $list;
}

/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $delimiter . $units[$i];
}

function zhuangtaiEdit($id)
{
    switch ($id) {
        case 0:
            return '<li style="background-color:#8cbae5; color:#fff;"><a href="#">修改为<strong style="color:#157d17;">已激活</strong></a></li>';
            
            break;
        case 1:
            return '<li style="background-color:#8cbae5; color:#fff;"><a href="#">修改为<strong style="color:#f00;">已禁用</strong></a></li>';
            
            break;
        case 2:
            return '<li style="background-color:#8cbae5; color:#fff;"><a href="#">修改为<strong style="color:#157d17;">已激活</strong></a></li>';
            
            break;
    }
}

function renzhengedit($id)
{
    $Userverifyinfo = M("Userverifyinfo");
    $uploadsfzzm = $Userverifyinfo->where(["userid" => $id])->getField("uploadsfzzm");
    $uploadsfzbm = $Userverifyinfo->where(["userid" => $id])->getField("uploadsfzbm");
    $uploadscsfz = $Userverifyinfo->where(["userid" => $id])->getField("uploadscsfz");
    
    $uploadsfzzm = $uploadsfzzm != '' ? "http://" . C("DOMAIN") . "/Uploads/verifyinfo/" . $uploadsfzzm : "http://" . C("DOMAIN");
    $uploadsfzbm = $uploadsfzbm != '' ? "http://" . C("DOMAIN") . "/Uploads/verifyinfo/" . $uploadsfzbm : "http://" . C("DOMAIN");
    $uploadscsfz = $uploadscsfz != '' ? "http://" . C("DOMAIN") . "/Uploads/verifyinfo/" . $uploadscsfz : "http://" . C("DOMAIN");
    
    $liststr = '<li><a href="' . $uploadsfzzm . '" target="_blank">查看身份证正面</a></li><li class="divider"></li><li><a href="' . $uploadsfzbm . '" target="_blank">查看身份证反面</a></li><li class="divider"></li><li><a href="' . $uploadscsfz . '" target="_blank">查看手持身份证</a></li>';
    
    $Userverifyinfo = M("Userverifyinfo");
    $status = $Userverifyinfo->where(["userid" => $id])->getField("status");
    switch ($status) {
        case 2:
            return '<ul class="dropdown-menu">' . $liststr . '<li class="divider"></li><li class="divider"></li><li style="background-color:#8cbae5; color:#fff;"><a href="javascript:renzheng(' . $id . ');">修改为<strong style="color:#157d17">已认证</strong></a></li><li class="divider"></li><li style="background-color:#8cbae5; color:#fff;"><a href="javascript:weirenzheng(' . $id . ');">修改为<strong style="color:#ccc">未认证</strong></a></li></ul>';
            
            break;
        case 1:
            return ' <ul class="dropdown-menu">' . $liststr . '</ul>';
            break;
    }
}

function getinviteconfigzt($id)
{
    $Invitecode = M("Invitecode");
    $list = $Invitecode->where(["id" => $id])->find();

    switch ($list["inviteconfigzt"]) {
        case 0:
            return '<span style="color:#F00;">禁用</span>';
            break;
        case 1:
            if (time() < $list["yxdatetime"]) {
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

function payapiaccount($payapiid)
{
    $Payapiaccount = M("Payapiaccount");
    $list = $Payapiaccount->where(["payapiid" => $payapiid])->select();
    $str = "";
    foreach ($list as $key) {
        $str = $str . '<option value="' . $key["id"] . '">商户ID：' . $key['sid'] . '</option>';
    }
    return $str;
}

function articleclasslist($fatherid, $num = 0)
{
    $Articleclass = M("Articleclass");
    $list = $Articleclass->where(["fatherid" => $fatherid, "status" => 0, "type" => 0])->select();
    $str = "";
    $f = "";
    $fc = "";
    for ($var = 0; $var < $num; $var ++) {
        $f = $f . "&nbsp;&nbsp;&nbsp;&nbsp;";
        $fc = "color:#06F";
    }
    foreach ($list as $key) {
        $str = $str . '<option value="' . $key["id"] . '" style="font-weight:bold;' . $fc . '">' . $f . $sjname . $key["classname"] . '</option>';
        $str = $str . articleclasslist($key["id"], ++ $num);
    }
    return $str;
}

function getarticleclass($id)
{
    $Articleclass = M("Articleclass");
    $classname = $Articleclass->where(["id" => $id])->getField("classname");
    return $classname;
}

function huoqutktype()
{
    $Tikuanconfig = M("Tikuanconfig");
    $tktype = $Tikuanconfig->where(["websiteid" => session("admin_websiteid") ,"userid" => 0])->getField("tktype");
    if ($tktype == 1) {
        $tktypestr = "单笔";
    } else {
        $tktypestr = "比例";
    }
    return $tktypestr;
}

/**
 * 增加日志
 * @param $log
 * @param bool $name
 */
function addlog($log, $name = false)
{
    $Model = M('log');
    if (!$name) {
        session_start();
        $uid = session('userid');
        if ($uid) {
            $user = M('User')->field('username')->where(array('id' => $uid))->find();
            $data['name'] = $user['username'];
        } else {
            $data['name'] = '';
        }
    } else {
        $data['name'] = $name;
    }
    $data['t'] = time();
    $data['ip'] = $_SERVER["REMOTE_ADDR"];
    $data['log'] = $log;
    $Model->data($data)->add();
}

/**
 * description: 递归菜单
 * @param unknown $array
 * @param number $fid
 * @param number $level
 * @param number $type 1:顺序菜单 2树状菜单
 * @return multitype:number
 */
function get_column($array,$type=1,$fid=0,$level=0)
{
    $column = [];
    if($type == 2)
        foreach($array as $key => $vo){
            if($vo['pid'] == $fid){
                $vo['level'] = $level;
                $column[$key] = $vo;
                $column [$key][$vo['id']] = get_column($array,$type=2,$vo['id'],$level+1);
            }
        }else{
        foreach($array as $key => $vo){
            if($vo['pid'] == $fid){
                $vo['level'] = $level;
                $column[] = $vo;
                $column = array_merge($column, get_column($array,$type=1,$vo['id'],$level+1));
            }
        }
    }

    return $column;
}
