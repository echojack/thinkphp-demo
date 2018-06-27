<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;

use app\common\controller\ApiLogin;
use app\api\model\ConfigModel;
/**
 * Class Config
 * 配置信息接口
 * @package app\api\controller
 */
class Config extends ApiLogin {
    /**
     * 职业 
     * @param type = 1
     */
    public function all(){
        $where = [];
        $type = $this->request->param('type', 0, 'intval');
        if($type){
            $where['type'] = $type;
        }
        $list = ConfigModel::self()->where($where)->select();
        // 类别区分
        $cat = [1=>'services', 2=>'freetime', 3=>'demand_category', 4=>'user_tags'];
        $tmp_data = [];
        foreach ($list as $key => $val) {
            $tmp = [];
            $tmp['id'] = $val['id'];
            $tmp['type'] = $val['type'];
            $tmp['value'] = $val['value'];
            $tmp['icon'] = $this->img_url.$val['icon'];
            if(isset($cat[$val['type']])){
		        if($val['type'] == 4 || $val['type']==2){
                    unset($tmp['icon']);
                    unset($tmp['type']);
                }
                $tmp_data[$cat[$val['type']]][] = $tmp;
            }
        }

        $tmp_data['tips'] = $this->tips();
        // $tmp_data['pay'] = $this->alipay();
        $this->response($tmp_data, 1, 'success');
    }
    
    /**
     * 提示语设置
     */
    public function tips(){
        $return['service_tips'] = '服务提示语';
        $return['demand_tips'] = '邀约提示语';
        $return['withdraw_tips'] = '提现提示语';
        return $return;
    }
    /**
     * 支付配置
     */
    public function alipay(){
        $alipay = \think\Config::get('pay.alipay'); 
        return ['alipay'=>$alipay];
    }

}
