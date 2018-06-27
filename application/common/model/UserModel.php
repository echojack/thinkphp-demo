<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;

use think\Model;

class UserModel extends Model{
    protected $name = "users";

    public static function self(){
        return new self();
    }

    /**
     * 获取用户信息
     */
    public function get_user_by_login_name($login_name = ''){
        $where['login_name'] = $login_name;
        $where['is_admin'] = 1;
        $user = $this->where($where)->find();
        // echo $this->getLastSql();die();
        if(!$user) return [];
        
        $user = json_decode(json_encode($user), true);
        $user['nick_name'] = hide_phone(strDecode($user['nick_name']));
        return $user;
    }
    /**
     * 获取用户信息
     */
    public function get_user_by_uid($uid = ''){
        $where['uid'] = $uid;
        $user = $this->where($where)->find();
        if(!$user){
            return [];
        }
        $user['nick_name'] = hide_phone(strDecode($user['nick_name']));
        return json_decode(json_encode($user), true);
    }
    /**
     * 获取用户登录需要信息
     */
    public function login_data($uid = ''){
        $user = $this->where(['uid'=>$uid])->find();
        if(!$user){
            return [];
        }

        $user = json_decode(json_encode($user), true);
        $user['nick_name'] = hide_phone(strDecode($user['nick_name']));
        return $user;
    }
    /**
     * 更新用户信息
     */
    public function updateUser($data,$user_id=0){
        if($user_id){
            return $this->where('uid',$user_id)->update($data);
        }
        return false;
    }
    /**
     * 用户列表
     */
    public function lists($param = [], $per_page = 10 ){
        $where = $this->_lists_where($param);
        $lists = $this->where($where)
                ->order('uid DESC')
                ->paginate($per_page);
        return $lists;
    }
    /**
     * 用户列表
     */
    public function lists_count($param = []){
        $where = $this->_lists_where($param);
        $count = $this->where($where)->count();
        return $count;
    }
    /**
     * 用户列表
     */
    private function _lists_where($param = []){
        $where = [];
        if(!empty($param['is_admin'])){
            $where['is_admin'] = $param['is_admin'];
        }
        return $where;
    }



}
