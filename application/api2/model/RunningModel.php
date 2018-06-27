<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\model;

use think\Model;
/**
 * 充值 提现流水model
 */
class RunningModel extends Model{
    protected $name = "running";

    public static function self(){
        return new self();
    }
    /**
     * 添加流水
     * type 流水分类 1：充值+；2：消费-；3：退款+；4：提现-；5：收入+
     * money 流水金额
     * source_id
     */
    public function add($pay_type = 0, $type = '', $money = '0', $uid = '', $options = []){
        $save['pay_type'] = $pay_type;
        $save['type'] = $type;
        $save['money'] = $money;
        $save['uid'] = $uid;
        $save['options'] = serialize($options);
        $save['created_at'] = time();
        return $this->insertGetId($save);
    }
}