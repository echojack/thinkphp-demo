<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Db;
use think\Model;

class CircleModel extends Model{
    protected $name = "circle";

    public static function self(){
        return new self();
    }
    /**
     * 列表搜索
     */
    public function ajax_lists($param = [], $page = 1, $limit = 10){
        $where = $this->_list_where($param);
        $lists = $this->field('circle_id')
                ->where($where)
                ->order('circle_id DESC')
                ->page($page, $limit)->select();
        // echo $this->getLastSql();die();
        return $lists;
    }
    /**
     * 列表
     */
    public function lists($param = [], $per_page = 10){
        $where = $this->_list_where($param);
        $lists = $this->field('circle.*')
                ->where($where)
                ->order('circle.circle_id DESC')
                ->paginate($per_page);
        return $lists;
    }
    /**
     * 列表 统计数据
     */
    public function lists_count($param = []){
        $where = $this->_list_where($param);
        $count = $this->field('circle.*')
                ->where($where)->count();
        return $count;
    }
    /**
     * 搜索条件组装
     */
    private function _list_where($param = []){
        $where = [];
        
        if(!empty($param['status'])){
            $where['status'] = $param['status'];
        }
        if(!empty($param['type'])){
            $where['type'] = $param['type'];
        }
	if(!empty($param['key'])){
            $where['from_base64(title)'] = ['LIKE', '%'.$param['key'].'%'];
        }
        return $where;
    }

    /**
     * 列表搜索
     */
    public function detail($id = '', $fields = '*'){
        $detail = $this->field($fields)
                ->where(['circle_id'=>$id])
                ->find();
        $detail = json_decode(json_encode($detail), true);
        return $detail;
    }

    /**
     * 添加圈子
     */
    public function add($save = []){
        if(!$save){
            return false;
        }
        return $this->insertGetId($save);
    }

    /**
     * 加入圈子
     */
    public function user_add($circle_id = '', $user_id = '', $has = ''){
        // 启动事务
        Db::startTrans();
        try{
            if($has){
                $where['uid'] = $user_id;
                $where['circle_id'] = $circle_id;
                $save['status'] = 1;
                $save['created_at'] = time();
                $res = Db::table('circle_user')->where($where)->update($save);
            }else{
                $data['uid'] = $user_id;
                $data['circle_id'] = $circle_id;
                $data['posts_count'] = 0;
                $data['status'] = 1;
                $data['created_at'] = time();
                $res = Db::table('circle_user')->insert($data);
            }
            
            if(!$res){
                return '加入圈子失败';
            }
            $res = Db::table('circle')->where(['circle_id'=>$circle_id])->setInc('user_count');
            if(!$res){
                return '加入圈子失败';
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '加入圈子失败';
        }
    }
    /**
     * 退出圈子
     */
    public function user_out($circle_id = '', $user_id = ''){
        // 启动事务
        Db::startTrans();
        try{
            $where['uid'] = $user_id;
            $where['circle_id'] = $circle_id;
            $save['status'] = 2;
            $save['update_at'] = time();
            $res = Db::table('circle_user')->where($where)->update($save);
            if(!$res){
                return '退出圈子失败';
            }
            $res = Db::table('circle')->where(['circle_id'=>$circle_id])->setDec('user_count');
            if(!$res){
                return '退出圈子失败';
            }
            // 提交事务
            Db::commit(); 
            return true;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return '退出圈子失败';
        }
    }
    /**
     * 增加话题浏览量
     */
    public function view_count_inc($circle_id = '', $uid = ''){
        Db::table('circle')->where(['circle_id'=>$circle_id])->setInc('view_count');
        $save['circle_id'] = $circle_id;
        $save['created_uid'] = $uid;
        $save['created_at'] = time();
        Db::table('circle_view')->insert($save);
    }
}
