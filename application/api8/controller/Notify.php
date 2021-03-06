<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\controller;
use app\common\controller\ApiBase;
use app\api8\service\PayService;

/**
 * Class Message
 * @package app\api8\controller
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
      \think\Log::write($_POST,'debug');
      $res = PayService::self()->recharge_callback($_POST);
      if(is_string($res)){
          if($res == 'TRADE_ERROE'){
            // 支付失败，修改订单状态为失败
            $out_trade_no = $_POST['out_trade_no'];
            PayService::self()->recharge_error($out_trade_no);
          }
          \think\Log::write('ERROR','debug');
          die("ERROR");
      }
      \think\Log::write('SUCCESS','debug');
      die("SUCCESS");
    }

    /**
     * 支付宝订单支付回调
     */
    public function alipay_order_callback(){
        // 记录日志信息
        \think\Log::write($_POST,'debug');
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
///////////////////////////////////微信支付相关接口//////////////////////////////////////////////////
    /**
     * 微信支付异步通知接口 充值
     */
    public function wx_recharge_callback(){
      $xmlData = file_get_contents('php://input');
      libxml_disable_entity_loader(true);
      $data = json_decode(json_encode(simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
      // 记录日志信息
      \think\Log::write($data,'debug');
      $res = PayService::self()->wx_recharge_callback($data);
      if($res === true){
          //处理完成之后，告诉微信成功结果
          echo '<xml>
                    <return_code><![CDATA[SUCCESS]]></return_code>
                    <return_msg><![CDATA[OK]]></return_msg>
                </xml>';
          exit();
      }
    }
    /**
     * 微信支付异步通知接口 订单
     */
    public function wx_order_callback(){
      $xmlData = file_get_contents('php://input');
      libxml_disable_entity_loader(true);
      $data = json_decode(json_encode(simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
      // 记录日志信息
      \think\Log::write($data,'debug');
      $res = PayService::self()->wx_order_callback($data);
      if($res === true){
          //处理完成之后，告诉微信成功结果
          echo '<xml>
                    <return_code><![CDATA[SUCCESS]]></return_code>
                    <return_msg><![CDATA[OK]]></return_msg>
                </xml>';
          exit();
      }
    }

}