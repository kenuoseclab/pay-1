// JavaScript Document
function editshow(id){
	$("#articetitle").html('<img src="Public/images/loading.gif">');
	$("#articecontent").html('<img src="Public/images/loading.gif">');
	$('#myModal').modal('show');
	ajaxurl = $("#myModal").attr("ajaxurl")+"?a="+Math.random();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"id="+id,
			dataType:'json',
			success:function(obj){
				
					$("#articetitle").html(obj["title"]);
					$("#articecontent").html(obj["content"]);
	            
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
}

function browsenum(id){
	$("#articetitle").html('<img src="Public/images/loading.gif">');
	$("#articecontent").html('<img src="Public/images/loading.gif">');
	$('#myModal').modal('show');
	ajaxurl = $("#myModal").attr("browsenumurl")+"?a="+Math.random();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"id="+id,
			dataType:'json',
			success:function(obj){
				
					$("#articetitle").html("浏览记录(最近20条)");
					var str = '<table class="table">';
				    str = str+"<tr><td>用户ID</td><td>浏览时间</td></tr>"
					for(var o in obj){
						str = str+"<tr>";
						str = str+"<td>"+(parseInt(obj[o]["userid"])+10000)+"</td>";
						str = str+"<td>"+(obj[o]["datetime"])+"</td>";
						str = str+"</tr>";
					}
					str = str+'</table>';
					$("#articecontent").html(str);
	            
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				alert(errorThrown);
				//////////////////////////
				}	
			});
}

function deldel(id,ajaxurl){
	if(confirm("确认要删除吗？")){
		$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"id="+id,
			dataType:'text',
			success:function(obj){
					jAlert("删除成功！","提款信息",function(e){
						window.location.reload();
					});
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				jAlert("删除成功！","提款信息",function(e){
						window.location.reload();
					});
				//////////////////////////
				}	
			});
	}
}

function edit(id){
	$('#myModal').modal('show');
	ajaxurl = $("#myModal").attr("ajaxurl")+"?a="+Math.random();
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"id="+id,
			dataType:'json',
			success:function(obj){
					$("#dealcontent table tr td span").each(function(index, element) {
						$(this).html(obj[index]);
					});
					$("#orderidModal").html(obj[0]);
	            
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
				
				//////////////////////////
				}	
			});
}
