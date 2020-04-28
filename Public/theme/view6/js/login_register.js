$(function(){
//  点击注册下侧"服务协议"展开关闭
    $(".main_content .register_bottom .protocol_btn").on('click',function(){
   		$(".reg_xy").removeClass("active");
    });
   
    $(".agree ").on('click',function(){
   		$(".reg_xy").addClass("active");
    });
    
    $(".contents_btnClick").on("click",function(){
    	$(".contents_box").removeClass("active");
    });
   
    $(".bottom_btn").on("click",function(){
   		$(".contents_box").addClass("active");
    });
    
    $(".protocol_box .protocol_btnClose").on('click',function(){
    	$(".protocol_content").addClass("active");
    });
    
//  点击注册登录切换页面内容
    $(".main_content .top_btns .top_btnRegister").on('click',function(){
    	$(".wrapper_con .main_box .main_registerCon").removeClass("active");
    	$(".wrapper_con .main_box .main_loginCon").addClass("active");
    });
    $(".main_content .top_btns .top_btnLogin").on('click',function(){
    	$(".wrapper_con .main_box .main_loginCon").removeClass("active");
    	$(".wrapper_con .main_box .main_registerCon").addClass("active");
    });
	
})
$(window).resize(function(){
   window.location.reload();
    //location.reload()
    //这里你可以尽情的写你的刷新代码！
});