function Navigation(controller, title, config){
	this.opts = $.extend(true, {
        speed: 500
    }, config);
    this.controller = controller; // 控制条
    this.title = title; // 所有的标题
    this.win = $(window); //window
    this.list = [];  //用来保存控制条所有的元素
    this.arr = []; // 用来保存每个标题距离顶部的距离
    this.curIndex = 0; 
    this.init();
}

Navigation.prototype = {
	init: function(){
		this.list = this.controller.find("li");
		this.topArr();
		this.scrollEvent();
		this.clickEvent();
	},
	topArr: function(){
		for(var i = 0; i<this.title.length; i++){
			this.arr.push(this.title.eq(i).offset().top - 30);
		}
	},
	scrollEvent: function(){
		var $this = this;
		var controllerTop = this.controller.offset().top -50 ;
		this.win.on('scroll', function(){
			var scrollTop = $(window).scrollTop();
			if(scrollTop > controllerTop){
				$this.controller.addClass("fixed");
			}else{
				$this.controller.removeClass("fixed");
			}
			$this.checkPos(scrollTop);
		})
	},
	checkPos: function(scroTop){
		for (var i = 0; i < this.arr.length; i++) {
            if (scroTop >= this.arr[i]) {
                this.list.removeClass("active");
                this.list.eq(i).addClass("active");
            }else if(scroTop < this.arr[0]){
                this.list.removeClass("active");
                this.list.eq(0).addClass("active");
            }
        }
	},
	clickEvent: function(){
		var $this = this;
		this.controller.on('click', 'li', function(){
			var index = $(this).index();
			$this.curIndex = index;
			$("html,body").animate({
                scrollTop: $this.arr[index]
            }, $this.opts.speed);
		})
	}
}

new Navigation($(".controller"), $(".item-title"));