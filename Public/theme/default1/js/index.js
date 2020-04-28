// define('modules/ui/index', function(require, exports, module) {

  /**
   * 标记 css 依赖, 需要用到 loading 图标。
   * @require modules/css/test_index_requireFromJS.scss
   */
  var $ = require('components/jquery/jquery');
  require('modules/lib/ysbbase');
  require('modules/app/jquery.scroll');
  require('modules/app/bootstrap-datepicker');
  
  $(document).ready(function() {
  
      // Twitter Bootstrap 3 carousel plugin
      $("#myCarousel").carousel();
  
      $('.beautify').hover(function() {
          console.log('dddd');
          $(this).addClass('active').siblings().removeClass('active');
      }, function() {
          $(this).siblings().removeClass('active');
      });
  
      $("#scrollDiv").Scroll({
          line: 1,
          speed: 500,
          timer: 3000,
          up: "but_up",
          down: "but_down"
      });
  
      // console.log('from index.js');
  
  // });
  //
  // var modal = module.exports = function(url, opt) {
  // };
  
  
  
  
  
  
  
  
  
  
  
  
  

});
