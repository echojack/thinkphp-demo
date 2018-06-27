<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api7\controller;
use app\common\controller\ApiBase;
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
     * 用户协议页面
     */
    public function service_agreement()
    {
        return $this->fetch('service_agreement');
    }


}
