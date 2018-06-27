<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api3\controller;
use app\common\controller\ApiLogin ;
use app\api3\model\UserExtModel;
use app\api3\service\UserService;
use app\api3\service\ServicesService;
use app\api3\service\CommentsService;
/**
 * Class Profile
 * @package app\api3\controller
 * 发布接口
 */
class Lists extends ApiLogin {
    /**
     * 服务列表
     * @method post
     * @parameter string token 必须
     */
    public function service(){
        $latitude = $this->request->param('latitude');
        if($latitude == '' || $latitude == null){
            $latitude = NULL;
        }else{
            $latitude = floatval($latitude);
        }
        // if(!$latitude){
        //    $this->wrong(0, '缺少经度参数或者参数不能为空');
        // }
        $longitude = $this->request->param('longitude');
        if($longitude == '' || $longitude == null){
            $longitude = NULL;
        }else{
            $longitude = floatval($longitude);
        }
        // if(!$longitude){
        //    $this->wrong(0, '缺少经度参数或者参数不能为空');
        // }
        // 更新用户经纬度
        $user['latitude'] = $latitude;
        $user['longitude'] = $longitude;
        UserExtModel::self()->updateExt($user, $this->user['uid']);
        // 清楚缓存
        UserService::self()->cleanCache($this->user['uid']);

        $param['category_id'] = $this->request->param('category_id', 0, 'string');
        $param['time_id'] = $this->request->param('time_id', 0, 'intval');
        $param['sort'] = $this->request->param('sort', 0, 'intval');
        $param['sex'] = $this->request->param('sex', 0, 'intval');
	    $param['key'] = $this->request->param('key', 0, 'string');
        $lists = ServicesService::self()->service_lists($param, $this->page, $this->limit, $this->user);
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
        $lists = ServicesService::self()->demand_lists($param, $this->page, $this->limit, $this->user);
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


}
