<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api3\controller;
use think\Validate;
use app\common\controller\ApiLogin ;
use app\api3\model\UploadModel;
use app\api3\model\FeedbackModel;
use app\api3\model\ReportsModel;
use app\api3\model\BlackListModel;
use app\api3\service\ServicesService;
use app\api3\service\CommentsService;
use app\api3\service\RongCloudService;
/**
 * Class Profile
 * @package app\api3\controller
 * 发布接口
 */
class Post extends ApiLogin {
    private $rule = [
        'title'  =>  'require|max:20',
        'price'  =>  'require|float',
        'intro'  =>  'require',
        'category_id'  =>  'require',
        'time_id'  =>  'require',
        // 'attaches'  =>  'require',
        'time_type'  =>  'require',
        'date_time'  =>  'require',
        'time_long'  =>  'require',
        'province_id'  =>  'require',
        'city_id'  =>  'require',
        'area_id'  =>  'require',
        'address'  =>  'require',
        'gender'  =>  'require',
        'pay_way'  =>  'require',
    ];

    private $message  =   [
        'title.require' => '请输入服务标题',
        'title.max' => '标题不能超过16个字',
        'price.require'     => '请输入服务价格',
        'intro.require'   => '请输入服务介绍',
        'category_id.require'   => '请选择服务分类',
        'time_id.require'   => '请选择空闲时间',
        // 'attaches.require'   => '请上传至少一张图片',
    ];
    private $dmessage  =   [
        'category_id.require'   => '请选择邀约类型',
        'title.require' => '请输入邀约主题',
        'title.max' => '邀约主题不能超过16个字',
        'time_type.require'   => '请选择时间类型',
        'province_id.require'   => '请选择邀约地址省',
        'city_id.require'   => '请选择邀约地址市',
        'area_id.require'   => '请选择邀约地址区',
        'gender.require'   => '请选择邀约对象',
        'pay_way.require'   => '请输买单方式',
        // 'attaches.require'   => '请上传至少一张图片',
    ];
    protected $scene = [
        'service'  =>  ['title','price','intro','category_id','time_id'],
        'demand'  =>  ['category_id','title','time_type', 'province_id', 'city_id', 'area_id', 'gender', 'pay_way'],
    ];


    /**
     * 发布服务
     * @method post
     * @parameter string token 必须
     */
    public function service(){
        $validate = new Validate($this->rule, $this->message);
        $validate->scene('service', $this->scene['service']);
        $result   = $validate->scene('service')->check($this->param);
        if(!$result){
            $this->wrong(0,$validate->getError());
        }
        $param = $this->param;
        $attaches = [];       
        $sounds = ''; 
        // 编辑的时候先处理 原始图片信息
        if(isset($param['id']) && $param['id']){
            if(isset($param['attaches_url'])){
                $attaches_url = json_decode($param['attaches_url'], true);
                foreach ($attaches_url as $val) {
                    if($val){
                        $tmp = explode('uploads', $val);
                        if($tmp && $tmp['1']){
                            $attaches[] = 'uploads'.$tmp['1'];
                        }
                    }
                }
            }
            if(isset($param['sounds_url'])){
                $sounds_url = $param['sounds_url'];
                if($sounds_url){
                    $tmp = explode('uploads', $sounds_url);
                    if($tmp && $tmp['1']){
                        $sounds = 'uploads'.$tmp['1'];
                    }
                }
            }
        }
        // 上传图片信息
        if(request()->file('attaches')){
            $attach = UploadModel::self()->arr_upload( request()->file('attaches'), $this->upload_path, 0);
            if($attach){
                foreach ($attach as $k => $val) {
                    $attaches[] = $val['save_path'].$val['save_name'];
                }
            }
        }
        if(!$attaches){
            $this->wrong(0,'请上传至少一张图片');
        }
        // 上传音频文件
        if(isset($_FILES['sounds']) && $_FILES['sounds']){
            $sounds = UploadModel::self()->upload_sound( $_FILES['sounds'], $this->upload_path, 0);
            if(!is_array($sounds)){
                $this->wrong(0,$sounds);
            }
            $sounds = $sounds ? $sounds['url'] : '';
        }
        $param['attaches'] = $attaches;
        $param['sounds'] = $sounds;
        $param['uid'] = $this->user['uid'];
        $res = ServicesService::self()->post_service($param, $this->user);
        if(!$res){
           $this->wrong(0, '发布失败');
        }
        $this->response(['id'=>$res] , 1, '发布成功');
    }


    /**
     * 删除服务
     * 只能删除自己的服务
     */
    public function del_service(){
        $str_id = $this->request->param('id', 0, 'string');
    	if(!$str_id){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $id_arr = explode(',', str_replace('，', ',', $str_id));
        if(!$id_arr){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $res = ServicesService::self()->del($id_arr, $this->user);
        if(!$res){
            $this->wrong(0, '删除失败');
        }
        $this->response([] , 1, '删除成功');
    }
    /**
     * 关闭服务
     */
    public function close_service(){
        $str_id = $this->request->param('id', 0, 'string');
        if(!$str_id){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $id_arr = explode(',', str_replace('，', ',', $str_id));
        if(!$id_arr){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $res = ServicesService::self()->close_service($id_arr, $this->user);
        if(!$res){
            $this->wrong(0, '关闭失败');
        }
        $this->response([] , 1, '关闭成功');
    }
    /**
     * 开启服务
     */
    public function open_service(){
        $str_id = $this->request->param('id', 0, 'string');
        if(!$str_id){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $id_arr = explode(',', str_replace('，', ',', $str_id));
        if(!$id_arr){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $res = ServicesService::self()->open_service($id_arr, $this->user);
        if(!$res){
            $this->wrong(0, '开启失败');
        }
        $this->response([] , 1, '开启成功，请耐心等待审核');
    }
    /**
     * 发布邀约
     * @method post
     * @parameter string token 必须
     */
    public function demand(){
        $validate = new Validate($this->rule, $this->dmessage);
        $validate->scene('demand', $this->scene['demand']);
        $result   = $validate->scene('demand')->check($this->param);
        if(!$result){
            $this->wrong(0,$validate->getError());
        }
                
        $param = $this->param;
        if($param['time_type'] == 1){
            if(empty($param['date_time'])){
                $this->wrong(0, '请输入指定日期');
            }
        }
        // 上传图片信息
        $attaches = [];
        $sounds = ''; 
        // 编辑的时候先处理 原始图片信息
        if(isset($param['id']) && $param['id']){
            if(isset($param['attaches_url'])){
                $attaches_url = json_decode($param['attaches_url'], true);
                foreach ($attaches_url as $val) {
                    if($val){
                        $tmp = explode('uploads', $val);
                        if($tmp && $tmp['1']){
                            $attaches[] = 'uploads'.$tmp['1'];
                        }
                    }
                }
            }
            if(isset($param['sounds_url'])){
                $sounds_url = $param['sounds_url'];
                if($sounds_url){
                    $tmp = explode('uploads', $sounds_url);
                    if($tmp && $tmp['1']){
                        $sounds = 'uploads'.$tmp['1'];
                    }
                }
            }
        }

        if(request()->file('attaches')){
            $attach = UploadModel::self()->arr_upload( request()->file('attaches'), $this->upload_path, 0);
            if($attach){
                foreach ($attach as $k => $val) {
                    $attaches[] = $val['save_path'].$val['save_name'];
                }
            }
        }
        if(!$attaches){
            $this->wrong(0,'请上传至少一张图片');
        }
        // 上传音频文件
        if(isset($_FILES['sounds']) && $_FILES['sounds']){
            $sounds = UploadModel::self()->upload_sound( $_FILES['sounds'], $this->upload_path, 0);
            if(!is_array($sounds)){
                $this->wrong(0,$sounds);
            }
            $sounds = $sounds ? $sounds['url'] : '';
        }
        $param['attaches'] = $attaches;
        $param['sounds'] = $sounds;
        $param['uid'] = $this->user['uid'];

        $res = ServicesService::self()->post_demand($param, $this->user);
        if(!$res){
           $this->wrong(0, $res);
        }
        $this->response(['id'=>$res] , 1, '发布成功');
    }

    /**
     * 收藏
     */
    public function collection(){
        $id = $this->request->param('id', 0, 'intval');
        if(!$id){
           $this->wrong(0, '缺少参数，非法操作');
        }
        $num = ServicesService::self()->collect($id, $this->user);
        if(!$num){
           $this->wrong(0, '收藏失败');
        }
        $this->response([] , 1, '收藏成功');
    }
    /**
     * 取消收藏，可以一次取消多个
     */
    public function uncollection(){
        $id = $this->request->param('id', 0, 'string');
        if(!$id){
           $this->wrong(0, '收藏失败');
        }
        $num = ServicesService::self()->uncollect($id, $this->user);
        if(!$num){
           $this->wrong(0, '取消失败');
        }
        $this->response([] , 1, '取消成功');
    }

    /**
     * 意见反馈
     */
    public function feedback(){
        $content = $this->request->param('content', '', 'string');
        if(!$content){
            $this->wrong(0, '非法请求，请输入反馈内容');
        }

        $data['content'] = $content;
        $data['uid'] = $this->user['uid'];
        $data['created_at'] = $this->request->time();
        $res = FeedbackModel::self()->add($data);
        if(!$res){
            $this->wrong(0, '提交成功，请稍后再试');
        }
        $this->response([] , 1, '提交成功');
    }

    /**
     * 发布评论
     */
    public function comment(){
        $source_id = $this->request->param('source_id', 0, 'int');
        if(!$source_id){
            $this->wrong(0, '缺少参数，非法操作');
        }
        $content = $this->request->param('content', '', 'string');
        if(!$content){
            $this->wrong(0, '请输入评价内容');
        }
        $star = $this->request->param('star', 1, 'int');
        $is_anonymous = $this->request->param('is_anonymous', 1, 'int');
        $res = CommentsService::add($source_id, $content, $star, $is_anonymous, $this->user);
        if(!$res){
            $this->wrong(0, '评价失败，请稍后再试！');
        }
        $this->response([] , 1, '评价成功');
    }
    /**
     * 删除评论
     */
    public function del_comment(){
        $comment_id = $this->request->param('comment_id', '', 'int');
        if(!$comment_id){
            $this->wrong(0, '缺少参数，非法操作');
        }
        $res = CommentsService::del($comment_id,$this->user);
        if(!$res){
            $this->wrong(0, '删除失败');
        }
        $this->response([] , 1, '删除成功');
    }
    /**
     * 举报
     */
    public function report(){
        $source_id = $this->request->param('source_id', 0, 'int');
        if(!$source_id){
            $this->wrong(0, '请输入举报资源');
        }
        $source_type = $this->request->param('source_type', 1, 'int');

        $configs_id = $this->request->param('configs_id', 0, 'int');
        if(!$configs_id){
            $this->wrong(0, '请选择举报内容');
        }

        $data['source_id'] = $source_id;
        $data['configs_id'] = $configs_id;
        $data['type'] = $source_type;
        $data['created_uid'] = $this->user['uid'];
        $data['created_at'] = time();
        $res = ReportsModel::self()->add($data);
        if(!$res){
            $this->wrong(0, '举报失败，请刷新再试！');
        }
        $this->response([] , 1, '举报成功');
    }
    /**
     * 加入黑名单
     */
    public function blacklist(){
        $uid = $this->request->param('uid', 0, 'int');
        if(!$uid){
            $this->wrong(0, '请输入用户id');
        }
        $data['uid'] = $uid;
        $data['created_uid'] = $this->user['uid'];
        $data['created_at'] = time();
        $res = BlackListModel::self()->add($data);
        if(!$res){
            $this->wrong(0, '添加黑名单失败');
        }
        // 融云加入黑名单
        RongCloudService::self()->addBlacklist($this->user['uid'], $uid);
        $this->response([] , 1, '添加黑名单成功');
    }
    /**
     * 从黑名单中删除
     */
    public function remove_blacklist(){
        $uid = $this->request->param('uid', 0, 'int');
        if(!$uid){
            $this->wrong(0, '请输入用户id');
        }
        $where['uid'] = $uid;
        $data['created_uid'] = $this->user['uid'];
        $res = BlackListModel::self()->del($data);
        if(!$res){
            $this->wrong(0, '移除黑名单失败');
        }
        // 融云加入黑名单
        RongCloudService::self()->removeBlacklist($this->user['uid'], $uid);
        $this->response([] , 1, '移除黑名单成功');
    }

}
