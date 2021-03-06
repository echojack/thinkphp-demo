<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\model;

use think\Model;
use think\Config;
use app\api\model\ConfigModel;

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
     */
    public function detail_simple($uid = ''){
        if(!$uid){
            return false;
        }
        $detail = $this->field('users.uid , login_name  , nick_name  , token,ry_token, account ,freeze_money, sex , avatar  , latitude  , longitude')
                ->join('users_ext ue', 'ue.uid = users.uid', 'left')
                ->where('users.uid', $uid)
                ->find()->getData();
	    $detail['avatar'] = get_avatar($detail['avatar'], $detail['sex']);
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
        $detail = $this->field('users.uid , login_name  , nick_name  , token, ue.*')
                ->join('users_ext ue', 'ue.uid = users.uid', 'left')
                ->where('users.uid', $uid)
                ->find()->getData();
	    $detail['avatar'] = get_avatar($detail['avatar'], $detail['sex']);
        $detail['nick_name'] = strDecode($detail['nick_name']);
        $detail['intro'] = strDecode($detail['intro']);
	    // 标签处理
        $tags = ConfigModel::self()->get_key_values(4);
        $tmp_tags = [];
        $tags_id = explode(',', $detail['tags']);
	    if($tags_id){
        	foreach ($tags_id as $k => $tag_id) {
        		if($tag_id && isset($tags[$tag_id])){
            		$tmp_tags[] =['id'=>$tag_id, 'value'=> $tags[$tag_id]];
        		}
       		}
	    }
        $detail['tags'] = $tmp_tags;
        return $detail;
    }
}
