<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\model;

use think\Model;

class ServicesModel extends Model{
    protected $name = "services";

    public static function self(){
        return new self();
    }
    /**
     * 发布服务
     */
    public function add($data = []){
        if(!$data){
            return false;
        }
        return $this->insertGetId($data);
    }
    /**
     * 列表搜索
     * 分类只针对 邀约有效，服务是多个分类的
     */
    public function lists($where = [], $fields = '*', $page = 1, $limit = 10, $type='demand'){
        if($type == 'service'){
            $list = $this->where($where)->field($fields)
                ->join('services_time st','services.id = st.source_id', 'left')
                ->join('users u','services.created_uid = u.uid')
                ->join('users_ext ue','ue.uid = u.uid')
                ->order('services.id DESC')
                ->page($page, $limit)->select();
        }else{
            $list = $this->where($where)->field($fields)
                ->join('services_category sc','services.id = sc.source_id', 'left')
                ->join('services_time st','services.id = st.source_id', 'left')
                ->join('users u','services.created_uid = u.uid')
                ->join('users_ext ue','ue.uid = u.uid')
                ->order('services.id DESC')
                ->page($page, $limit)->select();
        }
        return $list;
    }

    /**
     * 删除
     */
    public function del($id = ''){
        if(!$id){
            return false;
        }
        return $this->where(['id'=>$id])->delete();
    }
    /**
     * 修改服务信息
     */
    public function do_update($save = [], $where = []){
        if(!$save || !$where){
            return false;
        }
        return $this->where($where)->update($save);
    }

    /**
     * 列表搜索
     */
    public function service_one($where = [], $fields = '*'){
        $detail = $this->field($fields)
                ->join('users u','u.uid = services.created_uid', 'left')
                ->join('users_ext ue','services.created_uid = ue.uid', 'left')
                ->where($where)
                ->find();
        $detail = json_decode(json_encode($detail), true);
        return $detail;
    }

    /**
     * 列表搜索
     */
    public function demand_one($where = [], $fields = '*'){
        $detail = $this->field($fields)
                ->join('users u','u.uid = services.created_uid', 'left')
                ->join('users_ext ue','services.created_uid = ue.uid', 'left')
                ->join('services_category sc','sc.source_id = services.id', 'left')
                ->join('services_time st','st.source_id = services.id', 'left')
                ->where($where)
                ->find();
        $detail = json_decode(json_encode($detail), true);
        return $detail;
    }
    /**
     * 查询指定数据是否存在
     */
    public function check_exit($where = []){
        if(!$where){
            return false;
        }
        return $this->where($where)->count();
    }

}
