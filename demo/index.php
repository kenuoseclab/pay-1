<?php
$pay_orderid = 'E'.date("YmdHis").rand(10000,99999);    //订单号
$pay_amount = "1.00";    //交易金额
$product_name="测试订单";
?>

<!DOCTYPE html>
<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=0" name="viewport">
    <title>聚合支付 DEMO测试页</title>
    <link href="./demo/css/Reset.css" rel="stylesheet" type="text/css">
    <script src="./demo/js/jquery-1.11.3.min.js"></script>
    <link href="./demo/css/main12.css" rel="stylesheet" type="text/css">
    <style>
        .pay_li input{
            display: none;
        }
        .immediate_pay{
            border:none;
        }
        .PayMethod12
        {
            min-height: 150px;
        }
        @media screen and (max-width: 700px) {
            .PayMethod12{
                padding-top:0;
            }
            .order-amount12{
                margin-bottom: 0;
            }
            .order-amount12,.PayMethod12{
                padding-left: 15px;padding-right: 15px;
            }
        }
        .order-amount12-right input{
            border:1px solid #efefef;
            width:6em;
            padding:5px 20px;
            font-size: 15px;
            text-indent: 0.5em;
            line-height: 1.8em;
        }



    </style>
    <script>
        var lastClickTime;
        var orderNo = "15248148988132090444";
        $(function () {
            $('.PayMethod12 ul li').each(function (index, element) {
                $('.PayMethod12 ul li').eq(5 * index + 4).css('margin-right', '0')
            });

            //支付方式选择
            $('.PayMethod12 ul li').click(function (e) {
                $(this).addClass('active').siblings().removeClass('active');
            });

            $(".pay_li").click(function () {
                $(".pay_li").removeClass("active");
                $(this).addClass("active");
            });
            //点击立即支付按钮
            $(".immediate_pay").click(function () {
                //判断用户是否选择了支付渠道
                if (!$(".pay_li").hasClass("active")) {
                    message_show("请选择支付功能");
                    return false;
                }
                //获取选择的支付渠道的li
                var payli = $(".pay_li[class='pay_li active']");
                if (payli[0]) {
                    prepay(payli.attr("data_power_id"), payli.attr("data_product_id"));
                } else {
                    message_show("请重新选择支付功能");
                }

            });


            $('.mt_agree').click(function (e) {
                $('.mt_agree').fadeOut(300);
            });

            $('.mt_agree_main').click(function (e) {
                return false;
            });

            //弹窗
        // 		$('.pay_sure12').click(function(e) {
        // 			$(this).fadeOut();
        // 		});

            $('.pay_sure12-main').click(function (e) {
                //e. stopPropagation();
                return false;
            });
        });

</script>

    <script>
        if(/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {
            window.location.href = "mobile.php";
        } else {

        }
    </script>
</head>
<body style="background-color:#f9f9f9">
<form action="index1.php" method="post" autocomplete="off">
<!--弹窗开始-->
<div class="pay_sure12">
    <div class="pay_sure12-main">
        <h2>支付确认</h2>
        <h3 class="h3-01">请在新打开的页面进行支付！<br><strong>支付完成前请不要关闭此窗口。</strong></h3>
        <div class="pay_sure12-btngroup">
            <a class="immediate_button immediate_payComplate" onclick="callback_pc();">已完成支付</a>
            <a class="immediate_button immediate_payChange" onclick="hide();">更换支付方式</a>
        </div>
        <p>支付遇到问题？请联系 <span class="f12 blue">智联易付</span> 客服获得帮助。</p>
    </div>
</div>
<!--弹窗结束-->
<!--导航-->
<div class="w100 navBD12">
    <div class="w1080 nav12">
        <div class="nav12-left">
            <a href="/"><img src="/Uploads/logo/5bcb83745a191.png" style="max-height: 45px;"></a>
            <span class="shouyintai"></span>
        </div>
        <div class="nav12-right">
                <span class="contact">支付体验收银台</span>
            </div>
    </div>
</div>
<!--订单金额-->
<div class="w1080 order-amount12" style="border-radius: 1em;">
    <ul class="order-amount12-left">
        <li>
            <span>商品名称：</span>
            <span><?php echo $product_name;?></span>
        </li>
        <li>
            <span>订单编号：</span>
            <span><?php echo $pay_orderid;?></span>
        </li>
    </ul>
    <div class="order-amount12-right">
        <span>订单金额：</span>
        <strong><input type="text" name="amount" value="<?php echo $pay_amount;?>"></strong>
        <span>元</span>
    </div>
</div>
<!--支付方式-->

    <input type="hidden" name="orderid" value="<?php echo $pay_orderid;?>">
   
<div class="w1080 PayMethod12" style="border-radius: 1em;">
    <div class="row">
        <h2>支付方式</h2>
        <ul>



            <label for="zfb">

            
			<li class="pay_li active" data_power_id="3000000011" data_product_id="3000000001">
            <input value="903" checked="checked" name="channel" id="zfb" type="radio">

                <i class="i1"></i>
                <span>支付宝扫码</span>
            </li></label>


           


            <label for="wx">
            <li class="pay_li" data_power_id="3000000031" data_product_id="3000000031">
                <input value="902" name="channel" id="wx" type="radio">

                <i class="i2"></i>
                <span>微信扫码</span>

            </li>  </label>
          
          
	
          
			<label for="qq">
				<li class="pay_li" data_power_id="3000000041" data_product_id="3000000041">
            <input value="908"  name="channel" id="qq" type="radio">

                <i class="i5"></i>
                <span>QQ钱包扫码</span>
            </li></label>


           
			
			
			<label for="baidu">
			<li class="pay_li" data_power_id="3000000051" data_product_id="3000000051">
            <input value="909" name="channel" id="baidu" type="radio">

                <i class="i6"></i>
                <span>百度钱包</span>
            </li></label>
			

			
		
			 <label for="yl">
            <li class="pay_li" data_power_id="3000000061" data_product_id="3000000061">
                <input value="911" name="channel" id="yl" type="radio">

                <i class="i3"></i>
                <span>银联快捷</span>
            </li></label>
			

			
			
			
            <label for="jd">
            <li class="pay_li" data_power_id="3000000071" data_product_id="3000000071">
                <input value="907" name="channel" id="jd" type="radio">

                <i class="i3"></i>
                <span>在线网银</span>

            </li></label>
			




        </ul>
    </div>
</div>
<!--立即支付-->
<div class="w1080 immediate-pay12" style="border-radius: 1em; padding-top:1em; padding-bottom: 1em;padding-right: 1em;">
    <div class="immediate-pay12-right">
        <span>需支付：<strong><?php echo $pay_amount;?></strong>元</span>

        <button type="submit" class="immediate_pay" >立即支付</button>
    </div>
</div>
<div class="mt_agree">
    <div class="mt_agree_main">
        <h2>提示信息</h2>
        <p id="errorContent" style="text-align:center;line-height:36px;"></p>
        <a class="close_btn" onclick="message_hide()">确定</a>
    </div>
</div>
<!--底部-->
<div class="w1080 footer12">
    <p>Copyright © 2018 聚合支付 版权所有</p>
	   

</div>


<script type="text/javascript">
    function message_show(message) {
        $("#errorContent").html(message);
        $('.mt_agree').fadeIn(300);
    }

    function message_hide() {
        $('.mt_agree').fadeOut(300);
    }

</script>
</form>

</body>
</html>