<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>交易成功</title>
<link rel="stylesheet" type="text/css" href="/Public/css/bootstrap.min.css"/>	
<script src="/Public/js/jquery.js" type="text/javascript" charset="utf-8"></script>
<script src="/Public/js/bootstrap.min.js" type="text/javascript" charset="utf-8"></script>
</head>

<body>
<div class="panel panel-default" style="width: 800px; margin: 0px auto; margin-top: 50px; text-align: center; font-family: '微软雅黑';">
  <div class="panel-body" style="color:#F60; font-size:50px;">
  	<h1>充值成功！</h1>
  </div>
  <div class="panel-body" style="text-align: left; padding-left: 30px;">
    <h2>交易金额：<span style="color:#060; font-size:25px;"><{$factMoney}> 元</span></h3>
  </div>
  <div class="panel-body" style="text-align: left; padding-left: 30px;">
    <h2>订单号：<span style="color:#39F"><{$TransID}></span></h3>
  </div>
  <div class="panel-body" style="text-align: left; padding-left: 30px;">
    <h2>交易时间：<span style="color:#69C"><{$SuccTime}></span></h3>
  </div>
  <div class="panel-body">
    <h1><a href="/">返回首页</a></h1>
  </div>
</div>


</body>
</html>
