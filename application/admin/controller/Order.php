<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;

use app\admin\controller\MY_admin;
use app\admin\service\ServiceService;
use app\admin\service\OrderService;
use app\admin\service\LogsService;
use app\admin\service\PushService;
use think\Request;

/**
 * 订单
 */
class Order extends MY_admin{
    /**
     * 订单列表
     */
    public function order_lists(){
        $status = $this->request->param('status', 0, 'intval');
        $key = $this->request->param('key', '', 'string');
        $param['status'] = $status;
        $param['key'] = $key;
        $data = OrderService::self()->lists($param);
        return $this->fetch('lists', $data);
    }
    /**
     * 流水明细
     */
    public function running_lists()
    {
        $status = $this->request->param('status', 0, 'intval');
        $key = $this->request->param('key', '', 'string');
        $param['status'] = $status;
        $param['key'] = $key;
        $data = OrderService::self()->running_lists($param);
        return $this->fetch('running_lists', $data);
    }
}
