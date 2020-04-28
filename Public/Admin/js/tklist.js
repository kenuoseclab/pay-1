// JavaScript Document
$(document).ready(function(e) {
	
    $("#checkAll").click(function() {
               $('input[name="subBox"]').attr("checked",this.checked); 
	});
	var $subBox = $("input[name='subBox']");
	$subBox.click(function(){
		$("#checkAll").attr("checked",$subBox.length == $("input[name='subBox']:checked").length ? true : false);
	});
    
	
	$("#ptshsearch").click(function(e) {
        memberid = $("#memberid").val();
		tongdao = $("#tongdao").val();
		bank = $("#bank").val();
		T = $("#T").val();
		status = $("#status").val();
		r = $("#r").val();
		//alert("?memberid="+memberid+"&tongdao="+tongdao+"&bank="+bank+"&T="+T+"&status="+status+"&r="+r);
		window.location.href="?memberid="+memberid+"&tongdao="+tongdao+"&bank="+bank+"&T="+T+"&status="+status+"&r="+r;
		
    });

});

function editstatus(id,status,ajaxurl){
	$.ajax({
			type:'POST',
			url:ajaxurl,
			data:"status="+status+"&id="+id,
			dataType:'text',
			success:function(str){
					if(str == "ok"){
						jAlert("状态修改成功！","提示信息",function(){
							window.location.reload();
							});
					}else{
						jAlert("修改失败，请稍后重试！","提示信息");
					}
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {  
			   // alert(XMLHttpRequest+"-----"+textStatus+"-------"+errorThrown);
				}	
			});
}