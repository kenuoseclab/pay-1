define('modules/widget/header/header', function(require, exports, module) {

  var $ = require("components/jquery/jquery");
  require('modules/lib/ysbbase');
  console.log('header.js');
  
  $(document).ready(function() {
  	
  	$("li.my").mouseover(function() {
  		$(".rvm").show();
  	});
  	
  	$("li.my").mouseout(function() {
  		$(".rvm").hide();
  	});
  	
  });

});
