<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api8\controller;
use think\Db;
use app\api8\model\ConfigModel;
use app\common\controller\ApiLogin;
/**
 * Class Config
 * 配置信息接口
 * @package app\api8\controller
 */
class Config extends ApiLogin {
    /**
     * 职业 
     * @param type = 1
     */
    public function all(){
        $lists['service_category'] = $this->service_category();
        $lists['service_time'] = $this->service_time();
        $lists['service_sort'] = $this->service_sort();
        $lists['price_unit'] = $this->price_unit();
        $lists['demand_category'] = $this->demand_category();
        $lists['demand_sort'] = $this->demand_sort();
        $lists['user_sex'] = $this->user_sex();
        $lists['user_tags'] = $this->user_tags();
        $lists['report'] = $this->report_content();
        $lists['occupation'] = $this->occupation();
        $lists['marry'] = $this->marry();
        $this->response($lists, 1, 'success');
    }
    /**
     * 服务搜索相关
     */
    public function service_category(){
        $lists = ConfigModel::self()->lists(1);
        $lists = $this->_show($lists);
        // 处理数据
        if($lists){
            foreach ($lists as $key => $value) {
                $parent_id = $value['configs_id'];
                $list = ConfigModel::self()->lists(1, $parent_id);
                $list = $this->_show($list);
                $value['children'] = $list;
                $lists[$key] = $value;
            }
        }
        return $lists;        
    }
    /**
     * 服务时间
     */
    public function service_time(){
        $lists = ConfigModel::self()->lists(2);
        $lists = $this->_show($lists);
        return $lists;
    }
    /**
     * 服务排序
     */
    public function service_sort(){
        $lists = ConfigModel::self()->lists(5);
        $lists = $this->_show($lists);
        return $lists;
    }
    /**
     * 邀约分类
     */
    public function demand_category(){
        $lists = ConfigModel::self()->lists(3);
        $lists = $this->_show($lists);
        return $lists;        
    }
    /**
     * 邀约排序
     */
    public function demand_sort(){
        $lists = ConfigModel::self()->lists(6);
        $lists = $this->_show($lists);
        return $lists;
    }
    /**
     * 用户性别
     */
    public function user_sex(){
        $tmp[] = ['configs_id'=>1, 'value'=>'男'];
        $tmp[] = ['configs_id'=>2, 'value'=>'女'];
        $tmp[] = ['configs_id'=>0, 'value'=>'不限'];
        return $tmp;
    }
    /**
     * 用户标签
     */
    public function user_tags(){
        $lists = ConfigModel::self()->lists(4);
        $lists = $this->_show($lists);
        return $lists;
    }
    /**
     * 数据处理
     */
    private function _show($list = []){
        $tmp_list = [];
        if($list){
            foreach ($list as $key => $value) {
                $tmp_detail = [
                    'configs_id' => $value['configs_id'],
                    'value' => $value['value'],
                    'icon' => $value['icon'],
                    'intro' => $value['intro'],
                    'select' => 0,
                ];
                if($value['icon']){
                    $tmp_detail['icon'] = \think\Config::get('img_url').$value['icon'];
                }
                $tmp_list[] = $tmp_detail;
            }
        }
        return $tmp_list;
    }
    /**
     * 数据处理
     */
    private function _show2($list = []){
        $tmp_list = [];
        if($list){
            foreach ($list as $key => $value) {
                $tmp_detail = [
                    'configs_id' => $value['configs_id'],
                    'value' => $value['value'],
                ];
                $tmp_list[] = $tmp_detail;
            }
        }
        return $tmp_list;
    }
    /**
     * 举报内容
     */
    private function report_content(){
        $lists = ConfigModel::self()->lists(7);
        $lists = $this->_show2($lists);
        return $lists;
    }
    /**
     * 行业列表
     */
    public function occupation(){
        $lists = ConfigModel::self()->lists(8);
        $lists = $this->_show($lists);
        return $lists;
    }
    /**
     * 情感状态
     */
    public function marry(){
        $lists = ConfigModel::self()->lists(9);
        $lists = $this->_show($lists);
        return $lists;
    }
    /**
     * 价格单位
     */
    public function price_unit(){
        $lists = ConfigModel::self()->lists(10);
        $lists = $this->_show($lists);
        return $lists;
    }
    /**
     * 学校信息列表
     */
    public function schools(){
        $key = $this->request->param('key', '', 'string');
        // $lists = \think\Cache::get('schools');
        // if(!$lists){
        $where['school_name'] = ['LIKE', '%'.$key.'%'];
        $lists = Db::table('school')->where($where)->select();
        //     \think\Cache::set('schools', $lists);
        // }
        $this->response($lists, 1, 'success');
    }
}
