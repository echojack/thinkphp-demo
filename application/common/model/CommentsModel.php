<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;

use think\Model;
use think\Validate;

class CommentsModel extends Model{
    protected $name = "circle_posts_comment";

    public static function self(){
        return new self();
    }

    /**
     * 获取帖子 动态 评论详情
     */
    public function detail($comments_id){
        //$temp_detail = $this->where(['comments_id'=>$comments_id])->find();
        $temp_detail = $this->field('circle_posts_comment.*, cp.type as posts_type')
                    ->join('circle_posts cp', 'cp.posts_id=circle_posts_comment.posts_id', 'left')
                    ->where(['comments_id'=>$comments_id])->find();

	$detail = json_decode(json_encode($temp_detail), true);
        $detail['content'] = strDecode($detail['content']);
        return $detail;
    }


}
