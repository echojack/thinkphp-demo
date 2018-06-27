<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\service;

use think\Db;
use think\Cache;
use think\Config;
use app\api\model\UserModel;
use app\api\model\UserExtModel;
use app\api\model\UserAccountModel;
use app\api\model\ConfigModel;
use app\api\model\RunningModel;
use app\api\model\LogsModel;
use app\api\service\RongCloudService;
use app\api\service\OrderAccountService;

class UserService {

    public static function self(){
        return new self();
    }
    /**
     * 获取用户详情
     */
    public function detail($uid = '', $my_uid = ''){
        if(!$uid){
            return [];
        }
        // 查询数据库信息
        $return = UserModel::self()->detail_all($uid);
        // 只能看到自己的账户信息
        if($my_uid != $uid){
            unset($return['account']);
            unset($return['freeze_money']);
        }
        unset($return['token']);
	    $return['age'] = 0;
        if($return['birthday']){
            $return['age'] = date("Y")- substr($return['birthday'], 0,4);
        }
        // 分享链接
        $return['share_links'] = url('Share/user', ['uid'=>$uid, 'token'=>md5($uid.Config::get('public.key'))], true, Config::get('domain'));
	    $return['user_bg'] = Config::get('user_bg.bg'.rand(1,4));
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
            $data['nick_name'] = strEncode($param['nick_name']);
            $ry_name = $param['nick_name'];
        }
        // 扩展信息 头像
        if(isset($param['avatar']) && !empty($param['avatar']) ){
            $ext['avatar'] = $param['avatar'];
            $ry_avatar = Config::get('img_url').$data['avatar'];
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
        $this->refreshToken($uid);
        // 融云信息修改
        RongCloudService::self()->refresh($uid, $ry_name, $ry_avatar);
        return $this->detail($uid, $uid);
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
    public function recharge($account = 0, $pay_type = 1, $user =[]){
	    $uid = $user['uid'];
        // 生成对应的订单信息
        $order_id = OrderAccountService::self()->add($account, $pay_type, 1, $uid);
        if($order_id === false){
            return '充值失败，请稍后再试';
        }
        // 获取订单信息
        $order = OrderAccountService::self()->detail($order_id);
        return $order;
    }
    /**
     * 账户提现
     */
    public function withdraw($uid, $account = 0, $pay_type = 3){
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
        return $order;
    }

    /**
     * 刷新用户缓存信息
     */
    public static function refreshToken($uid = ''){
        if(!$uid){
            return false;
        }
        // 查询数据库信息
        $user = UserModel::self()->detail_simple($uid);
        // 更新缓存
        //$user_id = $user['uid'];
        $token = $user['token'];
        $user = serialize($user);
        $setting_token = Config::get('setting.token');
        $expire = isset($setting_token['token_expire'])?$setting_token['token_expire']:3600;
        //Cache::set('login_token'.$user_id, $token);
        return Cache::set('login/'.$token,$user,$expire);
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
            case 'recharge':
                // 更新账户信息
                $save['account'] = $account['account'] + $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                // 流水分类
                $running_type = Config::get('running_type.recharge');
                $title = '账户充值';
                break;
            case 'pay_error':
                // 更新账户信息
                $save['account'] = $account['account'] - $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                // 流水分类
                $running_type = Config::get('running_type.pay_error');
                $title = '支付失败';
                break;
            case 'withdraw':
                // 更新账户信息
                $save['account'] = $account['account'] - $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                // 流水分类
                $running_type = Config::get('running_type.withdraw');
                $title = '账户提现';
                break;
            case 'freeze':// 冻结
                // 操作金额不能大于 账户可用余额
                if($op_money > $account['account']){
                    return '冻结金额不能大于账户可用余额';
                }
                // 更新账户信息
                $save['account'] = $account['account'] - $op_money;
                $save['freeze_money'] = $account['freeze_money'] + $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                // 流水分类
                $running_type = Config::get('running_type.freeze');
                $title = '账户金额冻结';
                break;
            case 'consume'://消费
                if($op_money > $account['account']){
                    return '账户余额不足，请先充值或用其他方式支付';
                }
                // 修改用户账户信息
                $save['account'] = $account['account'] - $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                if(!$res){
                    return '更新账户余额失败，请稍后再试';
                }
                // 添加明细记录
                $running_type = Config::get('running_type.consume');
                $title = '消费';
                break;
            case 'income':
                // 修改用户账户信息
                $save['account'] = $account['account'] + $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                if(!$res){
                    return '更新账户余额失败，请稍后再试';
                }
                // 添加明细记录             
                $running_type = Config::get('running_type.income');
                $title = '收入';
                break;
            case 'free':
                //解冻
                if($op_money > $account['freeze_money']){
                    return false;
                }
                // 更新账户信息
                $save['account'] = $account['account'] + $op_money;
                $save['freeze_money'] = $account['freeze_money'] - $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                // 流水分类
                $running_type = Config::get('running_type.free');
                $title = '冻结金额解冻';
                break;
            case 'refund'://退款
                // 修改用户账户信息
                $save['account'] = $account['account'] + $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                if(!$res){
                    return '更新账户余额失败，请稍后再试';
                }
                // 添加明细记录             
                $running_type = Config::get('running_type.refund');
                $title = '退款';
                break;
            case 'canceld'://被取消  获得收入
                // 修改用户账户信息
                $save['account'] = $account['account'] + $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                if(!$res){
                    return '更新账户余额失败，请稍后再试';
                }
                // 添加明细记录             
                $running_type = Config::get('running_type.canceld');
                $title = '订单被取消退款';
                break;
            case 'cancel':
                //解冻
                if($op_money > $account['freeze_money']){
                    return false;
                }
                // 更新账户信息
                $save['freeze_money'] = $account['freeze_money'] - $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                // 流水分类
                $running_type = Config::get('running_type.cancel');
                $title = '取消订单，冻结金额被扣除';
                break;
            case 'make_up':
                // 修改用户账户信息
                $save['account'] = $account['account'] + $op_money;
                $res = UserExtModel::self()->updateExt($save, $uid);
                if(!$res){
                    return '更新账户余额失败，请稍后再试';
                }
                // 添加明细记录             
                $running_type = Config::get('running_type.make_up');
                $title = '订单被取消补偿';
                break;
            default:
                # code...
                break;
        }
        // 添加流水信息
        $pay_info = Config::get('pay.pay_type');
        $running['pay_intro'] = $pay_info[$pay_type];
        $running['title'] = $title;
        $res = RunningModel::self()->add($pay_type,$running_type, $op_money, $uid, $running);
        if(!$res){
            return false;
        }
        // 刷新用户缓存
        $this->refreshToken($uid);
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

}
