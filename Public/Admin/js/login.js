$(document).ready(function() {
	$("#loginbutton").click(function(){
		
		logincheck();
		
	});
	
});

function logincheck(){

	username = $("#username").val();
		if(username == ""){
			alert("用户名不能为空");
			jAlert('用户名不能为空！','提示信息',function(){
				$("#username").focus();
			});
			return;
		}
		
		loginpassword = $("#loginpassword").val();
		if(loginpassword == ""){
			jAlert('登录密码不能为空！','提示信息',function(){
				$("#loginpassword").focus();
			});
			return;
		}
		
		verification = $("#verification").val();
		if(verification == ""){
			jAlert('验证码不能为空！','提示信息',function(){
				$("#verification").focus();
			});
			return;
		}
		
		ajaxurl = $("#loginbutton").attr("ajaxurl");
		dlurl = $("#loginbutton").attr("dlurl");
		datastr = "username="+username+"&loginpassword="+loginpassword+"&verification="+verification;
		
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:datastr,
			dataType:'text',
			success:function(str){
				
				switch(str){
					case "ok":
					window.location.href = "/"+dlurl;
					break;
					case "userpasserror":
					jAlert('账号或密码输入错误！','提示信息');
					break;
					case "statuserror":
					jAlert('您的账号已被禁用或激活！','提示信息');
					break;
					case "usertypeerror":
					jAlert('你的账号类型不能在此登录！','提示信息');
					break;
					case "verificationerror":
					jAlert('验证码输入错误 !','提示信息');
					break;
				}
				
				$(".verifyimg").click();
			
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
}


var SubmitOrHidden = function(evt){  
     evt = window.event || evt;  
     if(evt.keyCode==13){//如果取到的键值是回车  
	 	logincheck(); 
      }
 }  
 window.document.onkeydown=SubmitOrHidden;//当有键按下时执行函数  