<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\service;
use think\Db;
use think\Config;
use app\api8\service\UserService;
use app\api8\service\OrderService;
use app\api8\service\RongCloudService;
use app\api8\service\CircleService;
class PushService {

    public static function self(){
        return new self();
    }

    /**
     * 推送订单信息
     */
    public function push_order_msg($order_id = '', $type = '', $cur_uid = ''){
        $order = OrderService::self()->detail($order_id);
        $this->_msg_content($order, $type, $cur_uid);
    }
    /**
     * 内容提示
     */
    private function _msg_content($order = '',$type = '', $cur_uid = 0){
        $current_user = UserService::self()->detail($cur_uid);
        // $nick_name = $order['nick_name'];
        $nick_name = $current_user['nick_name'];
        switch ($type) {
            case 'post_service'://服务下单
                $toUserId = $order['copy']['uid'];
                $tmp_content['extra'] = '2';
                $tmp_content['content'] = '['.$nick_name.'] 想要购买您的服务，快去订单列表看看吧！';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            case 'cancel_service_order'://取消服务订单
                if($cur_uid == $order['created_uid']){
                    $toUserId = $order['copy']['uid'];
                }else if($cur_uid == $order['copy']['uid']){
                    $toUserId = $order['created_uid'];
                }
                $tmp_content['extra'] = '2';
                $tmp_content['content'] = '您的服务订单['.$order['copy']['title'].']被用户['.$nick_name.']取消了。';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            case 'confirm_service_order'://确认服务订单
                $toUserId = $order['created_uid'];
                $tmp_content['extra'] = '2';
                $tmp_content['content'] = '您提交的服务订单['.$order['copy']['title'].']已经被对方确认，立即去订单列表付款吧！';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            case 'finish_order'://完成服务订单
                $toUserId = $order['copy']['uid'];
                $tmp_content['extra'] = '2';
                $tmp_content['content'] = '您有服务已经完成啦，快去看看收益吧！';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            case 'post_demand'://参加邀约
                $toUserId = $order['copy']['uid'];
                $tmp_content['extra'] = '3';
                $tmp_content['content'] = $nick_name.' 参加了您发起的邀约，快去看看吧！';
                $fromUserId = Config::get('rongcloud.demandUserId');
                break;
            case 'agree_order'://同意邀约
                $toUserId = $order['created_uid'];
                $tmp_content['extra'] = '3';
                $tmp_content['content'] = $nick_name.' 同意了您的邀约申请，快去看看吧！';
                $fromUserId = Config::get('rongcloud.demandUserId');
                break;
            case 'reject_order'://决绝邀约
                $toUserId = $order['created_uid'];
                $tmp_content['extra'] = '3';
                $tmp_content['content'] = '您的邀约申请['.$order['copy']['title'].']被用户['.$nick_name.']拒绝了。';
                $fromUserId = Config::get('rongcloud.demandUserId');
                break;
            case 'cancel_demand_order'://取消邀约
                if($cur_uid == $order['created_uid']){
                    $toUserId = $order['copy']['uid'];
                }else if($cur_uid == $order['copy']['uid']){
                    $toUserId = $order['created_uid'];
                }
                $tmp_content['extra'] = '3';
                $tmp_content['content'] = '您的邀约申请['.$order['copy']['title'].']被用户['.$nick_name.']取消了。';
                $fromUserId = Config::get('rongcloud.demandUserId');
                break;
            case 'pay_by_account'://
            case 'alipay_order_success'://订单支付
                $toUserId = $order['copy']['uid'];
                $tmp_content['extra'] = '2';
                $tmp_content['content'] = '您的服务订单 '.$order['copy']['title'].' 已经被['.$nick_name.']支付啦，快去订单列表看看吧！';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            case 'wx_order_success'://订单支付
                $toUserId = $order['copy']['uid'];
                $tmp_content['extra'] = '1';
                $tmp_content['content'] = '您的服务订单 '.$order['copy']['title'].' 已经被['.$nick_name.']支付啦，快去订单列表看看吧！';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            case 'post_zh_order'://提交置换订单
                $toUserId = $order['copy']['uid'];
                $tmp_content['extra'] = '2';
                // 置换分类
                $zh_where['status'] = ['in', $order['copy']['category_id']];
                $zh_category = Db::table('configs')->field('configs.value')->where($zh_where)->find();
                // 我的服务分类
                $copy_id2 = $order['copy_id2'];
                $category = Db::table('services')->field('configs.value')
                    ->join('configs','services.category_id = configs.configs_id', 'left')
                    ->where(['services.id'=>$copy_id2])->find();
                $my_category = $lists ? '未知分类' : $category['value'];
                // [用户名]想用[服务分类]换你的[服务分类]技能，快去置换订单列表看看吧！
                $tmp_content['content'] = '['.$nick_name.']想用['.$my_category.']换你的['.$zh_category.']技能，快去置换订单列表看看吧！';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            case 'zh_order_deal'://确认置换订单
                $toUserId = $order['created_uid'];
                $tmp_content['extra'] = '2';
                // 同意者昵称
                $nick_name = $order['copy']['nick_name'];
                $tmp_content['content'] = '['.$nick_name.']同意了您的技能交换请求。';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            default:
                $fromUserId = '';
                break;
        }
        if($fromUserId){
            $objectName = 'RC:TxtMsg';
            $content = json_encode($tmp_content, JSON_UNESCAPED_UNICODE);
            $pushContent = $tmp_content['content'];
            $tmp_pushData['pushData'] = $tmp_content['content'];
            $pushData = json_encode($tmp_pushData, JSON_UNESCAPED_UNICODE);
            RongCloudService::self()->publishPrivate($fromUserId, $toUserId,  $objectName, $content, $pushContent, $pushData);
        }
    }

    /**
     * 发送关注消息
     */
    public function push_follow_msg($follow_id = '', $follower_id = ''){
        // 获取名字信息
        $follower_user = UserService::self()->get_user_base_info($follower_id);
        $nick_name = $follower_user['nick_name'];

        $sysUserId = Config::get('rongcloud.sysUserId');
        $toUserId = $follow_id;
        $objectName = 'RC:TxtMsg';
        $tmp_content['content'] = $nick_name.' 关注了你，快去看看吧！';
        $tmp_content['extra'] = '1';
        $content = json_encode($tmp_content, JSON_UNESCAPED_UNICODE);
        $pushContent = $tmp_content['content'];
        $tmp_pushData['pushData'] = $tmp_content['content'];
        $pushData = json_encode($tmp_pushData, JSON_UNESCAPED_UNICODE);
        return RongCloudService::self()->publishPrivate($sysUserId, $toUserId,  $objectName, $content, $pushContent, $pushData);
    }

    /**
     * 发送点赞 
     * 5：点赞帖子；6：点赞动态；7：点赞评论
     */
    public function push_posts_like_msg($source_id = '', $cur_uid = '', $type = 'comment'){
        // 数据组装
        $m_save['source_id'] = $source_id;
        $m_save['created_uid'] = $cur_uid;
        $m_save['new_source_id'] = 0;
        $m_save['content'] = '';
        $m_save['status'] = 0;//未读
        $m_save['created_at'] = time();
        switch ($type) {
            case 'comment':
                // 点赞 评论 和点赞动态不通知
                $comment = Db::table('circle_posts_comment')->field('created_uid')->where(['comments_id'=>$source_id])->find();
                $m_save['to_uid'] = $comment['created_uid'];
                $m_save['type'] = 7;//点赞评论
                break;
            case 'posts':
                $posts = Db::table('circle_posts')->field('posts_id, type, created_uid')->where(['posts_id'=>$source_id])->find();
                if($posts){
                    if($posts['type'] ==1){
                        $m_save['type'] = 5;//点赞帖子
                    }else if($posts['type'] == 2 ){
                        $m_save['type'] = 6;//点赞动态
                    }
                    $m_save['to_uid'] = $posts['created_uid'];
                }
                break;
        }
        Db::table('message')->insertGetId($m_save);
    }
    /**
     * 发送评论消息 
     * 1:评论帖子；2：评论动态；3：回复帖子；4：回复动态；
     */
    public function push_posts_comment_msg($new_source_id, $posts_id = '', $comments_id = '', $cur_uid = '', $content = ''){
        $posts = Db::table('circle_posts')->field('posts_id, type, created_uid')->where(['posts_id'=>$posts_id])->find();
        if($posts){
            if($posts['type'] == 1){//圈子帖子
                if($comments_id){
                    $m_save['type'] = 3;
                }else{
                    $m_save['type'] = 1;
                }
            }else if($posts['type'] ==2 ){//话题动态
                if($comments_id){
                    $m_save['type'] = 4;
                }else{
                    $m_save['type'] = 2;
                }
            }
            $m_save['source_id'] = $posts_id;
            $m_save['to_uid'] = $posts['created_uid'];
        }
        if($comments_id){
            $comment = Db::table('circle_posts_comment')->field('created_uid')->where(['comments_id'=>$comments_id])->find();
            $m_save['source_id'] = $comments_id;
            $m_save['to_uid'] = $comment['created_uid'];
        }
        // 数据组装
        $m_save['created_uid'] = $cur_uid;
        $m_save['new_source_id'] = $new_source_id;
        $m_save['content'] = strEncode($content);
        $m_save['status'] = 0;//未读
        $m_save['created_at'] = time();
        Db::table('message')->insertGetId($m_save);
    }


}
