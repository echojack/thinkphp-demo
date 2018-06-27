<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api4\controller;

use app\common\controller\ApiLogin ;
use app\api4\service\ServicesService;
use app\api4\service\UserService;
use app\common\model\MessageModel;
use app\api4\service\CircleService;
/**
 * Class Get
 * @package app\api4\controller
 * 获取详细信息
 */
class Detail extends ApiLogin {
    /**
     * 服务详情
     * @method post
     * @parameter string token 必须
     */
    public function service(){
        $id = $this->request->param('id', 0, 'intval');
        if(!$id){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $detail = ServicesService::self()->service_detail($id, $this->user['uid']);
        if(!$detail){
            $this->wrong(0, '服务不存在或已被删除');
        }
        $this->response($detail, 1, 'success');
    }

    /**
     * 邀约详情
     * @method post
     * @parameter string token 必须
     */
    public function demand(){
        $id = $this->request->param('id', 0, 'intval');
        if(!$id){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $detail = ServicesService::self()->demand_detail($id, $this->user['uid']);
        if(!$detail){
            $this->wrong(0, '邀约不存在或已被删除');
        }
        $this->response($detail, 1, 'success');
    }
    /**
     * 用户个人主页信息
     */
    public function user(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $user = UserService::self()->detail($uid, $this->user['uid']);
        // 获取用户 服务信息
        $param['created_uid'] = $uid;
        $param['type'] = 1;
	$param['uid'] = $this->user['uid'];
        $user['service_count'] = ServicesService::self()->lists_count($uid, 1);
        $user['service'] = ServicesService::self()->service_lists($param,1, 2);
        // 邀约 信息
        $param['type'] = 2;
        $user['demand_count'] = ServicesService::self()->lists_count($uid, 2);
        $user['demand'] = ServicesService::self()->demand_lists($param, 1, 2);
        // 帖子
        $param['type'] = 1;
        $param['uid'] = $uid;
        $user['my_posts_count'] = CircleService::self()->my_posts_count($param);
        $user['my_posts'] = CircleService::self()->my_posts($param, 1,4);
        // 动态
        $param['type'] = 2;
        $user['my_topic_posts_count'] = CircleService::self()->my_posts_count($param);
        $user['my_topic_posts'] = CircleService::self()->my_posts($param, 1, 4);
	$this->response($user);
    }
    
    /**
     *  类似未读消息总数 提醒数据
     */
    public function remind(){
        // 未读消息总数提醒
        $message_unread = MessageModel::self()->get_message_count($this->user['uid']);
        $return['message_unread'] = $message_unread;
        $this->response($return, 1, 'success');
    }
}
