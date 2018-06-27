<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api3\service;
use app\common\model\AppLogModel;
class LogsService{
    public static function self(){
        return new self();
    }

    /**
     * 添加日志
     */
    public function add($user_id = 0, $user_name = '', $action_type = 4, $source_id = 0, $source_type = '', $is_suc = 0, $remark = ''){
        $save['user_id'] = $user_id;
        $save['user_name'] = strEncode($user_name);
        $save['action_type'] = $action_type;
        $save['source_id'] = $source_id;
        $save['source_type'] = $source_type;
        $save['is_suc'] = $is_suc;
        $save['remark'] = $remark;
        $save['created_time'] = time();
        $save['login_ip'] = get_client_ip();
        AppLogModel::self()->add($save);
    }

}
