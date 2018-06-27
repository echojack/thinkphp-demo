<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;
use Exception;
use think\Validate;
use app\common\controller\ApiLogin ;
use app\api\model\UploadModel;
use app\api\model\FeedbackModel;
use app\api\service\ServicesService;
/**
 * Class Profile
 * @package app\api\controller
 * 发布接口
 */
class Post extends ApiLogin {
    private $rule = [
        'title'  =>  'require|max:20',
        'price'  =>  'require|float',
        'intro'  =>  'require',
        'categorys'  =>  'require',
        'times'  =>  'require',
        'attaches'  =>  'require',
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
        'title.max' => '标题不能超过20个字',
        'price.require'     => '请输入服务价格',
        'intro.require'   => '请输入服务介绍',
        'categorys.require'   => '请选择服务分类',
        'times.require'   => '请选择空闲时间',
        'attaches.require'   => '请上传至少一张图片',
    ];
    private $dmessage  =   [
        'categorys.require'   => '请选择邀约类型',
        'title.require' => '请输入邀约主题',
        'title.max' => '邀约主题不能超过20个字',
        'time_type.require'   => '请选择时间类型',
        'province_id.require'   => '请选择邀约地址省',
        'city_id.require'   => '请选择邀约地址市',
        'area_id.require'   => '请选择邀约地址区',
        'gender.require'   => '请选择邀约对象',
        'pay_way.require'   => '请输买单方式',
        'price.require'     => '请输入赏金',
        'attaches.require'   => '请上传至少一张图片',
    ];
    protected $scene = [
        'service'  =>  ['title','price','intro','categorys','times'],
        'demand'  =>  ['categorys','title','time_type', 'gender', 'pay_way', 'price', 'province_id', 'city_id', 'area_id'],
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
        // 上传图片信息
        $attaches = [];
        if(request()->file('attaches')){
            $attach = UploadModel::self()->arr_upload( request()->file('attaches'), $this->upload_path, 0);
            if($attach){
                foreach ($attach as $k => $val) {
                    $attaches[] = $val['save_path'].$val['save_name'];
                }
            }
        }else{
            $this->wrong(0,'请上传至少一张图片');
        }

        $param['attaches'] = $attaches;
        $param['uid'] = $this->user['uid'];
        $res = ServicesService::self()->post_service($param);
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
        // 只能删除自己发布的信息
        $where['id'] = ['in', $id_arr];
        $where['created_uid'] = $this->user['uid'];
        // 修改状态时间
        $save['is_del'] = 1;
        $save['update_at'] = time();
        $res = ServicesService::self()->update($save, $where);
        if(!$res){
            $this->wrong(0, '删除失败');
        }
        $this->response([] , 1, '删除成功');
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
        // 上传图片信息
        $attaches = [];
        if(request()->file('attaches')){
            $attach = UploadModel::self()->arr_upload( request()->file('attaches'), $this->upload_path, 0);
            if($attach){
                foreach ($attach as $k => $val) {
                    $attaches[] = $val['save_path'].$val['save_name'];
                }
            }
        }else{
            $this->wrong(0,'请上传至少一张图片');
        }

        $param['attaches'] = $attaches;
        $param['uid'] = $this->user['uid'];
        $res = ServicesService::self()->post_demand($param);
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
           $this->wrong(0, '收藏失败');
        }
        $type = $this->request->param('type', 0, 'intval');
        if(!$type){
           $this->wrong(0, '非法请求，缺少参数');
        }
        if (!in_array($type, [1,2])) {
            $this->wrong(0, '非法请求');
        }

        $save['source_id'] = $id;
        $save['uid'] = $this->user['uid'];
        $save['type'] = $type;
        $count = ServicesService::self()->collect_count($save);
        if($count){
           $this->wrong(0, '不能重复收藏');
        }
        $save['created_at'] = $this->request->time();
        $num = ServicesService::self()->collect($save);

        if(!$num){
           $this->wrong(0, '收藏失败');
        }
        $this->response([] , 1, '收藏成功');
    }
    /**
     * 取消收藏
     */
    public function uncollection(){
        $id = $this->request->param('id', 0, 'string');
        if(!$id){
           $this->wrong(0, '收藏失败');
        }
        $type = $this->request->param('type', 1, 'intval');
        if(!$type){
           $this->wrong(0, '非法请求，缺少参数');
        }
        if (!in_array($type, [1,2])) {
            $this->wrong(0, '非法请求');
        }
        $save['source_id'] = $id;
        $save['uid'] = $this->user['uid'];
        $save['type'] = $type;
        $count = ServicesService::self()->collect_count($save);
        if(!$count){
           $this->wrong(0, '取消失败11');
        }
        $num = ServicesService::self()->uncollect($save);
        if(!$num){
           $this->wrong(0, '取消失败22');
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
}
