$(function(){
//	查询订单
	var screenH = $(window).height();
	var headerH = $(".header_common").height();
	var content_boxH = screenH - headerH;
	$(".check_helpCom").css("min-height",content_boxH + "px");
	var order_help_contentH = $(".order_help_content").height();
	$(".check_helpCom").css("height", order_help_contentH - 200 + "px");
})

$(window).resize(function(){    
	var screenH = $(window).height();
	var headerH = $(".header_common").height();
	var content_boxH = screenH - headerH;
	$(".check_helpCom").css("min-height",content_boxH + "px");
	var order_help_contentH = $(".order_help_content").height();
	$(".check_helpCom").css("height", order_help_contentH - 200 + "px");
})
