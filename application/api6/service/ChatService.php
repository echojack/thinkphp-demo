<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\service;
use think\Config;
use think\Cache;

use app\common\model\ChatModel;
use app\api6\service\UserService;
use app\api6\service\RongCloudService;


class ChatService {
    public static function self(){
        return new self();
    }

    /**
     * 创建聊天室
     */
    public function create_room($name = '', $uid = ''){
        $id = ChatModel::self()->create_room($name, $uid);
        $res = RongCloudService::self()->create_room($id, $name);
        if($res && $res['code'] == '200'){
            return true;
        }
        return '创建聊天室失败，请稍后再试';
    }
    /**
     *销毁聊天室
     */
    public function destroy_room($room_id = ''){
        if(!is_array($room_id)){
            $room_id = explode(',', $room_id);
        }
        $res = ChatModel::self()->del_room($room_id);
        if($res){
            $res = RongCloudService::self()->destroy_room($room_id);
            if($res && $res['code'] == '200'){
                return true;
            }
        }
        return '销毁聊天室失败，请稍后再试';
    }
    
    /**
     * 查询聊天室
     * room_id 查询指定聊天室
     */
    public function lists_chatroom($room_id = '', $page = 1, $limit = 10){
        $ids = [$room_id];
        if(!$room_id){
            $list = ChatModel::self()->lists_chatroom($page, $limit);
            $ids = array_column($list, 'id');
        }
        // 查询融云存在的聊天室
        $rong_list = [];
        $res = RongCloudService::self()->lists_chatroom($ids);
        if($res && $res['code'] == '200'){
            $chatRooms = $res['chatRooms'];
            if($chatRooms){
                foreach ($chatRooms as $v) {
                    $users = $this->lists_users($v['chrmId'], 6, 2);
                    $rong_list[$v['chrmId']] = array_merge($v, $users);
                }
            }
        }
        // 处理聊天室数据
        $tmp_list = [];
        if($list){
            foreach ($list as $k => $val) {
                if(isset($rong_list[$val['id']])){
                    $tmp_list[] = $rong_list[$val['id']];
                }else{
                    $tmp_list[] = [
                        'chrmId' => $val['id'],
                        'name' => $val['name'],
                        'time' => date('Y-m-d H:i:s', $val['created_at']),
                        'total' => 0,
                        'flag' => 0,
                        'users' => [],
                    ];
                }
            }
        }
        return $tmp_list;
    }
    /**
     * 加入聊天室
     */
    public function join_room($user_id, $room_id){
        if(!is_array($user_id)){
            $user_id = explode(',', $user_id);
        }
        $res = RongCloudService::self()->join_room($user_id, $room_id);
        if($res && $res['code'] == '200'){
            return true;
        }
        return '加入聊天室失败';
    }
    /**
     * 查询聊天室用户
     */
    public function lists_users($room_id = '', $count = 500, $order = 2){
        $return['total'] = 0;
        $return['users'] = [];
        $res = RongCloudService::self()->lists_users($room_id, $count, $order);
        if($res && $res['code'] == '200'){
            $return['total'] = $res['total'];
            $tmp_users = $res['users'];
            if($tmp_users){
                foreach ($tmp_users as $k => $v) {
                    $user = UserService::self()->simple_detail($v['id']);
                    $user['join_time'] = $v['time'];
                    $return['users'][] = $user;
                }
            }
        }
        return $return;
    }
}