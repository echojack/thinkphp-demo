<?php
namespace app\api2\model;

use think\Model;

class FeedbackModel extends Model
{
    protected $name = 'feedback';
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
        return $this->insertGetId($data);
    }
}