// JavaScript Document
$(document).ready(function(e) {
    $('*[data-toggle=tooltip]').css("cursor","pointer").attr("data-container","body").mouseover(function() {
 $(this).tooltip('show');
  })
});

function cookieselect(value,titlename){
	
	$("#cookiename").val(value);
	
	$("#selectcookiebutton").text(titlename);
	
}

function logincheck(){
	if($("#username").val() == ""){
		 $("#tscontent").text("用户名不能为空！");
		 $('#myModal').modal('show');
		 $("#username").focus();
		 return false;
	}
	if($("#password").val() == ""){
		 $("#tscontent").text("密码不能为空！");
		 $('#myModal').modal('show');
		 $("#password").focus();
		 return false;
	}
	if($("#verification").val() == ""){
		 $("#tscontent").text("验证码不能为空！");
		 $('#myModal').modal('show');
		 $("#verification").focus();
		 return false;
	}
	ajaxurl = $("#verification").attr("ajaxurl");
	datastr = "code="+$("#verification").val();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:datastr,
			dataType:'text',
			success:function(str){
				//alert(str);
				///////////////////////////////////
				if(str == "no"){
					$("#tscontent").text("验证码输入错误 ！");
				    $('#myModal').modal('show');
					return false;
				}else{
					$("#formlogin").submit();
				}
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				//alert("aaaaaaaa");	  
				//////////////////////////
				}	
			});
}