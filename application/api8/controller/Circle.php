<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\controller;

use app\common\controller\ApiLogin ;
use app\api8\service\CircleService;
use app\api8\service\LogsService;
use app\api8\service\MessageService;

/**
 * @package app\api8\controller
 * 圈子控制器
 */
class Circle extends ApiLogin {
    /**
     * 发布帖子
     */
    public function posts(){
        $param['circle_id'] = $this->request->param('circle_id', '', 'string');
        $param['title'] = $this->request->param('title', '', 'string');
        $param['content'] = $this->request->param('content', '', 'string');
	    // $param['content'] = $this->request->post('content');
        // 图片相关信息
        $images_files = request()->file('images');
        if($images_files){
            if(count($images_files) > 30){
                $this->wrong(0, '一次上传图片最多30张');
            }
            $param['images_files'] = $images_files;
            $param['images_sizes'] = $_FILES['images']['size'];
        }
        
        $param['upload_path'] = $this->upload_path;
        $param['uid'] = $this->user['uid'];
        $msg = CircleService::self()->posts($param);
        if($msg !== true){
            $this->wrong(0,$msg);
        }
        $param['intro'] = '发布帖子';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $msg, POSTS, SUC_ACT, $param);
        $this->response([] , 1, '发布成功');
    }
    
    /**
     * 圈子列表
     */
    public function lists(){
        $param = [];
        $param['uid'] = $this->user['uid'];
        $param['type'] = 1;
        $lists = CircleService::self()->lists($param, $this->page, $this->limit);
        $this->response($lists , 1, 'success');
    }

    /**
     * 圈子详情
     */
    public function detail(){
        $id = $this->request->param('circle_id', 0, 'intval');
        $detail = CircleService::self()->circle_detail($id, $this->user['uid']);
        $this->response($detail , 1, 'success');
    }

    /**
     * 帖子列表
     */
    public function posts_lists(){
        $circle_id = $this->request->param('circle_id', 0, 'intval');
        if(!$circle_id){
            $this->wrong(0,'请先进入圈子');
        }
        $param['circle_id'] = $circle_id;
        $param['type'] = 1;
        $param['uid'] = $this->user['uid'];
        $lists = CircleService::self()->posts_lists($param, $this->page, $this->limit);
        $this->response($lists , 1, 'success');
    }
    /**
     * 帖子详情
     */
    public function posts_detail(){
        $id = $this->request->param('posts_id', 0, 'intval');
        $lists = CircleService::self()->posts_detail($id, $this->user['uid']);
        $this->response($lists , 1, 'success');
    }
    /**
     * 加入圈子
     */
    public function user_add(){
        $circle_id = $this->request->param('circle_id', '', 'string');
        if(!$circle_id){
            $this->wrong(0,'非法操作，缺少圈子id');
        }
        $res = CircleService::self()->user_add($circle_id, $this->user['uid']);
        if($res !== true){
            $this->wrong(0,$res);
        }
        $param['circle_id'] = $circle_id;
        $param['intro'] = '加入圈子';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $circle_id, CIRCLE, SUC_ACT, $param);
        $this->response([] , 1, '加入圈子成功');
    }
    /**
     * 退出圈子
     */
    public function user_out(){
        $circle_id = $this->request->param('circle_id', 0, 'intval');
        if(!$circle_id){
            $this->wrong(0,'非法操作，缺少圈子id');
        }
        $res = CircleService::self()->user_out($circle_id, $this->user['uid']);
        if($res !== true){
            $this->wrong(0,$res);
        }
        $param['circle_id'] = $circle_id;
        $param['intro'] = '退出圈子';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $circle_id, CIRCLE, SUC_ACT, $param);
        $this->response([] , 1, '退出圈子成功');
    }

    /**
     * 广场页面
     */
    public function square(){
        // 活动列表
        $param['status'] = 1;
        $param['is_del'] = 0;
        $param['type'] = 3;
        $param['uid'] = $this->user['uid'];
        $ads = CircleService::self()->ads_lists($param);
        $lists['ads'] = $ads;
        unset($param['is_del']);
        // 我的圈子
        $my_circle = CircleService::self()->my_circle($this->user['uid'], false);
        $lists['my_circle'] = $my_circle;
        // 热门话题
        $topic_lists = CircleService::self()->topic_lists($param, 1, 3, $this->user['uid']);
        $lists['topic_lists'] = $topic_lists;
        $this->response($lists, 1, 'success');
    }

    /**
     * 精选帖子列表
     */
    public function selected_lists(){
        $param['type'] = 1;
        $param['is_top'] = 1;
        $param['uid'] = $this->user['uid'];
        $lists = CircleService::self()->posts_lists($param, $this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }

    /**
     * 点赞帖子
     */
    public function like_posts(){
        $posts_id = $this->request->param('posts_id', 0, 'intval');
        $res = CircleService::self()->like_posts($posts_id, $this->user['uid']);
        if($res !== true){
            $this->wrong(0,$res);
        }
        $param['posts_id'] = $posts_id;
        $param['intro'] = '点赞帖子';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $posts_id, POSTS, SUC_ACT, $param);
        $this->response([], 1, '点赞成功');
    }
    /**
     * 取消点赞帖子
     */
    public function unlike_posts(){
        $posts_id = $this->request->param('posts_id', 0, 'intval');
        $res = CircleService::self()->unlike_posts($posts_id, $this->user['uid']);
        if($res !== true){
            $this->wrong(0,$res);
        }
        $param['posts_id'] = $posts_id;
        $param['intro'] = '取消点赞帖子';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $posts_id, POSTS, SUC_ACT, $param);
        $this->response([], 1, '取消点赞成功');
    }

    /**
     * 评论帖子
     */
    public function add_posts_comments(){
        $param['posts_id'] = $this->request->param('posts_id', 0, 'intval');
        $param['comments_id'] = $this->request->param('comments_id', 0, 'intval');
        $param['content'] = $this->request->param('content', 0, 'string');
        $res = CircleService::self()->add_posts_comments($param, $this->user['uid']);
        if($res !== true){
            $this->wrong(0,$res);
        }
        $param['intro'] = '评论帖子';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $param['posts_id'], POSTS, SUC_ACT, $param);
        $this->response([], 1, '发布评论成功');
    }
    /**
     * 帖子评论列表
     */
    public function posts_comments(){
        $posts_id = $this->request->param('posts_id', 0, 'intval');
        $lists = CircleService::self()->posts_comments($this->user['uid'], $posts_id, $this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }

    /**
     * 点赞帖子评论
     */
    public function like_posts_comments(){
        $comments_id = $this->request->param('comments_id', 0, 'intval');
        $res = CircleService::self()->like_posts_comments($comments_id, $this->user['uid']);
        if($res !== true){
            $this->wrong(0,$res);
        }
        $param['intro'] = '点赞帖子评论';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $comments_id, POSTS, SUC_ACT, $param);
        $this->response([], 1, '点赞成功');
    }
    /**
     * 取消点赞帖子评论
     */
    public function unlike_posts_comments(){
        $comments_id = $this->request->param('comments_id', 0, 'intval');
        $res = CircleService::self()->unlike_posts_comments($comments_id, $this->user['uid']);
        if($res !== true){
            $this->wrong(0,$res);
        }
        $param['intro'] = '取消点赞帖子评论';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $comments_id, POSTS, SUC_ACT, $param);
        $this->response([], 1, '取消点赞成功');
    }
    /**
     * 话题列表
     */
    public function topic_lists(){
        // $param['status'] = 2;//历史话题
        $param['uid'] = $this->user['uid'];
	    $param['key'] = $this->request->param('key', '', 'string');
	    $lists = CircleService::self()->topic_lists($param, $this->page, $this->limit);
        $this->response($lists , 1, 'success');
    }
    /**
     * 话题详情
     */
    public function topic_detail(){
        $id = $this->request->param('circle_id', 0, 'intval');
        $lists = CircleService::self()->circle_detail($id, $this->user['uid']);
        $this->response($lists , 1, 'success');
    }
    /**
     * 发布话题讨论
     */
    public function topic_posts(){
        $param['topic_id'] = $this->request->param('circle_id', 0, 'string');
        $param['title'] = '话题动态';
        $param['content'] = $this->request->param('content', '', 'string');
        // 图片相关信息
        $images_files = request()->file('images');
        if($images_files){
            $param['images_files'] = $images_files;
            $param['images_sizes'] = $_FILES['images']['size'];
        }
        $param['upload_path'] = $this->upload_path;
        $param['uid'] = $this->user['uid'];
        $msg = CircleService::self()->topic_posts($param);
        if($msg !== true){
            $this->wrong(0,$msg);
        }
        $param['intro'] = '发布话题动态';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, POSTS, SUC_ACT, $param);
        $this->response([] , 1, '发布成功');
    }
    /**
     * 话题讨论列表
     */
    public function topic_posts_lists(){
        $topic_id = $this->request->param('circle_id', 0, 'intval');
        $order = $this->request->param('order', 'new', 'string');
        $lists = CircleService::self()->topic_posts_lists($this->user['uid'], $topic_id, $order, $this->page, $this->limit);
        $this->response($lists , 1, 'success');
    }

    /**
     * 话题动态详情
     */
    public function topic_posts_detail(){
        $id = $this->request->param('posts_id', 0, 'intval');
        $lists = CircleService::self()->posts_detail($id, $this->user['uid']);
        $this->response($lists , 1, 'success');
    }

    /**
     * 精选帖子
     */
    public function selected_posts_lists(){
        $param['type'] = 1;
        $param['is_top'] = 1;
        $param['uid'] = $this->user['uid'];
        $posts_lists = CircleService::self()->posts_lists($param, $this->page, $this->limit);
        $this->response($posts_lists , 1, 'success');
    }
    /**
     * 帖子点赞列表
     */
    public function posts_like_user(){
        $posts_id = $this->request->param('posts_id', 0, 'intval');
        if(!$posts_id){
            $this->wrong(0,'请输入帖子id');
        }
        $like_user = CircleService::self()->posts_like_user($posts_id, $this->page, $this->limit, $this->user['uid']);
        $this->response($like_user , 1, 'success');
    }

    /**
     * 删除帖子和话题动态
     */
    public function del_posts(){
        $posts_id = $this->request->param('posts_id', 0, 'intval');
        if(!$posts_id){
            $this->wrong(0,'请输帖子id');
        }
        $res = CircleService::self()->del_posts($posts_id, $this->user['uid']);
        if($res !== true ){
            $this->wrong(0,$res);
        }
        $param['posts_id'] = $posts_id;
        $param['intro'] = '删除帖子和话题动态';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $posts_id, POSTS, SUC_ACT, $param);
        $this->response([] , 1, '删除成功');
    }

    /**
     * 删除帖子评论/话题动态评论
     */
    public function del_comment(){
        $comments_id = $this->request->param('comments_id', 0, 'intval');
        if(!$comments_id){
            $this->wrong(0,'请输评论id');
        }
        $res = CircleService::self()->del_comment($comments_id, $this->user['uid']);
        if($res !== true ){
            $this->wrong(0,$res);
        }
        $param['comments_id'] = $comments_id;
        $param['intro'] = '删除帖子和话题评论';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $comments_id, POSTS, SUC_ACT, $param);
        $this->response([] , 1, '删除成功');
    }

    /**
     * 我的消息
     */
    public function message(){
        $type = $this->request->param('type', '', 'string');
        if(!$type){
            $this->wrong(0,'请输入消息类型');
        }
        $lists = MessageService::self()->lists($type, $this->user['uid'], $this->page, $this->limit);
        $this->response($lists , 1, 'success');
    }

    /**
     * 推荐圈子列表
     */
    public function recommend_lists(){
        $param['type'] = 1;
        $param['uid'] = $this->user['uid'];
        $lists = CircleService::self()->lists($param, $this->page, $this->limit);
        $page_count = CircleService::self()->page_count($param, $this->limit);
        $return['page_count'] = $page_count;
        $return['lists'] = $lists;
        $this->response($return , 1, 'success');
    }


}
