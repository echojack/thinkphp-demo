<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api3\model;

use think\Db;
use think\Model;

class ServicesModel extends Model{
    protected $name = "services";

    public static function self(){
        return new self();
    }
    /**
     * 发布服务
     */
    public function add($data = []){
        if(!$data){
            return false;
        }
        return $this->insertGetId($data);
    }
    /**
     * 列表搜索
     * 分类只针对 邀约有效，服务是多个分类的
     * sort 排序标识 ，暂未添加
     * sort 标识  19：按人气最高；20：按评价最高；21：按价格最低
     */
    public function lists($param = [], $page = 1, $limit = 10, $sort = 'id DESC', $user = []){
        switch ($sort) {
            case 18://距离我最近
                $sort = 'distince ASC';
                break;
            case 19://人气最高
                $sort = 'sd.hot DESC';
                break;
            case 20://评价最高
                $sort = 'sd.avg_star DESC';
                break;
            case 21://低价优先
                $sort = 'services.price ASC';
                break;
            default:
                $sort = 'services.id DESC';
                break;
        }
        // 搜搜数据
        $where['services.is_del'] = 0;
        if(isset($param['key']) && $param['key']){
            $where['from_base64(services.title)'] = ['LIKE', '%'.$param['key'].'%'];
        }
        if(isset($param['type'])){
            $where['services.type'] = $param['type'];
        }
        if(isset($param['status'])){
            $where['services.status'] = $param['status'];
        }
        if(isset($param['created_uid'])){
            $where['services.created_uid'] = $param['created_uid'];
        }
        if(isset($param['sex']) && $param['sex']){
            $where['ue.sex'] = $param['sex'];
        }
        if(isset($param['time_id']) && $param['time_id']){
            $where['st.time_id'] = $param['time_id'];
        }
        if(isset($param['category_id']) && $param['category_id']){
            $where['sc.category_id'] = $param['category_id'];
        }
        // 黑名单数据过滤
        if(isset($param['blacklist_uids']) && $param['blacklist_uids']){
            $where['services.created_uid'] = ['NOT IN', $param['blacklist_uids']];
        }

        // 获取用户经纬度
        $distince = '';
        if(!is_null($user['latitude']) && !is_null($user['longitude'])){
            $distince = $this->distince_field($user['latitude'], $user['longitude']);    
        }else{
            $distince = ', 0 as distince ';
        }
        // $lists = $this->field('services.id'.$distince)
        //         ->join('services_time st','services.id = st.source_id', 'left')
        //         ->join('services_data sd','sd.source_id = services.id', 'left')
        //         ->join('users_ext ue','ue.uid = services.created_uid', 'left')
        //         ->where($where)
        //         ->order($sort)
        //         ->page($page, $limit)->select();
        $lists = Db::table('services_category sc')->field('services.id'.$distince)
                ->join('services','sc.source_id = services.id', 'inner')
                ->join('services_time st','services.id = st.source_id', 'left')
                ->join('services_data sd','sd.source_id = services.id', 'left')
                ->join('users_ext ue','ue.uid = services.created_uid', 'left')
                ->where($where)
                ->group('services.id')
                ->order($sort)
                ->page($page, $limit)->select();
        // echo $this->getLastsql();die();
        return array_column($lists, 'id');
    }
    /**
     * 列表搜索
     * 分类只针对 邀约有效，服务是多个分类的
     * sort 排序标识 ，暂未添加
     * sort 标识  19：按人气最高；20：按评价最高；21：按价格最低
     */
    public function demand_lists($param = [], $page = 1, $limit = 10, $sort = 'id DESC', $user = []){
        switch ($sort) {
            case 27://离我最近
                $sort = 'distince ASC';
                break;
            case 28://最新发布
                $sort = 'services.id DESC';
                break;
            case 29://近期约会
                $sort = 'st.date_time DESC';
                break;
            default:
                $sort = 'services.id DESC';
                break;
        }
        // 搜搜数据
        $where['services.is_del'] = 0;
        if(isset($param['key']) && $param['key']){
            $where['from_base64(services.title)'] = ['LIKE', '%'.$param['key'].'%'];
        }
        if(isset($param['type'])){
            $where['services.type'] = $param['type'];
        }
        if(isset($param['status'])){
            $where['services.status'] = $param['status'];
        }
        if(isset($param['category_id']) && $param['category_id']){
            $where['sc.category_id'] = $param['category_id'];
        }
        if(isset($param['created_uid'])){
            $where['services.created_uid'] = $param['created_uid'];
        }

        if(isset($param['sex']) && $param['sex']){
            $where['ue.sex'] = $param['sex'];
        }
        // 黑名单过滤
        if(isset($param['blacklist_uids']) && $param['blacklist_uids']){
            $where['services.created_uid'] = ['NOT IN', $param['blacklist_uids']];
        }

        // 获取用户经纬度
        $distince = '';
        if(!is_null($user['latitude']) && !is_null($user['longitude'])){
            $distince = $this->distince_field($user['latitude'], $user['longitude']);    
        }
        $lists = $this->field('services.id'.$distince)
                ->join('services_data sd','sd.source_id = services.id', 'left')
                ->join('services_category sc','sc.source_id = services.id', 'left')
                ->join('services_time st','st.source_id = services.id', 'left')
                ->join('users_ext ue','ue.uid = services.created_uid', 'left')
                ->where($where)
                ->order($sort)
                ->page($page, $limit)->select();
        return array_column($lists, 'id');
    }
    /**
     * 距离计算公式
     */
    private function distince_field($latitude = 0, $longitude = 0){
        $distince = ',ROUND(
                        6378.137 * 2 * ASIN(
                            SQRT(
                                POW(
                                    SIN(
                                        (
                                            '.$latitude.' * PI() / 180 - ue.latitude * PI() / 180
                                        ) / 2
                                    ),
                                    2
                                ) + COS('.$latitude.' * PI() / 180) * COS(ue.latitude * PI() / 180) * POW(
                                    SIN(
                                        (
                                            '.$longitude.' * PI() / 180 - ue.longitude * PI() / 180
                                        ) / 2
                                    ),
                                    2
                                )
                            )
                        ),
                        2
                    ) AS distince ';
        return $distince;
    }
    /**
     * 删除
     */
    public function del($id = ''){
        if(!$id){
            return false;
        }
        return $this->where(['id'=>$id])->delete();
    }
    /**
     * 修改服务信息
     */
    public function do_update($save = [], $where = []){
        if(!$save || !$where){
            return false;
        }
        return $this->where($where)->update($save);
    }

    /**
     * 列表搜索
     */
    public function detail($where = [], $fields = '*'){
        $detail = $this->field($fields)
                ->where($where)
                ->find();
        $detail = json_decode(json_encode($detail), true);
        return $detail;
    }

    /**
     * 查询指定数据是否存在
     */
    public function check_exit($where = []){
        if(!$where){
            return false;
        }
        return $this->where($where)->count();
    }

    /**
     * 根据category——id搜索服务id
     * category_id 多个以逗号分隔
     */
    public function get_ids_by_category($category_id = '', $status = 1,$type = 1){
        $tmp_arr = array_unique(explode(',', $category_id));
        if(!$tmp_arr){
            return [];
        }
        $where['services.type'] = $type;
        $where['services.is_del'] = 0;
        $where['services.status'] = $status;
        $where['sc.category_id'] = ['in', $tmp_arr];
        $lists = $this->field('services.id')
                ->join('services_category sc', 'services.id = sc.source_id', 'left')
                ->where($where)
                ->select();
        $tmp_ids = [];
        if($lists){
            foreach ($lists as $key => $value) {
                $tmp_ids[$value['id']] = $value['id'];
            }
        }
        return $tmp_ids;
    }
    /**
     * 根据时间类型筛选服务
     */
    public function get_ids_by_time($time_id = '', $status = 1,$type = 1){
        if(!$time_id){
            return [];
        }
        switch ($time_id) {
            case 15://白天
                $where['st.time_id'] = ['in', [15,17]];
                break;
            case 16://晚上
                $where['st.time_id'] = ['in', [16,17]];
                break;
            case 17://全天
                $where['st.time_id'] = ['in', [15,16,17]];
                break;
        }
        $where['services.type'] = $type;
        $where['services.is_del'] = 0;
        $where['services.status'] = $status;
        $lists = $this->field('services.id')
                ->join('services_time st', 'services.id = st.source_id', 'left')
                ->where($where)
                ->select();
        $tmp_ids = [];
        if($lists){
            foreach ($lists as $key => $value) {
                $tmp_ids[$value['id']] = $value['id'];
            }
        }
        return $tmp_ids;
    }
    /**
     * 发布服务
     * 编辑服务
     */
    public function post_service($param = [], $id = '', $user = []){
        $data['title'] = strEncode($param['title']);
        $data['price'] = floatval ($param['price']);
        $data['price_unit'] = 1;
        $data['intro'] = strEncode($param['intro']);
        $data['type'] = 1;
        $data['attaches'] = isset($param['attaches']) ? serialize($param['attaches']) : '';
        $data['sounds'] = isset($param['sounds']) ? $param['sounds'] : '';
        $data['sounds_length'] = isset($param['sounds_length']) ? $param['sounds_length'] : 0;
        $data['status'] = 2;//编辑过后需要重新审核
        if($id){
            $data['update_at'] = time();
        }else{
            $data['created_uid'] = $user['uid'];
            $data['created_at'] = time();
        }
        // 分类 时间处理
        $categorys = explode(',', str_replace('，', ',', $param['category_id']));
        $time_id = intval($param['time_id']);
        // 启动事务
        Db::startTrans();
        try{
            if($id){
                // 编辑之前存储未编辑时的信息
                $this->copy_service($id);
                $res = $this->where(['id'=>$id])->update($data);
            }else{
                $res = $id = $this->insertGetId($data);
            }
            if(!$res){
                Db::rollback();
                return false;
            }

            $ext_where['source_id'] = $id;
            // 删除已存在数据
            Db::table('services_category')->where($ext_where)->delete();
            Db::table('services_time')->where($ext_where)->delete();
            // 批量插入新数据 分类处理
            $tmp_category = [];
            foreach ($categorys as $category_id) {
                if($category_id){
                    $tmp_category[$category_id] = ['source_id'=>$id, 'category_id'=>$category_id];
                }
            }
            if($tmp_category) {
                $res = Db::table('services_category')->insertAll($tmp_category);
                if(!$res){
                    Db::rollback();
                    return false;
                }    
            }
            // 空闲时间处理
            if($time_id){
                $tmp_time = ['source_id'=>$id, 'time_id'=>$time_id];
                $res = Db::table('services_time')->insert($tmp_time);
                if(!$res){
                    Db::rollback();
                    return false;
                } 
            }
            if($id){
                
            }else{
                // 统计初始数据添加
                $s_data = ['source_id'=>$id, 'comment_count'=>0, 'avg_star'=>0, 'hot'=>0];
                $res = Db::table('services_data')->insert($s_data);
                if(!$res){
                    Db::rollback();
                    return false;
                }
            }
            // 提交事务
            Db::commit(); 
            return $id;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }
    /**
     * 复制邀约服务
     */
    public function copy_service($id = ''){
        $detail = $this->where(['id'=>$id])->find();
        $data = json_decode(json_encode($detail), true);
        $data['source_id'] = $data['id'];
        unset($data['id']);
        unset($data['created_at']);
        unset($data['update_at']);
        $data['created_at'] = time();
        return Db::table('services_copy')->insertGetId($data);
    }
    /**
     * 收藏列表
     */
    public function collect_lists($uid = '', $page = 1, $limit = 10){
        if(!$uid){
            return [];
        }
        $tmp = [];
        $where['c.uid'] = $uid;
        $where['services.is_del'] = 0;
        $lists = $this->field('services.id')
                ->join('collections c','c.source_id = services.id', 'left')
                ->where($where)
                ->order('c.created_at DESC')
                ->page($page, $limit)->select();
        if($lists){
            foreach ($lists as $val) {
                $tmp[] = $val['id'];
            }
        }
        return $tmp;
    }
    /**
     * 发布邀约
     */
    public function post_demand($param = [], $id = '', $user = []){
        // 类型
        $categorys = explode(',', str_replace('，', ',', $param['category_id']));

        $data['title'] = strEncode($param['title']);
        $data['province_id'] = intval($param['province_id']);
        $data['city_id'] = intval($param['city_id']);
        $data['area_id'] = intval($param['area_id']);
        $data['address'] = isset($param['address']) ? string($param['address']) : ''; 
        $data['gender'] = intval($param['gender']);
        $data['pay_way'] = intval($param['pay_way']);
        $data['intro'] = isset($param['intro'])? strEncode($param['intro']) : '';
        $data['attaches'] = serialize($param['attaches']);
        $data['sounds'] = isset($param['sounds']) ? $param['sounds'] : '';
        $data['sounds_length'] = isset($param['sounds_length']) ? $param['sounds_length'] : 0;
        $data['type'] = 2;        
        $data['status'] = 1;//默认审核通过
        // 启动事务
        Db::startTrans();
        try{
            if($id){
                // 编辑之前存储未编辑时的信息
                $this->copy_service($id);
                $data['update_at'] = time();
                $res = $this->where(['id'=>$id])->update($data);
            }else{
                $data['created_uid'] = $param['uid'];
                $data['created_at'] = time();
                $res = $id = $this->insertGetId($data);
            }
            if(!$res){
                Db::rollback();
                return false;
            }
            // 删除已存在数据
            $ext_where['source_id'] = $id;
            Db::table('services_category')->where($ext_where)->delete();
            Db::table('services_time')->where($ext_where)->delete();
            // 批量插入新数据
            $tmp_category = [];
            foreach ($categorys as $category_id) {
                if($category_id){
                    $tmp_category[] = ['source_id'=>$id, 'category_id'=>intval($category_id)];
                }
            }
            if($tmp_category) {
                $res = Db::table('services_category')->insertAll($tmp_category);
                if(!$res){
                    Db::rollback();
                    return false;
                }
            }
            // 邀约时间处理
            $tmp_time['source_id'] = $id;
            // 时间类型  不限时间  平时周末 指定日期
            $tmp_time['time_type'] = intval($param['time_type']);
            if(isset($param['date_time'])){
                $tmp_time['date_time'] = strtotime($param['date_time']);
            }
            // 邀约时长
            if(isset($param['time_long'])){
                $tmp_time['time_long'] = floatval($param['time_long']);
            }

            if($tmp_time){
                $res = Db::table('services_time')->insert($tmp_time);
                if(!$res){
                    Db::rollback();
                    return false;
                }
            }
            // 提交事务
            Db::commit();   
            return $id;
        } catch (\Exception $error) {
            throw $error;
            // 回滚事务
            Db::rollback();
            return false;
        }
    }
    /**
     * 邀约详情
     */
    public function demand_detail($id = ''){
        $where['services.id'] = intval($id);
        $where['services.type'] = 2;
        $where['services.status'] = 1;
        $where['services.is_del'] = 0;
        $demand = $this->field('*')
                ->join('services_data sd','sd.source_id = services.id', 'left')
                ->join('services_category sc','sc.source_id = services.id', 'left')
                ->join('services_time st','st.source_id = services.id', 'left')
                ->where($where)
                ->find();
        return json_decode(json_encode($demand), true);
    }

}
