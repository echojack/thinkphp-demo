<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\controller;
use app\common\controller\ApiLogin;
use app\api2\service\FollowService;
use app\api2\service\UserService;
class Follow extends ApiLogin
{
    /**
     * 关注用户
     */
    public function dofollow(){
        $follow_id = $this->request->param('follow_id', 0, 'intval');
        if(!$follow_id){
            $this->wrong(0, '请输入用户id');
        }
        $follower_id = $this->user['uid'];
        if($follow_id == $follower_id){
            $this->wrong(0, '你想关注你自己？');
        }

        $res = FollowService::self()->dofollow($follow_id, $follower_id);
        if($res !== true){
            $this->wrong(0, $res);
        }
        $this->response([], 1, 'success');
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

        $res = FollowService::self()->unfollow($follow_id, $follower_id);
        if($res !== true){
            $this->wrong(0, $res);
        }
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
