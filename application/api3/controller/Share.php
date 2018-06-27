<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api3\controller;

use think\Config;
use think\Controller;
use app\api3\service\ServicesService;
use app\api3\service\UserService;
use app\api3\service\CircleService;
use app\api3\model\ConfigModel;

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
        // var_dump($detail);die();
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
        // var_dump($detail);die();
        return $this->fetch('demand_detail', $detail);
    }

    /**
     * 广告/活动列表
     */
    public function ads_detail(){
        $id = $this->request->param('id', '', 'intval');
        $token = $this->request->param('token', '', 'string');
        if($token !== md5($id.Config::get('public.key'))){
            exit('非法操作');
        }
        $detail = CircleService::self()->posts_detail($id);
        return $this->fetch('ads_detail', $detail);
    }

}
