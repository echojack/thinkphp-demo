<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Db;
use think\Model;

class CircleLikeModel extends Model{
    public static function self(){
        return new self();
    }

    /**
     * 点赞帖子
     */
    public function like_posts($posts_id = '', $uid = ''){
        // 启动事务
        Db::startTrans();
        try{
            $data['posts_id'] = $posts_id;
            $data['created_uid'] = $uid;
            $data['created_at'] = time();
            $res = Db::table('circle_posts_like')->insert($data);
            if(!$res){
                return '点赞帖子失败';
            }
            $res = Db::table('circle_posts')->where(['posts_id'=>$posts_id])->setInc('like_count');
            if(!$res){
                return '更新帖子点赞数失败';
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '点赞帖子失败';
        }
    }
    /**
     * 取消点赞帖子
     */
    public function unlike_posts($posts_id = '', $uid = ''){
        // 启动事务
        Db::startTrans();
        try{
            $data['posts_id'] = $posts_id;
            $data['created_uid'] = $uid;
            $res = Db::table('circle_posts_like')->where($data)->delete();
            if(!$res){
                return '取消点赞帖子失败';
            }
            $res = Db::table('circle_posts')->where(['posts_id'=>$posts_id])->setDec('like_count');
            if(!$res){
                return '更新帖子点赞数失败';
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '取消点赞帖子失败';
        }
    }

    /**
     * 帖子点赞列表
     */
    public function posts_like_user($posts_id = '', $page = 1, $per_page = 10){
        $where['posts_id'] = $posts_id;
        $lists = Db::table('circle_posts_like')->where($where)->order('created_at DESC')->page($page, $per_page)->select();
        return $lists;
    }

    /**
     * 点赞帖子评论
     */
    public function like_posts_comments($comments_id = '', $uid = ''){
        // 启动事务
        Db::startTrans();
        try{
            $data['comments_id'] = $comments_id;
            $data['created_uid'] = $uid;
            $data['created_at'] = time();
            $res = Db::table('circle_posts_comment_like')->insert($data);
            if(!$res){
                return '点赞帖子失败';
            }
            $res = Db::table('circle_posts_comment')->where(['comments_id'=>$comments_id])->setInc('like_count');
            if(!$res){
                return '更新帖子点赞数失败';
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '点赞帖子失败';
        }
    }
    /**
     * 取消点赞帖子评论
     */
    public function unlike_posts_comments($comments_id = '', $uid = ''){
        // 启动事务
        Db::startTrans();
        try{
            $where['comments_id'] = $comments_id;
            $where['created_uid'] = $uid;
            $res = Db::table('circle_posts_comment_like')->where($where)->delete();
            if(!$res){
                return '取消点赞帖子失败';
            }
            $res = Db::table('circle_posts_comment')->where(['comments_id'=>$comments_id])->setDec('like_count');
            if(!$res){
                return '更新帖子点赞数失败';
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '取消点赞帖子失败';
        }
    }
    /**
     * 检测是否点赞过
     */
    public function check_posts_like($posts_id, $uid){
        $where['posts_id'] = $posts_id;
        $where['created_uid'] = $uid;
        $res = Db::table('circle_posts_like')->where($where)->count();
    	if(!$res){
    		return 0;
    	}
    	return 1;
    }
    /**
     * 检测是否点赞过
     */
    public function check_comments_like($comments_id, $uid){
        $where['comments_id'] = $comments_id;
        $where['created_uid'] = $uid;
        $res = Db::table('circle_posts_comment_like')->where($where)->count();
        if(!$res){
            return 0;
        }
        return 1;
    }


}
