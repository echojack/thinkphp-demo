<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api4\controller;

use app\common\controller\ApiBase;

/**
 * Class Message
 * @package app\api4\controller
 * 授权相关接口
 */
class Oauth extends ApiBase
{
    /**
     * 授权回调页
     */
    public function wb_callback()
    {
        echo "SUCCESS";
    }
    /**
     * 取消授权回调页
     */
    public function cancel_wb_callback()
    {
        echo "SUCCESS";
    }

    /**
     * 支付宝授权回调地址
     */
    public function alipay_callback(){
        echo "SUCCESS";
    }
    /**
     * 支付宝应用网关
     */
    public function alipay_gateway(){
        echo "SUCCESS";
    }   
}
