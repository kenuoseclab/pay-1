$(document).ready(function(){
	
	$("#tkconfigbutton").click(function(e) {
		var btn = $(this);
		btn.button('loading');
        ajaxurl = $("#tikuanconfigform").attr("action");
               $.ajax({
					type:'POST',
					url:ajaxurl,
					data:"tkzxmoney="+$("#tkzxmoney").val()+"&tkzdmoney="+$("#tkzdmoney").val()+"&dayzdmoney="+$("#dayzdmoney").val()+"&dayzdnum="+$("#dayzdnum").val()+"&t1zt="+$("#t1zt").val()+"&t0zt="+$("#t0zt").val()+"&gmt0="+$("#gmt0").val()+"&tkzt="+$("#tkzt").val()+"&id="+$("#tkconfigid").val()+"&tktype="+$('#tktype').val()+"&systemxz="+$("#systemxz").val()+"&userid="+$("#tkuserid").val()+"&sxfrate="+$("#sxfrate").val()+"&sxffixed="+$("#sxffixed").val(),
					dataType:'text',
					success:function(str){
						    btn.button('reset');
							jAlert(str,'提示信息');
						},
					error:function(XMLHttpRequest, textStatus, errorThrown) {
						}	
				});
    });
	
	$("#systemxz").change(function(e) {
        if($(this).val() == 1){
			$(".tkconfigdiv").show();
		}else{
			$(".tkconfigdiv").hide();
			}
    });
	
	$("#qxqx").click(function(e){
		
	    $("[name='xz']").each(function(){

		    if($(this).attr("checked")){
		    	$(this).removeAttr("checked");
		    }else{
		    	$(this).attr("checked",'true');
		    }
			    
	     });
	});
	
	
	$(".tikuan-btn").click(function(e) {
		var datastr = "";
		var btn = $(this);
		btn.button('loading');
		
        formname = $(this).attr("tjname");
		
		$("#"+formname+" input").each(function(index, element) {
				$(this).attr("disabled","disabled");
            	datastr = datastr+ $(this).attr("name") + "="+ $(this).val()+"&";    
        });
		urlstr = $("#"+formname).attr("action");
		
		$.ajax({
			type:'POST',
			url:urlstr,
			data:datastr,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				$("#"+formname+" input").each(function(index, element) {
					$(this).removeAttr("disabled");
                });
				
				btn.button('reset');
			    jAlert(str,"提示信息");
				//$("#tscontent").text(str);
				//$('#myModal').modal('show');
				///////////////////////////////////
				},
			error:function(str){
				
				//////////////////////////
				}	
			});
		
		
    });
	
	
	$("#ptshsearch").click(function(){
		usernameidsearch = $("#usernameidsearch").val();
		statussearch = $("#statussearch").val();
		rzsearch = $("#rzsearch").val();
		usertype = $("#usertype").val();
		sjusernamesearch = $("#sjusernamesearch").val();
		window.location.href = "?usernameidsearch="+usernameidsearch+"&statussearch="+statussearch+"&rzsearch="+rzsearch+"&sjusernamesearch="+sjusernamesearch+"&usertype="+usertype;
	});
	
	$("#selectpage").change(function(e) {
        window.location.href = $(this).val();
    });
	
	$("#cxsc").click(function(e){		
	    $("#cxsc").button("loading");
        ajaxurl = $(this).attr("ajaxurl");
		$.ajax({
			type:'POST',
			url:ajaxurl,
			dataType:'text',
			success:function(str){
				$("#md5key").val(str);
				$("#cxsc").button("reset");
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				}	
			});
    });
	
	$(".modalgb").click(function(e) {
        window.location.reload();
    });
	
	
	$("#zjjbutton").click(function(e) {
		bgmoney = $("#bgmoney").val();
		if(bgmoney == "" || bgmoney == 0){
			jAlert('变更金额不能为空或为0','提示信息');
		}else{
			cztype = $("input[name='cztype']:checked").val();
			zjjuserid = $("#zjjuserid").val();
			zjjtongdaoid = $("#zjjtongdaoid").val();
			ajaxurl = $("#zjjform").attr("action");
			contentstr = $("#contentstr").val();
			
			$.ajax({
				type:'POST',
				url:ajaxurl,
				data:"userid="+zjjuserid+"&tongdaoid="+zjjtongdaoid+"&cztype="+cztype+"&bgmoney="+bgmoney+"&contentstr="+contentstr,
				dataType:'json',
				success:function(obj){
					
					if(obj["status"] == "ok"){
						$("#zjjtmoney").text(obj["money"]);
						jAlert('金额变更成功！','提示信息');
					}else{
						jAlert(obj["status"],'提示信息');
					}
					
					},
				error:function(XMLHttpRequest, textStatus, errorThrown) {
					}	
			});
		}

    });
	
	$("#djjebutton").click(function(e) {
        //////////////////////////////////////////////////////////////////////////////////////////////////////////
		bgmoney = $("#djbgmoney").val();
		if(bgmoney == "" || bgmoney == 0){
			jAlert('变更金额不能为空或为0','提示信息');
		}else{
			cztype = $("input[name='djtype']:checked").val();
			zjjuserid = $("#djjeuserid").val();
			zjjtongdaoid = $("#djjetongdaoid").val();
			ajaxurl = $("#djjeform").attr("action");
			contentstr = $("#djcontentstr").val();
			
			$.ajax({
				type:'POST',
				url:ajaxurl,
				data:"userid="+zjjuserid+"&tongdaoid="+zjjtongdaoid+"&cztype="+cztype+"&bgmoney="+bgmoney+"&contentstr="+contentstr,
				dataType:'json',
				success:function(obj){
					
					if(obj["status"] == "ok"){
						$("#zjjtmoney").text(obj["money"]);
						jAlert('金额变更成功！','提示信息');
					}else{
						jAlert(obj["status"],'提示信息');
					}
					
					},
				error:function(XMLHttpRequest, textStatus, errorThrown) {
					}	
			});
		}

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
    });
	
});

function clearNoNum(obj)
	{
		//先把非数字的都替换掉，除了数字和.
		obj.value = obj.value.replace(/[^\d.]/g,"");
		//必须保证第一个为数字而不是.
		obj.value = obj.value.replace(/^\./g,"");
		//保证只有出现一个.而没有多个.
		obj.value = obj.value.replace(/\.{2,}/g,".");
		//保证.只出现一次，而不能出现两次以上
		obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
	}


function editmoney(username,id,tongdaoid,divname){
	$("#zjj").hide();
	$("#djje").hide();
	$('#myModal').modal('show');
	$("#usernamemodal").text(username);
	$(".radioclass:eq(0)").attr("checked","checked");
	$(".radiospan").unbind("click").click(function(e) {
        $(this).prev().click();
		$("#zjjbutton").text($(this).text());
    });
	if(divname == "zjj"){
		ajaxurl =  $("#zjjform").attr("loadurl");
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"userid="+id+"&tongdaoid="+tongdaoid,
			dataType:'json',
			success:function(obj){
			     $("#zjjtongdaoname").text(obj["tongdaoname"]);
				 $("#zjjtmoney").text(obj["money"]);
				 $("#zjjuserid").val(id);
				 $("#zjjtongdaoid").val(tongdaoid);
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
	}
	if(divname == "djje"){
		$(".radioclassdj:eq(0)").attr("checked","checked");
		$(".radiospandj").unbind("click").click(function(e) {
			$(this).prev().click();
			$("#djjebutton").text($(this).text());
		});
		ajaxurl =  $("#djjeform").attr("loadurl");
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"userid="+id+"&tongdaoid="+tongdaoid,
			dataType:'json',
			success:function(obj){
			     $("#djjetongdaoname").text(obj["tongdaoname"]);
				 $("#djjemoney").text(obj["money"]);
				 $("#djjeymoney").text(obj["freezemoney"]);
				 $("#djjeuserid").val(id);
				 $("#djjetongdaoid").val(tongdaoid);
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
	}
	$("#"+divname).show();	
}

function edit(username,id,eq){
	$("#edituserlist input").val("");
	$('#myModal').modal('show');
	$("#edituserlist li a").unbind("click");
	$("#edituserlist li").each(function(index, element){
		$(this).children("a").click(function(e){
			 editmodal(id,index);
		});
	});
	$("#usernamemodal").text(username);
	$("#edituserlist li:eq("+eq+") a").click();
	//editmodal(id,eq);
}

function editmodal(id,eq){
	ajaxurl = $("#edituserlist li:eq("+eq+") a").attr("ajaxurl");
	switch(eq){
		case 0:
		// 基本信息
		$("#jbxx .form-group").hide();
		$("#jbxx .loadingclass").show();
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"userid="+id,
			dataType:'json',
			success:function(obj){
				$("#fullname").val(obj["fullname"]);
				$("#sfznumber").val(obj["sfznumber"]);
				$('select[name="sex"]').val(obj["sex"]);
				$("#birthday").val(obj["birthday"]);
				$("#phonenumber").val(obj["phonenumber"]);
				$("#qqnumber").val(obj["qqnumber"]);
				$("#address").val(obj["address"]);
				$('select[name="usertype"]').val(obj["usertype"]);
		        $("#jbxxid").val(obj["id"]);
				$("#userid").val(obj["userid"]);
				$("#jbxx .form-group").show();
				$("#jbxx .loadingclass").hide();
			}	
			});
		break;
		case 1:
		/*
		 * 状态信息
		 */
		$("#jihuo").hide();
		$("#jinyong").hide();
	    $("#zhengchang").hide();
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"userid="+id,
			dataType:'text',
			success:function(str){
				
				switch(parseInt(str)){
					case 0:
					zttitle = '<span style="color:#CCC">未激活</span>';
					$("#jihuo").show();
					break;
					case 1:
					zttitle = '<span style="color:#0C0">正常</span>';
					$("#jinyong").show();
					break;
					case 2:
					zttitle = '<span style="color:#F00">已禁用</span>';
					$("#zhengchang").show();
					break;
				}
				$("#dqzt").html(zttitle);
				$("#zhuangtaiid").val(id);
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
		break;
		case 2:
		/*
		 * 认证信息
		 */
		$("#weirenzheng").hide();
		$("#yirenzheng").hide();
		$("#renzhengtupian").hide();
		$("#shenhebutongguo").hide()
		$("#shenhetongguo").hide();
		$(".domainmd5key").hide();
	    $("#renzhengtupian a").each(function(index,element){
	    	$(this).attr("href","/Public/images/default.gif");
	    	$(this).children("img").attr("src","/Public/images/default.gif");
	    });
	    
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"userid="+id,
			dataType:'json',
			success:function(obj){
				switch(parseInt(obj["status"])){
					case 0:
					$("#weirenzheng").show();
					break;
					case 1:
					$("#yirenzheng").show();
					if(obj["uploadsfzzm"]){
						$("#renzhengtupian a:eq(0)").attr("href","/Uploads/verifyinfo/"+obj["uploadsfzzm"]);
						$("#renzhengtupian a:eq(0) img").attr("src","/Uploads/verifyinfo/"+obj["uploadsfzzm"]);
					}
					if(obj["uploadsfzbm"]){
						$("#renzhengtupian a:eq(1)").attr("href","/Uploads/verifyinfo/"+obj["uploadsfzbm"]);
						$("#renzhengtupian a:eq(1) img").attr("src","/Uploads/verifyinfo/"+obj["uploadsfzbm"]);
					}
					if(obj["uploadscsfz"]){
						$("#renzhengtupian a:eq(2)").attr("href","/Uploads/verifyinfo/"+obj["uploadscsfz"]);
						$("#renzhengtupian a:eq(2) img").attr("src","/Uploads/verifyinfo/"+obj["uploadscsfz"]);
					}
					
					if(obj["uploadyhkzm"]){
						$("#renzhengtupian a:eq(3)").attr("href","/Uploads/verifyinfo/"+obj["uploadyhkzm"]);
						$("#renzhengtupian a:eq(3) img").attr("src","/Uploads/verifyinfo/"+obj["uploadyhkzm"]);
					}
					if(obj["uploadyhkbm"]){
						$("#renzhengtupian a:eq(4)").attr("href","/Uploads/verifyinfo/"+obj["uploadyhkbm"]);
						$("#renzhengtupian a:eq(4) img").attr("src","/Uploads/verifyinfo/"+obj["uploadyhkbm"]);
					}
					if(obj["uploadyyzz"]){
						$("#renzhengtupian a:eq(5)").attr("href","/Uploads/verifyinfo/"+obj["uploadyyzz"]);
						$("#renzhengtupian a:eq(5) img").attr("src","/Uploads/verifyinfo/"+obj["uploadyyzz"]);
					}
					
				
					$("#renzhengtupian").show();
					$("#domain").val(obj["domain"]);
					$("#md5key").val(obj["md5key"]);
					$("#renzhengid").val(id);
					$(".domainmd5key").show();
					break;
					case 2:
					if(obj["uploadsfzzm"]){
						$("#renzhengtupian a:eq(0)").attr("href","/Uploads/verifyinfo/"+obj["uploadsfzzm"]);
						$("#renzhengtupian a:eq(0) img").attr("src","/Uploads/verifyinfo/"+obj["uploadsfzzm"]);
					}
					if(obj["uploadsfzbm"]){
						$("#renzhengtupian a:eq(1)").attr("href","/Uploads/verifyinfo/"+obj["uploadsfzbm"]);
						$("#renzhengtupian a:eq(1) img").attr("src","/Uploads/verifyinfo/"+obj["uploadsfzbm"]);
					}
					if(obj["uploadscsfz"]){
						$("#renzhengtupian a:eq(2)").attr("href","/Uploads/verifyinfo/"+obj["uploadscsfz"]);
						$("#renzhengtupian a:eq(2) img").attr("src","/Uploads/verifyinfo/"+obj["uploadscsfz"]);
					}
					
					if(obj["uploadyhkzm"]){
						$("#renzhengtupian a:eq(3)").attr("href","/Uploads/verifyinfo/"+obj["uploadyhkzm"]);
						$("#renzhengtupian a:eq(3) img").attr("src","/Uploads/verifyinfo/"+obj["uploadyhkzm"]);
					}
					if(obj["uploadyhkbm"]){
						$("#renzhengtupian a:eq(4)").attr("href","/Uploads/verifyinfo/"+obj["uploadyhkbm"]);
						$("#renzhengtupian a:eq(4) img").attr("src","/Uploads/verifyinfo/"+obj["uploadyhkbm"]);
					}
					if(obj["uploadyyzz"]){
						$("#renzhengtupian a:eq(5)").attr("href","/Uploads/verifyinfo/"+obj["uploadyyzz"]);
						$("#renzhengtupian a:eq(5) img").attr("src","/Uploads/verifyinfo/"+obj["uploadyyzz"]);
					}
					$("#renzhengtupian").show();
					$("#shenhebutongguo").show()
		            $("#shenhetongguo").show();
		            $("#renzhengid").val(id);
					break;
				}
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
		break;
		case 3:
		$("#loginpassword").val("");
		$("#paypassword").val("");
		$("#passwordid").val(id);
		break;
		case 4:
		$("#yinhangka .form-group").hide();
		$("#yinhangka .loadingclass").show();
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"userid="+id,
			dataType:'json',
			success:function(obj){
				
				$("#kdatetime").text(obj["kdatetime"]);
				$("#ip").text(obj["ip"]);
				$("#ipaddress").text(obj["ipaddress"]);
				if(parseInt(obj["disabled"]) == 0){
					$("#jdatetime").text(obj["jdatetime"]);
				}else{
					$("#jdatetime").text("已禁止修改");
				}
			    $("#bankname").val(obj["bankname"]);
			    $("#bankfenname").val(obj["bankfenname"]);
			    $("#bankzhiname").val(obj["bankzhiname"]);
			    $("#banknumber").val(obj["banknumber"]);
			    $("#bankfullname").val(obj["bankfullname"]);
			    $("#sheng").val(obj["sheng"]);
			    $("#shi").val(obj["shi"]);
				
				$("#yinhangka .form-group").show();
				$("#yinhangka .loadingclass").hide();
				$("#bankcardid").val(obj["id"]);
				$("#bankcarduserid").val(id);
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
		break;
		/*
		*提款手续费
		*/
		case 5:
		
        $.ajax({
			type:'POST',
			url:ajaxurl,
			data:"userid="+id,
			dataType:'json',
			success:function(obj){
				    tksz = obj["tksz"];
					for(t in tksz){
						$("#"+t+" input").each(function(index, element) {
							$(this).val(tksz[t][$(this).attr("name")]);    
						});
					}
					tikuanconfig = obj["tikuanconfig"];
					if(tikuanconfig["systemxz"] == 0){
						$(".tkconfigdiv").hide();
					}
					for(t in tikuanconfig){
						if(t == "id"){
							$("#tkconfigid").val(tikuanconfig["id"]);
							$("#tkuserid").val(tikuanconfig["userid"]);
						}else{
							$("#"+t).val(tikuanconfig[t]);
						}
						
					}
				}
			});
		break;
		/*
		 * 费率信息
		 */
		case 6:
		$("#feilv button").attr("disabled",true);
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"userid="+id,
			dataType:'json',
			success:function(obj){
				
				for(t in obj){
					valval = obj[t].split("|");
					$("#feilv"+t).val(valval[0]);
					$("#fengding"+t).val(valval[1]);
				 }
				
				 for(t in obj){
					$("#feilvbuttton"+t).unbind("click").click(function(e){
						payapiid = $(this).attr("payapiid");
						ajaxurl = $(this).attr("ajaxurl");
						feilvval = $("#"+$(this).attr("inputid")).val();
						fengdingval = $("#"+$(this).attr("fengdingid")).val();
						
						$.ajax({
						type:'POST',
						url:ajaxurl,
						data:"userid="+id+"&payapiid="+payapiid+"&feilvval="+feilvval+"&fengdingval="+fengdingval,
						dataType:'text',
						success:function(str){
							   if(str == "ok"){
									jAlert('修改成功！','提示信息');
									
								}else{
									jAlert("修改失败，请稍后重试！",'提示信息');
								}
							},
						error:function(XMLHttpRequest, textStatus, errorThrown) {  
							
							//////////////////////////
							}	
						});
					});
				}
				
				$("#feilv button").attr("disabled",false);
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				}	
			});
		break;
		case 7:
		/*
		 * 通道信息
		 */
		$("#tongdao button").attr("disabled",true).removeClass("btn-danger").attr("select",0);
		$("#tongdao button span").removeClass("glyphicon").removeClass("glyphicon-ok");
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"userid="+id,
			dataType:'json',
			success:function(str){
				payapiaccountarray = str["payapiaccountarray"];
				for(var keystr in payapiaccountarray){
					$("#"+keystr).val(payapiaccountarray[keystr]);
				}
				obj = str["list"];
				arraystr = obj["payapicontent"].split("|");
				for(var i = 0; i < arraystr.length; i++){
					$("#"+arraystr[i]).addClass("btn-danger").attr("select",1);
					$("#"+arraystr[i]).children("span").addClass("glyphicon").addClass("glyphicon-ok");
				}
				$("#tongdaouserid").val(id);
				$("#tongdao button").attr("disabled",false);
				$("#tongdao button").unbind("click").click(function(e){
					indexindex = $(this).index();
					$.ajax({
						type:'POST',
						url:$("#tongdaouserid").attr("ajaxurl"),
						data:"userid="+id+"&selecttype="+$("#tongdao button:eq("+indexindex+")").attr("select")+"&payname="+$("#tongdao button:eq("+indexindex+")").attr("id"),
						dataType:'text',
						success:function(str){
							//editmodal(id,7);
							if(str == "ok"){
								if($("#tongdao button:eq("+indexindex+")").attr("select") == "1"){
									$("#tongdao button:eq("+indexindex+")").removeClass("btn-danger").attr("select",0);
									$("#tongdao button:eq("+indexindex+") span").removeClass("glyphicon").removeClass("glyphicon-ok");
								}else{
									$("#tongdao button:eq("+indexindex+")").addClass("btn-danger").attr("select",1);
									$("#tongdao button:eq("+indexindex+") span").addClass("glyphicon").addClass("glyphicon-ok");
									if(obj["disabled"] == $("#tongdao button:eq("+indexindex+")").attr("id")){
										$("#tongdao button[id='"+obj["disabled"]+"']").attr("disabled",true);
									}
								}
								
							}
							
							},
						error:function(XMLHttpRequest, textStatus, errorThrown) {  
							
							//////////////////////////
							}	
						});
					////////////////////////////////////////////////////////////////////////////
				});
				
				if($("#tongdao button[id='"+obj["disabled"]+"']").attr("select") == "1"){
					$("#tongdao button[id='"+obj["disabled"]+"']").attr("disabled",true);
				}
				
				
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
		break;
	}
}

function editjbxx(ajaxurl){
	var fullname = $("#fullname").val();
	var sfznumber = $("#sfznumber").val();
	var sex = $("#sex").val();
	var birthday = $("#birthday").val();
	var phonenumber = $("#phonenumber").val();
	var qqnumber = $("#qqnumber").val();
	var address = $("#address").val();
	var id = $("#jbxxid").val();
	var userid = $("#userid").val();
    var usertype = $('#usermodel').val();
	$datastr = "fullname="+fullname+"&sfznumber="+sfznumber+"&sex="+sex+"&birthday="+birthday+"&phonenumber="+phonenumber+"&qqnumber="+qqnumber+"&usertype="+usertype+"&address="+address+"&id="+id+"&userid="+userid;
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:$datastr,
			dataType:'text',
			success:function(str){
				
				if(str == "ok"){
					jAlert('修改成功！','提示信息');
					
				}else{
					jAlert("修改失败，请稍后重试！",'提示信息');
				}
				$('#myModal').modal('hide');
				},
			});
}

function xgzhuangtai(ajaxurl,status){
	userid = $("#zhuangtaiid").val();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"status="+status+"&userid="+userid,
			dataType:'text',
			success:function(str){
				if(str == "ok"){
					jAlert('修改成功！','提示信息');
					editmodal(userid,1);
				}else{
					jAlert("修改失败，请稍后重试！",'提示信息');
				}
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				alert(errorThrown);
				//////////////////////////
				}	
			});
}

function renzhengedit(ajaxurl,status){
	userid = $("#renzhengid").val();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"status="+status+"&userid="+userid,
			dataType:'text',
			success:function(str){
				if(str == "ok"){
					jAlert('修改成功！','提示信息');
					editmodal(userid,2);
				}else{
					jAlert("修改失败，请稍后重试！",'提示信息');
				}
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				alert(errorThrown);
				//////////////////////////
				}	
			});
}

function renzhengeditdomain(ajaxurl){
	userid = $("#renzhengid").val();
	domain = $("#domain").val();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"domain="+domain+"&userid="+userid,
			dataType:'text',
			success:function(str){
				if(str == "ok"){
					jAlert('绑定域名修改成功！','提示信息');
					editmodal(userid,2);
				}else{
					jAlert("修改失败，请稍后重试！",'提示信息');
				}
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				alert(errorThrown);
				//////////////////////////
				}	
			});
}

function renzhengeditmd5key(ajaxurl){
	userid = $("#renzhengid").val();
	md5key = $("#md5key").val();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"md5key="+md5key+"&userid="+userid,
			dataType:'text',
			success:function(str){
				if(str == "ok"){
					jAlert('商户密钥修改成功！','提示信息');
					editmodal(userid,2);
				}else{
					jAlert("修改失败，请稍后重试！",'提示信息');
				}
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				alert(errorThrown);
				//////////////////////////
				}	
			});
}

function editpassword(ajaxurl,type){
	userid = $("#passwordid").val();
	if(type == 0){
		fieldstr = "loginpassword";
		passwordstr = $("#loginpassword").val();
		confirmstr = "登录密码";
	}else{
		fieldstr = "paypassword";
		passwordstr = $("#paypassword").val();
		confirmstr = "支付密码";
	}
	jConfirm("您确认要修改"+confirmstr,"提示信息",function(e){
		if(e){
			if(passwordstr == ""){
				jAlert('密码不能为空！','提示信息');
			}else{
				$.ajax({
					type:'POST',
					url:ajaxurl,
					data:"passwordstr="+passwordstr+"&userid="+userid+"&fieldstr="+fieldstr,
					dataType:'text',
					success:function(str){
						if(str == "ok"){
							jAlert('修改成功！','提示信息');
							editmodal(userid,3);
						}else{
							jAlert("修改失败，请稍后重试！",'提示信息');
						}
						
						},
					error:function(XMLHttpRequest, textStatus, errorThrown) {  
						alert(errorThrown);
						//////////////////////////
						}	
					});
			}
		}
	});
}


function editbankcard(ajaxurl){
	id = $("#bankcardid").val();
   	bankname = $("#bankname").val();
   	bankfenname = $("#bankfenname").val();
   	bankzhiname = $("#bankzhiname").val();
   	banknumber = $("#banknumber").val();
   	bankfullname = $("#bankfullname").val();
   	sheng = $("#sheng").val();
   	shi = $("#shi").val();
   	datastr = "id="+id+"&bankname="+bankname+"&bankfenname="+bankfenname+"&bankzhiname="+bankzhiname+"&banknumber="+banknumber+"&bankfullname="+bankfullname+"&sheng="+sheng+"&shi="+shi;
   	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:datastr,
			dataType:'text',
			success:function(str){
				if(str == "ok"){
					jAlert('修改成功！','提示信息');
				}else{
					jAlert("修改失败，请稍后重试！",'提示信息');
				}
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				alert(errorThrown);
				//////////////////////////
				}	
			});
	
}

function suoding(ajaxurl,disabled){
	id = $("#bankcardid").val();
	userid = $("#bankcarduserid").val();
	if(disabled == 0){
		confirmstr = "已解除锁定银行卡修改！";
	}else{
		confirmstr = "已锁定银行卡修改！";
	}
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"id="+id+"&disabled="+disabled,
			dataType:'text',
			success:function(str){
				if(str == "ok"){
					jAlert(confirmstr,'提示信息');
					editmodal(userid,4);
				}else{
					jAlert("修改失败，请稍后重试！",'提示信息');
				}
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				alert(errorThrown);
				//////////////////////////
				}	
			});
}

function editdefaultpayapiuser(mythis,payapiid){
	ajaxurl = $(mythis).attr("ajaxurl");
	val = $(mythis).val();
	userid = $("#tongdaouserid").val();
	if(payapiid){
        $.ajax({
            type:'POST',
            url:ajaxurl,
            data:"val="+val+"&userid="+userid+"&payapiid="+payapiid,
            dataType:'text',
            success:function(str){
                if(str == "ok"){
                    jAlert("修改成功！",'提示信息');
                }else{
                    jAlert("修改失败，请稍后重试！",'提示信息');
                }

            },
        });

    }else{
        jAlert('没有可选项!');
	}

}
