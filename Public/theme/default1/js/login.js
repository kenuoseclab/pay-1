function outPwdControl(width) {
	if (checkControl()) {//innerHTML中的div去掉了
		document.getElementById("pwdControl").innerHTML='<OBJECT CLASSID="CLSID:135B5FDA-315E-47C2-8198-AC488DFD917A" id="inPwd" width="'+width+'px" height="26px" tabindex="3" CodeBase="./download/itruspta.cab#version=3.0.5.0"><PARAM NAME="ObjectName" VALUE="inPwd" /><PARAM NAME="DisplayStyle" VALUE="width:'+width+'px;height=23px;color:#000000;font-family:宋体; font-size:10px; font-style:normal" /><PARAM NAME="InputType" VALUE="password"/><PARAM NAME="MaxLength" VALUE="20"/><PARAM NAME="ShowKeyboard" VALUE="none"/></OBJECT><div style="display:none;"><OBJECT CLASSID="CLSID:F83A15A2-BAD8-465E-85C4-74ACB165924D" CodeBase="./download/itruspta.cab#version=3.0.5.0" id="SafeCtrl" width=0 height=0></OBJECT></div>';
	} else {
		document.getElementById("pwdControl").innerHTML='<b class="securitytip" style="width:155px;"><a href="./download/SecCtrlSetup.exe">点击下载安全控件</a></b><div style="display:none;"><OBJECT CLASSID="CLSID:F83A15A2-BAD8-465E-85C4-74ACB165924D" CodeBase="./download/itruspta.cab#version=2,5,3,0" ID="SafeCtrl" width=0 height=0></OBJECT></div>';
	}
}

function divShow(name, isShow) {
	$("#"+ name).removeClass("errortip");
	if (isShow == true) {
		$("#D" + name).show();
	} else {
		$("#D" + name).hide();
	}
}

function login() {
	var form = document.getElementById("pageForm");
	if (!Validator.Validate(form, 2)){return false;}
	var pwd = document.all.inPwd;
	try{
		var retval = pwd.CheckData('/^([ -~]{6,16})$/');
		if(retval !=0){alert("请输入正确的登录密码。登录密码应是数字、英文(长度6-16位)。");return;}
		document.getElementById("password").value =  crypt(pageForm.authCode.value + "|" + pwd.GetData(1,safe_cert));
		document.pageForm.submit();
	} catch(e) {
		alert("密码安全控件没有正确安装，请下载安装后再试");return;
	}
}

function commLogin() {
	var pwd = document.all.inPwd;
	var retval = pwd.CheckData('/^\s*$/g');// '/^([ -~]{6,16})$/'改成为空的正则 if(retval !=0)改成了if(retval ==0)
	if(retval ==0){alert("请输入登录密码。");return;}
	var cryptStr = crypt(pageForm.authCode.value + "|" + pwd.GetData(1,safe_cert));
	document.getElementById("password").value=cryptStr;
	return true;
	//document.pageForm.submit();
}

function mediaIdKeyDown() {
	if(event.keyCode == 13){document.getElementById("inPwd").focus();}
}

function codeKeyDown() {
	if(event.keyCode == 13){
		if (document.getElementById("mediaId_img") == null) {
			login();
		} else {
			commLogin();
		}
	}
}


function sendMsgCode(){
	
	var phoneNumber = $("#phoneNumber").val();
	var reg = /1[0-9]{10}/;
	if (reg.test(phoneNumber) != true) {
		$("#errorMsgLi").css('display','block');
		$("#errorMsg").innerHTML("手机号码格式有误，请输入正确的手机号码！")
	}else{
		
		
	}
}


var ssoUrl = document.pageForm.action + "/../";
function reloadImage(){
	var sId = "g" + parseInt(Math.random()*1000);
	document.getElementById("confirmCode").src = "confirm.img?sId=" + sId;
}
window.setTimeout("reloadImage()",100);


function parseSubject(subject) {
	var dataArray = subject.split(",");
		
	var ss1,ss2,ss3;
	for(var j = 0; j < dataArray.length; j++) {
	    var temstr = dataArray[j];
		var s1 = temstr.indexOf('O=');
		if(s1 >= 0) {
			ss1 = temstr.substring(s1 + 2);
		}
		var s2 = temstr.indexOf('OU=');
		if(s2 >= 0) {
			ss2 = temstr.substring(s2 + 3);
		}
		var s3 = temstr.indexOf('CN=');
		if(s3 >= 0) {
			ss3 = temstr.substring(s3 + 3);
		}
	}
	var resultStr = ss3 + '|' + ss1 + '|' + ss2;
	return resultStr;
}

var webm = {
      tm:null,
      tq:null,
      init:function(m,a){  //页面初始化
        function clear(o){
          if (o==null) o=m;
          if (webm.tm!=null){
            if (webm.tq!=null) {clearTimeout(webm.tq);webm.tq=null;}
            try{o.removeChild(webm.tm)}catch(e){webm.tm.style.display="none";};
            webm.tm=null;
          }
        }
        function ntitle(m,obj){
          clear(m);
          if (obj.value!="null"){
            webm.tm = document.createElement("div");var a=webm.tm;
            if(typeof(arrayCerts[i]) != "undefined"){
            a.innerHTML = webm.rlText(obj).value; //设置层中的内容;
            m.appendChild(a);
            webm.setCss(a,"text","position:absolute;cursor:pointer;background:#fff;top:"+(webm.offset(obj).top-webm.gpx(a,"height").height()-webm.gpx(obj,"height").height())+"px;left:"+(webm.offset(obj).left)+"px;border:4px #ccc solid;padding:5px").setAttr(a,"text","id:select_title");
          	}
          }
        }
        webm.evut({t:"add",o:a,s:"change",f:function(){
          clear(m);
        }}).evut({t:"add",o:a,s:"mouseover",f:function(){
          ntitle(m,a);
        }}).evut({t:"add",o:a,s:"mouseout",f:function(){
          webm.tq = setTimeout(clear,5000);
        }});
      },
      rlText:function(m){
          var v=m.value;
          return (function(){
            var a= v.split(','),i,s="";
            var index = a[a.length-1];
            for (i=0;i<a.length-1;i++){
              if (i<a.length-1){
                s+=a[i]+"<br />";
              }
            }
            if(typeof(arrayCerts[i]) != "undefined"){
            var subject = arrayCerts[index].Subject;
            var exDate = new Date(arrayCerts[i].ValidTo);
            var resubj = parseSubject(subject);
            var subArr = resubj.split('|');
            var certValue = '证书主题：' + subArr[0] + '<br />到期日：' + exDate.format('yyyy-MM-dd') + '<br />颁发者：' + subArr[1];
            return {value:certValue,index:subArr[subArr.length-1]};
            }
          })();
        },
        setCss:function(m,t,v){  //设置样式属性值或获取属性值
          if (m!=null){
            if (v!=null){
              if (t == "text") {
              var s=v.split(';'),i;
              for (i=0;i<s.length;i++){
                m.style[s[i].split(':')[0]] =s[i].split(':')[1];
              }
              } else {
                m.style[t] = v;
              }
              return webm;
            }else{
              return (m.style[t]) ? m.style[t] : null;
            }
          }else{
            return null;
          }
        },
        setAttr:function(m,t,v){  //设置标签属性值或获取属性值
          if (m!=null||m!=undefined){
            if (v!=null){
              if (t == "text") {
                var s = v.split(','), i;
                for (i = 0; i < s.length; i++) {
                  (m.setAttribute) ? m.setAttribute(s[i].split(':')[0], s[i].split(':')[1]) : m[s[i].split(':')[0]] = s[i].split(':')[1];
                }
              }else {
                (m.setAttribute) ? m.setAttribute(t, v) : m[t] = v;
              }
              return webm;
            }else{
              return (m.getAttribute) ? m.getAttribute(t) : m[t];
            }
          }else{
            return null;
          }
        },
        isArray:function(v){
            return (v!=null) ? (v.constructor==Array) ? true : false : false;
          },
          isF:function(v){
            return (v!=null) ? typeof v === "function" : false;
          },
          isO:function(v){
            return (v!=null) ? typeof v === "object" : false;
          },
          isW: function(v) {
            return v && typeof v === "object" && "setInterval" in v;
           },
           isNaN: function(v) {
            webm.rdigit = /\d/;
            return v == null || !webm.rdigit.test( v ) || isNaN( v );
           },
           isEmpty:function(v){
            var reg = /^\s*$/;
            return reg.test(v);
           },
           isN:function(v){
            var nodecol = ",a,abbr,acronym,address,applet,area,b,base,basefont,bdo,big,blockquote,body,br,button,caption,center,cite,code,col,colgroup,dd,del,dir,div,dfn,dl,dt,em,fieldset,font,form,frame,frameset,h1,h2,h3,h4,h5,h6,head,hr,html,i,iframe,img,input,ins,isindex,kbd,label,legend,li,link,map,menu,meta,noframes,noscript,object,ol,optgroup,option,p,param,pre,q,s,samp,script,select,small,span,strike,strong,style,sub,sup,table,tbody,td,textarea,tfoot,th, thead,title,tr,tt,u,ul,var,item,pubDate,author,description,";
            nodecol +="abbr,figcaption,mark,output,summary,article,aside,audio,canvas,command,hgroup,datagrid,datalist,datatemplate,details,dialog,embed,event-source,figure,footer,header,m,meter,nav,nest,output,progress,rule,section,time,video,",isNbool =false;
            return isNbool = (nodecol.indexOf(","+v.toLowerCase()+",")>-1) ? true : false;
          },
          isS:function(v){
            return (v!=null) ? typeof v === "string" : false;
          },
          isNum:function(v){
            return (v!=null) ? typeof v === "number" : false;
          },
        evut:function(_o){
          function evut(stype,oTarget,sEventType,fnHandler){
            var eventUtil = {};
            //事件绑定
            eventUtil.addEventHandler = function(){
              //IE和FF的兼容性处理
              if(oTarget.addEventListener){//如果是FF
                oTarget.addEventListener(sEventType,fnHandler,false);
              } else if(oTarget.attachEvent){//如果是IE
                oTarget.attachEvent('on'+sEventType,fnHandler);
              } else{
                oTarget['on'+sEventType] = fnHandler;
              }
            };
            //事件移除
            eventUtil.removeEventHandler = function(){
              if(oTarget.removeEventListener) {//如果是FF
                oTarget.removeEventListener(sEventType,fnHandler,false);
              } else if (oTarget.detachEvent) {//如果是IE
                oTarget.detachEvent('on'+sEventType,fnHandler);
              } else {
                oTarget['on'+sEventType] = null;
              }
            };
            (stype=="add") ? eventUtil.addEventHandler() : (stype=="del") ? eventUtil.removeEventHandler() : null;
            return webm;
          }
          return evut(_o.t,_o.o,_o.s,_o.f);
        },
      offset:function(m){
            function initialize(){
              var body = document.body, container = document.createElement("div"), innerDiv, checkDiv, table, td, bodyMarginTop = parseFloat( webm.setCss(document.body,"marginTop") ) || 0,
   html = "<div style='position:absolute;top:0;left:0;margin:0;border:5px solid #000;padding:0;width:1px;height:1px;'><div></div></div><table style='position:absolute;top:0;left:0;margin:0;border:5px solid #000;padding:0;width:1px;height:1px;' cellpadding='0' cellspacing='0'><tr><td></td></tr></table>";
              webm.setCss(container,"text","position:absolute;top:0;left:0;margin:0;border:0;width:1px;height:1px;visibility:hidden");
              container.innerHTML = html;
              body.insertBefore( container, body.firstChild );
              innerDiv = container.firstChild;
              checkDiv = innerDiv.firstChild;
              td = innerDiv.nextSibling.firstChild.firstChild;
              webm.doesNotAddBorder = (checkDiv.offsetTop !== 5);
              webm.doesAddBorderForTableAndCells = (td.offsetTop === 5);
              checkDiv.style.position = "fixed";
              checkDiv.style.top = "20px";
              // safari subtracts parent border width here which is 5px
              webm.supportsFixedPosition = (checkDiv.offsetTop === 20 || checkDiv.offsetTop === 15);
              checkDiv.style.position = checkDiv.style.top = "";
              innerDiv.style.overflow = "hidden";
              innerDiv.style.position = "relative";
              webm.subtractsBorderForOverflowNotVisible = (checkDiv.offsetTop === -5);
              webm.doesNotIncludeMarginInBodyOffset = (body.offsetTop !== bodyMarginTop);
              body.removeChild( container );
              body = container = innerDiv = checkDiv = table = td = null;
            }
            function bodyOffset(body){
              var top = body.offsetTop,
              left = body.offsetLeft;
              initialize();
              if ( webm.doesNotIncludeMarginInBodyOffset ){
               top  += parseFloat( webm.setCss(document.body,"marginTop") ) || 0;
               left += parseFloat(webm.setCss(document.body,"marginLeft") ) || 0;
              }
              return { top: top, left: left };
             }
            if (m===document.body){return bodyOffset(m)};
            initialize();
            var offsetParent = m.parentNode,prevOffsetParent = m,prevComputedStyle = document.defaultView ? document.defaultView.getComputedStyle(m,null) : m.currentStyle,computedStyle,rtable = /^t(?:able|d|h)$/i;
            if (m){
              var x=m.offsetLeft,y=m.offsetTop;
              while((m=m.parentNode)&&m!=document.body&&m!=document.documentElement){
                 if ( webm.supportsFixedPosition && prevComputedStyle.position === "fixed" ) {
                  break;
                 }
                computedStyle = document.defaultView ? document.defaultView.getComputedStyle(m, null) : m.currentStyle;
                x-=m.scrollLeft;
                y-=m.scrollTop;
                if (m===m.parentNode){
                  x+=m.offsetLeft;
                  y+=m.offsetTop;
                  if (webm.doesNotAddBorder && !(webm.doesAddBorderForTableAndCells && rtable.test(m.nodeName)) ) {
                     y += parseFloat( computedStyle.borderTopWidth  ) || 0;
                     x += parseFloat( computedStyle.borderLeftWidth ) || 0;
                  }
                  prevOffsetParent = offsetParent;
                  offsetParent = m.parentNode;
                }
                 if ( webm.subtractsBorderForOverflowNotVisible && computedStyle.overflow !== "visible" ) {
                  y += parseFloat( computedStyle.borderTopWidth  ) || 0;
                  x += parseFloat( computedStyle.borderLeftWidth ) || 0;
                 }
                 prevComputedStyle = computedStyle;
              }
              if ( prevComputedStyle.position === "relative" || prevComputedStyle.position === "static" ) {
               y  += document.body.offsetTop;
               x += document.body.offsetLeft;
              }
              if ( webm.supportsFixedPosition && prevComputedStyle.position === "fixed" ) {
               y  += Math.max( document.documentElement.scrollTop, document.body.scrollTop );
               x += Math.max( document.documentElement.scrollLeft, document.body.scrollLeft );
              }
              return{left:x||0,top:y||0};
            }else{
              return null;
            }
          },
          rpnum:function(v){
            v=v+"";
            if (v.indexOf(' ')>-1){
            var gv=v.split(' '),i,gvl = gv.length,rv=null;
            for (i=0;i<gvl;i++){
              if (gv[i].indexOf("px")>-1){
                rv=gv[i].replace("px","");break;
              }
            }
            return rv;
            }else{return v;}
          },
          gpx:function(m,n){
            var e = {};
            e["outer"+n]=function(margin){
              return m ? (n=="width") ? (((margin) ? parseFloat(webm.rpnum(webm.setCss(m,"marginLeft")||0))+parseFloat(webm.rpnum(webm.setCss(m,"marginRight")||0)) : 0)+parseFloat(webm.rpnum(webm.setCss(m,"borderLeft")||0))+parseFloat(webm.rpnum(webm.setCss(m,"borderRight")||0))) : (((margin) ? parseFloat(webm.rpnum(webm.setCss(m,"marginTop")||0))+parseFloat(webm.rpnum(webm.setCss(m,"marginBottom")||0)) : 0)+parseFloat(webm.rpnum(webm.setCss(m,"borderTop")||0))+parseFloat(webm.rpnum(webm.setCss(m,"borderBottom")||0))) : null;
            };
            e["inner"+n]=function(){
              return m ? (n=="width") ? (parseFloat(webm.setCss(m,"paddingLeft")||0)+parseFloat(webm.setCss(m,"paddingRight")||0)) : (parseFloat(webm.setCss(m,"paddingTop")||0)+parseFloat(webm.setCss(m,"paddingBottom")||0)) : null;
            };
            e["scroll"+n]=function(v){
              n = (n=="left") ? "Left" : (n=="top") ? "Top" : (n=="height") ? "Height" : (n=="width") ? "Width" : n;
              var method = "scroll" + n,win = webm.gWin(m);
              if (v){
                if (win) {
                  win.scrollTo((n=="Left") ? v : webm.gpx(m,"Left").scrollLeft(),(n=="Top") ? v : webm.gpx(m,"Top").scrollTop());
                }else{
                  (n=="Left") ? m.scrollLeft = v : (n=="Top") ? m.scrollTop = v : (n=="Width") ? m.scrollWidth = v : (n=="Height") ? m.scrollHeight = v : null;
                }
              }else{
                return win ? win.document.documentElement[ method ] || win.document.body[ method ] : m[ method ];
              }
            };
            e[n] = function(size){
              if (m){
                n = (n=="width") ? "Width" : (n=="height") ? "Height" : n;
                if(webm.isW(m)){
                  var docElemProp = m.document.documentElement[ "client" + n ];
                  return m.document.compatMode === "CSS1Compat" && docElemProp || m.document.body[ "client" + n ] || docElemProp;
                }else if (m === document.body) {
                  m = document;
                  return Math.max(m.documentElement["client" + n],m.body["scroll" + n], m.documentElement["scroll" + n],m.body["offset" + n], m.documentElement["offset" + n]);
                }else if (size === undefined){
                  var f = (webm.setCss(m,n)) ? parseFloat(webm.setCss(m,n))||0 : m["offset"+n],g=parseFloat(f);
                  return (f!=""||webm.isEmpty(f)==false) ? webm.isNaN(g) ? f : g : null;
                }else{
                  return webm.setCss(m,n,typeof size === "string" ? size : size + "px");
                }
              }else{
                return null;
              }
            };
            return e;
          },
          gWin:function(elem){
            return webm.isW(elem) ? elem : elem.nodeType === 9 ? elem.defaultView || elem.parentWindow : false;
          },
          gnav:function(){
            var na = navigator.appName,nv = navigator.appVersion,navname,naver,bs = webm.uaMatch().bs,vs = webm.uaMatch().vs;
            if (bs==""&&vs=="0"){
              navname = (na == "Microsoft Internet Explorer") ? "ie" : ((na == "Netscape") ? "ns" : ((na == "Opera") ? "op" : null));
              navname = (nv.indexOf("Chrome")>-1) ? "wk" : navname;
              naver = (nv.indexOf("MSIE 6.0")>-1) ? "6" : ((nv.indexOf("MSIE 7.0")>-1) ? "7" : ((nv.indexOf("MSIE 8.0")>-1) ? "8" : ((nv.indexOf("MSIE 9.0")>-1) ? "9" : ((nv.indexOf("MSIE 5.0")>-1) ? "5" : null))));
            }else{
              navname = (bs=="mozilla") ? "ns" : (bs=="msie") ? "ie" : (bs=="opera") ? "op" : (bs=="webkit") ? "wk" : bs;
              naver = vs.substr(0,vs.indexOf('.'));
            }
            return (naver!=null) ? (navname+naver) : navname;
          },
          uaMatch: function() {
            var rwebkit = /(webkit)[ \/]([\w.]+)/,
             ropera = /(opera)(?:.*version)?[ \/]([\w.]+)/,
             rmsie = /(msie) ([\w.]+)/,
             rmozilla = /(mozilla)(?:.*? rv:([\w.]+))?/,
            ua = navigator.userAgent.toLowerCase();
            var match = rwebkit.exec( ua ) ||
             ropera.exec( ua ) ||
             rmsie.exec( ua ) ||
             ua.indexOf("compatible") < 0 && rmozilla.exec( ua ) ||
             [];
            return { bs: match[1] || "", vs: match[2] || "0" };
           }
    };
    var a=document.getElementById("seldata"),m=document.getElementById("m");
    //webm.init(m,a); //页面初始化