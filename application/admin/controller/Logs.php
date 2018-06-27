<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;
use app\admin\controller\MY_admin;
use app\admin\service\LogsService;
/**
 * 日志处理
 */
class Logs extends MY_admin{

    /**
     * 管理日志
     */
    public function admin_logs(){
        $data = LogsService::self()->admin_logs();
        return $this->fetch('admin_logs', $data);
    }

    /**
     * app 日志
     */
    public function app_logs(){
        $data = LogsService::self()->app_logs();
        return $this->fetch('app_logs', $data);
    }
}