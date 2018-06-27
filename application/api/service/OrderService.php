<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\service;
use think\Db;
use think\Config;
use app\api\model\LogsModel;
use app\api\model\OrderModel;
use app\api\model\ServicesModel;
use app\api\service\UserService;
use app\api\service\PayService;
use app\api\model\RunningModel;
use app\api\model\UserExtModel;
class OrderService {

    public static function self(){
        return new self();
    }
    /**
     * 订单列表
     */
    public function lists($where = [], $page = 1, $limit = 10, $type="demand", $on_flag = ''){
        $fields = 'orders.*, u.uid,u.nick_name, ue.avatar,ue.sex,ue.latitude,ue.longitude';
        if($type == 'demand'){
            $fields .= ',sc.category_id';
        }
        $lists = OrderModel::self()->lists($where, $fields, 'orders.order_id DESC', $page, $limit , $type, $on_flag);
        $tmp_lists = [];
        foreach ($lists as $val) {
            $options = order_options($val['options']);
            $tmp_detail = [];
            $tmp_detail['order_id'] = $val['order_id'];
            $tmp_detail['order_no'] = $val['order_no'];
            $tmp_detail['source_id'] = $val['source_id'];
            $tmp_detail['source_title'] = $options['title'];
            $tmp_detail['price'] = $options['price'];
            $tmp_detail['price_unit'] = $options['price_unit'];
            $tmp_detail['uid'] = $val['uid'];
            $tmp_detail['nick_name'] = strDecode($val['nick_name']);
	        $tmp_detail['avatar'] = get_avatar($val['avatar'], $val['sex']);
            $tmp_detail['time_type'] = $val['time_type'];
            $tmp_detail['date_time'] = date("m-d H:i", $val['date_time']);
            // $tmp_detail['end_time'] = date("Y-m-d H:i", $val['date_time']+$val['time_long']*3600);
            $tmp_detail['time_long'] = $val['time_long'];
            $tmp_detail['status'] = $val['status'];
            $tmp_detail['intro'] = strDecode($val['intro']);
            $tmp_detail['created_at'] = date("Y-m-d H:i:s", $val['created_at']);
            $tmp_detail['total_price'] = $val['total_fee'];
            $tmp_detail['category_id'] = isset($val['category_id']) ? $val['category_id'] : 0;
            $tmp_detail['category_icon'] = Config::get('img_url').'static/images/demand/icon'.$tmp_detail['category_id'].'.png';
            // 地址信息
            $address = get_province_name($val['province_id'], false).' '.get_city_name($val['city_id']).' '.get_area_name($val['area_id']).' ';
            $tmp_detail['address'] = trim($address.$val['address']);
	       // 下单具体信息
            $tmp_detail['options'] = $options;
            $tmp_lists[] = $tmp_detail;
        }
        return $tmp_lists;
    }
    /**
     * 添加服务订单
     * 判断服务是否存在
     * 账户余额是否够用
     */
    public function post_service($param = [], $user = []){
        $where['services.id'] = intval($param['source_id']);
        $where['services.type'] = 1;
        $where['services.is_del'] = 0;
        $service = ServicesModel::self()->service_one($where);
        if(!$service){
           return '服务丢失啦，快去看看其他的吧';//服务不存在
        }

        $save = $this->service_data($param, $user, $service);
        $order_id = OrderModel::self()->insertGetId($save);
        if(!$order_id){
            Db::rollback();
            return '添加订单失败，请刷新再试';
        }
        return (int)$order_id;
    }
    /**
     * 参数处理
     */
    private function service_data($param = [], $user = [], $service = []){
        $save['source_type'] = 1;
        $save['order_no'] = order_no();
        $save['source_id'] = intval($param['source_id']);
        $save['intro'] = strEncode($param['intro']);
        $save['time_type'] = 1;//指定时间

        if(isset($param['date_time'])){
            $save['date_time'] = strtotime($param['date_time']);
        }

        if(isset($param['time_long'])){
            $save['time_long'] = floatval($param['time_long']);
        }

        if(isset($param['province_id'])){
            $save['province_id'] = intval($param['province_id']);
        }
        if(isset($param['city_id'])){
            $save['city_id'] = intval($param['city_id']);
        }
        if(isset($param['area_id'])){
            $save['area_id'] = intval($param['area_id']);
        }
        $save['address'] = string($param['address']);
        $save['price'] = floatval($service['price']);
        $save['total_fee'] = $service['price']*$save['time_long'];
        $save['options'] = serialize($service);
        $save['status'] = 1;
        $save['created_at'] = time();
        $save['created_uid'] = $user['uid'];
        return $save;
    }
    /**
     * 邀约订单提交
     */
    public function post_demand($param = [], $uid = '', &$msg = ''){
        $where['services.id'] = intval($param['source_id']);
        $where['services.type'] = 2;
        $where['services.is_del'] = 0;
        $service = ServicesModel::self()->demand_one($where);
        if(!$service){
            $msg = '邀约消失啦，快去看看其他的吧';
            return '0';//服务不存在
        }
        $save['source_type'] = 2;
        $save['order_no'] = order_no();
        $save['source_id'] = intval($param['source_id']);
        $save['intro'] = $service['title'];
        $save['date_time'] = $service['date_time'];
        $save['time_long'] = $service['time_long'];
        $save['date_time'] = $service['date_time'];
        $save['province_id'] = $service['province_id'];
        $save['city_id'] = $service['city_id'];
        $save['area_id'] = $service['area_id'];
        $save['address'] = $service['address'];
        $save['price'] = $service['price'];
        $save['total_fee'] = $service['price'];
        $save['options'] = serialize($service);
        $save['status'] = 1;
        $save['created_at'] = time();
        $save['created_uid'] = $uid;

        // 启动事务
        Db::startTrans();
        try{
            // 账户余额检测
            $freeze_money = order_freeze_money($save);
            $account = UserExtModel::self()->findExt($uid, 'account, freeze_money');
            if($freeze_money > $account['account']){
                $msg = '余额不足，请先充值';
                return '20001';
            }
            // 冻结订单 5%的金额
            $res = UserService::self()->updata_user_account($uid, $freeze_money, 'freeze');
	        if($res !== true){
                Db::rollback();
                $msg = '更新账户金额失败，请稍后再试';
                return '0';
            }
            // 生成订单
            $order_id = OrderModel::self()->insertGetId($save);
            if(!$order_id){
                Db::rollback();
                $msg = '提交失败，请刷新再试';
                return '0';
            }
            // 添加操作日志
            Db::commit();    
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return '操作失败';
        }
        return true;
    }
    /**
     * 订单详情
     */
    public function detail($order_id = '', $order_no = ''){
        if(!$order_id && !$order_no){
            return false;
        }
        if($order_id){
            $where['order_id'] = $order_id;
        }
        if($order_no){
            $where['order_no'] = $order_no;
        }

        $detail = OrderModel::self()->where($where)->find();
        $detail = json_decode(json_encode($detail), true);
        if($detail){
            $detail['options'] = order_options($detail['options']);
        }
        return $detail;
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
        switch ($order['source_type']) {
            case 1://服务
                $res = $this->_finish_service_order($order ,$uid);
                break;
            case 2://邀约
                $res = $this->_finish_demand_order($order ,$uid);
                break;
        }
        return $res;
    }
    // 确认完成邀约订单
    public function _finish_demand_order($order = '', $uid = ''){
        $o_where['order_id'] = $order['order_id'];
        $o_where['source_type'] = $order['source_type'];
        $o_where['status'] = 3;
        $options = unserialize($order['options']);
        if($options['created_uid'] != $uid){
            return '非法操作，该订单只能由发布邀约者确认完成';
        }
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
            // 支付赏金给参加者账户，同时解冻参加者冻结的金额；
            $demand_uid = $order['created_uid'];
            $op_money = $order['total_fee'];
            $res = UserService::self()->updata_user_account($demand_uid, $op_money, 'income');
            if(!$res){
                Db::rollback();
                return '更新账户金额失败，请稍后再试';
            }
            // 解冻
            $op_money = order_freeze_money($order);
            $res = UserService::self()->updata_user_account($demand_uid, $op_money, 'free');
            if(!$res){
                Db::rollback();
                return '解冻账户失败，请刷新再试';
            }
            Db::commit();    
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return '操作失败';
        }
    }
    /**
     * 服务订单完成 购买者确认
     */
    public function _finish_service_order($order = '', $uid = ''){
        $o_where['order_id'] = $order['order_id'];
        if($order['created_uid'] != $uid){
            return '非法操作，该订单只能由购买者确认完成';
        }
        $order = OrderModel::self()->where($o_where)->find();
        if($order['status'] != 3){
            return '订单貌似还不能完成，请刷新再试';
        }
        // 提供服务者
        $options = unserialize($order['options']);
        $provider_uid = $options['created_uid'];
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
            $res = UserService::self()->updata_user_account($provider_uid, $op_money, 'income');
            if(!$res){
                Db::rollback();
                return '更新账户金额失败，请稍后再试';
            }
            // 并解冻5%冻结金额
            $op_money = order_freeze_money($order);
            $res = UserService::self()->updata_user_account($provider_uid, $op_money, 'free');
            if(!$res){
                Db::rollback();
                return '解冻账户失败，请刷新再试';
            }
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
    public function cancel($order_id = '', $uid = ''){
        // 查询订单数据
        $o_where['order_id'] = $order_id;
        $order = OrderModel::self()->where($o_where)->find();
        if(!$order){
            return '订单信息不存在，请刷新再试';
        }
        switch ($order['source_type']) {
            case 1://服务
                $res = $this->_cancel_service_order($order ,$uid);
                break;
            case 2://邀约
                $res = $this->_cancel_demand_order($order ,$uid);
                break;
        }
        return $res;
    }
    /**
     * 取消邀约订单
     */
    public function _cancel_demand_order($order = '', $uid = ''){
        $o_where['order_id'] = $order['order_id'];

        $options = unserialize($order['options']);
        // 提供邀约者
        $sell_uid = $options['created_uid'];
        // 参加邀约者
        $parter_uid = $order['created_uid'];
         // 启动事务
        Db::startTrans();
        try{
            // 修改订单状态
            $save['status'] = 5;
	        $save['update_at'] = time();
            $res = OrderModel::self()->where($o_where)->update($save);
            if(!$res){
                Db::rollback();
                return '修改订单状态失败';
            }
            switch ($order['status']) {
                case 1://参加者参加邀约 发布者还未同意之前 无论双方谁取消，都解冻参加者的冻结金额
                    $op_money = order_freeze_money($order);
                    $res = UserService::self()->updata_user_account($parter_uid, $op_money, 'free');
	                if(!$res){
                        Db::rollback();
                        return '解冻账户失败，请刷新再试';
                    }
	                Db::commit();
                    return $res;
                    break;
                case 3://参加者参加邀约，提供者已同意并支付赏金
                    if($parter_uid == $uid){
                        // 扣除参加者冻结的金额，平台和提供者37分
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->updata_user_account($parter_uid, $op_money, 'cancel');
                        if(!$res){
                            Db::rollback();
                            return '解冻账户失败，请刷新再试';
                        }
                        //参加者取消 退还提供者支付订单的金额，
                        $op_money = $order['total_fee'];
                        $res = UserService::self()->updata_user_account($sell_uid, $op_money, 'canceld');
                        if(!$res){
                            Db::rollback();
                            return '解冻账户失败，请刷新再试';
                        }
                        // 按比例给提供者补偿
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->dist_penalty_money($sell_uid, $op_money);
                        if($res === false){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                        Db::commit();    
                        return true;
                    }else if($sell_uid == $uid){ //提供者取消 
                        // 退还提供者95%的赏金
                        $op_money = $order['total_fee'] - order_freeze_money($order);
                        $res = UserService::self()->updata_user_account($sell_uid, $op_money, 'refund');
                        if(!$res){
                            Db::rollback();
                            return '退款失败请稍后再试';
                        }
                        // 解冻参加者冻结的5%的金额
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->updata_user_account($parter_uid, $op_money, 'free');
                        if(!$res){
                            Db::rollback();
                            return '退款失败请稍后再试';
                        }
                        // 扣除提供者支付赏金的5%，平台和参加者37分5%，
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->dist_penalty_money($parter_uid, $op_money);
                        if($res === false){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                        Db::commit();    
                        return true;
                    }
                    break;
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return '操作失败';
        }
    }
    /**
     * 取消服务订单
     */
    public function _cancel_service_order($order = '', $uid = ''){
        $o_where['order_id'] = $order['order_id'];
        $options = unserialize($order['options']);
        // 提供服务
        $provider_uid = $options['created_uid'];
        // 购买服务者
        $buyer_uid = $order['created_uid'];

        // 启动事务
        Db::startTrans();
        try{
            // 修改订单状态
            $save['status'] = 5;
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
                    $res = UserService::self()->updata_user_account($provider_uid, $op_money, 'free');
                    if(!$res){
                        Db::rollback();
                        return '解冻账户失败，请刷新再试';
                    }
                    break;
                case 3:// 购买者支付完订单
                    // 购买者取消，扣除购买者订单5%的金额，平台和提供者37分；
                    if($buyer_uid == $uid){
                        // 退换购买者95%订单金额
                        $op_money = $order['total_fee'] - order_freeze_money($order);
                        $res = UserService::self()->updata_user_account($buyer_uid, $op_money, 'refund');
                        if(!$res){
                            Db::rollback();
                            return '解冻账户失败，请刷新再试';
                        }
                        // 按比例给提供者补偿
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->dist_penalty_money($provider_uid, $op_money);
                        if($res === false){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                    }else if($provider_uid == $uid){ //发布者取消 
                        // 退换购买者订单金额
                        $op_money = $order['total_fee'];
                        $res = UserService::self()->updata_user_account($buyer_uid, $op_money, 'canceld');
                        if(!$res){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                        // 扣除发布者冻结的5%，
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->updata_user_account($provider_uid, $op_money, 'cancel');
                        if(!$res){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                        // 购买者和平台37分；
                        $op_money = order_freeze_money($order);
                        $res = UserService::self()->dist_penalty_money($buyer_uid, $op_money);
                        if($res === false){
                            Db::rollback();
                            return '更新账户信息失败';
                        }
                    }
                    break;
            }
            Db::commit();    
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return '操作失败';
        }
    }
    /**
     * 确认服务订单
     * @param order 订单信息
     * @param status = 2
     * @param uid 当前用户id
     * 冻结服务订单提供者5%的金额
     */
    public function confirm_service_order($order_id = '', $uid = ''){
        // 查询订单数据
        $o_where['order_id'] = $order_id;
        $o_where['status'] = 1;
        $order = OrderModel::self()->where($o_where)->find();
        if(!$order){
            return '订单信息不存在，请刷新再试';
        }
        // 用户余额必须大于 订单金额的 5%
        $user = UserExtModel::self()->findExt($uid, 'account, freeze_money');
        // 订单需冻结金额
        $least_money = order_freeze_money($order);
        if($least_money > $user['account']){
           return '20001';//账户余额不足
        }

        $options = unserialize($order['options']);
        if($uid != $options['created_uid']){
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
    public function del_order($order_id = '', $uid = ''){
        // 查询订单数据
        $o_where['order_id'] = $order_id;
        $o_where['created_uid'] = $uid;
        $order = OrderModel::self()->where($o_where)->find();
        if(!$order){
            return '订单信息不存在，请刷新再试';
        }
        $save['status'] = 6;
        $save['update_at'] = time();
        $res =  OrderModel::self()->where($o_where)->update($save);
        if(!$res){
            return '删除订单失败，请刷新再试';
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
        $save['status'] = 2;
        $save['update_at'] = time();
        return OrderModel::self()->where($where)->update($save);
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
    public function alipay_recharge_success($param = ''){
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
     * 订单支付成功处理
     */
    public function alipay_order_success($param = ''){
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
                return false;
            }
            // 流水明细到底该写在谁的名下
            if($order['source_type'] == 1){//服务
                $uid = $order['created_uid'];
            }else if($order['source_type'] == 2){//邀约
                $options = order_options($order['options']);
                $uid = $options['created_uid'];
            }
            // 添加流水信息
            $pay_type = 2;//支付宝支付
            $pay_info = Config::get('pay.pay_type');
            $running_type = Config::get('running_type.consume');
            $op_money = $order['total_fee'];
            $running['pay_intro'] = $pay_info[$pay_type];
            $running['title'] = '订单支付';
            $res = RunningModel::self()->add($pay_type,$running_type, $op_money, $uid, $running);
            if(!$res){
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
    }
}
