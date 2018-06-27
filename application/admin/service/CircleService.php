<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\service;
use think\Cache;
use think\Config;
use app\common\model\CircleModel;
use app\common\model\CircleCategoryModel;
use app\common\model\CirclePostsModel;
class CircleService{
    public static function self(){
        return new self();
    }
    /**
     * 添加圈子
     */
    public function circle_add($param = []){
        $data['title'] = string($param['title']);
        if(!$data['title']){
            return '请输入圈子标题';
        }
        $data['logo'] = trimAll($param['logo']);
        if(!$data['logo']){
            return '请上传圈子LOGO';
        }
        $data['intro'] = string($param['intro']);
        if(!$data['intro']){
            return '请输入圈子描述';
        }
        $data['rule'] = string($param['rule']);
        if(!$data['rule']){
            return '请输入圈子规则';
        }
        $data['title'] = strEncode($data['title']);
        $data['intro'] = strEncode($data['intro']);
        $data['rule'] = strEncode($data['rule']);
        $data['type'] = 1;
        $data['status'] = 1;

        $circle_id = intval($param['circle_id']);
        if($circle_id){
            $data['update_at'] = time();
            $res = CircleModel::self()->where(['circle_id'=>$circle_id])->update($data);
        }else{
            $data['created_uid'] = $param['created_uid'];
            $data['created_at'] = time();
            $res = $circle_id = CircleModel::self()->add($data);
        }
        if(!$res){
            return '保存失败，请刷新再试';
        }
        Cache::rm('circle_'.$circle_id);
        return true;
    }
    /**
     * 圈子列表
     */
    public function lists($param = [], $per_page = 10){
        $lists = CircleModel::self()->lists($param);
        $page = $lists->render();
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $detail) {
                $tmp_lists[] = $this->detail($detail['circle_id']);
            }
        }
        $data['page'] = $page;
        $data['lists'] = $tmp_lists;
        $data['count'] = CircleModel::self()->lists_count($param);
        return $data;
    }

    /**
     * 圈子详情
     */
    public function detail($id = ''){
        $cache_name = 'circle_'.$id;
        $tmp_detail = Cache::get($cache_name);
        if(!$tmp_detail){
            $tmp_detail = CircleModel::self()->detail($id);
            if($tmp_detail){
                $tmp_detail['title'] = strDecode($tmp_detail['title']);
                $tmp_detail['intro'] = strDecode($tmp_detail['intro']);
                $tmp_detail['rule'] = strDecode($tmp_detail['rule']);
		        $tmp_detail['logo_path'] = $tmp_detail['logo'];
                $tmp_detail['logo'] = Config::get('img_url').$tmp_detail['logo'];
                Cache::set($cache_name, $tmp_detail);
            }
        }
        return $tmp_detail;
    }
    /**
     * 帖子详情
     */
    public function posts_detail($id = ''){
        $cache_name = 'circle_posts_'.$id;
        $detail = Config::get($cache_name);
        if(!$detail){
            $where['posts_id'] = $id;
            $detail = CirclePostsModel::self()->detail($where);
            if(!$detail){
                return [];
            }
            
            $tmp_attaches = [];
            if(!empty($detail['attaches'])){
                $attaches = unserialize($detail['attaches']);
                foreach ($attaches as $k => $url) {
                    $pex = substr($url, 0, 1);
                    if($pex == '/'){
                        $url = substr($url, 1);
                    }
                    $tmp_attaches[] = Config::get('img_url').$url;
                }
                $detail['attaches_path'] = $attaches;
            }
            
            if($detail['type'] == 3 ){
                $detail['logo'] = $tmp_attaches['0'];
                unset($detail['attaches']);
            }else{
                $detail['attaches'] = $tmp_attaches;
            }
            $detail['title'] = strDecode($detail['title']);
            $detail['content'] = strDecode($detail['content']);
            $detail = $detail ? $detail : [];
            Config::set($cache_name, $detail);
        }
        return $detail;
    }
    /**
     * 添加话题
     */
    public function topic_add($param = []){
        $data['title'] = string($param['title']);
        if(!$data['title']){
            return '请输入话题名称';
        }
        $data['intro'] = string($param['intro']);
        if(!$data['intro']){
            return '请输入话题描述';
        }
        $data['logo'] = trimAll($param['logo']);
        if(!$data['logo']){
            return '请上传话题图片';
        }

        $data['title'] = strEncode($data['title']);
        $data['intro'] = strEncode($data['intro']);
        $data['status'] = 1;
        $data['type'] = 2;
        
        $circle_id = intval($param['circle_id']);
        if($circle_id){
            $data['update_at'] = time();
            $res = CircleModel::self()->where(['circle_id'=>$circle_id])->update($data);
        }else{
            $data['created_uid'] = $param['created_uid'];
            $data['created_at'] = time();
            $res = $circle_id = CircleModel::self()->add($data);
        }
        if(!$res){
            return '保存失败，请刷新再试';
        }
        Cache::rm('circle_'.$circle_id);
        return true;
    }

    /**
     * 审核操作
     */
    public function audit($id, $status){
        $where['circle_id'] = $id;
        $save['status'] = $status;
        $save['update_at'] = time();
        $res = CircleModel::self()->where($where)->update($save);
        if($res){
            $cache_name = 'circle_'.$id;
            Cache::rm($cache_name);
            return true;
        }
        return '操作失败，请刷新再试';
    }
    // 圈子列表
    public function lists_all($type = 1){
        $where['type'] = $type;
        $where['status'] = 1;
        $lists = CircleModel::self()->where($where)->select();
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $detail) {
                $tmp_lists[] = $this->detail($detail['circle_id']);
            }
        }
        return $tmp_lists;
    }
}
