// define('modules/widget/indexSection/sectioniphone', function(require, exports, module) {

  "use strict";
  
  var $ = require('components/jquery/jquery');
  
  function _classCallCheck(t, e) {
  	if (!(t instanceof e))
  		throw new TypeError("Cannot call a class as a function")
  }
  
  function PolygonFactory(t) {
  	this.width = 200,
  		this.height = 113,
  		this.rowCount = t.rowCount || 5,
  		this.colCount = t.colCount || 4,
  		this.gutterX = 100,
  		this.gutterY = 120,
  		this.startX = random(-100, 0),
  		this.leftX = -200,
  		this.objects = [],
  		this.container = t.container
  }
  
  function random(t, e) {
  	return t + (e - t) * Math.random()
  }
  
  function slideScene() {
  	var t = $(".line-boxes"),
  		el_heroSceneText = $(".hero-scene-text"),
  		n = $(".scene-slider-wrap .iphone-hand-bg"),
  
  		e = $("#iphone6 .scene-viewes"),
  		r = $("#iphone6"),
  		o = $("#iphone6 .topbar"),
  		s = $("#iphone6 .camera"),
  		a = $("#iphone6 .speaker"),
  		l = $("#iphone6 .speaker-before"),
  		u = $("#iphone6 .home"),
  
  		timelineMax = new TimelineMax({
  			yoyo: !1,
  			repeat: -1
  		});
      //内屏
      var _x=220;
      var _neip=219;
  
      var _y=296;
      var _q=1372;
  
  
  
      //292+半个间距
      var _waip=252;
  
  
      var _t=336;
      var _z=351;
  
      //第一张
  	timelineMax.add([
  		TweenLite.to(t, 1, {
  			x: -(_q+_waip*1),
  			ease: Power3.easeInOut
  		}),
  		TweenLite.to(e, 1, {
  			x: -(_x+_neip*0),
  			ease: Power3.easeInOut
  		}),
  		TweenLite.to(el_heroSceneText, 1, {
  			y: -(_z),
  			ease: Power3.easeInOut
  		})], "+=2"),
  
          //第二张
  		timelineMax.add([
  			TweenLite.to(t, 1, {
  			x: -(_q+_waip*2),
  			ease: Power3.easeInOut
  		}),
  		TweenLite.to(e, 1, {
  			x: -(_x+_neip*1),
  			ease: Power3.easeInOut
  		}),
  		TweenLite.to(el_heroSceneText, 1, {
  			y: -(_z*2),
  			ease: Power3.easeInOut
  		})], "+=2"),
  
          //第三张
  		timelineMax.add([
  			TweenLite.to(t, 1, {
  			x: -(_q+_waip*3),
  			ease: Power3.easeInOut
  		}),
  		TweenLite.to(e, 1, {
  			x: -(_x+_neip*2),
  			ease: Power3.easeInOut
  		}),
  		TweenLite.to(el_heroSceneText, 1, {
  			y: -(_z*3),
  			ease: Power3.easeInOut
  		})], "+=2"),
  
          //第四张
  		timelineMax.add([
  			TweenLite.to(t, 1, {
  			x: -(_q+_waip*4),
  			ease: Power3.easeInOut
  		}),
  		TweenLite.to(e, 1, {
  			x: -(_x+_neip*3),
  			ease: Power3.easeInOut
  		}),
  		TweenLite.to(el_heroSceneText, 1, {
  			y: -(_z*4),
  			ease: Power3.easeInOut
  		})], "+=2"),
  
  
          //第一张
          timelineMax.add([
              TweenLite.to(t, 1, {
                  x: -(_q+_waip*5),
                  ease: Power3.easeInOut
              }),
              TweenLite.to(e, 1, {
                  x: -(_x+_neip*4),
                  ease: Power3.easeInOut
              }),
              TweenLite.to(el_heroSceneText, 1, {
                  y: -(_z*5),
                  ease: Power3.easeInOut
              })], "+=2"),
  
          //第五章
          timelineMax.to(n, .5, {
              scale: 1,
              opacity: 1
          }, "-=1.2"),
  
  		timelineMax.timeScale(1)
  }
  
  function resizeFrame() {
  
  	var t = $(".line-box-wrap").width(),
  		e = $(".line-boxes"),
  		i = 295,
  		n = 246,
  		r = Math.ceil(t / 2),
  		o = i - r,
  		s = -(n + o);
  
  	TweenLite.to(e, .1, {
  			x: 400,
  			ease: Power3.easeInOut
  		}),
  		console.log("currentLeftOfLineBox:", s)
  }
  
  $(document).ready(function() {
  
  			slideScene();
  
  });
  

// });
