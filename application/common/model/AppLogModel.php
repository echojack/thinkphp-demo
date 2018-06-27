<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Model;

class AppLogModel extends Model{
    protected $name = "app_log";

    public static function self(){
        return new self();
    }
    /**
     * 添加圈子
     */
    public function add($save = []){
        if(!$save){
            return false;
        }
        return $this->insertGetId($save);
    }

}
