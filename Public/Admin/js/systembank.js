$(document).ready(function(e) {
  	$(".modalgb").click(function(e) {
        window.location.reload();
    });
});
function check(){
	if($("#bankname").val() == ""){
		jAlert('银行名称不能为空！','提示信息');
		return false;
	}
	if($("#bankcode").val() == ""){
		jAlert('银行编码不能为空！','提示信息');
		return false;
	}
	if($("#bankimages").val() == ""){
		jAlert('银行图片不能为空！','提示信息');
		return false;
	}
}

function editcheck(){
	if($("#bankname").val() == ""){
		jAlert('银行名称不能为空！','提示信息');
		return false;
	}
	if($("#bankcode").val() == ""){
		jAlert('银行编码不能为空！','提示信息');
		return false;
	}
	
}

function edit(id){
	editurl = $("#iframesystembank").attr("editurl");
	editurl = editurl+"?id="+id;
	$("#iframesystembank").attr("src",editurl);
	$('#myModal').modal('show');
}

function del(ajaxurl,id){
	jConfirm("您确认要删除吗？","提示信息",function(r){
		if(r){
			jConfirm("删除银行后相应的银行信息都会删，您确认要删除吗？","提示信息",function(r){
					if(r){
						$.ajax({
							type:'POST',
							url:ajaxurl,
							data:"id="+id,
							dataType:'text',
							success:function(str){
								if(str == "ok"){
									jAlert('删除成功！','提示信息',function(){
										window.location.reload();
										});
								}else{
									jAlert('删除失败，请稍后重试！','提示信息',function(){
										window.location.reload();
										});
									}
								},
							error:function(XMLHttpRequest, textStatus, errorThrown) {
								}	
							});
						}
				});
			}
		});
}