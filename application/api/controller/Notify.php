<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;
use Exception;
use think\Db;
use think\Config;
use app\common\controller\ApiBase;
use app\api\model\RunningModel;
use app\api\service\PayService;
use app\api\service\OrderService;

/**
 * Class Message
 * @package app\api\controller
 * 支付异步通知
 */
class Notify extends ApiBase
{
    /**
     * 账户充值
     * 支付宝 异步通知
     */
    public function recharge_callback(){
      // 记录日志信息
      \think\Log::write($_POST,'recharge_callback');
      $res = PayService::self()->recharge_callback($_POST);
      if(is_string($res)){
          if($res == 'TRADE_ERROE'){
            // 支付失败，修改订单状态为失败
            $out_trade_no = $_POST['out_trade_no'];
            PayService::self()->recharge_error($out_trade_no);
          }
          die("ERROR");
      }
      die("SUCCESS");
    }

    /**
     * 支付宝订单支付回调
     */
    public function alipay_order_callback(){
        // 记录日志信息
        \think\Log::write($_POST,'alipay_order_callback');
        $res = PayService::self()->alipay_order_callback($_POST);
        if(is_string($res)){
            if($res == 'TRADE_ERROE'){
                // 修改订单状态为支付失败
                $out_trade_no = $_POST['out_trade_no'];
                PayService::self()->alipay_order_error($out_trade_no);
            }
            die("ERROR");
        }
        die("SUCCESS");
    }

    
}