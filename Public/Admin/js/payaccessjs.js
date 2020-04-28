$(document).ready(function(e) {

    $('#loading-yinghang-btn').click(function() {

        var datastr = "";
        var btn = $(this);
        btn.button('loading');

        var en_payname = $("#en_payname").val();
        var forminput = "input";
        var datastr = "";
        //alert(en_payname);
        if (en_payname == "Default") {
            forminput = "select";
            datastr = "payapiid=" + $("#payapiid").val() + "&websiteid=" + $("#websiteid").val() + "&";
        }

        $("#form2 " + forminput).each(function(index, element) {
            $(this).attr("disabled", "disabled");
            datastr = datastr + $(this).attr("name") + "=" + $(this).val() + "&";
        });

        //alert(datastr);

        $("#tscontent").text("");
        urlstr = $("#form2").attr("action");


        $.ajax({
            type: 'POST',
            url: urlstr,
            data: datastr,
            dataType: 'text',
            success: function(str) {
                $("#form2 " + forminput).each(function(index, element) {
                    $(this).removeAttr("disabled");
                });
                btn.button('reset');
                $("#tscontent").text(str);
                $('#myModal').modal('show');
            },
        });

    });

    $('#loading-tikuan-btn').click(function() {

        var datastr = "";
        var btn = $(this);
        btn.button('loading');

        $("#form3 input").each(function(index, element) {
            $(this).attr("disabled", "disabled");
            datastr = datastr + $(this).attr("name") + "=" + $(this).val() + "&";
        });

        $("#tscontent").text("");
        urlstr = $("#form3").attr("action");

        $.ajax({
            type: 'POST',
            url: urlstr,
            data: datastr,
            dataType: 'text',
            success: function(str) {
                $("#form3 input").each(function(index, element) {
                    $(this).removeAttr("disabled");
                });
                btn.button('reset');

                $("#tscontent").text(str);
                $('#myModal').modal('show');
            },
        });

    });

});

function startusing(payapiid, websiteid, zh_payname, mythis) {
    btn = $(mythis);
    //btn.attr("disabled","disabled");
    $("#tscontent").text("确认要启用【" + zh_payname + "】通道吗？");
    $('#myModal').modal('show');
    $("#okbutton").unbind("click").show().click(function(e) {

        btn.button("loading");
        $("#tscontent").text("请稍后，正在启用【" + zh_payname + "】通道......");
        $("#okbutton").hide();
        urlstr = $("#urlstr").val();
        $.ajax({
            type: 'POST',
            url: urlstr,
            data: "payapiid=" + payapiid + "&websiteid=" + websiteid + "&ty=1",
            dataType: 'text',
            success: function(str) {
                btn.button('reset');
                btn.parent().css("background-color", "#5cb85c");
                //btn.parent().parent().children(".list-group-item").children("button").removeAttr("disabled");
                $(".tingyong" + payapiid).removeAttr("disabled");
                //alert("#qiyong"+payapiid);
                $("#qiyong" + payapiid).attr("disabled", "disabled");
                $("#tscontent").text(str);
                $('#myModal').modal('show');
            },
            error: function(str) {
                btn.button('reset');
                $("#tscontent").text("启用失败！");
                $('#myModal').modal('show');
            }
        });
    });
}

function shutoff(payapiid, websiteid, zh_payname, mythis) {
    fanhui();
    btn = $(mythis);
    //btn.attr("disabled","disabled");
    $("#tscontent").text("确认要停用【" + zh_payname + "】通道吗？");
    $('#myModal').modal('show');
    $("#okbutton").unbind("click").show().click(function(e) {
        btn.button("loading");
        $("#tscontent").text("请稍后，正在停用【" + zh_payname + "】通道......");
        $("#okbutton").hide();
        urlstr = $("#urlstr").val();
        $.ajax({
            type: 'POST',
            url: urlstr,
            data: "payapiid=" + payapiid + "&websiteid=" + websiteid + "&ty=0",
            dataType: 'text',
            success: function(str) {
                btn.button('reset');
                btn.parent().css("background-color", "#f0ad4e");
                //btn.parent().parent().children(".list-group-item").children("button").removeAttr("disabled");
                $(".tingyong" + payapiid).attr("disabled", "disabled");
                //alert("#qiyong"+payapiid);
                $("#qiyong" + payapiid).removeAttr("disabled");
                $("#tscontent").text(str);
                $('#myModal').modal('show');

                var btnDisabled = '';
                btnDisabled = $("#btnDefault_" + payapiid).attr('disabled');
                if (btnDisabled == 'disabled') {
                    $(".Payaccessdiv").removeClass("defaultclass");
                    $('#btnDefault_' + payapiid).removeAttr("disabled").text("设置为默认通道");
                }
            },
            error: function(str) {
                btn.button('reset');
                $("#tscontent").text("停用失败！");
                $('#myModal').modal('show');
            }
        });
    });
}

function shezhi(mythis, payapiid, websiteid) {
    indexnumber = $(mythis).parent().parent().parent().index();
    indexnumber = indexnumber - 1;

    $(".Payaccessdiv").each(function(index, element) {
        if (index != indexnumber) {
            $(this).hide(500);
        }
    });
    urlstr = $("#form1").attr("loadtab");
    $.ajax({
        type: 'POST',
        url: urlstr,
        data: "payapiid=" + payapiid + "&websiteid=" + websiteid,
        success: function(str) {
            if (str == "no") {
                $("#tscontent").text("数据加载失败......");
                $("#okbutton").hide();
                $('#myModal').modal('show');
                fanhui();
            } else {
                //alert(str);
                $("#myTab").html("");
                $("#mytabcontent").html("");
                splitstr = str.split("|");
                for (var i = 1; i < splitstr.length; i++) {
                    $("#myTab").append('<li><a href="#zh' + i + '" data-toggle="tab" accountid="' + splitstr[i] + '"><strong>账号' + i + '</strong></a></li>');
                    $("#mytabcontent").append('<div class="tab-pane" id="zh' + i + '"></div>');
                }
                if (i < 6) {
                    $("#myTab").append('<li><a href="#zh' + i + '" disabled="true" data-toggle="tab" onclick="javascript:tjaccount(' + payapiid + ')"><strong>添加账号</strong></a></li>');
                }

                $('#myTab a').click(function(e) {
                    e.preventDefault();
                    $($(this).attr("href")).html($("#payform").html());
                    zylwj = $(this).attr("href");
                    $.ajax({
                        type: 'POST',
                        url: $("#form1").attr("loadurl"),
                        data: "id=" + $(this).attr("accountid"),
                        dataType: 'text',
                        success: function(str) {
                            splitstr = str.split("|");
                            $(zylwj + " input[name='id']").val(splitstr[0]);
                            $(zylwj + " input[name='sid']").val(splitstr[1]);
                            $(zylwj + " input[name='key']").val(splitstr[2]);
                            $(zylwj + " input[name='account']").val(splitstr[3]);
                            $(zylwj + " input[name='domain']").val(splitstr[4]);
                            $(zylwj + " input[name='pagereturn']").val(splitstr[9]);
                            $(zylwj + " input[name='serverreturn']").val(splitstr[10]);
                            $(zylwj + " input[name='defaultrate']").val(splitstr[5]);
                            $(zylwj + " input[name='rate']").val(splitstr[6]);
                            $(zylwj + " input[name='fengding']").val(splitstr[8]);
                            $(zylwj + " input[name='keykey']").val(splitstr[11]);
                            $(zylwj + " label[name='updatetime']").html(splitstr[12]);
                            $(zylwj + " input[name='unlockdomain']").val(splitstr[13]);
                            $(zylwj + " input[name='defaultpayapiuser']").attr("checked", splitstr[7] == 1 ? true : false);
                            $(zylwj + " .loading-example-btn").attr("zylwj", zylwj);

                            $(".loading-example-btn").click(function(e) {
                                var datastr = "";
                                var btn = $(this);
                                btn.button('loading');
                                $($(this).attr("zylwj") + " input").each(function(index, element) {
                                    //$(this).attr("disabled",true);
                                    datastr = datastr + $(this).attr("name") + "=" + $(this).val() + "&";
                                });
                                $("#tscontent").text("");
                                urlstr = $("#form1").attr("action");
                                $.ajax({
                                    type: 'POST',
                                    url: urlstr,
                                    data: datastr,
                                    dataType: 'text',
                                    success: function(str) {
                                        $("#form2 input").each(function(index, element) {
                                            $(this).attr("disabled", false);
                                        });
                                        btn.button('reset');
                                        $("#tscontent").text(str);
                                        $('#myModal').modal('show');
                                    },
                                });
                            });
                        },
                        error: function() {}
                    });
                    $(this).tab('show');
                });
                $('#myTab li:eq(0) a').click();
            }

            $("#zhmysz").show(500);
            $("#fanhui" + payapiid).show();
        },
    });
}


function tikuanmoney(mythis, payapiid, websiteid) {
    indexnumber = $(mythis).parent().parent().parent().index();
    indexnumber = indexnumber - 1;

    $(".Payaccessdiv").each(function(index, element) {
        if (index != indexnumber) {
            $(this).hide(500);
        }
    });

    urlstr = $("#form3").attr("loadurl");

    $.ajax({
        type: 'POST',
        url: urlstr,
        data: "payapiid=" + payapiid + "&websiteid=" + websiteid,
        dataType: 'json',
        success: function(str) {
            $("#form3 input").each(function(index, element) {
                $(this).val(str[$(this).attr("name")]);
            });

            $("#tikuanpayapiid").val(payapiid);
            $("#tikuanwebsiteid").val(websiteid);
            $("#tikuanmoney").show(500);
            $("#fanhui" + payapiid).show();
        }
    });
}

function yinhang(mythis, payapiid, websiteid) {
    indexnumber = $(mythis).parent().parent().parent().index();
    indexnumber = indexnumber - 1;

    $(".Payaccessdiv").each(function(index, element) {
        if (index != indexnumber) {
            $(this).hide(500);
        }
    });
    urlstr = $("#form2").attr("loadurl");
    $.ajax({
        type: 'POST',
        url: urlstr,
        data: "payapiid=" + payapiid + "&websiteid=" + websiteid,
        dataType: 'json',
        success: function(str) {
            frominput = "input";
            if (str["en_payname"] == "Default") {
                $("#form2 input").hide();
                $("#form2 select").show();
                frominput = "select";
            } else {
                $("#form2 input").show();
                $("#form2 select").hide();
            }
            $("#form2 " + frominput).each(function(index, element) {
                $(this).val(str[$(this).attr("name")]);
            });

            $("#payapiid").val(payapiid);
            $("#en_payname").val(str["en_payname"]);
            $("#websiteid").val(websiteid);
            $("#yhsz").show(500);
            $("#fanhui" + payapiid).show();
        },
    });
}

function fanhui() {

    $("#zhmysz,#yhsz,#tikuanmoney").hide(500, function() {
        $(".Payaccessdiv[id!='zhmysz'][id!='yhsz'][id!='tikuanmoney']").show(500);
    });
    $(".fanhui").hide();

}

function szdefault(mythis, payapiid, websiteid) {
    urlstr = $(mythis).attr("urlstr");
    $.ajax({
        type: 'POST',
        url: urlstr,
        data: "payapiid=" + payapiid + "&websiteid=" + websiteid,
        dataType: 'text',
        success: function(str) {
            if (str == "ok") {
                $("#tscontent").text("默认通道修改成功！");
                $("#okbutton").hide();
                $('#myModal').modal('show');
                $(".Payaccessdiv").removeClass("defaultclass");
                $(mythis).parent().parent().parent().addClass("defaultclass");
                $(".default_sz").removeAttr("disabled").text("设置为默认通道");
                $(mythis).attr("disabled", "disabled").text("默认通道");
            }
        },
    });
}

function tjaccount(payapiid) {
    urlstr = $("#form1").attr("addtab");
    $.ajax({
        type: 'POST',
        url: urlstr,
        data: "payapiid=" + payapiid,
        dataType: 'text',
        success: function(str) {
            urlstr = $("#form1").attr("loadtab");
            $.ajax({
                type: 'POST',
                url: urlstr,
                data: "payapiid=" + payapiid + "&websiteid=0",
                dataType: 'text',
                success: function(str) {
                    if (str == "no") {
                        $("#tscontent").text("数据加载失败......");
                        $("#okbutton").hide();
                        $('#myModal').modal('show');
                        fanhui();
                    } else {
                        //alert(str);
                        $("#myTab").html("");
                        $("#mytabcontent").html("");
                        splitstr = str.split("|");
                        for (var i = 1; i < splitstr.length; i++) {
                            //alert(i);
                            $("#myTab").append('<li><a href="#zh' + i + '" data-toggle="tab" accountid="' + splitstr[i] + '"><strong>账号' + i + '</strong></a></li>');
                            $("#mytabcontent").append('<div class="tab-pane" id="zh' + i + '"></div>');
                        }

                        if (i < 6) {
                            $("#myTab").append('<li><a href="#zh' + i + '" data-toggle="tab" onclick="javascript:tjaccount(' + payapiid + ')"><strong>添加账号</strong></a></li>');
                        }
                        $('#myTab a').click(function(e) {
                            e.preventDefault();
                            $($(this).attr("href")).html($("#payform").html());
                            zylwj = $(this).attr("href");
                            $.ajax({
                                type: 'POST',
                                url: $("#form1").attr("loadurl"),
                                data: "id=" + $(this).attr("accountid"),
                                dataType: 'text',
                                success: function(str) {
                                    splitstr = str.split("|");
                                    //alert(splitstr[0]);
                                    $(zylwj + " input[name='id']").val(splitstr[0]);

                                    $(zylwj + " input[name='sid']").val(splitstr[1]);
                                    $(zylwj + " input[name='key']").val(splitstr[2]);
                                    $(zylwj + " input[name='account']").val(splitstr[3]);

                                    $(zylwj + " input[name='domain']").val(splitstr[4]);
                                    $(zylwj + " input[name='defaultrate']").val(splitstr[5]);
                                    $(zylwj + " input[name='rate']").val(splitstr[6]);
                                    $(zylwj + " .loading-example-btn").attr("zylwj", zylwj);
                                    //$("#"+zylwj+" input[name='id']").val(splitstr[0]);
                                    //$("#sid").val(splitstr[1]);
                                    //$("#key").val(splitstr[2]);
                                    //$("#account").val(splitstr[3]);

                                    $(".loading-example-btn").click(function(e) {
                                        var datastr = "";
                                        var btn = $(this);
                                        btn.button('loading');

                                        $($(this).attr("zylwj") + " input").each(function(index, element) {
                                            $(this).attr("disabled", "disabled");
                                            datastr = datastr + $(this).attr("name") + "=" + $(this).val() + "&";
                                        });

                                        $("#tscontent").text("");
                                        urlstr = $("#form1").attr("action");
                                        $.ajax({
                                            type: 'POST',
                                            url: urlstr,
                                            data: datastr,
                                            dataType: 'text',
                                            success: function(str) {
                                                $("#form2 input").each(function(index, element) {
                                                    $(this).removeAttr("disabled");
                                                });
                                                btn.button('reset');
                                                $("#tscontent").text(str);
                                                $('#myModal').modal('show');
                                            }
                                        });
                                    });
                                },
                                error: function() {}
                            });
                            $(this).tab('show');
                        });
                        $('#myTab li:eq(0) a').click();
                    }

                    $("#zhmysz").show(500);
                    $("#fanhui" + payapiid).show();
                }
            });
        }
    });
}