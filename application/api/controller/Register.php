<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;

use app\common\controller\ApiBase;
use think\Db;
use app\api\service\LoginService;
use app\common\service\UserService;
/**
 * Class Register
 * @package app\example\controller
 */
class Register extends ApiBase {
    /**
     * 注册接口
     * @method post
     * @parameter string username 用户名
     * @parameter string password 密码
     * @parameter string nickname 非必须
     */
    public function index(){
        $username  = $this->param['username'];
        // $password  = $this->param['password'];
        $password  = $this->param['username'];

        if(Db::name('users')->where('login_name',$username)->count()){
            $this->wrong( 0 ,"手机号已存在，请直接登录");
        }

        /***针对短信通知验证码的验证***/
       $verify   = $this->param['verify'];
       $check = UserService::checkVerifyCode($username,$verify);  // 验证码检查
       // if($check){
       //     $this->wrong($check);
       // }

        $user_uniq = uniqid($username);
        $password  = make_password($password,$user_uniq);

        $data = [
            'user_uniq'=>$user_uniq,
            'login_name'=>$username,
            'login_pass'=>$password,
            'created_at'=>time()
        ];

        if(isset($this->param['nickname']) && !empty($this->param['nickname'])){
            $data['nickname']  = $this->param['nickname'];
        }

        $user_id = Db::name('users')->insertGetId($data);
        if($user_id){
            $user = Db::name('users')->find($user_id);
            $result = LoginService::login($user);
            if(is_int($result)){
                $this->wrong($result);
            }
            $this->response($result, 1, '注册成功');
        }
        $this->wrong(500);
    }
}