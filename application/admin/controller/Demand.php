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
class Demand extends MY_admin{
    /**
     * 服务分类
     */
    public function category(){
        return $this->fetch('category');
    }

    /**
     * 服务列表
     */
    public function lists(){
        return $this->fetch('lists');
    }


}
