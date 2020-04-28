// JavaScript Document
$(document).ready(function(e) {
    
	autoresize();
	
	window.onresize = function(){
		autoresize();
	};
	
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
	
	
    $(".nav_div:eq(0)").click();
	
	
});

function autoresize(){   //自己获取中间的高度
    ManagesContentHeight = document.documentElement.clientHeight - 135;
	$("#ManagesContent").css("height", ManagesContentHeight+"px");
	$("#ManagesContentLeft").css("height", (ManagesContentHeight-2)+"px");
	$("#ManagesContentRight").css({"height":ManagesContentHeight+"px","width":(document.documentElement.clientWidth-250)+"px"});
	$("#ManagesTopContent").css("width",(document.documentElement.clientWidth-530)+"px");
}

function basicinfosubmit(mythis){
    
	var datastr = "";
	var btn = $(mythis);
	btn.button('loading');
	$("#basicinfoform input").each(function(index, element) {
		$(this).attr("disabled","disabled");
		datastr = datastr+ $(this).attr("name") + "="+ $(this).val()+"&";    
	});
	
	datastr = datastr+$("#sex").attr("name")+"="+$("#sex").val();
	ajaxurl = $("#basicinfoform").attr("action");
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:datastr,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				$("#basicinfoform input").each(function(index, element) {
					$(this).removeAttr("disabled");
                });
				btn.button('reset');
				$("#tscontent").text(str);
				$('#myModal').modal('show');
				///////////////////////////////////
				}
			});
}

function bankcardsubmit(mythis){
    
	var datastr = "";
	var btn = $(mythis);
	btn.button('loading');
	//alert("ddddddddddddd");
	$("#bankcardform input,#bankcardform select").each(function(index, element) {
		$(this).attr("disabled","disabled");
		datastr = datastr+ $(this).attr("name") + "="+ $(this).val()+"&";    
	});
	
	datastr = datastr+$("#bankname").attr("name")+"="+$("#bankname").val();
	
	ajaxurl = $("#bankcardform").attr("action");
	
	//alert(datastr);
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:datastr,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				/*$("#bankcardform input").each(function(index, element) {
					$(this).removeAttr("disabled");
                });*/
				
				btn.button('reset');
			    btn.hide();
				$("#reloadbutton").show();
				$("#tscontent").text(str);
				$('#myModal').modal('show');
				///////////////////////////////////
				},
			error:function(str){
				//////////////////////////
				}	
			});


}


function disabledsubmit(){
	//$("#tscontent").text("你确认要禁止修改银行卡信息吗？禁止修改后，如再需修改银行卡信息请联系管理员！");
	//$('#myModal').modal('show');
	$("#disabledok").show();
	disableddisabledok();
}

function disableddisabledok(){

	var btn = $("#disabledbutton");
	btn.button('loading');
	ajaxurl = btn.attr("ajaxurl");
	
	datastr = "id="+$("#id").val();
	
	//alert(ajaxurl);
	//alert(datastr);
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:datastr,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				
				btn.button('reset');
			    btn.hide();
				$("#reloadbutton").show();
				$("#tscontent").text(str);
				$("#disabledok").hide();
				$('#myModal').modal('show');
				///////////////////////////////////
				},
			error:function(str){
				//////////////////////////
				}	
			});
}

function loginpasswordsubmit(mythis){
	if($("#yloginpassword").val() == ""){
		$("#tscontent").text("原密码不能为空！");
		$('#myModal').modal('show');
	}else{
		$.ajax({
			type:'POST',
			url:$(mythis).attr("ajaxurl"),
			data:"loginpassword="+$("#yloginpassword").val(),
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				if(str == "no"){
					$("#tscontent").text("原密码错误");
				    $('#myModal').modal('show');
				}else{
					if($("#loginpassword").val() == ""){
						$("#tscontent").text("新密码不能为空");
				        $('#myModal').modal('show');
					}else{
						if($("#loginpassword").val() != $("#okloginpassword").val()){
							$("#tscontent").text("新密码两次输入不一致！");
				            $('#myModal').modal('show');
						}else{
							$("#loginpasswordform").submit();
						}
					}
				}
				
				///////////////////////////////////
				},
			error:function(str){
				//////////////////////////
				}	
			});

	}
}

function paypasswordsubmit(mythis){
	if($("#yloginpassword").val() == ""){
		$("#tscontent").text("原密码不能为空！");
		$('#myModal').modal('show');
	}else{
		$.ajax({
			type:'POST',
			url:$(mythis).attr("ajaxurl"),
			data:"loginpassword="+$("#yloginpassword").val(),
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				if(str == "no"){
					$("#tscontent").text("原密码错误");
				    $('#myModal').modal('show');
				}else{
					if($("#loginpassword").val() == ""){
						$("#tscontent").text("新密码不能为空");
				        $('#myModal').modal('show');
					}else{
						if($("#loginpassword").val() != $("#okloginpassword").val()){
							$("#tscontent").text("新密码两次输入不一致！");
				            $('#myModal').modal('show');
						}else{
							$("#loginpasswordform").submit();
						}
					}
				}
				
				///////////////////////////////////
				},
			error:function(str){
				//////////////////////////
				}	
			});

	}
}


function edit(id){
	$('#myModal').modal('show');
	ajaxurl = $("#myModal").attr("ajaxurl")+"?a="+Math.random();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"id="+id,
			dataType:'json',
			success:function(obj){
					$("#dealcontent table tr td span").each(function(index, element) {
						$(this).html(obj[index]);
					});
					$("#orderidModal").html(obj[0]);
	            
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
}

function deldel(id,ajaxurl){
	if(confirm("您确认要删除这条交易信息吗？")){
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"id="+id,
			dataType:'text',
			success:function(str){
					if(str == "ok"){
						jAlert("删除成功！","提示信息");
						window.location.reload();
					}else{
						jAlert("删除失败，请稍后重试！","提示信息");
					}
	            
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
	}
}
