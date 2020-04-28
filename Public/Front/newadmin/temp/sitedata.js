/**
 * ITKEE.CN 数据统计
 */
$(function(){
    var GV = {datas:""};
    $.ajax({
        type:'get',
        url:$("#data_main").attr('data-href'),
        data:'',
        async:false,
        success:function(data){
            GV.datas = data;
        }
    });
    //++++++++++++++++++++++++++++++++++++++++站点数据统计分析+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    var myData = echarts.init(document.getElementById('data_main'));
    option = {
        title: {
            text: '站点数据统计分析'
        },
        tooltip : {
            trigger: 'axis',
            axisPointer: {
                type: 'cross',
                label: {
                    backgroundColor: '#6a7985'
                }
            }
        },
        legend: {
            data:GV.datas['showtitle'] //展示项标题
        },
        toolbox: {
            feature: {
                saveAsImage: {}
            }
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis : [
            {
                type : 'category',
                boundaryGap : false,
                data : GV.datas['showtime'] //展示项标题
            }
        ],
        yAxis : [
            {
                type : 'value'
            }
        ],
        series : GV.datas['dataline']
    };
    // 使用刚指定的配置项和数据显示图表。
    myData.setOption(option);
    //+++++++++++++++++++++++++++++++++++++++++++++++站点数据统计分析+++++++++++++++++++++++++++++++++++++++++++++++++++++
    //+++++++++++++++++++++++++++++++++++++++++++++++站点会员统计分析+++++++++++++++++++++++++++++++++++++++++++++++++++++
    var UserData = echarts.init(document.getElementById('data_user'));
    option = {
        title : {
            text: '用户类型数据分析',
            subtext: '可供参考',
            x:'center'
        },
        tooltip : {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend: {
            orient: 'vertical',
            left: 'left',
            data: ['第三方用户','本站用户']
        },
        series : [
            {
                name: '用户类型',
                type: 'pie',
                radius : '80%',
                center: ['50%', '60%'],
                data:[
                    {value:GV.datas['user_data']['user_sns_data'], name:'第三方用户'},
                    {value:GV.datas['user_data']['user_local_data'], name:'本站用户'}
                ],
                itemStyle: {
                    emphasis: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }
        ]
    };
    UserData.setOption(option);
    //+++++++++++++++++++++++++++++++++++++++++++++++站点会员统计分析+++++++++++++++++++++++++++++++++++++++++++++++++++++
    //统计总数据
    var AllDataChart = echarts.init(document.getElementById('all_main'));
    var AllDataUrl = $("#all_main").attr('data-href');
    AllDataChart.setOption({
        title: {
            text: '数据总量统计'
        },
        color: ['#5FB878'],
        tooltip : {
            trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow',        // 默认为直线，可选为：'line' | 'shadow'
            }
        },
        toolbox: {
            feature: {
                saveAsImage: {}
            }
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis : [
            {
                type : 'category',
                data : ['注册会员', '话题量', '话题评价'],
                axisTick: {
                    alignWithLabel: true
                }
            }
        ],
        yAxis : [
            {
                type : 'value'
            }
        ],
        series : [
            {
                name:'数据统计',
                type:'bar',
                barWidth: '60%',
                data:[]
            }
        ]
    });

    // 异步加载数据
    $.get(AllDataUrl).done(function (data) {
        // 填入数据
        AllDataChart.setOption({
            // 根据名字对应到相应的系列
            series: [
                {
                    name: '数据统计',
                    data: [data.user_register.all, data.new_topic.all , data.user_comments.all]
                }
            ]
        });
    });
});