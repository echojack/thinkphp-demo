<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Db;
use think\Model;

class AdminLogModel extends Model{
    protected $name = "admin_log";

    public static function self(){
        return new self();
    }
    /**
     * 添加圈子
     */
    public function add($save = []){
        if(!$save){
            return false;
        }
        return $this->insertGetId($save);
    }
    /**
     * 获取日志列表
     */
    public function admin_logs($param = [], $per_page = 10){
        $lists = $this->field('*')
                ->where([])
                ->order('admin_log.id DESC')
                ->paginate($per_page);
        return $lists;
    }
    public function admin_logs_count($param = []){
        return $this->field('*')->count();
    }

    /**
     * 获取app日志列表
     */
    public function app_logs($param = [], $per_page = 10){
        $lists = Db::table('app_log')->field('*')
                ->where([])
                ->order('app_log.id DESC')
                ->paginate($per_page);
        return $lists;
    }
    public function app_logs_count($param = []){
        return Db::table('app_log')->field('*')->count();
    }
}
