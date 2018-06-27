<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\model;

use think\Model;

class UserExtModel extends Model{
    protected $name = "users_ext";

    public static function self(){
        return new self();
    }
    public function exits($uid){
        return $this->where('uid',$uid)->count();
    }

    public function findExt($uid=0,$field = '*'){
        $where = [];
        if($uid){
            $where['uid'] = $uid;
        }

        $user = $this->where($where)->count();
        if($user){
            return $this->field($field)->where($where)->find()->getData();    
        }else{
            return [];
        }
    }

    public function addUser($data){
        return $this->insertGetId($data);
    }

    public function updateExt($data,$uid=0){
        if($uid){
            return $this->where('uid',$uid)->update($data);
        }
        return false;
    }
}