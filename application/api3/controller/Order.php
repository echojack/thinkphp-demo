<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api3\controller;
use think\Validate;
use app\api3\service\OrderService;
use app\common\controller\ApiLogin;

/**
 * Class Order
 * @package app\api3\controller
 * 订单接口
 */
class Order extends ApiLogin {

    private $rule = [
        'source_id'     =>  'require',
        'date_time'     =>  'require',
        'time_long'     =>  'require',
        'province_id'   =>  'require',
        'city_id'       =>  'require',
        'area_id'       =>  'require',
        'address'       =>  'require',
        // 'intro'         =>  'require',
    ];

    private $message  =   [
        'source_id.require'     => '非法请求',
        'date_time.require'     => '请输入服务日期',
        'time_long.require'     => '请输入服务时长',
        'province_id.require'   => '请选择服务所在省',
        'city_id.require'       => '请选择服务所在市',
        'area_id.require'       => '请选择服务所在区',
        'address.require'       => '请选择服务详细地址',
        // 'intro.require'         => '订单备注',
    ];

    /**
     * 编辑 服务  下单
     */
    public function service(){
        $validate = new Validate($this->rule, $this->message);
        $result   = $validate->check($this->param);
        if(!$result){
            $this->wrong(0,$validate->getError());
        }
        $source_id = intval($this->param['source_id']);
        // 检测是否存在未完成订单
        $order = OrderService::self()->order_check($source_id, 1 ,$this->user['uid']);
        if($order){
            $this->wrong(0, '您有未完成订单，请先完成');
        }
        $order_id = OrderService::self()->post_service($this->param, $this->user);
        if(!is_int($order_id)){
            $this->wrong(0, $order_id);
        }
        $this->response(['id'=>$order_id] , 1, '提交成功');
    }

    /**
     * 参加邀约 下单
     */
    public function demand(){
        $id = $this->request->param('id', 0, 'intval');
        if(!$id){
           $this->wrong(0, '非法操作，缺少参数');
        }
	    // 检测是否存在未完成订单
        $order = OrderService::self()->order_check($id, 2 ,$this->user['uid']);
	    if($order){
            $this->wrong(0, '您有未完成订单，请先完成');
        }
        $code = OrderService::self()->post_demand($this->param, $this->user);
        if($code !== true){
            $this->wrong(0, $code);
        }
        $this->response([] , 1, '提交成功');
    }
    /**
     * 订单详情
     */
    public function detail(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $detail = OrderService::self()->detail($order_id);
        $this->response($detail , 1, 'success');
    }
    /**
     * 确认订单 只有服务订单可以确认
     */
    public function confirm(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $res = OrderService::self()->confirm_service_order($order_id, $this->user);
	    if($res === '20001'){
            $this->wrong('20001', '账户余额不足，请先充值');
        }else if($res !== true){
            $this->wrong(0, $res);
        }
        $this->response([] , 1, '操作成功');       
    }
    /**
     * 取消服务订单
     */
    public function freeze_tips(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        $total_fee = $this->request->param('total_fee', 0, 'floatval');
        if(!$order_id || !$total_fee){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $tips = OrderService::self()->freeze_tips($total_fee);
        $this->response($tips , 1, 'success');
    }
    /**
     * 确认取消
     */
    public function cancel(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $msg = OrderService::self()->cancel_order($order_id,$this->user);
        if($msg !== true){
           $this->wrong(0, $msg);
        }
        $this->response(['id'=>$order_id] , 1, '操作成功');
    }
    /**
     * 确认完成
     */
    public function finish(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $msg = OrderService::self()->finish_order($order_id,$this->user['uid']);
        if($msg !== true){
           $this->wrong(0, $msg);
        }
        $this->response(['id'=>$order_id] , 1, '操作成功');
    }
    /**
     * 删除订单
     * 只有订单的创建者可以删除订单
     * 只有 已完成 和 已取消的订单可以删除
     */
    public function del(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $msg = OrderService::self()->del_order($order_id,$this->user);
        if(!$msg){
           $this->wrong(0, '删除失败，请刷新再试');
        }
        $this->response([] , 1, '删除成功');
    }

    /**
     * 拒绝邀约
     */
    public function reject(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $msg = OrderService::self()->reject_order($order_id,$this->user);
        if(!$msg){
           $this->wrong(0, '操作失败，请刷新再试');
        }
        $this->response([] , 1, '操作成功');
    }
    /**
     * 同意
     */
    public function agree(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $msg = OrderService::self()->agree_order($order_id,$this->user);
        if(!$msg){
           $this->wrong(0, '操作失败，请刷新再试');
        }
        $this->response([] , 1, '操作成功');
    }


}
