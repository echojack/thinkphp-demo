<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\service;
use Exception;
use think\Db;
use think\Config;
use app\api6\model\LogsModel;
use app\api6\model\RunningModel;
use app\api6\model\OrderAccountModel;
class OrderAccountService {

    public static function self(){
        return new self();
    }
    /**
     * @param total_fee float 金额
     * @param pay_type int 支付方式 1 微信 2支付宝
     * @param type int 金额 交易类型 1充值  2 提现
     * @param user array 金额 当前用户信息
     */
    public function add($total_fee = 0, $pay_type = 1, $type=1, $uid =[]){
        // 配置信息
        $pay_info = Config::get('pay.pay_type');
        $data['order_no'] = order_no($pay_type);
        $data['type'] = $type;
        $data['pay_type'] = $pay_type;
        $data['total_fee'] = $total_fee;
        $data['uid'] = $uid;
        $data['created_uid'] = $uid;
        $data['intro'] = $pay_info[$pay_type];
        $data['status'] = 2;
        $data['created_at'] = time();
        Db::startTrans();
        try{
            // 账户充值订单添加
            $order_id = OrderAccountModel::self()->insertGetId($data);
            if(!$order_id){
                Db::rollback();    
                return false;
            }
            Db::commit();    
            return $order_id;
        }catch(\Exception $e){
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

    /**
     * 订单详情
     */
    public function detail($order_id = ''){
        if(!$order_id) return [];
        
        $where['order_id'] = $order_id;
        $detail = OrderAccountModel::self()->where($where)->find();
        if(!$detail){
            return [];
        }
        $return['order_id'] = $detail->order_id;
        $return['order_no'] = $detail->order_no;
        $return['total_fee'] = $detail->total_fee;
        $return['pay_type'] = $detail->pay_type;
        $return['created_uid'] = $detail->created_uid;
        $return['subject'] = ($detail->type == 1) ? '账户充值' : '账户提现';
        return $return;
    }
}