<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;

use app\admin\controller\MY_admin;
use app\admin\service\LogsService;
/**
 * 后台登录首页
 */
class Logout extends MY_admin{
    /**
     * 退出登录
     */
    public function index(){
        // 退出日志
        LogsService::self()->add($this->uid, $this->nick_name, OTHER_ACT, 0, LOGOUT, SUC_ACT);
        \think\Cache::rm('user_login_'.$this->uid);
        $this->redirect('Login/index');
    }
}
