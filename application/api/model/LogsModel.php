<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\model;

use think\Model;

class LogsModel extends Model{
    protected $name = "logs";

    public static function self(){
        return new self();
    }

    /**
     * 添加操作记录
     */
    public function add($source_id ='', $source_mod = '', $source_act = '', $options = '', $uid = ''){
        $data['source_id'] = $source_id;
        $data['source_mod'] = $source_mod;
        $data['source_act'] = $source_act;
        $data['options'] = $options;
        $data['created_at'] = time();
        $data['created_uid'] = $uid;
        return $this->insert($data);
    }

}