<?php
//配置文件
$domain = 'www.zhuomazaima.net';
return [
    // 强制模式
    'url_route_on'  =>  true,
    'url_route_must'  =>  true,
    // 扩展函数文件
    'extra_file_list'        => [ APP_PATH . 'common/helper.php', THINK_PATH . 'helper.php'],
    // 显示错误信息
    'show_error_msg'         => true,
    'exception_handle'       => '\app\common\exception\Http',
    // 上传文件保存位置
    'upload_path'         => ROOT_PATH.'/public/',
    'setting'=>[
        'token' => [
            'token_expire' => 30*24*60*60,//token 过期时间
        ]
    ],
    // 模板替换字符串
    'view_replace_str' => [
        '__static__' => 'https://'.$domain.'/static',
    ],
    // 网站公用设置
    'img_url' => 'https://'.$domain.'/',
    'site_url' => 'https://'.$domain.'/',
    'domain' => $domain,
    // 充值类型配置
    'running_type' => [
        'recharge' => 1,
        'withdraw' => 4,
        'consume' => 2,
        'income' => 5,
        'refund' => 3,
        'freeze' => 6,
        'free' => 7,
        'canceld' => 8,//被取消
        'cancel' => 9,//自己取消
        'order' => 10,//自己取消
        'pay_error' => 11,//支付失败
        'make_up' => 12,//补偿
    ],
    
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
    // 
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
#    // 测试环境启用redis 缓存
#    'cache' => [
#        // 驱动方式
#        'type'   => 'redis',
#        // 服务器地址
#        'host'       => '127.0.0.1',
#    ],
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
    'max_image_size' => 2,
];
