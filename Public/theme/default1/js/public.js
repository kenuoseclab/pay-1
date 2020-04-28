    
    function drawNotify(obj){
    
		$("#notifyBtn").html("");
		$("#notifyMsg").html("");
		$("#topmsg").addClass("open");
		if(obj.notifyType=='1'){
			RecvNotifyForOne(obj.wholeStr);
		}else if(obj.notifyType=='5'){
			RecvNotifyForTwo(obj.frontStr,obj.lateStr,obj.aUrl,obj.aStr);
			$('#notifyMsg a').click(function(){
				$.ajax({
					url : '/spEnterprise/saas/merNoPassInfAction!getMerNoPassInf.action',
				    dataType : "json",//设置需要返回的数据类型
				    type :"post",
				    async: false,
					success : function(data1) {
					    var obj1 = $.parseJSON(data1);
					    var urlJump;
					    if(obj1.flagPage== 1){
					    	window.location.href= obj1.flagPageUrl;
					    }else if(obj1.flagPage== 2){
					    	window.location.href= obj1.flagPageUrl;
					    }else{
					    	window.location.href= obj1.flagPageUrl;
					    }
		      		}						
				});
			})

		}else if(obj.notifyType=='3'){
			RecvNotifyForThree(obj.wholeStr,obj.btnUrl,obj.btnStr);
		}else if(obj.notifyType=='4'){
			RecvNotifyForFour(obj.frontStr,obj.lateStr,obj.aUrl,obj.aStr,obj.btnUrl,obj.btnStr);
		}else if(obj.notifyType=='2'){
			RecvNotifyForTwo(obj.frontStr,obj.lateStr,obj.aUrl,obj.aStr);
		}	
	};

	function RecvNotifyForOne(wholeStr){
		$("#notifyMsg").append("您有一待办事件："+"<b>"+wholeStr+"</b>");
	     $(".dzz-top-msg").addClass("open animation");
}
function RecvNotifyForTwo(frontStr,lateStr,aUrl,aStr){
		$("#notifyMsg").append("您有一待办事件："+"<b>"+frontStr+"</b>"+"<a href=\""+aUrl+"\">"+aStr+"</a>"+"<b>"+lateStr+"</b>");
	     $(".dzz-top-msg").addClass("open animation");
		
}
function RecvNotifyForThree(wholeStr,btnUrl,btnStr){
		$("#notifyMsg").append("您有一待办事件："+"<b>"+wholeStr+"</b>");
		$("#notifyBtn").append("<a class=\"common-btn-back\" href=\""+btnUrl+"\">"+btnStr+"</a>");
	     $(".dzz-top-msg").addClass("open animation");

}
function RecvNotifyForFour(frontStr,lateStr,aUrl,aStr,btnUrl,btnStr){
		$("#notifyMsg").append("您有一待办事件："+"<b>"+frontStr+"</b>"+"<a href=\""+aUrl+"\">"+aStr+"</a>"+"<b>"+lateStr+"</b>");
		$("#notifyBtn").append("<a class=\"common-btn-back\" href=\""+btnUrl+"\">"+btnStr+"</a>");
	     $(".dzz-top-msg").addClass("open animation");

};

$(document).ready(function(){

	$(".title-bar-toggle").off().on("click",function(){
					      $(".pc-switch").toggleClass('title-bar-active');
					      $(".mobile-nav").toggleClass('show')
    					});
	//点击调用动画
	$(".addanimation").click( function(){
			 new $.modal({
		         animation:true,//调用动画
	         }).show();
	});
	$(".animationtwo").click( function(){
		 new $.modal({
	         animation:1,//调用动画
        }).hide();
});


	
    });

