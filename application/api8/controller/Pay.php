<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\controller;
use think\Db;
use app\common\controller\ApiLogin;
use app\api8\service\UserService;
use app\api8\service\PayService;
use app\api8\service\LogsService;
/**
 * Class Order
 * @package app\api8\controller
 * 支付接口
 */
class Pay extends ApiLogin {
    /**
     * 账户充值
     * 发起充值请求  添加充值订单 流水记录，回调支付成功后修改账户余额，流水记录信息
     */
    public function recharge(){
        $account = $this->request->param('money', 0, 'floatval');
        $pay_type = $this->request->param('pay_type', 0, 'intval');
        if(!$account || !$pay_type){
            $this->wrong(0, '非法请求，缺少参数');
        }
        if($account <= 0){
            $this->wrong(0, '充值金额必须大于0');
        }
        // 生成相应的充值订单
        $order = UserService::self()->generate_recharge_order($account, $pay_type, $this->user);
        if(!isset($order['code']) || $order['code'] != 1){
            $this->wrong(0, $order);
        }
        // 发起订单支付请求 微信  or  支付宝
        $return['pay'] = PayService::self()->recharge_call($pay_type, $order['detail']);
	    if($pay_type == 1){
		    $return['pay']['order_no'] = $order['detail']['order_no'];
        }
	 // 微信支付各种错误验证提示
        if($pay_type == 1 && $return['pay']['return_code'] == 'FAIL'){
            $this->wrong(0, $return['pay']['return_msg']);
        }
        // 返回支付订单信息 供客户端调起支付请求
        $remarks = ['money'=>$account, 'pay_type'=>$pay_type, 'intro'=>'调用支付'];
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, $order['detail']['order_id'], OTHER, SUC_ACT, $remarks);
        return $this->response($return, 1, '调用支付');
    }
    /**
     * 查询充值结果即 验证充值结果OK
     */
    public function alipay_recharge_search(){
        $resultStatus = $this->request->param('resultStatus', 0, 'string');
        $param = $this->request->param('result', 0, 'string');
        $param = html_entity_decode($param);
        $param = html_entity_decode($param);
        $param = json_decode($param, true);
        $param['current_uid'] = $this->user['uid'];
        $pay = PayService::self()->alipay_recharge_search($resultStatus, $param);
        if($pay !== true){
            // 支付失败，修改订单状态为失败
            $result = $param['alipay_trade_app_pay_response'];
            if(isset($result['out_trade_no'])){
                $out_trade_no = $result['out_trade_no'];
                PayService::self()->recharge_error($out_trade_no, 2);
            }
            $remarks = ['param'=>$param, 'intro'=>'支付失败'];
            LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, SUC_ACT, $remarks);
            $this->wrong(0, $pay);
        }
        $remarks = ['param'=>$param, 'intro'=>'支付成功'];
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, SUC_ACT, $remarks);
        return $this->response([], 1, '支付成功');
    }
    /**
     * 账户提现
     */
    public function withdraw(){
        $money = $this->request->param('money', 0, 'floatval');
        $pay_type = $this->request->param('pay_type', 2, 'intval');
        $account = $this->request->param('account', '', 'string');
        $payee_real_name = $this->request->param('payee_real_name', '', 'string');

        if(!$money){
            $this->wrong(0, '请输入提现金额');
        }
        // 前期测试 0.1 
        if($money < 0.1){
            $this->wrong(0, '提现金额最小为0.1元');
        }
        if(!$account){
            $this->wrong(0, '请输入提现账号');
        }
        
        // 生成对应的提现订单
        $order = UserService::self()->generate_withdraw_order($this->user['uid'], $money, $pay_type);
        if(!isset($order['code']) || $order['code'] != 1){
            $this->wrong(0, $order);
        }
        // 调用单笔转账到支付宝账号接口
        $pay = PayService::self()->withdraw($account, $payee_real_name, $pay_type, $order['detail']);
        if($pay !== true){
            $remarks = ['param'=>$order, 'intro'=>'提现失败'];
            LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, SUC_ACT, $remarks);
            $this->wrong(0, $pay);
        }
        $remarks = ['param'=>$order, 'intro'=>'提现成功'];
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, SUC_ACT, $remarks);
        return $this->response([], 1, '提现成功');
    }
    /**
     * 发起支付宝支付请求
     * 订单支付  支付宝支付
     */
    public function alipay_order(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id || $order_id <= 0){
            $this->wrong(0, '非法请求，订单id不存在');
        }
        // 检测订单状态
        $where['order_id'] = $order_id;
        $order = Db::table('orders')->where($where)->find();
        if(!$order){
            $this->wrong(0, '订单不存在，请刷新再试');
        }
        if($order['total_fee'] == 0){
            $this->wrong(0, '订单金额为0，不能支付');
        }
        if($order['status'] == 3){
            $this->wrong(0, '订单已支付');
        }
        if($order['status'] != 2){
            $this->wrong(0, '该订单状态不支持支付');
        }

        // 创建支付订单
        $pay = PayService::self()->order_pay($order, 2);
        if($pay === false){
            $this->wrong(0, '支付失败，请刷新再试');
        }
        $return['pay'] = $pay;
        return $this->response($return, 1, '调用支付');
    }
    /**
     * 订单查询接口
     */
    public function alipay_order_search(){
        $resultStatus = $this->request->param('resultStatus', 0, 'string');
        $param = $this->request->param('result', 0, 'string');
        $param = html_entity_decode($param);
        $param = html_entity_decode($param);
        $param = json_decode($param, true);
        // 当前用户信息
        $param['current_uid'] = $this->user['uid'];
        $pay = PayService::self()->alipay_order_search($resultStatus, $param);
        if($pay !== true){
            $this->wrong(0, $pay);
        }
        return $this->response([], 1, '支付成功');
    }
    /**
     * 发起微信支付
     */
    public function wx_order(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id || $order_id <= 0){
            $this->wrong(0, '非法请求，订单id不存在');
        }
        // 检测订单状态
        $where['order_id'] = $order_id;
        $order = Db::table('orders')->where($where)->find();
        if(!$order){
            $this->wrong(0, '订单不存在，请刷新再试');
        }
        if(!$order['total_fee']){
            $this->wrong(0, '订单金额为0，不能支付');
        }
        if($order['status'] == 3){
            $this->wrong(0, '订单已支付');
        }
        if($order['status'] != 2){
            $this->wrong(0, '该订单状态不支持支付');
        }

        // 创建支付订单
        $pay = PayService::self()->order_pay($order, 1);
        if($pay['return_code'] == 'FAIL'){
            $this->wrong(0, $pay['return_msg']);
        }

        if($pay['result_code'] == 'FAIL'){
            if($pay['err_code'] == 'ORDERPAID'){
                // 订单已支付 处理后续问题
                PayService::self()->wx_order_search($order['order_no'], $this->user['uid']);
            }
            $this->wrong(0, $pay['err_code_des']);
        }
        if($pay === false){
            $this->wrong(0, '支付失败，请刷新再试');
        }
        $pay['order_no'] = $order['order_no'];
        $return['pay'] = $pay;
        return $this->response($return, 1, '调用支付');
    }
    /**
     * 微信支付回调查询
     */
    public function wx_order_search(){
        // $transaction_id = $this->request->param('transaction_id', 0, 'string');
        $order_no = $this->request->param('order_no', 0, 'string');
        if(!$order_no){
            $this->wrong(0, '请输入订单号');
        }
        $pay = PayService::self()->wx_order_search($order_no, $this->user['uid']);
        if($pay !== true){
            $remarks = ['order_no'=>$order_no, 'intro'=>'微信支付失败'];
            LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, SUC_ACT, $remarks);
            $this->wrong(0, $pay);
        }
        $remarks = ['order_no'=>$order_no, 'intro'=>'微信支付成功'];
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, SUC_ACT, $remarks);
        return $this->response([], 1, '支付成功');
    }

    /**
     * 余额支付订单
     */
    public function account(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id || $order_id <= 0){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $res = PayService::self()->pay_by_account($order_id, $this->user['uid']);
        if($res !== true){
            $this->wrong(0, $res);
        }
        return $this->response([], 1, '支付成功');
    }

    /**
     * 查询交易状态
     */
    public function alipay_status(){
        $out_trade_no = $this->request->param('out_trade_no', '', 'string');
        $res = PayService::self()->alipay_status($out_trade_no);
        if($res !== true){
            $this->wrong(0, '支付失败');
        }
        return $this->response([], 1, '支付成功');
    }

    /////////////////////////微信支付相关接口///////////////////////////////////////////////
    /**
     * 订单查询
     */
    public function wx_recharge_search(){
        // $transaction_id = $this->request->param('transaction_id', 0, 'string');
        $order_no = $this->request->param('order_no', 0, 'string');
        if(!$order_no){
            $this->wrong(0, '请输入订单号');
        }
        $pay = PayService::self()->wxpay_search($order_no);
        if($pay !== true){
            $remarks = ['order_no'=>$order_no, 'intro'=>'微信支付失败'];
            LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, SUC_ACT, $remarks);
            $this->wrong(0, $pay);
        }
        $remarks = ['order_no'=>$order_no, 'intro'=>'微信支付成功'];
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], ADD_ACT, 0, OTHER, SUC_ACT, $remarks);
        return $this->response([], 1, '支付成功');
    }

}
