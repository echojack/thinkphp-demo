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
namespace app\common\controller;

use app\common\service\GlobalService;
use app\common\service\LangService;
use app\common\service\ToolService;
use think\Cache;
use think\Config;
use think\Controller;

use think\Env;
use think\Log;
use think\Request;
use think\Response;
// use think\Validate;

class ApiBase extends Controller{
    /**
     * 当前请求方法：post,get...
     * @var
     */
    public $method;

    /**
     * 当前请求参数
     * @var mixed
     */
    public $param;
    /**
     * 分页  限制
     */
    public $page ;
    public $limit ;
    /**
     * 接口请求配置
     * @var mixed
     */
    public $filter;

    /**
     * 当前路由信息
     * @var string
     */
    public $route='';

    /**
     * 当前访问路径
     * @var string
     */
    public $path='';

    /**
     * 当前请求是否缓存
     * @var bool
     */
    public $cache = false;

    /**
     * 接口配置名称
     * @var
     */
    public $filter_name;

    /**
     * 当前请求标识
     * @var string
     */
    public $identify = '';

    /**
     * 调试状态
     * @var bool
     */
    public $debug = false;

    public $sign_status = false;

    public $sign_name = 'sign';

    public $timestamp_name = 't';

    public $sign_expire = 10;

    public $code=200;

    public $data=[];

    protected $return_type = 'json';
    /**
     * 网站根目录
     */
    public $img_url = '';
    public $site_url = '';
    public $domain = '';
    public $upload_path = '';

    function __construct(Request $request = null)
    {
        parent::__construct($request);
        $return_type = Env::get('response.return_type');
        if(!empty($return_type)){
            $this->return_type = $return_type;
        }
        // 设置调试模式
        Config::set('app_debug',Env::get('debug.status'));
        // Config::set('exception_handle','\\axios\\tpr\\exception\\Http');
        // 异常捕获处理
        Config::set('exception_handle','\\app\\common\\exception\\Http');

        // 获取参数
        $this->method  = $this->request->method();
        GlobalService::set("method",$this->method);
        $this->param   = $this->request->param();
        GlobalService::set('param',$this->param);
        // 分页配置
	    $this->page = isset($this->param['page']) && $this->param['page'] ? $this->param['page'] : 1;
        $this->limit = isset($this->param['limit']) && $this->param['limit'] ? $this->param['limit'] : 10;

        // 路由 过滤设置
        $this->route   = $this->request->route();
        $this->filter  = Config::get('filter');
        $route         = $this->request->routeInfo();
        $this->route   = '';
        if(!empty($route['rule'])){
            foreach ($route['rule'] as $key=>$r){
                $this->route = $key==0? $this->route.$r:$this->route."/".$r;
            }
        }

        $this->path    = strtolower($this->request->module())."/".strtolower($this->request->controller())."/".$this->request->action();
        $this->debug   = Env::get("debug.status");
        // 签名相关设置
        $this->sign_status = Env::get('auth.sign_status');
        if(!empty($this->sign_status)){
            $setting_sign = Config::get('setting.sign');
            if(isset($setting_sign['sign_mame'])){
                $this->sign_name = $setting_sign['sign_mame'];
            }
            if(isset($setting_sign['timestamp_name'])){
                $this->timestamp_name = $setting_sign['timestamp_name'];
            }
            if(isset($setting_sign['sign_expire'])){
                $this->sign_expire = $setting_sign['sign_expire'];
            }
        }
        // 根目录
        $this->img_url = Config::get('img_url');
        $this->site_url = Config::get('site_url');
        $this->domain = Config::get('domain');
        $this->upload_path = Config::get('upload_path');
        // 版本控制问题
        $this->checkVersion();
        $this->filter(); //请求过滤
        $this->middleware('before');  //前置中间件
    }

    /**
     * 请求过滤
     * @return bool|mixed
     */
    protected function filter(){
        $this->sign();
        /*** 获取接口请求配置 ***/
        if(!empty($this->route) && isset($this->filter[$this->route])){
            $this->filter_name = $this->route;
            $filter = $this->filter[$this->filter_name];
        }else if(isset($this->filter[$this->path])){
            $this->filter_name = $this->path;
            $filter = $this->filter[$this->filter_name];
        }else{
            $filter = [];
        }
        $this->identify = ToolService::uuid($this->filter_name);
        GlobalService::set('identify',$this->identify);
        if(!empty($filter)){
            /*** 接口缓存 ***/
            if(isset($filter['cache']) && !$this->debug){
                $this->cache = true;
                $param = $this->request->except(['token',$this->sign_name,$this->timestamp_name]);
                $param_md5 = md5(serialize($param));
                $response_cache = Cache::get($this->filter_name.$param_md5);
                if(!empty($response_cache)){
                    $this->send($response_cache);
                }
            }

            /*** 设备请求过滤 ***/
            if(isset($filter['mobile']) && $filter['mobile']===true){
                if(!$this->request->isMobile()){
                    $this->wrong(406);
                }
            }

            /*** 请求参数过滤 ***/
            $Validate = validate($filter['validate']);
            $check = isset($filter['scene'])?$Validate->scene($filter['scene'])->check($this->param):$Validate->check($this->param);
            if(!$check){
                return $this->wrong(400,LangService::trans($Validate->getError()));
            }
        }
        return true;
    }

    /**
     * 中间件
     * @param string $when
     */
    private function middleware($when='before'){
        /*** 获取中间件配置 ***/
        $middleware = Config::get('middleware.'.$when);

        //common middleware
        if(!empty($middleware) && isset($middleware['common'])){
            $c = $middleware['common'];
            $CommonMiddleware = $middleware($c['middleware']);
            $CommonFunc = isset($c['func']) && !empty($c['func']) ? $c['func']:"index";
            call_user_func([$CommonMiddleware,$CommonFunc]);
        }

        if(!empty($this->route) && isset($middleware[$this->route])){
            $m = $middleware[$this->route];
        }else if(isset($middleware[$this->path])){
            $m = $middleware[$this->path];
        }else{
            $m = [];
        }

        /*** 使用中间件 ***/
        if(!empty($m)){
            $Middleware = middleware($m['middleware']);
            $func = isset($m['func']) && !empty($m['func']) ? $m['func']:"index"; // default to index
            call_user_func([$Middleware,$func]);
        }
    }

    /**
     * 签名授权检测
     * env auth_sign_status
     */
    private function sign(){
        if($this->sign_status){
            if(!isset($this->param[$this->timestamp_name])){
                $this->wrong(0,$this->timestamp_name.' not exits');
            }
            $timestamp = $this->param[$this->timestamp_name];

            if(!isset($this->param[$this->sign_name])){
                $this->wrong(0,$this->sign_name.' not exits');
            }
            $sign = $this->param[$this->sign_name];

            if(time()-intval($timestamp) > intval($this->sign_expire)){
                $this->wrong(0,'sign timeout'.time());
            }

            $SignService = middleware("SignService",'service');
            $sign_result = call_user_func_array([$SignService,'checkSign'],[$timestamp,$sign]);

            if($sign_result===500){
                $this->wrong(0,' Env->auth:api_key not exits');
            }
            if(!$sign_result){
                $this->wrong(0,'wrong sign');
            }
        }
    }

    /**
     * 接口缓存
     * @param $req
     */
    private function cache($req){
        if($this->cache && !$this->debug){
            $filter = $this->filter[$this->filter_name];
            $param = $this->request->except(['token',$this->sign_name,$this->timestamp_name]);
            $cache_md5 = md5(serialize($param));
            if(isset($filter['cache']) && $filter['cache']){
                Cache::set($this->filter_name.$cache_md5,$req,$filter['cache']);
            }
        }
    }

    /**
     * 请求错误情况下的回调
     * @param $code
     * @param string $message
     */
    protected function wrong($code,$message='')
    {
        $this->response([],strval($code),$message);
    }

    /**
     * 一般情况下的回调
     * @param array $data
     * @param int $code
     * @param string $message
     * @param array $header
     */
    protected function rep($data=[],$code=1,$message='',array $header=[]){
        $this->code = $code;
        $this->data = $data;
        $req['code'] = strval($code);
        $req['data'] = $data;
        $req['message'] = !empty($message)?LangService::trans($message):LangService::message($code);
        $this->cache($req);
        $this->send($req,$header);
    }

    /**
     * 回调的每个数据全部转为string类型
     * @param array $data
     * @param int $code
     * @param string $message
     * @param array $header
     */
    protected function response($data=[],$code=1,$message='',array $header=[]){
        $data = arrayDataToString($data);
        $this->rep($data,$code,$message,$header);
    }

    /**
     * 回调数据给客户端，并运行后置中间件
     * @param $req
     * @param $header
     */
    private function send($req,$header=[]){
	   $formats = isset($this->param['formats']) ? $this->param['formats'] : '';
        if($formats == 'test'){
            dump($req);
            die();
        }
	
        Response::create($req,  $this->return_type, "200")->header($header)->send();
        if(function_exists('fastcgi_finish_request')){
            fastcgi_finish_request();
        }

        // 请求日志记录
        $log_status= Env::get('log.status');
        if(!empty($log_status) && $log_status){
            $log_database = Env::get('log.database');
            $log_database =  !empty($log_database)?$log_database:"tpr_log";
            $log = [
                'response'=>$req,
                'data'=>$this->data,
                'code'=>$this->code,
                'return _type'=>$this->return_type,
                'identify'=>$this->identify
            ];
	    if($this->code == 0){
                Log::record($log,$log_database);
            }
        }
        GlobalService::set('req',$req);
        $this->middleware('after');
        die();
    }

    /**
     * 方法不存在时的空置方法
     */
    public function __empty(){
        $this->wrong(404);
    }

    /**
     * 版本控制
     */
    public function checkVersion(){
	    $mod = $this->request->controller();
        if($mod == 'Index' || $mod == 'Notify'){
            return ;
        }
        $version = $this->request->param('version');
        if($version < Config::get('version')){
            $this->response([], 40001, 'update');
        }
    }

    
}
