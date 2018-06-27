<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Db;
use think\Model;

class CircleCommentModel extends Model{
    protected $name = "circle_posts_comment";

    public static function self(){
        return new self();
    }

    /**
     * 评论帖子
     */
    public function add_posts_comments($param = '', $uid = ''){

        // 启动事务
        Db::startTrans();
        try{

            $data['parent_id'] = 0;
            if(isset($param['comments_id'])){
                $data['parent_id'] = $param['comments_id'];
            }
            $data['posts_id'] = $param['posts_id'];
            $data['content'] = strEncode($param['content']);
            $data['created_uid'] = $uid;
            $data['created_at'] = time();
            $new_comments_id = Db::table('circle_posts_comment')->insertGetId($data);
            if(!$new_comments_id){
                return '发布评论失败';
            }
            $res = Db::table('circle_posts')->where(['posts_id'=>$param['posts_id']])->setInc('comment_count');
            if(!$res){
                return '更新评论数失败';
            }
            // 提交事务
            Db::commit(); 
            return $new_comments_id;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '发布评论失败';
        }
    }
    /**
     * 帖子评论列表
     */
    public function posts_comments($posts_id = '', $page = 1, $limit = 10){
        if(!$posts_id){
            return [];
        }
        $where['posts_id'] = $posts_id;
        $where['is_del'] = 0;
        // $where['parent_id'] = 0;
        if($page){
            $lists = Db::table('circle_posts_comment')
            ->where($where)
            ->order('comments_id DESC')
            ->page($page, $limit)->select();
        }else{
            $lists = Db::table('circle_posts_comment')
            ->where($where)
            ->order('comments_id DESC')
            ->select();
        }
        $lists = json_decode(json_encode($lists), true);
        return $lists;
    }

    /**
     * 评论详情
     */
    public function comments_detail($comments_id = ''){
        $where['comments_id'] = $comments_id;
        $detail = $this->where($where)->find();
        $detail = json_decode(json_encode($detail), true);
        return $detail;
    }

    /**
     * 删除评论
     */
    public function del_comment($comments_id = '', $uid = ''){
        $where['comments_id'] = $comments_id;
        $where['created_uid'] = $uid;
        $comments = $this->where($where)->find();
        // 启动事务
        Db::startTrans();
        try{
            $res = $this->where($where)->delete();
            if(!$res){
                return '删除失败';
            }
            $res = Db::table('circle_posts')->where(['posts_id'=>$comments['posts_id']])->setDec('comment_count');
            if(!$res){
                return '更新评论数失败';
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '发布评论失败';
        }
    }

}
