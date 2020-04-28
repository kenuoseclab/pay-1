$(document).ready(function(e){
	//实例化编辑器
   var ue = UE.getEditor('container',{
   		autoHeight: true
   });
   
   $(".jsruserid").click(function(e){
   		if($(this).attr("jsuserid") == 0){
   			$(".addjsf").show();
   		}
   		$(this).remove();
   });
   
   $("#tjjsf").click(function(e){
   		userid = $("#jsr_userid").val();
   		if(userid == 0){
   			    $(".jsruserid").remove();
   				$('<button type="button" class="jsruserid" jsuserid="0">全部&nbsp;<span class="glyphicon glyphicon-remove"></span></button>').appendTo("#jsr");
  				$(".jsruserid").click(function(e){
			   		if($(this).attr("jsuserid") == 0){
			   			$(".addjsf").show();
			   		}
			   		$(this).remove();
			   });
			   $("#jsr_userid").val("");
			   $(".addjsf").hide();
			   return;
   		}
   		$.ajax({
   		  		type:"POST",
   		  		url:$("#pduserid").val(),
   		  		data:"userid="+userid,
   		  		dataType:"text",
   		  		success:function(str){
   		  			if(str == "no"){
   		  				jAlert("您输入的商户编号不存在！","提示信息");
   		  			}else{
   		  				$('<button type="button" class="jsruserid" jsuserid="'+(userid-10000)+'">'+userid+'&nbsp;<span class="glyphicon glyphicon-remove"></span></button>').appendTo("#jsr");
   		  				$(".jsruserid").click(function(e){
					   		if($(this).attr("jsuserid") == 0){
					   			$(".addjsf").show();
					   		}
					   		$(this).remove();
					   });
					   $("#jsr_userid").val("");
   		  			}
   		  		},
   		  		error:function(XMLHttpRequest, textStatus, errorThrown) {
   									
   			    }	
   		  		
   		});
   });
});	

function check(){
	title = $("#title").val();
	if(!title){
		jAlert("标题不能为空！","提示信息",function(){
			$("#title").focus();
		});
		return false;
	}else{
		articleclassid = $("#articleclassid").val();
		if(articleclassid == ""){
			jAlert("请选择文章所属栏目","提示信息",function(){
				$("#articleclassid").focus();
			});
		    return false;
		}else{
			if($(".jsruserid").size() <=0){
				jAlert("接收人不能为空，如果全部接收请输入0","提示信息");
			}else{
				content = UE.getEditor("container").getContent();
				
				if(!content){
					jAlert("文章内容不能为空！","提示信息",function(){
						UE.getEditor("container").focus();
					});
			        return false;
				}else{
					
					status = $("#status").val();
					jieshouuserlist = "";
					$(".jsruserid").each(function(index, element){
						jieshouuserlist = jieshouuserlist+$(this).attr("jsuserid")+"|";
					});
					$("#jieshouuserlist").val(jieshouuserlist);
				}
			}
			
		}
	}
}

function checkedit(){
	title = $("#title").val();
	if(!title){
		jAlert("标题不能为空！","提示信息",function(){
			$("#title").focus();
		});
		return false;
	}else{
		articleclassid = $("#articleclassid").val();
		if(articleclassid == ""){
			jAlert("请选择文章所属栏目","提示信息",function(){
				$("#articleclassid").focus();
			});
		    return false;
		}else{
			if($(".jsruserid").size() <=0){
				jAlert("接收人不能为空，如果全部接收请输入0","提示信息");
			}else{
				content = UE.getEditor("container").getContent();
				if(!content){
					jAlert("文章内容不能为空！","提示信息",function(){
						UE.getEditor("container").focus();
					});
			        return false;
				}else{
					status = $("#status").val();
					jieshouuserlist = "";
					$(".jsruserid").each(function(index, element){
						jieshouuserlist = jieshouuserlist+$(this).attr("jsuserid")+"|";
					});
				$("#jieshouuserlist").val(jieshouuserlist);
					
				}
			}
			
		}
	}
	
}