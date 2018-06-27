<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;

use app\admin\controller\MY_admin;
/**
 * 后台登录首页
 */
class Admin extends MY_admin{
    /**
     * 登录
     */
    public function index(){
        return $this->fetch('index');
    }

}
