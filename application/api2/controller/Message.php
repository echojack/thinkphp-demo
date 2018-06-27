<?php
namespace app\api2\controller;
use think\Db;
use app\common\controller\ApiLogin;
use app\api2\service\RongCloudService;
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
class Message extends ApiLogin {

    /**
     * 发送系统消息，公告
     * 发送单聊消息方法（一个用户向另外一个用户发送消息，单条消息最大 128k。每分钟最多发送 6000 条信息，每次发送用户上限为 1000 人，如：一次发送 1000 人时，示为 1000 条消息。）
     */
    public function send_sys(){
        $page = $this->request->param('page', 1, 'intval');
        $users = Db::table('users')->field('uid')->where(['status'=>1])->page($page, 6000)->select();
        $uids = array_column($users, 'uid');
        $tmp_user = array_chunk($uids, 1000);
        if($tmp_user){
            $sysUserId = Config::get('rongcloud.sysUserId');
            $objectName = 'RC:TxtMsg';
            $tmp_content['content'] = '群发系统公告测试，呜啦啦呜啦啦呜啦呜啦啦';
            $content = json_encode($tmp_content, JSON_UNESCAPED_UNICODE);
            $pushContent = $tmp_content['content'];
            $tmp_pushData['pushData'] = $tmp_content['content'];
            $pushData = json_encode($tmp_pushData, JSON_UNESCAPED_UNICODE);
            foreach ($tmp_user as $toUserId) {
                RongCloudService::self()->publishPrivate($sysUserId, $toUserId,  $objectName, $content, $pushContent, $pushData);
            }
        }
        echo "SUCCESS";
    }

}