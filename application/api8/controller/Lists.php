<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\controller;
use app\admin\service\ServiceService;
use app\common\controller\ApiLogin ;
use app\api8\service\UserService;
use app\api8\service\ServicesService;
use app\api8\service\CommentsService;
use app\api8\service\CircleService;
use think\Db;

/**
 * Class Profile
 * @package app\api8\controller
 * 发布接口
 */
class Lists extends ApiLogin {
    /**
     * 服务列表
     * @method post
     * @parameter string token 必须
     */
    public function service(){
        $param['category_id'] = $this->request->param('category_id', '', 'string');
        $param['time_id'] = $this->request->param('time_id', 0, 'intval');
        $param['sort'] = $this->request->param('sort', 0, 'intval');
        $param['sex'] = $this->request->param('sex', 0, 'intval');
        $param['key'] = $this->request->param('key', 0, 'string');
	    $param['city_id'] = $this->request->param('city_id', 0, 'string');
        $param['uid'] = $this->user['uid'];
        $lists = ServicesService::self()->service_lists($param, $this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }

    /**
     * 邀约列表
     * @method post
     * @parameter string token 必须
     */
    public function demand(){
        $param['category_id'] = $this->request->param('category_id', 0, 'string');
        $param['key'] = $this->request->param('key', 0, 'string');
        $param['sex'] = $this->request->param('sex', 0, 'intval');
        $param['sort'] = $this->request->param('sort', 0, 'intval');
        $param['uid'] = $this->user['uid'];
        $lists = ServicesService::self()->demand_lists($param, $this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }
    /**
     * 评论列表
     */
    public function comments(){
        $source_id = $this->request->param('source_id', 0, 'intval');
        if(!$source_id){
           $this->wrong(0, '非法操作');
        }
        $lists = CommentsService::lists($source_id, $this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }
    /**
     * 获取用户列表
     */
    public function recommend_user(){
        $param['uid'] = $this->user['uid'];
        $param['status'] = 1;
        $tmp_list = [];
        $lists = UserService::self()->lists($param, $this->page, $this->limit);
        if($lists){
            foreach ($lists as $k => $val) {
                $user = UserService::self()->simple_detail($val['uid'], $param['uid']);
                $tmp_list[] = $user;
            }
        }
        $page_count = UserService::self()->lists_count($param, $this->limit);
        $return['page_count'] = $page_count;
        $return['lists'] = $tmp_list;
        $this->response($return, 1, 'success');
    }
    /**
     * 首页动态列表
     */
    public function posts_lists(){
        $param['uid'] = $this->user['uid'];
        $param['type'] = 2;
        $posts_lists = CircleService::self()->posts_lists($param, $this->page, $this->limit);
        $this->response($posts_lists, 1, 'success');
    }

    /**
     * NEW 首页猜你喜欢列表
     */
    public function index_list()
    {
            $post_list = ServicesService::self()->index_list($this->page,$this->limit);
            $this->response($post_list,1,'success');
    }

    /**
     * 首页分类
     */
    public function index_type()
    {
        $type = $this->request->param('type');
        $category_id = $this->request->param('category_id');
        $sex = $this->request->param('sex');
        $time = $this->request->param('time');
        //排序参数
        $order = $this->request->param('order');
        $post_list = ServicesService::self()->index_type($type,$category_id,$this->page,$this->limit,$sex,$time,$order);
        $this->response($post_list,'1','Success');
    }

    /**
     * 分类接口
     */
    public function Type_list()
    {
        $type = $this->request->param('type');
        $list = ServicesService::self()->Type_list($type);
        $this->response($list,'1','success');
    }



}
