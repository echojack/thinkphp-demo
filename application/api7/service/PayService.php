<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api7\service;
use think\Db;
use think\Config;
use app\api7\service\OrderService;
use app\api7\service\UserService;
use app\api7\service\PushService;
use app\api7\model\CallbackModel;
use app\api7\model\RunningModel;
// 微信相关
use wxpay3\lib\WxPayApi;
use wxpay3\lib\WxPayNotify;
use wxpay3\lib\WxPayUnifiedOrder;
use wxpay3\lib\WxPayOrderQuery;
use wxpay3\lib\WxPayResults;

// 支付宝支付
use alipay\aop\AopClient;
use alipay\aop\request\AlipayTradeAppPayRequest;
use alipay\aop\request\AlipayFundTransToaccountTransferRequest;
use alipay\aop\request\AlipayTradeQueryRequest;
use alipay\aop\request\AlipayTradeCancelRequest;

class PayService {
    public static function self(){
        return new self();
    }
    //////////////////////////////////////充值相关///////////////////////////////////////////////////
    /**
     * 充值入口
     * @param pay_type 支付方式
     * @param param 支付订单相关参数
     */
    public function recharge_call($pay_type = 1,$order = []){
        switch ($pay_type) {
            case 1:
                $notify_url = url('Notify/wx_recharge_callback', '', false, true);
                $detail = $this->_wxpay_order($order, $notify_url);
                break;
            case 2:
                $notify_url = url('Notify/recharge_callback', '', false, true);
                $detail = $this->_alipay_order($order, $notify_url);
                break;
        }
        return $detail;
    }

    /**
     * 创建支付宝支付订单
     */
    private function _alipay_order($param = [], $notify_url = ''){
        // 支付配置参数
        $alipay = Config::get('pay.alipay');
        // 创建订单参数组装
        $tmp_content = [
            'body'=>isset($param['body']) ? $param['body'] : '',
            'subject'=>$param['subject'],
            'out_trade_no'=>$param['order_no'],
            'timeout_express'=>'30m',
            'total_amount'=>$param['total_fee'],
            'product_code'=>'QUICK_MSECURITY_PAY',
        ];

        $aop = new AopClient;
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = $alipay['app_id'];
        $aop->rsaPrivateKey = $alipay['private_key'];
        $aop->alipayrsaPublicKey = $alipay['public_key'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new AlipayTradeAppPayRequest();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = json_encode($tmp_content, JSON_UNESCAPED_UNICODE);
        $request->setNotifyUrl($notify_url);
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
	    return $response;
    }
    /**
     * 支付宝回调处理
     * 支付宝同步数据检测
     */
    public function recharge_callback($param = []){
        // 支付配置参数
        $alipay = Config::get('pay.alipay');
        $aop = new AopClient;
        $aop->alipayrsaPublicKey = $alipay['public_key'];
        $flag = $aop->rsaCheckV1($param, NULL, "RSA2");
        if($flag !== true){
            \think\Log::write($param,'支付宝签名异常');
            return '签名验证失败';
        }
        // 搜素商户订单
        $where['order_no'] =  $param['out_trade_no'];//商户订单号
        $order = Db::table('orders_account')->where($where)->find();
        if(!$order){
            return '订单不存在';
        }
        if($param['total_amount'] != $order['total_fee']){
            return '订单金额不符';
        }
        if($param['app_id'] != $alipay['app_id']){
            return 'app_id无效';
        }
        if($param['seller_email'] != $alipay['seller_email']){
            return 'seller_email 无效';
        }

        if($param['trade_status'] != 'TRADE_SUCCESS' && $param['trade_status'] != 'TRADE_FINISHED'){
            return 'TRADE_ERROE';
        }

        if($order['status'] == 1){
            return true;
        }
        // 启动事务
        Db::startTrans();
        try{
            //修改订单状态
            $o_save['status'] = 1;
            $o_save['update_at'] = time();
            $res = Db::table('orders_account')->where($where)->update($o_save);
            if(!$res){
                Db::rollback();
                return false;
            }
            // 增加用户账户金额
            $uid = $order['created_uid'];
            $op_money = $order['total_fee'];
            $res = UserService::self()->updata_user_account($uid,$op_money,'recharge', 2);
            if($res !== true ){
                Db::rollback();
                return false;
            }
            // 提交事务
            Db::commit();   
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return false;
        }
        return true;
    }
    /**
     * 支付宝回调处理
     * 支付宝同步数据检测
     */
    public function alipay_recharge_search($resultStatus = '', $param = []){
        $response = $param['alipay_trade_app_pay_response'];
        switch ($resultStatus) {
            case '9000'://订单支付成功
                $res = OrderService::self()->alipay_recharge_success($response, $param['current_uid']);
                break;
            case '5000'://重复请求
            case '8000'://正在处理中，支付结果未知（有可能已经支付成功），请查询商户订单列表中订单的支付状态
            case '6004'://支付结果未知（有可能已经支付成功），请查询商户订单列表中订单的支付状态
                $res = $this->alipay_status($response['out_trade_no'], $response['trade_no']);
                if($res){
                    $res = OrderService::self()->alipay_recharge_success($response, $param['current_uid']);
                }
                break;
            case '4000'://订单支付失败
            case '6001'://用户中途取消
            case '6002'://网络连接出错
                $res = '订单支付失败';
                break;
            default:
                $res = '支付失败';
                break;
        }
        return $res;
    }

    /**
     * 充值失败处理
     */
    public function recharge_error($order_no = ''){
        $where['order_no'] = $order_no;
        $order = Db::table('orders_account')->where($where)->find();
        if($order['status'] == 1){
            // 更正用户的充值记录 
            $op_money = $order['total_fee'];
            $uid = $order['created_uid'];
            UserService::self()->updata_user_account($uid,$op_money,'pay_error', 2);
        }
        // 修改订单状态为支付失败
        OrderService::self()->recharge_error($order_no);
    }
    /**
     * 订单支付失败处理
     */
    public function alipay_order_error($order_no = ''){
        $where['order_no'] = $order_no;
        $order = Db::table('orders')->where($where)->find();
        if($order['status'] == 3){
            switch ($orders['source_type']) {
                case 1://服务 购买服务的人支付
                    $uid = $order['created_uid'];
                    break;
                case 2://邀约 创建邀约的人支付
                    $c_where['id'] = $order['copy_id'];
                    $copy = Db::table('services_copy')->field('title')->where($c_where)->find();
                    $uid = $copy['created_uid'];
                    break;
            }
            // 添加用户支付 失败 流水信息
            $pay_info = Config::get('pay.pay_type');
            $running_type = Config::get('running_type.pay_error');
            $running['pay_intro'] = $pay_info[$pay_type];
            $running['title'] = '支付失败扣除';
            $running['intro'] = '支付失败';
            $op_money = $order['total_fee'];
            $res = RunningModel::self()->add(2,$running_type, $op_money, $uid, $running);
        }
        // 修改订单状态为支付失败
        OrderService::self()->alipay_order_error($order_no);
    }
    //////////////////////////////////////提现相关///////////////////////////////////////////////////
    /**
     * 账户提现功能接口
     */
    public function withdraw($account = '', $payee_real_name = '', $pay_type = 1, $order = []){
        switch ($pay_type) {
            case 1://微信提现
                # code...
                break;
            case 2://支付宝提现
                $res = $this->_alipay_widthdraw($account,$payee_real_name, $order);
                break;
        }
        return $res;
    }
    /**
     * 支付宝 转账提现
     */
    private function _alipay_widthdraw($account = '',$payee_real_name = '', $order = []){
        // 配置参数
        $alipay = Config::get('pay.alipay');
        // 订单参数
        $tmp_content['out_biz_no'] = $order['order_no'];
        $tmp_content['payee_type'] = 'ALIPAY_LOGONID';
        $tmp_content['payee_account'] = $account;//收款方账户
        $tmp_content['amount'] = $order['total_fee'];
        // $tmp_content['payer_show_name'] = '北京在吗科技有限公司';
        $tmp_content['payee_real_name'] = $payee_real_name;
        $tmp_content['remark'] = '账户提现';
        $bizcontent = json_encode($tmp_content, JSON_UNESCAPED_UNICODE);

        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $alipay['app_id'];
        $aop->rsaPrivateKey = $alipay['private_key'];
        $aop->alipayrsaPublicKey=$alipay['public_key'];
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new AlipayFundTransToaccountTransferRequest ();
        $request->setBizContent($bizcontent);
        $result = $aop->execute ( $request); 

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(empty($resultCode) || $resultCode != 10000){
            return '提现失败';
        } 
        // 修改账户金额
        Db::startTrans();
        try{
            //修改订单状态
            $where['order_id'] = $order['order_id'];
            $where['type'] = 2;
            $o_save['status'] = 1;
            $o_save['update_at'] = time();
            $res = Db::table('orders_account')->where($where)->update($o_save);
            if(!$res){
                Db::rollback();
                return '修改订单状态失败';
            }
            // 修改账户余额
            $uid = $order['created_uid'];
            $op_money = $order['total_fee'];
            $res = UserService::self()->updata_user_account($uid,$op_money,'withdraw', 2);
            if($res !== true ){
                Db::rollback();
                return '更新账户金额失败';
            }
            // 提交事务
            Db::commit();   
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '未知错误，提现失败';
        }
    }
    //////////////////////////////////////订单支付相关///////////////////////////////////////////////////
    /**
     * 业务订单查询
     * 验证数据为支付宝返回的数据
     */
    public function alipay_order_callback( $param = ''){
        // 支付配置参数
        $alipay = Config::get('pay.alipay');
        $aop = new AopClient;
        $aop->alipayrsaPublicKey = $alipay['public_key'];
        $flag = $aop->rsaCheckV1($param, NULL, "RSA2");
        if($flag !== true){
            // 记录异常日志
            \think\Log::write($param,'支付宝签名异常');
            return '签名验证失败';
        }

        // 搜素商户订单
        $where['order_no'] =  $param['out_trade_no'];//商户订单号
        $order = Db::table('orders')->where($where)->find();
        if(!$order){
            return '订单不存在';
        }
        if($param['total_amount'] != $order['total_fee']){
            return '订单金额不符';
        }

        if($param['app_id'] !== $alipay['app_id']){
            return 'app_id无效';
        }
        if($param['seller_email'] !== $alipay['seller_email']){
            return 'seller_email 无效';
        }

        if($param['trade_status'] != 'TRADE_SUCCESS' && $param['trade_status'] != 'TRADE_FINISHED'){
            return 'TRADE_ERROE';
        }
        if($order['status'] == 3){
            return true;
        }
        // 启动事务
        Db::startTrans();
        try{
            //修改订单状态
            $o_save['status'] = 3;
            $o_save['update_at'] = time();
            $res = Db::table('orders')->where($where)->update($o_save);
            if(!$res){
                Db::rollback();
                return '更新订单状态失败';
            }
            OrderService::self()->clear_cache($order['order_id']);
	        // 流水明细到底该写在谁的名下
            if($order['source_type'] == 1){//服务
                $uid = $order['created_uid'];
            }else if($order['source_type'] == 2){//邀约
                $c_where['id'] = $order['copy_id'];
                $copy = Db::table('services_copy')->where($c_where)->find();
                $uid = $copy['created_uid'];
            }
            // 添加流水信息
            $pay_type = 2;//支付宝支付
            $pay_info = Config::get('pay.pay_type');
            $running_type = Config::get('running_type.consume');
            $op_money = $order['total_fee'];
            $running['pay_intro'] = $pay_info[$pay_type];
            $running['title'] = '订单支付';
            $running['intro'] = '订单支付';
            $res = RunningModel::self()->add($pay_type,$running_type, $op_money, $uid, $running);
            if(!$res){
                Db::rollback();
                return '添加流水信息失败';
            }
            // 提交事务
            Db::commit();   
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '支付失败';
        }
    }
    /**
     * 业务订单查询
     * 验证数据为支付宝返回的数据
     * 同步回调参数默认成功
     */
    public function alipay_order_search( $resultStatus = '',$param = ''){
        $response = $param['alipay_trade_app_pay_response'];
        switch ($resultStatus) {
            case '9000'://订单支付成功
                $res = OrderService::self()->alipay_order_success($response, $param['current_uid']);
                break;
            case '5000'://重复请求
            case '8000'://正在处理中，支付结果未知（有可能已经支付成功），请查询商户订单列表中订单的支付状态
            case '6004'://支付结果未知（有可能已经支付成功），请查询商户订单列表中订单的支付状态
                $res = $this->alipay_status($response['out_trade_no'], $response['trade_no']);
                if($res){
                    $res = OrderService::self()->alipay_order_success($response, $param['current_uid']);
                }
                break;
            case '4000'://订单支付失败
            case '6001'://用户中途取消
            case '6002'://网络连接出错
                $res = '订单支付失败';
                break;
            default:
                $res = '支付失败';
                break;
        }
        return $res;
    }
    /**
     * 充值失败处理
     */
    public function order_pay_error($order_no = '', $pay_type = 2){
        $where['order_no'] = $order_no;
        $order = Db::table('orders')->where($where)->find();
        if(!$order){
            return false;
        }
        \think\Cache::rm('order_'.$order['order_id']);
        // 第一次修改状态为充值失败
        $res = OrderService::self()->alipay_order_error($order_no);
        if($order['status'] == 3){
            // 如果订单已被修改为成功状态，则添加充值记录
            $op_money = $order['total_fee'];
            $uid = $order['created_uid'];
            UserService::self()->updata_user_account($uid,$op_money,'pay_error', $pay_type);
        }
    }

    /**
     * 发起 订单支付
     */
    public function order_pay($order_id = '', $pay_type = 1){
        $where['order_id'] = $order_id;
        $order = Db::table('orders')->field('order_no, status,total_fee, intro, copy_id')->where($where)->find();
        if(!$order){
            return false;
        }
	    if($order['status'] == 3){
            return false;
        }
        if($order['status'] != 2){
            return false;
        }
        $c_where['id'] = $order['copy_id'];
        $copy = Db::table('services_copy')->field('title')->where($c_where)->find();
        // 创建支付订单数据准备
        $param['order_id'] = $order_id;
        $param['order_no'] = $order['order_no'];
        $param['total_fee'] = $order['total_fee'];
        $param['subject'] = strDecode($copy['title']);
        $param['body'] = strDecode($order['intro']);
        switch ($pay_type) {
            case 1://微信
                $notify_url = url('Notify/wx_order_callback', '', true, true);
                $detail = $this->_wxpay_order($param, $notify_url);
                break;
            case 2://支付宝
                $notify_url = url('Notify/alipay_order_callback', '', true, true);
                $detail = $this->_alipay_order($param,$notify_url);
                break;
        }
        return $detail;
    }

    /**
     * 余额支付订单
     */
    public function pay_by_account($order_id = '', $uid = ''){
        $where['order_id'] = $order_id;
        $order = Db::table('orders')->where($where)->find();
        if(!$order){
            return '订单信息不存在，请刷新再试';
        }
        if($order['status'] == 3){
            return '订单已支付，请勿重复操作';
        }
        if($order['status'] != 2){
            return '该订单暂不能支付，请稍后再试';
        }
        // 启动事务
        Db::startTrans();
        try{
            //修改订单状态
            $o_save['status'] = 3;
            $o_save['update_at'] = time();
            $res = Db::table('orders')->where($where)->update($o_save);
            if(!$res){
                Db::rollback();
                return '修改订单状态失败';
            }
            OrderService::self()->clear_cache($order_id);
            // 修改用户账户金额
            $op_money = $order['total_fee'];
            $res = UserService::self()->updata_user_account($uid,$op_money,'consume', 3);
            if($res !== true ){
                Db::rollback();
                return '修改账户金额失败';
            }
            PushService::self()->push_order_msg($order_id, 'pay_by_account', $uid);
            // 提交事务
            Db::commit();   
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '支付失败';
        }
    }

    /**
     * 检测交易状态
     */
    public function alipay_status($out_trade_no = '', $trade_no = ''){
        $alipay = Config::get('pay.alipay');
        $aop = new AopClient ();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = $alipay['app_id'];
        $aop->rsaPrivateKey = $alipay['private_key'];
        $aop->alipayrsaPublicKey = $alipay['public_key'];
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";

        $bizcontent['out_trade_no'] = $out_trade_no;
        $bizcontent['trade_no'] = $trade_no;
        $bizcontent = json_encode($bizcontent, JSON_UNESCAPED_UNICODE);

        $request = new AlipayTradeQueryRequest ();
        $request->setBizContent($bizcontent);
        $result = $aop->execute ( $request); 
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            return true;
        } else {
            return false;
        }
    }

    // //////////////微信相关支付///////////////////////////////////////////////////////////
    /**
     * 创建微信支付订单
     */
    public function _wxpay_order($order, $notify_url){
        //统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($order['subject']);
        $input->SetOut_trade_no($order['order_no']);
        $input->SetTotal_fee($order['total_fee']*10*10);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));// 10分钟内支付有效
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("APP");
        $result = WxPayApi::unifiedOrder($input);
    	
	    $result['timestamp'] = time();
        // appid，partnerid，prepayid，noncestr，timestamp，package。注意：package的值格式为Sign=WXPay
        $string = 'appid='.$result['appid'].'&noncestr='.$result['nonce_str'].'&package=Sign=WXPay';
        $string .= '&partnerid='.$result['mch_id'].'&prepayid='.$result['prepay_id'];
        $string .= '&timestamp='.$result['timestamp'];
	    $string .= '&key=50c6077aa65f11bd17b64cfd5707a42d';
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result['sign'] = strtoupper($string);
        return $result;    
    }
    /**
     * 查询支付结果
     */
    public function wxpay_search($order_no = ''){
        $input = new WxPayOrderQuery();
        // $input->SetTransaction_id($transaction_id);
        $input->SetOut_trade_no($order_no);
        $result = WxPayApi::orderQuery($input);
        
        if(array_key_exists("return_code", $result)  && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS")
        {
            // 通信成功检测订单状态
            if($result['trade_state'] == 'NOTPAY'){
                return '订单未支付';
            }
            if($result['trade_state'] == 'CLOSED'){
                return '订单已关闭';
            }
            if($result['trade_state'] == 'REFUND'){
                return '订单已转入退款';
            }
            if($result['trade_state'] == 'PAYERROR'){
                return '订单支付失败';
            }
            // 支付成功 处理数据
            $out_trade_no = $result['out_trade_no'];
            // 搜素商户订单
            $where['order_no'] =  $out_trade_no;//商户订单号
            $order = Db::table('orders_account')->where($where)->find();
            if($order['status'] == 1){
                return true;
            }
            // 启动事务
            Db::startTrans();
            try{
                //修改订单状态
                $where['type'] = 1;
                $where['pay_type'] = 1;
                $o_save['status'] = 1;
                $o_save['update_at'] = time();
                $res = Db::table('orders_account')->where($where)->update($o_save);
                if(!$res){
                    Db::rollback();
                    return '修改订单状态失败';
                }
                // 增加用户账户金额
                $uid = $order['created_uid'];
                $op_money = $order['total_fee'];
                $res = UserService::self()->updata_user_account($uid,$op_money,'recharge', 1);
                if($res !== true ){
                    Db::rollback();
                    return '修改账户金额失败';
                }
                // 提交事务
                Db::commit();   
                return true;
            } catch (\Exception $error) {
                // 回滚事务
                Db::rollback();
                return '支付失败请稍后再试';
            }
        }
        return $result['err_code_des'];
    }

    /**
     * 查询支付结果
     */
    public function wx_order_search($order_no = '', $current_uid = ''){
        $input = new WxPayOrderQuery();
        // $input->SetTransaction_id($transaction_id);
        $input->SetOut_trade_no($order_no);
        $result = WxPayApi::orderQuery($input);
        
        if(array_key_exists("return_code", $result)  && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS")
        {
            // 通信成功检测订单状态
            if($result['trade_state'] == 'NOTPAY'){
                return '订单未支付';
            }
            if($result['trade_state'] == 'CLOSED'){
                return '订单已关闭';
            }
            if($result['trade_state'] == 'REFUND'){
                return '订单已转入退款';
            }
            if($result['trade_state'] == 'PAYERROR'){
                return '订单支付失败';
            }
            // 支付成功 处理数据
            $out_trade_no = $result['out_trade_no'];
            // 搜素商户订单
            $where['order_no'] =  $out_trade_no;//商户订单号
            $order = Db::table('orders')->where($where)->find();
            if(!$order){
                return '订单不存在';
            }
            if($order['total_fee']*100 != @$result['total_fee']){
                return '订单金额不符';
            }
            switch ($order['status']) {
                case 3://支付成功
                    return true;
                    break;
                case 4://已完成
                    return '订单已完成';
                    break;
                case 5://已取消
                    return '订单已取消';
                    break;
                case 6://已删除
                    return '订单已删除';
                    break;
            }

            // 启动事务
            Db::startTrans();
            try{
                //修改订单状态
                $o_save['status'] = 3;//已付款
                $o_save['update_at'] = time();
                $res = Db::table('orders')->where($where)->update($o_save);
                if(!$res){
                    Db::rollback();
                    return '修改订单状态失败';
                }
                OrderService::self()->clear_cache($order['order_id']);
                // 当前支付用户写入流水明细
                // 服务订单由 购买服务者支付
                $pay_type       = 1;//微信支付
                $pay_info       = Config::get('pay.pay_type');
                $running_type   = Config::get('running_type.consume');
                $op_money       = $order['total_fee'];

                $running['pay_intro']   = $pay_info[$pay_type];
                $running['title']       = '订单支付';
                $running['intro']    = '购买服务支付';

                $res = RunningModel::self()->add($pay_type,$running_type, $op_money, $current_uid, $running);
                if(!$res){
                    Db::rollback();
                    return false;
                }
                PushService::self()->push_order_msg($order['order_id'], 'wx_order_success', $current_uid);
                // 提交事务
                Db::commit();   
                return true;
            } catch (\Exception $error) {
                // 回滚事务
                Db::rollback();
                return '支付失败请稍后再试';
            }
        }
        return $result['return_msg'];
    }

    /**
     * 异步通知结果
     */
    public function wx_recharge_callback($data){
        // 签名验证
        $payResult = new WxPayResults($data);
        $sign = $payResult->CheckSign();
        if($sign !== true){
            return '签名失败';
        }
        // 订单金额验证
        $out_trade_no = $data['out_trade_no'];
        // 搜素商户订单
        $where['order_no'] =  $out_trade_no;//商户订单号
        $order = Db::table('orders_account')->where($where)->find();
        if($order['status'] == 1){
            return true;
        }
        if($data['total_fee'] != $order['total_fee']){
            return '回调金额不符';
        }

        // 启动事务
        Db::startTrans();
        try{
            //修改订单状态
            $o_save['status'] = 1;
            $o_save['update_at'] = time();
            $res = Db::table('orders_account')->where($where)->update($o_save);
            if(!$res){
                Db::rollback();
                return '更改订单状态失败';
            }
            // 增加用户账户金额
            $uid = $order['created_uid'];
            $op_money = $order['total_fee'];
            $res = UserService::self()->updata_user_account($uid,$op_money,'recharge', 1);
            if($res !== true ){
                Db::rollback();
                return '更改账户金额失败';
            }
            // 提交事务
            Db::commit();   
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '支付失败请稍后再试';
        }
    }
    /**
     * 异步通知结果
     */
    public function wx_order_callback($data){
        // 签名验证
        $payResult = new WxPayResults($data);
        $sign = $payResult->CheckSign();
        if($sign !== true){
            return '签名失败';
        }
        // 订单金额验证
        $out_trade_no = $data['out_trade_no'];
        // 搜素商户订单
        $where['order_no'] =  $out_trade_no;//商户订单号
        $order = Db::table('orders')->where($where)->find();
        if($order['status'] == 3){
            return true;
        }
        if($data['total_fee'] != $order['total_fee']){
            return '回调金额不符';
        }

        // 启动事务
        Db::startTrans();
        try{
            //修改订单状态
            $o_save['status'] = 3;
            $o_save['update_at'] = time();
            $res = Db::table('orders')->where($where)->update($o_save);
            if(!$res){
                Db::rollback();
                return '更改订单状态失败';
            }
            OrderService::self()->clear_cache($order['order_id']);
            // 提交事务
            Db::commit();   
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '支付失败请稍后再试';
        }
    }
}
