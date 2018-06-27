<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api5\service;
use app\api5\service\UserService;
use app\api5\model\CommentsModel;

class CommentsService {
    
    /**
     * 添加评论
     */
    public static function add($source_id = '', $content = '', $star = 1, $is_anonymous = 1, $type = 1, $user = []){
        $data['created_uid'] = $user['uid'];
        $data['content'] = $content;
        $data['source_id'] = $source_id;
        $data['star'] = $star;
        $data['is_anonymous'] = $is_anonymous;
        $data['type'] = $type;
        $data['created_at'] = time();
        return CommentsModel::self()->add($data);
    }
    /**
     * 删除评论
     */
    public static function del($comment_id = '', $user = []){
        if(!$comment_id){
            return true;
        }
        $where['comment_id'] = $comment_id;
        $where['created_uid'] = $user['uid'];
        return CommentsModel::self()->where($where)->delete();
    }
    /**
     * 评论列表
     */
    public static function lists($source_id = '', $page = 1, $limit = 10){
        $lists = CommentsModel::self()->lists($source_id, $page, $limit);
        $lists = json_decode(json_encode($lists), true);
        if($lists){
            foreach ($lists as $k=>$val) {
                $user = UserService::self()->detail($val['created_uid']);
                if($val['is_anonymous'] == 1){
                    $val['nick_name'] = '匿名用户';
                }else{
                    $val['nick_name'] = hide_phone($user['nick_name']);
                }
                
                $val['avatar'] = $user['avatar'];
                $lists[$k] = $val;
            }
        }
        return $lists;
    }
}
