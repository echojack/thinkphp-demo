window.addEventListener('DOMContentLoaded', function (){
	var shuping = 'onorientationchange' in window ? 'orientationchange' : 'resize';
	var isAndorid = /(Android)/i.test(navigator.userAgent);
	var timer = null;

	//设置字体
	function setFontSize(){
		var w = window.innerWidth;
		//设计图 宽度是750  --------------------> 对应/750, 1rem = 100px;(css已设置)
		document.documentElement.style.fontSize = 100*w/750 + 'px';
	}
	setFontSize();

	//手机横竖屏时 改变大小，Andorid手机切换有延迟 故开定时器
	window.addEventListener(shuping, function (){
		clearTimeout(timer);
		timer = setTimeout(setFontSize, isAndorid?300:0);
	}, false);
}, false);