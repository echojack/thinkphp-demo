<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\controller;

use app\common\controller\ApiBase ;
use app\common\controller\ApiLogin ;
use app\api\model\UploadModel;
use app\common\service\UserService;
use think\Config;
use think\Db;
use think\Cache;

/**
 * Class Profile
 * @package app\api\controller
 * 上传接口
 */
class Test extends ApiBase {

    protected $options = [
        'expire'        => 0,
        'cache_subdir'  => true,
        'prefix'        => '',
        'path'          => CACHE_PATH,
        'data_compress' => false,
    ];


    // 上传路径
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 上传用户头像
     * @method post
     * @parameter string token 必须
     */
    public function avatar(){

        $return['file'] = $_FILES;
        $return['attaches'] = request()->file('attaches');
        $return['_POST'] = $_POST;
        $this->response($return , 1, 'success');die();
    }


    public function do_avatar(){
        return $this->fetch('avatar');
    }

    public function alipay(){
        $url = url('Notify/recharge_callback', '', '.php', true);
        var_dump($url);
    }
    
    public function token(){
        $num = $this->request->param('i', '', 'int');
        $token = $this->request->param('token', '', 'string');
        // $token = '222656549ac8bc31ba01c4eac6974f73';
        for ($i=0; $i < $num; $i++) { 
            $result = UserService::checkToken($token);
            if(is_int($result)){
                switch ($result) {
                    case 403101:
                        \think\Log::write($token,'签名超时无效i1');
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
            var_dump(gettype($result));echo "<br>";
        }
    }

    // 'latitude' => '39.856541',
    // 'limit' => '5',
    // 'longitude' => '116.413289',
    // 'page' => '1',
    // 'token' => '344c89415327c787022f75874d31d923',
    // 'version' => '1.0',
    /**
     * 发起请求
     */
    public function curl_test(){
        $num = $this->request->param('i', '', 'int');
        $token = '2f2ed777edc67917f83b655f10eb154e';
        $param['token'] = $token;
        $param['latitude'] = 39.856541;
        $param['longitude'] = 39.856541;
        $param['uid'] = 4;

        $profile_url = 'http://47.93.27.168/think/public/index.php/api/profile/index';
        
        $url = 'http://47.93.27.168/think/public/index.php/api/lists/service';
        for ($i=0; $i < $num; $i++) {
            $res = $this->curl($profile_url, $param);
            var_dump($res); 
            $res = $this->curl($url, $param);
            var_dump($res);
        }
    }
    /**
     * 模拟请求
     */
    public function curl($url, $post_data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $temp = curl_exec($ch);
        return $temp;
    }


    public function getCacheKey(){
        $name = '04479dd5d56e1416a1fd10fb3f1f718a';
        $name = md5($name);
        if ($this->options['cache_subdir']) {
            // 使用子目录
            $name = substr($name, 0, 2) . DS . substr($name, 2);
        }
        if ($this->options['prefix']) {
            $name = $this->options['prefix'] . DS . $name;
        }
        $filename = $this->options['path'] . $name . '.php';
        $dir      = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        var_dump($filename);die();
        if (!is_file($filename)) {
            return $default;
        }
        $filename = '/usr/local/nginx/html/think/runtime/cache/7b/db64caa51507979941b9cf58c5dc14.php';
        $content = file_get_contents($filename);

        if (false !== $content) {
            $expire = (int) substr($content, 8, 12);
            if (0 != $expire && $_SERVER['REQUEST_TIME'] > filemtime($filename) + $expire) {
                //缓存过期删除缓存文件
                \think\Log::write($filename,'key_filename_expire');
                $this->unlink($filename);
                return $default;
            }
            $content = substr($content, 20, -3);
            if ($this->options['data_compress'] && function_exists('gzcompress')) {
                //启用数据压缩
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            var_dump($content);
            var_dump(unserialize($content));
            return $content;
        } else {
            return $default;
        }
    }

    /**
     * 检测token过期
     */
    public function checkToken(){
        
        $token = '04479dd5d56e1416a1fd10fb3f1f718a';
        $result = UserService::checkToken($token);
        var_dump($result);
    }
    /**
     * 缓存测试
     */
    public function redis(){
        $res = cache('login/aaaa', 'hahah');
        var_dump($res);
        $res = cache('login/aaaa');
        var_dump($res);
    }
}
