<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\model;
use think\Db;
use think\Model;

class OrderModel extends Model{
    protected $name = "orders";

    public static function self(){
        return new self();
    }
    /**
     * 我购买的
     */
    public function lists($where = [], $fields = '*', $order_by = 'orders.order_id DESC', $page = 1, $limit = 10, $type='demand', $on_flag = ''){
        if($type == 'service'){
            if ($on_flag == 'sell') {
                $lists = Db::table('orders')
                    ->field($fields)
                    ->join('services','services.id = orders.source_id', 'left')
                    ->join('users u','u.uid = orders.created_uid', 'left')
                    ->join('users_ext ue','orders.created_uid = ue.uid', 'left')
                    ->where($where)->order($order_by)
                    ->page($page, $limit)->select();
            }else{
                $lists = Db::table('orders')
                    ->field($fields)
                    ->join('services','services.id = orders.source_id', 'left')
                    ->join('users u','u.uid = services.created_uid', 'left')
                    ->join('users_ext ue','services.created_uid = ue.uid', 'left')
                    ->where($where)->order($order_by)
                    ->page($page, $limit)->select();
            }
        }else{
            if($on_flag == 'create'){
                $lists = Db::table('orders')
                    ->field($fields)
                    ->join('services','services.id = orders.source_id', 'left')
                    ->join('services_category sc','services.id = sc.source_id', 'left')
                    ->join('users u','u.uid = orders.created_uid', 'left')
                    ->join('users_ext ue','orders.created_uid = ue.uid', 'left')
                    ->where($where)->order($order_by)
                    ->page($page, $limit)->select();
            }else{
                $lists = Db::table('orders')
                    ->field($fields)
                    ->join('services','services.id = orders.source_id', 'left')
                    ->join('services_category sc','services.id = sc.source_id', 'left')
                    ->join('users u','u.uid = services.created_uid', 'left')
                    ->join('users_ext ue','services.created_uid = ue.uid', 'left')
                    ->where($where)->order($order_by)
                    ->page($page, $limit)->select();
            }
        }
        
        return $lists;
    }

    
}