<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api3\service;
use think\Config;
use think\Cache;

use app\api3\model\UploadModel;
use app\api3\service\UserService;
use app\api3\service\PushService;
use app\common\model\CircleModel;
use app\common\model\CircleLikeModel;
use app\common\model\CircleUserModel;
use app\common\model\CirclePostsModel;
use app\common\model\CircleCommentModel;
use app\common\model\CircleCategoryModel;

class CircleService {
    public static function self(){
        return new self();
    }

    /**
     * 圈子发布
     */
    public function posts($param = [], $id = ''){
        if(empty($param['circle_id'])){
            return '请输入圈子id';
        }
        // 检测用户是否加入圈子，加入才能发帖
        $has = CircleUserModel::self()->check_add_circle($param['circle_id'],$param['uid']);
        if(!$has){
            return '加入圈子后才能发帖';
        }
        if(empty($param['title'])){
            return '请输入帖子标题';
        }
        // if(empty($param['content'])){
        //     return '请输入帖子内容';
        // }
        $config_size = Config::get('max_image_size');
        $max_size = $config_size*1024*1024;

        $images_files = isset($param['images_files']) ? $param['images_files'] : [];
        $images_sizes = isset($param['images_sizes']) ? $param['images_sizes'] : [];
        $upload_path = $param['upload_path'];
        // 如果上传了新的图片 判断图片大小
        if($images_files){
            foreach ($images_sizes as $size) {
                if($size > $max_size){
                    return '单张图片上传不能超过'.$config_size.'M';
                }
            }
        }
        // 图片上传信息
        $attaches = [];

        $images = UploadModel::self()->arr_upload( $images_files, $upload_path, 0);
        if($images){
            foreach ($images as $k => $val) {
                $attaches[] = $val['save_path'].$val['save_name'];
            }
        }
        if(!$attaches && !$param['content']){
            return '帖子内容不能为空';
        }

        $save['title'] = strEncode($param['title']);
        $save['content'] = strEncode($param['content']);
        $save['attaches'] = serialize($attaches);
        $save['created_uid'] = $param['uid'];
        $save['circle_id'] = $param['circle_id'];
        // 发布帖子
        $res = CirclePostsModel::self()->posts($save);
        if($res !== true){
            return $res;
        }
        return true;
    }

    /**
     * 圈子列表
     */
    public function lists($param = [], $page = 1, $limit = 10){
        $tmp_list = [];
        $param['status'] = 1;
        $lists = CircleModel::self()->ajax_lists($param, $page, $limit);
        if($lists){
            $lists = json_decode(json_encode($lists), true);
            foreach ($lists as $k => $val) {
                $tmp_list[$k] = $this->circle_detail($val['circle_id'], $param['uid']);
            }
        }
        return $tmp_list;
    }

    /**
     * 圈子详情
     */
    public function circle_detail($id = '', $uid = ''){
        $cache_name = 'circle_'.$id;
        $detail = Cache::get($cache_name);
        if(!$detail){
            $detail = CircleModel::self()->detail($id);
            if(!$detail){
               return [];
            }
            $detail = json_decode(json_encode($detail), true);
            $detail['title'] = strDecode($detail['title']);
            $detail['intro'] = strDecode($detail['intro']);
            $detail['rule'] = strDecode($detail['rule']);
	    $detail['logo_path'] = $detail['logo'];
            $detail['logo'] = Config::get('img_url').$detail['logo'];
            Cache::set($cache_name, $detail);
        }
        // 是否加入圈子
        $add_circle = CircleUserModel::self()->check_add_circle($id, $uid);
        $detail['add_circle'] = $add_circle;
        // 增加话题浏览量
        if($detail['type'] ==2 ){
            CircleModel::self()->view_count_inc($id, $uid);
        }
        return $detail;
    }
    /**
     * 话题列表
     */
    public function topic_lists($param = [], $page = 1, $limit = 10){
        $param['type'] = 2;
        $lists = CircleModel::self()->ajax_lists($param, $page, $limit);

        $tmp_lists = [];
        if($lists){
            foreach ($lists as $val) {
                $tmp_lists[] = $this->circle_detail($val['circle_id']);
            }
        }
        return $tmp_lists;
    }

    /**
     * 帖子列表
     */
    public function posts_lists($param = [], $page = 1, $limit = 10){
        $lists = CirclePostsModel::self()->ajax_lists($param, $page, $limit);
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $val) {
                $tmp_lists[] = $this->posts_detail($val['posts_id'], $param['uid'], 2);
            }
        }
        return $tmp_lists;
    }
    /**
     * 帖子详情
     * @param type 1 详情，2 列表
     */
    public function posts_detail($id = '', $current_uid = '', $type = 1){
        $cache_name = 'circle_posts_'.$id;
        $detail = Config::get($cache_name);
        if(!$detail){
            $where['posts_id'] = $id;
            $detail = CirclePostsModel::self()->detail($where);
            if(!$detail){
                return [];
            }
            
            $tmp_attaches = [];
            if(!empty($detail['attaches'])){
                $attaches = unserialize($detail['attaches']);
                foreach ($attaches as $k => $url) {
                    $tmp_attaches[] = Config::get('img_url').$url;
                }
		$detail['attaches_path'] = $attaches;
            }

            if($detail['type'] == 3 ){
                $detail['logo'] = $tmp_attaches['0'];
                unset($detail['attaches']);
            }else{
                $detail['attaches'] = $tmp_attaches;
            }
            $detail['title'] = strDecode($detail['title']);
            $detail['content'] = strDecode($detail['content']);
            $detail = $detail ? $detail : [];
            Config::set($cache_name, $detail);
        }
        switch ($detail['type']) {
            case 1://帖子
		        // 是否点赞过
                $detail['is_like'] = $this->check_posts_like($detail['posts_id'], $current_uid);
                // 创建人昵称 头像
                $detail['created_user'] = UserService::self()->simple_detail($detail['created_uid'], $current_uid);
                // 圈子名称 logo
                $detail['circle'] = $this->circle_detail($detail['circle_id']);
                // 点赞列表
                if($type == 1){
                    $detail['like_user'] = $this->posts_like_user($detail['posts_id']);
                }
                break;
            case 2://话题动态
                // 是否点赞过
                $detail['is_like'] = $this->check_posts_like($detail['posts_id'], $current_uid);
                // 创建人昵称 头像
                $detail['created_user'] = UserService::self()->simple_detail($detail['created_uid'], $current_uid);
		        $detail['like_user'] = $this->posts_like_user($detail['posts_id']);
                break;
            case 3://广告
                unset($detail['content']);unset($detail['status']);unset($detail['is_top']);
                unset($detail['like_count']);unset($detail['is_del']);unset($detail['created_uid']);
                unset($detail['comment_count']);unset($detail['status']);unset($detail['status']);
                unset($detail['view_count']);unset($detail['created_at']);unset($detail['update_at']);
                unset($detail['circle_id']);
                $detail['share_url'] = url('Share/ads_detail', ['posts_id'=>$detail['posts_id'], 'token'=> md5($detail['posts_id'].Config::get('public.key'))], true, Config::get('domain'));
                $detail['share_url'] = 'https://www.zhuomazaima.com';
		break;
        }
        
        return $detail;
    }

    /**
     * 加入圈子
     */
    public function user_add($circle_id = '', $user_id = ''){
        $where['uid'] = $user_id;
        $where['circle_id'] = $circle_id;
        $res = CircleUserModel::self()->where($where)->find();
        if( $res && $res['status'] == 1){
            return '您已加入圈子，请勿重复操作';
        }
        return CircleModel::self()->user_add($circle_id, $user_id, $res);
    }
    /**
     * 退出圈子
     */
    public function user_out($circle_id = '', $user_id = ''){
        $where['uid'] = $user_id;
        $where['circle_id'] = $circle_id;
        $res = CircleUserModel::self()->where($where)->find();
        if(!$res || $res && $res['status'] == 2){
            return '您未加入圈子，不能退出';
        }
        return CircleModel::self()->user_out($circle_id, $user_id);
    }
    /**
     * 广告列表
     */
    public function ads_lists($param = []){
        $lists = CirclePostsModel::self()->ajax_lists($param);
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $val) {
                $tmp_lists[] = $this->posts_detail($val['posts_id'], $param['uid']);
            }
        }
        return $tmp_lists;
    }
    /**
     * 我的圈子列表
     */
    public function my_circle($uid = ''){
        $tmp_list = [];
	    $where['status'] = 1;
	    $where['uid'] = $uid;
        $lists = CircleUserModel::self()->where($where)->select();
        if($lists){
            foreach ($lists as $k => $val) {
                $tmp_list[$k] = $this->circle_detail($val['circle_id'], $uid);
            }
        }
        return $tmp_list;
    }

    /**
     * 点赞帖子
     */
    public function like_posts($posts_id, $uid = ''){
        if(!$posts_id){
            return '请输入帖子id';
        }
        $like = $this->check_posts_like($posts_id, $uid);
        if($like){
            return '不能重复点赞';
        }

        $res = CircleLikeModel::self()->like_posts($posts_id, $uid);
        if($res){
            PushService::self()->push_posts_like_msg($posts_id, $uid, 'posts');
        }
        return $res;
    }
    /**
     * 取消点赞帖子
     */
    public function unlike_posts($posts_id, $uid = ''){
        if(!$posts_id){
            return '请输入帖子id';
        }
        
        $like = $this->check_posts_like($posts_id, $uid);
        if(!$like){
            return '您并未点赞该帖子';
        }

        $res = CircleLikeModel::self()->unlike_posts($posts_id, $uid);
        if($res){
            $cache_name = 'posts_like_'.$posts_id.'_'.$uid;
            Cache::rm($cache_name);
        }
        return $res;
    }

    /**
     * 评论帖子
     */
    public function add_posts_comments($param = [], $uid = ''){
        if(empty($param['posts_id'])){
            return '帖子id不能为空';
        }
        if(empty($param['content'])){
            return '请输入评论内容';
        }
        $res = CircleCommentModel::self()->add_posts_comments($param, $uid);
        if($res){
            $posts_id = $param['posts_id'];
            $comments_id = isset($param['comments_id']) ? $param['comments_id'] : 0;
            PushService::self()->push_posts_comment_msg($posts_id, $comments_id, $uid);
        }
        return $res;
    }
    /**
     * 点赞帖子评论
     */
    public function like_posts_comments($comments_id = [], $uid = ''){
        if(empty($comments_id)){
            return '评论id不能为空';
        }
        $has = $this->check_comments_like($comments_id, $uid);
        if($has){
            return '不能重复点赞';
        }
        $res = CircleLikeModel::self()->like_posts_comments($comments_id, $uid);
        if($res){
            $cache_name = 'comments_like_'.$comments_id.'_'.$uid;
            Cache::set($cache_name, 1);
            PushService::self()->push_posts_like_msg($comments_id, $uid, 'comment');
        }
        return $res;
    }
    /**
     * 取消点赞帖子评论
     */
    public function unlike_posts_comments($comments_id = [], $uid = ''){
        if(empty($comments_id)){
            return '评论id不能为空';
        }
        $has = $this->check_comments_like($comments_id, $uid);
        if(!$has){
            return '您并未点赞该评论';
        }
        $res = CircleLikeModel::self()->unlike_posts_comments($comments_id, $uid);

        if($res){
            $cache_name = 'comments_like_'.$comments_id.'_'.$uid;
            Cache::rm($cache_name);
        }
        return $res;
    }
    /**
     * 帖子评论列表
     */
    public function posts_comments($current_uid = '', $posts_id = '', $page = 1, $limit = 10){
        $lists = CircleCommentModel::self()->posts_comments($posts_id, $page, $limit);
        if($lists){
            foreach ($lists as $k => $val) {
                $lists[$k]['content'] = strDecode($val['content']);
                $lists[$k]['is_like'] = $this->check_comments_like($val['comments_id'], $current_uid);
		        $lists[$k]['open'] = 0;
                $lists[$k]['created_user'] = UserService::self()->simple_detail($val['created_uid']);
                $lists[$k]['floor'] = $this->comments_comments($val['comments_id'], $current_uid);
            }
        }
        return $lists;
    }
    /**
     * 帖子评论的评论
     */
    public function comments_comments($comments_id = '', $current_uid = ''){
        $lists = [];
        $detail = CircleCommentModel::self()->comments_detail($comments_id);
        do{
            $detail = CircleCommentModel::self()->comments_detail($detail['parent_id']);
            if($detail){
                $lists[$detail['comments_id']] = $detail;
                $lists[$detail['comments_id']]['content'] = strDecode($detail['content']);
                $lists[$detail['comments_id']]['is_like'] = $this->check_comments_like($detail['comments_id'], $current_uid);
		        $lists[$detail['comments_id']]['open'] = 0;
            }
        } while ($detail['parent_id'] != 0);
        sort($lists);
        // 排序
        if($lists){
            $floor = 1;
            foreach ($lists as $k=>$val) {
                $val['floor'] = $floor;
                $val['created_user'] = UserService::self()->simple_detail($val['created_uid']);
                $lists[$k] = $val;
                $floor++;
            }
        }
        return $lists;
    }
    /**
     * 检测是否已添加过喜欢
     */
    public function check_comments_like($comments_id = '', $uid = ''){
        $cache_name = 'comments_like_'.$comments_id.'_'.$uid;
        $has = Cache::has($cache_name);
        if(!$has){
            $like = CircleLikeModel::self()->check_comments_like($comments_id, $uid);
            Cache::set($cache_name, $like);
        }else{
            $like = Cache::get($cache_name);
        }
        return $like;        
    }
    /**
     * 检测是否已添加过喜欢
     */
    public function check_posts_like($posts_id = '', $current_uid = ''){
        $cache_name = 'posts_like_'.$posts_id.'_'.$current_uid;
        $has = Cache::has($cache_name);
        if(!$has){
            $like = CircleLikeModel::self()->check_posts_like($posts_id, $current_uid);
            Cache::set($cache_name, $like);
        }else{
            $like = Cache::get($cache_name);
        }
        return $like;        
    }
    /**
     * 帖子点赞列表
     */
    public function posts_like_user($posts_id = '', $page = 1, $per_page = 10, $current_uid = ''){
        $lists =  CircleLikeModel::self()->posts_like_user($posts_id, $page, $per_page);
        if($lists){
            foreach ($lists as $k => $val) {
                $lists[$k] = UserService::self()->simple_detail($val['created_uid'], $current_uid);
            }
        }
        return $lists;
    }

    /**
     * 话题讨论
     */
    public function topic_posts($param = [], $id = ''){
        if(empty($param['topic_id'])){
            return '请输入话题id';
        }
        // if(empty($param['content'])){
        //     return '请输入讨论内容';
        // }
        $config_size = Config::get('max_image_size');
        $max_size = $config_size*1024*1024;

        $images_files = isset($param['images_files']) ? $param['images_files'] : [];
        $images_sizes = isset($param['images_sizes']) ? $param['images_sizes'] : [];
        $upload_path = $param['upload_path'];
        // 如果上传了新的图片 判断图片大小
        if($images_files){
            foreach ($images_sizes as $size) {
                if($size > $max_size){
                    return '单张图片上传不能超过'.$config_size.'M';
                }
            }
        }
        // 图片上传信息
        $attaches = [];
        $images = UploadModel::self()->arr_upload( $images_files, $upload_path, 0);
        if($images){
            foreach ($images as $k => $val) {
                $attaches[] = $val['save_path'].$val['save_name'];
            }
        }

        if(!$attaches && !$param['content']){
            return '动态内容不能为空';
        }
        
        $save['title'] = strEncode($param['title']);
        $save['content'] = strEncode($param['content']);
        $save['attaches'] = serialize($attaches);
        $save['created_uid'] = $param['uid'];
        $save['circle_id'] = $param['topic_id'];
        // 发布帖子
        $res = CirclePostsModel::self()->topic_posts($save);
        if($res !== true){
            return $res;
        }
	Cache::rm('circle_'.$param['topic_id']);
        return true;
    }
    /**
     * 话题讨论列表
     */
    public function topic_posts_lists($current_uid = '', $topic_id = '', $order = '', $page = 1, $limit = 10){
        $tmp_lists = [];
        $where['circle_id'] = $topic_id;
        $where['order'] = $order;
        $where['type'] = 2;
        $lists = CirclePostsModel::self()->ajax_lists($where, $page, $limit);
        if($lists){
            foreach ($lists as $k => $val) {
                $tmp_lists[] = $this->posts_detail($val['posts_id'], $current_uid);
            }
        }
        return $tmp_lists;
    }

}
