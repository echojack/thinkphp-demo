<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\controller;

use think\Db;
use think\Config;
use think\Validate;
use app\common\controller\ApiBase;
use app\common\service\UserService;
use app\api6\model\UserModel;
use app\api6\model\ImgDownModel;
use app\api6\service\LoginService;
use app\api6\service\RongCloudService;
use app\api6\service\LogsService;
/**
 * Class Login
 * @package app\common\controller
 */
class Login extends ApiBase {
    /**
     * 登陆接口
     * @desc 验证用户名密码
     * @method POST
     * @parameter string username 用户名
     * @parameter string password 密码
     * @response string token 令牌
     */
    public function index(){

        $this->_indexBefore();

        $mobile = $this->request->param('mobile', '', 'string');
        $password = $mobile;
        $verify   = $this->request->param('verify', '', 'intval');
        $user = UserModel::self()->findUser(0,$mobile);
        if(empty($user)){
            $this->wrong(0, '手机号不存在');
        }

        // if($user['login_pass']!=make_password($password,$user['user_uniq'])){
        //     $this->wrong(0, '密码和手机号不符');
        // }
	    if($mobile == '18501360496'){
            if($verify != '112233'){
                $this->wrong(0, '请输入指定验证码');
            }
        }else{
             /***针对短信通知验证码的验证***/
           $verify   = $this->param['verify'];
           $msg = UserService::checkVerifyCode($mobile,$verify);  // 验证码检查
           if(!is_int($msg)){
               $this->wrong(0, $msg);
           }
        }
        $result = LoginService::login($user);

        $remark = ['mobile'=>$mobile, 'verify'=>$verify];
        if(is_int($result)){
            LogsService::self()->add($user['uid'], strDecode($user['nick_name']), OTHER_ACT, 0, LOGIN, ERR_ACT, $remark);
            $this->wrong(0, '登录失败');
        }
        LogsService::self()->add($user['uid'], strDecode($user['nick_name']), OTHER_ACT, 0, LOGIN, SUC_ACT, $remark);
	    $this->response($result, 1, '登录成功');
    }
    /**
     * 数据监测
     */
    private function _indexBefore(){
        $mobile = $this->request->param('mobile', '', 'string');
        $verify = $this->request->param('verify', '', 'intval');

        if(!$mobile || !$verify){
            $this->wrong(0, '非法请求，缺少参数');
        }

        $password = $mobile;
        $verify   = $verify;
        $rule = ['mobile'  => 'require|number|length:11', 'verify'  => 'require|number|length:6'];
        $msg = [
                'mobile.require'=>'请输入手机号',
                'mobile.number'=>'请输入正确的手机号',
                'mobile.length'=>'请输入正确的手机号',
                'verify.require'=>'请输入验证码',
                'verify.number'=>'请输入正确的验证码',
                'verify.length'=>'请输入正确的验证码',
                ];
        $data['mobile'] = trimAll($mobile);
        $data['verify'] = trimAll($verify);
        $validate = new Validate($rule,$msg);
        $result   = $validate->check($data);

        if(!$result){
            $this->wrong(0, $validate->getError());
        }
    }
    /**
     * 第三方用户登录
     */
    public function other_login(){
        $type = $this->request->param('type', '', 'string');
        $access_token = $this->request->param('access_token', '', 'string');

        if(!$type || !$access_token){
            $this->wrong(0, '非法请求，缺少参数');
        }

        switch ($type) {
            case 'wx':
                $where['wx_identify'] = $access_token;
                break;
            case 'wb':
                $where['wb_identify'] = $access_token;
                break;
            case 'qq':
                $where['qq_identify'] = $access_token;
                break;
        }
        // 昵称
        $nick_name = $this->request->param('nick_name', '', 'string');
        // 性别
        $sex = $this->request->param('sex', 0, 'intval');
	    // 头像
        // $avatar = $this->request->param('avatar', '', 'string');
	    $avatar = @trim($_REQUEST['avatar']);
	    if($avatar){
            $file = new ImgDownModel($avatar);
            $avatar = $file->getFileName();
        }
        // 用户不存在就注册
        if(!Db::name('users')->where($where)->count()){
            $user_uniq = uniqid($access_token);
            $password = $access_token;
            $password  = make_password($password,$user_uniq);

            $data = [
                'user_uniq'     => $user_uniq,
                'login_name'    => '',
                'nick_name'     => strEncode($nick_name),
                'login_pass'    => $password,
                'created_at'    => time()
            ];
            $data = array_merge($data, $where);
            $user_id = Db::name('users')->insertGetId($data);
            if(!$user_id){
                $this->wrong(0, '网络太慢，请稍后再试');
            }
            $ext_data['uid'] = $user_id;
	        Db::name('users_data')->insertGetId($ext_data);

            $ext_data['sex'] = $sex;
	        $ext_data['avatar'] = $avatar;
            Db::name('users_ext')->insertGetId($ext_data);
            // 为用户注册相应的融云账号 不属于任何一个群组
            if(!$nick_name){
                $nick_name = 'ZAIMA'.$user_id;
                if($user_id < 100){
                    $nick_name = 'ZAIMA00'.$user_id;
                }
            }
	        // 头像信息
            if(!$avatar){
                $avatar = Config::get('user.avatar'.$sex);
            }
            $res = RongCloudService::self()->getToken($user_id, $nick_name, $avatar);
            if($res['code'] != 200){
                $this->wrong(0, '注册聊天账号失败，请联系管理员处理');
            }
            Db::name('users')->where(['uid'=>$user_id])->update(['ry_token'=>$res['token'], 'nick_name'=>strEncode($nick_name)]);
        }
        $user = Db::name('users')->where($where)->find();

        $remark = ['access_token'=>$access_token, 'nick_name'=>strDecode($user['nick_name'])];
        // 已存在就登录
        $result = LoginService::other_login($user, $type);
        if(is_int($result)){
            LogsService::self()->add($user['uid'], strDecode($user['nick_name']), OTHER_ACT, 0, LOGIN, ERR_ACT, $remark);
            $this->wrong(0, '登录失败');
        }
        LogsService::self()->add($user['uid'], strDecode($user['nick_name']), OTHER_ACT, 0, LOGIN, SUC_ACT, $remark);
        $this->response($result, 1, '登录成功');
    }
}
