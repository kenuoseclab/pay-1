<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/> 
    <title>微信支付</title>
    <js href="/Public/js/jquery.js" />
<script>
$(document).ready(function(e) {
    r = window.setInterval(function(){
		////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////
				$.ajax({
				type:'POST',
				url:'<{:U("WeiXin/checkstatus")}>',
				data:"orderid=<{$orderid}>",
				dataType:'text',
				success:function(str){
					///////////////////////////////////
					if(str == "ok"){
						//$("#ewm").attr("src","Uploads/successpay.png");
						$("#zf").hide();
						$("#cg").show();
						window.clearInterval(r);
					}
					///////////////////////////////////
					},
				error:function(str){
					//////////////////////////
					
					/////////////////////////
					}	
				});
				///////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////
		},2000);
});
</script>
    <script type="text/javascript">
    //调用微信JS api 支付
    function jsApiCall()
    {
        WeixinJSBridge.invoke(
            'getBrandWCPayRequest',
            <{$jsApiParameters}>,
            function(res){
                WeixinJSBridge.log(res.err_msg);
               // alert(res.err_code+res.err_desc+res.err_msg);
            }
        );
    }

    function callpay()
    {
        if (typeof WeixinJSBridge == "undefined"){
            if( document.addEventListener ){
                document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
            }else if (document.attachEvent){
                document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
                document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
            }
        }else{
            jsApiCall();
        }
    }
    </script>
    <script type="text/javascript">
    //获取共享地址
    function editAddress()
    {
        WeixinJSBridge.invoke(
            'editAddress',
            <{$editAddress}>,
            function(res){
                var value1 = res.proviceFirstStageName;
                var value2 = res.addressCitySecondStageName;
                var value3 = res.addressCountiesThirdStageName;
                var value4 = res.addressDetailInfo;
                var tel = res.telNumber;
                
               // alert(value1 + value2 + value3 + value4 + ":" + tel);
            }
        );
    }
    
    window.onload = function(){
        if (typeof WeixinJSBridge == "undefined"){
            if( document.addEventListener ){
                document.addEventListener('WeixinJSBridgeReady', editAddress, false);
            }else if (document.attachEvent){
                document.attachEvent('WeixinJSBridgeReady', editAddress); 
                document.attachEvent('onWeixinJSBridgeReady', editAddress);
            }
        }else{
            editAddress();
        }
    };
    callpay();
    </script>
</head>
<body>
    <br/>
    <font color="#9ACD32"><br/><br/><br/><br/>
    <div align="center" id="zf">
        <button style="width:210px; height:50px; border-radius: 15px;background-color:#FE6714; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onclick="callpay();" >立即支付</button>
    </div>
     <div align="center" id="cg" style="display:none;">
     <span>支付成功！</span><br><br>
        <button style="width:210px; height:50px; border-radius: 15px; background-color:#F00; border:0px #FE6714 solid; cursor: pointer;  color:white;  font-size:16px;" type="button" onClick="javascript:WeixinJSBridge.call('closeWindow');">关 闭</button>
    </div>
</body>
</html>