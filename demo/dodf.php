<?php
error_reporting(0);
header("Content-type: text/html; charset=utf-8");	
$mchid = "10002";  
$Md5key = "t4ig5acnpx4fet4zapshjacjd9o4bhbi";
$out_trade_no = date("YmdHis").mt_rand(1000,9999);    //订单号
$_POST['out_trade_no'] = $out_trade_no;
$money =  $_POST["money"];    //交易金额
$_POST['mchid'] = $mchid;
if(empty($mchid)||empty($_POST['money'])||empty($_POST['bankname']) 
	|| empty($_POST['subbranch']) || empty($_POST['accountname']) 
	|| empty($_POST['cardnumber']) || empty($_POST['province']) 
	|| empty($_POST['city'])){
		die("信息不完整！");
}
if($_POST['extends']) {
	$_POST['extends'] = base64_encode(json_encode($_POST['extends']));
}
$tjurl = "http://" . $_SERVER['HTTP_HOST'] . "/Payment_Dfpay_add.html";   //提交地址
ksort($_POST);
//var_dump($_POST);die;
$md5str = "";
foreach ($_POST as $key => $val) {
    $md5str = $md5str . $key . "=" . $val . "&";
}
$sign = strtoupper(md5($md5str . "key=" . $Md5key));
$param = $_POST;
$param["pay_md5sign"] = $sign;
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
                foreach ($param as $key => $val) {
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
