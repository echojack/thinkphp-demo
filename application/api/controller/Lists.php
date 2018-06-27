<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;
use think\Validate;
use app\common\controller\ApiLogin ;
use app\api\model\UserExtModel;
use app\api\service\UserService;
use app\api\service\ServicesService;
/**
 * Class Profile
 * @package app\api\controller
 * 发布接口
 */
class Lists extends ApiLogin {
    /**
     * 服务列表
     * @method post
     * @parameter string token 必须
     */
    public function service(){
        $latitude = $this->request->param('latitude', '', 'floatval');
        if(!$latitude){
           $this->wrong(0, '缺少经度参数或者参数不能为空');
        }
        $longitude = $this->request->param('longitude', '', 'floatval');
        if(!$longitude){
           $this->wrong(0, '缺少经度参数或者参数不能为空');
        }
        // 更新用户经纬度
        $user['latitude'] = $latitude;
        $user['longitude'] = $longitude;
        UserExtModel::self()->updateExt($user, $this->user['uid']);
	    // 清楚缓存
        UserService::self()->refreshToken($this->user['uid']);

        $category_id = $this->request->param('category_id', 0, 'intval');
        if($category_id){
            $service_ids = ServicesService::self()->service_ids($category_id);
            $where['services.id'] = ['in', $service_ids];
        }
        $time_id = $this->request->param('time_id', 0, 'intval');
        if($time_id){
            $where['st.time_id'] = $time_id;
        }
        if($this->request->has('sex')){
            $where['ue.sex'] = $this->request->param('sex', 0, 'intval');
        }
	    // 获取未删除的列表数据
        $where['services.is_del'] = 0;
        $where['services.type'] = 1;
        $where['services.status'] = 1;
        $lists = ServicesService::self()->service_lists($where, $this->page, $this->limit, $this->user['latitude'], $this->user['longitude']);
        $this->response($lists, 1, 'success');
    }

    /**
     * 邀约列表
     * @method post
     * @parameter string token 必须
     */
    public function demand(){
        $category_id = $this->request->param('category_id', 0, 'intval');
        if($category_id){
            $where['sc.category_id'] = $category_id;
        }
	    // 未删除状态
        $where['services.is_del'] = 0;
        $where['services.type'] = 2;
        $where['services.status'] = 1;
        $lists = ServicesService::self()->demand_lists($where, $this->page, $this->limit, $this->user['latitude'], $this->user['longitude']);
        $this->response($lists, 1, 'success');
    }
    /**
     * 精选服务列表
     */
    public function recommend_service(){
        $where['services.is_del'] = 0;
        $where['services.type'] = 1;
        $where['services.status'] = 1;
        $lists = ServicesService::self()->service_lists($where, $this->page, $this->limit, $this->user['latitude'], $this->user['longitude']);
        $this->response($lists, 1, 'success');
    }


}
