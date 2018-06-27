<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 操作日志记录
define('SERVICE_MOD', 1);
define('ORDER_MOD', 3);
define('USER_MOD', 4);
// 操作类型
define('ADD_ACT', 1);//增加
define('UPD_ACT', 2);//修改
define('DEL_ACT', 3);//删除
define('OTHER_ACT', 4);//其他
// 状态
define('SUC_ACT', 1);//成功
define('ERR_ACT', 2);//失败
// 分类
define('LOGIN', 1);//登录
define('LOGOUT', 2);//退出
define('SERVICE', 3);//服务
define('DEMAND', 4);//邀约
define('CIRCLE', 5);//圈子
define('POSTS', 6);//帖子
define('COMMENT', 7);//评论
define('TOPIC', 8);//评论
define('DONGTAI', 9);//动态
define('ADS', 10);//广告
define('OTHER', 11);//用户
define('ORDER', 12);//订单
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
