<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\service;
use think\Db;
use think\Config;
use app\admin\service\UserService;
use app\admin\service\RongCloudService;
use app\admin\service\ServiceService;
class PushService {

    public static function self(){
        return new self();
    }

    /**
     * 发送服务审核通知消息
     */
    public function push_audit_msg($id = ''){
        // 服务详情
        $service = ServiceService::self()->service_detail($id);
        if(!$service){
            return false;
        }
        $created_uid = $service['created_uid'];
        // 获取名字信息
        switch ($service['status']) {
            case 1:
                $content = '您的服务['.$service['title'].']审核通过啦，快去看看吧！';
                break;
            case 4:
                $content = '真不好意思，您的服务['.$service['title'].']审核被拒了。';
                break;
            default:
                $content = '呀！在吗！';
                break;
        }

        $sysUserId = Config::get('rongcloud.serviceUserId');
        $toUserId = $created_uid;
        $objectName = 'RC:TxtMsg';
        $tmp_content['content'] = $content;
        $tmp_content['extra'] = '1';
        $content = json_encode($tmp_content, JSON_UNESCAPED_UNICODE);
        $pushContent = $tmp_content['content'];
        $tmp_pushData['pushData'] = $tmp_content['content'];
        $pushData = json_encode($tmp_pushData, JSON_UNESCAPED_UNICODE);
        return RongCloudService::self()->publishPrivate($sysUserId, $toUserId,  $objectName, $content, $pushContent, $pushData);
    }



}
