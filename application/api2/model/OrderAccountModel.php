<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\model;
use think\Db;
use think\Model;

class OrderAccountModel extends Model{
    protected $name = "orders_account";

    public static function self(){
        return new self();
    }
    /**
     * @param total_fee float 金额
     * @param pay_type int 支付方式 1 微信 2支付宝
     * @param type int 金额 交易类型 1充值  2 提现
     * @param user array 金额 当前用户信息
     */


}