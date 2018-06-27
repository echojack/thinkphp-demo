<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\service;
use think\Config;
use think\Cache;

use app\common\model\ChatModel;
use app\api8\service\UserService;
use app\api8\service\RongCloudService;


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
            return $id;
        }
        return '创建聊天室失败，请稍后再试';
    }
    /**
     *销毁聊天室
     */
    public function destroy_room($chrmId = ''){
        if(!is_array($chrmId)){
            $chrmId = explode(',', $chrmId);
        }
        $res = ChatModel::self()->del_room($chrmId);
        if($res){
            $res = RongCloudService::self()->destroy_room($chrmId);
            if($res && $res['code'] == '200'){
                return true;
            }
        }
        return '销毁聊天室失败，请稍后再试';
    }
    
    /**
     * 查询聊天室
     * chrmId 查询指定聊天室
     */
    public function lists_chatroom($chrmId = '', $page = 1, $limit = 10){
        $list = [];
        $ids = [$chrmId];
        if(!$chrmId){
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
                    $tmp_list[$k] = $rong_list[$val['id']];

                    $tmp_list[$k]['hot'] = 0;
                    if($tmp_list[$k]['total'] > 50){
                        $tmp_list[$k]['hot'] = 1;
                    }
                    $tmp_list[$k]['bg_url'] = Config::get('img_url').'static/images/bg_chat/'.rand(1, 10).'.png';
                }else{
                    $tmp_list[$k] = [
                        'chrmId' => $val['id'],
                        'name' => $val['name'],
                        'time' => date('Y-m-d H:i:s', $val['created_at']),
                        'total' => 0,
                        'flag' => 0,
                        'hot' => 0,
                        'bg_url' => Config::get('img_url').'static/images/bg_chat/'.rand(1, 10).'.png',
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
    public function join_room($user_id, $chrmId){
        if(!is_array($user_id)){
            $user_id = explode(',', $user_id);
        }
        $res = RongCloudService::self()->join_room($user_id, $chrmId);
        if($res && $res['code'] == '200'){
            return true;
        }
        return '加入聊天室失败';
    }
    /**
     * 查询聊天室用户
     */
    public function lists_users($chrmId = '', $count = 500, $order = 2){
        $return['total'] = 0;
        $return['users'] = [];
        $res = RongCloudService::self()->lists_users($chrmId, $count, $order);
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