// JavaScript Document
$(document).ready(function(e) {
	
	$("#dp5").datepicker();
	
    $("#yqmsz").click(function(e) {
		
		$(".pagination").hide();
		
		//$("#yqmsz").button('loading');
		//$("#yqmtj").button('loading');
		$("#addyqm").hide();
		/////////////////////////////////////////////////////////////////////////////
		ajaxurl = $("#inviteconfig").attr("ajaxurl");
		ajaxurl = $.trim(ajaxurl);
		ajaxurl = ajaxurl+"?a"+ Math.random();
		//alert(ajaxurl);
		$.ajax({
			type:'POST',
			url:ajaxurl,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				splitstr = str.split("|");
				$("#inviteconfigid").val(splitstr[0]);
				$("#invitezt").val(splitstr[1]);
				$("#invitetype2number").val(splitstr[2]);
				$("#invitetype2ff").val(splitstr[3]);
				$("#invitetype5number").val(splitstr[4]);
				$("#invitetype5ff").val(splitstr[5]);
				$("#invitetype6number").val(splitstr[6]);
				$("#invitetype6ff").val(splitstr[7]);
				///////////////////////////////////
				$("#szyqm").floatdiv("middle").show();
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				//alert("aaaaaaaa");	  
				//////////////////////////
				}	
			});
			
		////////////////////////////////////////////////////////////////////////////
        
    });
	
	
	$("#invitebc").click(function(e) {
		$("#invitebc").button('loading');
        ajaxurl = $(this).attr("ajaxurl");
		datastr = "id="+$("#inviteconfigid").val()+"&invitezt="+$("#invitezt").val()+"&invitetype2number="+$("#invitetype2number").val()+"&invitetype2ff="+$("#invitetype2ff").val()+"&invitetype5number="+$("#invitetype5number").val()+"&invitetype5ff="+$("#invitetype5ff").val()+"&invitetype6number="+$("#invitetype6number").val()+"&invitetype6ff="+$("#invitetype6ff").val();
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:datastr,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				if(str == "ok"){
					$("#tscontent").text("保存成功！");
				    $('#myModal').modal('show');
					$("#okdelbutton").hide();
				}else{
					$("#tscontent").text("数据没有更改，提交保存失败！");
				    $('#myModal').modal('show');
					$("#okdelbutton").hide();
				}
				$("#invitebc").button('reset');
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				//alert("aaaaaaaa");	  
				//////////////////////////
				}	
			});
    });
	
	
	$("#yqmtj").click(function(e) {
		
		$(".pagination").hide();
		
		//$("#yqmtj").button('loading');
		//$("#yqmsz").button('loading');
		$("#szyqm").hide();
		ajaxurl = $(this).attr("ajaxurl");
		/////////////////////////////////////////////////////////
		$.ajax({
			type:'POST',
			url:ajaxurl,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				//alert(str);
				$("#invitecode").val(str);
				$("#spaninvitecode").text(str);
				$("#addyqm").floatdiv("middle").show();
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				//alert("aaaaaaaa");	  
				//////////////////////////
				}	
			});
		/////////////////////////////////////////////////////////
		
		
        
    });
	
	$("#cxsc").click(function(e) {
		
		$("#cxsc").button("loading");
		
        ajaxurl = $("#yqmtj").attr("ajaxurl");
		/////////////////////////////////////////////////////////
		$.ajax({
			type:'POST',
			url:ajaxurl,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				//alert(str);
				$("#invitecode").val(str);
				$("#spaninvitecode").text(str);
				$("#cxsc").button("reset");
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				//alert("aaaaaaaa");	  
				//////////////////////////
				}	
			});
		/////////////////////////////////////////////////////////
    });
	
	
	$("#inviteadd").click(function(e) {
        $("#inviteadd").button('loading');
		 ajaxurl = $.trim($("#inviteaddtj").attr("ajaxurl"));
		 datastr = "invitecode="+$("#invitecode").val()+"&yxdatetime="+$("#yxdatetime").val()+"&regtype="+$("#regtype").val();
		// alert(ajaxurl+"------"+datastr);
		/////////////////////////////////////////////////////////
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:datastr,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				    if(str == "no"){
						$("#tscontent").text("您添加邀请码的上限已到或添加邀请码功能已关闭！");
					}else{
						$("#tscontent").text("添加成功！");
						}
					
				    $('#myModal').modal('show');
					$("#okdelbutton").hide();
				
				$("#cxsc").click();
				$("#inviteadd").button("reset");
				
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				//alert(XMLHttpRequest+"---------"+textStatus+"-------------"+errorThrown);
				$("#tscontent").text("添加成功！");
				    $('#myModal').modal('show');
					$("#okdelbutton").hide();
				
				$("#cxsc").click();
				$("#inviteadd").button("reset");
				//////////////////////////
				}	
			});
		/////////////////////////////////////////////////////////
    });
	

	$("#selectpage").change(function(e) {
		window.location.href = $(this).val();
    });	
	
	$("#search_search").click(function(e) {
        invitecode_search = $("#invitecodesearch").val();
		syusername_search = $("#syusernamesearch").val();
		regtype_search = $("#regtypesearch").val();
		zt_search = $("#ztsearch").val();
		getstr = "invitecodesearch="+invitecode_search+"&syusernamesearch="+syusername_search+"&regtypesearch="+regtype_search+"&ztsearch="+zt_search;
		window.location.href=$("#selectpage").val()+"?"+getstr;;
    });
	
	
	$("#okdelbutton").click(function(e) {
		//alert("sdggsad");
        datastr = "delid="+$("#delid").val();
		ajaxurl = $("#delid").attr("ajaxurl");
		//alert(ajaxurl);
		/////////////////////////////////////////////////////////
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:datastr,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				if(str == "ok"){
					$("#tscontent").text("删除成功！");
				   
				}else{
					$("#tscontent").text("删除成功！");
				}
				 $('#myModal').modal('show');
					$("#okdelbutton").hide();
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
				//alert("aaaaaaaa");	  
				//////////////////////////
				}	
			});
		/////////////////////////////////////////////////////////
    });
	
	
	
	$("#ptshsearch").click(function(){
		usernameidsearch = $("#usernameidsearch").val();
		statussearch = $("#statussearch").val();
		rzsearch = $("#rzsearch").val();
		sjusernamesearch = $("#sjusernamesearch").val();
		window.location.href = "?usernameidsearch="+usernameidsearch+"&statussearch="+statussearch+"&rzsearch="+rzsearch+"&sjusernamesearch="+sjusernamesearch
	});
	
	
	
});



function clearNoNum(obj){
		//先把非数字的都替换掉，除了数字和.
		obj.value = obj.value.replace(/[^\d.]/g,"");
		//必须保证第一个为数字而不是.
		obj.value = obj.value.replace(/^\./g,"");
		//保证只有出现一个.而没有多个.
		obj.value = obj.value.replace(/\.{2,}/g,".");
		//保证.只出现一次，而不能出现两次以上
		obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
	}

function delinvitecode(id){
	$("#tscontent").text("您确认要删除吗？删除后信息在回收站内，可以在回收站内恢复删除的信息！");
	$('#myModal').modal('show');
	$('#okdelbutton').show();
	$("#delid").val(id);
	}	
	
