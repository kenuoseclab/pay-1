// JavaScript Document
$(document).ready(function(e) {
    $(".tongdaolist > input").click(function(e) {
		$(".tongdaolist span").removeClass("tongdaolistspan");
        $(this).next("span").addClass("tongdaolistspan");
		loadbank($(this).val());
    });
	
	$(".tongdaolist span").click(function(e) {
        $(this).prev().click();
		$(".tongdaolist  span").removeClass("tongdaolistspan");
        $(this).addClass("tongdaolistspan");
    });
	
	$("#chongzhibutton button").click(function(e) {
            var btn = $(this);
			btn.button('loading');
			money = $("#Money").val();
			if(money == ""){
				jAlert("金额不能为空！","提示信息");
				btn.button('reset');
				return false;
			}else{
				paypassword = $("#paypassword").val();
				if(paypassword == ""){
					jAlert("支付密码不能为空！","提示信息");
					btn.button('reset');
					return false;
				}else{
					////////////////////////////////////////////////////////////////////
					ajaxurl = $("#checkpaypassword").val();
					$.ajax({
							type:'POST',
							url:ajaxurl,
							data:"paypassword="+paypassword,
							dataType:'text',
							success:function(str){
								switch(str){
									case "nullerror":
										jAlert("支付密码不能为空！","提示信息");
										btn.button('reset');
					                	return false;
									break;
									case "passworderror":
										jAlert("支付密码错误！","提示信息");
										btn.button('reset');
					                	return false;
									break;
									case "ok":
										$("#lightbox").show();
			                        	$("#overlay").show();
										$("#jyje").text(money);
										$("#Formczcz").submit();
									break;
								}
								},
							error:function(XMLHttpRequest, textStatus, errorThrown) {
								
								}	
							});
					////////////////////////////////////////////////////////////////////
				}
			}
			
    });
	
});


function loadbank(payapiid){
	ajaxurl = $("#ajaxurl").val();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"payapiid="+payapiid,
			dataType:'json',
			success:function(obj){
				$(".czmmbutton").hide();
				str = "";
				for(t in obj){
					str = str + '<div><input type="radio" name="bankname" value="'+obj[t]["bankcode"]+'"> <img src="/Uploads/bankimg/'+obj[t]["images"]+'" alt="'+obj[t]["bankname"]+'"></div>';
				}
				$(".banklist").html(str);
				$(".banklist div input").click(function(e) {
					$(".banklist div img").removeClass("bankselect");
					$(this).next("img").addClass("bankselect");
					$(".czmmbutton").show();
				});
				$(".banklist div img").click(function(e) {
					$(this).prev().click();
					$(".banklist div img").removeClass("bankselect");
					$(this).addClass("bankselect");
				});
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				
				}	
			});
}

function guanbi(){
	$('#overlay').hide();
	$('#lightbox').hide();
	$("#chongzhibutton button").button('reset');
}
