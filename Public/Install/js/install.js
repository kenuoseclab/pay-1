// JavaScript Document
$(document).ready(function(e) {
    
	$("#install_top_div").floatdiv("lefttop");
	
	$("#install_bottom_div").floatdiv("leftbottom");
	
	$("#install_top div").addClass("install_top_title_w");
	
	//$("#install_top div").click(function(e) {
//        $(this).addClass("install_top_title_x");
//    });
	
	$("#install_top div").mouseover(function(e) {
        $(this).addClass("install_top_title_xx");
    });
	
	$("#install_top div").mouseout(function(e) {
        $(this).removeClass("install_top_title_xx");
    });
	
	$("#tyazyx").click(function(e) {
        window.location.href = $("#tyazyx").attr("url");
    });
	
});