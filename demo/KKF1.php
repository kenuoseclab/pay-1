<?php
$pay_orderid = 'E'.date("YmdHis").rand(100000,999999);    //订单号
?>
<!DOCTYPE html>
<html lang=zh>
<head>
    <meta charset=UTF-8>
    <title>聚合收银台</title>
    <link href="cashier.css" rel="stylesheet">
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
                    <form action="KKF2.php" method="post" autocomplete="off">
                        <input type="hidden" name="orderid" value="<?php echo $pay_orderid;?>">
                   
                        <div class="two-step">
                            <p><strong>请您及时付款，以便订单尽快处理！</strong>请您在提交订单后<span>24小时</span>内完成支付，否则订单会自动取消。</p>
                            <ul class="pay-infor">
                                <li>商品名称：测试应用-支付功能体验(非商品消费)</li>
                                <li>支付金额：<strong><input type="input" name="amount" value="1"> <span>元</span></strong></li>
                                <li>订单编号：<span><?php echo $pay_orderid;?></span></li>
                            </ul>
                            <h5>选择支付方式：</h5>
                            <ul class="pay-label">
                                <li>
                                    <input value="903" checked="checked" name="channel" id="zfb" type="radio">
                                    <label for="zfb"><img src="zhifubao.png" alt="支付宝"><span>支付宝扫码</span></label>
                                </li>
                                <li>
                                    <input value="904"  name="channel" id="zfb1" type="radio">
                                    <label for="zfb1"><img src="zhifubao.png" alt="支付宝"><span>支付宝WAP</span></label>
                                </li>
<!--                                <li>-->
<!--                                    <input value="901" name="channel" id="wx" type="radio">-->
<!--                                    <label for="wx"><img src="weixin.png" alt=""><span>微信WAP</span></label>-->
<!--                                </li>-->
                                <!--<li>-->
                                <!--    <input value="902" name="channel" id="wx1" type="radio">-->
                                <!--    <label for="wx1"><img src="weixin.png" alt=""><span>微信扫码</span></label>-->
                                <!--</li>-->
                                <!--<li>-->
                                <!--    <input value="908" name="channel" id="qq" type="radio">-->
                                <!--    <label for="qq"><img src="qq.jpeg" alt="QQ扫码支付"><span>QQ扫码</span></label>-->
                                <!--</li>-->
                                <!--<li>-->
                                <!--    <input value="905" name="channel" id="qq1" type="radio">-->
                                <!--    <label for="qq1"><img src="qq.jpeg" alt="QQ扫码支付"><span>QQWAP</span></label>-->
                                <!--</li>-->
                                <!--<li>-->
                                <!--    <input value="910" name="channel" id="jd" type="radio">-->
                                <!--    <label for="jd"><img src="jd.jpeg" alt="京东钱包"><span>京东钱包</span></label>-->
                                <!--</li>-->
<!--                               <li>-->
<!--                                    <input value="907" name="channel" id="bd" type="radio">-->
<!--                                    <label for="bd"><img src="yinlian.png" alt="银联支付"><span>银联支付</span></label>-->
<!--                                </li>-->

                            </ul>
                            <style>
                                .plist p{float:left;margin-left:25px;margin-top:10px;border:1px solid #eee; width:154px; height:33px;}
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
                                    $(".plist").hide();
                                })
                            </script>
                            <div>
                                <input type="hidden" name="bankCode" value="ICBC" />
                                <div class="plist banklist" style="display: ;">
                                    <p class="current">
                                        <img src="/Public/images/bank/ICBC.gif" data-pid="ICBC">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/ABC.gif" data-pid="ABC">
                                    </p>
                                    <!--<p>-->
                                    <!--    <img src="/Public/images/bank/BOCSH.gif" data-pid="BOCSH">-->
                                    <!--</p>-->
                                    <p>
                                        <img src="/Public/images/bank/CCB.gif" data-pid="CCB">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/CMB.gif" data-pid="CMB">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/SPDB.gif" data-pid="SPDB">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/GDB.gif" data-pid="GDB">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/BCM.gif" alt="交通银行" data-pid="BOCOM">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/PSBC.gif" data-pid="PSBC">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/CNCB.gif" data-pid="CNCB">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/CMBC.gif" alt="民生银行" data-pid="CMBC">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/CEB.gif" data-pid="CEB">
                                    </p>

                                    <p>
                                        <img src="/Public/images/bank/CIB.gif" data-pid="CIB">
                                    </p>
                                    <p>
                                        <img src="/Public/images/bank/BOS.gif" data-pid="BOS">
                                    </p>

                                    <!--<p>-->
                                    <!--    <img src="/Public/images/bank/PAB.gif" data-pid="PAB">-->
                                    <!--</p>-->
                                    <!--<p>-->
                                    <!--    <img src="/Public/images/bank/BCCB.gif" alt="北京银行" data-pid="BCCB">-->
                                    <!--</p>-->
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
