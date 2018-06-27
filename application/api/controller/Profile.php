<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;
use think\Validate;
use app\common\controller\ApiLogin;
use app\api\service\UserService;
use app\api\service\ServicesService;
use app\api\service\OrderService;
/**
 * Class Profile
 * @package app\api\controller
 */
class Profile extends ApiLogin {
    /**
     * 获取用户信息接口
     * @method post
     * @parameter string token 必须
     */
    public function index(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $user = UserService::self()->detail($uid, $this->user['uid']);
        if(!$user){
            $this->wrong(0, '用户不存在');
        }
        $this->response($user);
    }

    /**
     * 更新用户信息
     * @method post
     * @parameter string nickname
     */
    public function update(){
        $user = UserService::self()->update($this->param, $this->user['uid']);
        return $this->response($user, 1, '修改成功');
    }

    /**
     * 我的服务订单
     * purchase 购买  sell 出售
     */
    public function service_order(){
        $type = $this->request->param('type', 'purchase', 'string');
        $status = $this->request->param('status', 0, 'string');
        // 订单分类
        switch ($type) {
            case 'sell':
                $where['services.created_uid'] = $this->user['uid'];
                break;
            case 'purchase':
                $where['orders.created_uid'] = $this->user['uid'];
                break;
        }
        // 订单状态
        $where['orders.status'] = ['neq', 6];
        if($status){
            $where['orders.status'] = intval($this->param['status']);
        }
        $where['orders.source_type'] = 1;
        $lists = OrderService::self()->lists($where,$this->page, $this->limit, 'service', $type);
        $this->response($lists, 1, 'success');
    }
    /**
     * 我的邀约订单 
     * part参加  create发起
     */
    public function demand_order(){
        $type = $this->request->param('type', 'part', 'string');
        $status = $this->request->param('status', 0, 'string');
        // 订单分类
        switch ($type) {
            case 'create':
                $where['services.created_uid'] = $this->user['uid'];
                break;
            case 'part':
                $where['orders.created_uid'] = $this->user['uid'];
                break;
        }
        // 订单状态
        $where['orders.status'] = ['neq', 6];
        if($status){
            $where['orders.status'] = $this->param['status'];
        }
        $where['orders.source_type'] = 2;
        $lists = OrderService::self()->lists($where,$this->page, $this->limit, 'demand', $type);
        $this->response($lists, 1, 'success');
    }
    /**
     * 我的收藏
     */
    public function collection(){
        $where['collections.uid'] = $this->user['uid'];
        $lists = ServicesService::self()->collect_lists($where, $this->page, $this->limit, $this->user['latitude'], $this->user['longitude']);
        $this->response($lists, 1, 'success');
    }
    /**
     * 我的服务
     */
    public function myservice(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $uid = $this->user['uid'];
        }
        $where['type'] = 1;
        $where['is_del'] = 0;
        $where['created_uid'] = $uid;
        $lists = ServicesService::self()->service_lists($where, $this->page, $this->limit, $this->user['latitude'], $this->user['longitude']);
        $this->response($lists, 1, 'success');
    }

    /**
     * 我的邀约
     */
    public function mydemand(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $uid = $this->user['uid'];
        }
        $where['type'] = 2;
        $where['is_del'] = 0;
        $where['created_uid'] = $uid;
        $lists = ServicesService::self()->demand_lists($where, $this->page, $this->limit, $this->user['latitude'], $this->user['longitude']);
        $this->response($lists, 1, 'success');
    }

    /**
     * 我的钱包
     */
    public function mywallet(){
        $wallet =UserService::self()->mywallet($this->user['uid']);
        $this->response($wallet, 1, 'success');
    }
    /**
     * 流水列表
     */
    public function running(){
        $tmp_lists =UserService::self()->running($this->user['uid'], $this->page, $this->limit);
        $this->response($tmp_lists, 1, 'success');
    }
    /**
     * 绑定提现账户信息
     */
    public function bind(){
        $account = $this->request->param('account', '', 'string');
        $account_type = $this->request->param('account_type', 2, 'intval');//默认支付宝
        $res =UserService::self()->bind($this->user['uid'], $account_type, $account);
        if($res !== true){
            $this->wrong(0, $res);
        }
        $this->response([], 1, 'success');
    }
    /**
     * 获取账户信息
     */
    public function bind_list(){
        $list =UserService::self()->bind_list($this->user['uid']);
        $this->response($list, 1, 'success');
    }
}
