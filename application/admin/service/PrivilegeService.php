<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\service;
use think\Cache;
use think\Config;
use app\common\model\PrivilegeModel;
class PrivilegeService{
    public static function self(){
        return new self();
    }
    /**
     * 添加权限
     */
    public function license_add($param = [], $license_id = ''){
        $save['license_name'] = trimAll($param['license_name']);
        if(!$save['license_name']){
            return '请输入权限名称';
        }
        $save['license_url'] = trimAll($param['license_url']);
        if(!$save['license_url']){
            return '请输入链接地址';
        }
        // 检测链接明细是否存在
        if(!$license_id){
            $where['license_url'] = $save['license_url'];
            $has = PrivilegeModel::self()->lists_count($where);
            if($has){
                return '权限地址已存在';
            }
        }
        $save['is_true'] = intval($param['is_true']);
        $save['is_show'] = intval($param['is_show']);
        $res = PrivilegeModel::self()->license_add($save, $license_id);
        if(!$res){
            return '网络不给力，请刷新再试';
        }
        Cache::rm('license_lists');
        return true;
    }
    /**
     * 权限列表
     */
    public function license_lists($param = []){
        if($param == 0){
            return PrivilegeModel::self()->license_all($param);
        }
        $lists = PrivilegeModel::self()->license_lists($param);
        $page = $lists->render();
        $data['page'] = $page;
        $data['lists'] = $lists;
        $data['count'] = PrivilegeModel::self()->lists_count($param);
        return $data;
    }

    /**
     * 详情
     */
    public function license_detail($license_id){
        return PrivilegeModel::self()->license_detail($license_id);
    }

    /**
     * 添加权限
     */
    public function object_add($param = [], $m_id = ''){
        $save['mod_name'] = string($param['mod_name']);
        if(!$save['mod_name']){
            return '请输入权限名称';
        }
        // 搜素栏目名称是否存在
        if(isset($param['license']) && $param['license']){
            $save['license_ids'] = implode(',', $param['license']);
        }
        $save['controller'] = string($param['controller']);
        $save['icon'] = string($param['icon']);
        $res = PrivilegeModel::self()->object_add($save, $m_id);
        if(!$res){
            return '网络不给力，请刷新再试';
        }
        Cache::rm('mod_lists');
        return true;
    }
    /**
     * 栏目列表
     */
    public function object_lists($param = []){
        if($param == 0){
            return PrivilegeModel::self()->object_all($param);
        }
        $lists = PrivilegeModel::self()->object_lists($param);
        $page = $lists->render();
        $data['page'] = $page;
        $data['lists'] = $lists;
        $data['count'] = PrivilegeModel::self()->object_lists_count($param);
        return $data;
    }
    /**
     * 详情
     */
    public function object_detail($m_id){
        return PrivilegeModel::self()->object_detail($m_id);
    }

    /**
     * 添加权限
     */
    public function group_add($param = [], $group_id = ''){
        $save['group_name'] = string($param['group_name']);
        if(!$save['group_name']){
            return '请输入分组名称';
        }
        // 搜素栏目名称是否存在
        if(isset($param['object']) && $param['object']){
            $save['object_ids'] = implode(',', $param['object']);
        }
        $res = PrivilegeModel::self()->group_add($save, $group_id);
        if(!$res){
            return '网络不给力，请刷新再试';
        }
        Cache::rm('group_lists');
        Cache::rm('group_name_lists');
        return true;
    }
    /**
     * 栏目列表
     */
    public function group_lists($param = []){
        if($param == 0){
            $lists = Cache::get('group_lists');
            if(!$lists){
                $lists = PrivilegeModel::self()->group_all($param);
            }
            Cache::set('group_lists', $lists);
            return $lists;
        }

        $lists = PrivilegeModel::self()->group_lists($param);
        $page = $lists->render();
        $data['page'] = $page;
        $data['lists'] = $lists;
        $data['count'] = PrivilegeModel::self()->object_lists_count($param);
        return $data;
    }
    /**
     * 详情
     */
    public function group_detail($m_id){
        return PrivilegeModel::self()->group_detail($m_id);
    }
    /**
     * 获取对应角色下的栏目 权限
     */
    public function get_objects($group_id = ''){
        $object_ids = [];
        
        if(!$group_id){
            return [];
        }
        $group = PrivilegeModel::self()->group_detail($group_id);
        if($group){
            $object_ids = explode(',', $group['object_ids']);    
        }
        $where['m_id'] = ['in', $object_ids];
        $lists = PrivilegeModel::self()->object_all($where);
        if($lists){
            foreach ($lists as $k => $val) {
                $l_lists = [];
                $license_ids = array_unique(explode(',', $val['license_ids']));
                if($license_ids){
                    $l_where['license_id'] = ['in', $license_ids];
                    $l_lists = PrivilegeModel::self()->license_all($l_where);
                }
                $val['license'] = $l_lists;
                $lists[$k] = $val;
            }
        }
        return $lists;
    }

    /**
     * 格式化保存用户权限
     */
    public function add_privilege($uid = '', $license = [], $object =[], $group = ''){
        $tmp_lists = [];
        foreach ($license as $m_id) {
            $license = ['m_id'=>$m_id];
            $tmp_object = [];
            if(isset($object[$m_id])){
                foreach ($object[$m_id] as $license_id) {
                    $tmp_object[] = [ 'license_id'=>$license_id ];
                }
            }
            $license['children'] = $tmp_object;
            $tmp_lists[] = $license;
        }
        Cache::rm('user_privilege_'.$uid);
        Cache::rm('user_menu_'.$uid);
        Cache::rm('license_lists');
        Cache::rm('mod_lists');
        return PrivilegeModel::self()->add_privilege($uid, $tmp_lists, $group);
    }

    // 权限缓存
    public function menu_mod_lists(){
        $cache_name = 'mod_lists';
        $mod = Cache::get($cache_name);
        if(!$mod){
            $mod = PrivilegeModel::self()->menu_mod_lists();
            Cache::set($cache_name, $mod);
        }
        return $mod;
    }
    // 权限缓存
    public function menu_license_lists(){
        $cache_name = 'license_lists';
        $mod = Cache::get($cache_name);
        if(!$mod){
            $mod = PrivilegeModel::self()->menu_license_lists();
            Cache::set($cache_name, $mod);
        }
        return $mod;
    }
    /**
     * 删除角色
     */
    public function group_del($group_id){
        return PrivilegeModel::self()->group_del( $group_id);
    }

    /**
     * 删除权限
     */
    public function license_del($license_id){
        return PrivilegeModel::self()->license_del( $license_id);
    }
    /**
     * 删除栏目
     */
    public function object_del($m_id){
        return PrivilegeModel::self()->object_del( $m_id);
    }
}