<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\service;

use app\common\service\ToolService;
use app\common\model\UserModel;
use think\Config;
use think\Cache;
use think\helper\Hash;
class LoginService {
    public static function login($user = '', $login_pass = '', $online = 1){
//        if($user['login_pass'] != make_password($login_pass,$user['user_uniq'])){
            if($user['login_pass'] != md5($login_pass)){
            return '用户名和密码不匹配';
        }

        $token = ToolService::token($user['login_name']);
        $data = [
            // 'token'=>$token,
            'last_login_ip'=>get_client_ip(),
            'last_login_time'=>time()
        ];
        
        if(UserModel::self()->updateUser($data,$user['uid'])){
            return self::doLogin($user['uid'],$token, $online);
        }
        return '更新登录信息失败';
    }

    public static function doLogin($user_id,$token, $online = 1){
        // 用户登录信息
        $user = UserModel::self()->login_data($user_id);
        if(!$user){
            return [];
        }
        $login_session['nick_name'] = $user['nick_name'];
        $login_session['login_name'] = $user['login_name'];
        $login_session['user_key'] = Hash::make($user['uid'].','.$user['user_uniq'].','.$user['token']);
        // 默认 连续登录一小时后失效
        $expire_time = 60*60;
        if($online){
            $expire_time = 30*24*60*60;
        }
        // 设置sessionid 
        if (PHP_SESSION_ACTIVE != session_status()) {
            session_start();
        }
        Cache::set(session_id(), $user_id);
        Cache::set('user_login_'.$user_id, $login_session, $expire_time);
        // 返回后台用户登录信息
        $return['uid'] = $user['uid'];
        $return['nick_name'] = $user['nick_name'];
        return $return;
    }

    /**
     * 检测登录
     */
    public static function checkLogin(){
        if (PHP_SESSION_ACTIVE != session_status()) {
            session_start();
        }
        $session_id = session_id();
        $user_id = Cache::get($session_id);
        $user = Cache::get('user_login_'.$user_id);
        if(!$user){
            return false;
        }
        $hash_value = $user['user_key'];
        $login_name = $user['login_name'];
        $user = UserModel::self()->get_user_by_login_name($login_name);
        $key = $user['uid'].','.$user['user_uniq'].','.$user['token'];
        $res = Hash::check($key, $hash_value);
        if(!$res){
            return false;
        }
        return $user;
    }

}
