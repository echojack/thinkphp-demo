// 登录相关js
var login = {};
// 更新验证码
login.refreshVerify = function(){
    var ts = Date.parse(new Date())/1000;
    $('#verify_img').attr("src", "/index.php/captcha?id="+ts);
}


/**
 * [ajax description]
 * @param url 必填 请求url
 * @param type  必填  get/post
 * @param data  选填 请求的数据
 * @param location 成功跳转url,和success其一必填
 * @param success 执行成功回调,location其一必填
 * @param success,error,beforeSend 回调函数
 *
 * @param obj 增加 和编辑的时候用 提交时让保存按钮不可点击
 * @return 
 */
function ajax(param,obj){
    var _this = obj,
        url = param.url,
        data = param.data,
        type = param.type,
        success  = param.success,
        error    = param.error,
        beforeSend = param.beforeSend,
        location_url = param.location
        before_text = param.before_text
        after_text = param.after_text;
    if (!url || !type || (!success && !location_url)) {
        $.Huimodalalert('参数不完整',2000);
        return;
    }

    // ajax请求
    if (!data) {data = new Array()};
    if (typeof data == 'object') {
        if ( typeof data.length == 'number') {
            // 数组
            data.push({name:'inajax',value:1});
        } else {
            // 对象
            data.inajax = 1;
        }
    } else {
        data += '&inajax=1';
    }

    $.ajax({
        type  : type,
        url   : url,
        data  : data,
        cache : false,
        async : true,
        dataType : "json",
        success: function(msg){
            if (_this) {
                _this.prop('disabled',false).prop('value', before_text);
            }
            if(msg.code > 0){
                if (typeof success == 'function') {
                    success(msg);
                } else {
                    
                    setTimeout(function(){
                        location.href = location_url;
                        // history.go(-1);
                    },1000); 
                }
            }else{
                if (typeof error == 'function') {
                    error(msg);
                } else{
                    $.Huimodalalert(msg.message,2000);
                }
                return false ;              
            }
        },
        beforeSend:function(){
            if (_this) {
                _this.prop('disabled',true).prop('value', after_text);
            }
            if (typeof beforeSend == 'function') {
                beforeSend();
            }
        },
        error:function(){
            if (_this) {
                _this.prop('disabled',false).prop('value', before_text);
            }
            if (typeof error == 'function') {
                error();
            } else {
                $.Huimodalalert('网络不给力，请稍后',2000);
            }
        }
    });    
}
