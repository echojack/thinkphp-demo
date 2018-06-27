<?php
// +----------------------------------------------------------------------
// | TPR [ Design For Api Develop ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2017 http://hanxv.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axios <axioscros@aliyun.com>
// +----------------------------------------------------------------------

namespace app\common\service;

class CommonService {
    public $uid;
    public $nick_name;
    public $user;

    // public static function self(){
    //     return new self();
    // }
    /**
     * 初始化数据
     */
    public function init_user($uid = '', $nick_name = '', $user = ''){
        $this->uid = $uid;
        $this->nick_name = $nick_name;
        $this->user = $user;
    }

}