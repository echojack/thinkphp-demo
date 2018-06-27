<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\service;
use think\Db;
use think\Config;
use app\api2\service\UserService;
use app\api2\service\OrderService;
use app\api2\service\RongCloudService;
use app\api2\service\CircleService;
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
        $nick_name = $order['nick_name'];
        switch ($type) {
            case 'post_service'://服务下单
                $toUserId = $order['copy']['uid'];
                $tmp_content['extra'] = '2';
                $tmp_content['content'] = $nick_name.' 愿意为您提供服务啦，快去看看吧！';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            case 'cancel_service_order'://取消服务订单
                if($cur_uid == $order['created_uid']){
                    $toUserId = $order['copy']['uid'];
                }else if($cur_uid == $order['copy']['uid']){
                    $toUserId = $order['created_uid'];
                }
                $tmp_content['extra'] = '2';
                $tmp_content['content'] = '您的服务订单被 '.$nick_name.' 取消了。';
                $fromUserId = Config::get('rongcloud.serviceUserId');
                break;
            case 'confirm_service_order'://确认服务订单
                $toUserId = $order['created_uid'];
                $tmp_content['extra'] = '2';
                $tmp_content['content'] = '您有服务订单被 '.$nick_name.' 确认啦！';
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
                $tmp_content['content'] = '您的邀约申请被 '.$nick_name.' 拒绝了。';
                $fromUserId = Config::get('rongcloud.demandUserId');
                break;
            case 'cancel_demand_order'://取消邀约
                if($cur_uid == $order['created_uid']){
                    $toUserId = $order['copy']['uid'];
                }else if($cur_uid == $order['copy']['uid']){
                    $toUserId = $order['created_uid'];
                }
                $tmp_content['extra'] = '3';
                $tmp_content['content'] = '您的邀约申请被 '.$nick_name.' 取消了。';
                $fromUserId = Config::get('rongcloud.demandUserId');
                break;
            case 'pay_by_account'://
            case 'alipay_order_success'://订单支付
                $toUserId = $order['copy']['uid'];
                $tmp_content['extra'] = '2';
                $tmp_content['content'] = '您的服务订单 '.$order['copy']['title'].' 已经支付啦，快去看看吧！';
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
     */
    public function push_posts_like_msg($source_id = '', $cur_uid = '', $type = 'comment'){
        // 获取名字信息
        $user = UserService::self()->get_user_base_info($cur_uid);
        $nick_name = $user['nick_name'];
        $sysUserId = Config::get('rongcloud.sysUserId');
        switch ($type) {
            case 'comment':
                $source = Db::table('circle_posts_comment')->where(['comments_id'=>$source_id])->find();
                $toUserId = $source['created_uid'];
                $tmp_content['content'] = $nick_name.' 赞了你的评论，快去看看吧！';
                break;
            case 'posts':
                $source = Db::table('circle_posts')->where(['posts_id'=>$source_id])->find();
                $toUserId = $source['created_uid'];
                $tmp_content['content'] = $nick_name.' 赞了你的帖子 '.strDecode($source['title']).' ，快去看看吧！';
                break;
        }
        if($toUserId != $cur_uid){
            $objectName = 'RC:TxtMsg';
            $tmp_content['extra'] = '1';
            $content = json_encode($tmp_content, JSON_UNESCAPED_UNICODE);
            $pushContent = $tmp_content['content'];
            $tmp_pushData['pushData'] = $tmp_content['content'];
            $pushData = json_encode($tmp_pushData, JSON_UNESCAPED_UNICODE);
            return RongCloudService::self()->publishPrivate($sysUserId, $toUserId,  $objectName, $content, $pushContent, $pushData);
        }
    }
    /**
     * 发送评论消息
     */
    public function push_posts_comment_msg($posts_id = '', $comments_id = '', $cur_uid = ''){
        // 获取名字信息
        $user = UserService::self()->get_user_base_info($cur_uid);
        $nick_name = $user['nick_name'];
        $sysUserId = Config::get('rongcloud.sysUserId');
        if($posts_id){
            $source = Db::table('circle_posts')->where(['posts_id'=>$posts_id])->find();
            $toUserId = $source['created_uid'];
            $tmp_content['content'] = $nick_name.' 评论了你的帖子 '.strDecode($source['title']).' ，快去看看吧！';
        }else if($comments_id){
            $source = Db::table('circle_posts_comment')->where(['comments_id'=>$comments_id])->find();
            $toUserId = $source['created_uid'];
            $tmp_content['content'] = $nick_name.' 回复了你的评论，快去看看吧！';
        }
        if($toUserId != $cur_uid){
            $objectName = 'RC:TxtMsg';
            $tmp_content['extra'] = '1';
            $content = json_encode($tmp_content, JSON_UNESCAPED_UNICODE);
            $pushContent = $tmp_content['content'];
            $tmp_pushData['pushData'] = $tmp_content['content'];
            $pushData = json_encode($tmp_pushData, JSON_UNESCAPED_UNICODE);
            return RongCloudService::self()->publishPrivate($sysUserId, $toUserId,  $objectName, $content, $pushContent, $pushData);
        }
    }


}
