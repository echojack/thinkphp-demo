<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Db;
use think\Model;

class PrivilegeModel extends Model{
    protected $name = "configs";

    public static function self(){
        return new self();
    }

    /**
     * 添加权限
     */
    public function license_add($save = [], $license_id = ''){
        if($license_id){
            $save['update_time'] = time();
            return Db::table('admin_license')->where(['license_id'=>$license_id])->update($save);          
        }else{
            return Db::table('admin_license')->insert($save);          
        }
    }

    /**
     * 权限列表
     */
    public function license_lists($param = [], $per_page = 10){
        $where = $this->_list_where($param);
        $lists = Db::table('admin_license')->field('*')
                ->where($where)
                ->order('license_id DESC')
                ->paginate($per_page);
        return $lists;
    }
    /**
     * 权限列表
     */
    public function license_all($where = []){
        $lists = Db::table('admin_license')->field('*')
                ->where($where)
                ->order('license_id DESC')
                ->select();
        return $lists;
    }

    /**
     * 列表 统计数据
     */
    public function lists_count($param = []){
        $where = $this->_list_where($param);
        $count = Db::table('admin_license')->field('*')
                ->where($where)->count();
        return $count;
    }

    /**
     * 搜索条件
     */
    private function _list_where($param = []){
        $where = [];
        if(!empty($param['license_url'])){
            $where['license_url'] = $param['license_url'];
        }
        return $where;
    }
    /**
     * 详情
     */
    public function license_detail($license_id = ''){
        $detail = Db::table('admin_license')->where(['license_id'=>$license_id])->find();
        if(!$detail){
            return [];
        }
        return json_decode(json_encode($detail), true);
    }

    /**
     * 添加权限
     */
    public function object_add($save = [], $m_id = ''){
        if($m_id){
            $save['update_time'] = time();
            return Db::table('admin_object')->where(['m_id'=>$m_id])->update($save);          
        }else{
            return Db::table('admin_object')->insert($save);          
        }
    }
    /**
     * 权限列表
     */
    public function object_lists($param = [], $per_page = 10){
        $where = $this->_object_lists_where($param);
        $lists = Db::table('admin_object')->field('*')
                ->where($where)
                ->order('m_id DESC')
                ->paginate($per_page);
        return $lists;
    }
    /**
     * 权限列表
     */
    public function object_all($where = []){
        $lists = Db::table('admin_object')->field('*')
                ->where($where)
                ->order('m_id DESC')
                ->select();
        return $lists;
    }
    /**
     * 列表 统计数据
     */
    public function object_lists_count($param = []){
        $where = $this->_object_lists_where($param);
        $count = Db::table('admin_object')->field('*')
                ->where($where)->count();
        return $count;
    }
    /**
     * 搜索条件
     */
    private function _object_lists_where($param = []){
        return [];
    }
    /**
     * 详情
     */
    public function object_detail($m_id = ''){
        $detail = Db::table('admin_object')->where(['m_id'=>$m_id])->find();
        if(!$detail){
            return [];
        }
        return json_decode(json_encode($detail), true);
    }


    /**
     * 添加权限
     */
    public function group_add($save = [], $group_id = ''){
        if($group_id){
            $save['update_time'] = time();
            return Db::table('admin_group')->where(['group_id'=>$group_id])->update($save);          
        }else{
            return Db::table('admin_group')->insert($save);          
        }
    }
    /**
     * 权限列表
     */
    public function group_lists($param = [], $per_page = 10){
        $where = $this->_group_lists_where($param);
        $lists = Db::table('admin_group')->field('*')
                ->where($where)
                ->order('group_id DESC')
                ->paginate($per_page);
        return $lists;
    }
    /**
     * 列表 统计数据
     */
    public function group_lists_count($param = []){
        $where = $this->_group_lists_where($param);
        $count = Db::table('admin_group')->field('*')
                ->where($where)->count();
        return $count;
    }
    /**
     * 搜索条件
     */
    private function _group_lists_where($param = []){
        return [];
    }
    /**
     * 详情
     */
    public function group_detail($group_id = ''){
        $detail = Db::table('admin_group')->where(['group_id'=>$group_id])->find();
        if(!$detail){
            return [];
        }
        return json_decode(json_encode($detail), true);
    }
    /**
     * 权限列表
     */
    public function group_all(){
        $lists = Db::table('admin_group')->field('*')
                ->where([])
                ->order('group_id DESC')
                ->select();
        return $lists;
    }
    /**
     * 修改用户权限信息
     */
    public function add_privilege($uid = '', $license = [], $group = ''){
        $save['update_at'] = time();
        $save['license'] = serialize($license);
        $save['group'] = $group;
        return Db::table('users')->where(['uid' => $uid])->update($save);
    }

    /**
     * 模块列表
     */
    public function menu_mod_lists(){
        $lists = Db::table('admin_object')->select();
        $lists = json_decode(json_encode($lists), true);
        $tmp_lists = [];
        foreach ($lists as $k => $val) {
            $tmp_lists[$val['m_id']] = $val;
        }
        return $tmp_lists;
    }
    /**
     * 模块列表
     */
    public function menu_license_lists(){
        $lists = Db::table('admin_license')->select();
        $lists = json_decode(json_encode($lists), true);
        $tmp_lists = [];
        foreach ($lists as $k => $val) {
            $tmp_lists[$val['license_id']] = $val;
        }
        return $tmp_lists;
    }
    /**
     * 删除角色
     */
    public function group_del($group_id){
        return Db::table('admin_group')->where(['group_id'=>$group_id])->delete();      
    }

    /**
     * 删除权限
     */
    public function license_del($license_id){
        return Db::table('admin_license')->where(['license_id'=>$license_id])->delete();      
    }
    /**
     * 删除栏目
     */
    public function object_del($m_id){
        return Db::table('admin_object')->where(['m_id'=>$m_id])->delete();      
    }
}