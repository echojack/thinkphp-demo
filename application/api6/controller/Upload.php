<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\controller;
use think\Db;
use app\common\controller\ApiLogin ;
use app\common\model\UploadModel;
use app\api6\service\LogsService;
/**
 * Class Profile
 * @package app\api6\controller
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
	        // 清除用户缓存
            $cache_key = 'user_'.$this->user['uid'];
            \think\Cache::rm($cache_key);
            // 操作日志记录
            LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, 0, OTHER, SUC_ACT, $u_data);
            $this->response($return, 1, '上传成功');
        }else{
            $this->wrong(0, '上传失败，请刷新再试！');
        }
    }

    public function do_avatar(){
        return $this->fetch('avatar');
    }
    
    public function sound(){
        return $this->fetch('sound');   
    }

    public function do_sound(){
        $attach = UploadModel::self()->upload_sound( $_FILES['sound'], $this->upload_path, 0);
        var_dump($attach);
    }
}
