<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2017/12/29
 * Time: 11:19
 */

namespace app\common\model;

use think\Db;
use think\Model;
use think\Config;

class OrderModel extends Model
{
    protected $name = 'orders';
    public static function self(){
        return new self();
    }
    /**
     * 订单列表
     */
    public function lists($param = [], $per_page = 10)
    {
        $where = $this->_list_where($param);
        $lists = $this->field('orders.*')
            ->where($where)
            ->order('orders.order_id DESC')
            ->paginate($per_page);

        return $lists;
    }
    /**
     * 流水明细列表
     */
    public function running_lists($param = [], $per_page = 10)
    {
        $where = $this->running_list_where($param);
        $lists = Db::table('running')
            ->where($where)
            ->order('id DESC')
            ->paginate($per_page);
        return $lists;
    }
    /**
     * 搜索条件组装
     */
    private function _list_where($param = []){
        $where['orders.order_id'] = ['neq',0];
        if(!empty($param['key'])){
            $where['orders.order_no'] = $param['key'];
        }
        if(!empty($param['status'])){
            $where['orders.status'] = $param['status'];
        }
        return $where;
    }
    /**
     * 搜索条件组装
     */
    private function running_list_where($param = []){
        $where['id'] = ['neq',0];
        if(!empty($param['key'])){
            $where[''] = $param['key'];
        }
        if(!empty($param['status'])){
            $where[''] = $param['status'];
        }
        return $where;
    }
    /**
     * 列表 统计数据
     */
    public function lists_count($param = []){
        $where = $this->_list_where($param);
        $count = $this->field('orders.*')
            ->where($where)->count();
        return $count;
    }
    /**
     * 列表 统计数据
     */
    public function running_lists_count($param = []){
        $where = $this->running_list_where($param);
        $count = Db::table('running')
            ->where($where)->count();
        return $count;
    }


}