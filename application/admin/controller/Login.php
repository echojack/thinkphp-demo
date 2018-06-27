<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;

use app\admin\controller\AdminBase;
use app\admin\service\LoginService;
use app\admin\service\LogsService;
use app\common\model\UserModel;
/**
 * 后台登录首页
 */
class Login extends AdminBase{
    /**
     * 登录
     */
    public function index(){
        $user = session('user');
        if($user){
            $res = LoginService::checkLogin($user);
            if($res){
                $this->redirect('Admin/index');
            }
        }
        return $this->fetch('index');
    }
    /**
     * 验证码
     */
    public function doLogin(){
        $login_name = $this->request->param('login_name', '', 'string');
        $login_pass = $this->request->param('login_pass', '', 'string');
        $online = $this->request->param('online', 0, 'intval');
        if(!$login_name || !$login_pass){
            $this->wrong(0, '请输入用户名和密码');
        }
#        $captcha = $this->request->param('captcha', '', 'string');
#        if(!captcha_check($captcha)){
#            //验证失败
#            $this->wrong(0, '验证码错误');
#        };

        $user = UserModel::self()->get_user_by_login_name($login_name);
        if(empty($user)){
            $this->wrong(0, '账号不存在');
        }

        $result = LoginService::login($user, $login_pass, $online);
        $remark = ['login_name'=>$login_name, 'login_pass'=>$login_pass];
        if(!is_array($result) || !$result){
            // 登录失败
            LogsService::self()->add($user['uid'], $user['nick_name'], OTHER_ACT, 0, LOGIN, ERR_ACT, $remark);
            $this->wrong(0, $result);
        }
        // 登录成功
        LogsService::self()->add($user['uid'], $user['nick_name'], OTHER_ACT, 0, LOGIN, SUC_ACT, $remark);
        $this->response($result, 1, '登录成功');
    }
}
