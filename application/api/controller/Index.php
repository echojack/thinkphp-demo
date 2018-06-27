<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;

use app\common\controller\ApiBase;

/**
 * Class Message
 * @package app\api\controller
 * 首页 未登录用户展示接口
 */
class Index extends ApiBase
{
    /**
     * 用户协议页面
     */
    public function user_agreement()
    {
        return $this->fetch('user_agreement');
    }

    /**
     * 支持与帮助
     */
    public function help()
    {
        return $this->fetch('help');
    }

    /**
     * 支持与帮助
     */
    public function service()
    {
        return $this->fetch('index');
    }
}
