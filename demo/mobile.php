<?php
$pay_orderid = 'E'.date("YmdHis").rand(10000,99999);    //订单号
$pay_amount = "1.00";    //交易金额
$product_name="H5测试订单";
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black" />
<meta name="format-detection" content="telephone=no" />
<meta name="format-detection" content="email=no" />
<!-- 启用360浏览器的极速模式(webkit) -->
<meta name="renderer" content="webkit">
<!-- 避免IE使用兼容模式 -->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<!-- 针对手持设备优化，主要是针对一些老的不识别viewport的浏览器，比如黑莓 -->
<meta name="HandheldFriendly" content="true">
<!-- 微软的老式浏览器 -->
<meta name="MobileOptimized" content="320">
<!-- uc强制竖屏 -->
<meta name="screen-orientation" content="portrait">
<!-- QQ强制竖屏 -->
<meta name="x5-orientation" content="portrait">
<!-- UC强制全屏 -->
<meta name="full-screen" content="yes">
<!-- QQ强制全屏 -->
<meta name="x5-fullscreen" content="true">
<!-- UC应用模式 -->
<meta name="browsermode" content="application">
<!-- QQ应用模式 -->
<meta name="x5-page-mode" content="app">
<!--这meta的作用就是删除默认的苹果工具栏和菜单栏-->
<meta name="apple-mobile-web-app-capable" content="yes">
<!--网站开启对web app程序的支持-->
<meta name="apple-touch-fullscreen" content="yes">
<!--在web app应用下状态条（屏幕顶部条）的颜色-->
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<!-- windows phone 点击无高光 -->
<meta name="msapplication-tap-highlight" content="no">
<!--移动web页面是否自动探测电话号码-->
<meta http-equiv="x-rim-auto-match" content="none">
<!--移动端版本兼容 start -->
<meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=0" name="viewport" />
<!--移动端版本兼容 end -->
    <title>聚合支付 移动收银台</title>
    <link href="./demo/css/Reset.css" rel="stylesheet" type="text/css">
    <script src="./demo/js/jquery-1.11.3.min.js"></script>
    <link href="./demo/css/main12.css" rel="stylesheet" type="text/css">
    <style>	.pc_dis{		display:none;	}
        .pay_li input{
            display: none;
        }
		.border_radis{
			border-radius:0.5em
		}
		.immediate-pay12 {
			padding-top:1em;padding-bottom:1em; padding-right:0em;
		}		
		.PayMethod12 ul li		{			margin-right:60px;		}
        .immediate_pay{
            border:none;
        }
        .PayMethod12
        {
           padding-bottom:15px;
        }
        @media screen and (max-width: 700px) {						.pc_dis{		display:inline-block;	}						.mobile_dis{			display:none;			}
			.immediate-pay12 {
			padding-top:0em;padding-bottom:0em; padding-right:0em;
		}
		.immediate-pay12-right
		{
			margin-left:0;
			margin-right:0;
			width:100%;
		}
			.immediate-pay12-right .immediate_pay
			{
			float:none;
 margin:0 auto;			
			}
			.border_radis{
			border-radius:0em
		}
		.immediate_pay
		{
			width:50%;
		}
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
               // $('.PayMethod12 ul li').eq(4 * index + 4).css('margin-right', '0')
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
</script><script>if(/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {    } else {     window.location.href = "index.php";}  </script>
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
        <p>支付遇到问题？请联系客服获得帮助。</p>
    </div>
</div>
<!--弹窗结束-->
<!--导航-->
<div class="w100 navBD12">
    <div class="w1080 nav12">
        <div class="nav12-left">
            <a href="/"><img src="/Uploads/logo/5bcb83745a191.png" alt=""title=" " style="max-height: 45px;"></a>
        </div>
       <div class="nav12-right">
                <span class="contact">支付体验收银台</span>
            </div>
    </div>
</div>
<!--订单金额-->
<div class="w1080 order-amount12 border_radis">
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

<div class="w1080 PayMethod12 border_radis">
    <div class="row">
        <h2>支付方式：</h2>
      <ul>
        <label for="Mowool1">
          <li class="pay_li pc_dis active " data_power_id="3000000011" data_product_id="3000000001">
            <input value="927" checked="checked" name="channel" id="Mowool1" type="radio">
            <i class="i1"></i>
            <span>支付宝固码(Mowool)</span>
          </li></label>
        <label for="Bankpay">
          <li class="pay_li pc_dis  " data_power_id="3000000011" data_product_id="3000000001">
            <input value="922" name="channel" id="Bankpay" type="radio">
            <i class="i1"></i>
            <span>支付宝转卡</span>
          </li></label>
          
          <label for="pddwx">
          <li class="pay_li pc_dis  " data_power_id="3000000011" data_product_id="3000000001">
            <input value="926"  name="channel" id="pddwx" type="radio">
            <i class="i1"></i>
            <span>pdd微信</span>
          </li></label>
        
        <label for="pddzfb">
          <li class="pay_li pc_dis  " data_power_id="3000000011" data_product_id="3000000001">
            <input value="925"  name="channel" id="pddzfb" type="radio">
            <i class="i1"></i>
            <span>pdd支付宝</span>
          </li></label>
        
        
        <label for="Aliwap">
          <li class="pay_li pc_dis  " data_power_id="3000000011" data_product_id="3000000001">
            <input value="904"  name="channel" id="Aliwap" type="radio">
            <i class="i1"></i>
            <span>支付宝H5</span>
          </li></label>
        
        
            <label for="wxh5">
            <li class="pay_li pc_dis" data_power_id="3000000021" data_product_id="3000000001">
                <input value="901" name="channel" id="wxh5" type="radio">
                <i class="i2"></i>
                <span>微信支付H5</span>
            </li>  </label>
			
			<label for="qqwap">
            <li class="pay_li pc_dis" data_power_id="3000000021" data_product_id="3000000001">
                <input value="905" name="channel" id="qqwap" type="radio">
                <i class="i5"></i>
                <span>QQ钱包H5</span>
            </li>  </label>
	
        <label for="Kjwap">
            <li class="pay_li pc_dis" data_power_id="3000000021" data_product_id="3000000016">
                <input value="916" name="channel" id="baidu" type="radio">
                <i class="i3"></i>
                <span>快捷支付H5</span>
            </li>  </label>
        
        <label for="Ylh5">
            <li class="pay_li pc_dis" data_power_id="3000000021" data_product_id="3000000015">
                <input value="915" name="channel" id="baidu" type="radio">
                <i class="i3"></i>
                <span>银联H5</span>
            </li>  </label>
        
          <label for="Ylh5">
            <li class="pay_li pc_dis" data_power_id="3000000021" data_product_id="3000000018">
                <input value="918" name="channel" id="baidu" type="radio">
                <i class="i3"></i>
                <span>在线网银</span>
            </li>  </label>
        
			<label for="baidu">
			
			   <li class="pay_li pc_dis" data_power_id="3000000021" data_product_id="3000000001">
                <input value="909" name="channel" id="baidu" type="radio">
                <i class="i6"></i>
                <span>百度钱包H5</span>
            </li>  </label>
			
			
        </ul>
    </div>
</div>
<!--立即支付-->
  <BR>
<div class="w1080">
    <div>
        <center><button type="submit" class="immediate_pay" >立即支付</button></center>
    </div>
</div>
<div class="mt_agree">
    <div class="mt_agree_main">
        <h2>提示信息</h2>
        <p id="errorContent" style="text-align:center;line-height:36px;"></p>
        <a class="close_btn" onclick="message_hide()">确定</a>
    </div>
</div>
<!--底部
<div class="w1080 footer12">
    <p>Copyright © 2018 聚合支付 版权所有</p>
	   
</div>-->
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