<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\model;

use think\Db;
use think\Config;
use think\Model;

/**
 * 充值 提现流水model
 */
class CallbackModel extends Model{
    protected $name = "running_callback";

    public static function self(){
        return new self();
    }
    /**
     * 回调处理账户信息
     */
}