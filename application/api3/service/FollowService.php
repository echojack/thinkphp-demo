<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api3\service;
use think\Db;
use think\Config;
use app\common\service\UserService;
use app\api3\service\PushService;
use app\api3\model\FollowModel;
class FollowService {
    public static function self(){
        return new self();
    }
     /**
     * 关注用户
     */
    public function dofollow($follow_id = '', $follower_id = ''){
        $data['follow_id'] = $follow_id;
        $data['follower_id'] = $follower_id;
        // 检测是否已关注过
        $res = FollowModel::self()->where($data)->count();
        if($res){
            return '请勿重复关注';
        }
        $data['created_at'] = time();
        // 启动事务
        Db::startTrans();
        try{
            // 添加关注用户
            $res = FollowModel::self()->add($data);
            if(!$res){
                Db::rollback();
                return '添加关注失败，请稍后再试！';
            }
            // 更新当前用户粉丝数
            $res = Db::table('users_data')->where(['uid'=>$follow_id])->setInc('follower_count');
            if(!$res){
                Db::rollback();
                return '更新粉丝数失败，请稍后再试！';
            }
            // 更新粉丝的关注数
            $res = Db::table('users_data')->where(['uid'=>$follower_id])->setInc('follow_count');
            if(!$res){
                Db::rollback();
                return '更新关注数失败，请稍后再试！';
            }
            // 检测双方好有关系并更新数据
            $f_where['follow_id'] = $follow_id;
            $f_where['follower_id'] = $follower_id;
            $res1 = FollowModel::self()->where($f_where)->find();
            $f_where['follow_id'] = $follower_id;
            $f_where['follower_id'] = $follow_id;
            $res2 = FollowModel::self()->where($f_where)->find();
            if($res1 && $res2){
                $res = Db::table('users_data')->where(['uid'=>$follow_id])->setInc('friends_count');
                if(!$res){
                    Db::rollback();
                    return '更新好友数失败，请稍后再试！';
                }
                $res = Db::table('users_data')->where(['uid'=>$follower_id])->setInc('friends_count');
                if(!$res){
                    Db::rollback();
                    return '更新好友数失败，请稍后再试！';
                }
            }

            // 发送关注消息推送
            PushService::self()->push_follow_msg($follow_id, $follower_id);
            // 提交事务
            Db::commit();   
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '关注失败，请刷新再试！';
        }
        return false;
    }

    /**
     * 取消关注用户
     */
    public function unfollow($follow_id = '', $follower_id = ''){
        $where['follow_id'] = $follow_id;
        $where['follower_id'] = $follower_id;
        // 启动事务
        Db::startTrans();
        try{
            // 检测是否已关注过
            $res1 = FollowModel::self()->where($where)->count();
            if(!$res1){
                return '您并没有关注TA呀！';
            }
            // 检测双方好有关系并更新数据
            $where2['follow_id'] = $follower_id;
            $where2['follower_id'] = $follow_id;
            $res2 = FollowModel::self()->where($where2)->count();
            if($res2){
                // 更新双方好友数据
                $res = Db::table('users_data')->where(['uid'=>$follow_id])->setDec('friends_count');
                if(!$res){
                    Db::rollback();
                    return '更新好友数失败，请稍后再试！';
                }
                $res = Db::table('users_data')->where(['uid'=>$follower_id])->setDec('friends_count');
                if(!$res){
                    Db::rollback();
                    return '更新好友数失败，请稍后再试！';
                }
            }
            // 删除关注关系
            $res = FollowModel::self()->where($where)->delete();
            if(!$res){
                Db::rollback();
                return '取消关注失败，请稍后再试！';
            }
            // 更新当前用户关注数
            $res = Db::table('users_data')->where(['uid'=>$follower_id])->setDec('follow_count');
            if(!$res){
                Db::rollback();
                return '更新关注数失败，请稍后再试！';
            }
            // 更新取消用户粉丝数
            $res = Db::table('users_data')->where(['uid'=>$follow_id])->setDec('follower_count');
            if(!$res){
                Db::rollback();
                return '更新粉丝数失败，请稍后再试！';
            }
            // 提交事务
            Db::commit();   
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '取消关注失败，请稍后再试！';
        }
    }

    /**
     * 好友列表
     */
    public function friends($uid, $page = 1, $limit = 10){
        if(!$uid){
            return [];
        }
        $sql = "SELECT *
                FROM follows
                WHERE follow_id = ".$uid." AND follower_id IN (
                    SELECT follow_id
                    FROM  follows
                    WHERE follower_id = ".$uid."
                ) ORDER BY created_at DESC ";
        // 分页
        $offset = ($page -1)*$limit;
        $sql .= ' LIMIT '.$offset.','.$limit;
        $lists = Db::query($sql);
        $ids = [];
        foreach ($lists as $val) {
            $ids[] = $val['follower_id'];
        }
        return $ids;
    }
    /**
     * 粉丝列表
     */
    public function follower($uid, $page = 1, $limit = 10){
        $where['follow_id'] = $uid;
        $lists = FollowModel::self()->where($where)->order('created_at DESC')->page($page, $limit)->select();
        $ids = [];
        foreach ($lists as $val) {
            $ids[] = $val->follower_id;
        }
        return $ids;
    }
    /**
     * 关注列表
     */
    public function follow($uid, $page = 1, $limit = 10){
        $where['follower_id'] = $uid;
        $lists = FollowModel::self()->where($where)->order('created_at DESC')->page($page, $limit)->select();
        $ids = [];
        foreach ($lists as $val) {
            $ids[] = $val->follow_id;
        }
        return $ids;
    }

}
