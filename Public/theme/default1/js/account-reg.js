define('modules/ui/account-reg', function(require, exports, module) {

  var $ = require('components/jquery/jquery');
  
  $(document).ready(function() {
  
  	var username = document.getElementById('username');
  	var validation = document.getElementById('validation');
  	var btn = document.getElementById('btnSubmit');
  	var b = document.getElementById("setting");
  	var b1 = document.getElementById("setting1");
  	var c;
  	var u;
  	var reg = /^[1-9]\d*$|^0$/; //纯数字
  	var user_zeng = /^1[3|4|5|7|8]\d{9}$/; //手机号
  	var emali_zeng = /^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/; //邮箱
  
  	username.oninput = function() {
  		var user_value = username.value;
  		if(reg.test(user_value)) {
  			if(!user_zeng.test(username.value)) {
  				u = false;
  			} else {
  				u = true;
  			}
  		} else if(user_value.indexOf("@") > -1) {
  			if(!emali_zeng.test(username.value)) {
  				u = false;
  			} else {
  				u = true;
  			}
  		} else {
  			u = false;
  		}
  
  		if(!u) {
  			b.innerHTML = '<b></b>账户名不正确';
  			btn.style.backgroundColor = "#d5d5d5";
  			btn.type = "button";
  			b.style.display = "block";
  		} else {
  			b.innerHTML = '';
  			b.style.display = "none";
  			if(u && c) {
  				btn.style.backgroundColor = "#19A8E8";
  				btn.type = "submit";
  
  			}
  		}
  	};
  
  	validation.oninput = function() {
  		if(validation.value == "") {
  			c = false;
  			b1.innerHTML = '<b></b>验证码不能为空';
  			btn.style.backgroundColor = "#d5d5d5";
  			btn.type = "button";
  			b1.style.display = "block";
  
  		} else {
  			c = true;
  			b1.innerHTML = '';
  			b1.style.display = "none";
  			if(u && c) {
  				btn.style.backgroundColor = "#19A8E8";
  				btn.type = "submit";
  
  			}
  
  		};
  
  	};
  
  	$("#btnSubmit").bind("click", function() {
  
  		if($("#username").val() == "") {
  			$("#setting").html("<b></b>手机号码或者邮箱填写错误或者已经被注册");
  			$("#setting").show();
  		} else if($("#validation").val() == "") {
  			$("#setting1").html("<b></b>验证码不能为空");
  			$("#setting1").show();
  		} else {
  			$("#setting").html("");
  			$("#setting").hide();
  		}
  
  		//dynamic open the dialog
  		//$('#myModal').modal('toggle');
  		//$('#myModal').modal('show');
  		//$('#myModal').modal('hide')
  		$("#myModal").modal({
  			show: true
  		});
  
  	});
  
  	$('#myModal').on('hidden.bs.modal', function(e) {
  		console.log('myModal dialog');
  	})
  
  });

});
