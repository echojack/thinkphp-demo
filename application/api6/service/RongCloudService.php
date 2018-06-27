<?php
namespace app\api6\service;

use rongcloud\RongCloud;
use rongcloud\methods\User;
use think\Config;

class RongCloudService 
{
    private $appKey;
    private $appSecret;
    public $RongCloud = '';

    public function __construct()
    {
        $this->appKey = Config::get('rongcloud.appKey');
        $this->appSecret = Config::get('rongcloud.appSecret');
        $this->RongCloud = new RongCloud($this->appKey,$this->appSecret);
    }

    public static function self(){
        return new self();
    }
    /**
     * 获取用户token
     */
    public function getToken($user_id = '', $user_name = '', $avatar_url = ''){
        // 获取 Token 方法
        $result = $this->RongCloud->User()->getToken($user_id, $user_name, $avatar_url);
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
        // 更新用户信息
        $result = $this->RongCloud->User()->refresh($user_id, $user_name, $avatar_url);
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
        // 更新用户信息
        $result = $this->RongCloud->Message()->broadcast($fromUserId, $objectName, $content, $pushContent, $pushData, $os);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 发送单聊消息方法（一个用户向另外一个用户发送消息，单条消息最大 128k。每分钟最多发送 6000 条信息，每次发送用户上限为 1000 人，如：一次发送 1000 人时，示为 1000 条消息。）
     */
    public function publishPrivate($fromUserId, $toUserId,  $objectName, $content, $pushContent,$pushData){
        // 更新用户信息
        $result = $this->RongCloud->Message()->publishPrivate($fromUserId, $toUserId,  $objectName, $content, $pushContent, $pushData);
        $result = json_decode($result, true);
        return $result;
    }
    /**
     * 消息历史记录下载地址获取 方法消息历史记录下载地址获取方法。获取 APP 内指定某天某小时内的所有会话消息记录的下载地址。（目前支持二人会话、讨论组、群组、聊天室、客服、系统通知消息历史记录下载） 
     * 
     * @param  date:指定北京时间某天某小时，格式为2014010101,表示：2014年1月1日凌晨1点。（必传）
     *
     * @return $json
     **/
    public function getHistory($date) {
        // 更新用户信息
        $result = $this->RongCloud->Message()->getHistory($date);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 加入黑名单
     */
    public function addBlacklist($userId = '', $blackUserId = ''){
        $result = $this->RongCloud->User()->addBlacklist($userId, $blackUserId);
        $result = json_decode($result, true);
        return $result;
    }
    /**
     * 移除黑名单
     */
    public function removeBlacklist($userId = '', $blackUserId = ''){
        $result = $this->RongCloud->User()->removeBlacklist($userId, $blackUserId);
        $result = json_decode($result, true);
        return $result;
    }
    /**
     * 发送系统消息
      发送系统消息方法（一个用户向一个或多个用户发送系统消息，单条消息最大 128k，会话类型为 SYSTEM。每秒钟最多发送 100 条消息，每次最多同时向 100 人发送，如：一次发送 100 人时，示为 100 条消息。）
     */
    public function PublishSystem($fromUserId, $toUserId,  $objectName, $content, $pushContent,$pushData){
        $result = $RongCloud->message()->PublishSystem($fromUserId, $toUserId, $objectName,$content, $pushContent, $pushData);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     *创建聊天室
     */
    public function create_room($id = '', $name = ''){
        $chatRoomInfo[$id] = $name;
        $result = $this->RongCloud->Chatroom()->create($chatRoomInfo);
        $result = json_decode($result, true);
        return $result;
    }
    /**
     *销毁聊天室
     */
    public function destroy_room($room_ids = []){
        $result = $this->RongCloud->Chatroom()->destroy($room_ids);
        $result = json_decode($result, true);
        return $result;
    }
    /**
     * 查询聊天室用户
     */
    public function lists_users($room_id = '', $count = 500, $order = 2){
        $result = $this->RongCloud->Chatroom()->queryUser($room_id, $count, $order);
        $result = json_decode($result, true);
        return $result;
    }
    /**
     * 查询聊天室
     */
    public function lists_chatroom($room_ids = []){
        if(!$room_ids){
            return [];
        }
        $result = $this->RongCloud->Chatroom()->query($room_ids);
        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 加入聊天室
     */
    public function join_room($uids = [], $room_id = ''){
        $result = $this->RongCloud->Chatroom()->join($uids, $room_id);
        $result = json_decode($result, true);
        return $result;
    }
    
}