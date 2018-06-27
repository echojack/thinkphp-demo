<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;

use think\Db;
use think\Validate;

/**
 * Class Get
 * @package app\api\controller
 * 获取详细信息
 */
class Tools {
    /**
     * 5 分钟更新服务状态 为已审核通过
     */
    public function update_service(){
        $where['status'] = 2;
        $where['is_del'] = 0;
        $where['created_at'] = ['neq', time() - 5*60];

        $save['status'] = 1;
        $save['update_at'] = time();

        Db::table('services')->where($where)->update($save);
        echo "success";
    }

    
}
