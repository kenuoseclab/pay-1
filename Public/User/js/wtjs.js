// JavaScript Document
function check(){
	selecttongdao = $("#selecttongdao").val();
	if(selecttongdao == ""){
		jAlert("请择结算通！","道提示信息");
		$("#selecttongdao").focus();
		return false;
	}
	
	selectlx = $("#selectlx").val();
	if(selectlx == ""){
		jAlert("请选择结算类型！","道提示信息");
		$("#selectlx").focus();
		return false;
	}
	
	paypassword = $("#paypassword").val();
	if(paypassword == ""){
		jAlert("请输入支付密码！","道提示信息");
		$("#paypassword").focus();
		return false;
	}
	
	fieldsname = $("#fieldsname").val();
	if(fieldsname == ""){
		jAlert("上传Excel文件不能为空！","道提示信息");
		$("#fieldsname").focus();
		return false;
	}
}