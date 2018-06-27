<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api4\model;
use think\Db;
use think\Model;
use app\common\model\ServiceModel;
class OrderModel extends Model{
    protected $name = "orders";

    public static function self(){
        return new self();
    }
    /**
     * 下单
     */
    public function add($data = [], $user = []){
        if(!$data){
            return false;
        }
        // 启动事务
        Db::startTrans();
        try{
            // 存储 服务信息
            $source_id = $data['source_id'];
            $copy_id = ServiceModel::self()->copy_service($source_id);
            if(!$copy_id){
                Db::rollback();
                return false;
            }
            $data['copy_id'] = $copy_id;
            $order_id = $this->insertGetId($data);
            if(!$order_id){
                Db::rollback();
                return false;
            }
            // 提交事务
            Db::commit(); 
            return $order_id;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }
    /**
     * 我购买的
     */
    public function purchase($param = [], $type = 1,$page = 1, $limit = 10){
        $uid = $param['uid'];
        if(!$uid){
            return [];
        }
        if($param['status']){
            $where['orders.status'] = $param['status'];    
        }else{
            $where['orders.status'] = ['neq', 6];    
        }
        $where['orders.source_type'] = $type;
        $where['orders.created_uid'] = $uid;
        $lists = $this->field('orders.order_id')
                    ->join('services','services.id = orders.source_id', 'left')
                    ->where($where)->order('orders.order_id DESC')
                    ->page($page, $limit)->select();
        // echo $this->getLastSql();die();
        $tmp = [];
        if($lists){
            foreach ($lists as $val) {
                $tmp[] = $val['order_id'];
            }
        }
        return $tmp;
    }
    /**
     * 我出售的
     */
    public function sell($param = [], $type = 1,$page = 1, $limit = 10){
        $uid = $param['uid'];
        if(!$uid){
            return [];
        }
        if($param['status']){
            $where['orders.status'] = $param['status'];    
        }else{
            $where['orders.status'] = ['neq', 6];    
        }
        $where['orders.source_type'] = $type;
        $where['services.created_uid'] = $uid;
        $lists = $this->field('orders.order_id')
                    ->join('services','services.id = orders.source_id', 'left')
                    ->where($where)->order('orders.order_id DESC')
                    ->page($page, $limit)->select();
        $tmp = [];
        if($lists){
            foreach ($lists as $val) {
                $tmp[] = $val['order_id'];
            }
        }
        return $tmp;
    }
    /**
     * 删除订单
     * 只有 已完成 和 已取消的订单可以删除
     */
    public function del_order($order_id = '', $user = []){
        if(!$order_id){
            return false;
        }
        $o_where['order_id'] = $order_id;
        $o_where['created_uid'] = $user['uid'];
        $o_where['status'] = ['in', [1,4,5]];
        $save['status'] = 6;
        $save['update_at'] = time();
        return $this->where($o_where)->update($save);
    }
    /**
     * 拒绝订单
     */
    public function reject_order($order_id = '', $user = []){
        if(!$order_id){
            return false;
        }
        $o_where['order_id'] = $order_id;
        $save['status'] = 5;
        $save['update_at'] = time();
        return $this->where($o_where)->update($save);
    }
    /**
     * 同意订单
     */
    public function agree_order($order_id = '', $user = []){
        if(!$order_id){
            return false;
        }
        $o_where['order_id'] = $order_id;
        $save['status'] = 4;
        $save['update_at'] = time();
        return $this->where($o_where)->update($save);
    }

    /**
     * 添加置换订单
     */
    public function post_zh_order($save =  []){
        if(!$save){
            return false;
        }
        $save['created_at'] = time();
        // 启动事务
        Db::startTrans();
        try{
            // 存储 服务信息
            $source_id = $save['source_id'];
            $copy_id = ServiceModel::self()->copy_service($source_id);
            if(!$copy_id){
                Db::rollback();
                return false;
            }
            // 存储 服务信息
            $my_source_id = $save['my_source_id'];
            $copy_id2 = ServiceModel::self()->copy_service($my_source_id);
            if(!$copy_id2){
                Db::rollback();
                return false;
            }
            $save['copy_id'] = $copy_id;
            $save['copy_id2'] = $copy_id2;
            $order_id = $this->insertGetId($save);
            if(!$order_id){
                Db::rollback();
                return false;
            }
            // 提交事务
            Db::commit(); 
            return $order_id;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }
    /**
     * 置换订单列表
     */
    public function my_zh_order($param = [], $page = 1, $limit = 10){
        $uid = $param['uid'];
        $offset = ($page -1)*$limit;

        $sql = 'SELECT
                    orders.*
                FROM
                    orders
                LEFT JOIN services ON services.id = orders.source_id
                WHERE
                    orders.type = 3 
		AND  orders.status != 6 
                AND ( services.created_uid = '.$uid.' OR orders.created_uid = '.$uid.' )';
        $sql .= " ORDER BY orders.order_id DESC";
        $sql .= " LIMIT ".$offset.','.$limit;
        // echo $sql;die();
        $lists = $this->query($sql);
        return $lists;
    }

    /**
     * 检测是否有权限操作 订单
     */
    public function zh_order_deal_check($order_id = '', $curr_uid = ''){
        $sql = " SELECT count(*) as num FROM orders LEFT JOIN services ON services.id = orders.source_id WHERE orders.order_id = ".$order_id;
        $sql .= " AND orders.type =3 AND (orders.created_uid = ".$curr_uid." OR services.created_uid =".$curr_uid.")";
        $res = $this->query($sql);
        return $res['0']['num'];
    }
    

}
