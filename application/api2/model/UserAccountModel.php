<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\model;

use think\Model;

class UserAccountModel extends Model{
    protected $name = "users_account";

    public static function self(){
        return new self();
    }

    public function findAccount($uid=0,$field = '*'){
        $where = [];
        if($uid){
            $where['uid'] = $uid;
        }

        $user = $this->where($where)->count();
        if($user){
            return $this->field($field)->where($where)->select()->getData();    
        }else{
            return [];
        }
    }

    public function addAccount($data){
        return $this->insert($data);
    }

    public function updateAccount($data,$uid=0){
        if($uid){
            return $this->where('uid',$uid)->update($data);
        }
        return false;
    }
}