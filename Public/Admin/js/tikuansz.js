// JavaScript Document
$(document).ready(function(e) {
	
	$("#tkconfigbutton").click(function(e) {
        var tkzxmoney =$("#tkzxmoney").val(),
			tkzdmoney=$("#tkzdmoney").val(),
			dayzdmoney=$("#dayzdmoney").val(),
			dayzdnum=$("#dayzdnum").val(),
			t1zt=$("#t1zt").val(),
			t0zt=$("#t0zt").val(),
			gmt0=$("#gmt0").val(),
			tktype=$("#tktype").val(),
			tkzt=$("#tkzt").val(),
			id= parseInt($("#tkconfigid").val()),
			sxfrate = $('#sxfrate').val(),
			sxffixed = $('#sxffixed').val();
        var ajaxurl = $("#tikuanconfigform").attr("action");
               $.ajax({
					type:'POST',
					url:ajaxurl,
					data:"tkzxmoney="+tkzxmoney+"&tkzdmoney="+tkzdmoney+"&dayzdmoney="+dayzdmoney+"&dayzdnum="+dayzdnum+"&t1zt="+t1zt+"&t0zt="+t0zt+"&gmt0="+gmt0+"&tktype="+tktype+"&tkzt="+tkzt+"&id="+id+"&sxfrate="+sxfrate+"&sxffixed="+sxffixed,
					dataType:'text',
					success:function(str){
							jAlert(str,'提示信息');
					}
				});
    });
	
    $("#tksjszbutton").click(function(e) {
		ajaxurl = $("#tksjsz > form").attr("action");
               $.ajax({
					type:'POST',
					url:ajaxurl,
					data:"baiks="+$("#baiks").val()+"&baijs="+$("#baijs").val()+"&wanks="+$("#wanks").val()+"&wanjs="+$("#wanjs").val(),
					dataType:'text',
					success:function(str){
							jAlert(str,'提示信息');
						},
					error:function(XMLHttpRequest, textStatus, errorThrown) {
						}	
				});
    });
	
	
	
	$("#buttonpcjjr").click(function(e) {
        val = $("#pcjjrval").val();
		if(val == ""){
			jAlert("添加的日期不能为空！","提示信息");
		}else{
			/////////////////////////////////////////////////////////////////////////
			ajaxurl = $(this).attr("ajaxurl");
			 $.ajax({
					type:'POST',
					url:ajaxurl,
					data:"datetime="+val,
					dataType:'text',
					success:function(str){
							switch(str){
								case "a":
								jAlert("添加的日期不能为空！","提示信息");
								break;
								case "b":
								jAlert("添加的日期已存在","提示信息");
								break;
								default:
								jAlert("添加成功！","提示信息");
					            $("#pcjjr").prepend('<div style="color:#F93;">'+val+'</div>');
					            $("#pcjjrval").val("");
								break;
								}
						},
					error:function(XMLHttpRequest, textStatus, errorThrown) {
						}	
				});
			////////////////////////////////////////////////////////////////////////
			
		}
    });
	
	
	$("#buttontjjjr").click(function(e) {
        val = $("#tjjjrval").val();
		shuoming = $("#shuoming").val();
		if(val == "" || shuoming == ""){
			jAlert("添加的日期或假日说明不能为空！","提示信息");
		}else{
			/////////////////////////////////////////////////////////////////////////
			ajaxurl = $(this).attr("ajaxurl");
			 $.ajax({
					type:'POST',
					url:ajaxurl,
					data:"datetime="+val+"&shuoming="+shuoming,
					dataType:'text',
					success:function(str){
							switch(str){
								case "a":
								jAlert("添加的日期不能为空！","提示信息");
								break;
								case "b":
								jAlert("添加的日期已存在","提示信息");
								break;
								default:
								jAlert("添加成功！","提示信息");
					            $("#tjjjr").prepend('<div style="color:#F93;">'+val+'(<span>'+shuoming+'</span>)</div>');
					            $("#tjjjrval").val("");
								 $("#shuoming").val("");
								break;
								}
						},
					error:function(XMLHttpRequest, textStatus, errorThrown) {
						}	
				});
			////////////////////////////////////////////////////////////////////////
			
		}
    });
	
	
});







function pcjjrdel(id,mythis){
	ajaxurl = $("#pcjjrdelurl").val();
	$.ajax({
		type:'POST',
		url:ajaxurl,
		data:"id="+id,
		dataType:'text',
		success:function(str){
				if(str == "ok"){
					jAlert("删除成功！",'提示信息');
				    $(mythis).parent("div").remove();
				}else{
					jAlert("删除失败，你稍后重试！",'提示信息');
				}
			},
		error:function(XMLHttpRequest, textStatus, errorThrown) {
			}	
	});
}

function tjjjrdel(id,mythis){
	ajaxurl = $("#tjjjrdelurl").val();
	$.ajax({
		type:'POST',
		url:ajaxurl,
		data:"id="+id,
		dataType:'text',
		success:function(str){
				if(str == "ok"){
					jAlert("删除成功！",'提示信息');
				    $(mythis).parent("div").remove();
				}else{
					jAlert("删除失败，你稍后重试！",'提示信息');
				}
			},
		error:function(XMLHttpRequest, textStatus, errorThrown) {
			}	
	});
}