<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api4\controller;

use think\Config;
use think\Controller;
use app\api4\service\ServicesService;
use app\api4\service\UserService;
use app\api4\service\CircleService;
use app\api4\model\ConfigModel;

/**
 * h5分享页面控制器
 */
class Share extends Controller
{
    /**
     * 用户个人主页
     */
    public function user()
    {
        $uid = $this->request->param('uid', 0, 'intval');
        $token = $this->request->param('token', '', 'string');
        if($token !== md5($uid.Config::get('public.key'))){
            exit('非法操作');
        }
        $user = UserService::self()->detail($uid);
        // 获取用户 服务信息
        $param['created_uid'] = $uid;
        $param['type'] = 1;
        $user['service_count'] = ServicesService::self()->lists_count($uid, 1);
        $user['service'] = ServicesService::self()->service_lists($param,1, 2, $user);
        // 邀约 信息
        $param['type'] = 2;
        $user['demand_count'] = ServicesService::self()->lists_count($uid, 2);
        $user['demand'] = ServicesService::self()->demand_lists($param, 1, 2, $user);
        return $this->fetch('user', $user);
    }

    /**
     * 服务详情
     */
    public function service_detail()
    {
        $id = $this->request->param('id', '', 'intval');
        $token = $this->request->param('token', '', 'string');
        if($token !== md5($id.Config::get('public.key'))){
            exit('非法操作');
        }

        $detail = ServicesService::self()->service_detail($id);
        $detail['category'] = ConfigModel::self()->lists(1, 1);
        $detail['times'] = ConfigModel::self()->lists(2, 0);
        return $this->fetch('service_detail', $detail);
    }

    /**
     * 邀约详情
     */
    public function demand_detail()
    {
        $id = $this->request->param('id', '', 'intval');
        $token = $this->request->param('token', '', 'string');
        if($token !== md5($id.Config::get('public.key'))){
            exit('非法操作');
        }
        $detail = ServicesService::self()->demand_detail($id);
        return $this->fetch('demand_detail', $detail);
    }

    /**
     * 帖子、话题动态、广告活动列表
     */
    public function posts_detail(){
        $id = $this->request->param('posts_id', '', 'intval');
        $token = $this->request->param('token', '', 'string');
        if($token !== md5($id.Config::get('public.key'))){
            exit('非法操作');
        }
        $detail = CircleService::self()->posts_detail($id);
        // 图片处理替换
        if(!empty($detail['attaches'])){
            foreach ($detail['attaches'] as $k => $img) {
                $patterns[$k] = '/@#￥%\(!---UIImageView---!\)@#￥%/i';
                $detail['attaches'][$k] = '<img src="'.$img.'"/>';
            }
            $detail['content'] = preg_replace($patterns, $detail['attaches'], $detail['content']);
        }
        switch ($detail['type']) {
            case '1'://帖子
            case '2'://动态
                $comments = CircleService::self()->posts_comments('', $id);
                $detail['comments'] = $comments;
                return $this->fetch('posts_detail', $detail);
                break;
            case '3'://广告
                return $this->fetch('ads_detail', $detail);
                break;
        }
        
    }

    /**
     * 话题 、圈子分享
     */
    public function topic_detail(){
        $id = $this->request->param('circle_id', '', 'intval');
        $token = $this->request->param('token', '', 'string');
        if($token !== md5($id.Config::get('public.key'))){
            exit('非法操作');
        }
        $detail = CircleService::self()->circle_detail($id);
        // 话题讨论列表
        $order = $this->request->param('order', 'new', 'string');
        $lists = CircleService::self()->topic_posts_lists('', $id, $order);
        $detail['comments'] = $lists;
        // var_dump($lists);die();
        return $this->fetch('topic_detail', $detail);
    }



}
