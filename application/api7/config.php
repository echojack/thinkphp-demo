<?php
//配置文件
return [
    // 扩展函数文件
    'extra_file_list'        => [ APP_PATH . 'common/helper.php', THINK_PATH . 'helper.php', THINK_PATH . 'extra_helper' . EXT,],
    // 显示错误信息
    'show_error_msg'         => true,
    'exception_handle'       => '\app\common\exception\Http',
];
