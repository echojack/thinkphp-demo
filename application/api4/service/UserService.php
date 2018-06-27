<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api4\service;

use think\Db;
use think\Cache;
use think\Config;
use app\api4\model\UserModel;
use app\api4\model\UserExtModel;
use app\api4\model\UserAccountModel;
use app\api4\model\ConfigModel;
use app\api4\model\RunningModel;
use app\api4\model\LogsModel;
use app\api4\service\RongCloudService;
use app\api4\service\OrderAccountService;

class UserService {

    public static function self(){
        return new self();
    }
    /**
     * 获取用户详情
     */
    public function detail($uid = '', $current_uid=''){
        if(!$uid){
            return [];
        }
        $return = $this->get_user_base_info($uid, $current_uid);
        // 只能看到自己的账户信息
        unset($return['account']);
        unset($return['freeze_money']);
        unset($return['token']);
        unset($return['update_at']);
        // 用户统计数据
        $return['friends_count'] = $return['follow_count'] = $return['follower_count'] = 0;
        $user_data = Db::name('users_data')->where(['uid'=>$uid])->find();
        if($user_data){
            $return['friends_count'] = $user_data['friends_count'];
            $return['follow_count'] = $user_data['follow_count'];
            $return['follower_count'] = $user_data['follower_count'];
        }
        // 和当前用户的关系
        if($current_uid){
            $f_where['follow_id'] = $uid;
            $f_where['follower_id'] = $current_uid;
            $follow = Db::name('follows')->where($f_where)->count();
            $return['follow'] = $follow ? 1 : 2;
        }
        // 是否被加入黑名单
        $b_where['uid'] = $uid;
        $b_where['created_uid'] = $current_uid;
        $is_black = Db::name('blacklist')->where($b_where)->count();
        $return['is_black'] = $is_black ? 1 : 0;
        return $return;
    }
    /**
     * 修改用户信息
     */
    public function update($param = [], $uid = ''){
        if(!$param || !$uid){
            return false;
        }
        $data = [];$user = [];$ext = [];$ry_name = $ry_avatar = '';
        // 用户昵称
        if(isset($param['nick_name'])){
            $data['nick_name'] = strEncode(trim($param['nick_name']));
            $ry_name = $param['nick_name'];
        }
        // 扩展信息 头像
        if(isset($param['avatar']) && !empty($param['avatar']) ){
            $ext['avatar'] = trim($param['avatar']);
            $ry_avatar = Config::get('img_url').$ext['avatar'];
        }
        if(isset($param['sex'])){
            $ext['sex'] = intval($param['sex']);
        }
        
        if(isset($param['birthday']) && string($param['birthday']) ){
            $ext['birthday'] = string($param['birthday']);
        }
        if(isset($param['intro']) && $param['intro'] ){
            $ext['intro'] = strEncode($param['intro']);
        }

        if(isset($param['occupation'])){
            $ext['occupation'] = intval($param['occupation']);
        }
        if(isset($param['height'])){
            $ext['height'] = floatval($param['height']);
        }
        if(isset($param['weight']) ){
            $ext['weight'] = floatval($param['weight']);
        }
        if(isset($param['marry']) ){
            $ext['marry'] = intval($param['marry']);
        }
        if(isset($param['hot_address'])){
            $ext['hot_address'] = strEncode($param['hot_address']);
        }
        // 学校
        if(isset($param['school_id'])){
            $ext['school_id'] = intval($param['school_id']);
        }
        // 标签
        if(isset($param['tags']) ){
            $ext['tags'] = string($param['tags']);
        }
        // 昵称修改
        if(!empty($data)){
            $data['update_at'] = time();
            $res = UserModel::self()->updateUser($data,$uid);
        }
        // 其他修改
        if(!empty($ext)){
            Db::name('users_ext')->where(['uid'=>$uid])->update($ext);
        }
        $this->cleanCache($uid);
        // 融云信息修改
        if($ry_name || $ry_avatar){
            RongCloudService::self()->refresh($uid, $ry_name, $ry_avatar);
        }
        return $this->detail($uid);
    }
    /**
     * 我的钱包
     */
    public function mywallet($uid = ''){
        $wallet = UserExtModel::self()->findExt($uid, 'account, freeze_money');
        if(!$uid || !$wallet){
            $return['account'] = $return['freeze_money'] = 0;
            return $return;
        }
        return $wallet;
    }
    /**
     * 流水明细
     */
    public function running($uid = '', $page = 1, $limit = 10){
        $tmp_lists = [];
        $lists = Db::name('running')->where(['uid'=>$uid])->order('id DESC')
                ->page($page, $limit)->select();
        if($lists){
            foreach ($lists as $val) {
                $options = unserialize($val['options']);
                $val['options'] = ['title'=>@$options['title'], 'pay_intro'=>@$options['pay_intro']];
                $tmp_lists[] = $val;
            }
        }
        return $tmp_lists;
    }
    /**
     * 用户充值
     */
    public function generate_recharge_order($account = 0, $pay_type = 1, $user =[]){
	    $uid = $user['uid'];
        // 生成对应的订单信息
        $order_id = OrderAccountService::self()->add($account, $pay_type, 1, $uid);
        if($order_id === false){
            return '生成充值订单失败，请刷新再试';
        }
        // 获取订单信息
        $order = OrderAccountService::self()->detail($order_id);
        return ['code'=>1, 'detail'=>$order];
    }
    /**
     * 账户提现
     */
    public function generate_withdraw_order($uid, $account = 0, $pay_type = 3){
        if(!$uid || !$account){
            return '非法请求';
        }
        // 检测账户余额是否可提现
        $user = UserExtModel::self()->findExt($uid, 'account, freeze_money');
        if($account > $user['account']){
            return '账户余额不足，提现失败';
        }
        // 生成对应的订单信息
        $order_id = OrderAccountService::self()->add($account, $pay_type, 2, $uid);
        if($order_id === false){
            return '添加提现订单失败';
        }
        // 获取订单信息
        $order = OrderAccountService::self()->detail($order_id);
        return ['code'=>1, 'detail'=>$order];
    }

    /**
     * 刷新用户缓存信息
     */
    public function cleanCache($uid = ''){
        // 清除文件缓存
        $cache_key = 'user_'.$uid;
        Cache::rm($cache_key);
    }

    /**
     * 修改账户余额信息
     * @param uid 操作用户id
     * @param op_money 操作金额
     */
    public function updata_user_account($uid = '', $op_money = '', $flag = 'freeze', $pay_type = 3){
        if(!$uid || !$op_money ){
            return '非法请求，参数不完整';
        }
        $save['update_at'] = time();
        // 用户目前账户信息
        $account = UserExtModel::self()->findExt($uid, 'account, freeze_money');
        switch ($flag) {
            case 'freeze':// 冻结
                if($op_money > $account['account']){
                    return '冻结金额不能大于账户可用余额';
                }
                // 更新账户信息
                $save['account'] = $account['account'] - $op_money;
                $save['freeze_money'] = $account['freeze_money'] + $op_money;
                // 流水日志信息
                $running['title'] = '账户金额冻结';
                $running['intro'] = '确认服务订单';
                break;
            case 'demand_freeze':// 冻结
                if($op_money > $account['account']){
                    return '冻结金额不能大于账户可用余额';
                }
                // 更新账户信息
                $save['account'] = $account['account'] - $op_money;
                $save['freeze_money'] = $account['freeze_money'] + $op_money;
                // 流水日志信息
                $running['title'] = '账户金额冻结';
                $running['intro'] = '参加邀约';
                $flag = 'freeze';
                break;
            case 'free':
                //解冻
                if($op_money > $account['freeze_money']){
                    return '解冻金额不能大于用户冻结金额';
                }
                // 更新账户信息
                $save['account'] = $account['account'] + $op_money;
                $save['freeze_money'] = $account['freeze_money'] - $op_money;
                // 流水日志信息
                $running['title'] = '冻结金额解冻';
                $running['intro'] = '订单取消';
                break;
            case 'finish'://订单完成
                //解冻
                if($op_money > $account['freeze_money']){
                    return '解冻金额不能大于用户冻结金额';
                }
                // 更新账户信息
                $save['account'] = $account['account'] + $op_money;
                $save['freeze_money'] = $account['freeze_money'] - $op_money;
                // 流水日志信息
                $running['title'] = '冻结金额解冻';
                $running['intro'] = '订单完成';
                $flag = 'free';
                break;
            case 'consume'://消费
                if($op_money > $account['account']){
                    return '账户余额不足，请先充值或用其他方式支付';
                }
                // 修改用户账户信息
                $save['account'] = $account['account'] - $op_money;
                // 流水日志信息
                $running['title'] = '消费';
                $running['intro'] = '订单支付';
                break;
            case 'refund'://退款
                // 修改用户账户信息
                $save['account'] = $account['account'] + $op_money;
                // 流水日志信息
                $running['title'] = '退款';
                $running['intro'] = '订单取消';
                break;
            case 'make_up':
                // 修改用户账户信息
                $save['account'] = $account['account'] + $op_money;
                // 流水日志信息
                $running['title'] = '补偿';
                $running['intro'] = '订单被取消';
                break;
            case 'canceld'://被取消  获得收入
                // 修改用户账户信息
                $save['account'] = $account['account'] + $op_money;
                // 流水日志信息
                $running['title'] = '退款';
                $running['intro'] = '订单被取消';
                break;
            case 'cancel':
                //解冻
                if($op_money > $account['freeze_money']){
                    return '解冻金额不能大于用户冻结金额';
                }
                // 更新账户信息
                $save['freeze_money'] = $account['freeze_money'] - $op_money;
                // 流水日志信息
                $running['title'] = '冻结金额扣除';
                $running['intro'] = '取消订单';
                break;
            case 'income':
                // 修改用户账户信息
                $save['account'] = $account['account'] + $op_money;
                // 流水日志信息
                $running['title'] = '收入';
                $running['intro'] = '订单完成';
                break;
            case 'recharge':
                // 更新账户信息
                $save['account'] = $account['account'] + $op_money;
                // 流水日志信息
                $running['title'] = '账户充值';
                $running['intro'] = '支付宝充值';
                break;
            case 'pay_error':
                // 更新账户信息
                $save['account'] = $account['account'] - $op_money;
                // 流水日志信息
                $running['title'] = '支付失败扣除';
                $running['intro'] = '支付失败';
                break;
            case 'withdraw':
                // 更新账户信息
                $save['account'] = $account['account'] - $op_money;
                // 流水日志信息
                $running['title'] = '账户提现';
                $running['intro'] = '账户提现';
                break;
            default:
                # code...
                break;
        }
        $res = UserExtModel::self()->updateExt($save, $uid);
        if(!$res){
            return '更新账户余额失败，请稍后再试';
        }
        // 添加流水信息
        $running_type   = Config::get('running_type.'.$flag);
        $pay_info       = Config::get('pay.pay_type');
        $running['pay_intro'] = $pay_info[$pay_type];
        $res = RunningModel::self()->add($pay_type,$running_type, $op_money, $uid, $running);
        if(!$res){
            return '添加流水失败';
        }
        // 刷新用户缓存
        $this->cleanCache($uid);
        return true;
    }

    /**
     * 平台和 用户按比例  获取 违约金
     */
    public function dist_penalty_money($uid = '', $money = ''){
        $ratio = Config::get('payment.penalty_platform_ratio');
        $user_money = $money - round($ratio*$money)/100;
        $msg = $this->updata_user_account($uid, $user_money, 'make_up');
        return $msg;
    }

    /**
     * 获取指定用户的黑名单列表
     */
    public function blacklist($uid = ''){
        if(!$uid){
            return [];
        }

        // 数据处理
        $tmp_ids = [];
        $list = Db::table('blacklist')->where(['created_uid' => $uid])->select();
        if($list){
            foreach ($list as $val) {
                $tmp_ids[] = $val['uid'];
            }
        }
        return $tmp_ids;
    }
    
    /**
     * 简单用户详情
     */
    public function simple_detail($uid = '', $current_uid='' ){
        $detail = $this->detail($uid, $current_uid);
        $simple_detail['uid'] = $detail['uid'];
        $simple_detail['nick_name'] = $detail['nick_name'];
        $simple_detail['avatar'] = $detail['avatar'];
	$simple_detail['age'] = $detail['age'];
        $simple_detail['sex'] = $detail['sex'];
	$simple_detail['intro'] = $detail['intro'];
        $simple_detail['is_black'] = $detail['is_black'];
        $simple_detail['follow'] = isset($detail['follow']) ? $detail['follow'] : 0;
        return $simple_detail;
    }

    /**
     * 获取用户经纬度
     */
    public function get_user_base_info($uid = '', $current_uid = ''){
        $uid = intval($uid);
        $cache_key = 'user_'.$uid;
        $return = Cache::get($cache_key);
        if(1){
            // 查询数据库信息
            $return = UserModel::self()->detail_all($uid);
            $return['occupation_txt'] = occupation($return['occupation']);
            $return['marry_txt'] = marry($return['marry']);
            $return['age'] = 0;
            if($return['birthday']){
                $return['age'] = date("Y")- substr($return['birthday'], 0,4);
            }
            $return['xingzuo'] = get_xingzuo($return['birthday']);
            // 分享链接
            $return['share_links'] = url('Share/user', ['uid'=>$uid, 'token'=>md5($uid.Config::get('public.key'))], true, Config::get('domain'));
            $return['user_bg'] = Config::get('user_bg.bg'.rand(1,4));
           Cache::set($cache_key, $return);
        }
        return $return;
    }

    /**
     * 用户列表
     */
    public function lists($param = [], $page = 1, $limit = 10){
        $where = $this->_list_where($param);
        $lists = Db::table('users')->field('*')
                ->where($where)
                ->order('uid DESC')
                ->page($page, $limit)->select();
        return $lists;
    }

    private function _list_where($param = []){
        $where['uid'] = ['egt', 4];
        if(isset($param['status'])){
            $where['status'] = $param['status'];
        }
        if(isset($param['uid'])){
        	$where['uid'] = ['not in', [$param['uid'], 1, 2, 3]];
	}
        return $where;
    }
    /**
     * 列表 统计数据
     */
    public function lists_count($param = [], $per_page = 10){
        $where = $this->_list_where($param);
        $count = Db::table('users')->field('users.*')
                ->where($where)->count();
        return ceil($count/$per_page);
    }


}
