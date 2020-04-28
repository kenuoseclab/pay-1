$(document).ready(function(){
    $(".title-bar-toggle").off().on("click",function(){
        $(".pc-switch").toggleClass('title-bar-active');
        $(".mobile-nav").toggleClass('show') ;
    });
    $.ajax({
        url:'/spEnterprise/saas/deoratorsAction!merTopThird.action',
    
        dataType : "json",//设置需要返回的数据类型
        type :"get",
        success : function(data) {
            var obj = $.parseJSON(data);
            if(obj.loginState==1){

                if(obj.signState==4){
                    $("#xiala").append('<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown"><span id="username"title='+obj.name+'>'+obj.name+'</span><span class="caret"></span></a><ul class="dropdown-menu dzz-menu" role="menu"><li class="dzz-menu-top"><img src="../../../images/saas/sel-white.png"></li><li><a href="/spEnterprise/soopay/porder_SAAS.action">运营中心</a></li><li><a href="/spEnterprise/account/cashDetail_SAAS.action">财务中心</a></li><li><a href="/spEnterprise/saas/tAppInfoAction!listPage.action">应用管理</a></li><li><a href="/spEnterprise/saas/signatureAction!TurnProduct.action">合同管理</a></li><li><a href="/spEnterprise/saas/accountInformationAction!Exhibition.action">账号管理</a></li><li class="divider"></li><li><a id="loginOut" href="/spEnterprise/authenticationSaasAction!loginOut.action">退出登录</a></li></ul></li>')
                }else{

                    $("#xiala").append('<li class="dropdown"><a href="#" class="dropdown-toggle weirenzheng-model" data-toggle="dropdown"><span id="username"title='+obj.name+'>'+obj.name+'</span><span class="caret"></span></a><ul class="dropdown-menu dzz-menu" role="menu"><li class="dzz-menu-top"><img src="${basePath}/images/saas/sel-white.png"></li><li><a href="/spEnterprise/saas/cryptographicAction!GoModifyPassword.action">密码修改</a></li><li class="divider"></li><li><a id="loginOut" href="/spEnterprise/authenticationSaasAction!loginOut.action">退出登录</a></li></ul></li>')
                }
            }
            var username = $("#username").text();
            if(username.length>12){
                $("#username").text(username.substring(0, 12)+"...");

            }
            if(obj.loginState==0){
               $("#xiala").append('<li class="dropdown"><a class="register" href="/spEnterprise/registerSaasAction!execute.action">注册</a></li><li class="dropdown"><a class="login" href="/spEnterprise/authenticationSaasAction!execute.action">登录</a></li>');
            }else{

                if(obj.flag==0){
                    $('#xiala').append('<li class="news-li"><a class="dzz-news" href="/spEnterprise/saas/msgCenterAction!gotoMessageCenter.action"><img src="image/news-icon.png"><span ></span></a></li>');
                }else{
                    $('#xiala').append('<li class="news-li"><a class="dzz-news" href="/spEnterprise/saas/msgCenterAction!gotoMessageCenter.action"><img src="image/news-icon.png" class="sign-animation"><span class="dzz-news-sign"></span></a></li>');

                }
            }
        }

    });
      //接入流程状态
     //var state=${sessionScope.merJoin.signState};
     //合同状态
     //var contState=${sessionScope.contState};
     //应用状态
    /* var appCount=${sessionScope.appCount};
     gaoliangxiangshi(state,contState,appCount);
     function gaoliangxiangshi(val,val2,val3){
     var notify;
     switch(val){
     case 0:
     //企业未提交认证信息（初始化状态）
     notify=1;
     break;
     case 1:
     //企业已提交认证，未返回认证结果（运营审核中）
     notify=2;
     break;
     case 2:
     //企业认证已通过，银行打款成功，待企业回填认证金额
     notify=5;
     break;
     case 3:
     //企业认证被驳回
     notify=3;
     break;
     case 4:
     //企业已认证
     if(val2=="2"){
     //企业已认证且已签约合同
     if(val3==0){
     //企业认证已通过，已签约线上支付产品，未添加应用
     notify=7;
     }else{
     //企业认证已通过，已签约线上支付产品，已添加应用（最终状态）
     notify=8;
     }
     }else{
     //企业认证已通过，尚未签约线上支付产品（判断签约优先级大于应用，故此不区分应用）
     notify=6;
     }
     break;
     case 5:
     //企业认证已通过，银行打款失败
     notify=4;
     break;
     default:
     //其他状态
     notify=0;
     break;
     }
     $.ajax({
     url:'js/common/gaoli.txt',
     dataType : "json",//设置需要返回的数据类型
     type :"get",
     data : {
     notifyId : notify
     },
     dataType : "json",//设置需要返回的数据类型
     success : function(data,textStatus,jqXHR) {
     var obj = $.parseJSON(data);
     drawNotify(obj);
     console.log(obj.retCode);
     if(obj.retCode!=undefined){
     if(obj.retCode="00060999"){
     $("#topmsg").hide();
     }
     }

     }
     });
     }
    /*   var obj={notifyType:2}
     console.log(obj.notifyType)
     drawNotify(obj);*/
    /**
     * PC端导航
     */
    $(".product_price,.sub-nav").hover(function(){
//        var n1 = 52;
//        $(".nav-line").stop().animate({width:n1+3.5},300,function(){
//        });
        $(".sub-nav").addClass("show");
    	  $(this).find("img").attr("src","image/bannerred.png");
//        $(".sub-nav").stop().animate({ display:"block",opacity:"1"},500,function(){});
//        $(".sub-nav").show();


    },function(){
    	 $(this).find("img").attr("src","image/bannerdown.png");
    	$(".sub-nav").removeClass("show");
//        $(".sub-nav").stop().animate({ display:"none", opacity:"0"},1000,function(){$(".sub-nav").hide()});

//        $(".nav-line").stop().animate({width:0},300,function(){
//
//        });
    });
    $(".navbar-nav li").hover(function(){
        var n1 = 52;
        $(this).find(".nav-line").stop().animate({width:n1+3.5},300,function(){
         });
        
    },
       function(){
         $(".nav-line").stop().animate({width:0},300,function(){

         });
    });
    /**
     * PC端绑定跳转footer jsp页面
     */
    $("ul.footer-nav").on("click","li",function(){
        var typeClass = $(this).children("a").attr("class");
        typeClass = typeClass.substring(4);
        jumpJsp(typeClass);
    });
    /**
     * 手机端绑定跳转footer jsp页面
     */
    $(".mobile-footer ul").on("click","li",function(){
        var typeClass = $(this).children("a").attr("class");
        typeClass = typeClass.substring(4);
        jumpJsp(typeClass);
    });
    function jumpJsp(typeClass){
        var Url = "saas/guidelinesAction!gotoFooterPage.action?typeClass=" + typeClass;
        window.location.href = Url;
    }
    
   $('.dzz-logo').hover(function(){
	   $(this).attr('src', 'image/logofont.png');
   }, function(){
	   $(this).attr('src', 'image/logo.png');
   })
   
//	控制春节放假通知的显示隐藏
	var nowTime= new Date().getTime();
	var startTime = (new Date("2018/2/7 0:0:0")).getTime();  
	var endTime = (new Date("2018/2/21 23:59:59")).getTime();  
	var _width= document.body.clientWidth;
	
	function initani(){
		$('.head').removeClass('springani');
		$('.newmianboady').removeClass('springanicon');
		$('.head').removeClass('springaniup');
		$('.newmianboady').removeClass('springaniconup');
	}
	if(nowTime>=startTime&&nowTime<=endTime&&_width>768){
		initani();
		$('.sub-nav').css('top','133px');
		$('.head').addClass('springani');
		$('.newmianboady').addClass('springanicon');	
	}else{
		$('.spring-ino').hide();
		$('.head').css('top','0px');
		//$('.sub-nav').css('top','87px');
		//$('.newmianboady').css('margin-top','87px');
	}

	$('#springx').on('click',function(){
		initani();
		$('.head').addClass('springaniup');
		if(_width>768){
			$('.newmianboady').addClass('springaniconup');
		}	
	})
  
});