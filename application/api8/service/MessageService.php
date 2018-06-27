<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\service;

use think\Db;
use app\common\model\MessageModel;
use app\common\model\CommentsModel;
use app\api8\service\UserService;
use app\api8\service\CircleService;

class MessageService{
    public static function self(){
        return new self();
    }

    /**
     * 消息列表
     * 1:评论帖子；2：评论动态；3：回复帖子；4：回复动态；5：点赞帖子；6：点赞动态；7：点赞评论
     */
    public function lists($type = '', $uid = '', $page = 1, $limit = 10){
        $tmp_lists = [];
        $unread_count = 0;
        switch ($type) {
            case 'comments':
                $lists = MessageModel::self()->get_posts_message($uid, $page, $limit);
                if($lists){
                    foreach ($lists as $k => $val) {
                        $tmp_lists[$k]['id'] = $val['id'];
			            $tmp_lists[$k]['new_source_id'] = $val['new_source_id'];
                        $tmp_lists[$k]['type'] = $val['type'];
                        $tmp_lists[$k]['status'] = $val['status'];
                        $tmp_lists[$k]['is_del'] = $val['is_del'];
                        $tmp_lists[$k]['created_at'] = $val['created_at'];
                        $tmp_lists[$k]['content'] = strDecode($val['content']);
                        $tmp_lists[$k]['created_user'] = UserService::self()->simple_detail($val['created_uid']);
                        $tmp_lists[$k]['parents_detail'] = CircleService::self()->posts_detail($val['source_id'], $uid, 2);
                    }
                }
                $unread_count = MessageModel::self()->get_posts_message_count($uid);
                break;
            case 'reply':
                $lists = MessageModel::self()->get_comments_message($uid, $page, $limit);
                if($lists){
                    foreach ($lists as $k => $val) {
                        $tmp_lists[$k]['id'] = $val['id'];
			            $tmp_lists[$k]['new_source_id'] = $val['new_source_id'];
                        $tmp_lists[$k]['type'] = $val['type'];
                        $tmp_lists[$k]['status'] = $val['status'];
                        $tmp_lists[$k]['is_del'] = $val['is_del'];
                        $tmp_lists[$k]['created_at'] = $val['created_at'];
                        $tmp_lists[$k]['content'] = strDecode($val['content']);
                        $tmp_lists[$k]['created_user'] = UserService::self()->simple_detail($val['created_uid']);
                        // 回复评论内容组装
                        $tmp_lists[$k]['parents_detail'] = CommentsModel::self()->detail($val['source_id']);
                    }
                }
                $unread_count = MessageModel::self()->get_comments_count($uid);
                break;
            case 'digg':
                $lists = MessageModel::self()->get_digg_message($uid, $page, $limit);
                if($lists){
                    foreach ($lists as $k => $val) {
                        $tmp_lists[$k]['id'] = $val['id'];
                        $tmp_lists[$k]['type'] = $val['type'];
                        $tmp_lists[$k]['status'] = $val['status'];
                        $tmp_lists[$k]['is_del'] = $val['is_del'];
                        $tmp_lists[$k]['created_at'] = $val['created_at'];
                        $tmp_lists[$k]['content'] = strDecode($val['content']);
                        $tmp_lists[$k]['created_user'] = UserService::self()->simple_detail($val['created_uid']);
                        if($val['type'] == 7){
                            // 回复评论内容组装
                            $tmp_lists[$k]['parents_detail'] = CommentsModel::self()->detail($val['source_id']);
                        }else{
                            $tmp_lists[$k]['parents_detail'] = CircleService::self()->posts_detail($val['source_id'], $uid, 2);    
                        }
                    }
                }
                $unread_count = MessageModel::self()->get_digg_message_count($uid);
                break;
            default:
                $lists = [];
                break;
        }
        return ['unread_count'=>$unread_count, 'lists'=>$tmp_lists]; 
    }

    /**
     * 删除消息
     */
    public function del($s_ids = '', $uid = ''){
        if(!is_array($s_ids)){
            $s_ids = array_unique(explode(',', str_replace('，', ',', $s_ids)));
        }
        $where['id'] = ['IN', $s_ids];
        $where['to_uid'] = $uid;
        return MessageModel::self()->where($where)->update(['is_del'=>1]);
    }
}
