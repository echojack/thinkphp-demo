<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function getLastUrl(){
    return \think\Session::get('last_url');
}

function make_password($password,$auth){
    return md5($auth.$password);
}

function trans($message){
    return \axios\tpr\service\LangService::trans($message);
}
function makeAppVersion($app,$update_type,$version_type="release"){
    $temp_base = $app['base_version'];

    if(!empty($app['last_version'])){
        list($temp_main,$temp_next,$temp_debug) = explode(".",$app['last_version']);
    }else{
        $temp_main = $temp_base;
        $temp_next = 0;
        $temp_debug = 0;
    }
    $main = $temp_main;$next = 0;$debug=0;
    switch ($update_type){
        case 2:
            $main = ++$app['base_version'];
            break;
        case 1:
            $next = ++$temp_next;
            break;
        case 0:
            $next = $temp_next;
            $debug = ++$temp_debug;
            break;
    }

    return makeVersion($main,$next,$debug,$version_type);
}

function makeVersion($main,$next="0",$debug="0",$type="release")
{
    return $main . "." . $next . "." . $debug . "." . date("ymd") . "_" . $type;
}

function domain(){
    $domain = \think\Env::get('web.host');
    $last_str = substr($domain,-1);
    if($last_str!= "/"){
        $domain .= "/";
    }
    return $domain;
}

function getMonthBeginEndDay($year,$month,$format='timestamp'){
    $month = sprintf('%02d',$month);
    $ymd = $year."-".$month."-01";
    $begin = strtotime($ymd." 00:00:00");
    $end   = strtotime("$ymd +1 month -1 seconds");
    if($format=='timestamp'){
        return [
            'begin'=>$begin,
            'end'=>$end
        ];
    }else{
        return [
            'begin'=>date($format,$begin),
            'end'=>date($format,$end),
        ];
    }
}

function getDayBeginEndTime($date,$format='timestamp'){
    $begin = strtotime($date." 00:00:00");
    $end   = strtotime("$date +1 day -1 seconds");
    if($format=='timestamp'){
        return [
            'begin'=>$begin,
            'end'=>$end
        ];
    }else{
        return [
            'begin'=>date($format,$begin),
            'end'=>date($format,$end),
        ];
    }
}

function getHourBeginEndTime($date ,$hour,$format='timestamp'){
    $hour = sprintf('%02d',$hour);
    $begin = strtotime($date." ".$hour.":00:00");
    $end   = strtotime($date." ".$hour.":00:00 +1 hour -1 seconds");
    if($format=='timestamp'){
        return [
            'begin'=>$begin,
            'end'=>$end
        ];
    }else{
        return [
            'begin'=>date($format,$begin),
            'end'=>date($format,$end),
        ];
    }
}
// 获取用户角色
function get_user_role_name($group_id = ''){
    if(!$group_id){
        return '';
    }
    $group_name_lists = \think\Cache::get('group_name_lists');
    if(!$group_name_lists){
        $group = \think\Cache::get('group_lists');
        if(!$group){
            $group_name_lists = [];
            $lists = \think\Db::table('admin_group')->select();
            if($lists){
                foreach ($lists as $key => $value) {
                    $group_name_lists[$value['group_id']] = $value['group_name'];
                }
            }
            \think\Cache::set('group_lists', $lists);
            \think\Cache::set('group_name_lists', $group_name_lists);
        }
    }
    return $group_name_lists[$group_id];
}