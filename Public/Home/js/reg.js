// JavaScript Document
$(document).ready(function(e) {
    $(".form-control").attr("data-placement","right").attr("data-toggle","popover").attr("data-html","true").attr("data-container","body").attr("data-trigger","manual").focus(function(e) {
        $(this).popover("hide");
    });;
	
	
	$("#username").blur(function(e) {
	warning_a = "<span style='color:#F60;'><span class='glyphicon glyphicon-remove-circle' style='color:#f00;'></span> <strong>";
	warning_b = "</strong></span>";
	username = $(this).val();
	username = username.replace(/(^\s*)|(\s*$)/g, "");
       if(username == ""){
		   content = "用户名不能为空!";
		   $(this).attr("data-content",warning_a+content+warning_b).popover("show");
		    $("#tj").val(1);
		   return false;
	   }else{
		   if(username.length < 4 || username.length >15){
		   		content = "用户名长度不能小于4个字符或大于15个字符！";
				$(this).attr("data-content",warning_a+content+warning_b).popover("show");
				$("#tj").val(1);
		        return false;
		   }else{
			   var reg = /^(\w|[\u4E00-\u9FA5])*$/;
			   arr=username.match(reg);
			   if(!arr){
				   content = "用户名只能是中文/英文/数字/下划线的组合";
				   $(this).attr("data-content",warning_a+content+warning_b).popover("show");
				   $("#tj").val(1);
		           return false;
			   }else{
				   urlstr = $(this).attr("checkurl");
				   datastr = "username="+username;
				   ////////////////////////////////////////////////////////////////////////////////////
				   $.ajax({
					type:'POST',
					url:urlstr,
					data:datastr,
					dataType:'text',
					success:function(str){
						///////////////////////////////////
						if(str == "no"){
							content = "用户名已存在！";
						    $("#username").attr("data-content",warning_a+content+warning_b).popover("show");
						    $("#tj").val(1);
						    return false;
						}else{
							success = "<span class='glyphicon glyphicon-ok-circle' style='color:#0c0;'></span>";
				            $("#username").attr("data-content",success).popover("show");
					        return false;
						}
						///////////////////////////////////
						},
					error:function(XMLHttpRequest, textStatus, errorThrown) {
						//content = "判断用户名重复失败，请稍后再试！";
						  //  $("#username").attr("data-content",warning_a+content+warning_b).popover("show");
						    $("#tj").val(1);
						    return false;
						//////////////////////////
						}	
					});
				   ///////////////////////////////////////////////////////////////////////////////////
				   
			   }
		   }
	   }
    });
	
	
	$("#password").blur(function(e) {
	warning_a = "<span style='color:#F60;'><span class='glyphicon glyphicon-remove-circle' style='color:#f00;'></span> <strong>";
		  warning_b = "</strong></span>";
		  password = $(this).val();
		  password = password.replace(/(^\s*)|(\s*$)/g, "");
		  if(password == ""){
			   content = "密码不能为空!";
			   $(this).attr("data-content",warning_a+content+warning_b).popover("show");
			  $("#tj").val(1);
			   return false;
		  }else{
			  if(password.length < 5){
				  content = "密码不能小于5个字符！";
			      $(this).attr("data-content",warning_a+content+warning_b).popover("show");
				  $("#tj").val(1);
			      return false;
			  }else{
				   success = "<span class='glyphicon glyphicon-ok-circle' style='color:#0c0;'></span>";
				   $(this).attr("data-content",success).popover("show");
				     return false;
			  }
		  }
    });
	
	
	$("#confirmpassword").blur(function(e) {
	warning_a = "<span style='color:#F60;'><span class='glyphicon glyphicon-remove-circle' style='color:#f00;'></span> <strong>";
		  warning_b = "</strong></span>";
		  confirmpassword = $(this).val();
		  confirmpassword = confirmpassword.replace(/(^\s*)|(\s*$)/g, "");
		  if(confirmpassword == ""){
			   content = "密码不能为空!";
			   $(this).attr("data-content",warning_a+content+warning_b).popover("show");
			   $("#tj").val(1);
			   return false;
		  }else{
			  if(confirmpassword.length < 5){
				  content = "密码不能小于5个字符！";
			      $(this).attr("data-content",warning_a+content+warning_b).popover("show");
				  $("#tj").val(1);
			      return false;
			  }else{
				  password = $("#password").val();
		          password = password.replace(/(^\s*)|(\s*$)/g, "");
				  if(confirmpassword != password){
					   content = "两次密码输入不一致！";
			           $(this).attr("data-content",warning_a+content+warning_b).popover("show");
					   $("#tj").val(1);
			           return false;
				  }else{
					  success = "<span class='glyphicon glyphicon-ok-circle' style='color:#0c0;'></span>";
				      $(this).attr("data-content",success).popover("show");
					   return false;
				  }
			  }
		  }
    });
	
	
	$("#email").blur(function(e) {
      warning_a = "<span style='color:#F60;'><span class='glyphicon glyphicon-remove-circle' style='color:#f00;'></span> <strong>";
		  warning_b = "</strong></span>";
		  email = $(this).val();
		  email = email.replace(/(^\s*)|(\s*$)/g, "");  
		  if(email == ""){
			   content = "邮箱不能为空!";
			   $(this).attr("data-content",warning_a+content+warning_b).popover("show");
			   $("#tj").val(1);
			   return false;
		  }else{
			   if(!/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(email)){
				   content = "邮箱格式错误 !";
				   $(this).attr("data-content",warning_a+content+warning_b).popover("show");
				  $("#tj").val(1);
				   return false;
               }else{
				    urlstr = $(this).attr("checkurl");
					//alert(urlstr);
				   datastr = "email="+email;
				 //  alert(datastr);
				   ////////////////////////////////////////////////////////////////////////////////////
				   $.ajax({
					type:'POST',
					url:urlstr,
					data:datastr,
					dataType:'text',
					success:function(str){
						///////////////////////////////////
						if(str == "no"){
							content = "邮箱已存在！";
						    $("#email").attr("data-content",warning_a+content+warning_b).popover("show");
						    $("#tj").val(1);
						    return false;
						}else{
							 success = "<span class='glyphicon glyphicon-ok-circle' style='color:#0c0;'></span>";
						     $("#email").attr("data-content",success).popover("show");
							 return false;
						}
						///////////////////////////////////
						},
					error:function(XMLHttpRequest, textStatus, errorThrown) {
						   // alert(XMLHttpRequest+"---"+textStatus+"---"+errorThrown);
							//content = "判断邮箱重复失败，请稍后再试！";
						  //  $("#email").attr("data-content",warning_a+content+warning_b).popover("show");
						    $("#tj").val(1);
						    return false;
						//////////////////////////
						}	
					});
				   ///////////////////////////////////////////////////////////////////////////////////
				  
			   }
		  }
    });
	
	$("#invitecode").blur(function(e) {
         warning_a = "<span style='color:#F60;'><span class='glyphicon glyphicon-remove-circle' style='color:#f00;'></span> <strong>";
		  warning_b = "</strong></span>";
		  invitecode = $(this).val();
		  invitecode = invitecode.replace(/(^\s*)|(\s*$)/g, ""); 
		  if(invitecode == ""){
			   content = "邀请码不能为空!";
			   $(this).attr("data-content",warning_a+content+warning_b).popover("show");
			   $("#tj").val(1);
			   return false;
		  }else{
			       urlstr = $(this).attr("checkurl");
				   datastr = "invitecode="+invitecode;
				   ////////////////////////////////////////////////////////////////////////////////////
				   $.ajax({
					type:'POST',
					url:urlstr,
					data:datastr,
					dataType:'text',
					success:function(str){
						///////////////////////////////////
						if(str == "no"){
							content = "邀请码不存在或不可用！";
						    $("#invitecode").attr("data-content",warning_a+content+warning_b).popover("show");
						    $("#tj").val(1);
						    return false;
						}else{
							success = "<span class='glyphicon glyphicon-ok-circle' style='color:#0c0;'></span>";
				            $("#invitecode").attr("data-content",success).popover("show");
					        return false;
						}
						///////////////////////////////////
						},
					error:function(XMLHttpRequest, textStatus, errorThrown) {
							//content = "系统加载失败，请稍后再试！";
						   // $("#invitecode").attr("data-content",warning_a+content+warning_b).popover("show");
						   $("#tj").val(1);
						    return false;
						//////////////////////////
						}	
					});
				   ///////////////////////////////////////////////////////////////////////////////////
			  success = "<span class='glyphicon glyphicon-ok-circle' style='color:#0c0;'></span>";
				   $(this).attr("data-content",success).popover("show");
				    return false;
		  }
    });
	
	
});

function check(mythis){
	 $(mythis).button("loading");
	 $("#username").blur();
	 $("#password").blur();
	 $("#confirmpassword").blur();
	 $("#email").blur();
	 $("#invitecode").blur();
	 
	 if($("#tj").val() == 1){
		 $("#tj").val(0);
		// return false;
	 }else{
		 $("#Formreg").submit();
	 }
	 $(mythis).button("reset");
}