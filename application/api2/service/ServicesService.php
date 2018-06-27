<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\service;

use think\Db;
use think\Config;
use think\Cache;
use app\api2\model\LogsModel;
use app\api2\model\ServicesModel;
use app\api2\model\CollectionsModel;
use app\api2\model\ConfigModel;
use app\api2\service\CommentsService;
use app\api2\service\UserService;
class ServicesService {
    public static function self(){
        return new self();
    }
    /**
     * 发布服务
     */
    public function post_service($param = [], $user = 0){
        $id = isset($param['id']) ? intval($param['id']) : 0;
        if($id){
            $service_id = ServicesModel::self()->post_service($param, $id, $user);
        }else{
            $service_id = ServicesModel::self()->post_service($param, 0, $user);
        }
        $this->clear_cache($service_id);
        return $service_id;
    }
    /**
     * 删除服务
     */
    public function del($ids = [], $user = []){
        if(!$ids){
            return false;
        }
        $where['id'] = ['in', $ids];
        $where['created_uid'] = $user['uid'];
        $save['is_del'] = 1;
        $save['update_at'] = time();
        $res= ServicesModel::self()->where($where)->update($save);
        if(!$res){
            return false;
        }
        // 清除服务缓存
        foreach ($ids as $service_id) {
            $this->clear_cache($service_id);
        }
        // 删除其他信息 如 用户的收藏记录
        //$del_where['source_id'] = ['in', $ids];
        //CollectionsModel::self()->del($del_where);

        return $res;
    }
    /**
     * 关闭服务
     */
    public function close_service($ids = [], $user = []){
        if(!$ids){
            return false;
        }
        $where['id'] = ['in', $ids];
        $where['created_uid'] = $user['uid'];
        $save['status'] = 3;
        $save['update_at'] = time();
        $res= ServicesModel::self()->where($where)->update($save);
        if(!$res){
            return false;
        }
        // 清除服务缓存
        foreach ($ids as $service_id) {
            $this->clear_cache($service_id);
        }
        return $res;
    }
    /**
     * 开启服务
     */
    public function open_service($ids = [], $user = []){
        if(!$ids){
            return false;
        }
        $where['id'] = ['in', $ids];
        $where['created_uid'] = $user['uid'];
        $save['status'] = 1;
        $save['update_at'] = time();
        $res= ServicesModel::self()->where($where)->update($save);
        if(!$res){
            return false;
        }
        // 清除服务缓存
        foreach ($ids as $service_id) {
            $this->clear_cache($service_id);
        }
        return $res;
    }
    /**
     * 发布需求
     */
    public function post_demand($param = [], $user = []){
        $id = isset($param['id']) ? intval($param['id']) : 0;
        if($id){
            $demand_id = ServicesModel::self()->post_demand($param, $id, $user);
        }else{
            $demand_id = ServicesModel::self()->post_demand($param, 0, $user);
        }
        $this->clear_cache($demand_id);
        return $demand_id;
    }
    /**
     * 添加收藏
     */
    public function collect($id = '', $user = []){
        if(!$id){
            return false;
        }
        // 是否已经收藏
        $where['source_id'] = $id;
        $where['uid'] = $user['uid'];
        $check = CollectionsModel::self()->check($where);
        if($check){
            return false;
        }
        // 添加收藏
        $save['source_id'] = $id;
        $save['uid'] = $user['uid'];
        $save['created_at'] = time();
        $res = CollectionsModel::self()->add($save);
        if(!$res){
            return false;
        }
        // 添加收藏标志缓存
        $collect_cache = 'collect_'.$id.'_'.$user['uid'];
        Cache::set($collect_cache, 1);
        return true;
    }
    /**
     * 取消收藏
     */
    public function uncollect($id = '', $user = []){
        $id_arr = array_unique(explode(',', str_replace('，', ',', $id)));
        if(!$id_arr){
            return false;
        }

        $where['source_id'] = ['in', $id_arr];
        $where['uid'] = $user['uid'];
        $res = CollectionsModel::self()->del($where);
        if(!$res){
            return false;
        }
        // 删除收藏标志缓存
        foreach ($id_arr as $source_id) {
            $collect_cache = 'collect_'.$source_id.'_'.$user['uid'];
            Cache::rm($collect_cache);
        }
        return true;
    }

    /**
     * 服务列表
     */
    public function service_lists($param = [], $page = 1, $limit = 10, $user = []){
        // 用户信息
        $user_detail = UserService::self()->get_user_base_info($user['uid']);
        // 排序处理
        $sort = isset($param['sort']) ? $param['sort'] : '';
        $param['type'] = 1;
        $param['status'] = 1;
        // 黑名单过滤
        $blacklist_uids = UserService::self()->blacklist($user['uid']);
        $param['blacklist_uids'] = $blacklist_uids;
        $lists = ServicesModel::self()->lists($param, $page, $limit, $sort, $user_detail);
        // 展示数据处理
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $id) {
                $detail = $this->service_detail($id, $user_detail, false);
                if($detail){
                    $tmp_lists[] = $detail;
                }
            }
        }
        return $tmp_lists;
    }
    /**
     * 我的服务
     */
    public function myservice($uid = '', $current_uid = '',$page = 1, $limit = 10){
        $param['type'] = 1;
        $param['created_uid'] = $uid;
        if($uid != $current_uid){
            $param['status'] = 1;
        }
	    // 用户信息
        $user_detail = UserService::self()->detail($uid);
        $lists = ServicesModel::self()->lists($param, $page, $limit, 0, $user_detail);
        // 展示数据处理
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $id) {
                $detail = $this->service_detail($id, $user_detail, false);
                if($detail){
                    $tmp_lists[] = $detail;
                }
            }
        }
        return $tmp_lists;
    }
    /**
     * 我的邀约
     */
    public function mydemand($uid = '', $current_uid = '', $page = 1, $limit = 10){
        $param['type'] = 2;
        $param['created_uid'] = $uid;
        if($uid != $current_uid){
            $param['status'] = 1;
        }
	    // 用户信息
        $user_detail = UserService::self()->detail($uid);	
        $lists = ServicesModel::self()->demand_lists($param, $page, $limit, 0, $user_detail);
        // 展示数据处理
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $id) {
                $detail = $this->demand_detail($id, $user_detail);
                if($detail){
                    $tmp_lists[] = $detail;
                }
            }
        }
        return $tmp_lists;
    }

    /**
     * 统计数据
     */
    public function lists_count($uid = '', $type = 1){
        if(!$uid){
            return 0;
        }
        $where['created_uid'] = $uid;
        $where['type'] = $type;
        $where['is_del'] = 0;
        $count = ServicesModel::self()->where($where)->count();
        return $count;
    }

    /**
     * 邀约列表
     */
    public function demand_lists($param = [], $page = 1, $limit = 10, $user = []){
        $category_id = $sort = 0;

        if(isset($param['category_id'])){
            $category_id = $param['category_id'];
        }
        $param['type'] = 2;
        $param['status'] = 1;
        // 用户信息
        $user_detail = UserService::self()->detail($user['uid']);
        $sort = isset($param['sort']) ? $param['sort'] : '';
        // 黑名单过滤
        $blacklist_uids = UserService::self()->blacklist($user['uid']);
        $param['blacklist_uids'] = $blacklist_uids;
        
        $lists = ServicesModel::self()->demand_lists($param, $page, $limit, $sort, $user_detail);
        // 展示数据处理
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $id) {
                $detail = $this->demand_detail($id, $user);
                if($detail){
                    $tmp_lists[] = $detail;
                }
            }
        }
        return $tmp_lists;
    }
    
    /**
     * 收藏列表
     */
    public function collect_lists($user = [], $page = 1, $limit = 10){
        $lists = ServicesModel::self()->collect_lists($user['uid'], $page, $limit);
        // 展示数据处理
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $id) {
                $detail = $this->service_detail($id, $user, false);
                if($detail){
                    $tmp_lists[] = $detail;
                }
            }
        }
        return $tmp_lists;
    }
    /**
     * 服务详情展示
     */
    public function service_detail($id = '', $user = '', $flag = true){
        if(!$id){
            return [];
        }
        $cache_name = 'detail_'.$id;
        $tmp_detail = Cache::get($cache_name);
        if(!$tmp_detail){
            $where['services.id'] = $id;
            $where['services.type'] = 1;
            $detail = ServicesModel::self()->detail($where);
            if(!$detail)  return [];       
            // 空闲时间
            $ext_where['source_id'] = $detail['id'];
            $time = Db::table('services_time')->where($ext_where)->select();
            $time_id = array_column($time, 'time_id');
            // 服务范围
            $categorys = Db::table('services_category')->where($ext_where)->select();
            $category_id = array_column($categorys, 'category_id');
            // 处理图片信息
            $attaches = unserialize($detail['attaches']);
            if($attaches){
                foreach ($attaches as $k => $val) {
                    $attaches[$k] = Config::get('img_url').$val;
                }
            }
            // 服务信息
            $tmp_detail['id'] = $detail['id'];
            $tmp_detail['title'] = strDecode($detail['title']);
            $tmp_detail['type'] = $detail['type'];
            $tmp_detail['price'] = $detail['price'];
            $tmp_detail['price_unit'] = $detail['price_unit'];
            $tmp_detail['sounds'] = $detail['sounds'];
            if($detail['sounds']){
                $tmp_detail['sounds'] = Config::get('img_url').$detail['sounds'];
            }
            $tmp_detail['sounds_length'] = $detail['sounds_length'];
            $tmp_detail['intro'] = strDecode($detail['intro']);
            $tmp_detail['attaches'] = $attaches;
            $tmp_detail['time_id'] = $time_id;
            $tmp_detail['category_id'] = $category_id;
            $tmp_detail['parents_category'] = ConfigModel::self()->get_parents_id($category_id['0']);
            $tmp_detail['status'] = $detail['status'];
            $tmp_detail['is_del'] = $detail['is_del'];
            $tmp_detail['created_at'] = $detail['created_at'];
            $tmp_detail['created_uid'] = $detail['created_uid'];
            Cache::set($cache_name, $tmp_detail);
        }
        // 用户信息
        $current_uid = $user ? $user['uid'] : '';
        $created_user = UserService::self()->detail($tmp_detail['created_uid'], $current_uid);
        if($created_user){
            $tmp_detail['uid'] = $tmp_detail['created_uid'];
            $tmp_detail['nick_name'] = $created_user['nick_name'];
            $tmp_detail['sex'] = $created_user['sex'];
            $tmp_detail['birthday'] = $created_user['birthday'];
            $tmp_detail['avatar'] = $created_user['avatar'];
            $tmp_detail['age'] = $created_user['age'];
            $tmp_detail['latitude'] = $created_user['latitude'];
            $tmp_detail['longitude'] = $created_user['longitude'];
            $tmp_detail['distance'] = $created_user['distance'];
        }
        // 距离标识
        if($user){
            // 收藏标识
            $collect_cache = 'collect_'.$id.'_'.$user['uid'];
            $is_collection = Cache::get($collect_cache);
            if(!$is_collection){
                $c_where['source_id'] = $id;
                $c_where['uid'] = $user['uid'];
                $is_collection = Db::table('collections')->where($c_where)->count();
                Cache::set($collect_cache, $is_collection);
            }
            $tmp_detail['collect'] = $is_collection ? 1 : 0;
        }
        // 分享链接
        $tmp_detail['share_links'] = url('Share/service_detail', ['id'=>$id, 'token'=>md5($id.Config::get('public.key'))], true, Config::get('domain'));
        // 评论
        if($flag){
            $services_data = Db::table('services_data')->where(['source_id'=>$id])->find();
            $tmp_detail['comment_count'] = intval($services_data['comment_count']);
            $tmp_detail['comments'] = CommentsService::lists($id, 1, 5);
        }
        return $tmp_detail;
    }

    /**
     * 邀约详情
     */
    public function demand_detail($id = '', $user = ''){
        if(!$id){
            return [];
        }
        $cache_name = 'detail_'.$id;
        $tmp_detail = Cache::get($cache_name);
        if(!$tmp_detail){
            $where['services.id'] = $id;
            $where['services.type'] = 2;
            $detail = ServicesModel::self()->detail($where);
            if(!$detail)  return [];       
            // 服务范围
            $categorys = Db::table('services_category')->where(['source_id'=>$id])->select();
            $category_id = array_column($categorys, 'category_id');
            // 处理图片信息
            $attaches = unserialize($detail['attaches']);
            if($attaches){
                foreach ($attaches as $k => $val) {
                    $attaches[$k] = Config::get('img_url').$val;
                }
            }
            // 服务信息
            $tmp_detail['id'] = $detail['id'];
            $tmp_detail['title'] = strDecode($detail['title']);
            $tmp_detail['type'] = $detail['type'];
            $tmp_detail['sounds'] = $detail['sounds'];
            if($detail['sounds']){
                $tmp_detail['sounds'] = Config::get('img_url').$detail['sounds'];    
            }
            $tmp_detail['sounds_length'] = $detail['sounds_length'];
            
            $tmp_detail['intro'] = strDecode($detail['intro']);
            $tmp_detail['attaches'] = $attaches;
            $tmp_detail['time'] = demandTime($id);
            $tmp_detail['category_id'] = $category_id;
            $tmp_detail['status'] = $detail['status'];
            $tmp_detail['is_del'] = $detail['is_del'];
            $tmp_detail['gender'] = $detail['gender'];
            $tmp_detail['pay_way'] = $detail['pay_way'];
            $tmp_detail['created_at'] = $detail['created_at'];
            $tmp_detail['created_uid'] = $detail['created_uid'];
            // 地址信息
            $address = get_province_name($detail['province_id'], false).' '.get_city_name($detail['city_id']).' '.get_area_name($detail['area_id']).' ';
            $tmp_detail['address'] = trim($address.$detail['address']);
            Cache::set($cache_name, $tmp_detail);
        }
        // 邀约发布用户信息
        $current_uid = $user ? $user['uid'] : '';
        $created_user = UserService::self()->detail($tmp_detail['created_uid'], $current_uid);
        if($created_user){
            $tmp_detail['uid'] = $tmp_detail['created_uid'];
            $tmp_detail['nick_name'] = $created_user['nick_name'];
            $tmp_detail['sex'] = $created_user['sex'];
            $tmp_detail['birthday'] = $created_user['birthday'];
            $tmp_detail['avatar'] = $created_user['avatar'];
            $tmp_detail['age'] = $created_user['age'];
            $tmp_detail['latitude'] = $created_user['latitude'];
            $tmp_detail['longitude'] = $created_user['longitude'];
            $tmp_detail['distance'] = $created_user['distance'];
        }
        // 分享链接
        $tmp_detail['share_links'] = url('Share/demand_detail', ['id'=>$id, 'token'=>md5($id.Config::get('public.key'))], true, Config::get('domain'));
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
     * 清除服务缓存
     */
    public function clear_cache($id = ''){
        $cache_name = 'detail_'.$id;
        Cache::rm($cache_name);
    }

}
