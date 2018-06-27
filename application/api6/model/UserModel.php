<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\model;

use think\Model;
use think\Config;
use app\api6\model\ConfigModel;

class UserModel extends Model{
    protected $name = "users";

    public static function self(){
        return new self();
    }
    public function exits($username){
        return $this->where('login_name',$username)->count();
    }

    public function findUser($user_id=0,$username='',$field = '*'){
        $where = [];
        if($user_id){
            $where['uid'] = $user_id;
        }
        if(!empty($username)){
            $where['login_name'] = $username;
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

    public function updateUser($data,$user_id=0){
        if($user_id){
            return $this->where('uid',$user_id)->update($data);
        }
        return false;
    }
    /**
     * 获取用户详细信息
     * 仅在用户登录时 获取token缓存信息时有用
     */
    public function detail_simple($uid = ''){
        if(!$uid){
            return false;
        }
        $detail = $this->field('users.uid , login_name  , nick_name  , token, ry_token, latitude, longitude, school_id, sex, avatar')
                ->join('users_ext ue', 'ue.uid = users.uid', 'left')
                ->where('users.uid', $uid)
                ->find()->getData();
	    //$detail['avatar'] = get_avatar($detail['avatar'], $detail['sex']);
        $detail['nick_name'] = strDecode($detail['nick_name']);
        return $detail;
    }
        /**
     * 获取用户详细信息
     */
    public function detail_all($uid = ''){
        if(!$uid){
            return false;
        }
        $detail = $this->field('ue.*,users.uid , login_name  , nick_name  , token')
                ->join('users_ext ue', 'ue.uid = users.uid', 'left')
                ->where('users.uid', $uid)
                ->find()->getData();
	    $detail['avatar'] = get_avatar($detail['avatar'], $detail['sex']);
        $detail['nick_name'] = hide_phone(strDecode($detail['nick_name']));
        $detail['hot_address'] = strDecode($detail['hot_address']);
        $detail['intro'] = strDecode($detail['intro']);
	    // 标签处理
        $tags_count = 0;
        $tags = ConfigModel::self()->get_key_values(4);
        $tmp_tags = [];
        $tags_id = explode(',', $detail['tags']);
	    if($tags_id){
        	foreach ($tags_id as $k => $tag_id) {
        		if($tag_id && isset($tags[$tag_id])){
                    $tags_count += 1;
            		$tmp_tags[] =['configs_id'=>$tag_id, 'value'=> $tags[$tag_id]];
        		}
       		}
	    }
        $detail['tags_count'] = $tags_count;
        $detail['tags'] = $tmp_tags;
        // 学校信息
        $detail['school_id'] = $detail['school_id'];
        $detail['school_name'] = school_name($detail['school_id']);
        // 资料完善度统计
        $detail['data_percent'] = $this->_data_percent($detail);
        return $detail;
    }
    /**
     * 资料完善度统计
     */
    private function _data_percent($user = []){
        // 用户信息
        $num = 0;
        if($user['sex']){
            $num += 1;
        }
        if($user['birthday']){
            $num += 1;
        }
        if($user['tags']){
            $num += 1;
        }
        if($user['intro']){
            $num += 1;
        }
        if($user['avatar']){
            $num += 1;
        }
        if($user['occupation']){
            $num += 1;
        }
        if($user['height']){
            $num += 1;
        }
        if($user['weight']){
            $num += 1;
        }
        if($user['marry']){
            $num += 1;
        }
        if($user['hot_address']){
            $num += 1;
        }
        return 10*$num.'%';
    }
}
