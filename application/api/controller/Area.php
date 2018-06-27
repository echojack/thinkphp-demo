<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;
use app\common\controller\ApiBase;
use think\Db;
use think\Cache;
/**
 * Class Config
 * 地区接口
 * @package app\api\controller
 */
class Area extends ApiBase {
    /**
     * 省级地区 
     * @param type = 1
     */
    public function province(){
        $provinceID = $this->request->param('provinceID', 0, 'intval');
        $lists = Cache::get('area/province_'.$provinceID);
        if(!$lists){
            $where = [];
            if($provinceID){
                $where['provinceID'] = $provinceID;
            }
            $lists = Db::table('hat_province')->where($where)->order('id ASC')->select();
            Cache::set('area/province_'.$provinceID, $lists);
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
        $lists = Cache::get('area/city_'.$provinceID.'_'.$cityID);
        if(!$lists){
            $where = [];
            if($cityID){
                $where['cityID'] = $cityID;
            }
            if($provinceID){
                $where['father'] = $provinceID;
            }
            $lists = Db::table('hat_city')->where($where)->order('id ASC')->select();
            Cache::set('area/city_'.$provinceID.'_'.$cityID, $lists);
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
        $lists = Cache::get('area/area_'.$cityID.'_'.$areaID);
        if(!$lists){
            $where = [];
            if($areaID){
                $where['areaID'] = $areaID;
            }
            if($cityID){
                $where['father'] = $cityID;
            }
            $lists = Db::table('hat_area')->where($where)->order('id ASC')->select();
            Cache::set('area/area_'.$cityID.'_'.$areaID, $lists);
        }
        
        $this->response($lists, 1, 'success');
    }

}