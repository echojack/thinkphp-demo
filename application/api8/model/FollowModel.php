<?php
namespace app\api8\model;

use think\Model;

class FollowModel extends Model
{
    protected $name = 'follows';
    public static function self(){
        return new self();
    }
    /**
     * 添加反馈信息
     */
    public function add($data = []){
        if(!$data){
            return false;
        }
        return $this->insert($data);
    }


}