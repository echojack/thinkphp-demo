<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api7\controller;

use think\Db;
use think\Validate;
use think\Config;
use app\api7\service\RongCloudService;
use app\api7\service\ServicesService;
use app\api7\service\ChatService;
/**
 * Class Get
 * @package app\api7\controller
 * 获取详细信息
 */
class Tools {
    /**
     * 5 分钟更新服务状态 为已审核通过
     */
    public function update_service(){
        $where['status'] = 2;
        $where['is_del'] = 0;
        $where['created_at'] = ['lt', time() - 1*30];
        $list = Db::table('services')->field('id, title, created_uid')->where($where)->select();
        if($list){
            $save['status'] = 1;
            $save['update_at'] = time();
            $res = Db::table('services')->where($where)->update($save);
            if($res){
                // 发送审核通知
                foreach ($list as $val) {
                    // $this->auditMsg($val);
		            ServicesService::self()->clear_cache($val['id']);
                }
            }
        }
    }

    /**
     * 服务审核通过消息通知
     */
    private function auditMsg($val = []){
        $serviceUserId = Config::get('rongcloud.serviceUserId');
        $toUserId = $val['created_uid'];
        $objectName = 'RC:TxtMsg';
        $tmp_content['content'] = '您的服务'.strDecode($val['title']).'审核通过啦，快去看看吧！';
        $content = json_encode($tmp_content, JSON_UNESCAPED_UNICODE);
        $pushContent = $tmp_content['content'];
        $tmp_pushData['pushData'] = $tmp_content['content'];
        $pushData = json_encode($tmp_pushData, JSON_UNESCAPED_UNICODE);
        RongCloudService::self()->publishPrivate($serviceUserId, $toUserId,  $objectName, $content, $pushContent, $pushData);
    }

    /**
     * 更新服务 统计排序信息
     */
    public function services_data(){
        $sql = 'SELECT source_id, COUNT(*) AS comment_count, AVG(star) AS avg_star FROM comments Group by source_id';
        $list = Db::query($sql);
        if($list){
            Db::table('services_data')->where('1=1')->delete();
            $save = [];
            foreach ($list as $val) {
                $detail['source_id'] = $val['source_id'];
                $detail['comment_count'] = $val['comment_count'];
                $detail['avg_star'] = $val['avg_star'];
                $detail['hot'] = $val['comment_count'];
                $save[] = $detail;
            }
            if($save){
                Db::name('services_data')->insertAll($save);
            }
        }
    }

    /**
     * 删除无效聊天室
     */
    public function check_chatroom(){
        $list = ChatService::self()->lists_chatroom(0, 0);
        if($list){
            $ids = [];
            foreach ($list as $key => $value) {
                if(isset($value['flag']) && $value['flag'] == 0){
                    $ids[] = $value['chrmId'];
                }else{
                    Db::name('chat_room')->where(['id'=>$value['chrmId']])->update(['user_count'=>$value['total']]);
                }
            }
            if($ids){
                ChatService::self()->destroy_room($ids);
            }
        }
    }

}
