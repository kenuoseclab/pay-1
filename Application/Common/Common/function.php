<?php

/**
 * 发送邮件
 * @param $SendAddress
 * @param string $Subject
 * @param string $MsgHTML
 * @param int $Websiteid
 * @return bool|string
 */
function sendEmail($SendAddress, $subject = "支付平台", $msgHTML = "支付平台")
{
    $Email = M('Email');
    $config = $Email->find();
    Vendor('PHPMailer.PHPMailerAutoload');
    $mail = new PHPMailer();
    $mail->SMTPDebug = 0;                               // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = $config['smtp_host'];  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->CharSet = 'UTF-8';
    $mail->Username = $config['smtp_user'];                 // SMTP username
    $mail->Password = $config['smtp_pass'];                           // SMTP password
    if($config['smtp_host'] == 'smtp.qq.com' || $config['smtp_port']==465){
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
    }
    $mail->Port = $config['smtp_port'];                                    // TCP port to connect to
    $mail->setFrom($config['smtp_email'],$config['smtp_name']);
    $mail->addAddress($SendAddress);               // Name is optional
    $mail->AddReplyTo($config['smtp_email'], $config['smtp_name']);
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $msgHTML;
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    return $mail->Send() ? true : $mail->ErrorInfo;
}

function phpexcelobject()
{
    Vendor('PHPExcel175.PHPExcel');
    $objPHPExcel = new PHPExcel();
    return $objPHPExcel;
}

/**
 * 金额格式化函数
 */
function doFormatMoney($money)
{
    $tmp_money = strrev($money);
    $format_money = "";
    for ($i = 3; $i < strlen($money); $i += 3) {
        $format_money .= substr($tmp_money, 0, 3) . ",";
        $tmp_money = substr($tmp_money, 3);
    }
    $format_money .= $tmp_money;
    $format_money = strrev($format_money);
    return $format_money;
}

function random_str($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str ="";
    for ( $i = 0; $i < $length; $i++ ){
        $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
    }
    return $str;
}

function getusername($id)
{
    if ($id == 0) {
        return "-";
    }
    $User = M("Member");
    $username = $User->where(["id" => $id])->getField("username");
    return $username;
}

/**
 * 发送注册邮件
 * @param $username
 * @param $email
 * @param $activate
 * @param $siteconfig
 */
function sendPasswordEmail($username, $email, $password,$siteconfig)
{
    $sitename = $siteconfig["websitename"];
    $domain = $siteconfig["domain"];
    $qqlist = $siteconfig["qq"];
    $tel = $siteconfig["tel"];
    
 $contentstr = "尊敬的合作伙伴：<span style='color:#F30;'>" . $username . "</span> 您好！ <br />";
    $contentstr .= "您已成功开通【" . $sitename . "】商户服务。  <br />";
    $contentstr .= "您的登陆账号为：<span style='color:#F30;'>" . $username . "</span> <br />";
    $contentstr .= "您的登录密码为：<span style='color:#F30;'>" . $password . "</span> <br />";
    $contentstr .= "此为系统邮件，请勿回复。<a href='http://" . $domain . "' target='_blank'>点这里登陆【" . $sitename . "】</a><br />";
    $contentstr .= "进入商户平台，您可以查询产品配置，账户信息、交易明细。<br />";
    $contentstr .= "（1）更改商户的注册信息（包括联系人、地址、电话、收款信息，邮箱等）<br />";
    $contentstr .= "（2）查看订单，查询通道费率，获取对接开发文档<br />";
    $contentstr .= "（3）添加修改收款信息，手机验证等其他功能<br /><br />";
    $contentstr .= "再次感谢您使用" . $sitename . "为您提供的各项服务，为了更好的提升我们的服务质量，您可以随时将宝贵的意见反馈给我们。";
    return sendEmail($email, $sitename . "【账户开通成功】邮件", $contentstr);
}

/**
 * 发送注册邮件
 * @param $username
 * @param $email
 * @param $activate
 * @param $siteconfig
 */
function sendRegemail($username, $email, $activate,$siteconfig)
{
    $sitename = $siteconfig["websitename"];
    $domain = $siteconfig["domain"];
    $qqlist = $siteconfig["qq"];
    $tel = $siteconfig["tel"];
    
    $contentstr = "亲爱的合作伙伴：<span style='color:#F30;'>" . $username . "</span> 您好！ <br />";
    $contentstr .= "感谢您注册【" . $sitename . "】商户服务！ <br />";
    $contentstr .= "您现在可以激活您的账户，激活成功后，您可以使用【" . $sitename . "】提供的各种支付服务。  <br />";
    $contentstr .= "<a href='http://" . $domain . "/Activate_" . $activate . ".html' target='_blank'>点此激活【" . $sitename . "】账户 </a> <br />";
    $contentstr .= "如果上述文字点击无效，请把下面网页地址复制到浏览器地址栏中打开 <br />";
    $contentstr .= "http://" . $domain . "/Activate_" . $activate . ".html <br />";
    $contentstr .= "此为系统邮件，请勿回复";
    $contentstr .= "再次感谢您使用" . $sitename . "为您提供的各项服务，为了更好的提升我们的服务质量，您可以随时将宝贵的意见反馈给我们。";
    
    return sendEmail($email, $sitename . "【账号激活】邮件", $contentstr);
}
/**
 * 找回密码邮件
 * author: feng
 * create: 2017/10/18 22:49
 */
function sendFindpwdemail($username, $email, $activate,$siteconfig)
{
    $sitename = $siteconfig["websitename"];
    $domain = $siteconfig["domain"];
    $qqlist = $siteconfig["qq"];
    $tel = $siteconfig["tel"];

    $contentstr = "找回密码验证码：<span style='color:#F30;'>" . $activate . "</span> ,十分钟内有效 <br />";
    $contentstr .= "此为系统邮件，请勿回复。登陆地址：http://" . $domain . "/";
    return sendEmail($email, $sitename . "【找回密码】邮件", $contentstr);
}

function usertype($user_type)
{
    $title = M('AuthGroup')->where(['id'=>$user_type])->getField('title');
    return $title;
}

function sjusertype($id)
{
    $User = M("User");

    $usertype = $User->where(["id" => $id])->getField("usertype");

    switch ($usertype) {
        case 0:
            return "系统总管理员";
            break;
        case 1:
            return "系统子管理员";
            break;
        case 2:
            return "分站管理员";
            break;
        case 3:
            return "分站子管理员";
            break;
        case 4:
            return "普通商户";
            break;
        case 5:
            return "普通代理商";
            break;
        case 6:
            return "独立代理商";
            break;
    }
}

/**
 * 获取代理用户名
 * @param $id
 * @param int $s
 * @return string
 */
function getParentName($uid, $s = 0)
{
    $User = M("Member");
    if (! $uid) {
        return "-";
    }
    $find = $User->where(["id" => $uid])->field('id,username,groupid')->find();
    if ($find["groupid"] == 1) {
        return "总管理员";
    } else {
        if ($s == 0) {
            return '<a href="'.U('Admin/User/index',['username'=>$find['username']]).'">' . $find["username"] .
                '</a>';
        } else {
            return $find["username"];
        }
    }
}

function shanghubianhao($id)
{
    return 10000 + $id;
}

function zhuangtai($id)
{
    switch ($id) {
        case 0:
            return '<span class="label label-default">未激活</span>';
            break;
        case 1:
            return '<span class="label label-success">正常</span>';
            break;
        case 2:
            return '<span class="label label-danger">已禁用</span>';
            break;
    }
}

function renzheng($id)
{
    $Userverifyinfo = M("Userverifyinfo");
    $status = $Userverifyinfo->where(["userid" => $id])->getField("status");
    switch ($status) {
        case 0:
            return '<span class="label label-default">未认证</span>';
            break;
        case 1:
            return '<span class="label label-success">已认证</span>';
            break;
        case 2:
            return '<span class="label label-warning">等待审核</span>';
            break;
    }
}

function zhanghuzongyue($id)
{
    $Money = M("Money");
    $summoney = $Money->where(["userid" => $id])->getField("money");
    return $summoney ? $summoney : '0.00';
}

function qianbaoyue($id)
{
    $Money = M("Money");
    $wallet = $Money->where(["userid" => $id])->getField("wallet");
    return $wallet;
}

function status($pay_status)
{
    switch ($pay_status) {
        case 0:
            return "<span style='color:#f00'>未处理</span>";
            break;
        case 1:
            return "<span style='color:#F60'>成功,未返回</span>";
            break;
        case 2:
            return "<span style='color:#030'>成功,已返回</span>";
            break;
        case 3:
            return "<span style='color:#0f0'>超时订单</span>";
            break;
    }
}

function tongji($id)
{
    if($id){
        $Websiteconfig = D("Websiteconfig");
        $tongji = $Websiteconfig->where("websiteid=0")->getField("tongji");
        $content = str_replace("&lt;", "<", $tongji);
        $content = str_replace("&gt;", ">", $content);
        $content = str_replace("%22", "", $content);
        $content = str_replace("&quot;", '"', $content);
        $content = str_replace("&amp;", "&", $content);

        return '<div style="display:none;">' . $content . '</div>';
    }else{
        return '<div style="display:none;"></div>';
    }

}

function HTMLHTML($content)
{
     $content = str_replace("&lt;","<",$content);
     $content = str_replace("&gt;",">",$content);
     $content = str_replace("%22","",$content);
     $content = str_replace("&quot;",'"',$content);
     $content=str_replace( "&amp;","&",$content);
    return $content;
}

function browserecord($articleid)
{
    $Browserecord = M("Browserecord");

    $count = $Browserecord->where(array('articleid'=>$articleid,'userid'=>session("userid")))->count();
    $str = "";
    if ($count <= 0) {
        $str = $str . '<img src="/Public/images/new.gif">';
    }
    
    $Article = M("Article");

    $count = $Article->where(array('id'=>$articleid,'jieshouuserlist'=>array('like','%" . session("userid") . "|%')))->count();
    if ($count <= 0) {
        $str = $str . ' <img src="/Public/images/shi.png">';
    }
    
    return $str;
}

function browsenum($articleid)
{
    $Browserecord = M("Browserecord");
    $count = $Browserecord->where(["articleid" => $articleid])->count();
    if ($count > 0) {
        return $count;
    } else {
        return 0;
    }
}

function jieshouuserlist($list)
{
    if ($list == "0|") {
        return "全部";
    } else {
        $array = explode("|", $list);
        $str = "";
        foreach ($array as $key => $val) {
            if ($val) {
                $str = $str . "【" . ($val + 10000) . "】";
            }
        }
        return $str;
    }
}

function zjbdlx($lx, $orderid)
{
    $str = "";
    switch ($lx) {
        case 1:
            $str = "账户充值(<span style='color:#999'>" . $orderid . "</span>)";
            break;
    }
    return $str;
}

function bdje($money)
{
    $strmoney = "";
    if ($money < 0) {
        $strmoney = "<span style='color:#f6a000'>" . $money . "</spa>";
    } else {
        $strmoney = "<span style='color:#53a057'>+" . $money . "</spa>";
    }
    return $strmoney;
}

/**
 * 获取产品名称
 * @param $id
 * @return mixed
 */
function getProduct($id)
{
    $Payapi = M("Product");
    $name = $Payapi->where(array('id'=>$id))->getField("name");
    return $name;
}

/**
 * 获取支付类型
 * @param $id
 * @return mixed
 */
function getPaytype($id){
    $paytyps = C('PAYTYPES');
    foreach ($paytyps as $item){
        $return[$item['id']]= $item;
    }
    return $return[$id]['name'] ? $return[$id]['name'] : '----';
}
/**
 * 资金变动记录
 * @param $ArrayField
 */
function moneychangeadd($ArrayField)
{
    $Moneychange = M("Moneychange");
    foreach ($ArrayField as $key => $val) {
        $data[$key] = $val;
    }
    $res = $Moneychange->add($data);
    return $res;
}

/**
 * 金额格式化
 * @param $s
 * @return mixed|string
 */
function del0($s)
{ // 去除数字后面的零
    $s = trim(strval($s));
    if (preg_match('#^-?\d+?\.0+$#', $s)) {
        return preg_replace('#^(-?\d+?)\.0+$#', '$1', $s);
    }
    if (preg_match('#^-?\d+?\.[0-9]+?0+$#', $s)) {
        return preg_replace('#^(-?\d+\.[0-9]+?)0+$#', '$1', $s);
    }
    return $s;
}

function huoquddlx($transid){
    $Order = M("Order");
    $ddlx = $Order->where(["pay_orderid" => $transid])->getField("ddlx");
    $ddlx==0?$lxname="<spans style='color:#060;'>充值订单</span>":$lxname="收款订单";
    return $lxname;
}


function randpw($len=8,$format='ALL'){
    $is_abc = $is_numer = 0;
    $password = $tmp ='';
    switch($format){
        case 'ALL':
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
        case 'CHAR':
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
        case 'NUMBER':
            $chars='0123456789';
            break;
        default :
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            break;
    }
    mt_srand((double)microtime()*1000000*getmypid());

    while(strlen($password)<$len){

        $tmp =substr($chars,(mt_rand()%strlen($chars)),1);
        if(($is_numer <> 1 && is_numeric($tmp) && $tmp > 0 )|| $format == 'CHAR'){
            $is_numer = 1;
        }
        if(($is_abc <> 1 && preg_match('/[a-zA-Z]/',$tmp)) || $format == 'NUMBER'){
            $is_abc = 1;
        }
        $password.= $tmp;
    }
    if($is_numer <> 1 || $is_abc <> 1 || empty($password) ){
        $password = randpw($len,$format);
    }
    return $password;
}
/*
 * HTTP、HTTPS判断
 */
function is_https(){
    if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return TRUE;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return TRUE;
    } elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return TRUE;
    }
    return FALSE;
}
function arrayToXml($arr)
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?><xml>';
    foreach ($arr as $key=>$val)
    {
        if (is_numeric($val)){
            $xml.="<".$key.">".$val."</".$key.">";
        }else{
            $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
    }
    $xml.="</xml>";
    return $xml;
}

//将XML转为array
function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

//获取订货号
function get_requestord()
{
    return date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid('',true), 7, 17), 1))), 0, 6);
}

/**
 * API行为日志
 * @param $orderid
 * @param $params
 * @param int $level
 * @param string $memo
 * @return bool
 */
function acetion_log($orderid,$params,$memo=""){
    $rows = [
        'remote_addr'=>$_SERVER['REMOTE_ADDR'],
        'http_refferer'=>$_SERVER['HTTP_REFERER'],
        'http_user_agent'=>$_SERVER['HTTP_USER_AGENT'],
        'params'=> $params,
        'orderid' => $orderid,
        'memo' => $memo,
    ];
    M('Actionlog')->add($rows);
    return true;
}

//判断是否是手机端还是电脑端
function isMobile() {
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
    $mobile_browser = '0';
    if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
        $mobile_browser++;
    if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))
        $mobile_browser++;
    if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
        $mobile_browser++;
    if(isset($_SERVER['HTTP_PROFILE']))
        $mobile_browser++;
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
    $mobile_agents = array(
        'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
        'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
        'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
        'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
        'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
        'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
        'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
        'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
        'wapr','webc','winw','winw','xda','xda-'
    );
    if(in_array($mobile_ua, $mobile_agents))
        $mobile_browser++;
    if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
        $mobile_browser++;
    // Pre-final check to reset everything if the user is on Windows
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
        $mobile_browser=0;
    // But WP7 is also Windows, with a slightly different characteristic
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
        $mobile_browser++;
    if($mobile_browser>0)
        return true;
    else
        return false;
}

//导出CSV
function exportCsv($list,$title){
    $file_name="CSV".date("mdHis",time()).".csv";
    header ( 'Content-Type: application/vnd.ms-excel' );
    header ( 'Content-Disposition: attachment;filename='.$file_name );
    header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
    header('Expires:0');
    header('Pragma:public');
    $file = fopen('php://output',"a");
    $limit=10000;
    $calc=0;
    //列名
    foreach ($title as $v){
        $tit[]=iconv('UTF-8', 'GB2312//IGNORE',$v);
    }
    //将数据通过fputcsv写到文件句柄
    fputcsv($file,$tit);

    foreach ($list as $v){
        $calc++;
        if($limit==$calc){
            ob_flush();
            flush();
            $calc=0;
        }
        foreach ($v as $t){
            $tarr[]=iconv('UTF-8', 'GB2312//IGNORE',$t."\t");
        }
        fputcsv($file,$tarr);
        unset($tarr);
    }
    unset($list);
    fclose($file);
    exit();
}

/**
 *
 */
function sendForm($url,$data,$referer){
    $headers['Content-Type'] = "application/x-www-form-urlencoded; charset=utf-8";
    $headerArr = array();
    foreach( $headers as $n => $v ) {
        $headerArr[] = $n .':' . $v;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
    curl_setopt($ch, CURLOPT_REFERER, "http://".$referer."/");
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 * @param $word 要写入日志里的文本内容 默认值：空值
 */
function logResult($word = '') {
    $fp = fopen ( "log.txt", "a" );
    flock ( $fp, LOCK_EX );
    fwrite ( $fp, "执行日期：" . strftime ( "%Y%m%d%H%M%S", time () ) . "\n" . $word . "\n" );
    flock ( $fp, LOCK_UN );
    fclose ( $fp );
}

/**
 * 权重
 * @param $array
 * @return array
 */
function getWeight($proArr) {
    $result = array();
    $weight = 0;
    $users = array();
    foreach ($proArr as $key => $val) {
        $oneWeight = (int)$val['weight'] ? $val['weight'] : 1;
        $weight += $oneWeight;
        for ($i = 0; $i < $oneWeight; $i ++) {
            $users[] = $key;
        }
        // $arr[$key] = $val['weight'];
    }
    $rr=$users[rand(0, $weight-1)];
    return $proArr[$rr];
}
/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = NULL,$format='Y-m-d H:i'){
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

function sendSMS($mobile,$templateCode,$templeContent)
{
    $config =  M('sms')->find();
    if(!$config['is_open'])
        return;
    if(!isset($config['sms_channel']) || !$config['sms_channel']) {
        $config['sms_channel'] = 'aliyun';
    }
    switch ($config['sms_channel']) {
        case 'aliyun'://阿里云
            vendor('AlidayuLite.SmsApi');
            $sms = new \Aliyun\DySDKLite\Sms\SmsApi($config['app_key'], $config['app_secret']); // 请参阅 https://ak-console.aliyun.com/ 获取AK信息
            $response = $sms->sendSms(
                $config['sign_name'], // 短信签名
                $templateCode, // 短信模板编号
                $mobile, // 短信接收者
                $templeContent
            );        	
            return $response->Code == 'OK' ? true : $response->Message;
            break;
        case 'smsbao'://短信宝
            $sms = new \Org\Util\SmsBao($config['smsbao_user'], $config['smsbao_pass']);
            $content  = "【".$config['sign_name']."】您的验证码为：{$templeContent['code']}，该验证码5分钟内有效，请勿泄露他人。";
            $response = $sms->sendSms($mobile, $content);
            return $response->Code == '0' ? true : $response->Message;
            break;
        default:
            return;
    }


}
//    /**
//     * 旧阿里大于发送短信
//     * @param $mobile  手机号码
//     * @param $code    验证码
//     * @return bool    短信发送成功返回true失败返回false
//     */
function sendSMS1($mobile,$templateCode,$templeContent)
{
    //时区设置：亚洲/上海
    date_default_timezone_set('Asia/Shanghai');
    //这个是你下面实例化的类
    vendor('Alidayu.TopClient');
    //这个是topClient 里面需要实例化一个类所以我们也要加载 不然会报错
    vendor('Alidayu.ResultSet');
    //这个是成功后返回的信息文件
    vendor('Alidayu.RequestCheckUtil');
    //这个是错误信息返回的一个php文件
    vendor('Alidayu.TopLogger');
    //这个也是你下面示例的类
    vendor('Alidayu.AlibabaAliqinFcSmsNumSendRequest');

    $c = new \TopClient;
    $config =  M('sms')->find();
    if(!$config['is_open'])
        return;

    //App Key的值 这个在开发者控制台的应用管理点击你添加过的应用就有了
    $c->appkey = $config['app_key'];
    //App Secret的值也是在哪里一起的 你点击查看就有了
    $c->secretKey =$config['app_secret'];
    //这个是用户名记录那个用户操作
    $req = new \AlibabaAliqinFcSmsNumSendRequest;
    //代理人编号 可选
    $req->setExtend("123456");
    //短信类型 此处默认 不用修改
    $req->setSmsType("normal");
    //短信签名 必须
    $req->setSmsFreeSignName($config['sign_name']);

    //短信模板 必须
    //$req->setSmsParam("{\"code\":\"$code\"}");
    $req->setSmsParam($templeContent);
    //短信接收号码 支持单个或多个手机号码，传入号码为11位手机号码，不能加0或+86。群发短信需传入多个号码，以英文逗号分隔，
    $req->setRecNum("$mobile");
    //短信模板ID，传入的模板必须是在阿里大鱼“管理中心-短信模板管理”中的可用模板。
    $req->setSmsTemplateCode($templateCode); // templateCode

    $c->format='json';
    //发送短信
    $resp = $c->execute($req);

    //短信发送成功返回True，失败返回false
    //if (!$resp)
    if ($resp && $resp->result)   // if($resp->result->success == true)
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 生成用户数据
 * @param  array $user       
 * @param  array $siteconfig 网站设置
 * @return 
 */
function generateUser($user, $siteconfig = null)
{
    if (empty($siteconfig)) {
        $siteconfig = M("Websiteconfig")->find();
    }

    $password = $user['password'] ?: random_str(6);

    //激活码
    $activatecode = md5(md5($user['username']) . md5($password) . md5($user['email']).C('DATA_AUTH_KEY'));


    //是否需要认证
    $authorized = $siteconfig['authorized'] ? 0 : 1;

    //是否需要激活
    $register_need_activate = $siteconfig['register_need_activate'] ? 0 : 1;

    $salt = rand(1000,9999);
    if($user['verifycode']['fmusernameid'] && !$user['verifycode']['is_admin']) {
        $parentid = $user['verifycode']['fmusernameid'];
    } else {
        $parentid = 0;
    }

    //写入
    $userdata = array(
        'origin_password' => $password,
        'username'=>$user['username'],
        'password'=>md5($password.$salt),
        'paypassword'=>md5('123456'),
        'parentid'=>$parentid ,
        'email'=>$user['email'],
        'groupid'=> $user['verifycode']['regtype'] ? $user['verifycode']['regtype'] :4,
        'regdatetime'=>time(),
        'activate'=>$activatecode,
        'authorized'=>$authorized,
        'apikey'=>random_str(),
        'salt'=>$salt,
        'status'=>$register_need_activate,
        'df_domain'=>'',
        'df_ip'=>''
    );
    if($siteconfig['random_mchno'] == 1) {
        $mchno = generateMchNo();
        $userdata['id'] = $mchno;
    }
    return array_merge($user, $userdata);
}
/**
 * 查询短信id
 * @param  [type] $callIndex 短信调用代码
 * @return [type]            短信模板信息
 */
function getSmsTemplateCode($callIndex)
{
    $res = M('sms_template')->where(['call_index' => $callIndex])->find();
    return $res;
}
/**
 * 检查是否需要发送短信
 */
function smsStatus()
{
    $config =  M('sms')->find();

    if(!$config['is_open'])
        return 0;

    return 1;
}
/**
 * 判断是否微信浏览器
 */
function is_weixin() { 
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) { 
        return true; 
    } return false; 
}

/**
 * 获取来路域名
 */
function getHttpReferer() {

    if(!isset($_SERVER["HTTP_REFERER"])) {
        return '';
    }
    $url = $_SERVER["HTTP_REFERER"];
    return $url;
}

/**
 * 取得根域名
 * @param type $domain 域名
 * @return string 返回根域名
 */
function getBaseDomain($url=''){
    if(!$url){
        return $url;
    }
    #列举域名中固定元素
    $state_domain = array(
        'al','dz','af','ar','ae','aw','om','az','eg','et','ie','ee','ad','ao','ai','ag','at','au','mo','bb','pg','bs','pk','py','ps','bh','pa','br','by','bm','bg','mp','bj','be','is','pr','ba','pl','bo','bz','bw','bt','bf','bi','bv','kp','gq','dk','de','tl','tp','tg','dm','do','ru','ec','er','fr','fo','pf','gf','tf','va','ph','fj','fi','cv','fk','gm','cg','cd','co','cr','gg','gd','gl','ge','cu','gp','gu','gy','kz','ht','kr','nl','an','hm','hn','ki','dj','kg','gn','gw','ca','gh','ga','kh','cz','zw','cm','qa','ky','km','ci','kw','cc','hr','ke','ck','lv','ls','la','lb','lt','lr','ly','li','re','lu','rw','ro','mg','im','mv','mt','mw','my','ml','mk','mh','mq','yt','mu','mr','us','um','as','vi','mn','ms','bd','pe','fm','mm','md','ma','mc','mz','mx','nr','np','ni','ne','ng','nu','no','nf','na','za','aq','gs','eu','pw','pn','pt','jp','se','ch','sv','ws','yu','sl','sn','cy','sc','sa','cx','st','sh','kn','lc','sm','pm','vc','lk','sk','si','sj','sz','sd','sr','sb','so','tj','tw','th','tz','to','tc','tt','tn','tv','tr','tm','tk','wf','vu','gt','ve','bn','ug','ua','uy','uz','es','eh','gr','hk','sg','nc','nz','hu','sy','jm','am','ac','ye','iq','ir','il','it','in','id','uk','vg','io','jo','vn','zm','je','td','gi','cl','cf','cn','yr','com','arpa','edu','gov','int','mil','net','org','biz','info','pro','name','museum','coop','aero','xxx','idv','me','mobi','asia','ax','bl','bq','cat','cw','gb','jobs','mf','rs','su','sx','tel','travel'
    );

    if(!preg_match("/^http/is", $url)){
        $url="http://".$url;
    }

    $res = null;
    $res->domain = null;
    $res->host = null;
    $url_parse = parse_url(strtolower($url));
    $urlarr = explode(".", $url_parse['host']);
    $count = count($urlarr);

    if($count <= 2){
        #当域名直接根形式不存在host部分直接输出
        $res->domain = $url_parse['host'];
    }elseif($count > 2){
        $last = array_pop($urlarr);
        $last_1 = array_pop($urlarr);
        $last_2 = array_pop($urlarr);

        $res->domain = $last_1.'.'.$last;
        $res->host = $last_2;

        if(in_array($last, $state_domain)){
            $res->domain=$last_1.'.'.$last;
            $res->host=implode('.', $urlarr);
        }

        if(in_array($last_1, $state_domain)){
            $res->domain = $last_2.'.'.$last_1.'.'.$last;
            $res->host = implode('.', $urlarr);
        }
    }
    return $res->domain;
}
/**
 * 检查代付提交域名是被已报备
 * @param string $referer 来源地址
 * @param string $domain_recorded 已报备域名
 * @return boolean
 */
function checkDfDomain($referer, $domain_recorded) {
    if(!$referer) {
        return false;
    }
    $domain = getBaseDomain($referer);
    $domain_arr = explode("\r\n", $domain_recorded);
    if(empty($domain_arr)) {
        return false;
    }
    foreach ($domain_arr as $k => $v) {
        if($v) {
            $domain_arr[$k] = trim(getBaseDomain($v));
        }
    }
    if(in_array($domain, $domain_arr)) {
        return true;
    }
    return false;
}

/**
 * 检查代付提交IP是被已报备
 * @param string $referer 来源IP
 * @param string $domain_recorded 已报备域名
 * @return boolean
 */
function checkDfIp($ip_recorded) {
    $ip = get_client_ip();
    $ip_arr = explode("\r\n", $ip_recorded);
    if(empty($ip_arr)) {
        return false;
    }
    foreach ($ip_arr as $k => $v) {
        if($v) {
            $ip_arr[$k] = trim($v);
        }
    }
    if(in_array($ip, $ip_arr)) {
        return true;
    }
    return false;
}
/*
 * 生成商户号
 */
function generateMchNo(){
    $mchno = date('ym').mt_rand(10000,99999);
    $count = M('Member')->where(['id'=>$mchno])->count();
    if($count == 0) {
        return $mchno;
    } else {
        generateMchNo();
    }
}

/**
 *[验证传输的数据]
 * @param array $datas [键是要验证的变量名，值是错误信息]
 *
 * 例子：
 * $datas = ['a'=>'错误！'];
 * verifyData($datas)
 */
function verifyData($datas){
    if(is_array($datas)){
        foreach($datas as $k => $v){
            $return[$k] = isset($_REQUEST[$k]) ? trim($_REQUEST[$k]) : '';
            if($return[$k] === ''){
                showError($v);
            }
        }
        return $return;
    }
}

/**
 *[验证用户是否登录]
 */
function isLogin(){
    $user = session('admin_auth');
    !is_array($user) && showError('访问错误！');
    ksort($user); //排序
    session('admin_auth_sign') != sha1( http_build_query($user) ) && showError('访问错误！');
}

/**
 *[返回错误信息]
 *@param string $msg [错误信息]
 *@param array  $fields [返回的错误数据]
 *
 *例子：
 *showError('错误了');
 */
function showError($msg='操作失败', $fields=array()){
    header('Content-Type:application/json; charset=utf-8');
    $data = array('status'=>'error', 'msg'=>$msg, 'data'=>$fields);
    echo json_encode($data,320);
    exit;
}

/**
 *[返回成功信息]
 *@param string $msg [成功信息]
 *@param array  $fields [返回的成功数据]
 *
 *例子：
 *showSuccess('ok');
 */
function showSuccess($msg='操作成功',$fields=array()){
    header('Content-Type:application/json; charset=utf-8');
    $data = array('status'=>'success', 'msg'=>$msg, 'data'=>$fields);
    echo json_encode($data,320);
    exit;
}


function md5Sign($data, $key, $connect='',$is_md5 = true)
{
    ksort($data);
    $string = '';
    foreach( $data as $k => $vo ){
        if($vo !== '')
            $string .=  $k . '=' . $vo . '&' ;
    }
    $string = rtrim($string, '&');
    $result = $string . $connect . $key;

    return $is_md5 ? md5($result) : $result;

}

function curlPost($url, $data = '', $headers = array(), $agent = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // https请求 不验证证书和hosts
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    $file_contents = curl_exec($ch);

    //这里解析
    return $file_contents;
}

/**
 * [rsaEncryptVerify description]
 * @param  [string] $string    [加密的数据]
 * @param  [string] $file_path [加密的证书路径或者证书的内容]
 * @param  string $sign      [验证的秘钥，没有默认为加密]
 * @param  [type] $type      [description]
 * @return [type]            [description]
 */
function rsaEncryptVerify($string, $file_path, $sign = '', $type= OPENSSL_PKCS1_PADDING)
{
    $content =is_file($file_path) ? file_get_contents($file_path) : $file_path;
    if($sign ==''){
        $key = openssl_get_privatekey($content);
        openssl_sign($string, $result, $key , $type);
        return base64_encode($result);
    }else{
        $key = openssl_get_publickey($content);
        return (bool)openssl_verify($string, $sign, $key, $type);
    }
}

function nonceStr($length = 32)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++)
    {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function diffBetweenTwoDays ($day1, $day2)
{
    $second1 = strtotime($day1);
    $second2 = strtotime($day2);

    if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
    }
    return ($second1 - $second2) / 86400;
}

/**
 * 检查管理员是否绑定手机号
 */
function adminMobileBind($id)
{
    $mobile =  M('Admin')->where(['id' => $id])->getField('mobile');
    if(!$mobile) {
        return 0;
    }
    return 1;
}

/**
 * 检查管理员是否绑定谷歌身份验证器
 */
function adminGoogleBind($id)
{
    $googleAuth   = M('Websiteconfig')->getField('google_auth');
    if(!$googleAuth) {
        return 0;
    }
    $google_secret_key =  M('Admin')->where(['id' => $id])->getField('google_secret_key');
    if(!$google_secret_key) {
        return 0;
    }
    return 1;
}

/**
 * 使用PHPEXCEL导出数据为excel表格
 * @param $data    一个二维数组,结构如同从数据库查出来的数组
 * @param $title   excel的第一行标题,一个数组,如果为空则没有标题
 * @param $numberField 数组字段
 * @param $filename 下载的文件名
 * @examlpe
$stu = M ('User');
 * $arr = $stu -> select();
 * exportexcel($arr,array('id','账户','密码','昵称'),'文件名!');
 */
function exportexcel($data = array(), $title = array(), $numberField = [], $filename = 'report') {

    ini_set("memory_limit", "1024M"); // 设置php可使用内存
    set_time_limit(0);  # 设置执行时间最大值
    define('EOL', '<br />');
    vendor("PHPExcel.PHPExcel");
    $objPHPExcel = new \PHPExcel();
    $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
    if (!\PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
        die($cacheMethod . " 缓存方法不可用" . EOL);
    }
    $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
    $objPHPExcel->setActiveSheetIndex(0);
    if (!empty($title)) {
        foreach ($title as $k => $v) {
            $objPHPExcel->getActiveSheet()->getStyle($cellName[$k])->getNumberFormat()
                ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
            $objPHPExcel->getActiveSheet()->setCellValue($cellName[$k].'1', $v);
        }
    }
    if (!empty($data)) {
        $Cellkey = 2;
        foreach ($data as $key => $val) {
            $i = 0;
            foreach ($val as $ck => $cv) {
                if(strpos($cv, '=') === 0){ $cv= "'".$cv;}
                if(in_array($ck, $numberField)) {
                    $objPHPExcel->getActiveSheet()->setCellValue($cellName[$i]. $Cellkey, $cv);
                } else {
                    $objPHPExcel->getActiveSheet()->setCellValueExplicit($cellName[$i]. $Cellkey, $cv, PHPExcel_Cell_DataType::TYPE_STRING);
                }
                $i++;
            }
            $Cellkey++;
        }
        $objPHPExcel->setActiveSheetIndex(0);
        //$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $filename = "EXCEL".date("mdHis",time());
        \ob_end_clean();
        header("Content-type:application/octet-stream");
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=" . $filename . ".xlsx");
        header("Pragma: no-cache");
        header("Expires: 0");
        $objWriter->save('php://output');
    } else {
        die(" 暂无数据" . EOL);
    }
}
function getRoot($file){

    $fileUrl=str_replace('\\','/',realpath(dirname($file).'/'))."/";
    $genIndex=strpos($fileUrl,'/Application/');
    if($genIndex>0){
        $rootPath=substr($fileUrl,0,$genIndex).'/';
        return $rootPath;
    }
}

/**
 * 将秒数转换为时间（年、天、小时、分、秒）
 * @param number $time 秒数
 * @return string
 */
function sec2Time($time) {
	if(is_numeric($time)){
		$value = array(
			"years" => 0, "days" => 0, "hours" => 0,
			"minutes" => 0, "seconds" => 0,
		);
		if($time >= 31556926){
			$value["years"] = floor($time/31556926);
			$time = ($time%31556926);
		}
		if($time >= 86400){
			$value["days"] = floor($time/86400);
			$time = ($time%86400);
		}
		if($time >= 3600){
			$value["hours"] = floor($time/3600);
			$time = ($time%3600);
		}
		if($time >= 60){
			$value["minutes"] = floor($time/60);
			$time = ($time%60);
		}
		$value["seconds"] = floor($time);
		$t = '';
		if($value["years"]>0) {
			$t .= $value["years"] ."年";
		}
		if($value["days"]>0) {
			$t .= $value["days"] ."天";
		}
		if($value["hours"]>0) {
			$t .= $value["hours"] ."小时";
		}
		if($value["minutes"]>0) {
			$t .= $value["minutes"] ."分";
		}
		if($value["seconds"]>0) {
			$t .= $value["seconds"] ."秒";
		}
		Return $t;

	} else {
		return (bool) FALSE;
	}
}

/**
 * 检查用户验证信息
 * @param string $uid 用户ID
 * @param string $auth_type 0：商户登录 1：后台登录 2：商户短信验证 3：后台短信验证 4：谷歌令牌验证 5：支付密码验证
 * @return array
 */

function check_auth_error($uid, $auth_type=0) {

    $siteconfig = M("Websiteconfig")->find();
    $map['uid'] = $uid;
    $map['auth_type'] = $auth_type;
    //最后错误时间
    $last_time = M('auth_error_log')->where($map)->order('id desc')->getField('ctime');
    //删除过期的记录
    if($last_time) {
        $expire = $last_time-$siteconfig['auth_error_ban_time']*60;
        M('auth_error_log')->where(['uid' => $uid,'ctime'=>['elt', $expire]])->delete();
        $map['ctime'] = ['gt',$expire];
        $count = M('auth_error_log')->where($map)->count();
        if($count >= $siteconfig['max_auth_error_times']) {
            $time = $last_time + $siteconfig['auth_error_ban_time']*60 - time();
            $time_str = sec2Time($time);
            return ['status'=>0, 'msg' =>'输入错误次数过多，请于'.$time_str.'后再试!'];
        }
    }
    return ['status'=>1];
}

/**
 * 记录用户验证错误信息
 * @param string $uid 用户ID
 * @param string $auth_type 0：商户登录 1：后台登录 2：商户短信验证 3：后台短信验证 4：谷歌令牌验证 5：支付密码验证
 * @return null
 */
function log_auth_error($uid, $auth_type=0) {

    $log['auth_type'] = $auth_type;
    $log['uid'] = $uid;
    $log['ctime'] = time();
    M('auth_error_log')->add($log);
}

/**
 * 清除用户验证错误信息
 * @param string $uid 用户ID
 * @param string $auth_type 0：商户登录 1：后台登录 2：商户短信验证 3：后台短信验证 4：谷歌令牌验证 5：支付密码验证
 * @return null
 */
function clear_auth_error($uid, $auth_type=0) {

    $map['auth_type'] = $auth_type;
    $map['uid'] = $uid;
    M('auth_error_log')->where($map)->delete();
}

/**
 *递归创建多级目录
 */
function mkdirs($dir, $mode = 0777)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
    if (!mkdirs(dirname($dir), $mode)) return FALSE;

    return @mkdir($dir, $mode);
}

/**
 *记录向下游异步通知
 */
function log_server_notify($orderid, $url, $notifystr, $httpCode, $return) {
    $filePath = './Data/server_notify/';
    if(mkdirs($filePath)) {
        $destination = $filePath.date('y_m_d').'.log';
        if(!file_exists($destination)) {
            fopen($destination,   'wb ');
        }
        file_put_contents($destination, "【".date('Y-m-d H:i:s')."】\r\n订单号：".$orderid."\r\n通知地址：".$url."\r\n通知报文：".$notifystr."\r\nHTTP状态：".$httpCode."\r\n返回：".$return."\r\n\r\n",FILE_APPEND);
        return true;
    }
    return false;
}
?>
