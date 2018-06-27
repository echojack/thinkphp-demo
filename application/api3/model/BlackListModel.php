<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api3\model;

use think\Model;

class BlackListModel extends Model{
    protected $name = "blacklist";

    public static function self(){
        return new self();
    }

    /**
     * 添加黑名单
     */
    public function add($data = []){
        if(!$data){
            return false;
        }
        return $this->insert($data);
    }
    
    /**
     * 从黑名单列表删除
     */
    public function del($where = []){
        if(!$where){
            return false;
        }
        return $this->where($where)->delete();
    }

}