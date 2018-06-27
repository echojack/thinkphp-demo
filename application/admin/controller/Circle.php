<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;

use app\admin\controller\MY_admin;
use app\admin\service\CircleService;
use app\admin\service\CirclePostsService;
use app\admin\service\CircleCategoryService;
use app\admin\service\LogsService;
use app\admin\service\UserService;
/**
 * 后台登录首页
 */
class Circle extends MY_admin{
    /**
     * 圈子列表
     */
    public function lists(){
        $status = $this->request->param('status', 0, 'intval');
        $param['status'] = $status;
        $param['type'] = 1;
        $data = CircleService::self()->lists($param);
        // var_dump($data);die();
        return $this->fetch('lists', $data);
    }
    /**
     * 帖子列表
     */
    public function lists_ads(){
        $status = $this->request->param('status', 0, 'intval');
        $param['status'] = $status;
        $param['type'] = 3;
        $data = CirclePostsService::self()->lists($param);
        return $this->fetch('lists_ads', $data);
    }

    /**
     * 话题列表
     */
    public function lists_topic(){
        $status = $this->request->param('status', 0, 'intval');
        $param['status'] = $status;
        $param['type'] = 2;
        $data = CircleService::self()->lists($param);
        return $this->fetch('lists_topic', $data);
    }

    /**
     * 添加圈子
     */
    public function circle_add(){
        $circle_id = $this->request->param('circle_id', 0, 'intval');
        $circle = CircleService::self()->detail($circle_id);
        if($this->request->param('inajax')){
            $param = $this->request->param();
            $param['created_uid'] = $this->uid;
            $res = CircleService::self()->circle_add($param);
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, CIRCLE, ERR_ACT, $param);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, CIRCLE, SUC_ACT, $param);
            $this->response([], 1, 'success');
        }
        $data['circle'] = $circle;
        // dump($data);die();
        return $this->fetch('circle_add', $data);
    }
    /**
     * 添加话题
     */
    public function topic_add(){
        $circle_id = $this->request->param('circle_id', 0, 'intval');
        $circle = CircleService::self()->detail($circle_id);
        if($this->request->param('inajax')){
            $param = $this->request->param();
            $param['created_uid'] = $this->uid;
            $res = CircleService::self()->topic_add($param);
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, TOPIC, ERR_ACT, $param);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, TOPIC, SUC_ACT, $param);
            $this->response([], 1, 'success');
        }
        $data['circle'] = $circle;
        return $this->fetch('topic_add', $data);
    }
    /**
     * 添加广告
     */
    public function ads_add(){
        $posts_id = $this->request->param('posts_id', 0, 'intval');
        $posts = CircleService::self()->posts_detail($posts_id);
        if($this->request->param('inajax')){
            $param = $this->request->param();
            $param['created_uid'] = $this->uid;
            $res = CirclePostsService::self()->ads_add($param);
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, ADS, ERR_ACT, $param);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, ADS, SUC_ACT, $param);
            $this->response([], 1, 'success');
        }
        return $this->fetch('ads_add', $posts);
    }
    /**
     * 审核帖子
     */
    public function audit(){
        if($this->request->param('inajax')){
            $id = $this->request->param('id', 0, 'intval');
            $status = $this->request->param('status', 0, 'intval');  
            $res = CirclePostsService::self()->audit($id, $status);

            $remarks = ['id'=>$id, 'status'=>$status];
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $id, POSTS, ERR_ACT, $remarks);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $id, POSTS, SUC_ACT, $remarks);
            $this->response([], 1, 'success');
        }
    }

    /**
     * 审核圈子
     */
    public function audit_circle(){
        if($this->request->param('inajax')){
            $id = $this->request->param('id', 0, 'intval');
            $status = $this->request->param('status', 0, 'intval');  
            $res = CircleService::self()->audit($id, $status);
            $remarks = ['id'=>$id, 'status'=>$status];
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $id, CIRCLE, ERR_ACT, $remarks);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $id, CIRCLE, SUC_ACT, $remarks);
            $this->response([], 1, 'success');
        }
    }
    /**
     * 帖子列表
     */
    public function lists_posts(){
        $is_top = $this->request->param('is_top', 0, 'intval');
        $circle_id = $this->request->param('circle_id', 0, 'intval');
        $param['is_top'] = $is_top;
        $param['circle_id'] = $circle_id;
        $param['type'] = 1;
        $data = CirclePostsService::self()->lists($param);
        return $this->fetch('lists_posts', $data);
    }
    /**
     * 审核帖子
     */
    public function is_top(){
        if($this->request->param('inajax')){
            $id = $this->request->param('id', 0, 'intval');
            $is_top = $this->request->param('is_top', 2, 'intval');  
            $res = CirclePostsService::self()->is_top($id, $is_top);

            $remarks = ['id'=>$id, 'is_top'=>$is_top];
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $id, POSTS, ERR_ACT, $remarks);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $id, POSTS, SUC_ACT, $remarks);
            $this->response([], 1, 'success');
        }
    }

    /**
     * 后台发布帖子
     */
    public function posts_add(){
        $posts_id = $this->request->param('posts_id', 0, 'intval');
        $posts = CircleService::self()->posts_detail($posts_id);
        if($this->request->param('inajax')){
            $param = $this->request->param();
            if(!$param['created_uid']){
                $this->wrong(0 , '请输入发布用户UID');
            }
            // 检测账户id是否存在
            $users = UserService::self()->user_detail($param['created_uid']);
            if(!$users){
                $this->wrong(0 , '长点儿心吧，用户UID不存在');
            }
            // $param['created_uid'] = $this->uid;
            $res = CirclePostsService::self()->posts_add($param);
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, ADS, ERR_ACT, $param);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, ADS, SUC_ACT, $param);
            $this->response([], 1, 'success');
        }
        
        if($posts){
            $posts['content'] = deal_posts_show($posts['content'], $posts['attaches_path']);
        }
        // 圈子列表信息
        $posts['images'] = isset($posts['attaches_path']) ? implode("|", $posts['attaches_path']) : '';
        $posts['circle_list'] = CircleService::self()->lists_all();
        return $this->fetch('posts_add', $posts);
    }
    /**
     * 动态管理
     */
    public function topic_posts(){
        $circle_id = $this->request->param('circle_id', 0, 'intval');
        $param['circle_id'] = $circle_id;
        $param['type'] = 2;
        $data = CirclePostsService::self()->lists($param);
        if($data){
            foreach ($data['lists'] as $k => $val) {
                $data['lists'][$k]['circle'] = CircleService::self()->detail($val['circle_id']);
            }
        }
        $data['circle_list'] = CircleService::self()->lists_all(2);
        return $this->fetch('topic_posts', $data);
    }
    /**
     * 审核帖子
     */
    public function is_del(){
        if($this->request->param('inajax')){
            $id = $this->request->param('id', 0, 'intval');
            $res = CirclePostsService::self()->is_del($id, $this->user['uid']);

            $remarks = ['id'=>$id, 'is_del'=>1];
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $id, POSTS, ERR_ACT, $remarks);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $id, POSTS, SUC_ACT, $remarks);
            $this->response([], 1, 'success');
        }
    }

}
