<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\model;

use think\Model;
use think\Validate;

class ReportsModel extends Model{
    protected $name = "reports";

    public static function self(){
        return new self();
    }

    /**
     * 添加收藏
     */
    public function add($data = []){
        if(!$data){
            return false;
        }
        return $this->insertGetId($data);
    }
    
    /**
     * 删除收藏
     */
    public function del($where = []){
        if(!$where){
            return false;
        }
        return $this->where($where)->delete();
    }
    

}