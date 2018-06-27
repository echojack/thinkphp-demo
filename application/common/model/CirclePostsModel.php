<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Db;
use think\Model;

class CirclePostsModel extends Model{
    protected $name = "circle_posts";

    public static function self(){
        return new self();
    }

    /**
     * 列表
     */
    public function ajax_lists($param = [], $page = 1, $limit = 10){
        $where = $this->_list_where($param);
        switch (@$param['order']) {
            case 'hot':
                $order = 'hot DESC';
                break;
            default:
                $order = 'posts_id DESC';
                break;
        }
        $lists = $this->field('*, sum(`comment_count`+`like_count`) as hot')
                ->where($where)
                ->group('posts_id')
                ->order($order)
                ->page($page, $limit)->select();
        // echo $this->getLastSql();die();
        return json_decode(json_encode($lists), true);
    }

    /**
     * 列表
     */
    public function lists($param = [], $per_page = 10){
        $where = $this->_list_where($param);
        $lists = $this->field('*')
                ->where($where)
                ->order('posts_id DESC')
                ->paginate($per_page);
        return $lists;
    }
    /**
     * 列表 统计数据
     */
    public function lists_count($param = []){
        $where = $this->_list_where($param);
        $count = $this->field('*')
                ->where($where)->count();
        return $count;
    }
    /**
     * 搜索条件组装
     */
    private function _list_where($param = []){
        $where['is_del'] = 0;
        if(!empty($param['status'])){
            $where['status'] = $param['status'];
        }
        if(!empty($param['type'])){
            $where['type'] = $param['type'];
        }
        if(!empty($param['created_uid'])){
            $where['created_uid'] = $param['created_uid'];
        }
        if(isset($param['circle_id']) && $param['circle_id']){
            $where['circle_id'] = $param['circle_id'];
        }
        if(isset($param['is_top']) && $param['is_top']){
            $where['is_top'] = $param['is_top'];
        }

        return $where;
    }

    /**
     * 列表搜索
     */
    public function detail($where = [], $fields = '*'){
        $detail = $this->field($fields)
                ->where($where)
                ->find();
        $detail = json_decode(json_encode($detail), true);
        return $detail;
    }
    /**
     * 发布帖子
     */
    public function posts($save = []){
        if(!$save){
            return false;
        }
        $circle_id = $save['circle_id'];
        // 启动事务
        Db::startTrans();
        try{
            $save['created_at'] = time();
            $save['type'] = 1;
            $res = $this->insertGetId($save);
            if(!$res){
                Db::rollback();
                return '发布帖子失败';
            }
            if($circle_id){
                $res = Db::table('circle')->where(['circle_id'=>$circle_id])->setInc('posts_count');
                if(!$res){
                    Db::rollback();
                    return '更新圈子帖子数失败';
                }
                $res = Db::table('circle_user')->where(['circle_id'=>$circle_id, 'uid'=>$save['created_uid']])->setInc('posts_count');
                if(!$res){
                    Db::rollback();
                    return '更新圈子帖子数失败';
                }
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '发布帖子失败';
        }
    }

    /**
     * 发布话题讨论
     */
    public function topic_posts($save = []){
        if(!$save){
            return false;
        }
        $circle_id = $save['circle_id'];
        // 启动事务
        Db::startTrans();
        try{
            $save['created_at'] = time();
            $save['type'] = 2;
            $res = $this->insertGetId($save);
            if(!$res){
                Db::rollback();
                return '发布帖子失败';
            }
            if($circle_id){
                $res = Db::table('circle')->where(['circle_id'=>$circle_id])->setInc('posts_count');
                if(!$res){
                    Db::rollback();
                    return '更新圈子帖子数失败';
                }
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '发布帖子失败';
        }
    }

    /**
     * 删除帖子和 话题动态
     */
    public function del_posts($posts_id = '', $uid = ''){
        $where['posts_id'] = $posts_id;
        // if($uid){
        //     $where['created_uid'] = $uid;
        // }
        $has =  $this->where($where)->find();
        if(!$has || $has['is_del'] != 0){
            return true;
        }

        // 启动事务
        Db::startTrans();
        try{
            $save['is_del'] = 1;
            $save['update_at'] = time();
            $res = $this->where($where)->update($save);
            if(!$res){
                Db::rollback();
                return '删除失败';
            }
            if($has['circle_id']){
                $res = Db::table('circle')->where(['circle_id'=>$has['circle_id']])->setDec('posts_count');
                if(!$res){
                    Db::rollback();
                    return '更新帖子数失败';
                }
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '删除失败';
        }
    }

}
