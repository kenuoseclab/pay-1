/**
 * Created by liudachu on 18/4/17.
 */

//D0控制开关判断
if(OPEN_D0 != 'True'){
    $('.dt-dzero-show,.dt-dzero-choose').remove();
}


function payShow(_this,showbox){
    var showBox=$(showbox);
    if(_this.is(':checked')){
        showBox.show();
        showBox.find('input[type="text"]').removeAttr('disabled');
    }else{
        showBox.hide();
        showBox.find('input[type="text"]').attr('disabled',true);
    }
}

$('#payTypeWechat').click(function(){
    payShow($(this),'.pay-wechat');
});
$('#payTypeAlipay').click(function(){
    payShow($(this),'.pay-alipay');
});

//添加cookie方法
jQuery.cookie=function(name,value,options){
    if(typeof value!='undefined'){
        options=options||{};
        if(value===null){
            value='';
            options.expires=-1;
        }
        var expires='';
        if(options.expires&&(typeof options.expires=='number'||options.expires.toUTCString)){
             var date;
            if(typeof options.expires=='number'){
                date=new Date();
                date.setTime(date.getTime()+(options.expires * 24 * 60 * 60 * 1000));
             }else{
                date=options.expires;
            }
            expires=';expires='+date.toUTCString();
         }
        var path=options.path?';path='+options.path:'';
        var domain=options.domain?';domain='+options.domain:'';
        var secure=options.secure?';secure':'';
        document.cookie=[name,'=',encodeURIComponent(value),expires,path,domain,secure].join('');
     }else{
        var cookieValue=null;
        if(document.cookie&&document.cookie!=''){
            var cookies=document.cookie.split(';');
            for(var i=0;i<cookies.length;i++){
                var cookie=jQuery.trim(cookies[i]);
                if(cookie.substring(0,name.length+1)==(name+'=')){
                    cookieValue=decodeURIComponent(cookie.substring(name.length+1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};


//修改页面判断D0是否有值
if($('#bankRatePayWechat').val() == ''){
    $.cookie('recordWx','d1');
}else if($('#bankRatePayWechat').val() > 0){
    $.cookie('recordWx','d0');
}
if($('#bankRatePayAlipay').val() == ''){
    $.cookie('recordAlipay','d1');
}else if($('#bankRatePayAlipay').val() > 0){
    $.cookie('recordAlipay','d0');
}


//判断费率选择
if($.cookie('recordWx') == 'd0'){
    $('.pay-wechat .dt-dzero-show').show();
    $('.pay-wechat .dt-done-show').hide();
    $('input[name="mch_wx_pay"]').eq(1).attr('checked',true);

}else{
    $('.pay-wechat .dt-dzero-show').hide();
    $('.pay-wechat .dt-done-show').show();
    $('input[name="mch_wx_pay"]').eq(0).attr('checked',true);
}

if($.cookie('recordAlipay') == 'd0'){
    $('.pay-alipay .dt-dzero-show').show();
    $('.pay-alipay .dt-done-show').hide();
    $('input[name="mch_ali_pay"]').eq(1).attr('checked',true);
}else{
    $('.pay-alipay .dt-dzero-show').hide();
    $('.pay-alipay .dt-done-show').show();
    $('input[name="mch_ali_pay"]').eq(0).attr('checked',true);
}

//判断费率输入框是否有值
$('.pay-wechat tr').each(function(){
    if($(this).find('.dt-done-show input[type="text"]').val() != '' && $(this).find('.dt-done-show input[type="text"]').val() != undefined){
        $(this).find('.dt-done-show input[type="checkbox"]').attr('checked',true);
        $(this).find('.dt-done-show input[type="text"]').removeAttr('readonly');
        $('#payTypeWechat').attr('checked',true);
        $('.pay-wechat').show();
    }
    if($(this).find('.dt-dzero-show input[type="text"]').val() != '' && $(this).find('.dt-dzero-show input[type="text"]').val() != undefined){
        $(this).find('.dt-dzero-show input[type="checkbox"]').attr('checked',true);
        $(this).find('.dt-dzero-show input[type="text"]').removeAttr('readonly');
        $('#payTypeWechat').attr('checked',true);
        $('.pay-wechat').show();
    }
});
$('.pay-alipay tr').each(function(){
    if($(this).find('.dt-done-show input[type="text"]').val() != '' && $(this).find('.dt-done-show input[type="text"]').val() != undefined){
        $(this).find('.dt-done-show input[type="checkbox"]').attr('checked',true);
        $(this).find('.dt-done-show input[type="text"]').removeAttr('readonly');
        $('#payTypeAlipay').attr('checked',true);
        $('.pay-alipay').show();
    }
    if($(this).find('.dt-dzero-show input[type="text"]').val() != '' && $(this).find('.dt-dzero-show input[type="text"]').val() != undefined){
        $(this).find('.dt-dzero-show input[type="checkbox"]').attr('checked',true);
        $(this).find('.dt-dzero-show input[type="text"]').removeAttr('readonly');
        $('#payTypeAlipay').attr('checked',true);
        $('.pay-alipay').show();
    }
});


//D1,D0复选框选择 可输入判断
$('.dt-dzero-show input[type="checkbox"]').change(function () {
    if($(this).is(':checked')){
        $(this).parents('td').siblings('.dt-dzero-show').find('input[type="text"]').removeAttr('readonly');
    }else{
        $(this).parents('td').siblings('.dt-dzero-show').find('input[type="text"]').attr('readonly',true);
    }
});
$('.dt-done-show input[type="checkbox"]').change(function () {
    if($(this).is(':checked')){
        $(this).parents('td').siblings('.dt-done-show').find('input[type="text"]').removeAttr('readonly');
    }else{
        $(this).parents('td').siblings('.dt-done-show').find('input[type="text"]').attr('readonly',true);
    }
});


//D1,D0选择切换
$('input[name="mch_wx_pay"]').each(function(i){
    $(this).on('click',function(){
        if(i==0){
            $('.pay-wechat .dt-done-show').show();
            $('.pay-wechat .dt-dzero-show').hide();
            $('input[name="mch_wx_pay"]').eq(1).removeAttr('checked');
        }else{
            $('.pay-wechat .dt-done-show').hide();
            $('.pay-wechat .dt-dzero-show').show();
            $('input[name="mch_wx_pay"]').eq(0).removeAttr('checked');
        }
    })
});
$('input[name="mch_ali_pay"]').each(function(i){
    $(this).on('click',function(){
        if(i==0){
            $('.pay-alipay .dt-done-show').show();
            $('.pay-alipay .dt-dzero-show').hide();
            $('input[name="mch_ali_pay"]').eq(1).removeAttr('checked');
        }else{
            $('.pay-alipay .dt-done-show').hide();
            $('.pay-alipay .dt-dzero-show').show();
            $('input[name="mch_ali_pay"]').eq(0).removeAttr('checked');
        }
    })
});

//设置费率cookie
$('#submit-btn').click(function(){
    if($('#payTypeWechat').is(':checked') || $('#payTypeAlipay').is(':checked')){

    }else{
        $('.time-error').html('<p style="color:darkred;font-size:16px;">支付信息不能为空</p>');
        return false;
    }
    if($('input:radio[name="mch_wx_pay"]:checked').val() == 'd1'){
        $('.pay-wechat .dt-dzero-show input[type="text"]').val('');
    }else if($('input:radio[name="mch_wx_pay"]:checked').val() == 'd0'){
        if($('#bankRatePayWechat').val()==''){
            $(this).siblings('.time-error').html('<p style="color:darkred;font-size:16px;">D0微信提现手续费不能为空</p>');
            return false;
        }
        $('.pay-wechat .dt-done-show input[type="text"]').val('');
    }
    if($('input:radio[name="mch_ali_pay"]:checked').val() == 'd1'){
        $('.pay-alipay .dt-dzero-show input[type="text"]').val('');
    }else if($('input:radio[name="mch_ali_pay"]:checked').val() == 'd0'){
        if($('#bankRatePayAlipay').val()==''){
            $(this).siblings('.time-error').html('<p style="color:darkred;font-size:16px;">D0支付宝提现手续费不能为空</p>');
            return false;
        }
        $('.pay-alipay .dt-done-show input[type="text"]').val('');
    }

    $.cookie('recordWx',$('input:radio[name="mch_wx_pay"]:checked').val());
    $.cookie('recordAlipay',$('input:radio[name="mch_ali_pay"]:checked').val());
    //$(this).parents('form').submit();


    var del_annex='',del_wx_dine_annex='';
    $('#mchUpload .base-upload-img').each(function(i){
       if($(this).attr('src') == '' ){
           del_annex+=parseInt(i+1)+'-';
       }
    });
    $('#mchWxUpload .base-upload-img').each(function(i){
       if($(this).attr('src') == '' ){
           del_wx_dine_annex+=parseInt(i+1)+'-';
       }
    });
    del_annex=del_annex.substring(0,del_annex.length-1);
    del_wx_dine_annex=del_wx_dine_annex.substring(0,del_wx_dine_annex.length-1);
    $('input[name="del_annex"]').val(del_annex);
    $('input[name="del_wx_dine_annex"]').val(del_wx_dine_annex);
});


//围餐零费率
$('input[name="use_dine"]').click(function(){
    if($(this).is(':checked')){
        $('.table-dine').show();
        $('.table-role').hide();
        $('.table-role').find('input[type=text]').attr('disabled',true);
        $(this).val('1');
        $('select[name="use_dine"] option').val('1');
    }else{
        $('.table-dine').hide();
        $('.table-role').show();
        $('.table-role').find('input[type=text]').removeAttr('disabled');
        $(this).val('');
        $('select[name="use_dine"] option').val('');
    }
});
$(function(){
    if($('input[name="use_dine"]').val()==1){
        $('.table-dine').show();
        $('.table-role').hide();
        $('#payTypeWechat').attr('checked',true);
        $('.pay-wechat').show();
        $('input[name="use_dine"]').attr('checked',true);
    }else{
        $('.table-dine').hide();
        $('.table-role').show();
        $('input[name="use_dine"]').removeAttr('checked');
    }
    $('.table-dine input[type="checkbox"]').attr('checked',true);
});


if(has_been_authed == 'True'){
    /*if($('#payTypeWechat').is(':checked')){
        if($('#bankRatePayWechat').val() == ''){
            $('.pay-wechat .dt-dzero-show,.pay-wechat .dt-dzero-choose').remove();
        }else if($('#bankRatePayWechat').val() > 0){
            $('.pay-wechat .dt-done-show,.pay-wechat .dt_radio').remove();
        }
    }
    if($('#payTypeAlipay').is(':checked')){
        if($('#bankRatePayAlipay').val() == ''){
            $('.pay-alipay .dt-dzero-show,.pay-alipay .dt-dzero-choose').remove();
        }else if($('#bankRatePayWechat').val() > 0){
            $('.pay-alipay .dt-done-show,.pay-alipay .dt_radio').remove();
        }
    }*/
}
