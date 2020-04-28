$(function () {
	var contachTop = 100; // 右侧联系我们默认高度
	function Qust_contachScroll (){
		var st = 0;
		if (document.documentElement && document.documentElement.scrollTop) {
			st = document.documentElement.scrollTop;
		} else if (document.body) {
			st = document.body.scrollTop;
		}
		
		var contactTop = $(".qust_contach").offset().top;
		var height = $(".qust_contach").height();
		
		if ( st>80) {
			var top = (document.documentElement.clientHeight  - height)/2+st -80;
			
			$(".qust_contach").stop().animate(
				{
					top: top
				},300,null,function(){
					$("#toTop").stop().animate({
						height:45
					});
				});
		} else {
			var top = (document.documentElement.clientHeight  - height)/2 -80;
			//$(".qust_contach").css({
			//top: top
			//});
			if(top<=0)
			{
				top=contachTop
			}
			$(".qust_contach").stop().animate(
				{
					top: top
				},300,null,function(){
					$("#toTop").stop().animate({
						height:0
					});
				});
		}
	}
	
	function qust_showScroll(){
		var st = 0;
		if (document.documentElement && document.documentElement.scrollTop) {
			st = document.documentElement.scrollTop;
		} else if (document.body) {
			st = document.body.scrollTop;
		}
		
		var contactTop = $(".qust_show").offset().top;
		var height = $(".qust_show").height();
		
		if ( st>80) {
			var top = (document.documentElement.clientHeight  - height)/2+st -80;
			
			$(".qust_show").stop().animate(
				{
					top: top
				},300);
		} else {
			var top = (document.documentElement.clientHeight  - height)/2 -80;
			//$(".qust_contach").css({
			//top: top
			//});
			if(top<=0)
			{
				top=contachTop
			}
			$(".qust_show").stop().animate(
				{
					top: top
				},300);
		}
	}
	
	Qust_contachScroll();
	qust_showScroll();
	$(window).scroll(function () {
		Qust_contachScroll();
		qust_showScroll();
	});
	$(window).resize(function () {
		Qust_contachScroll();
		qust_showScroll();
	});
	
	$(".qst_close").click(function(){
		$(".qust_contach").fadeOut(function(){$(".qust_show").fadeIn();});
		
	});
	$(".qust_show").click(function(){
		
		$(".qust_show").fadeOut(function(){$(".qust_contach").fadeIn();});
	});
	$("#toTop").click(function(){
		$(".qust_contach").stop().animate(
			{
				top: contachTop
			},300);
		jQuery("html, body").animate({ scrollTop: 0 }, 300);			
		
	});
	
	
});