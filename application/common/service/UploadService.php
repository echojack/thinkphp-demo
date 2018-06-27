<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\service;

use think\Config;
use think\Cache;

class UploadService {


    public static function image($files = [], $width = 400, $height=400, $thumb = true){
        $image = \think\Image::open($files);
        $img_width = $image->width();
        $img_height = $image->height();
        $img_type = $image->type();
        $img_mime = $image->mime();

        if(!in_array($img_type,['jpg', 'jpeg', 'gif', 'png'])){
            return '文件格式不允许';
        }
        // 图片类型 
        $upload_path = Config::get('upload_path');
        $save_path = 'uploads/images/'.date("Ymd").'/';
        $save_name = md5(time()).'.'.$img_type;
        $path_name = $upload_path.$save_path.$save_name;

        if($img_width ){
            if($img_width > $width){
                $result = $image->thumb($width, $height, 1)->save($path_name);
            }else{
                $result = $image->thumb($width, $height, 2)->save($path_name);
            }
            
        }else{
            $result = $image->save($path_name);
        }
        if($result !== false){
            $return['save_path'] = $save_path;
            $return['save_name'] = $save_name;
            return $return;
        }
        return '上传失败';
    }
    
}
