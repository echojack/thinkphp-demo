<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\service;
use think\Db;
use think\Cache;
use think\Config;
use app\common\model\ServiceModel;
use app\common\model\ConfigModel;
class ServiceService {

    public static function self(){
        return new self();
    }
    /**
     * 服务列表
     */
    public function lists($param = [], $page = 1, $limit = 10){
        $lists = ServiceModel::self()->lists($param);
        $page = $lists->render();
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $detail) {
                $detail['title'] = strDecode($detail['title']);
                $detail['intro'] = strDecode($detail['intro']);
                $tmp_lists[] = $detail;
            }
        }
        $data['page'] = $page;
        $data['lists'] = $tmp_lists;
        $data['count'] = ServiceModel::self()->lists_count($param);
        return $data;
    }

    /**
     * 服务详情
     */
    public function service_detail($id = ''){
        if(!$id){
            return [];
        }
                
        $cache_name = 'detail_'.$id;
        $tmp_detail = Cache::get($cache_name);
        if(!$tmp_detail){
            $where['services.id'] = $id;
            $where['services.type'] = 1;
            $detail = ServiceModel::self()->detail($where);
            if(!$detail)  return [];       
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
            $tmp_detail['time_id'] = [$detail['time_type']];
            $tmp_detail['category_id'] = [$detail['category_id']];
            $tmp_detail['parents_category'] = ConfigModel::self()->get_parents_id($detail['category_id']);
            $tmp_detail['status'] = $detail['status'];
            $tmp_detail['is_del'] = $detail['is_del'];
            $tmp_detail['created_at'] = $detail['created_at'];
            $tmp_detail['created_uid'] = $detail['created_uid'];
            Cache::set($cache_name, $tmp_detail);
        }
        $tmp_detail['time_txt'] = $tmp_detail['categorys'] = '未知';
        // 服务分类
        if(!empty($tmp_detail['category_id'])){
            $config_where['configs_id'] = ['IN', $tmp_detail['category_id']];
            $categorys = Db::table('configs')->where($config_where)->select();
            $tmp_detail['categorys'] = $categorys;
        }
        // 服务时间
        if(empty($tmp_detail['time_id'])){
            $time = Db::table('configs')->where(['configs_id'=>@$tmp_detail['time_id']['0']])->find();
            $tmp_detail['time_txt'] = $time['value'];
        }
        return $tmp_detail;
    }
    /**
     * 审核操作
     */
    public function audit($id, $status){
        $where['id'] = $id;
        $save['status'] = $status;
        $save['update_at'] = time();
        $res = ServiceModel::self()->where($where)->update($save);
        $cache_name = 'detail_'.$id;
        Cache::rm($cache_name);
        return $res;
    }
}
