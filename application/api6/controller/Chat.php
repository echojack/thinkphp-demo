<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\controller;
use app\common\controller\ApiLogin;
use app\api6\service\ChatService;

/**
 * Class Config
 * 地区接口
 * @package app\api6\controller
 */
class Chat extends ApiLogin {

    /**
     * 创建聊天室
     */
    public function create_room(){
        $name = $this->request->param('name', '', 'string');
        if(!$name){
            $this->wrong(0, '请输入聊天室名称');
        }
        $res = ChatService::self()->create_room($name, $this->user['uid']);
        if($res !== true){
            $this->wrong(0, $res);
        }
        $this->response([], 1, '创建成功');
    }
    /**
     *销毁聊天室
     */
    public function destroy_room(){
        $room_id = $this->request->param('room_id', '', 'string');
        if(!$room_id){
            $this->wrong(0, '请输入聊天室ID');
        }
        $res = ChatService::self()->destroy_room($room_id);
        if($res !== true){
            $this->wrong(0, $res);
        }
        $this->response([], 1, '销毁成功');
    }
    
    /**
     * 查询聊天室
     */
    public function lists_chatroom(){
        $room_id = $this->request->param('room_id', '', 'string');
        $res = ChatService::self()->lists_chatroom($room_id, $this->page, $this->limit);
        $this->response($res, 1, 'success');
    }

    /**
     * 加入聊天室
     */
    public function join_room(){
        $room_id = $this->request->param('room_id', '', 'string');
        if(!$room_id){
            $this->wrong(0, '请输入聊天室ID');
        }
        $user_id = $this->request->param('user_id', '', 'string');
        if(!$user_id){
            $this->wrong(0, '请输入用户ID');
        }
        $res = ChatService::self()->join_room($user_id, $room_id);
        if($res !== true){
            $this->wrong(0, $res);
        }
        $this->response([], 1, '加入成功');
    }

    /**
     * 查询聊天室用户
     */
    public function lists_users(){
        $room_id = $this->request->param('room_id', '', 'string');
        if(!$room_id){
            $this->wrong(0, '请输入聊天室ID');
        }
        $count = $this->request->param('count', 500, 'string');
        $order = $this->request->param('order', 2, 'string');
        $res = ChatService::self()->lists_users($room_id, $count, $order);
        $this->response($res, 1, 'success');
    }


}
