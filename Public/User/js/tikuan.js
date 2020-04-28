$(document).ready(function(e){
    $("#selecttongdao").change(function(e){
        tongdaoid = $(this).val();
        tikuantype = $("#tikuantype").val();
        ajaxurl = $(this).attr("ajaxurl");
        //alert(ajaxurl+"---tongdaoid="+tongdaoid+"&tikuantype="+tikuantype);
        $.ajax({
            type:'POST',
            url:ajaxurl,
            data:"tongdaoid="+tongdaoid+"&tikuantype="+tikuantype+"&t="+$("#t").val(),
            dataType:'json',
            success:function(obj){
                if(obj.status=="0"){
                    jAlert(obj.info,"提示信息");
                }else{
                    $("#tongdaofeilvdiv").html(obj["str"]);
                    $("#sxfmoney").val(obj["sxfmoney"]);
                    $("#sxftype").val(obj["sxftype"]);
                    $("#yuemoney").val(obj["yuemoney"]);
                    $("#yuemoneydiv").html(parseInt(obj["yuemoney"])+"元");
                }
            },
        });
    });

    $("#jssxfbutton").click(function(e) {
        jssxf();
    });


    $("#sqjsbutton").click(function(e) {

        if(jssxf()){
            paypassword = $("#paypassword").val();
            if(paypassword == ""){
                jAlert("支付密码不能为空！","提示信息",function(){
                    $("#paypassword").focus();
                    btn.button('reset');
                    return false;
                });
            }else{
                var btn = $(this);
                btn.button('loading');
                ajaxurl=$("#sqjsbutton").attr("ajaxurl");
                $.ajax({
                    type:'POST',
                    url:ajaxurl,
                    data:"tongdaoid="+$("#selecttongdao").val()+"&jsmoney="+$("#jsmoney").val()+"&paypassword="+$("#paypassword").val()+"&t="+$("#t").val(),
                    dataType:'text',
                    success:function(str){
                        switch(str){
                            case "systemerror":
                                alertstr = "系统错误 ，请不要非法提交数据！";
                                break;
                            case "paypassworderror":
                                alertstr = "支付密码错误！";
                                break;
                            case "errorguanbi":
                                alertstr = "结算功能已关闭！";
                                break;
                            case "errormoney1":
                                alertstr = "您选择的通道的余额不足！";
                                break;
                            case "errormoney2":
                                alertstr = "结算金额不够支付手续费！";
                                break;
                            case "errormoney3":
                                alertstr = "单笔结算金额小于系统设置最小金额！";
                                break;
                            case "errormoney4":
                                alertstr = "单笔结算金额大于系统设置最大金额！";
                                break;
                            case "errormoney5":
                                alertstr = "结算金额超过今天系统设置最大金额！";
                                break;
                            case "errormoney6":
                                alertstr = "已超过今天结算最大次数！";
                                break;
                            case "errorbank":
                                alertstr = "请先完善结算银行信息后再申请结算！";
                                break;
                            case "errorkouquan":
                                alertstr = "扣款失败，请稍后重试！";
                                break;
                            case "ok":
                                alertstr = "申请结算成功！";
                                break;
                        }
                        jAlert(alertstr,"提示信息",function(){
                            btn.button('reset');
                            if(str == "ok"){
                                window.location.reload();
                            }
                        });

                    },
                });
            }
        }
    });
});

function jssxf(){
    var btn = $("#sqjsbutton");
    btn.button('loading');
    $("#sxfdiv").val("");
    $("#dzmoneydiv").val("");
    var tongdaoid = $("#selecttongdao").val();
    if(!tongdaoid){
        jAlert("请选择结算通道","提示信息",function(){
            $("#selecttongdao").focus();
            btn.button('reset');
            return false;
        });
    }
    var jsmoney = $("#jsmoney").val();
    var jsmoney = Math.round(jsmoney*100)/100;
    if(jsmoney == "" || jsmoney == 0){
        jAlert("结算金额不能为空或为0！","提示信息",function(){
            $("#jsmoney").focus();
            btn.button('reset');
            return false;
        });
    }else{
        var yuemoney = $("#yuemoney").val();
        if(parseFloat(jsmoney) >= parseFloat(yuemoney)){
            jAlert("结算余额不足！","提示信息",function(){
                $("#jsmoney").focus();
                btn.button('reset');
                return false;
            });
        }else{
            sxftype = $("#sxftype").val();
            sxfmoney = $("#sxfmoney").val();
            if(sxftype == 1){
                sxf = parseFloat(sxfmoney);
            }else{
                sxf = parseFloat(jsmoney)*parseFloat(sxfmoney);
            }
            if(parseFloat(jsmoney) <= parseFloat(sxfmoney)){
                jAlert("您结算的金额不够支付手续费！","提示信息",function(){
                    btn.button('reset');
                    return false;
                });
            }else{
                tkzxmoney = $("#tkzxmoney").val();
                if(parseFloat(jsmoney) < parseFloat(tkzxmoney)){
                    jAlert("单笔结算金额不能小于"+tkzxmoney+"元！","提示信息",function(){
                        btn.button('reset');
                        return false;
                    });
                }else{
                    $("#sxfdiv").css('display','block').val(parseFloat(sxf));
                    $("#dzmoneydiv").css('display','block').val(parseFloat(jsmoney-sxf));
                    btn.button('reset');
                    return true
                }
            }
        }
    }
}
