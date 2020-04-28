<?php
$pay_orderid = 'E'.date("YmdHis").rand(100000,999999);    //订单号
?>
<!DOCTYPE html>
<html lang=zh>
<head>
    <meta charset=UTF-8>
    <title>聚合收银台</title>
    <link href="cashier.css?<?php echo time();?>" rel="stylesheet">
    <style>
        .banklist img{
            width: 100%;
        }
    </style>
</head>
<body>
<div class="tastesdk-box">
    <div class="header clearfix">
        <div class="title">
            <p class="logo">
                <span>收银台</span>
            </p>
            <div class="right">
                <div class="clearfix">
                    <ul class="clearfix">

                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="main">
        <div class="typedemo">
            <div class="demo-pc">
                <div class="pay-jd">
                    <form action="SHF2.php" method="post" autocomplete="off">
                        <input type="hidden" name="orderid" value="<?php echo $pay_orderid;?>">
                   
                        <div class="two-step">
                            <p><strong>请您及时付款，以便订单尽快处理！</strong>请您在提交订单后<span>24小时</span>内完成支付，否则订单会自动取消。</p>
                            <ul class="pay-infor">
                                <li>商品名称：测试应用-支付功能体验(非商品消费)</li>
                                <li>支付金额：<strong><input type="number" name="amount" value="100"> <span>元</span></strong></li>
                                <li>订单编号：<span><?php echo $pay_orderid;?></span></li>
                            </ul>
                            <h5>选择支付方式：</h5>
                            <ul class="pay-label">
                                <li>
                                    <input value="907" name="channel" id="yinlian" type="radio">
                                    <label for="yinlian"><img src="yinlian.png" alt="银联网关"><span>银联网关支付</span></label>
                                </li>
                            </ul>
							<style>
								.plist p{float:left;margin-left:25px;margin-top:10px;border:1px solid #ccc; width:150px;}
								.plist p.current{border:1px solid #E43D40}
								#footer{background:#263445;text-align:center;color:#8392A7;margin-top:30px;padding:20px	}
							</style>
							<script src="/Public/Front/js/jquery.min.js"></script>
							<script>
								$(function(){
									  $('.plist p').click(function() {
										$('.plist p').removeClass('current');
										$(this).addClass('current');
										$('[name=bankCode]').val($(this).find('img').attr('data-pid'));
									});
									$(".pay-label li").click(function(){
										var code = $(this).find("input[name='channel']").val();
										if( code == 907 )
										{
											$(".plist").show();
										}
										else
										{
											$(".plist").hide();
										}
									})
								})
							</script>
							<div>
							<input type="hidden" name="bankCode" value="ICBC" />
                                <div class="plist banklist" style="display: none;;">
                                    <p class="current">
                                        <img src="bank/ICBC.png" alt="工商银行" data-pid="1001">
                                    </p>
                                    <p>
                                        <img src="bank/3005.png" alt="农业银行" data-pid="1002">
                                    </p>
                                    <p>
                                        <img src="bank/CCB.png" alt="建设银行" data-pid="1004">
                                    </p>
                                    <p>
                                        <img src="bank/PSBC.png" alt="邮政储蓄银行" data-pid="1006">
                                    </p>
                                    <p>
                                        <img src="bank/CEB.png" alt="光大银行" data-pid="1008">
                                    </p>
                                    <p>
                                        <img src="bank/CMBC.png" alt="民生银行" data-pid="1010">
                                    </p>
                                    <p>
                                        <img src="bank/SHB.png" alt="上海银行" data-pid="1025">
                                    </p>
                                    <p>
                                        <img src="bank/BJB.png" alt="北京银行" data-pid="1016">
                                    </p>
                                    <div style="clear:left">
                                    </div>
                                </div>
							</div>
							 
                            <div class="btns"> <button type="submit" class="pcdemo-btn sbpay-btn" >立即支付</button></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
