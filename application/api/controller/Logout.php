<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;
use think\Cache;
use app\common\controller\ApiLogin ;
/**
 * Class Login
 * @package app\common\controller
 */
class Logout extends ApiLogin {
    /**
     * 登陆接口
     * @desc 验证用户名密码
     * @method POST
     * @parameter string username 用户名
     * @parameter string password 密码
     * @response string token 令牌
     */
    public function index(){
        $token = $this->request->param('token', '', 'string');
        $user_id = $this->user['uid'];
        Cache::rm('login_token'.$user_id);
        Cache::rm($token);
        $this->response([], 1, '退出成功');
    }

}
