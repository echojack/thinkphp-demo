<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\model;

use think\Model;
use think\Image;
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
}