<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;
use app\common\controller\ApiLogin;
use app\api\service\OrderService;
use app\api\service\UserService;
use app\api\service\PayService;
/**
 * Class Order
 * @package app\api\controller
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
        $order = UserService::self()->recharge($account, $pay_type, $this->user);
        if(!is_array($order)){
            $this->wrong(0, $order);
        }
        // 发起订单支付请求 微信  or  支付宝
        $pay = PayService::self()->call($pay_type, $order);
        if($pay === false){
            $this->wrong(0, '支付失败，请稍后再试');
        }
        $return['pay'] = $pay;
        // 返回支付订单信息 供客户端调起支付请求
        return $this->response($return, 1, '调用支付');
    }
    /**
     * 查询充值结果即 验证充值结果OK
     */
    public function alipay_search(){
        $resultStatus = $this->request->param('resultStatus', 0, 'string');
        $param = $this->request->param('result', 0, 'string');
        $param = html_entity_decode($param);
        $param = html_entity_decode($param);
        $param = json_decode($param, true);
        $pay = PayService::self()->recharge_search($resultStatus, $param);
        if($pay !== true){
            // 支付失败，修改订单状态为失败
            $result = $param['alipay_trade_app_pay_response'];
            if(isset($result['out_trade_no'])){
                $out_trade_no = $result['out_trade_no'];
                PayService::self()->recharge_error($out_trade_no, 2);
            }
            $this->wrong(0, $pay);
        }
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
        $order = UserService::self()->withdraw($this->user['uid'], $money, $pay_type);
        if(!is_array($order)){
            $this->wrong(0, $order);
        }
        // 调用单笔转账到支付宝账号接口
        $pay = PayService::self()->withdraw($account, $payee_real_name, $pay_type, $order);
        if($pay === false){
            $this->wrong(0, '提现失败');
        }
        return $this->response($order, 1, '提现成功');
    }
    /**
     * 发起支付宝支付请求
     * 订单支付  支付宝支付
     */
    public function alipay(){
        $order_id = $this->request->param('order_id', 0, 'intval');
        if(!$order_id || $order_id <= 0){
            $this->wrong(0, '非法请求，订单id不存在');
        }
        // 获取订单信息
        $order = OrderService::self()->detail($order_id);
        if(!$order) {
            $this->wrong(0, '非法请求，订单信息不存在');
        }
        // 创建支付订单
        $pay = PayService::self()->order_pay($order, 2);
        if($pay === false){
            $this->wrong(0, '支付失败，请稍后再试');
        }
        $return['pay'] = $pay;
        return $this->response($return, 1, '调用支付');
    }
    /**
     * 订单查询接口
     */
    public function ali_order_search(){
        $resultStatus = $this->request->param('resultStatus', 0, 'string');
        $param = $this->request->param('result', 0, 'string');
        $param = html_entity_decode($param);
        $param = html_entity_decode($param);
        $param = json_decode($param, true);
        $pay = PayService::self()->alipay_order_search($resultStatus, $param);
        if($pay !== true){
            // 支付失败，修改订单状态为失败
            $result = $param['alipay_trade_app_pay_response'];
            if(isset($result['out_trade_no'])){
                $out_trade_no = $result['out_trade_no'];
                PayService::self()->order_pay_error($out_trade_no);
            }
            $this->wrong(0, $pay);
        }
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

    
}
