<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api5\service;

use think\Db;
use think\Config;
use think\Cache;
use app\api5\model\ConfigModel;
use app\api5\service\CommentsService;
use app\api5\service\UserService;
use app\api5\service\CircleService;
use app\common\model\CollectionsModel;
use app\common\model\ServiceModel;
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
            $service_id = ServiceModel::self()->post_service($param, $id, $user);
        }else{
            $service_id = ServiceModel::self()->post_service($param, 0, $user);
        }
        $this->clear_cache($service_id);
        return $service_id;
    }
    /**
     * 删除服务
     */
    public function del_service($ids = [], $uid = ''){
        if(!$ids){
            return false;
        }
        $res = ServiceModel::self()->del_service($ids, $uid);
        if(!$res){
            return false;
        }
        // 清除服务缓存
        foreach ($ids as $service_id) {
            $this->clear_cache($service_id);
        }
        return true;
    }
    /**
     * 关闭服务
     */
    public function close_service($ids = [], $uid = ''){
        if(!$ids){
            return false;
        }
        $res = ServiceModel::self()->close_service($ids, $uid);
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
    public function open_service($ids = [], $uid = ''){
        if(!$ids){
            return false;
        }
        $res = ServiceModel::self()->open_service($ids, $uid);
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
            $demand_id = ServiceModel::self()->post_demand($param, $id, $user);
        }else{
            $demand_id = ServiceModel::self()->post_demand($param, 0, $user);
        }
        $this->clear_cache($demand_id);
        return $demand_id;
    }
    /**
     * 添加收藏
     */
    public function collect($id = '', $type = '', $user = []){
        if(!$id){
            return '非法请求，缺少id';
        }
        // 是否已经收藏
        $check = CollectionsModel::self()->check_collect($id, $user['uid'], $type);
        if($check){
            return '不能重复收藏';
        }
        // 添加收藏
        $save['source_id'] = $id;
        $save['type'] = $type;
        $save['uid'] = $user['uid'];
        $save['created_at'] = time();
        $res = CollectionsModel::self()->add($save);
        if(!$res){
            return '收藏失败';
        }
        // 添加收藏标志缓存
        $collect_cache = 'collect_'.$type.'_'.$id.'_'.$user['uid'];
        Cache::set($collect_cache, 1);
        return true;
    }
    /**
     * 取消收藏
     */
    public function uncollect($id = '', $type = '', $user = []){
        $id_arr = array_unique(explode(',', str_replace('，', ',', $id)));
        if(!$id_arr){
            return '非法请求，缺少id';
        }
        // 是否已经收藏
        $check = CollectionsModel::self()->check_collect($id, $user['uid'], $type);
        if(!$check){
            return '您并未收藏';
        }
        $where['source_id'] = ['in', $id_arr];
        $where['type'] = $type;
        $where['uid'] = $user['uid'];
        $res = CollectionsModel::self()->del($where);
        if(!$res){
            return '取消收藏失败';
        }
        // 删除收藏标志缓存
        foreach ($id_arr as $source_id) {
            $collect_cache = 'collect_'.$type.'_'.$source_id.'_'.$user['uid'];
            Cache::rm($collect_cache);
        }
        return true;
    }

    /**
     * 服务列表
     */
    public function service_lists($param = [], $page = 1, $limit = 10){
        // 排序处理
        $param['type'] = 1;
        $param['status'] = 1;
        // 黑名单过滤
        if(isset($param['uid'])){
            $blacklist_uids = UserService::self()->blacklist($param['uid']);
            $param['blacklist_uids'] = $blacklist_uids;
        }
        $lists = ServiceModel::self()->lists_for_api($param, $page, $limit);
        // 展示数据处理
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $id) {
                $detail = $this->service_detail($id, $param['uid'], false);
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
        $lists = ServiceModel::self()->lists_for_api($param, $page, $limit);
        // 展示数据处理
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $id) {
                $detail = $this->service_detail($id, $current_uid, false);
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
        $lists = ServiceModel::self()->demand_lists($param, $page, $limit);
        // 展示数据处理
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $id) {
                $detail = $this->demand_detail($id, $current_uid);
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
        $count = ServiceModel::self()->where($where)->count();
        return $count;
    }

    /**
     * 邀约列表
     */
    public function demand_lists($param = [], $page = 1, $limit = 10){
        $param['type'] = 2;
        $param['status'] = 1;
        // 黑名单过滤
        if(isset($param['uid'])){
            $blacklist_uids = UserService::self()->blacklist($param['uid']);
            $param['blacklist_uids'] = $blacklist_uids;
        }
        $lists = ServiceModel::self()->demand_lists($param, $page, $limit);
        // 展示数据处理
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $id) {
                $detail = $this->demand_detail($id, $param['uid']);
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
    public function collect_lists($type = '', $user = [], $page = 1, $limit = 10){
        // 1：服务；2：邀约；3帖子；4话题；5：动态
        $tmp_lists = [];
        $param['uid'] = $user['uid'];
        switch ($type) {
            case 1:
                $param['type'] = 1;
                $lists = CollectionsModel::self()->lists($param, $page, $limit);
                // 展示数据处理
                if($lists){
                    foreach ($lists as $id) {
                        $detail = $this->service_detail($id, $user['uid'], false);
                        if($detail){
                            $tmp_lists[] = $detail;
                        }
                    }
                }
                break;
            case 2:
                $param['type'] = 2;
                $lists = CollectionsModel::self()->lists($param, $page, $limit);
                // 展示数据处理
                if($lists){
                    foreach ($lists as $id) {
                        $detail = $this->demand_detail($id, $user['uid']);
                        if($detail){
                            $tmp_lists[] = $detail;
                        }
                    }
                }
                break;
            case 3:
                $param['type'] = 3;
                $lists = CollectionsModel::self()->lists($param, $page, $limit);
                // 展示数据处理
                if($lists){
                    foreach ($lists as $id) {
                        $detail = CircleService::self()->posts_detail($id, $user['uid'], 2);
                        if($detail && $detail['type'] == 1){
                            $tmp_lists[] = $detail;
                        }
                    }
                }
                break;
            case 4:
                $param['type'] = 4;
                $lists = CollectionsModel::self()->lists($param, $page, $limit);
                // 展示数据处理
                if($lists){
                    foreach ($lists as $id) {
                        $detail = CircleService::self()->circle_detail($id, $user['uid']);
                        if($detail){
                            $tmp_lists[] = $detail;
                        }
                    }
                }
                break;
            case 5:
                $param['type'] = 5;
                $lists = CollectionsModel::self()->lists($param, $page, $limit);
                // 展示数据处理
                if($lists){
                    foreach ($lists as $id) {
                        $detail = CircleService::self()->posts_detail($id, $user['uid'], 2);
                        if($detail  && $detail['type'] == 2){
                            $tmp_lists[] = $detail;
                        }
                    }
                }
                break;
        }
        
        return $tmp_lists;
    }
    /**
     * 服务详情展示
     */
    public function service_detail($id = '', $uid = '', $flag = true){
        if(!$id){
            return [];
        }
        $tmp_detail = $this->_common_data($id, $uid);
        $tmp_detail['share_links'] = url('Share/service_detail', ['id'=>$id, 'token'=>md5($id.Config::get('public.key'))], true, Config::get('domain'));
        // 评论 列表里不添加评论信息，详情页面添加评论信息
        if($flag){
            $services_data = Db::table('services_data')->where(['source_id'=>$id])->find();
            $tmp_detail['comment_count'] = intval($services_data['comment_count']);
            $tmp_detail['comments'] = CommentsService::lists($id, 1, 5);
        }
        return $tmp_detail;
    }
    /**
     * 邀约 服务 公共信息
     */
    private function _common_data($id = '', $uid = ''){
        $cache_name = 'detail_'.$id;
        $tmp_detail = Cache::get($cache_name);
        if(1){
            $where['services.id'] = $id;
            //$where['services.type'] = 1;
            $detail = ServiceModel::self()->detail($where);
            if(!$detail)  return [];       
            // 处理图片信息
            $attaches = unserialize($detail['attaches']);
            if($attaches){
                foreach ($attaches as $k => $val) {
                    $attaches[$k] = Config::get('img_url').trim($val);//删除多余空格
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
            
            $tmp_detail['parents_category'] = ConfigModel::self()->get_parents_id($detail['category_id']);
            // 沿用旧版数据格式，app大神不愿意改呀
            $tmp_detail['category_id'] = [$detail['category_id']];
            if($detail['type'] == 1){
                $tmp_detail['time_id'] = [$detail['time_type']];
            }else if($detail['type'] == 2){
		        $tmp_detail['time'] = demandTime_new($detail['time_type'], $detail['date_time'], $detail['time_long']);
                // 地址信息
                $address = get_province_name($detail['province_id'], false).' '.get_city_name($detail['city_id']).' '.get_area_name($detail['area_id']).' ';
                $tmp_detail['address'] = trim($address.$detail['address']);
            }

            $tmp_detail['status'] = $detail['status'];
            $tmp_detail['skills'] = array_filter(explode(',', $detail['skills']));
            $tmp_detail['is_online'] = $detail['is_online'];
            $tmp_detail['city_id'] = $detail['city_id'];
            $tmp_detail['city_name'] = get_city_name($detail['city_id']);
            $tmp_detail['is_del'] = $detail['is_del'];
            $tmp_detail['city_id'] = $detail['city_id'];
            $tmp_detail['created_at'] = $detail['created_at'];
            $tmp_detail['created_uid'] = $detail['created_uid'];
            Cache::set($cache_name, $tmp_detail);
        }
        // 收藏标识
        if($uid){
            $collect_cache = 'collect_'.$id.'_'.$uid;
            $is_collection = Cache::get($collect_cache);
            if(!$is_collection){
                $c_where['source_id'] = $id;
                $c_where['uid'] = $uid;
                $is_collection = Db::table('collections')->where($c_where)->count();
                Cache::set($collect_cache, $is_collection);
            }
            $tmp_detail['collect'] = $is_collection ? 1 : 0;
        }
        // 用户信息
        $current_uid = $uid;
        $created_user = UserService::self()->detail($tmp_detail['created_uid'], $current_uid);
        if($created_user){
            $tmp_detail['uid'] = $tmp_detail['created_uid'];
            $tmp_detail['nick_name'] = $created_user['nick_name'];
            $tmp_detail['sex'] = $created_user['sex'];
            $tmp_detail['birthday'] = $created_user['birthday'];
            $tmp_detail['avatar'] = $created_user['avatar'];
            $tmp_detail['age'] = $created_user['age'];
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
        $tmp_detail = $this->_common_data($id, $user);
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
