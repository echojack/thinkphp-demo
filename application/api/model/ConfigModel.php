<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\model;

use think\Model;

class ConfigModel extends Model{
    protected $name = "config";

    public static function self(){
        return new self();
    }
    public function findOne($id=0,$field = ''){
        if($id){
            $this->where('id',$id);
        }
        if(!empty($field)){
            $this->field($field);
        }
        return $this->find()->getData();
    }

    /**
     * 配置列表
     */
    public function findAll($type = 0, $field = ''){
        if($type){
            $this->where('type',$type);
        }
        if(!empty($field)){
            $this->field($field);
        }
        $obj = $this->select();
        $result = json_decode(json_encode($obj),true);
        return $result;
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
            $where['id'] = $id;
        }
        $list = $this->where($where)->select();
        $temp_list = [];
        foreach ($list as $k => $val) {
            $temp_list[$val->id] = $val->value;
        }
        return $temp_list;
    }

}