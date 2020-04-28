#**支付系统接口文档**

系统的注意安装事项

1.Nginx 1.8

2.MySQL 5.5

3.php5.6

宝塔控制台

导入数据库

修改数据库   Application\Common\Conf\db.php

域名替换     Application\Common\Conf\version.php
            Application\Common\Conf\website.php.example
            Application\Common\Conf\website.php


后台登陆地址  网站地址/admin

账号   admin
密码   123456

上游支付对接目录 \Application\Pay\Controller

上游代付对接目录 \Application\Payment\Controller

商户开发文档目录 \Uploads\demo.zip



商户对接教程

支付请求
```Post``` 网站地址/Pay_Index.html

***
>###**请求参数：**

| 参数名称 | 参数含义  | 是否必填  | 参与签名  | 参数说明  |
| ------ |:-----|:-----|:-----|:-----|
|pay_memberid	|商户号	|是	|是	|平台分配商户号
|pay_orderid	|订单号	|是	|是	|上送订单号唯一, 字符长度20
|pay_applydate	|提交时间	|是	|是	|时间格式：2016-12-26 18:18:18
|pay_bankcode	|银行编码	|是	|是	|参考后续说明
|pay_notifyurl	|服务端通知	|是	|是	|服务端返回地址.（POST返回数据）
|pay_callbackurl	|页面跳转通知	|是	|是	|页面跳转返回地址（POST返回数据）
|pay_amount	|订单金额	|是	|是	|商品金额
|pay_md5sign	|MD5签名	|是	|否	|请看MD5签名字段格式
|pay_attach	|附加字段	|否	|否	|此字段在返回时按原样返回(中文需要url编码)
|pay_productname	|商品名称	|否	|否	
|pay_productnum	|商户品数量	|否	|否	
|pay_productdesc	|商品描述	|否	|否	
|pay_producturl	|商户链接地址	|否	|否	

***
>###**签名算法：**

签名生成的通用步骤如下：
第一步，设所有发送或者接收到的数据为集合M，将集合M内非空参数值的参数按照参数名ASCII码从小到大排序（字典序），使用URL键值对的格式（即key1=value1&key2=value2…）拼接成字符串。

第二步，在stringA最后拼接上key得到stringSignTemp字符串，并对stringSignTemp进行MD5运算，再将得到的字符串所有字符转换为大写，得到sign值signValue。

```
stringSignTemp="pay_amount=pay_amount&pay_applydate=pay_applydate&pay_bankcode=pay_bankcode&pay_callbackurl=pay_callbackurl&pay_memberid=pay_memberid&pay_notifyurl=pay_notifyurl&pay_orderid=pay_orderid&key=key"
 sign=MD5(stringSignTemp).toUpperCase()
```
***
>###**支付结果通知：**

如果接收到服务器点对点通讯时，在页面输出“OK”（没有双引号，OK两个字母大写）,否则会重复3次发送点对点通知.

| 参数名称 | 参数含义  |参数说明  |
|:------:|:-----:|:-----|
|memberid	|商户编号| 
|orderid	|订单号| 
|amount	|订单金额|
|datetime	|交易时间| 
|returncode	|交易状态|	“00” 为成功
|attach	|扩展返回| 商户附加数据返回 
|sign	|签名	|请看验证签名字段格式

注：签名见签名算法。

>###**附：银行编码**

| 银行编码 | 银行名称 |
|:----:|:----:|
|901	|微信公众号	
|902	|微信扫码支付		
|903	|支付宝扫码支付	
|904	|支付宝手机	
|905	|QQ手机支付	
|907	|网银支付	
|908	|QQ扫码支付	
|909	|百度钱包
|910	|京东支付

>###**接入示例（PHP）**

``index.php``
```
<?php
error_reporting(0);
header("Content-type: text/html; charset=utf-8");
$pay_memberid = "10002";   //商户ID
$pay_orderid = 'E'.date("YmdHis").rand(100000,999999);    //订单号
$pay_amount = "0.01";    //交易金额
$pay_applydate = date("Y-m-d H:i:s");  //订单时间
$pay_notifyurl = "http://www.yourdomain.com/demo/server.php";   //服务端返回地址
$pay_callbackurl = "http://www.yourdomain.com/demo/page.php";  //页面跳转返回地址
$Md5key = "t4ig5acnpx4fet4zapshjacjd9o4bhbi";   //密钥
$tjurl = "http://www.yourdomain.com/Pay_Index.html";   //提交地址

$pay_bankcode = "903";   //银行编码
//扫码
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
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>支付Demo</title>
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css"
          integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <!--[if lt IE 9]>
    <script src="https://cdn.bootcss.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
<div class="container">
    <div class="row" style="margin:15px;0;">
        <div class="col-md-12">
            <form class="form-inline" method="post" action="<?php echo $tjurl; ?>">
                <?php
                foreach ($native as $key => $val) {
                    echo '<input type="hidden" name="' . $key . '" value="' . $val . '">';
                }
                ?>
                <button type="submit" class="btn btn-success btn-lg">扫码支付(金额：<?php echo $pay_amount; ?>元)</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/bootstrap/3.3.7/js/bootstrap.min.js"
        integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa"
        crossorigin="anonymous"></script>
</body>
</html>



``page.php``同步通知
<?php
header('Content-type:text/html;charset=utf-8');
   $returnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 流水号
            "returncode" => $_REQUEST["returncode"]
        );
      
        $md5key = "t4ig5acnpx4fet4zapshjacjd9o4bhbi";

		ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $md5key)); 

        if ($sign == $_REQUEST["sign"]) {
            if ($_REQUEST["returncode"] == "00") {
				   $str = "交易成功！订单号：".$_REQUEST["orderid"];
                  
				   exit($str);
            }
        }
?>
```

``server.php`` 异步通知

<?php
   $returnArray = array( // 返回字段
            "memberid" => $_REQUEST["memberid"], // 商户ID
            "orderid" =>  $_REQUEST["orderid"], // 订单号
            "amount" =>  $_REQUEST["amount"], // 交易金额
            "datetime" =>  $_REQUEST["datetime"], // 交易时间
            "transaction_id" =>  $_REQUEST["transaction_id"], // 支付流水号
            "returncode" => $_REQUEST["returncode"],
        );
      
        $md5key = "t4ig5acnpx4fet4zapshjacjd9o4bhbi";
   
		ksort($returnArray);
        reset($returnArray);
        $md5str = "";
        foreach ($returnArray as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . $md5key));
        if ($sign == $_REQUEST["sign"]) {
			
            if ($_REQUEST["returncode"] == "00") {
				   $str = "交易成功！订单号：".$_REQUEST["orderid"];
                   file_put_contents("success.txt",$str."\n", FILE_APPEND);
				   exit("ok");
            }
        }
?>
```

新装系统后，一定要设置伪静态，不然后台和商户你是登录不了的
//伪静态开始
location / {
   if (!-e $request_filename) {
   rewrite  ^(.*)$  /index.php?s=$1  last;
   break;
    }
 }
  
 location ^~ /runtime {
   deny all;
}
  location ^~ /Runtime {
       deny all;
  }    
  location ^~ /cert {
       deny all;
   }

///伪静态结束

计划任务部署：建议使用宝塔面板里的 计划任务功能来自动执行
任务标题：#解冻保证金计划任务
执行命令： cd /www/wwwroot/wwww.zhkpay.com; php cli.php unfreeze
任务标题：#自动提交代付计划任务
执行命令：cd /www/wwwroot/wwww.zhkpay.com; php cli.php autodf
任务标题：#自动查询代付计划任务
执行命令：cd /www/wwwroot/www.zhkpay.com; php cli.php autodf_dfquery
任务标题：#解冻资金计划任务
执行命令：cd /www/wwwroot/www.zhkpay.com; php cli.php unfreezeMoney
