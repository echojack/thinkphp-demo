<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;

use app\common\controller\ApiLogin ;
use app\api\model\UploadModel;
use think\Config;
use think\Db;

/**
 * Class Profile
 * @package app\api\controller
 * 上传接口
 */
class Upload extends ApiLogin {
    // 上传路径
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 上传用户头像
     * @method post
     * @parameter string token 必须
     */
    public function avatar(){
        $attach = UploadModel::self()->upload( $this->user, request()->file('avatar'), $this->upload_path);
        // $attach = UploadModel::self()->arr_upload( request()->file('avatar'), $this->upload_path, 0);
        if(is_int($attach)){
            switch ($attach) {
                case 5001:
                    $this->wrong(0, '只能上传jpg, jpeg, gif,png格式图片');
                    break;
            }
        }else if(is_array($attach)){
            // 保存用户头像信息
            $u_data['attach_id'] = $attach['attach_id'];
            $u_data['avatar'] = $attach['save_path'].$attach['save_name'];
            $u_data['update_at'] = time();
            Db::table('users_ext')->where(['uid'=>$this->user['uid']])->update($u_data);
            $return['url'] = $this->img_url.$u_data['avatar'];
            $this->response($return, 1, '上传成功');
        }else{
            $this->wrong(0, '上传失败，请刷新再试！');
        }
    }


    public function do_avatar(){
        return $this->fetch('avatar');
    }
    
}