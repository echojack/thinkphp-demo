<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\controller;

use app\common\controller\ApiBase;
use app\common\controller\ApiLogin ;
use app\api2\model\UploadModel;
use app\common\service\UserService;
use think\Config;
use think\Db;
use think\Cache;

use rongcloud\RongCloud;
use app\api2\service\ServicesService;
use app\api2\service\PushService;
/**
 * Class Profile
 * @package app\api\controller
 * 上传接口
 */
class Test extends ApiBase {

    public function upload(){
        return $this->fetch('sound');
    }

    public function do_sound(){
        $sounds = UploadModel::self()->upload_sound( $_FILES['sound'], $this->upload_path, 0);
        var_dump($sounds);
    }

    public function get_xingzuo(){
        $xg = get_xingzuo($this->param['birthday']);
        var_dump($xg);
    }

    public function sys(){
        $res = PushService::self()->push_follow_msg(100003, 4);
        var_dump($res);
    }

    public function rm_cache(){
        ServicesService::self()->clear_cache(3);
    }


    public function distince(){
        $lat1 = $this->param['lat1'];
        $lng1 = $this->param['lng1'];
        $lat2 = $this->param['lat2'];
        $lng2 = $this->param['lng2'];

        $distince = getDistance($lat1, $lng1, $lat2, $lng2);
        var_dump($distince);
    }

    public function test_images(){
        return $this->fetch('upload');
    }
    /**
     * 图文混排测试
     */
    public function image_text(){
        $config_size = \think\Config::get('max_image_size');
        $max_size = $config_size*1024*1024;

        $sizes = $_FILES['avatar']['size'];
        foreach ($sizes as $size) {
            if($size > $max_size){
                $this->wrong(0, '单张图片上传不能超过'.$config_size.'M');
            }
        }
        $intro = $this->request->param('intro', '', 'string');
        $sounds = UploadModel::self()->arr_upload( request()->file('avatar'), $this->upload_path, 0);
        var_dump($sounds);
    }
}
