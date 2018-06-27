$(function(){
  // 点击下载跳转到对应的下载地址
  $('#download').on('click', function(){
    var u = navigator.userAgent;
    var ua = navigator.userAgent.toLowerCase();
    var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
    var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
    if(ua.match(/MicroMessenger/i) == "micromessenger" ) {   //微信内置浏览器+应用宝链接
        //跳转到下载地址  这块我写的是安卓下载地址
        window.location.href='http://tstatics.iyuwan.com/files/apk/201605/Iyuwan.apk';
    }else{
        if(isiOS){
            console.log(isiOS);
            //跳转到ios下载地址 
            window.location.href='https://itunes.apple.com/cn/app/id1250694265';
        }else if(isAndroid){
             window.location.href='http://tstatics.iyuwan.com/files/apk/201605/Iyuwan.apk';
        }else{  //PC 端
            //跳转到andriod下载地址 
            window.location.href='https://www.zhuomazaima.com';
        }
    }
  });

});