<?php
// +----------------------------------------------------------------------
// | TPR [ Design For Api Develop ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2017 http://hanxv.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axios <axioscros@aliyun.com>
// +----------------------------------------------------------------------

namespace app\common\exception;

use Exception;
use think\Env;
use think\exception\Handle;
use think\Response;

class Http extends Handle{
    public function render(Exception $e)
    {
        //TODO::开发者对异常的操作
        //可以在此交由系统处理
        if(Env::get('app_debug')){
            $code = $e->getCode();
            if($code == 0){
                $req['code'] = '500';
                $req['message'] = [
                    'code' => $e->getCode(),
                    'msg'  => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'   => $e->getLine()
                ];
                $req['data'] = [];
                $return_type = Env::get('response.return_type');
                if(empty($return_type)){
                    $return_type = "json";
                }
                Response::create($req,$return_type,"500")->send();
                die();
            }
            return parent::render($e);
        }else{
            $req['code']= "500";
            $req['message'] = "something error";
            $req['data'] = [];
            $return_type = Env::get('response.return_type');
            if(empty($return_type)){
                $return_type = "json";
            }
            Response::create($req,$return_type,"500")->send();
            die();
        }
    }
}
