/*
 * jQuery 模态提示组件
 */
( function($) {

    $.modal = function(options) {

        var plugin = this,
            $container = null,
            defaults = {
                title: "删除提示",
                content:"确定删除吗？",
                animation:false,
                tarid:false,
                isshowclosebtn:false,
                closecallback: function(){

                },surecallback: function(){

                },
                showcontent:false,
                afteropen: function(){}
            },
        // Shorthand variable so that we don't need to call
        // plugin.settings throughout the plugin code
            settings;
        plugin.publicMethods = {

        };
        plugin.init = function() {

            plugin.settings = settings = $.extend({}, defaults, options);
            if(plugin.settings.tarid){
                $(document.body).delegate(plugin.settings.tarid, 'click', function(){
                    plugin.curdata = $(this).data();
                    plugin.show();
                });
            }


        };
        plugin.show = function(){
        	if(plugin.settings.animation){
        
        		$container = $(tool.getAnimation());
        		 $container.appendTo($(document.body));
        	     return false;
        	};
        	
            if(plugin.settings.showcontent){

                $container = $(tool.getCommonModal(plugin.settings.title, plugin.settings.content));
                $container.appendTo($(document.body));
                initEvents_two();
            }else{
                $container = $(tool.getModalTem(plugin.settings.title, plugin.settings.content, plugin.settings.tarid, plugin.settings.isshowclosebtn));
                $container.appendTo($(document.body));
                initEvents();
                plugin.settings.afteropen();
                $(tool.getBgTem()).appendTo($(document.body));
                $container.show().addClass('in');
                $(document.body).addClass('modal-open');
            
			};
        };
        plugin.hide = function(){
        	if(plugin.settings.animation==1){
        		 
							$container = $(tool.getAnimation());
					 		$container.appendTo($(document.body));
					 		initEvents_animate();
					 		 return false;
				 
				};
				if(plugin.settings.animation==2){       			
							$container = $(tool.getAnimation());
					 		$container.appendTo($(document.body));
			
					 		 return false;
				 
				};
            $container.removeClass('in');
            $(document.body).removeClass('modal-open');
            $('.modal-backdrop').remove();
           	$container.remove();
        };
        var initEvents = function(){
            $container.delegate('#btn-close', 'click', function(){
                plugin.settings.closecallback();
                plugin.hide();
            }).delegate('#btn-ensure', 'click', function(){
                plugin.settings.surecallback();
                plugin.hide();
            }).delegate('#imgclose', 'click', function(){
                plugin.hide();
            });
        };
        var initEvents_two = function(){
            $container.animate({'opacity':0},3000,function(){$('.zbb-not-mask').css('display','none')});
        };
		 var initEvents_animate = function(){
            $container.animate({'opacity':0},4000,function(){$('.zbb-not-mask').css('display','none')});
            $container.animate({'opacity':0},4000,function(){$('.animation-body').css('display','none')});
    
                return false;
        };
        var tool = {
        	 getAnimation: function(argument) {
        	 	var str = "";
              str ='<div class="zbb-not-mask">'+
              ' <div class="animation-body" style=" background-image: url(style/animation-body.png);  background-repeat: no-repeat;">'+
			    ' 	<p>'+
			    ' 		<img  class="animationimg1" src="image/logo-white.png"/>'+
			    ' 	</p>'+
			    ' 	<p>'+
			    ' 		<img class="animationimg2" src="image/load3.gif"/>'+
	    		' 	</p>'+
	    		' </div>'+
                ' </div>';
                   return str;
              
            },
            getBgTem: function(argument) {
                return '<div class="modal-backdrop fade in"></div>';
            },
            getCommonModal: function(title, content){
                var str = "";
                str =  '<div class="zbb-not-mask"><div class="ymm-not-opened">'+content+'</div></div>';
                              
                return str;
            },
            getModalTem: function(title, noticeContent, id,isshowclosebtn){
                var str = "",
                    btntem = '';
                if(isshowclosebtn){
                    btntem += '<button type="button" class="common-btn-cancel" data-dismiss="modal" id="btn-close">取消</button>';
                }
                str =   '<div class="modal fade" id="modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">'+
                    '<div class="modal-dialog dzz-modal-dialog">'+
                    '<div class="modal-content dzz-modal-content">'+
                    '<div class="modal-header dzz-modal-header">'+
                    '<button type="button" class="close zbb_close" data-dismiss="modal" id="imgclose" aria-label="Close"> <span aria-hidden="true"><img src="image/close_btn.png" alt=""/></span></button>'+
                        //  '<button type="button" class="close" id="btn-close">×</button>'+
                        //'<h4 class="modal-title">'+title+'</h4>'+
                    '<p class="h2 text-center">'+title+'</p>'+
                    '</div>'+
                    '<div class="modal-body dzz-modal-body text-center">'+noticeContent+
                    '</div>'+
                    '<div class="modal-footer dzz-modal-footer">'+

                    '<button type="button" class="common-btn-big" data-dismiss="modal" id="btn-ensure">确认</button>'+
                    btntem+
                    '</div>'+
                    '</div>'+
                    '</div>'+
                    '</div>';
                return str;
            }
        };
        // =============================================================
        // Private functions
        // =============================================================

        plugin.init();
        return plugin;
    };
    // $.fn.modal = function(options) {
    //   return new $.modal(this, options);
    // };

})(jQuery);
