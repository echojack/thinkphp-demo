<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api7\controller;
use think\Validate;
use app\api7\service\OrderService;
use app\common\controller\ApiLogin;
use app\api7\service\LogsService;


/**
 * Class Order
 * @package app\api7\controller
 * 订单接口
 */
class Order extends ApiLogin {

    private $rule = [
        'source_id'     =>  'require|gt:0',
        'date_time'     =>  'require',
        'time_long'     =>  'require',
        // 'province_id'   =>  'require',
        // 'city_id'       =>  'require',
        // 'area_id'       =>  'require',
        // 'address'       =>  'require',
        // 'intro'         =>  'require',
    ];

    private $message  =   [
        'source_id.require'     => '请输入资源id',
        'source_id.gt'          => '资源id必须大于0',
        'date_time.require'     => '请输入服务日期',
        'time_long.require'     => '请输入服务时长',
        // 'province_id.require'   => '请选择服务所在省',
        // 'city_id.require'       => '请选择服务所在市',
        // 'area_id.require'       => '请选择服务所在区',
        // 'address.require'       => '请选择服务详细地址',
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

        $param['source_id'] = $source_id;

        if(!is_int($order_id)){
            $param['intro'] = '服务马上约失败';
            LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $source_id, SERVICE, ERR_ACT, $param);
            $this->wrong(0, $order_id);
        }
        $param['intro'] = '服务马上约成功';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $source_id, SERVICE, SUC_ACT, $param);
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

        $param['id'] = $id;
        if($code !== true){
            $param['intro'] = '邀约失败';
            LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $id, DEMAND, ERR_ACT, $param);
            $this->wrong(0, $code);
        }
        $param['intro'] = '邀约失败';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $id, DEMAND, SUC_ACT, $param);
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

        $param['order_id'] = $order_id;
        $param['intro'] = '确认订单成功';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
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
        $param['order_id'] = $order_id;
        $param['intro'] = '取消订单成功';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
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
        $param['order_id'] = $order_id;
        $param['intro'] = '完成订单';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
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
        $param['order_id'] = $order_id;
        $param['intro'] = '删除订单';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
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
        $param['order_id'] = $order_id;
        $param['intro'] = '拒绝订单';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
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
        $param['order_id'] = $order_id;
        $param['intro'] = '同意订单';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
        $this->response([] , 1, '操作成功');
    }

    /**
     * 编辑 服务  下单
     */
    public function post_zh_order(){
        // 服务id
        $id = $this->request->param('id', 0, 'intval');
        $my_service_id = $this->request->param('my_service_id', 0, 'intval');
        if(!$id || !$my_service_id){
            $this->wrong(0, '请输入要置换的服务id');
        }
        // 检测是否存在未完成订单
        $check = OrderService::self()->check_zh_order($id, $my_service_id, $this->user['uid']);
        if($check !== true){
            $this->wrong(0, $check);
        }
        $order_id = OrderService::self()->post_zh_order($id, $my_service_id, $this->user['uid']);
        if(!$order_id){
            $param['intro'] = '添加置换订单失败';
           LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $id, SERVICE, ERR_ACT, $param);
            $this->wrong(0, '置换申请失败');
        }
        $param['intro'] = '添加置换订单成功';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $id, SERVICE, SUC_ACT, $param);
        $this->response(['id'=>$order_id] , 1, '置换申请成功，请耐心等待');
    }

    /**
     * 确认置换订单
     * 订单状态（1：待确认;2:已确认待付款；3：已付款；4：已完成；5已取消包括自己取消和对方拒绝）;6：已删除订单;7:支付失败
     */
    public function zh_confirm(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        // 检测是否存在未完成订单
        $check = OrderService::self()->zh_order_deal_check($order_id, $this->user['uid']);
        if(!$check){
            $this->wrong(0, '无权操作');
        }

        $order = OrderService::self()->detail($order_id);
        if($order['status'] != 1){
            $this->wrong(0, '订单状态已修改，请刷新再试');
        }

        $res = OrderService::self()->zh_order_deal($order_id, $this->user['uid'], 2);
        if(!$res){
            $this->wrong(0, '操作失败，请刷新再试');
        }
        $param['order_id'] = $order_id;
        $param['intro'] = '确认置换订单';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
        $this->response([] , 1, '操作成功');   
    }
    /**
     * 拒绝置换订单
     * 订单状态（1：待确认;2:已确认待付款；3：已付款；4：已完成；5已取消包括自己取消和对方拒绝）;6：已删除订单;7:支付失败
     */
    public function zh_reject(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        // 检测是否存在未完成订单
        $check = OrderService::self()->zh_order_deal_check($order_id, $this->user['uid']);
        if(!$check){
            $this->wrong(0, '无权操作');
        }

        $res = OrderService::self()->zh_order_deal($order_id, $this->user['uid'], 5);
        if(!$res){
            $this->wrong(0, '操作失败，请刷新再试');
        }
        $param['order_id'] = $order_id;
        $param['intro'] = '拒绝置换订单';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
        $this->response([] , 1, '操作成功');   
    }
    /**
     * 完成置换订单
     * 订单状态（1：待确认;2:已确认待付款；3：已付款；4：已完成；5已取消包括自己取消和对方拒绝）;6：已删除订单;7:支付失败
     */
    public function zh_finish(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        // 检测是否存在未完成订单
        $check = OrderService::self()->zh_order_deal_check($order_id, $this->user['uid']);
        if(!$check){
            $this->wrong(0, '无权操作');
        }

        $order = OrderService::self()->detail($order_id);
        if($order['status'] != 2){
            $this->wrong(0, '订单状态已修改，请刷新再试');
        }

        $res = OrderService::self()->zh_order_deal($order_id, $this->user['uid'], 4);
        if(!$res){
            $this->wrong(0, '操作失败，请刷新再试');
        }
        $param['order_id'] = $order_id;
        $param['intro'] = '完成置换订单';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
        $this->response([] , 1, '操作成功');   
    }
    /**
     * 取消置换订单
     * 订单状态（1：待确认;2:已确认待付款；3：已付款；4：已完成；5已取消包括自己取消和对方拒绝）;6：已删除订单;7:支付失败
     */
    public function zh_cancel(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
        // 检测是否存在未完成订单
        $check = OrderService::self()->zh_order_deal_check($order_id, $this->user['uid']);
        if(!$check){
            $this->wrong(0, '无权操作');
        }

        $res = OrderService::self()->zh_order_deal($order_id, $this->user['uid'], 5);
        if(!$res){
            $this->wrong(0, '操作失败，请刷新再试');
        }
        $param['order_id'] = $order_id;
        $param['intro'] = '取消置换订单';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
        $this->response([] , 1, '操作成功');   
    } 
    /**
     * 删除置换订单
     * 订单状态（1：待确认;2:已确认待付款；3：已付款；4：已完成；5已取消包括自己取消和对方拒绝）;6：已删除订单;7:支付失败
     */
    public function zh_del(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id){
           $this->wrong(0, '非法操作，缺少参数');
        }
         // 检测是否存在未完成订单
        $check = OrderService::self()->zh_order_deal_check($order_id, $this->user['uid']);
        if(!$check){
            $this->wrong(0, '无权操作');
        }
        $order = OrderService::self()->detail($order_id);
        if($order['status'] == 2){
            $this->wrong(0, '订单已确认，不能删除');
        }
        
        $res = OrderService::self()->zh_order_deal($order_id, $this->user['uid'], 6);
        if(!$res){
            $this->wrong(0, '操作失败，请刷新再试');
        }
        $param['order_id'] = $order_id;
        $param['intro'] = '删除置换订单';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, $order_id, ORDER, SUC_ACT, $param);
        $this->response([] , 1, '操作成功');   
    }    

    
}
