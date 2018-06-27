<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\model;

use think\Model;

class ConfigModel extends Model{
    protected $name = "configs";

    public static function self(){
        return new self();
    }

    /**
     * 列表搜索
     */
    public function lists($type = '', $parent = 0){
        $where['parent'] = 0;
        if($type){
            $where['type'] = $type;
        }
        if($parent){
            $where['parent'] = $parent;
        }
        $list = $this->where($where)->order('configs_id ASC')->select();
        return json_decode(json_encode($list), true);
    }

    /**
     * 获取配置信息的key  value 值
     */
    public function get_key_values($type = '', $id = ''){
        if($type && $id){
            return [];
        }

        $where = [];
        if($type){
            $where['type'] = $type;
        }
        if($id){
            $where['configs_id'] = $configs_id;
        }
        $list = $this->where($where)->select();
        $temp_list = [];
        foreach ($list as $k => $val) {
            $temp_list[$val->configs_id] = $val->value;
        }
        return $temp_list;
    }
    /**
     * 获取对应的父级id
     */
    public function get_parents_id($category_id = ''){
        if(!$category_id){
            return 0;
        }
        $cache_name = 'category_parent_'.$category_id;
        $parent = \think\Cache::get($cache_name);
        if(!$parent){
            $res = false;
            $where['configs_id'] = $category_id;
            $count = $this->where($where)->count();
            if($count){
                $res = $this->where($where)->find()->getData();
            }
            $parent = $res ? $res['parent'] : 0;
            \think\Cache::set($cache_name, $parent);
        }
        return $parent;
    }

}