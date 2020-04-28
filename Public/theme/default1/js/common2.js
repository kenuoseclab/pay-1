define('modules/js/common2', function(require, exports, module) {

  /**
   * Created by Administrator on 2016/9/9.
   *
  
   */
  
  
  
  
  $(function(){
      //改变日期格式
  
  //    其他银行
      $(".qtyh").click(function(){
          console.log("ssss")
          $(".qtyha").slideToggle()
      });
  
  
     // 大小写转化
     $(".chinain").keyup(function(){
         if(!isNaN($(this).val())){
             china($(this).val(), $(".chinaout"))
         }
     });
  
  //    阿拉伯数字转化为中文
      function china(chin,chout) {
          console.log("大小写");
  //        哪里需要转换大小写
          if(chin!=0){
              daxiaoxie(chin,chout)
          }else{
              chout.val("");
          }
  
          function daxiaoxie(n,chout) {
              var fraction = ['角', '分'];
              var digit = ['零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'];
              var unit = [['元', '万', '亿'], ['', '拾', '佰', '仟']];
              var head = n < 0 ? '欠' : '';
              n = Math.abs(n);
              var s = '';
              for (var i = 0; i < fraction.length; i++) {
                  s += (digit[Math.floor(n * 10 * Math.pow(10, i)) % 10] + fraction[i]).replace(/零./, '');
              }
              s = s || '整';
              n = Math.floor(n);
              for (var i = 0; i < unit[0].length && n > 0; i++) {
                  var p = '';
                  for (var j = 0; j < unit[1].length && n > 0; j++) {
                      p = digit[n % 10] + unit[1][j] + p;
                      n = Math.floor(n / 10);
                  }
                  s = p.replace(/(零.)*零$/, '').replace(/^$/, '零') + unit[0][i] + s;
              }
              var _bigmoney = head + s.replace(/(零.)*零元/, '元').replace(/(零.)+/g, '零').replace(/^整$/, '零元整');
  //              输出到哪里
              (chout.val(_bigmoney))||(chout.html(_bigmoney));
          }
      }
  
  
  
  
  
  
  
  
  
      ////    自定义预留信息
      //jQuery.validator.addMethod("ccc", function(value, element) {
      //    return this.optional(element) || /^[A-Za-z]{0,45}$/.test(value)||/^[\u4e00-\u9fa5]{0,15}$/.test(value);
      //}, "匹配english");
      // 验证规则
      $(".ysbbd").validate({
          errorPlacement:function(error,element){
              error.appendTo(element.parent())
          },
          rules: {
              firstname: "required",
              //密码
              pwd:{
                  required: true
              },
              //新密码
              newpwd:{
                  required: true
              },
              //重复密码
              cu_newpwd: {
                  required: true,
                  equalTo: "#newpwd"
              },
              //密码
              paypwd:{
                  required: true
              },
              //新密码
              newpaypwd:{
                  required: true
              },
  
              //重复支付密码
              cu_newpaypwd: {
                  required: true,
                  equalTo: "#newpaypwd"
              },
  
              //校正码
              jzm:{
                  required: true
              },
              //校正码
              jym:{
                  required: true
              },
              //充值金额
              czje:{
                  required: true,
                  number:true,
                  xiane:true
              },
              //提现金额
              txje:{
                  required: true,
                  number:true,
              },
              //收款方账户
              skfzh:{
                  required: true
              },
              //安全保护问题
              safe_qus:{
                  required:true
              },
              //付款金额
              fkje:{
                  required: true,
                  number:true
              },
              //资金用途
              zjyt:{
                  required: true,
                  number:true,
                  maxlength:85
              },
              //预留信息
              ylxx:{
                  required: true,
                  ggg:true
              },
              //身份证有效期
              yxjhm:{
                  required: true
              },
              yourname:{
                  required: true
              },
              yourphone:{
                  required: true,
                  isMobile:true
              },
              youremail:{
                  required: true,
                  email:true
              },
              txm:{
                  required: true,
                  number:true
              },
              qrm:{
                  required: true
              },
              axe:{
                  required: true,
                  number:true,
                  range:[200,9999999999999999999999999999999999999999999999999999]
              },
              allxe:{
                  required: true,
                  number:true,
                  range:[500,9999999999999999999999999999999999999999999999999999],
                  xedx:true
              },
              sfz:{
                  required: true,
                  isIdCardNo:true
              },
              //自定义金额
              zdyje:{
                  yushu:true,
                  number:true,
                  required:true,
                  range:[0,3000]
              },
              //手机号码
              sjhm:{
                  required:true,
                  isMobile:true
              },
              //手机号码
              yhkh:{
                  required:true
              },
              //手机号码
              khxm:{
                  required:true
              },
              //手机号码
              khhmc:{
                  required:true
              },
              email: {
                  email: true
              },
              bdyx:{
                  email: true
              },
              zjhm:{
                  required:true
              },
              xsjhm:{
                  required:true,
                  isMobile:true
              },
              yzm:{
                  required:true
              },
              khmc:{
                  required:true
              },
              "topic[]": {
                  required: "#newsletter:checked",
                  minlength: 2
              },
              agree: "required"
          },
          messages: {
              firstname: "请输入您的名字",
              lastname: "请输入您的姓氏",
              username: {
                  required: "请输入用户名",
                  minlength: "用户名必需由两个字母组成"
              },
              //登录密码
              pwd:{
                  required: "请输入密码"
              },
              //新密码
              newpwd:{
                  required: "请输入新密码"
              },
              //修改支付密码
              paypwd:{
                  required: "请输入支付密码"
              },
              //新密码
              newpaypwd:{
                  required: "请输入新支付密码"
              },
              //重复新密码
              cu_newpwd: {
                  required: "请输入新密码",
                  equalTo: "两次密码输入不一致"
              },
              //重复新密码
              cu_newpaypwd: {
                  required: "请输入新支付密码",
                  equalTo: "两次密码输入不一致"
              },
              //校正码
              jzm:{
                  required: "请输入验证码"
              },
              //校正码
              jym:{
                  required: "请输入校验码"
              },
              //充值金额
              czje:{
                  required: "请输入充值金额",
                  number:"请输入正确格式",
                  xiane:"超出额度"
              },
              //提现金额
              txje:{
                  required: "请输入充值金额",
                  number:"请输入正确格式"
              },
              //付款金额
              fkje:{
                  required: "请输入充值金额",
                  number:"请输入正确格式"
              },
              //收款方账户
              skfzh:{
                  required: "请输入收款方账户"
              },
              //资金用途
              zjyt:{
                  required: "请输入资金用途",
                  maxlength:"长度太长"
              },
              safe_qus:{
                  required:"请输入答案"
              },
              //预留信息
              ylxx:{
                  required: "请输入预留信息",
                  maxlength: "预留信息太长"
              },
              //资金用途
              email: {
                  email:"请输入正确邮箱格式"
              },
              yxjhm:{
                  required: "请输入邮箱激活码"
              },
              yourname:{
                  required: "请输入你的名字"
              },
              yourphone:{
                  required: "请输入你的手机",
                  isMobile:"手机号码格式错了"
              },
              youremail:{
                  required: "请输入你的email",
                  email:"邮箱格式错了"
              },
              txm:{
                  required: "请输入你的email",
                  number:"格式错了"
              },
              axe:{
                  required: "请输入单笔限额",
                  number:"格式错了",
                  range:"最低200"
              },
              allxe:{
                  required: "请输入当日限额",
                  number:"格式错了",
                  range:"最低500",
                  xedx:"大小错误"
              },
              qrm:{
                  required: "请输入确认码"
              },
              sfz:{
                  required: "请输入身份证",
                  isIdCardNo:"格式错了"
              },
              zdyje:{
                  yushu:"输入10的倍数",
                  number:"输入数字",
                  required:"输入金额",
                  range:"超过限制"
              },
              //手机号码
              sjhm:{
                  required:"请输入手机号码",
                  isMobile:"格式错了"
              },
              //手机号码
              yhkh:{
                  required:"请输入银行卡号"
              },
              //手机号码
              khxm:{
                  required:"请输入开户姓名"
              },
              //手机号码
              khhmc:{
                  required:"请输入开户行名称"
              },
              bdyx:{
                  email:"请输入正确格式"
              },
              zjhm:{
                  required:"请输入证件号码"
              },
              xsjhm:{
                  required:"请输入手机号码",
                  isMobile:"请输入正确格式"
              },
              yzm:{
                  required:"请输入验证码"
              },
              khmc:{
                  required:"请输入开户名称"
              },
              agree: "请接受我们的声明",
              topic: "请选择两个主题"
          }
  
      });
  
      //选择单个个联系人并结算
      $("#mone .modalqd").click(function(){
          var _dr=$("#mone").find("input:checked").parents().siblings("td:last").html();
          var _dr1=$("#mone").find("input:checked").parents().siblings("td:nth-child(2)").html();
          $(".fmpeople").val(_dr);
          $(".fmname").val(_dr1)
      });
      //单选变色
      $(".xzlxr tbody input:radio").click(function(){
          $(".xzlxr tbody tr").css("background","white");
          $(this).parents("tr").css("background","#F0F0F0");
          //console.log($(this).parents().siblings("td:last").html())
      });
  
      //选择多个联系人并结算
      $("#mtwo .modalqd").click(function(){
          var _zl=0;
          var _dl="";
          var _zp=$(".ck").find("input:checked:visible").length;
          console.log(_zp)
          for(var i=0;i<_zp;i++){
              //console.log($(".ck").eq(i).find("input:checked").parents().siblings("td:last").children("input").val());
              _zl+=parseInt($(".ck").find("input:checked:visible").eq(i).parents().siblings("td:last").children("input").val());
          }
          $(".multipeo").html(_zp);
          $(".multipeo").val(_zp);
          $(".multimoney").html(_zl);
          $(".multimoney").val(_zl);
  
          china(_zl,$(".chinaout"))
      });
  
  //选择联系人全选
      var _ct=-1;
      $("#yh thead input:checkbox").click(function(){
          _ct*=-1;
          if(_ct==-1){
              $("#yh tbody :checkbox").prop("checked",false);
              $("#yh tbody :text").prop("disabled",true);
          }else if(_ct==1){
              $("#yh tbody :checkbox").prop("checked",true);
              $("#yh tbody :text").prop("disabled",false);
          }
      });
      var _ot=-1;
      $("#ysb thead input:checkbox").click(function(){
          _ot*=-1;
          if(_ot==-1){
              $("#ysb tbody :checkbox").prop("checked",false);
              $("#ysb tbody :text").prop("disabled",true);
          }else if(_ot==1){
              $("#ysb tbody :checkbox").prop("checked",true);
              $("#ysb tbody :text").prop("disabled",false);
          }
      });
  
      //改变勾选框颜色
      $(".ck").find(":checkbox").click(function(){
          if($(this).prop("checked")){
              $(this).parents().siblings("td:last").children("input").prop("disabled",false)
          }else{
              $(this).parents().siblings("td:last").children("input").prop("disabled",true)
          }
      });
  
  
      //根据位置弹出联系人
      $(".openlxr,.openlxr2").click(function(e){
                var _st=$(window).scrollTop();
                var _ot=$(this).offset().top;
                $(".modal-dialog").css({"left":130,"top":(_ot-_st-58)})
      })
  
      //点击表单验证aaaaaaaaaaaaaaaaaaaaaaaaaa
      $(".step_button,.step_button_s,.life_step").click(function(){
         $(".ysbbd:visible").valid()
      });
  
  
  
  
  
  
  
      //      自定义预留信息
      jQuery.validator.addMethod("ggg", function(value, element) {
          return this.optional(element) || /^[A-Za-z\u4e00-\u9fa5\u3002-\uff0c\d]$/.test(value);
      }, "格式错误");
  
      //  比较大小
      jQuery.validator.addMethod("xiane", function(value, element) {
          return this.optional(element) || parseInt($(".topa").html()||$(".topa").val())>=$(".topb").val();
      }, "超出上限");
  
      //  手机号码验证
      jQuery.validator.addMethod("isMobile", function(value, element) {
          var length = value.length;
          return this.optional(element) || (length == 11 && /^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/.test(value));
      }, "请正确填写您的手机号码");
  
      //  手机或者邮箱
      jQuery.validator.addMethod("sjyx", function(value, element) {
          var length = value.length;
          return this.optional(element) || /(^1[0-9]{10}$)|(^\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$)/.test(value);
      }, "请正确填写您的手机号码");
  
      //  限额大小
      jQuery.validator.addMethod("xedx", function(value, element) {
          var length = value.length;
          return this.optional(element) || $("#axe").val()<=$("#allxe").val();
      }, "大小错误");
  
      // 身份证号码验证
      jQuery.validator.addMethod("isIdCardNo", function(value, element) {
          return this.optional(element) || /^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$|^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X)$/.test(value)
      }, "请输入正确的身份证号码。");
  
      // 10的倍数
      jQuery.validator.addMethod("yushu", function(value, element) {
          return this.optional(element) || (value-parseInt(value/10)*10)==0;
      }, "请输入10的倍数");
  
      // 10的倍数
      jQuery.validator.addMethod("gou", function(value, element) {
          return this.optional(element) || $(".icheckbox_flat-blue").hasClass("checked")
      }, "请打钩");
  
  });
  
  
  
  
  
  
  
  
  

});
