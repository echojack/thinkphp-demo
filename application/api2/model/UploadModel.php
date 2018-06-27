<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\model;

use think\Model;
use think\Image;
use think\File;
class UploadModel extends Model{
    protected $name = "attaches";

    public static function self(){
        return new self();
    }

    /**
     * 上传单个文件
     */
    public function upload($user = [], $file = '', $upload_path = '', $width = 400, $height=400, $thumb = true){
        $image = \think\Image::open($file);
        
        $img_width = $image->width();
        $img_height = $image->height();
        $img_type = $image->type();
        $img_mime = $image->mime();

        if(!in_array($img_type,['jpg', 'jpeg', 'gif', 'png'])){
            return 5001;
        }
        // 图片类型 
        $save_path = 'uploads/avatar/';
        $save_name = md5(time()).$user['uid'].'.'.$img_type;
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
            $a_save['save_path'] = $save_path;
            $a_save['save_name'] = $save_name;
            $a_save['type'] = $img_type;
            $a_save['mime'] = $img_mime;
            $attach_id = $this->insertGetId($a_save);

            $return['attach_id'] = $attach_id;
            $return['save_path'] = $save_path;
            $return['save_name'] = $save_name;
            return $return;
        }
        return false;
    }

    /**
     * 多张图片上传
     */
    public function arr_upload($files = [], $upload_path = '', $width = 400, $height=400, $thumb = true){
        $tmp_attach = [];
        foreach ($files as $k => $file) {
            $image = \think\Image::open($file);
            $img_width = $image->width();
            $img_height = $image->height();

            $img_type = $image->type();
            $img_mime = $image->mime();

            // 图片类型 
            $save_path = 'uploads/images/'.date("Ymd").'/';
            $save_name = md5(time().$k).'.'.$img_type;
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
            // 返回数据信息
            if($result !== false){
                $a_save['save_path'] = $save_path;
                $a_save['save_name'] = $save_name;
                $a_save['type'] = $img_type;
                $a_save['mime'] = $img_mime;
                $attach_id = $this->insertGetId($a_save);

                $return['attach_id'] = $attach_id;
                $return['save_path'] = $save_path;
                $return['save_name'] = $save_name;
                $tmp_attach[$k] = $return;
            }
        }
        return $tmp_attach;
    }

    /**
     * 上传音频文件
     */
    public function upload_sound($files = '', $upload_path = ''){
        $size = $files['size'];
        if($size > 100*1024*1024){
            return '上传文件不能超过100M';
        }
        $file_type = explode('/', $files['type'])['1'];
        // 图片类型 
        $save_path = 'uploads/sounds/';
        $save_name = md5(time()).'.'.$file_type;
        $path_name = $upload_path.$save_path.$save_name;
        // 检测创建目录
        $path = dirname($path_name);
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                return '创建文件目录失败';
            }
        }
        // 移动文件
        if(!move_uploaded_file($files['tmp_name'], $path_name)){
            return '移动文件失败';
        }
        $a_save['save_path'] = $save_path;
        $a_save['save_name'] = $save_name;
        $a_save['type'] = $file_type;
        $a_save['mime'] = $files['type'];
        $attach_id = $this->insertGetId($a_save);
        if(!$attach_id){
            return '数据库写入文件信息失败';
        }
        $return['attach_id'] = $attach_id;
        $return['save_path'] = $save_path;
        $return['save_name'] = $save_name;
        $return['url'] = $save_path.$save_name;
        return $return;
    }

}