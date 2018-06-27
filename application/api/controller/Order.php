<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;
use think\Validate;
use app\api\service\OrderService;
use app\common\controller\ApiLogin;
use app\api\service\PayService;

/**
 * Class Order
 * @package app\api\controller
 * 订单接口
 */
class Order extends ApiLogin {

    private $rule = [
        'intro'  =>  'require',
        'date_time'  =>  'require',
        'time_long'  =>  'require',
        'province_id'  =>  'require',
        'city_id'  =>  'require',
        'area_id'  =>  'require',
        'address'  =>  'require',
        'source_id'  =>  'require',
    ];

    private $message  =   [
        'intro.require' => '请输入服务内容',
        'date_time.require' => '请输入服务日期',
        'time_long.require' => '请输入服务时长',
        'province_id.require'     => '请选择服务所在省',
        'city_id.require'   => '请选择服务所在市',
        'area_id.require'   => '请选择服务所在区',
        'address.require'   => '请选择服务详细地址',
        'source_id.require'   => '非法请求',
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
        $res = OrderService::self()->post_service($this->param, $this->user);
        if(!is_int($res)){
            $this->wrong(0, $res);
        }
        $this->response(['id'=>$res] , 1, '提交成功');
    }

    /**
     * 参加邀约 下单
     */
    public function demand(){
        $source_id = $this->request->param('source_id', 0, 'intval');
        if(!$source_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
	    // 检测是否存在未完成订单
        $order = OrderService::self()->order_check($source_id, 2 ,$this->user['uid']);
	    if($order){
            $this->wrong(0, '您有未完成订单，请先完成');
        }
        $code = OrderService::self()->post_demand($this->param, $this->user['uid'], $tips);
        if($code !== true){
            $this->wrong($code, $tips);
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
        // $source_type = $this->request->param('source_type', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $res = OrderService::self()->confirm_service_order($order_id, $this->user['uid']);
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
    public function cancel(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        $source_type = $this->request->param('source_type', 0, 'intval');
        if(!$source_type || !$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $msg = OrderService::self()->cancel($order_id,$this->user['uid']);
        if($msg !== true){
           $this->wrong(0, $msg);
        }
        $this->response(['id'=>$order_id] , 1, '操作成功');
    }
    /**
     * 确认完成订单包括  服务和 邀约
     */
    public function finish(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        $source_type = $this->request->param('source_type', 0, 'intval');
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
     */
    public function del(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        // $source_type = $this->request->param('source_type', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        $msg = OrderService::self()->del_order($order_id,$this->user['uid']);
        if($msg !== true){
           $this->wrong(0, $msg);
        }
        $this->response([] , 1, '操作成功');
    }

}
