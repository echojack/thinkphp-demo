<?php
/**
 * @author: Axios
 *
 * @email: axioscros@aliyun.com
 * @blog:  http://hanxv.cn
 * @datetime: 2017/5/2 13:49
 */
namespace app\common\controller;

use app\common\controller\ApiBase;
use app\common\service\GlobalService;
use app\common\service\LangService;
use app\common\service\UserService;
use think\Validate;
use think\Request;
use think\Config;
use think\Cache;

class ApiLogin extends ApiBase{
    protected $user ;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->commonFilter('login');//公共过滤
        $this->checkToken();
    }

    protected function commonFilter($scene='logout'){
        // 过滤时间戳和签名
        $setting = Config::get('setting.sign');
        $timestamp_name = isset($setting['timestamp_name'])&& !empty($setting['timestamp_name'])?$setting['timestamp_name']:"t";
        $sign_name = isset($setting['sign_name'])&& !empty($setting['sign_name'])?$setting['sign_name']:"sign";

        $rules = [
            'lang' => ['in:zh-cn,en-us'],
            'token' =>['regex:/^([a-z]|[0-9])*$/i']
        ];

        $message = ['token.regex'    =>'token@format@error ',];
        $message[$timestamp_name.".length"] = "timestamp@length@error";
        $message[$timestamp_name.".number"] = "timestamp@is not@number";
        $message[$sign_name.".length"] = "sign@length@error";
        $message[$sign_name.".regex"] = "sign@regex@error";

        $rules[$timestamp_name] = ['length:10','number'];
        $rules[$sign_name] = ['length:32','regex:/^([a-z]|[0-9])*$/i'];

        $Validate = new Validate($rules,$message);
        $Validate->scene('logout', [$timestamp_name,$sign_name,'lang']);
        $Validate->scene('login', [$timestamp_name,$sign_name,'lang','token']);
        $check = $Validate->scene($scene)->check($this->param);
        if(!$check){
            $this->wrong(0,LangService::trans($Validate->getError()));
        }
    }

    protected function checkToken(){
        if(isset($this->param['token']) && !empty($this->param['token'])){
            $token = $this->param['token'];
        }else{
            $token =$this->request->header("X-Client-Token");
        }
        if(empty($token)){
            $this->wrong(0,LangService::trans("签名不能为空"));
        }
        // 检测签名的正确性
        $result = UserService::checkToken($token);
	    if(is_int($result)){
            switch ($result) {
                case 403101:
                    \think\Log::write($token,'签名超时无效1');
                    $this->wrong('10001', '签名超时无效1');
                    break;
                case 403100:
                    \think\Log::write($token,'账号在其他地方登录过，请重新登录');
                    $this->wrong('10001', '账号在其他地方登录过，请重新登录');
                    break;
                default:
                    \think\Log::write($token,'签名无效');
                    $this->wrong('10001', '签名无效');
                    break;
            }
        }
        // 设置用户信息
        $this->user = $result;
        // 更新用户token信息
        //UserService::updateTokenExpire($token);
	    $user = serialize($result);
        $setting_token = Config::get('setting.token', false);
        $expire = isset($setting_token['token_expire'])?$setting_token['token_expire']:3600;
        Cache::set($token,$user,$expire, false);
    }
    
}
