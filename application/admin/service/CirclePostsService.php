<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\service;
use think\Db;
use think\Cache;
use think\Config;
use app\common\model\CirclePostsModel;
use app\api4\model\ImgDownModel;
class CirclePostsService {

    public static function self(){
        return new self();
    }
    /**
     * 服务列表
     */
    public function lists($param = [], $per_page = 10){
        $lists = CirclePostsModel::self()->lists($param);
        $page = $lists->render();
        $tmp_lists = [];
        if($lists){
            foreach ($lists as $detail) {
                $tmp_detail = json_decode(json_encode($detail), true);
                $tmp_list = [];
                $attaches = unserialize($tmp_detail['attaches']);
                if($attaches){
                    foreach ($attaches as $k => $url) {
                        $tmp_list[] = Config::get('img_url').$url;
                    }
                }
                $tmp_detail['attaches'] = $tmp_list;
                $tmp_detail['title'] = strDecode($detail['title']);
                $tmp_detail['content'] = strip_tags(strDecode($detail['content']));
                $tmp_lists[] = $tmp_detail;
            }
        }
        $data['page'] = $page;
        $data['lists'] = $tmp_lists;
        $data['count'] = CirclePostsModel::self()->lists_count($param);
        return $data;
    }

    /**
     * 添加话题
     */
    public function ads_add($param = []){
        $data['title'] = string($param['title']);
        if(!$data['title']){
            return '请输入广告名称';
        }
        $data['content'] = $param['content'];
        if(!$data['content']){
            return '请输入广告描述';
        }
        $attaches = trimAll($param['attaches']);
        if(!$attaches){
            return '请上传广告图片';
        }

        $data['title'] = strEncode($data['title']);
        $data['content'] = strEncode($data['content']);
        $data['attaches'] = serialize([$attaches]);
        $data['circle_id'] = 0;
        $data['status'] = 1;
        $data['type'] = 3;

        $posts_id = intval($param['posts_id']);
        if($posts_id){
            $data['update_at'] = time();
            $res = CirclePostsModel::self()->where(['posts_id'=>$posts_id])->update($data);
        }else{
            $data['created_uid'] = $param['created_uid'];
            $data['created_at'] = time();
            $res = $posts_id = CirclePostsModel::self()->insert($data);
        }
        
        if(!$res){
            return '保存失败，请刷新再试';
        }
        $cache_name = 'circle_posts_'.$posts_id;
        Cache::rm($cache_name);
        return true;
    }
    /**
     * 审核操作
     */
    public function audit($id, $status){
        $where['posts_id'] = $id;
        $save['status'] = $status;
        $save['update_at'] = time();
        $res = CirclePostsModel::self()->where($where)->update($save);
        if($res){
            $cache_name = 'circle_posts_'.$id;
            Cache::rm($cache_name);
            return true;
        }
        return '操作失败，请刷新再试';
    }
    /**
     * 设为精选帖子
     */
    public function is_top($id, $is_top){
        $where['posts_id'] = $id;
        $save['is_top'] = $is_top;
        $save['update_at'] = time();
        $res = CirclePostsModel::self()->where($where)->update($save);
        if($res){
            $cache_name = 'circle_posts_'.$id;
            Cache::rm($cache_name);
            return true;
        }
        return '操作失败，请刷新再试';
    }

    /**
     * 发布帖子
     */
    public function posts_add($param = []){
        $data['title'] = string($param['title']);
        if(!$data['title']){
            return '请输入广告名称';
        }
        $tmp_content = deal_posts($param['content']);
        if(!$tmp_content['content']){
            return '请输入广告描述';
        }
        // 图片处理
        $attaches = $tmp_content['attaches'];
        if($attaches){
            foreach ($attaches as $k => $img) {
                if(strpos($img, 'http') !== false){
                    $pex = substr($img, 0, 1);
                    if($pex == '/'){
                        $img = substr($img, 1);
                    }
                    $file = new ImgDownModel($img);
                    $attaches[$k] = $file->getFileName();
                }
            }
        }

        $data['title'] = strEncode($data['title']);
        $data['content'] = strEncode($tmp_content['content']);
        $data['attaches'] = serialize($attaches);
        $data['circle_id'] = intval($param['circle_id']);
        $data['created_uid'] = $param['created_uid'];
        $data['status'] = 1;
        $data['type'] = 1;

        $posts_id = intval($param['posts_id']);
        if($posts_id){
            $data['update_at'] = time();
            $res = CirclePostsModel::self()->where(['posts_id'=>$posts_id])->update($data);
            if(!$res){
                return '修改失败';
            }
            return true;
        }else{
             // 启动事务
            Db::startTrans();
            try{
                $data['created_at'] = time();
                $res = $posts_id = CirclePostsModel::self()->insert($data);
                if(!$res){
                    Db::rollback();
                    return '保存失败，请刷新再试';
                }
                $res = Db::table('circle')->where(['circle_id'=>$param['circle_id']])->setInc('posts_count');
                if(!$res){
                    Db::rollback();
                    return '更新圈子帖子数失败';
                }
                $cache_name = 'circle_posts_'.$posts_id;
                Cache::rm($cache_name);
                // 提交事务
                Db::commit(); 
                return true;
            } catch (\Exception $error) {
                // 回滚事务
                Db::rollback();
                return '发布帖子失败';
            }
        }
        
    }
    /**
     * 删除动态
     */
    public function is_del($id, $uid = ''){
        $res = CirclePostsModel::self()->del_posts($id);
        if($res !== true){
            return $res;
        }

        $where['posts_id'] = $id;
        $posts = CirclePostsModel::self()->field('circle_id')->where($where)->find();
        $circle_id = $posts['circle_id'];

        $cache_name = 'circle_posts_'.$id;
        Cache::rm($cache_name);
        Cache::rm('circle_'.$circle_id);
        return true;
    }

}
