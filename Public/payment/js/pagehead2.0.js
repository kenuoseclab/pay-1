var dpr, rem, scale;
var docEl = document.documentElement;
var fontEl = document.createElement('style');
var metaEl = document.querySelector('meta[name="viewport"]');
dpr = window.devicePixelRatio || 1;
rem = docEl.clientWidth / 10;
scale = 1 / dpr;


docEl.setAttribute('data-dpr', dpr);
// 动态写入样式
if((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
		metaEl.setAttribute('content', 'width=' + docEl.clientWidth + ',initial-scale=1,maximum-scale=1, minimum-scale=1,user-scalable=no');
		docEl.firstElementChild.appendChild(fontEl);
		fontEl.innerHTML = 'html{font-size:' + rem + 'px!important;}';
}
else{
		metaEl.setAttribute('content', 'width=320 ,initial-scale=1,maximum-scale=1, minimum-scale=1,user-scalable=no');
		rem = 32;
		docEl.firstElementChild.appendChild(fontEl);
		fontEl.innerHTML = 'html{font-size:32px!important;}body{width:320px;margin:0 auto}';
}

