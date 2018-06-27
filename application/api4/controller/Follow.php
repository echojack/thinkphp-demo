<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api4\controller;
use app\common\controller\ApiLogin;
use app\api4\service\FollowService;
use app\api4\service\UserService;
use app\api4\service\LogsService;
class Follow extends ApiLogin
{
    /**
     * 关注用户
     */
    public function dofollow(){
        $follow_id = $this->request->param('follow_id', '', 'string');
        if(!$follow_id){
            $this->wrong(0, '请输入用户id');
        }
        $follower_id = $this->user['uid'];
        if($follow_id == $follower_id){
            $this->wrong(0, '你想关注你自己？');
        }
        $remarks = ['follow_id'=>$follow_id, 'follower_id'=>$follower_id, 'intro'=>'添加关注'];

        $res = FollowService::self()->dofollows($follow_id, $follower_id);
        if($res !== true){
            LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, ERR_ACT, $remarks);
            $this->wrong(0, $res);
        }
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, SUC_ACT, $remarks);
        $this->response([], 1, '关注成功');
    }

    /**
     * 取消关注用户
     */
    public function unfollow(){
        $follow_id = $this->request->param('follow_id', 0, 'intval');
        if(!$follow_id){
            $this->wrong(0, '请输入用户id');
        }
        $follower_id = $this->user['uid'];
        if($follow_id == $follower_id){
            $this->wrong(0, '你想取消关注你自己？');
        }
        $remarks = ['follow_id'=>$follow_id, 'follower_id'=>$follower_id, 'intro'=>'取消关注'];

        $res = FollowService::self()->unfollow($follow_id, $follower_id);
        if($res !== true){
            LogsService::self()->add($this->user['uid'], $this->user['nick_name'], DEL_ACT, 0, OTHER, ERR_ACT, $remarks);
            $this->wrong(0, $res);
        }
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], DEL_ACT, 0, OTHER, SUC_ACT, $remarks);
        $this->response([], 1, 'success');
    }

    /**
     * 关注列表
     */
    public function lists(){
        $type = $this->request->param('type', 'friends', 'string');
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $uid = $this->user['uid'];
        }
        switch ($type) {
            case 'friends':
                $lists = FollowService::self()->friends($uid, $this->page, $this->limit);
                break;
            case 'follow':
                $lists = FollowService::self()->follow($uid, $this->page, $this->limit);
                break;
            case 'follower':
                $lists = FollowService::self()->follower($uid, $this->page, $this->limit);
                break;
            default:
                $lists = [];
                break;
        }

        $lists = $this->_show($lists, $this->user['uid']);
        $this->response($lists, 1, 'success');
    }
    /**
     * 展示处理
     */
    private function _show($lists = [], $current_uid = ''){
        $tmp_list = [];
        if($lists){
            foreach ($lists as $k => $uid) {
                $user = UserService::self()->detail($uid, $current_uid);
                $tmp_list[] = $user;
            }
        }
        return $tmp_list;
    }
}
