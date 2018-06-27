<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\model;

use think\Model;
use think\Validate;

class CollectionsModel extends Model{
    protected $name = "collections";

    public static function self(){
        return new self();
    }
    /**
     * 统计数据
     */
    public function do_count($where = []){
        return $this->where($where)->count();
    }
    /**
     * 添加收藏
     */
    public function add($data = []){
        if(!$data){
            return false;
        }
        // return $this->insertGetId($data);
        return $this->insert($data);
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