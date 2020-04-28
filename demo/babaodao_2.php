<?php
error_reporting(0);
header("Content-type: text/html; charset=utf-8");
$pay_memberid = "10007";   //商户ID
$pay_orderid = $_POST["orderid"];    //订单号
$pay_amount =  $_POST["amount"];    //交易金额
$pay_bankcode = $_POST["channel"];   //银行编码
if(empty($pay_memberid)||empty($pay_amount)||empty($pay_bankcode)){
    die("信息不完整！");
}
$pay_applydate = date("Y-m-d H:i:s");  //订单时间
$pay_notifyurl = "http://" . $_SERVER['HTTP_HOST'] . "/demo/server.php";   //服务端返回地址
$pay_callbackurl = "http://" . $_SERVER['HTTP_HOST'] . "/demo/page.php";  //页面跳转返回地址
$Md5key = "uve7o5efyh5r89e39o477t3ai0bx0tle";   //密钥
$tjurl = "http://" . $_SERVER['HTTP_HOST'] . "/Pay_Index.html";   //提交地址


//签名参数列表
$native = array(
    "pay_memberid" => $pay_memberid,
    "pay_orderid" => $pay_orderid,
    "pay_amount" => $pay_amount,
    "pay_applydate" => $pay_applydate,
    "pay_bankcode" => $pay_bankcode,
    "pay_notifyurl" => $pay_notifyurl,
    "pay_callbackurl" => $pay_callbackurl,
);
ksort($native);
$md5str = "";
foreach ($native as $key => $val) {
    $md5str = $md5str . $key . "=" . $val . "&";
}
//echo($md5str . "key=" . $Md5key);
$sign = strtoupper(md5($md5str . "key=" . $Md5key));
$native["pay_md5sign"] = $sign;
$native['pay_attach'] = "1234|456";
$native['pay_productname'] ='VIP基础服务';

//自定义参数
$native['id_no'] = $_POST['id_no']; //身份证号
$native['user_name'] = $_POST['user_name'];
$native['mobile'] = $_POST['mobile'];
$native['card_no'] = $_POST['card_no'];

//测试数据 todo:删除
$native['id_no'] = '445221199202071334'; //身份证号
$native['user_name'] = '张剑伟';
$native['mobile'] = '13428282024';
$native['card_no'] = '6225680323000195644';

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>

</head>
<body>
<div class="container">
    <div class="row" style="margin:15px;0;">
        <div class="col-md-12">
            <form class="form-inline" id="payform" method="post" action="<?php echo $tjurl; ?>">
                <?php
                foreach ($native as $key => $val) {
                    echo '<input type="hidden" name="' . $key . '" value="' . $val . '">';
                }
                ?>
                <button type="submit" style='display:none;' ></button>
            </form>
        </div>
    </div>
</div>
<script>
    document.forms['payform'].submit();
</script>
</body>
</html>
