<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Db;
use think\Model;

class CircleUserModel extends Model{
    protected $name = "circle_user";

    public static function self(){
        return new self();
    }

    /**
     * 检测是否加入圈子
     */
    public function check_add_circle($circle_id = '', $user_id = ''){
        $where['uid'] = $user_id;
        $where['circle_id'] = $circle_id;
        $where['status'] = 1;
        return $this->where($where)->count();
    }
    /**
     * 我的圈子列表
     */
    public function my_circle($uid = '', $page = 1, $limit = 10){
        $where['circle.status'] = 1;
        $where['circle_user.status'] = 1;
        $where['circle_user.uid'] = $uid;
        if($page != false){
            $list = $this->field('circle_user.circle_id')
                ->join('circle', 'circle.circle_id=circle_user.circle_id', 'left')
                ->where($where)->page($page, $limit)->select();
        }else{
            $list = $this->field('circle_user.circle_id')
                ->join('circle', 'circle.circle_id=circle_user.circle_id', 'left')
                ->where($where)->select();
        }
        return $list;
    }


}
