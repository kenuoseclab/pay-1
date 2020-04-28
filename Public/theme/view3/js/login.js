$(document).ready(function () {
    
    document.onkeydown=keyDownSearch; 
    function keyDownSearch(e) {  
        // 兼容FF和IE和Opera  
        var theEvent = e || window.event;  
        var code = theEvent.keyCode || theEvent.which || theEvent.charCode;  
        if (code == 13) {   
            $('#subbun').click();
        }  
    }      
    
    var allInputs = $(':input');
    allInputs.on('focusin', function () {
        $(this).prev('.label').addClass('show');
    });
    allInputs.on('focusout', function () {
        $(this).prev('.label').removeClass('show');
    });
    $('#flash_error').hide();
    $('#subbun').click(function () {
        $.post(login_url, $('#login-form').serialize(), function (d) {
            if (d.status) {
                location.href = d.url;
            } else {
                $('#flash_error').html(d.info);
                $('#flash_error').fadeIn(300);
            }
        }, 'json');
    });
});