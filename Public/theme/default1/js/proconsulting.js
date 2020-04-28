
$(function(){
	var flag=true;
	//实现滚动条无法滚动
	var mo=function(e){
		e.preventDefault();
	};
	/***产品咨询动画动画***/
	$("#hhx-cpzx").bind("mouseenter",function (event){
		var evt = event ? event:window.event;
		window.event? window.event.cancelBubble = true : evt.stopPropagation();
	});
	$(".consult-btn").bind({
	    mouseenter: ie9_mouseenter,
	    mouseleave: ie9_mouseleave
	});
	$("#pro-btn-close").bind({
		mouseenter: ie9_mouseenter
	});


	function ie9_mouseenter(event){
		var evt = event ? event:window.event;
		window.event? window.event.cancelBubble = true : evt.stopPropagation();
		$(".consult-btn #hhx-span1").animate({"top":"-100%"},300);
		$(".consult-btn #hhx-span2").animate({"top":"0"},300);
		$(".consult-btn #hhx-cpzx").animate({"top":"-40px","opacity":"1"},300);
	}
	function ie9_mouseleave(event){
		var evt = event ? event:window.event;
		window.event? window.event.cancelBubble = true : evt.stopPropagation();
		$(".consult-btn #hhx-span1").animate({"top":"0"},300);
		$(".consult-btn #hhx-span2").animate({"top":"100%"},300);
		$(".consult-btn #hhx-cpzx").animate({"top":"-35px","opacity":"0"},300);
	}
	/***产品咨询动画动画 end***/
	/***禁止滑动***/
	function stop(){
		document.body.parentNode.style.overflow='hidden';        
        document.addEventListener("touchmove",mo,false);//禁止页面滑动
	}

	/***取消滑动限制***/
	function move(){
	
        document.body.parentNode.style.overflow='';//出现滚动条
        document.removeEventListener("touchmove",mo,false);        
	}
	$("#hhx-cpzx").bind('click',function(){
		window.open("http://218.206.68.233:7005/JtalkManager/echatManager.do?companyPk=8a8ad81d4999e8ec014999eab564002b&codeKey=18");
	});
	
	$("#pro-btn-close").bind('click',function(){
		$(this).removeClass('btn-animateC');
		$(this).removeClass('btn-animateO');
		if(flag){
			$(".consult-btn").removeClass('hhx-hidden');
			$("#pro-btn-close").addClass('btn-animateC');
			$("#hhx-cpzx").css("display","none");
			$('.line').addClass('close-line');
			$('.btn-txt').hide();
			$('#mask').animate({'right':'0'},200);
			$('.fot').animate({'right':'0'},200);
			$('#mask').css('overflow-y','scroll');
			if(navigator.appName == "Microsoft Internet Explorer" && navigator.appVersion.match(/9./i)=="9.") 
			{ 
				$("#pro-btn-close").animate({'width':'50px','height':'50px'},300,function(){
					$("#pro-btn-close").css('background','#f3f3f3');
				});
				$('.line').css({"transform":"rotate(" + 90 + "deg)","opacity":"1"});
			}
			stop();
			flag=false;
		}else{
			$(".consult-btn").addClass('hhx-hidden');
			$("#pro-btn-close").addClass('btn-animateO');
			$("#hhx-cpzx").css("display","block");
			$('.line').removeClass('close-line');
			$('.btn-txt').show();
			$('#mask').animate({'right':'-450px'},200);
			$('.fot').animate({'right':'-450px'},200);
			$('#mask').css('overflow-y','hidden');
			if(navigator.appName == "Microsoft Internet Explorer" && navigator.appVersion.match(/9./i)=="9.") 
			{ 	
				$("#pro-btn-close").animate({'width':'100px','height':'45px'},300,function(){
					$("#pro-btn-close").css('background','#4ca6f4');
				});
			}
			move();
			flag=true;
		}
	});
})

$(function(){	
//	表单校验
    function errorTxt(txt){
      $('.error-txt').html(txt);
      $('.error-txt').show().stop(true).animate({'opacity':1},500).delay(700).animate({'opacity':0},500,function(){
      	$('.error-txt').hide();
      });
    };
    $(window).scroll(function() {
      $('.error-txt').animate({'opacity':0},500,function(){
      	$('.error-txt').hide();
      });
    });
    
	$('#pro-btn').off().on('click',function(){
		var phonereg= /^1(3|4|7|5|8)([0-9]{9})$/,
			phoneval=$.trim($("#phone").val()),
			uname=$.trim($("#name").val()),
			ucompany=$.trim($("#company").val()),
			ujob=$.trim($("#job").val()),
			uaddress=$.trim($("#address").val()),
			udisc=$.trim($("#disc").val()),
			chineselength=/^(([\u4e00-\uFA29])|(\·)|([A-Za-z])){2,16}$/,
			companylength=/^.{2,20}$/,
			joblength=/^.{2,20}$/,
			addresslength=/^.{5,50}$/,
			disclength=/^.{3,200}$/;
			if(!uname || !chineselength.test(uname)){			
				errorTxt('请填写正确的姓名，2-16个字符');
				$("#name").focus();
				$("#name").addClass('error-input');
				return false;
			}
    		else{
    			$("#name").removeClass('error-input');
    		}
    		if(!phoneval || !phonereg.test(phoneval)){			
				errorTxt('请正确填写11位手机号');
				$("#phone").focus();
				$("#phone").addClass('error-input');
				return false;
			}
    		else{
    			$("#phone").removeClass('error-input');
    		}
    		if(!ucompany || !companylength.test(ucompany)){			
				errorTxt('请填写正确公司名称，2-20个字符');
				$("#company").focus();
				$("#company").addClass('error-input');
				return false;
			}
    		else{
				$("#company").removeClass('error-input');
			}
    		if(!ujob || !joblength.test(ujob)){			
				errorTxt('请填写正确职位，2-20个字符');
				$("#job").focus();
				$("#job").addClass('error-input');
				return false;
			}
    		else{
				$("#job").removeClass('error-input');
			}
    		if(!uaddress || !addresslength.test(uaddress)){			
				errorTxt('请填写详细的公司地址，5-50个字符');
				$("#address").focus();
				$("#address").addClass('error-input');
				return false;
			}
    		else{
				$("#address").removeClass('error-input');
			}
    		if(!udisc || !disclength.test(udisc)){			
				errorTxt('请填写具体诉求，3-200个字符');
				$("#disc").focus();
				$("#disc").addClass('error-input');
				return false;
			}
    		else{
				$("#disc").removeClass('error-input');
			}
    		
    		
    		var alldata={'submitName':uname,'mobileId':phoneval,'storeShortName':ucompany,'contactPosition':ujob,'address':uaddress,'feedBack':udisc,'source':'PC官网'}
    		 $.ajax({
		 	 
		      url:"/spEnterprise/saas/expansionInfoAction!saveExpansion.action",
		      type : "get",
		      dataType: 'json',
		      data:alldata,
		      success : function(result){
		    	var res = JSON.parse(result);
		      	if(res.retCode== '0000'){
		      		errorTxt('提交成功');
		      		$('.pro-form input[type=text]').val('');
		      		$('.pro-form input[type=tel]').val('');
		      		$('.pro-form textarea').val('');
		      	}else{
		      		errorTxt('提交失败，请重试');
		      	}
		      },
		      error:function (XMLHttpRequest, textStatus){
		    	  errorTxt('提交失败，请重试');
		      }
		  })
	})

})
//ie9下动画兼容
var flag = true;  
if(navigator.userAgent.indexOf("MSIE")>0){     
    if(navigator.userAgent.indexOf("MSIE 9.0")>0){  
     
     var flag=true;
	$('#pro-btn-close').bind('click',function(){
		
		if(flag){
			$(this).animate({'width':'50px','height':'50px','border-radius':'50%'},200,function(){
				$(this).css('background','#f3f3f3');
			});
			$('.line').eq(0).css("transform", "rotate(" + 45 + "deg)");
			$('.line').eq(1).css("transform", "rotate(" + -45 + "deg)");
			flag=false;
		}else{
			$(this).animate({'width':'100px','height':'45px','border-radius':'25px'},200,function(){
				$(this).css('background','#4ca6f4');
			})
			flag=true;
		}
		
	})  
    }   
}else{  
	flag = false;  
}   
$(function(){
	var _h=$(window).height();
	$('#mask').css('height',_h+'px');
	$(window).resize(function(){
   		$('#mask').css('height',_h+'px');
	});
})
