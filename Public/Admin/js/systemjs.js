// JavaScript Document
$(document).ready(function(e) {
	 $("#csfsyj").click(function(e) {
            var datastr = "";
			var btn = $(this);
			btn.button('loading');
			$("#cs_text").attr("disabled");
			
			cs_text = $("#cs_text").val();

			urlstr = $("#cs_text").attr("url");
		
			datastr = "cs_text="+cs_text;
			//alert(datastr);
		$.ajax({
			type:'POST',
			url:urlstr,
			data:datastr,
			dataType:'text',
			success:function(str){
				///////////////////////////////////
				$("#cs_text").removeAttr("disabled");
				btn.button('reset');
				$("#tscontent").text(str);
				$('#myModal').modal('show');
				///////////////////////////////////
				},
			error:function(XMLHttpRequest, textStatus, errorThrown) {
					   $("#cs_text").removeAttr("disabled");
				       btn.button('reset');
					   $("#tscontent").text("请不要非法提交！");
					   $('#myModal').modal('show');
				//////////////////////////
				}	
			});
			
    });
});