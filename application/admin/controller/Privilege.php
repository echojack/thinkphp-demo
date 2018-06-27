<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;
use app\admin\controller\MY_admin;
use app\admin\service\PrivilegeService;
/**
 * 后台登录首页
 */
class Privilege extends MY_admin{
    /**
     * 权限列表
     */
    public function license(){
        $license_name = $this->request->param('license_name', '', 'string');
        $param['license_name'] = $license_name;
        $data = PrivilegeService::self()->license_lists($param);
        return $this->fetch('license', $data);
    }
    /**
     * 添加权限
     */
    public function license_add(){
        $license_id = $this->request->param('license_id', 0, 'intval');        
        if($this->request->param('inajax')){    
            $res = PrivilegeService::self()->license_add($this->request->param(), $license_id);
            if($res !== true){
                $this->wrong(0 , $res);
            }
            $this->response([], 1, 'success');
        }
        $detail = PrivilegeService::self()->license_detail($license_id);
        return $this->fetch('license_add', $detail);
    }
    /**
     * 栏目列表
     */
    public function object(){
        $license_name = $this->request->param('license_name', '', 'string');
        $param['license_name'] = $license_name;
        $data = PrivilegeService::self()->object_lists($param);
        return $this->fetch('object', $data);
    }
    /**
     * 添加栏目
     */
    public function object_add(){
        $m_id = $this->request->param('m_id', 0, 'intval');        
        if($this->request->param('inajax')){    
            $res = PrivilegeService::self()->object_add($this->request->param(), $m_id);
            if($res !== true){
                $this->wrong(0 , $res);
            }
            $this->response([], 1, 'success');
        }
        $data = PrivilegeService::self()->object_detail($m_id);
        if($data){
            $data['license_ids'] = explode(',', $data['license_ids']);
        }
        // 权限列表
        $data['license'] = PrivilegeService::self()->license_lists(0);
        return $this->fetch('object_add', $data);
    }
    /**
     * 分组列表
     */
    public function group(){
        $group_name = $this->request->param('group_name', '', 'string');
        $param['group_name'] = $group_name;
        $data = PrivilegeService::self()->group_lists($param);
        return $this->fetch('group', $data);
    }
    /**
     * 添加分组
     */
    public function group_add(){
        $group_id = $this->request->param('group_id', 0, 'intval');        
        if($this->request->param('inajax')){    
            $res = PrivilegeService::self()->group_add($this->request->param(), $group_id);
            if($res !== true){
                $this->wrong(0 , $res);
            }
            $this->response([], 1, 'success');
        }
        $data = PrivilegeService::self()->group_detail($group_id);
        if($data){
            $data['object_ids'] = explode(',', $data['object_ids']);
        }
        // 权限列表
        $data['object'] = PrivilegeService::self()->object_lists(0);
        return $this->fetch('group_add', $data);
    }
    /**
     * 删除角色
     */
    public function group_del(){
        $group_id = $this->request->param('group_id', 0, 'intval');
        $res = PrivilegeService::self()->group_del( $group_id);
        if(!$res){
            $this->wrong(0, '删除失败，请刷新再试');
        }
        $this->response([], 1, '删除成功');
    }

    /**
     * 删除权限
     */
    public function license_del(){
        $license_id = $this->request->param('license_id', 0, 'intval');
        $res = PrivilegeService::self()->license_del( $license_id);
        if(!$res){
            $this->wrong(0, '删除失败，请刷新再试');
        }
        $this->response([], 1, '删除成功');
    }
    /**
     * 删除栏目
     */
    public function object_del(){
        $m_id = $this->request->param('m_id', 0, 'intval');
        $res = PrivilegeService::self()->object_del( $m_id);
        if(!$res){
            $this->wrong(0, '删除失败，请刷新再试');
        }
        $this->response([], 1, '删除成功');
    }
}
