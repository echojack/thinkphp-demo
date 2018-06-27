<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\service;

use think\Config;
use think\Cache;

class UserService {

    public static function setToken($token,$user,$expire=0){
        $user_id = $user['uid'];
        $user = serialize($user);
        if(empty($expire)){
            $setting_token = Config::get('setting.token');
            $expire = isset($setting_token['token_expire'])?$setting_token['token_expire']:3600;
        }
        $expire = empty($expire)?3600:$expire;
        Cache::set('login_token'.$user_id, $token, $expire, false);
        return Cache::set($token,$user,$expire, false);
    }

    public static function checkToken($token){
        $user = Cache::get($token, false);
        if(empty($user)){
            return 403101; // token timeout
        }
        $user = unserialize($user);
        $user_id = $user['uid'];
        $hash_token = Cache::get('login_token'.$user_id, false);
        if($hash_token!==$token){
            return 403100; // other login
        }
        return $user; //pass
    }


    public static function updateTokenExpire($token){
	    $user = Cache::get($token);
        $setting_token = Config::get('setting.token', false);
        $expire = isset($setting_token['token_expire'])?$setting_token['token_expire']:3600;
        return Cache::set($token,$user,$expire, false);
    }

    /**
     * 验证码记录
     * 一分钟内最多发送 5次
     */
    public static function logVerifyCode($mobile,$code,$time=300){
        // 禁止频繁发送验证码
        $code_time = Cache::get('verify_code_counter_start'.$mobile, false);
        if($code_time){
            $count = Cache::inc('verify_code_counter_'.$mobile, 1, false);
            if($count > 5){
                return '发送验证码太频繁，请稍后再试！';
            }
        } else{
            Cache::rm('verify_code_counter_'.$mobile, false);
            Cache::set('verify_code_counter_start'.$mobile, 1, 60, false);//一分钟内 只能 发送 5次验证码
        }       
        Cache::set($mobile,$code,$time, false);
        return $code;
    }
    /**
     * 验证码验证，三分钟内登录有效
     * 登录成功一次 验证码 即失效
     */
    public static function checkVerifyCode($mobile,$code){
        // 配置文件 是否真实发送短信
        $sms_code = \think\Env::get('send.sms_code');
        if(!$sms_code){
            return 1;
        }

        $log_code = Cache::get($mobile, false);
        if(empty($log_code)){
            return '验证码不存在或已失效';
        }
        if($log_code!=$code){
            return '验证码错误';   
        }
        // 清除验证码计数
        Cache::rm('verify_code_counter_'.$mobile, false);
        // 删除验证码
        Cache::rm($mobile, false);
        return 1;
    }

}
