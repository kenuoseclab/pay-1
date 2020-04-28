// JavaScript Document
$(document).ready(function(e) {
	
    defaultpayapi = $("#defaultdfapi").val();
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