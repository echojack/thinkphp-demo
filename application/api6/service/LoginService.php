<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\service;

use app\common\service\ToolService;
use app\common\service\UserService;
use app\api6\model\UserModel;
use app\api6\model\UserExtModel;
use think\Config;
class LoginService {
    public static function login($user){
        $token = ToolService::token($user['login_name']);
        $data = [
            'token'=>$token,
            'last_login_ip'=>get_client_ip(),
            'last_login_time'=>time()
        ];
        
        if(UserModel::self()->updateUser($data,$user['uid'])){
            return self::doLogin($user['uid'],$token);
        }
        return 500;
    }

    public static function doLogin($user_id,$token){
        // 用户登录信息
        $user = UserModel::self()->detail_simple($user_id);
	// 注册引导页 1
        $user['step'] = 0;
        if(!$user['nick_name'] || !$user['sex']){
            $user['step'] = 1;
        }else if(!$user['avatar']){// 注册引导页 2
	        $user['step'] = 2;
        }else{
            // 用户必须关注 4个圈子才能进入
            $circle_count = CircleService::self()->my_circle_count($user_id);
            if($circle_count < 4){
                $user['step'] = 3;
            }
        }
        $setting_token = Config::get('setting.token', false);
        $expire = isset($setting_token['token_expire'])?$setting_token['token_expire']:3600;
        UserService::setToken($token,$user,$expire);
        return $user;
    }

    /**
     * 第三方登录
     */
    public static function other_login($user = [], $type = ''){
        if(!$user || !$type){
            return false;
        }
        switch ($type) {
            case 'wx':
                $token = ToolService::token($user['wx_identify']);
                break;
            case 'wb':
                $token = ToolService::token($user['wb_identify']);
                break;
            case 'qq':
                $token = ToolService::token($user['qq_identify']);
                break;
    	    default:
                $token = '';
    		break;
        }

        $data = [
            'token'=>$token,
            'last_login_ip'=>get_client_ip(),
            'last_login_time'=>time()
        ];

        if(UserModel::self()->updateUser($data,$user['uid'])){
            return self::doLogin($user['uid'],$token);
        }
        return 500;
    }
}
