<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\controller;
use app\common\controller\ApiLogin;
use think\Db;
use think\Cache;
/**
 * Class Config
 * 地区接口
 * @package app\api6\controller
 */
class Area extends ApiLogin {
    /**
     * 省级地区 
     * @param type = 1
     */
    public function province(){
        $provinceID = $this->request->param('provinceID', 0, 'intval');
        $lists = Cache::get('province_'.$provinceID);
        if(!$lists){
            $where = [];
            if($provinceID){
                $where['provinceID'] = $provinceID;
            }
            $lists = Db::table('hat_province')->where($where)->order('id ASC')->select();
            Cache::set('province_'.$provinceID, $lists);
        }
        
        $this->response($lists, 1, 'success');
    }

    /**
     * 市级地区 
     * @param type = 1
     */
    public function city(){
        $cityID = $this->request->param('cityID', 0, 'intval');
        $provinceID = $this->request->param('provinceID', 0, 'intval');
        $lists = Cache::get('city_'.$provinceID.'_'.$cityID);
        if(!$lists){
            $where = [];
            if($cityID){
                $where['cityID'] = $cityID;
            }
            if($provinceID){
                $where['father'] = $provinceID;
            }
            $lists = Db::table('hat_city')->where($where)->order('id ASC')->select();
            Cache::set('city_'.$provinceID.'_'.$cityID, $lists);
        }
        $this->response($lists, 1, 'success');
    }
    /**
     * 区级地区 
     * @param type = 1
     */
    public function area(){
        $areaID = $this->request->param('areaID', 0, 'intval');
        $cityID = $this->request->param('cityID', 0, 'intval');
        $lists = Cache::get('area_'.$cityID.'_'.$areaID);
        if(!$lists){
            $where = [];
            if($areaID){
                $where['areaID'] = $areaID;
            }
            if($cityID){
                $where['father'] = $cityID;
            }
            $lists = Db::table('hat_area')->where($where)->order('id ASC')->select();
            Cache::set('area_'.$cityID.'_'.$areaID, $lists);
        }
        
        $this->response($lists, 1, 'success');
    }
    /**
     * 地区信息  三级
     */
    public function all(){
        $province = $this->_province();
        if($province){
            foreach ($province as $k => $val) {
                $citys = $this->_city($val['provinceID']);
                if($citys){
                    foreach ($citys as $key => $value) {
                        $value['children'] = $this->_area($value['cityID']);
                        $citys[$key] = $value;
                    }
                }
                $val['children'] = $citys;
                $province[$k] = $val;
            }
        }
        $this->response($province, 1, 'success');
    }

    private function _province($provinceID = 110000){
        $lists = Cache::get('province_'.$provinceID);
        if(!$lists){
            $where = [];
            if($provinceID){
                $where['provinceID'] = $provinceID;
            }
            $lists = Db::table('hat_province')->where($where)->order('id ASC')->select();
            Cache::set('province_'.$provinceID, $lists);
        }
        return $lists;
    }
    /**
     * 市
     */
    private function _city($provinceID = 0, $cityID = 0){
        $lists = Cache::get('city_'.$provinceID.'_'.$cityID);
        if(!$lists){
            $where = [];
            if($cityID){
                $where['cityID'] = $cityID;
            }
            if($provinceID){
                $where['father'] = $provinceID;
            }
            $lists = Db::table('hat_city')->where($where)->order('id ASC')->select();
            Cache::set('city_'.$provinceID.'_'.$cityID, $lists);
        }
        return $lists;
    }
    /**
     * 区级地区 
     * @param type = 1
     */
    private function _area($cityID = 0, $areaID = 0){
        $lists = Cache::get('area_'.$cityID.'_'.$areaID);
        if(!$lists){
            $where = [];
            if($areaID){
                $where['areaID'] = $areaID;
            }
            if($cityID){
                $where['father'] = $cityID;
            }
            $lists = Db::table('hat_area')->where($where)->order('id ASC')->select();
            Cache::set('area_'.$cityID.'_'.$areaID, $lists);
        }
        return $lists;
    }
    /**
     * 市级地区 
     * @param type = 1
     */
    public function hot_city(){
        $type = $this->request->param('type', '', 'string');
        switch ($type) {
            case 'hot':
                $lists = Db::table('hat_city')->where(['hot'=>1])->select();
                break;
            default:
                $tmp_lists = Db::table('hat_city')->select();
                $lists = [];
                if($tmp_lists){
                    foreach ($tmp_lists as $val) {
                        if(isset($lists[$val['first_letter']])){
                            $lists[$val['first_letter']][] = $val;
                        }else{
                            $lists[$val['first_letter']][] = $val;
                        }
                    }
                }
                ksort($lists);
                break;
        }
        $this->response($lists, 1, 'success');
    }

}
