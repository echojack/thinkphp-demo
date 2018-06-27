<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\service;

use think\Db;
use think\Config;
use app\api\model\LogsModel;
use app\api\model\ServicesModel;
use app\api\model\CollectionsModel;
use app\api\model\ConfigModel;

class ServicesService {
    public static function self(){
        return new self();
    }
    /**
     * 发布服务
     */
    public function post_service($param = [], $id = 0){
        $data['title'] = strEncode($param['title']);
        $data['price'] = floatval ($param['price']);
        $data['intro'] = strEncode($param['intro']);
        $data['type'] = 1;
	    $data['status'] = 1;
        $data['attaches'] = serialize($param['attaches']);
        $data['created_uid'] = $param['uid'];
        $data['created_at'] = time();
        // 分类 时间处理
        $categorys = explode(',', str_replace('，', ',', $param['categorys']));
        // $times = explode(',', str_replace('，', ',', $param['times']));
        $times = intval($param['times']);
        
        // 启动事务
        Db::startTrans();
        try{
            $id = ServicesModel::insertGetId($data);
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
            }
            // 空闲时间处理
            if($times){
                $tmp_time = ['source_id'=>$id, 'time_id'=>$times];
                $res = Db::table('services_time')->insert($tmp_time);
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
     * 删除服务
     */
    public function update($save = [], $where = []){
        if(!$save || !$where){
            return false;
        }
        $res= ServicesModel::self()->do_update($save, $where);
        return $res;
    }
    /**
     * 发布需求
     */
    public function post_demand($param = []){
        if(isset($param['province_id'])){
            $data['province_id'] = intval($param['province_id']);
        }
        if(isset($param['city_id'])){
            $data['city_id'] = intval($param['city_id']);
        }
        if(isset($param['area_id'])){
            $data['area_id'] = intval($param['area_id']);
        }

        $data['title'] = strEncode($param['title']);
        $data['address'] = isset($param['address']) ? string($param['address']) : ''; 
        $data['gender'] = intval($param['gender']);
        $data['price'] = floatval($param['price']);
        $data['pay_way'] = intval($param['pay_way']);
        $data['type'] = 2;        
	$data['status'] = 1;
        $data['attaches'] = serialize($param['attaches']);
        $data['intro'] = isset($param['intro'])? strEncode($param['intro']) : '';
        $data['created_uid'] = $param['uid'];
        $data['created_at'] = time();

        $categorys = explode(',', str_replace('，', ',', $param['categorys']));
        
        // 启动事务
        Db::startTrans();
        try{
            $id = ServicesModel::insertGetId($data);
            $ext_where['source_id'] = $id;
            // 删除已存在数据
            Db::table('services_category')->where($ext_where)->delete();
            Db::table('services_time')->where($ext_where)->delete();
            // 批量插入新数据
            $tmp_category = [];
            foreach ($categorys as $category_id) {
                if($category_id){
                    $tmp_category[] = ['source_id'=>$id, 'category_id'=>$category_id];
                }
            }
            if($tmp_category) {
                Db::table('services_category')->insertAll($tmp_category);
            }

            $tmp_time['source_id'] = $id;
            $tmp_time['time_type'] = intval($param['time_type']);
            if(isset($param['date_time'])){
                $tmp_time['date_time'] = strtotime($param['date_time']);
            }
            if(isset($param['time_long'])){
                $tmp_time['time_long'] = floatval($param['time_long']);
            }
            if($tmp_time){
                Db::table('services_time')->insert($tmp_time);
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
     * 查询 数量
     */
    public function collect_count($where = []){
        return CollectionsModel::self()->do_count($where);
    }
    /**
     * 添加收藏
     */
    public function collect($param = []){
        if(!$param){
            return false;
        }
        return CollectionsModel::self()->add($param);
    }
    /**
     * 取消收藏
     */
    public function uncollect($param = []){
        if(!$param){
            return false;
        }
        $id_arr = array_unique(explode(',', str_replace('，', ',', $param['source_id'])));
        $tmp_id = [];
        if($id_arr){
            foreach ($id_arr as $key => $id) {
                if($id){
                    $tmp_id[] = $id;
                }
            }
        }
        $where['source_id'] = ['in', $tmp_id];
        $where['uid'] = $param['uid'];
        $where['type'] = $param['type'];
        return CollectionsModel::self()->del($where);
    }

    /**
     * 服务列表
     */
    public function service_lists($where = [], $page = 1, $limit = 10, $latitude = 0, $longitude = 0){
        $field = 'services.id, services.title,services.type,services.price, services.price_unit,services.intro, services.attaches, services.created_at,st.time_id, u.uid, u.nick_name, ue.sex,ue.birthday,ue.avatar,ue.latitude,ue.longitude';
	$lists = ServicesModel::self()->lists($where, $field, $page, $limit, 'service');
        $tmp_lists = $this->_service_show($lists, $latitude, $longitude);
        return $tmp_lists;
    }
    public function service_lists_count($where = []){
        $count = ServicesModel::self()->where($where)->count();
        return $count;
    }

    /**
     * 邀约列表
     */
    public function demand_lists($where = [], $page = 1, $limit = 10, $latitude = 0, $longitude = 0){
        $field = 'services.id, services.title,services.type,services.price, services.pay_way,services.intro, services.attaches, services.created_at,services.status,st.time_type,st.date_time,st.time_long, u.uid, u.nick_name, ue.sex,ue.birthday,ue.avatar,ue.latitude,ue.longitude, sc.category_id';
        $lists = ServicesModel::self()->lists($where, $field, $page, $limit);
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $key => $value) {
                $attaches = unserialize($value['attaches']);
                foreach ($attaches as $k => $val) {
                    $attaches[$k] = Config::get('img_url').$val;
                }
                $tmp_detail = [];
                $tmp_detail['id'] = $value['id'];
                $tmp_detail['title'] = strDecode($value['title']);
                $tmp_detail['status'] = $value['status'];
                $tmp_detail['type'] = $value['type'];
                $tmp_detail['price'] = $value['price'];
                $tmp_detail['pay_way'] = $value['pay_way'];
                $tmp_detail['time_type'] = $value['time_type'];
                $tmp_detail['date_time'] = $value['date_time'];
                $tmp_detail['time_long'] = $value['time_long'];
                $tmp_detail['intro'] = strDecode($value['intro']);
		//$tmp_detail['intro'] = strDecode($value['intro']);
                $tmp_detail['uid'] = $value['uid'];
                $tmp_detail['nick_name'] = strDecode($value['nick_name']);
                $tmp_detail['sex'] = $value['sex'];
                $tmp_detail['birthday'] = $value['birthday'];
		        $tmp_detail['avatar'] = get_avatar($value['avatar'], $value['sex']);
                $tmp_detail['age'] = get_age($value['birthday']);
                $tmp_detail['attaches'] = $attaches;
                // 计算距离
                $tmp_detail['latitude'] = $value['latitude'];
                $tmp_detail['longitude'] = $value['longitude'];
                $tmp_detail['distance'] = getDistance($latitude, $longitude, $value['latitude'], $value['longitude']);
                $tmp_detail['flag'] = rand(1, 2);
                $tmp_detail['category_icon'] = Config::get('img_url').'static/images/demand/icon'.$value['category_id'].'.png';
                $tmp_detail['created_at'] = tranTime($value['created_at']);
                $tmp_lists[$key] = $tmp_detail;
            }
        }
        return $tmp_lists;
    }
    public function demand_lists_count($where = []){
        $count = ServicesModel::self()->where($where)->count();
        return $count;
    }
    /**
     * 收藏列表
     */
    public function collect_lists($where = [], $page = 1, $limit = 10, $latitude = 0, $longitude = 0){
        // 指定查询字段
        $field = 'services.id, services.title,services.type,services.price, services.price_unit,services.intro, services.attaches,services.is_del, st.time_id, u.uid, u.nick_name, ue.sex,ue.birthday,ue.avatar,ue.latitude,ue.longitude';
        $field .= ',services.created_at';
        $lists = Db::table('collections')
            ->field($field)
            ->join('services','services.id = collections.source_id', 'left')
            ->join('services_time st','services.id = st.source_id', 'left')
            ->join('users u','u.uid = services.created_uid', 'left')
            ->join('users_ext ue','services.created_uid = ue.uid', 'left')
            ->where($where)->order('collections.created_at DESC')
            ->page($page, $limit)->select();
        
        return $this->_service_show($lists);
    }

    /**
     * 服务列表数据处理
     */
    private function _service_show($lists = [], $latitude = 0, $longitude = 0){
        $tmp_lists = [];
        if($lists){
            $time_config = ConfigModel::self()->get_key_values(2);
            foreach ($lists as $key => $value) {
                $attaches = unserialize($value['attaches']);
                foreach ($attaches as $k => $val) {
                    $attaches[$k] = Config::get('img_url').$val;
                }
                $tmp_detail = [];
                $tmp_detail['id'] = $value['id'];
                $tmp_detail['title'] = strDecode($value['title']);
                $tmp_detail['type'] = $value['type'];
                $tmp_detail['price'] = $value['price'];
                $tmp_detail['price_unit'] = $value['price_unit'];
                $tmp_detail['intro'] = strDecode($value['intro']);
                if(isset($value['is_del'])){
                    $tmp_detail['is_del'] = $value['is_del'];
                }
                $tmp_detail['uid'] = $value['uid'];
                $tmp_detail['nick_name'] = strDecode($value['nick_name']);
                $tmp_detail['sex'] = $value['sex'];
                $tmp_detail['birthday'] = $value['birthday'];
                $tmp_detail['age'] = get_age($value['birthday']);
		        $tmp_detail['avatar'] = get_avatar($value['avatar'], $value['sex']);
                $tmp_detail['time_name'] = '';
                if(isset($time_config[$value['time_id']])){
                    $tmp_detail['time_name'] = $time_config[$value['time_id']];
                }
                $tmp_detail['attaches'] = $attaches;
                // 计算距离
                $tmp_detail['latitude'] = $value['latitude'];
                $tmp_detail['longitude'] = $value['longitude'];
                
		        $distance = getDistance($latitude, $longitude, $value['latitude'], $value['longitude']);
                $tmp_detail['distance'] = $distance;

		        $tmp_detail['flag'] = rand(1, 2);
                $tmp_detail['category_icon'] = Config::get('img_url').'static/images/service_icon.png';
                $tmp_detail['created_at'] = tranTime($value['created_at']);
                $tmp_lists[$key] = $tmp_detail;
            }
        }
        return $tmp_lists;
    }
    /**
     * 服务详情展示
     */
    public function service_detail($where = [], $uid = ''){
        if(!$where){
            return [];
        }
        $where['services.type'] = 1;
        $where['services.is_del'] = 0;

        $field = 'services.id,services.title,services.type,services.price, services.price_unit,services.intro, services.attaches, u.uid, u.nick_name,ue.intro as user_intro, ue.sex,ue.birthday,ue.avatar,ue.latitude,ue.longitude';
        $detail = ServicesModel::self()->service_one($where, $field);
        if(!$detail)  return [];
        // 查询当前用户是否收藏
        $c_where['source_id'] = $detail['id'];
        $c_where['uid'] = $uid;
        $is_collection = Db::table('collections')->where($c_where)->count();

        $tmp_detail = [];
        // 空闲时间
        $ext_where['source_id'] = $detail['id'];
        $time = Db::table('services_time')->where($ext_where)->select();
        $time_id = array_column($time, 'time_id');
        // 服务范围
        $categorys = Db::table('services_category')->where($ext_where)->select();
        $category_id = array_column($categorys, 'category_id');
        // 处理图片信息
        $attaches = unserialize($detail['attaches']);
        foreach ($attaches as $k => $val) {
            $attaches[$k] = Config::get('img_url').$val;
        }
        // 收藏标志
        $tmp_detail['id'] = $detail['id'];
        $tmp_detail['title'] = strDecode($detail['title']);
        $tmp_detail['type'] = $detail['type'];
        $tmp_detail['price'] = $detail['price'];
        $tmp_detail['price_unit'] = $detail['price_unit'];
        $tmp_detail['intro'] = strDecode($detail['intro']);
        $tmp_detail['uid'] = $detail['uid'];
        $tmp_detail['nick_name'] = strDecode($detail['nick_name']);
        $tmp_detail['sex'] = $detail['sex'];
        $tmp_detail['birthday'] = $detail['birthday'];
        $tmp_detail['user_intro'] = $detail['user_intro'];
        $tmp_detail['latitude'] = $detail['latitude'];
        $tmp_detail['longitude'] = $detail['longitude'];
        $tmp_detail['collect'] = $is_collection ? 1 : 0;
        $tmp_detail['attaches'] = $attaches;
	    $tmp_detail['avatar'] = get_avatar($detail['avatar'], $detail['sex']);
        $tmp_detail['age'] = get_age($detail['birthday']);
        $tmp_detail['time_id'] = $time_id;
        $tmp_detail['category_id'] = $category_id;
        $tmp_detail['share_links'] = url('Share/service_detail', ['id'=>$detail['id'], 'token'=>md5($detail['id'].Config::get('public.key'))], true, Config::get('domain'));
        return $tmp_detail;
    }
    /**
     * 邀约详情
     */
    public function demand_detail($where = [], $user = []){
        if(!$where){
            return [];
        }
        $where['services.type'] = 2;
        $where['services.is_del'] = 0;
        $field = 'services.id,services.title,services.type,services.price, services.pay_way,services.intro, services.gender,services.attaches, u.uid, u.nick_name, ue.sex,ue.birthday,ue.avatar,ue.latitude,ue.longitude';
        $field .= ',services.province_id, services.city_id, services.area_id, services.address,st.time_type,st.date_time,st.time_long, sc.category_id';
        $detail = ServicesModel::self()->demand_one($where, $field);
        if(!$detail)  return [];
        $tmp_detail = [];
        // 空闲时间
        $ext_where['source_id'] = $detail['id'];
        $time = Db::table('services_time')->where($ext_where)->select();
        $time_id = array_column($time, 'time_id');
        // 服务范围
        $categorys = Db::table('services_category')->where($ext_where)->select();
        $category_id = array_column($categorys, 'category_id');
        // 处理图片信息
        $attaches = unserialize($detail['attaches']);
        foreach ($attaches as $k => $val) {
            $attaches[$k] = Config::get('img_url').$val;
        }
        // 收藏标志
        $tmp_detail['id'] = $detail['id'];
        $tmp_detail['title'] = strDecode($detail['title']);
        $tmp_detail['type'] = $detail['type'];
        $tmp_detail['price'] = $detail['price'];
        $tmp_detail['pay_way'] = $detail['pay_way'];
        $tmp_detail['time_type'] = $detail['time_type'];
        $tmp_detail['date_time'] = $detail['date_time'];
        $tmp_detail['time_long'] = $detail['time_long'];
        $tmp_detail['gender'] = $detail['gender'];
        $tmp_detail['intro'] = strDecode($detail['intro']);
        $tmp_detail['uid'] = $detail['uid'];
        $tmp_detail['nick_name'] = strDecode($detail['nick_name']);
        $tmp_detail['sex'] = $detail['sex'];
        $tmp_detail['birthday'] = $detail['birthday'];
        $tmp_detail['latitude'] = $detail['latitude'];
        $tmp_detail['longitude'] = $detail['longitude'];
        $tmp_detail['attaches'] = $attaches;
        $tmp_detail['age'] = get_age($detail['birthday']);
	    $tmp_detail['avatar'] = get_avatar($detail['avatar'], $detail['sex']);        
        $tmp_detail['time_id'] = $time_id;
        $tmp_detail['category_id'] = $category_id;
        $tmp_detail['category_icon'] = Config::get('img_url').'static/images/demand/icon'.$detail['category_id'].'.png';
        $tmp_detail['share_links'] = url('Share/demand_detail', ['id'=>$detail['id'], 'token'=>md5($detail['id'].Config::get('public.key'))], true, Config::get('domain'));
        // 邀约地址信息
        $tmp_detail['province_id'] = $detail['province_id'];
        $tmp_detail['city_id'] = $detail['city_id'];
        $tmp_detail['area_id'] = $detail['area_id'];
        $address = get_province_name($detail['province_id']).' '.get_city_name($detail['city_id']).' '.get_area_name($detail['area_id']).' ';
        $tmp_detail['address'] = trim($address.$detail['address']);
        // 计算距离
        $latitude = isset($user['latitude']) ? $user['latitude'] : 0;
        $longitude = isset($user['longitude']) ? $user['longitude'] : 0;
        $tmp_detail['distance'] = getDistance($latitude, $longitude, $detail['latitude'], $detail['longitude']);
        return $tmp_detail;
    }

    /**
     * 搜索指定分类的服务id
     */
    public function service_ids($category_id = ''){
        if(!$category_id){
            return [];
        }

        $tmp_ids = [];
        $where['category_id'] = $category_id;
        $list = Db::table('services_category')->where($where)->select();
        if($list){
            foreach ($list as $key => $value) {
                if($value['source_id']){
                    $tmp_ids[] = $value['source_id'];
                }
            }
        }
        return array_unique($tmp_ids);
    }

    /**
     * 检测发布服务的条件
     */
    public function check_post($user = []){
        
    }

}
