/**
 * ITKEE.CN
 */
$(function(){
    $(".level_two").hide();
    $(".level_three").hide();

    $(".open_level").click(function(){
        var id = $(this).attr("data-id");
        $(this).toggleClass('open','close');
        if($(this).hasClass("open")){
            $(this).html("&#xe625;");
        }else{
            $(this).html("&#xe623;");
        }


        $(".level_"+id).toggle();
        $(this).removeClass("open_level");
        $(this).addClass("close_level");
    });
});