<?php
//配置文件
$domain = \think\Config::get('domain');
return [
    'app_debug'              => true,
    'session_expire'         => 30,//天
    // 默认控制器名
    'default_controller'     => 'Admin',
    // 默认操作名
    'default_action'         => 'index',
    // 扩展函数文件
    'extra_file_list'        => [ APP_PATH . 'common/helper.php', THINK_PATH . 'helper.php'],
    // 模板替换字符串
    'view_replace_str' => [
        '__static__' => 'http://'.$domain.'/static_admin',
    ],
    // 显示错误信息
    'show_error_msg'         => true,
    'exception_handle'       => '\app\common\exception\Http',

];
