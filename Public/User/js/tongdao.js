// JavaScript Document
$(document).ready(function(e) {
	
	  $.ajax({
			type:'POST',
			url:$("#tksxfajaxurl").val(),
			dataType:'json',
			success:function(obj){
					for(t in obj){
						$("#"+t+" input").each(function(index, element) {
							$(this).val(obj[t][$(this).attr("name")]);    
						});
					}
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				}	
			});
	
    defaultpayapi = $("#defaultpayapi").val();
	$(".buttontongdao").each(function(index, element) {
        idname = $(this).attr("id");
		if(defaultpayapi == idname){
			$(this).addClass("btn-success").append('<span class="glyphicon glyphicon-ok"></span>');
		}
    });
	
	$(".buttontongdao").click(function(e) {
        idname = $(this).attr("id");
		urlstr = $("#ajaxurl").val();
		$.ajax({
			type:'POST',
			url:urlstr,
			data:"defaultpayapi="+idname,
			dataType:'text',
			success:function(str){
			    jAlert(str,"提示信息",function(){
					window.location.reload();
					});
				 
				},
			error:function(str){
				
				//////////////////////////
				}	
			});
    });
	
});