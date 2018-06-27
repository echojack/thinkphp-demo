<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/8/22
 */
namespace app\admin\controller;
use think\Db;
use app\admin\service\LoginService;
use app\admin\service\PrivilegeService;
use app\admin\controller\AdminBase;
/**
 * 后台登录首页
 */
class MY_admin extends AdminBase{

    public $uid;
    public $nick_name;
    public $user;
    public $menu;

    public function __construct(){
        parent::__construct();
        $this->is_login();
    }

    /**
     * 检测用户是否登录
     */
    public function is_login(){        
        $user = LoginService::checkLogin();
        if(!$user){
            $this->redirect('Login/index');
        }

        $action = $this->request->controller().'/'.$this->request->action();
        if($action != 'Logout/index'){
            // 初始化用户权限
            $this->get_user_power($user['uid']);
            $has = check_privilege($user['uid'], $action);
            if(!$has){
               // $this->redirect('Admin/index');
               $this->wrong(0, '权限不足，请联系管理员');
            }    
        }
        
        $this->uid = $user['uid'];
        $this->nick_name = $user['nick_name'];
        $this->user = $user;
        $this->assign('uid', $this->uid);
        $this->assign('nick_name', $this->nick_name);
        $this->assign('user', $this->user);
        $this->assign('controller', $this->request->controller());
        $this->assign('action', $action);
    }

    /**
     * 获取用户权限列表
     */
    private function get_user_power($uid){
        $user_license_url = [];
        
        $license = \think\Cache::get('user_menu_'.$uid);
        if(!$license){
            $license = Db::table('users')->where(['uid'=>$uid])->field('license')->find();
            $license = unserialize($license['license']);
            // 
            $mod_lists = PrivilegeService::self()->menu_mod_lists();
            $license_lists = PrivilegeService::self()->menu_license_lists();

            // 用户所有的权限
            if($license){
                foreach ($license as $k => $val) {
                    $license[$k] = $mod_lists[$val['m_id']];
                    foreach ($val['children'] as $v) {
                        if(isset($license_lists[$v['license_id']])){
                            $license[$k]['children'][] = $license_lists[$v['license_id']];
                        }else{
                            $license[$k]['children'][] = [];
                        }
                    }
                    if(isset($license[$k]['children'])){
                        $user_license_url = array_merge($user_license_url, array_column($license[$k]['children'], 'license_url'));
                    }
                }
            }
            \think\Cache::set('user_menu_'.$uid, $license);
        }
        
        $this->menu = $license;
        $this->init_user_license_url($uid, $user_license_url);
        $this->assign('menu', $this->menu);
    }
    /**
     * 检测用户是否有对应的权限
     */
    public function init_user_license_url($uid, $license_lists = []){
        $user_license_url = \think\Cache::get('user_privilege_'.$uid);
        if(!$user_license_url){
            $user_license_url = $license_lists;
            \think\Cache::set('user_privilege_'.$uid, $license_lists);
        }
    }

    
}
