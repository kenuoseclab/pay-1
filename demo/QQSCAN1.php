<?php
$pay_orderid = 'E'.date("YmdHis").rand(100000,999999);    //订单号
?>
<!DOCTYPE html>
<html lang=zh>
<head>
    <meta charset=UTF-8>
    <title>聚合收银台</title>
    <link href="cashier.css?<?php echo time();?>" rel="stylesheet">
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
                    <form action="QQSCAN2.php" method="post" autocomplete="off">
                        <input type="hidden" name="orderid" value="<?php echo $pay_orderid;?>">
                   
                        <div class="two-step">
                            <p><strong>请您及时付款，以便订单尽快处理！</strong>请您在提交订单后<span>24小时</span>内完成支付，否则订单会自动取消。</p>
                            <ul class="pay-infor">
                                <li>商品名称：测试应用-支付功能体验(非商品消费)</li>
                                <li>支付金额：<strong><input type="input" name="amount" value="0.01"> <span>元</span></strong></li>
                                <li>订单编号：<span><?php echo $pay_orderid;?></span></li>
                            </ul>
                            <h5>选择支付方式：</h5>
                            <ul class="pay-label">
								<li>
								    <input value="908" name="channel" id="qq" type="radio">
								    <label for="qq"><img src="qq.jpeg" alt="QQ扫码支付"><span>QQ扫码</span></label>
								</li>
                                <li>
                                    <input value="905" name="channel" id="qqwap" type="radio">
                                    <label for="qqwap"><img src="qq.jpeg" alt="QQ扫码支付"><span>QQWap</span></label>
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
                                                     <img src="static/payimg/ICBC.gif" data-pid="ICBC">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/ABC.gif" data-pid="ABC">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/BOCSH.gif" data-pid="BOCSH">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/CCB.gif" data-pid="CCB">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/CMB.gif" data-pid="CMB">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/SPDB.gif" data-pid="SPDB">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/GDB.gif" data-pid="GDB">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/BOCOM.gif" data-pid="BOCOM">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/PSBC.gif" data-pid="PSBC">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/CNCB.gif" data-pid="CNCB">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/CMBC.gif" data-pid="CMBC">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/CEB.gif" data-pid="CEB">
                                                 </p>

                                                                                                                         <p>
                                                     <img src="static/payimg/CIB.gif" data-pid="CIB">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/BOS.gif" data-pid="BOS">
                                                 </p>

                                                                                                                         <p>
                                                     <img src="static/payimg/PAB.gif" data-pid="PAB">
                                                 </p>
                                                                                                                         <p>
                                                     <img src="static/payimg/BCCB.gif" data-pid="BCCB">
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
