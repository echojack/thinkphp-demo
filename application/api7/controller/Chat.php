<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api7\controller;
use app\common\controller\ApiLogin;
use app\api7\service\ChatService;

/**
 * Class Config
 * 地区接口
 * @package app\api7\controller
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
        $chrmId = ChatService::self()->create_room($name, $this->user['uid']);
        if(!intval($chrmId)){
            $this->wrong(0, $chrmId);
        }
        $this->response(['chrmId'=>$chrmId], 1, '创建成功');
    }
    /**
     *销毁聊天室
     */
    public function destroy_room(){
        $chrmId = $this->request->param('chrmId', '', 'string');
        if(!$chrmId){
            $this->wrong(0, '请输入聊天室ID');
        }
        $res = ChatService::self()->destroy_room($chrmId);
        if($res !== true){
            $this->wrong(0, $res);
        }
        $this->response([], 1, '销毁成功');
    }
    
    /**
     * 查询聊天室
     */
    public function lists_chatroom(){
        $chrmId = $this->request->param('chrmId', '', 'string');
        $res = ChatService::self()->lists_chatroom($chrmId, $this->page, $this->limit);
        $this->response($res, 1, 'success');
    }

    /**
     * 加入聊天室
     */
    public function join_room(){
        $chrmId = $this->request->param('chrmId', '', 'string');
        if(!$chrmId){
            $this->wrong(0, '请输入聊天室ID');
        }
        $user_id = $this->request->param('user_id', '', 'string');
        if(!$user_id){
            $this->wrong(0, '请输入用户ID');
        }
        $res = ChatService::self()->join_room($user_id, $chrmId);
        if($res !== true){
            $this->wrong(0, $res);
        }
        $this->response([], 1, '加入成功');
    }

    /**
     * 查询聊天室用户
     */
    public function lists_users(){
        $chrmId = $this->request->param('chrmId', '', 'string');
        if(!$chrmId){
            $this->wrong(0, '请输入聊天室ID');
        }
        $count = $this->request->param('count', 500, 'string');
        $order = $this->request->param('order', 2, 'string');
        $res = ChatService::self()->lists_users($chrmId, $count, $order);
        $this->response($res, 1, 'success');
    }


}
