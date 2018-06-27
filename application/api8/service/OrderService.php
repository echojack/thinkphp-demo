<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\service;
use think\Db;
use think\Config;
use think\Cache;
use app\api8\model\OrderModel;
use app\api8\service\UserService;
use app\api8\model\RunningModel;
use app\api8\model\UserExtModel;
use app\api8\service\PushService;
use app\api8\service\ServicesService;
use app\common\model\ServiceModel;
class OrderService {

    public static function self(){
        return new self();
    }
    /**
     * 添加服务订单
     * 判断服务是否存在
     * 账户余额是否够用
     */
    public function post_service($param = [], $user = []){
        $where['services.id'] = intval($param['source_id']);
        $where['services.type'] = 1;
        $where['services.status'] = 1;
        $where['services.is_del'] = 0;
        $service = ServiceModel::self()->where($where)->find();
        if(!$service){
           return '服务不存在或未通过审核，快去看看其他的吧';//服务不存在
        }
        // 线下服务须填写地址
        if($service['is_online'] == 2){
            if(empty($param['province_id'])){
                return '请选择服务所在省';
            }
            if(empty($param['city_id'])){
                return '请选择服务所在市';
            }
            if(empty($param['area_id'])){
                return '请选择服务所在区';
            }
            if(empty($param['address'])){
                return '请选择服务详细地址';
            }
        }
        $save = $this->_service_data($param, $user, $service);
        $order_id = OrderModel::self()->add($save, $user);
        if(!$order_id){
            return '添加订单失败，请刷新再试';
        }
        // 添加消息推送
        PushService::self()->push_order_msg($order_id, 'post_service', $user['uid']);
        return (int)$order_id;
    }
    /**
     * 参数处理
     */
    private function _service_data($param = [], $user = [], $service = []){
        // 用户填写数据
        $save['date_time'] = strtotime($param['date_time']);
        $save['time_long'] = floatval($param['time_long']);
        $save['province_id'] = intval(@$param['province_id']);
        $save['city_id'] = intval(@$param['city_id']);
        $save['area_id'] = intval(@$param['area_id']);
        $save['address'] = string(@$param['address']);
        $save['intro'] = isset($param['intro'])? strEncode($param['intro']) : '';
        // 默认 或者系统数据
        $save['source_id'] = intval($param['source_id']);
        $save['source_type'] = 1;
        $save['order_no'] = order_no();
        $save['time_type'] = 0;//指定时间
        $save['price'] = floatval($service['price']);
        $save['total_fee'] = $service['price']*$save['time_long'];
        // $save['options'] = serialize($service);
        $save['status'] = 1;
        $save['type'] = 1;
        $save['created_at'] = time();
        $save['created_uid'] = $user['uid'];
        return $save;
    }
    /**
     * 邀约订单提交
     */
    public function post_demand($param = [], $user = []){
        $demand = ServiceModel::self()->demand_detail($param['id']);
        if(!$demand){
            return '邀约不存在，快去看看其他的吧';
        }
        $save = $this->_demand_data($param, $user, $demand);
        $uid = $user['uid'];
         // 启动事务
        Db::startTrans();
        try{
            // // 账户余额检测
            // $freeze_money = order_freeze_money($save);
            // $account = UserExtModel::self()->findExt($uid, 'account, freeze_money');
            // if($freeze_money > $account['account']){
            //     Db::rollback();
            //     return '余额不足，请先充值';
            // }
            // // 冻结订单 5%的金额
            // $res = UserService::self()->updata_user_account($uid, $freeze_money, 'demand_freeze');
            // if($res !== true){
            //     Db::rollback();
            //     return '更新账户金额失败，请稍后再试';
            // }
             // 存储 服务信息
            $source_id = $param['id'];
            $copy_id = ServiceModel::self()->copy_service($source_id);
            if(!$copy_id){
                Db::rollback();
                return '生成快照信息失败，请稍后再试';
            }
            $save['copy_id'] = $copy_id;
            $order_id = OrderModel::self()->insertGetId($save);
	    if(!$order_id){
                Db::rollback();
                return '生成订单失败，请稍后再试';
            }
            //PushService::self()->push_order_msg($order_id, 'post_demand', $uid);
            Db::commit();    
	    return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return '操作失败';
        }
    }
    /**
     * 邀约数据
     */
    private function _demand_data($param = [], $user = [], $demand = ''){
        $save['source_type'] = 2;
        $save['order_no'] = order_no();
        $save['source_id'] = intval($param['id']);
        $save['intro'] = $demand['title'];
        $save['province_id'] = $demand['province_id'];
        $save['city_id'] = $demand['city_id'];
        $save['area_id'] = $demand['area_id'];
        $save['address'] = $demand['address'];
        $save['time_type'] = $demand['time_type'];
        $save['date_time'] = $demand['date_time'];
        $save['status'] = 1;
        $save['type'] = 2;
        $save['created_at'] = time();
        $save['created_uid'] = $user['uid'];
        return $save;
    }
    /**
     * 订单详情
     */
    public function detail($order_id = ''){
        if(!$order_id){
            return [];
        }
        $cache_name = 'order_'.$order_id;
        $order = Cache::get($cache_name);
        if(!$order){
            if($order_id){
                $where['order_id'] = $order_id;
            }
            $order = OrderModel::self()->where($where)->find();
            if(!$order){
                return [];
            }
            $order = json_decode(json_encode($order), true);
            $order['intro'] = strDecode($order['intro']); 

            // 时间组装
            if($order['source_type'] == 1){
                // 服务订单时间信息，在下订单的时候选择
                $order['time'] = serviceTime($order);    
            }else{
                // 邀约订单的时间信息，在发布邀约的时候选择
                $order['time'] = demandTime_new($order['time_type'], $order['date_time'], $order['time_long']);    
            }
            unset($order['update_at']);
            unset($order['options']);
            unset($order['date_time']);
            unset($order['time_long']);
            unset($order['time_type']);
            Cache::set($cache_name, $order);
        }
        // 检测订单是否可以评论
        $order['comment_flag'] = $this->check_order_comment($order_id, $order['created_uid']);
        // 用户信息
        $uid = $order['created_uid'];
        $user = UserService::self()->detail($uid);
        $order['nick_name'] = $user['nick_name'];
        $order['avatar'] = $user['avatar'];
        // 服务详情
        $copy_id = $order['copy_id'];
        $copy = $this->copy_detail($copy_id, $order['source_id'], $order['source_type']);
        $order['copy'] = $copy;
        return $order;
    }
    /**
     * 服务详情展示
     */
    public function copy_detail($copy_id = '', $source_id = '', $source_type = ''){
        if(!$copy_id){
            return [];
        }
        $cache_name = 'copy_'.$copy_id;
        $tmp_detail = Cache::get($cache_name);
        if(!$tmp_detail){
            $where['id'] = $copy_id;
            $detail = Db::table('services_copy')->where($where)->find();
            if(!$detail){
        		if($source_type == 2){
        			return ServicesService::self()->demand_detail($source_id, '', false);
        		}
                return ServicesService::self()->service_detail($source_id, '', false);
            }
            // 处理图片信息
            $attaches = unserialize($detail['attaches']);
            if($attaches){
                foreach ($attaches as $k => $val) {
                    $attaches[$k] = Config::get('img_url').$val;
                }
            }
            // 服务信息
            $tmp_detail['id'] = $detail['source_id'];
            $tmp_detail['title'] = strDecode($detail['title']);
            $tmp_detail['type'] = $detail['type'];
            $tmp_detail['category_id'] = [$detail['category_id']];
            $tmp_detail['price'] = $detail['price'];
            $tmp_detail['price_unit'] = $detail['price_unit'];
            $tmp_detail['sounds'] = $detail['sounds'];
            if($detail['sounds']){
                $tmp_detail['sounds'] = Config::get('img_url').$detail['sounds'];
            }
            $tmp_detail['intro'] = strDecode($detail['intro']);
            $tmp_detail['attaches'] = $attaches;
	        $tmp_detail['created_uid'] = $detail['created_uid'];
            Cache::set($cache_name, $tmp_detail);
        }
        // 用户信息
        $uid = $tmp_detail['created_uid'];
        $user = UserService::self()->detail($uid);
        $tmp_detail['uid'] = $uid;
        $tmp_detail['nick_name'] = $user['nick_name'];
        $tmp_detail['avatar'] = $user['avatar'];
        return $tmp_detail;
    }
    /**
     * 订单确认完成
     * status = 4
     */
    public function finish_order($order_id = '', $uid = ''){
        // 查询订单数据
        $o_where['order_id'] = $order_id;
        $order = OrderModel::self()->where($o_where)->find();
        if(!$order){
            return '订单信息不存在，请刷新再试';
        }
        $res = $this->_finish_service_order($order ,$uid);
        return $res;
    }
    /**
     * 服务订单完成 购买者确认
     */
    public function _finish_service_order($order = '', $uid = ''){
        $o_where['order_id'] = $order['order_id'];
        if($order['created_uid'] != $uid){
            return '非法操作，该订单只能由购买者确认完成';
        }
        if($order['status'] != 3){
            return '订单貌似还不能完成，请刷新再试';
        }
        // 获取服务创建者信息
        $c_where['id'] = $order['copy_id'];
        $copy = Db::table('services_copy')->field('title, created_uid')->where($c_where)->find();
        $s_creater = $copy['created_uid'];
        // 启动事务
        Db::startTrans();
        try{
            // 更新订单数据
            $save['status'] = 4;
            $save['update_at'] = time();
            $res = OrderModel::self()->where($o_where)->update($save);
            if(!$res){
                Db::rollback();
                return '修改订单状态失败';
            }
            // 提供服务者获得订单金额，
            $op_money = $order['total_fee'];
            $res = UserService::self()->updata_user_account($s_creater, $op_money, 'income');
            if($res !== true){
                Db::rollback();
                return '更新账户金额失败，请稍后再试';
            }
            // 并解冻5%冻结金额
            $op_money = order_freeze_money($order);
            $res = UserService::self()->updata_user_account($s_creater, $op_money, 'finish');
            if($res !== true){
                Db::rollback();
                return '解冻账户失败，请刷新再试';
            }
            // 清除订单缓存
            $this->clear_cache($order['order_id']);
            // 发送订单完成通知
            PushService::self()->push_order_msg($order['order_id'], 'finish_order', $uid);
            Db::commit();    
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return '操作失败';
        }
    }
    /**
     * 取消订单
     */
    public function cancel_order($order_id = '', $user = []){
        // 查询订单数据
        $o_where['order_id'] = $order_id;
        $order = OrderModel::self()->where($o_where)->find();
        if(!$order){
            return '订单信息不存在，请刷新再试';
        }
        if(in_array($order['status'], [5,6])){
            return '订单不存在或已取消，请不要重复操作';
        }
        switch ($order['source_type']) {
            case 1://服务
                $res = $this->_cancel_service_order($order ,$user);
                break;
            case 2://邀约
                $res = $this->_cancel_demand_order($order ,$user);
                break;
        }
        
        return $res;
    }
    /**
     * 取消服务订单
     */
    public function _cancel_service_order($order = '', $user = ''){
        // 获取服务创建者信息
        $c_where['id'] = $order['copy_id'];
        $copy = Db::table('services_copy')->field('created_uid')->where($c_where)->find();
        $uid = $user['uid'];
        // 提供服务
        $s_creater = $copy['created_uid'];
        // 购买服务者
        $o_creater = $order['created_uid'];
        // 启动事务
        Db::startTrans();
        try{
            $o_where['order_id'] = $order['order_id'];
            // 修改订单状态
            $save['status'] = 5;
            $save['update_at'] = time();
            $res = OrderModel::self()->where($o_where)->update($save);
            if(!$res){
                Db::rollback();
                return '修改订单状态失败';
            }
            switch ($order['status']) {
                case 1:// 购买者编辑完订单，无论双方谁取消取消，不发生资金交易
                    break;
                case 2:
                    // 发布者确认后，发布者取消，解冻发布者冻结金额
                    // 发布者确认后，购买者取消，解冻发布者冻结金额；
                    $op_money = order_freeze_money($order);
                    $res = UserService::self()->updata_user_account($s_creater, $op_money, 'free');
                    if(!$res){
                        Db::rollback();
                        return '解冻账户失败，请刷新再试';
                    }
                    break;
                case 3:// 购买者支付完订单
                    // 购买者取消，扣除购买者订单5%的金额，平台和提供者37分；
                    if($o_creater == $uid){
                        // 退换购买者95%订单金额
                        $op_money = $order['total_fee'] - order_freeze_money($order);
                        $res = UserService::self()->updata_user_account($o_creater, $op_money, 'refund');
                        if(!$res){
                            Db::rollback();
                            return '解冻账户失败，请刷新再试';
                        }
                        // 按比例给提供者补偿
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->dist_penalty_money($s_creater, $op_money);
                        if($res === false){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                    }else if($s_creater == $uid){ //发布者取消 
                        // 扣除发布者冻结的5%，
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->updata_user_account($s_creater, $op_money, 'cancel');
                        if($res !== true){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                        // 退换购买者订单金额
                        $op_money = $order['total_fee'];
                        $res = UserService::self()->updata_user_account($o_creater, $op_money, 'canceld');
                        if($res !== true){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                        // 购买者和平台37分；
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->dist_penalty_money($o_creater, $op_money);
                        if($res !== true){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                    }
                    break;
            }
            // 更新订单缓存
            $this->clear_cache($order['order_id']);
            // 发送订单完成通知
            PushService::self()->push_order_msg($order['order_id'], 'cancel_service_order', $uid);

            Db::commit();    
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return '操作失败';
        }
    }
    /**
     * 取消邀约订单
     */
    public function _cancel_demand_order($order = '', $user = ''){
        $order_id = $order['order_id'];
        $res =  OrderModel::self()->reject_order($order_id, $user);
        if(!$res){
            return '修改订单状态失败';
        }
        // 更新订单缓存
        $this->clear_cache($order_id);
        // 发送订单完成通知
        PushService::self()->push_order_msg($order_id, 'cancel_demand_order', $user['uid']);
        return true;
    }
    /**
     * 确认服务订单
     * @param order 订单信息
     * @param status = 2
     * @param uid 当前用户id
     * 冻结服务订单提供者5%的金额
     */
    public function confirm_service_order($order_id = '', $user = ''){
        if(!$order_id || !$user){
            return '非法请求，参数不全';
        }
        // 查询订单数据
        $o_where['order_id'] = $order_id;
        $order = OrderModel::self()->where($o_where)->find();
        if(!$order){
            return '订单信息不存在，请刷新再试';
        }
        if($order['status'] != 1){
            return '只能待确认的订单可以操作，请刷新再试';
        }
        // 用户余额必须大于 订单金额的 5%
        $uid = $user['uid'];
        $user = UserExtModel::self()->findExt($uid, 'account, freeze_money');
        // 订单需冻结金额
        $least_money = order_freeze_money($order);
        if($least_money > $user['account']){
           return '20001';//账户余额不足
        }
        // 服务提供者
        $c_where['id'] = $order['copy_id'];
        $copy = Db::table('services_copy')->field('title, created_uid')->where($c_where)->find();
        if($uid != $copy['created_uid']){
            return '此订单只有服务提供者可以确认';
        }
        // 启动事务
        Db::startTrans();
        try{
            // 更新订单数据
            $save['status'] = 2;
            $save['update_at'] = time();
            $res = OrderModel::self()->where($o_where)->update($save);
            if(!$res){
                Db::rollback();
                return '修改订单状态失败';
            }
            // 修改用户账户信息
            $res = UserService::self()->updata_user_account($uid, $least_money, 'freeze');
            if(!$res){
                Db::rollback();
                return '更新账户金额失败，请稍后再试';
            }
            // 清除订单缓存
            $this->clear_cache($order_id);
            // 发送审核通知
            PushService::self()->push_order_msg($order_id, 'confirm_service_order', $uid);
            Db::commit();    
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return '操作失败';
        }
    }
    /**
     * 余额支付订单
     */
    public function pay_by_account($order_id = '', $uid = ''){
        if(!$order_id || !$uid){
            return '非法请求';
        }
        // 查询订单信息
        $where['order_id'] = $order_id;
        $where['created_uid'] = $uid;
        $order = OrderModel::self()->where($where)->find();
        if(!$order){
            return '订单信息不存在';
        }
        // 支付数据准备
        $type = config('running_type.consume');
        $running['order_id'] = $order_id;
        $running['order_no'] = $order['order_no'];
        $running['intro'] = strDecode($order['intro']);
        $running['price'] = $order['total_fee'];
        switch ($order['source_type']) {
            case '1':
                $running['title'] = '购买服务';
                break;
            case '2':
                $running['title'] = '购买需求';
                break;
        }
        // 启动事务
        Db::startTrans();
        try{
            // 修改订单状态
            $res = OrderModel::self()->where($where)->update(['status' => 3, 'update_at'=>time()]);
            if(!$res){
                Db::rollback();
                return '修改订单状态失败';
            }
            $this->clear_cache($order_id);
            // 修改账户信息
            $op_money = $order['total_fee'];
            $msg = UserService::self()->updata_user_account($uid, $op_money, 'consume', 3);
            if($msg !== true){
                Db::rollback();
                return '更新账户金额失败，请稍后再试';
            }
            Db::commit();    
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return '支付失败';
        }
        return true;
    }
    /**
     * 删除订单 
     */
    public function del_order($order_id = '', $user = []){
        $res =  OrderModel::self()->del_order($order_id, $user);
        if(!$res){
            return false;
        }
        $this->clear_cache($order_id);
        return true;
    }
    /**
     * 拒绝订单 
     */
    public function reject_order($order_id = '', $user = []){
        $res =  OrderModel::self()->reject_order($order_id, $user);
        if(!$res){
            return false;
        }
        $this->clear_cache($order_id);
        // 发送拒绝通知
        $o_where['order_id'] = $order_id;
        $order = OrderModel::self()->field('created_uid')->where($o_where)->find();
        if($order){
            PushService::self()->push_order_msg($order_id, 'reject_order', $user['uid']);
        }
        return true;
    }
    /**
     * 同意订单 
     */
    public function agree_order($order_id = '', $user = []){
        $res =  OrderModel::self()->agree_order($order_id, $user);
        if(!$res){
            return false;
        }
        $this->clear_cache($order_id);
        // 发送拒绝通知
        $o_where['order_id'] = $order_id;
        $order = OrderModel::self()->field('created_uid')->where($o_where)->find();
        if($order){
            PushService::self()->push_order_msg($order_id, 'agree_order', $user['uid']);
        }
        return true;
    }

    /**
     * 检测用户是否有未完成的订单
     * 同一个用户对于同一个邀约未完成之前只能参加一次
     */
    public function order_check($source_id = '', $source_type = 1, $uid = ''){
        $where['orders.status'] = ['in', [1,2,3]];
        $where['orders.source_id'] = $source_id;
        $where['orders.created_uid'] = $uid;
        $where['orders.source_type'] = $source_type;
        return OrderModel::self()->where($where)->count();
    }

    /**
     * 修改订单状态为失败
     */
    public function alipay_order_error($order_no = ''){
        $where['order_no'] = $order_no;
        // 搜素商户订单
        $order = Db::table('orders')->where($where)->find();
        if(!$order){
            return false;
        }        
        $save['status'] = 2;
        $save['update_at'] = time();
        $res = OrderModel::self()->where($where)->update($save);
        if(!$res){
            return false;
        }
        $this->clear_cache($order['order_id']);
        return true;
    }
    /**
     * 订单充值 支付失败
     */
    public function recharge_error($order_no = ''){
        $where['order_no'] = $order_no;
        $save['status'] = 2;
        $save['update_at'] = time();
        return Db::table('orders_account')->where($where)->update($save);
    }

    /**
     * 充值成功处理
     */
    public function alipay_recharge_success($param = '', $current_uid = ''){
        // 支付配置参数
        $alipay = Config::get('pay.alipay');
        if($param['app_id'] != $alipay['app_id']){
            return '非法操作，商户id不符';
        }
        // 搜素商户订单
        $where['order_no'] =  $param['out_trade_no'];//商户订单号
        $order = Db::table('orders_account')->where($where)->find();
        if(!$order){
            return '订单不存在';
        }
        if( $param['total_amount'] != $order['total_fee']){
            return '订单金额不符';
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
                return '修改订单状态失败';
            }
            // 增加用户账户金额
            $op_money = $order['total_fee'];
            $res = UserService::self()->updata_user_account($current_uid,$op_money,'recharge', 2);
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
            return '未知错误';
        }
    }
    /**
     * 订单支付成功处理
     */
    public function alipay_order_success($param = '', $current_uid = ''){
        // 支付配置参数
        $alipay = Config::get('pay.alipay');
        if($param['app_id'] != $alipay['app_id']){
            return '非法操作，商户id不符';
        }
        // 搜素商户订单
        $where['order_no'] =  $param['out_trade_no'];//商户订单号
        $order = Db::table('orders')->where($where)->find();
        if(!$order){
            return '订单不存在';
        }
        if($order['total_fee'] !== $param['total_amount']){
            return '订单金额不符';
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
                return '修改订单状态失败';
            }
            $this->clear_cache($order['order_id']);
            // 当前支付用户写入流水明细
            // 服务订单由 购买服务者支付，邀约订单由发布邀约者支付
            $pay_type       = 2;//支付宝支付
            $pay_info       = Config::get('pay.pay_type');
            $running_type   = Config::get('running_type.consume');
            $op_money       = $order['total_fee'];

            $running['pay_intro']   = $pay_info[$pay_type];
            $running['title']       = '订单支付';

            if($order['source_type'] == 1){
                $running['intro']    = '购买服务支付';
            }elseif($order['source_type'] == 2){
                $running['intro']    = '支付邀约赏金';
            }
            $res = RunningModel::self()->add($pay_type,$running_type, $op_money, $current_uid, $running);
            if(!$res){
                Db::rollback();
                return '添加流水信息失败';
            }
            PushService::self()->push_order_msg($order['order_id'], 'alipay_order_success', $current_uid);
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
     * 我的服务订单
     */
    public function service_order($param = [], $user = [], $page = 1, $limit = 10){
        $tmp_list = [];
        $param['uid'] = $user['uid'];
        // 订单分类
        switch ($param['type']) {
            case 'sell':
                $list = OrderModel::self()->sell($param, 1, $page, $limit);
                break;
            case 'purchase':
                $list = OrderModel::self()->purchase($param, 1, $page, $limit);
                break;
        }
        if($list){
            foreach ($list as $order_id) {
                $tmp_list[] = $this->detail($order_id);
            }
        }
        return $tmp_list;
    }
    /**
     * 我的邀约订单
     */
    public function demand_order($param = [], $user = [], $page = 1, $limit = 10){
        $tmp_list = [];
        $param['uid'] = $user['uid'];
        // 订单分类
        switch ($param['type']) {
            case 'create':
                $list = OrderModel::self()->sell($param, 2, $page, $limit);
                break;
            case 'part':
                $list = OrderModel::self()->purchase($param, 2, $page, $limit);
                break;
        }
        if($list){
            foreach ($list as $order_id) {
                $tmp_list[] = $this->detail($order_id);
            }
        }
        return $tmp_list;
    }
    /**
     * 清除删除订单缓存
     */
    public function clear_cache($order_id = ''){
	    $cache_name = 'order_'.$order_id;
        Cache::rm($cache_name);
    }
    /**
     * 订单冻结金额提示
     */
    public function freeze_tips($total_fee = 0){
        // 退单违约比例
        $freeze_ratio = Config::get('payment.service_fund_ratio');
        $return['freeze_money'] = $total_fee*$freeze_ratio/100;
        $return['tips'] = '为防止恶意订单，保证成交率。在吗平台规定确认订单会冻结'.$freeze_ratio.'%保证金，如果对方付款前取消或者订单完成，保证金会全额返还给您，如果对方付款之后您主动取消，系统将扣取这部分保证金！';
        return $return;
    }
    /**
     * 检测置换订单
     */
    public function check_zh_order($service_id = '', $my_service_id = '', $curr_uid  = ''){
        $my_where['id'] = $my_service_id;
        $my_where['created_uid'] = $curr_uid;
        $my_where['type'] = 1;
        $services = Db::table('services')->where($my_where)->count();
        if(!$services){
            return '您要置换的服务不存在';
        }

        $where['type'] = 3;
	$where['status'] = ['in', [1,2]];
        $where['source_id'] = $service_id;
        $where['my_source_id'] = $my_service_id;
        $where['created_uid'] = $curr_uid;
        $has = OrderModel::self()->where($where)->count();
        if($has){
            return '已申请置换，请耐心等待';
        }
        return true;
    }
    /**
     * 提交置换订单
     */
    public function post_zh_order($service_id = '', $my_service_id = '', $curr_uid  = ''){
        $save['type'] = 3;
        $save['source_id'] = $service_id;
        $save['my_source_id'] = $my_service_id;
        $save['created_uid'] = $curr_uid;
        $save['source_type'] = 1;
        $save['order_no'] = order_no();
        $order_id = OrderModel::self()->post_zh_order($save);
        if(!$order_id){
            return false;
        }
        // 添加消息推送
        PushService::self()->push_order_msg($order_id, 'post_zh_order', $curr_uid);
        return $res;
    }
    /**
     * 置换订单修改
     */
    public function zh_order_deal_check($order_id, $curr_uid){
        return OrderModel::self()->zh_order_deal_check($order_id, $curr_uid);
    }
    /**
     * 置换订单修改
     */
    public function zh_order_deal($order_id, $curr_uid, $status = 1){
        $where['order_id'] = $order_id;

        $order = OrderModel::self()->where($where)->find();
        if(!$order){
            return false;
        }
        // 删除订单  特殊处理
        if($status == 6){
            if($order['created_uid'] == $curr_uid){
                $save['is_del'] = 1;
            }else{
                $save['status'] = $status;    
            }
        }else{
            $save['status'] = $status;    
        }
        $save['update_at'] = time();
        $res = OrderModel::self()->where($where)->update($save);
        if(!$res){
            return false;
        }
        // 确认添加消息推送
        if($status == 2){
            PushService::self()->push_order_msg($order_id, 'zh_order_deal', $curr_uid);
        }
        $this->clear_cache($order_id);
        return true;
    }
    /**
     * 置换订单列表
     */
    public function my_zh_order($uid = '', $page = 1, $limit = 10){
        $tmp_list = [];
        $param['uid'] = $uid;
        $list = OrderModel::self()->my_zh_order($param, $page, $limit);

        if($list){
            foreach ($list as $k=> $order) {
                $source_id = $order['copy_id'];
                $my_service_id = $order['copy_id2'];
                $tmp_detail = [
                    'order_id' =>$order['order_id'],
                    'order_no' =>$order['order_no'],
                    'status' =>$order['status'],
                    'created_at' =>$order['created_at'],
                ];
                $tmp_list[$k] = $tmp_detail;
                $tmp_list[$k]['created_user'] = UserService::self()->simple_detail($order['created_uid']);
                // 检测当前用户是否可以评论该订单
                $tmp_list[$k]['comment_flag'] = $this->check_order_comment($order['order_id'], $uid);

                $tmp_list[$k]['source_detail'] = $this->copy_detail($source_id, $order['source_id'], 1);
                $tmp_list[$k]['change_source_detail'] = $this->copy_detail($my_service_id,$order['my_source_id'],1);
            }
        }
        return $tmp_list;
    }
    /**
     * 检测订单是否可以评论
     */
    public function check_order_comment($order_id = '', $uid = ''){
        // 缓存名称
        $cache_name = 'orders_comment_'.$order_id.'_'.$uid;

        $where['order_id'] = $order_id;
        $where['created_uid'] = $uid;
        $flag = Cache::get($cache_name);
        if(!$flag){
            $flag = Db::table('comments')->where($where)->count();
            Cache::set($cache_name, $flag);
        }
        return $flag ? 1 : 0;
    }
}
