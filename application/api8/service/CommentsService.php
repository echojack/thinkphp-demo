<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\service;
use app\api8\service\UserService;
use app\api8\model\CommentsModel;

class CommentsService {
    
    /**
     * 添加评论
     */
    public static function add($order_id = '', $source_id = '', $content = '', $star = 1, $is_anonymous = 1, $type = 1, $user = []){
        $data['created_uid'] = $user['uid'];
        $data['content'] = $content;
        $data['source_id'] = $source_id;
        $data['order_id'] = $order_id;
        $data['star'] = $star;
        $data['is_anonymous'] = $is_anonymous;
        $data['type'] = $type;
        $data['created_at'] = time();
        // 清除订单评论标志
        $cache_name = 'orders_comment_'.$order_id.'_'.$data['created_uid'];
        \think\Cache::rm($cache_name);
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
        $comment = CommentsModel::self()->where($where)->find();
        if(!$comment){
            return false;
        }
        // 清除订单评论标志
        $cache_name = 'orders_comment_'.$comment['order_id'].'_'.$comment['created_uid'];
        \think\Cache::rm($cache_name);
        // $where['created_uid'] = $user['uid'];
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
