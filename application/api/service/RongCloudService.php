<?php
namespace app\api\service;

use rongcloud\RongCloud;
use rongcloud\methods\User;
use think\Config;

class RongCloudService 
{
    private $appKey;
    private $appSecret;

    public function __construct()
    {
        $this->appKey = Config::get('rongcloud.appKey');
        $this->appSecret = Config::get('rongcloud.appSecret');
    }

    public static function self(){
        return new self();
    }
    /**
     * 获取用户token
     */
    public function getToken($user_id = '', $user_name = '', $avatar_url = ''){
        $RongCloud = new RongCloud($this->appKey,$this->appSecret);
        // 获取 Token 方法
        $result = $RongCloud->User()->getToken($user_id, $user_name, $avatar_url);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 刷新用户信息
     */
    public function refresh($user_id , $user_name = '', $avatar_url = ''){
        if(!$user_id){
            return false;
        }
        $RongCloud = new RongCloud($this->appKey,$this->appSecret);
        // 更新用户信息
        $result = $RongCloud->User()->refresh($user_id, $user_name, $avatar_url);
        $result = json_decode($result, true);
        return $result;
    }
    /**
     * 群发消息
     * 消息类型  文本消息   RC:TxtMsg   {"content":"hello","extra":"helloExtra"}
     * 图片消息 RC:ImgMsg   {"content":"ergaqreg", "imageUri":"http://www.demo.com/1.jpg","extra":"helloExtra"}
     *
     */
    public function broadcast($fromUserId,  $objectName, $content, $pushContent = '', $pushData = '', $os = ''){
        if(!$user_id){
            return false;
        }
        $RongCloud = new RongCloud($this->appKey,$this->appSecret);
        // 更新用户信息
        $result = $RongCloud->Message()->broadcast($fromUserId, $objectName, $content, $pushContent, $pushData, $os);
        $result = json_decode($result, true);
        return $result;
    }

}