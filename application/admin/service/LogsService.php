<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\service;
use app\common\model\AdminLogModel;
class LogsService{
    public static function self(){
        return new self();
    }

    /**
     * 添加日志
     */
    public function add($user_id = 0, $user_name = '', $action_type = 4, $source_id = 0, $source_type = '', $is_suc = 0, $remark = ''){
        $save['user_id'] = $user_id;
        $save['user_name'] = $user_name;
        $save['action_type'] = $action_type;
        $save['source_id'] = $source_id;
        $save['source_type'] = $source_type;
        $save['is_suc'] = $is_suc;
        $save['remark'] = json_encode($remark);
        $save['created_time'] = time();
        $save['login_ip'] = get_client_ip();
        AdminLogModel::self()->add($save);
    }

    /**
     * 日志列表
     */
    public function admin_logs($param = []){
        $lists = AdminLogModel::self()->admin_logs($param);
        $page = $lists->render();
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $detail) {
                // $detail['remark'] = unserialize($detail['remark']);
                $tmp_lists[] = $detail;
            }
        }
        $data['page'] = $page;
        $data['lists'] = $tmp_lists;
        $data['count'] = AdminLogModel::self()->admin_logs_count($param);
        return $data;
    }
    /**
     * 日志列表
     */
    public function app_logs($param = []){
        $lists = AdminLogModel::self()->app_logs($param);
        $page = $lists->render();
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $detail) {
                $detail['user_name'] = strDecode($detail['user_name']);
                $tmp_lists[] = $detail;
            }
        }
        $data['page'] = $page;
        $data['lists'] = $tmp_lists;
        $data['count'] = AdminLogModel::self()->app_logs_count($param);
        return $data;
    }

}
