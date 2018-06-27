<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Model;
class CollectionsModel extends Model{
    protected $name = "collections";

    public static function self(){
        return new self();
    }
    /**
     * 检测帖子是否收藏
     */
    public function check_collect($posts_id = '', $uid = '', $type = ''){
        if(!$posts_id || !$uid){
            return 0;
        }
        $where['source_id'] = $posts_id;
        $where['type'] = $type;
        $where['uid'] = $uid;
        return $this->where($where)->count();
    }

    /**
     * 添加收藏
     */
    public function add($data = []){
        if(!$data){
            return false;
        }
        // return $this->insertGetId($data);
        return $this->insert($data);
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
     * 收藏列表
     */
    public function lists($param = '', $page = 1, $limit = 10){
        if(!$param['uid']){
            return [];
        }
        $where = $this->_lists_where($param);
        $lists = $this->field('*')
                ->where($where)
                ->order('created_at DESC')
                ->page($page, $limit)->select();
        if(!$lists){
            return [];
        }
        return array_column($lists, 'source_id');
    }
    /**
     * 收藏搜索列表
     */
    private function _lists_where($param = []){
        $where = [];
        if(!empty($param['uid'])){
            $where['uid'] = $param['uid'];
        }
        if(!empty($param['type'])){
            $where['type'] = $param['type'];
        }
        return $where;
    }
}