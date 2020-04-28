$(document).ready(function(e){
	$(".zy-searchbutton").click(function(e){
		str = "";
		$(".zy-searchstr").each(function(index,element){
			str = str+$(this).attr("name")+"="+$(this).val()+"&";
		});
		str = str.substr(0,str.length-1);
		window.location.href = "?"+str;
	});
	
	$("#zjbdjldownload").click(function(e) {
        str = "";
		$(".zy-searchstr").each(function(index,element){
			str = str+$(this).attr("name")+"="+$(this).val()+"&";
		});
		str = str.substr(0,str.length-1);
		url = $(this).attr("url");
		window.open(url+"?"+str);
		//window.location.href = "?"+str;
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
