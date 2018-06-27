<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;

use app\admin\controller\MY_admin;
use app\common\service\UploadService;
/**
 * 后台登录首页
 */
class Upload extends MY_admin{

    /**
     * 上传单张图片
     */
    public function image(){
        $res = UploadService::image(request()->file('file'), 0);
        if(!is_array($res)){
            $this->wrong(0, $res);
        }
        $this->response($res, 1, 'success');
    }

}
