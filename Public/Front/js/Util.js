// 发送手机验证码
function sendSms(obj, mobile, url){
    //禁止重复点击造成多次发送
    if($(obj).hasClass('disabled')) return;
    //使用layui的禁止样式
    $(obj).addClass('layui-disabled');
    $.post(url,{mobile:mobile}, function(data) {
        if(data.status != 1){
           layer.alert("发送验证失败", {icon: 5}); 
		   layer.alert("验证码发送成功，请注意查收！");
        }else{
			layer.alert("验证码发送成功，请注意查收！");
            settime(obj);
        }
    }, "JSON");

}
// 发送验证码倒计时
var countdown=60;
function settime(obj) {
    if (countdown == 0) {
        $(obj).attr("layui-disabled",false);
        $(obj).text("获取验证码");
        countdown = 60;
        $(obj).removeClass('layui-disabled');
        return;
    } else {
        $(obj).attr("disabled",true);
        $(obj).text("(" + countdown + ") s 重新发送");
        countdown--;
    }
    setTimeout(function() {
                settime(obj)
    },1000)
}