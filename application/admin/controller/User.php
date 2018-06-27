<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;

use app\admin\controller\MY_admin;
use app\admin\service\UserService;
use app\admin\service\PrivilegeService;
use app\admin\service\LogsService;
/**
 * 后台登录首页
 */
class User extends MY_admin{

    /**
     * 管理员列表
     */
    public function admin_lists(){
        $param['is_admin'] = 1;
        $data = UserService::self()->user_lists($param);
        return $this->fetch('admin_lists', $data);
    }
    /**
     * 平台用户列表
     */
    public function user_lists(){
        $param['is_admin'] = 2;
        $data = UserService::self()->user_lists($param);
        return $this->fetch('user_lists', $data);
    }

    /**
     * 添加编辑用户
     */
    public function user_add(){
        $uid = $this->request->param('uid', 0, 'intval');    
        if($this->request->param('inajax')){  
            $res = UserService::self()->user_add($this->request->param(), $uid);
            $remark = $this->request->param();
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, OTHER, ERR_ACT, $remark);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, ADD_ACT, 0, OTHER, SUC_ACT, $remark);
            $this->response([], 1, 'success');
        }
        $detail = UserService::self()->user_detail($uid);
        if(!$detail){
            $detail['nick_name'] = '';
        }
        $detail['uid'] = $uid;
        $detail['role'] = $this->request->param('role', 0, 'intval');   
        return $this->fetch('user_add', $detail);   
    }

    /**
     * 设置、取消管理员
     */
    public function user_admin(){
        $uid = $this->request->param('uid', '', 'intval');
        $is_admin = $this->request->param('is_admin', '', 'intval');
        $res = UserService::self()->user_admin($uid, $is_admin);

        $remark = ['uid'=>$uid, 'is_admin'=>$is_admin];
        if(!$res){
            LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $uid, OTHER, ERR_ACT, $remark);
            $this->wrong(0, '操作失败，请刷新再试');
        }
        LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $uid, OTHER, SUC_ACT, $remark);
        $this->response([], 1, '操作成功');
    }

    /**
     * 用户权限列表
     */
    public function privilege(){
        $uid = $this->request->param('uid', 0, 'intval');        
        if($this->request->param('inajax')){    
            $res = UserService::self()->user_add($this->request->param(), $uid);

            $remark = $this->request->param();
            if($res !== true){
                LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $uid, OTHER, ERR_ACT, $remark);
                $this->wrong(0 , $res);
            }
            LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $uid, OTHER, SUC_ACT, $remark);
            $this->response([], 1, 'success');
        }
        $children = [];
        // 用户信息
        $data['user'] = UserService::self()->user_detail($uid);
        $data['user_group'] = $data['user']['group'];
        $license = unserialize($data['user']['license']);
        $data['user_mod'] = json_encode([]);
        if($license){
            $data['user_mod'] = json_encode(array_column($license, 'm_id'));
            foreach ($license as $k => $val) {
                $children[$val['m_id']] = array_column($val['children'], 'license_id');
            }
        }
        $data['user_license'] = json_encode($children);
        // 角色列表
        $data['group_lists'] = PrivilegeService::self()->group_lists(0);
        // 栏目对应权限
        $data['object_lists'] = PrivilegeService::self()->object_lists(0);
        $data['uid'] = $uid;
        return $this->fetch('privilege', $data); 
    }
    /**
     * 用户权限列表
     */
    public function get_objects(){
        $uid = $this->request->param('uid', 0, 'intval');        
        if($this->request->param('inajax')){    
            $group_id = $this->request->param('group_id', 0, 'intval');       
            $lists = PrivilegeService::self()->get_objects($group_id);
            $this->response($lists, 1, 'success');
        }
        // 用户信息
        $data['user'] = UserService::self()->user_detail($uid);
        // 角色列表
        $data['group_lists'] = PrivilegeService::self()->group_lists(0);
        // 栏目对应权限
        $data['object_lists'] = PrivilegeService::self()->object_lists(0);
        return $this->fetch('privilege', $data); 
    }
    /**
     * 保存用户权限
     */
    public function add_privilege(){
        $license = $_POST['license'];
        if(!$license){
            $this->wrong(0, '给几个权限吧小主');
        }
        $object = $_POST['object'];
        $group_id = $this->request->param('group', '', 'string');   
        $uid = $this->request->param('uid', 0, 'intval');            
        $res = PrivilegeService::self()->add_privilege($uid, $license, $object, $group_id);

        $remark = ['license'=>$license, 'object'=>$object, 'group_id'=>$group_id, 'uid'=>$uid];
        if(!$res){
            LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $uid, OTHER, ERR_ACT, $remark);
            $this->wrong(0, '给几个权限吧小主');
        }
        LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $uid, OTHER, SUC_ACT, $remark);
        $this->response([], 1, 'success');
    }

}
