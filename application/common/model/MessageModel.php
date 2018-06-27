<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;

use think\Model;

class MessageModel extends Model{
    protected $name = "message";

    public static function self(){
        return new self();
    }

    /**
     *获取评论消息 1:评论帖子；2：评论动态；
     */
    public function get_posts_message($uid, $page = 1, $limit = 10){
        $where['message.type'] = ['IN', [1, 2]];
        $where['message.to_uid'] = $uid;
        $where['message.is_del'] = 0;
        $lists = $this->field('message.*')
                ->where($where)
                ->order('message.created_at DESC')
                ->page($page, $limit)->select();
        return $lists;
    }
    /**
     *获取未读评论消息数 1:评论帖子；2：评论动态；
     */
    public function get_posts_message_count($uid){
        $where['message.type'] = ['IN', [1, 2]];
        $where['message.to_uid'] = $uid;
        $where['message.is_del'] = 0;
        $where['message.status'] = 0;
        $count = $this->where($where)->count();
        // 修改为已读状态
        $this->where($where)->update(['message.status'=>1]);
        return $count;
    }

    /**
     * 获取回复消息 3：回复帖子；4：回复动态；
     */
    public function get_comments_message($uid, $page = 1, $limit = 10){
        $where['message.type'] = ['IN', [3, 4]];
        $where['message.to_uid'] = $uid;
        $where['message.is_del'] = 0;
        $lists = $this->field('*')
                ->where($where)
                ->order('message.created_at DESC')
                ->page($page, $limit)->select();
        return $lists;
    }
    /**
     * 获取回复消息 3：回复帖子；4：回复动态；
     */
    public function get_comments_count($uid){
        $where['message.type'] = ['IN', [3, 4]];
        $where['message.to_uid'] = $uid;
        $where['message.is_del'] = 0;
        $where['message.status'] = 0;
        $count = $this->where($where)->count();
        // 修改为已读状态
        $this->where($where)->update(['message.status'=>1]);
        return $count;
    }

    /**
     * 获取点赞消息 5：点赞帖子；6：点赞动态；7：点赞评论
     */
    public function get_digg_message($uid, $page = 1, $limit = 10){
        $where['message.type'] = ['IN', [5, 6, 7]];
        $where['message.to_uid'] = $uid;
        $where['message.is_del'] = 0;
        $lists = $this->field('*')
                ->where($where)
                ->order('message.created_at DESC')
                ->page($page, $limit)->select();
        return $lists;
    }
    /**
     * 获取点赞消息 5：点赞帖子；6：点赞动态；7：点赞评论
     */
    public function get_digg_message_count($uid){
        $where['message.type'] = ['IN', [5, 6, 7]];
        $where['message.to_uid'] = $uid;
        $where['message.is_del'] = 0;
        $where['message.status'] = 0;
        $count = $this->where($where)->count();
        // 修改为已读状态
        $this->where($where)->update(['message.status'=>1]);
        return $count;
    }
    /**
     * 获取未读消息总数
     */
    public function get_message_count($uid){
        $where['message.to_uid'] = $uid;
        $where['message.is_del'] = 0;
        $where['message.status'] = 0;
        return $this->where($where)->count();
    }


}
