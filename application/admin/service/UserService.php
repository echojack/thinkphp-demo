<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\service;
use think\Db;
use think\Cache;
use think\Config;
use app\common\model\UserModel;
use app\admin\service\RongCloudService;
class UserService {

    public static function self(){
        return new self();
    }

    /**
     * 用户列表
     */
    public function user_lists($param = []){
        $lists = UserModel::self()->lists($param);
        $page = $lists->render();
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $detail) {
                $detail['nick_name'] = strDecode($detail['nick_name']);
                $detail['role'] = get_user_role_name($detail['group']);
                $tmp_lists[] = $detail;
            }
        }
        $data['page'] = $page;
        $data['lists'] = $tmp_lists;
        $data['count'] = UserModel::self()->lists_count($param);
        return $data;
    }

    /**
     * 添加编就用户
     */
    public function user_add($param = [], $uid = ''){
        $save = [];
        if(!$param['nick_name']){
            return '用户昵称不能为空';
        }
        $save['nick_name'] = strEncode($param['nick_name']);
        // if(!$param['login_name']){
        //     return '用户账号不能为空';
        // }
        $save['login_name'] = $param['login_name'];

        if(!$param['user_uniq']){
            $param['user_uniq'] = $save['user_uniq'] = uniqid($save['login_name']);
        }
        if($param['password'] !== $param['repassword']){
            return '两次密码输入不一致';
        }
        if($param['password']){
            $save['login_pass'] = make_password($param['password'],$param['user_uniq']);    
        }
        if($uid){
            $save['status'] = $param['status'];
            $save['update_at']  =time();
            $res = UserModel::self()->where(['uid'=>$uid])->update($save);
            Cache::rm('user_'.$uid);
        }else{
            $save['is_admin'] = 1;
            $save['created_at']  =time();
            $user_id = UserModel::self()->insertGetId($save);

            $ext_data['uid'] = $user_id;
            Db::name('users_ext')->insertGetId($ext_data);
            Db::name('users_data')->insertGetId($ext_data);
            // 为用户注册相应的融云账号 不属于任何一个群组
            $res = RongCloudService::self()->getToken($user_id, $param['nick_name'], Config::get('user.avatar0'));
            if($res['code'] != 200){
                return '注册聊天账号失败，请联系管理员处理';
            }
            Db::name('users')->where(['uid'=>$user_id])->update(['ry_token'=>$res['token']]);
        }
        
        if(!$res){
            return '操作失败';
        }
        return true;
    }
    /**
     * 用户详情
     */
    public function user_detail($uid = ''){
        if(!$uid){
            return [];
        }
        return UserModel::self()->get_user_by_uid($uid);
    }

    /**
     * 设置、取消管理员
     */
    public function user_admin($uid = '', $is_admin = ''){
        if(!$uid || !$is_admin){
            return '非法操作';
        }
        $save['is_admin'] = $is_admin;
        $save['update_at'] = time();
        $where['uid'] = $uid;
        return UserModel::self()->where($where)->update($save);
    }
}
