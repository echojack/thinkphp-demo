<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Model;

class ChatModel extends Model{
    protected $name = "chat_room";

    public static function self(){
        return new self();
    }

    /**
     * 创建聊天室
     */
    public function create_room($name = '', $uid = ''){
        $save['name'] = $name;
        $save['created_uid'] = $uid;
        $save['created_at'] = time();
        return $this->insertGetId($save);
    }
    /**
     * 删除聊天室
     */
    public function del_room($chrmId = [], $in = true){
        if(!$chrmId){
            return true;
        }
        if($in){
            $where['id'] = ['IN', $chrmId];
        }else{
            $where['id'] = ['NOT IN', $chrmId];
        }
        return $this->where($where)->delete();
    }
    /**
     * 查询聊天室
     */
    public function lists_chatroom($page = 1, $limit = 10){
        if($page){
            $lists = $this->page($page, $limit)->select();
        }else{
            $lists = $this->select();
        }
        return $lists;
    }

}
