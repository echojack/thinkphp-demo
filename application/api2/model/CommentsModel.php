<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api2\model;

use think\Model;
use think\Validate;

class CommentsModel extends Model{
    protected $name = "comments";

    public static function self(){
        return new self();
    }

    /**
     * 添加收藏
     */
    public function add($data = []){
        if(!$data){
            return false;
        }
        return $this->insertGetId($data);
    }
    
    /**
     * 删除收藏
     */
    public function del($where = []){
        if(!$where){
            return false;
        }
        return $this->where($where)->delete();
    }
    /**
     * 评论列表
     */
    public function lists($source_id = '', $page = 1, $limit = 10){
        if(!$source_id){
            return [];
        }
        $where['source_id'] = $source_id;
        $lists = $this->field('*')
                ->where($where)
                ->order('comment_id DESC')
                ->page($page, $limit)->select();
        return $lists;
    }
    

}