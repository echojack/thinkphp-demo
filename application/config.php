<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
//$domain = 'console.money.cc';
$domain = 'tuozhe.cn/public';
return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    // 应用命名空间
    'app_namespace'          => 'app',
    // 应用调试模式
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展函数文件
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT, THINK_PATH . 'extra_helper' . EXT,],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => 'trim',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'api',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'service',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str'       => [],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                  => [
        // 驱动方式
       'type'   => 'File',
        // 缓存保存目录
       'path'   => CACHE_PATH,
        // 缓存前缀
       'prefix' => '',
        // 缓存有效期 0表示永久缓存
       'expire' => 0,
    ],
     // // 测试环境启用redis 缓存
     // 'cache' => [
     //     // 驱动方式
     //     'type'   => 'redis',
     //     // 服务器地址
     //     'host'       => '127.0.0.1',
     // ],
    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

   'session'                => [
       'id'             => '',
       // SESSION_ID的提交变量,解决flash上传跨域
       'var_session_id' => '',
       // SESSION 前缀
       'prefix'         => 'think',
       // 驱动方式 支持redis memcache memcached
    //    'type'           => 'redis',
	   // 'host'       => '127.0.0.1',
       // 是否自动开启 SESSION
       'auto_start'     => true,
   ],
    // redis session
    // 'session'                => [
    //     // 驱动方式 支持redis memcache memcached
    //     'type'           => 'redis',
    //     // 是否自动开启 SESSION
    //     'auto_start'     => true,
    //     'host'           => '127.0.0.1',
    //     'port'           => 6379,
    //     'password'       =>'',
    //     'select'         => 0,
    //     'expire'         => 3600,
    //     'timeout'        => 0,
    //     'persistent'     => true,
    //     'session_name'   =>'s_zm_',
    // ],


    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 10,
    ],
    // 支付相关配置
    'pay' => [
        'pay_type' => [
            '1' => '微信支付',
            '2' => '支付宝支付',
            '3' => '账户余额支付',
        ],
        'alipay' => [
            'app_id' => '2017070407642435',
            'private_key' => 'MIIEowIBAAKCAQEAn0p1Cr+mK3aTtHXkzcBydU27lfihz2Oz564hD2wTZgxBxgNlmCTLcJ/Gb7pD4QKBVpGzF2OKF/60pvPpnETuH3uq4HKWN2qqBsoTaBHACUgVp+JtRq+A+u6/J0Q3Xny4Ak78tzONf5KNAkt21DCYp7lDlSXFYMJf4k9tlrlzmAJaYnEUVD07G5O5Sc9sZ3SfQHz1Py6C9m/TMCtVJ2PItJzTBJWj8l6okwKTSj7usgOoI6Stp9Jv9hZpiwW8nLhYcAEqvMSdHdGyTT7MRw9KxT5JBLKGfieH6//fB6XOymzslYM1fLLppXZu6ySTiGOana+7P8CbvfDlE4+rVxpQKQIDAQABAoIBACIlkvuP4+5TSAyabUcSJzcwR7M5jm5n4CwdLucgcvQgUoVBOyknUhk9lwticaStpc5KA4tTAkpshot3pC+ksys6loHw7nTIv9Qew5Q+od0bf9DygBx0CQFB5uZjAD+YGtYb2p7nRUEAyIuiY8HO/RqPY4Z4h1xbrrRT9Jkn/jiqieNEm/X2QyezahuPq/mMXGSaBwMAMtpZyCHKSAbsCXMAjntkOvBDegEDc/w46XDCt+s078bChcUxkJsn69XfbNyvx4O5Zfflk96ipwOlRtVgt+O/gQO6wAILZvPmFGZXfmokNOgLvQIanZraohdzI6ogQcxIThuKMY/IoWxb7lUCgYEAyur6DEJl2r2NnHO/gFFIHxleZMgPryYVc2pymxNd/mZfcYgg9pV8GWiwPJ0KOkvDkvvuujKoSepL8dT40oQFsiF1IXKWqp3raHBATY1RmBtWtfxeiDZ16nRIzk81Khcg2VWZxkzM54mSQ4sLY3Z9Cwp3tUQKZNxvfmHeGxj/7KcCgYEAyPXcirE+JWIvstbw3L1fSUTeP3fhxLKobZemJNGQ6HGpwY2bnIj00BAcM4OgExF/SDuJ8gFlLZOvGheHOGG1We/IAEK/G9unwU3emERsQV2wvVqFJGVmXQCN65QkLb1pi2S+y0tdiwy4Id+JzOLrlofw3js7SDZ4CIFdkyPbZq8CgYEAq3A/dmAaweZoYIiCgR0rO+spDyjf53DbqrmCvnZscWV4uejzQKInSShjzbI4U+xy/hoQJgxqlph3NYhj+ShFz6vo1CuGE/x8Sa6dBWiiSUq/xd3E2Hx6v20jnfrZxgfoXvathxaX//8BLkOpiY0wNEXRwboMtg5vvG1fQ7Gpd/0CgYB+i7wOJiChP9wTfSB9kE6Rf+mIBADKcUp4gJdh9gmPJgwk0vxbrS6kWpC3q7pAZ7NEFCIAn/pLogUQpCJFUdn2QXUrHNzlOQPBSTzTm7qjytDB3F+dFLFJ/VBhOY8ysmTlH1K6B8JnDmJhCjfnKjn6N65o8tmY1pvtlzEKt/iwBwKBgApGwsgstz9JreY+T9aY3brH/07Ybbuoi9fuMqSqGOfnUhtWeIdEjeQd0bhu8ZmVUKJ0rYXK4SZYl5rkEFiqk/D+iNt9tD2k8mhWl8CHflxKsS2foCkf11JLa6FrjUfbixKKEfYrVoLe6sXb8KboVOQPsEf5voypvLJ+Rr2SKJ8c',
            'public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAxe+XGzWmSHQtjUa4i05J2hGL5yfJQuCaPf722w72GMdzDk9ZLJ3bHxdX8VMCrz2uxMITzZnqn5acjDhNHvrLWZA12NPyPhkSZ/visHNWkc56VmThIyfVs7LCJVWOgMYNPh1aGulfTCdnsel3F//Cn7WpGybzXpe+PvgifkjEjKtFdYfda2/TgYC+KZ8Izmk6UB30FahWBwbZpa23o8EuQWFO8uPCk99HN9G1tg5qrc9mFk8EdFM8zn3g6e704DNsykobV7RdvDgjjp2x1r8AvPrklVfoBsSZZDgkyuE+dtTUefopcJm3U0etUZ9y1VNCF32mOWwGtmn+grAtuqresQIDAQAB',
            'seller_email' => 'zhuomazaime@163.com',
        ],
        'wx' => [
            'app_id' => '',
            'mch_id' => '',
            'key' => '',
            'app_secret' => '',
        ]
    ],
    // 公共接口访问加密串
    'public' =>[
        'key' => 'etwyrtwr',
    ],
    // 百分比
    'payment' => [
        'service_fund_ratio' => 5,//服务保证金百分比
        'demand_fund_ratio' => 5,//邀约保证金百分比
        'withdrawal_ratio' => 5,//提现百分比
        'penalty_platform_ratio' => 50,//违约金平台 分成百分比
    ],
    // 荣云信息配置
    'rongcloud'  => [
        'appKey' =>'pwe86ga5phm16',
        'appSecret' =>'S7yJ7RS0NKKP',
        'sysUserId' =>1,
        'serviceUserId' =>2,
        'demandUserId' =>3,
    ],
    // 云之讯短信配置
    'ucpass'  => [
        'accountSid' =>'c6d9032cf3459cde6369fc2d7066673c',//Account Sid
        'token' =>'61b3a8440fe10cd4233dfb0b2d8a87a1',//Auth Token
        'appId' =>'9c8a56ecf9544d9cbfdb8f32e661347e',//应用appid
        'templateId' =>'112201',//短信模板id
    ],
    'max_image_size' => 10,
    // 用户信息简单配置 1 男 2 女 0 保密
    'user' => [
        'avatar0' => 'https://www.zhuomazaima.net/static/images/avatar/avatar0.png',
        'avatar1' => 'https://www.zhuomazaima.net/static/images/avatar/avatar1.png',
        'avatar2' => 'https://www.zhuomazaima.net/static/images/avatar/avatar2.png',
    ],
    'user_bg' => [
        'bg1' => 'https://www.zhuomazaima.net/static/images/bg/bg1.png',
        'bg2' => 'https://www.zhuomazaima.net/static/images/bg/bg2.png',
        'bg3' => 'https://www.zhuomazaima.net/static/images/bg/bg3.png',
        'bg4' => 'https://www.zhuomazaima.net/static/images/bg/bg4.png',
    ],
    // 上传文件保存位置
    'upload_path'         => ROOT_PATH.'/public/',
    'setting'=>[
        'token' => [
            'token_expire' => 30*24*60*60,//token 过期时间
        ]
    ],
    // 模板替换字符串
    'view_replace_str' => [
        '__static__' => 'http://'.$domain.'/static',
    ],
    // 网站公用设置
    'img_url' => 'http://'.$domain.'/',
    'site_url' => 'http://'.$domain.'/',
    'domain' => $domain,
    // 充值类型配置
    'running_type' => [
        'recharge' => 1, 'withdraw' => 4, 'consume' => 2, 'income' => 5,
        'refund' => 3, 'freeze' => 6, 'free' => 7, 'canceld' => 8,//被取消
        'cancel' => 9,//自己取消
        'order' => 10,//自己取消
        'pay_error' => 11,//支付失败
        'make_up' => 12,//补偿
    ],
    'version' => 1.7,
];
