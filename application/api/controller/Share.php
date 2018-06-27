<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;

use think\Db;
use think\Config;
use think\Controller;
use app\api\model\ConfigModel;
use app\api\service\ServicesService;
use app\api\service\UserService;

/**
 * Class Message
 * @package app\api\controller
 * 首页 未登录用户展示接口
 */
class Share extends Controller
{
    /**
     * 用户协议页面
     */
    public function user()
    {
        $uid = $this->request->param('uid', 0, 'intval');
        $token = $this->request->param('token', '', 'string');
        if($token !== md5($uid.Config::get('public.key'))){
            exit('非法操作');
        }
        $return = UserService::self()->detail($uid, 0);

        // 服务信息
        $list_where['services.created_uid'] = $uid;
        $list_where['services.type'] = 1;
        $list_where['services.is_del'] = 0;
        $service = ServicesService::self()->service_lists($list_where,1, 2);
        $service_count = ServicesService::self()->service_lists_count($list_where);
        // 邀约信息
        $list_where['services.type'] = 2;
        $demand = ServicesService::self()->demand_lists($list_where, 1,2);
        $demand_count = ServicesService::self()->demand_lists_count($list_where);
        $return['service'] = $service ;
        $return['demand'] = $demand ;
        $return['service_count'] = $service_count ;
        $return['demand_count'] = $demand_count ;
        $return['tags_count'] = count($return['tags']) ;
        // var_dump($return);die();
        return $this->fetch('user', $return);
    }

    /**
     * 支持与帮助
     */
    public function service_detail()
    {
        $id = $this->request->param('id', '', 'intval');
        $token = $this->request->param('token', '', 'string');
        if($token !== md5($id.Config::get('public.key'))){
            exit('非法操作');
        }

        $where['services.id'] = $id;
        $detail = ServicesService::self()->service_detail($where);
        // dump($detail);die();
        $detail['category'] = ConfigModel::self()->where(['type'=>1])->select();
        return $this->fetch('service_detail', $detail);
    }

    /**
     * 支持与帮助
     */
    public function demand_detail()
    {
        $id = $this->request->param('id', '', 'intval');
        if(!$id){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $where['services.id'] = $id;
        $detail = ServicesService::self()->demand_detail($where);
        return $this->fetch('demand_detail', $detail);
    }




}
