// JavaScript Document
$(document).ready(function(e) {
    
	autoresize();
	
	window.onresize = function(){
		autoresize();
	};
	
	$("#xgpasswordbutton").click(function(e) {
        ypassword = $.trim($("#ypassword").val());
		if(ypassword == ""){
			jAlert("原密码不能为空","提示信息",function(){
				$("#ypassword").focus();
				});
			return;	
		}
		
		newpassword = $.trim($("#newpassword").val());
		if(newpassword == ""){
			jAlert("新密码不能为空","提示信息",function(){
				$("#newpassword").focus();
				});
			return;	
		}
		
		newpasswordok = $.trim($("#newpasswordok").val());
		if(newpassword == ""){
			jAlert("新密码不能为空","提示信息",function(){
				$("#newpasswordok").focus();
				});
			return;	
		}
		
	
		if(newpassword != newpasswordok){
			jAlert("两次新密码不一致","提示信息");
			return;	
		}
		
		if(ypassword == newpasswordok){
			jAlert("新密码不能与原密码相同","提示信息");
			return;	
		}
		
		urlstr = $(this).attr("ajaxurl");
		datastr = "ypassword="+ypassword+"&newpassword="+newpassword+"&newpasswordok="+newpasswordok;
		
		//alert(urlstr+"----"+datastr);
		$.ajax({
			type:'POST',
			url:urlstr,
			data:datastr,
			dataType:'text',
			success:function(str){
				//alert(str);
				///////////////////////////////////
				switch(str){
					case "a":
						jAlert("原密码错误！","提示信息");
					break;
					case "b":
					    jAlert("新密码不能为空！","提示信息");
					break;
					case "c":
					    jAlert("两次新密码不一致！","提示信息");
					break;
					case "d":
					    jAlert("新密码不能与原密码相同！","提示信息");
					break;
					case "ok":
					    jAlert("密码修改成功！","提示信息",function(){
							$("#ypassword").val("");
							$("#newpassword").val("");
							$("#newpasswordok").val("");
							});
					break;
				}
				///////////////////////////////////
				},
			error:function(str){
				//////////////////////////
				}	
			});
		
		
    });
	
	$(".nav_div:eq(0)").addClass("nav_div_x").attr("select","ok");
	$(".nav_div").mouseover(function(e) {
        if($(this).attr("select") != "ok"){
			$(this).addClass("nav_div_x");
		}
    });
	
	$(".nav_div").mouseout(function(e) {
        if($(this).attr("select") != "ok"){
			$(this).removeClass("nav_div_x");
		}
    });
	
	$(".nav_div").click(function(e) {
		$("#ManagesContentLeft").show();
        $(".nav_div").removeClass("nav_div_x").attr("select","");
		$(this).addClass("nav_div_x").attr("select","ok");
		$("#ManagesContentLeft").html("");
		$("#ManagesContentLeft").load($(this).attr("url"));
		$("#ManagesContentIfram").attr("src",$(this).attr("defaulturl"));
    });
	
	$(".nav_div:eq(0)").unbind("click").click(function(e) {
		$(".nav_div").removeClass("nav_div_x").attr("select","");
		$(this).addClass("nav_div_x").attr("select","ok");
        $("#ManagesContentLeft").show();
		$("#ManagesContentLeft").load($(this).attr("url"));
		$("#ManagesContentIfram").attr("src",$(this).attr("defaulturl"));
		
    });
	
    $(".nav_div:eq(0)").click();
	
	//###########################################################################################
	 $('#loading-example-btn').click(function () {
		    
			var datastr = "";
			var btn = $(this);
			btn.button('loading');
			$("#reset-btn").attr("disabled","disabled");
		
			$("#form1 input,#form1 textarea").each(function(index, element) {
				$(this).attr("disabled","disabled");
            	datastr = datastr+ $(this).attr("name") + "="+ $(this).val()+"&";    
            });
			
			$("#tscontent").text("");
			urlstr = $("#form1").attr("action");
			
			
	$.ajax({
			type:'POST',
			url:urlstr,
			data:datastr,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				$("#form1 input,#form1 textarea").each(function(index, element) {
					$(this).removeAttr("disabled");
                });
				$("#reset-btn").removeAttr("disabled");
				btn.button('reset');
			
				$("#tscontent").text(str);
				$('#myModal').modal('show');
				///////////////////////////////////
				},
			error:function(str){
				//////////////////////////
				}	
			});

     });
	 
	 
	//###########################################################################################
	
	
});

function autoresize(){   //自己获取中间的高度
    ManagesContentHeight = document.documentElement.clientHeight - 135;
	$("#ManagesContent").css("height", ManagesContentHeight+"px");
	$("#ManagesContentLeft").css("height", (ManagesContentHeight-2)+"px");
	$("#ManagesContentRight").css({"height":ManagesContentHeight+"px","width":(document.documentElement.clientWidth-250)+"px"});
	$("#ManagesTopContent").css("width",(document.documentElement.clientWidth-530)+"px");
}

function clearNoNum(obj)
	{
		//先把非数字的都替换掉，除了数字和.
		obj.value = obj.value.replace(/[^\d.]/g,"");
		//必须保证第一个为数字而不是.
		obj.value = obj.value.replace(/^\./g,"");
		//保证只有出现一个.而没有多个.
		obj.value = obj.value.replace(/\.{2,}/g,".");
		//保证.只出现一次，而不能出现两次以上
		obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
	}