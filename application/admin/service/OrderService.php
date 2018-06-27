<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/12/29
 * Time: 11:17
 */

namespace app\admin\service;

use think\Db;
use think\Cache;
use think\Config;
use app\common\model\OrderModel;
use app\common\model\ConfigModel;
class OrderService
{
    public static function self(){
        return new self();
    }
    /**
     * 订单列表
     */
    public function lists($param = [], $page = 1, $limit = 10)
    {
        $lists = OrderModel::self()->lists($param);
        $page = $lists->render();
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $detail) {
//                $detail['title'] = strDecode($detail['title']);
                $tmp_lists[] = $detail;
            }
        }
        $data['page'] = $page;
        $data['lists'] = $tmp_lists;
        $data['count'] = OrderModel::self()->lists_count($param);
        return $data;
    }

    /**
     * @param array $param
     * @param int $page
     * @param int $limit
     * @return mixed
     * 流水明细
     */
    public function running_lists($param = [], $page = 1, $limit = 10)
    {
        $lists = OrderModel::self()->running_lists($param);

        $page = $lists->render();
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $detail) {
                $tmp_lists[] = $detail;
            }
        }
        $data['page'] = $page;
        $data['running_lists'] = $tmp_lists;
        $data['count'] = OrderModel::self()->running_lists_count($param);
        return $data;
    }
}