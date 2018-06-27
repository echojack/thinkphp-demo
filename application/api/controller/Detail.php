<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;

use think\Validate;
use app\common\controller\ApiLogin ;
use app\api\service\ServicesService;
use app\api\service\UserService;


/**
 * Class Get
 * @package app\api\controller
 * 获取详细信息
 */
class Detail extends ApiLogin {
    /**
     * 服务详情
     * @method post
     * @parameter string token 必须
     */
    public function service(){
        $id = $this->request->param('id', 0, 'intval');
        if(!$id){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $where['services.id'] = $id;
        $detail = ServicesService::self()->service_detail($where, $this->user['uid']);
        if(!$detail){
            $this->wrong('-1', '服务不存在或已被删除');
        }
        $this->response($detail, 1, 'success');
    }

    /**
     * 邀约详情
     * @method post
     * @parameter string token 必须
     */
    public function demand(){
        $id = $this->request->param('id', 0, 'intval');
        if(!$id){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $where['services.id'] = $id;
        $detail = ServicesService::self()->demand_detail($where, $this->user);
        if(!$detail){
            $this->wrong(0, '邀约不存在或已被删除');
        }
        $this->response($detail, 1, 'success');
    }
    /**
     * 用户个人主页信息
     */
    public function user(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $return = UserService::self()->detail($uid, $this->user['uid']);

        // 服务信息
        $list_where['services.created_uid'] = $uid;
        $list_where['services.type'] = 1;
	    $list_where['services.is_del'] = 0;
        $service = ServicesService::self()->service_lists($list_where,1, 2, $this->user['latitude'], $this->user['longitude']);
        $service_count = ServicesService::self()->service_lists_count($list_where);
        // 邀约信息
        $list_where['services.type'] = 2;
        $demand = ServicesService::self()->demand_lists($list_where, 1, 2, $this->user['latitude'], $this->user['longitude']);
        $demand_count = ServicesService::self()->demand_lists_count($list_where);

        $return['service'] = $service ;
        $return['service_count'] = $service_count ;
        $return['demand'] = $demand ;
        $return['demand_count'] = $demand_count ;
        $this->response($return);
    }
    
}
