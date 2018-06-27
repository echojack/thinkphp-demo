<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;

use think\cache\Driver;

class Cache
{
    protected static $instance = [];
    public static $readTimes   = 0;
    public static $writeTimes  = 0;

    /**
     * 操作句柄
     * @var object
     * @access protected
     */
    protected static $handler;

    /**
     * 连接缓存
     * @access public
     * @param array         $options  配置数组
     * @param bool|string   $name 缓存连接标识 true 强制重新连接
     * @return Driver
     */
    public static function connect(array $options = [], $name = false)
    {
        $type = !empty($options['type']) ? $options['type'] : 'File';
        if (false === $name) {
            $name = md5(serialize($options));
        }

        if (true === $name || !isset(self::$instance[$name])) {
            $class = false !== strpos($type, '\\') ? $type : '\\think\\cache\\driver\\' . ucwords($type);

            // 记录初始化信息
            App::$debug && Log::record('[ CACHE ] INIT ' . $type, 'info');
            if (true === $name) {
                return new $class($options);
            } else {
                self::$instance[$name] = new $class($options);
            }
        }
        self::$handler = self::$instance[$name];
        return self::$handler;
    }

    /**
     * 自动初始化缓存
     * @access public
     * @param array         $options  配置数组
     * @return void
     */
    public static function init(array $options = [])
    {
        if (is_null(self::$handler)) {
            // 自动初始化缓存
            if (!empty($options)) {
                self::connect($options);
            } elseif ('complex' == Config::get('cache.type')) {
                self::connect(Config::get('cache.default'));
            } else {
                self::connect(Config::get('cache'));
            }
        }
    }

    /**
     * 切换缓存类型 需要配置 cache.type 为 complex
     * @access public
     * @param string $name 缓存标识
     * @return Driver
     */
    public static function store($name)
    {
        if ('complex' == Config::get('cache.type')) {
            self::connect(Config::get('cache.' . $name), strtolower($name));
        }
        return self::$handler;
    }

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public static function has($name)
    {
        $version = \think\Request::instance()->param('version');
        self::init();
        self::$readTimes++;
        return self::$handler->has($name.'_'.$version);
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存标识
     * @param mixed  $default 默认值
     * @return mixed
     */
    public static function get($name, $version_flag = true, $default = false)
    {
        $version = \think\Request::instance()->param('version');
        self::init();
        self::$readTimes++;

        if($version_flag){
            $name = $name.'_'.$version;
        }
        return self::$handler->get($name, $default);
    }

    /**
     * 写入缓存
     * @access public
     * @param string        $name 缓存标识
     * @param mixed         $value  存储数据
     * @param int|null      $expire  有效时间 0为永久
     * @return boolean
     */
    public static function set($name, $value, $expire = null, $version_flag = true)
    {
        $version = \think\Request::instance()->param('version');
        self::init();
        self::$writeTimes++;

        if($version_flag){
            $name = $name.'_'.$version;
        }
        return self::$handler->set($name , $value, $expire);
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public static function inc($name, $step = 1, $version_flag = false)
    {
        $version = \think\Request::instance()->param('version');
        self::init();
        self::$writeTimes++;

        if($version_flag){
            $name = $name.'_'.$version;
        }

        return self::$handler->inc($name, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public static function dec($name, $step = 1)
    {
        $version = \think\Request::instance()->param('version');
        self::init();
        self::$writeTimes++;
        return self::$handler->dec($name.'_'.$version, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string    $name 缓存标识
     * @return boolean
     */
    public static function rm($name, $version_flag = true)
    {
        $version = \think\Request::instance()->param('version');
        self::init();
        self::$writeTimes++;

        if($version_flag){
            $name = $name.'_'.$version;
        }
        return self::$handler->rm($name);
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 标签名
     * @return boolean
     */
    public static function clear($tag = null)
    {
        self::init();
        self::$writeTimes++;
        return self::$handler->clear($tag);
    }

    /**
     * 读取缓存并删除
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public static function pull($name)
    {
        $version = \think\Request::instance()->param('version');
        self::init();
        self::$readTimes++;
        self::$writeTimes++;
        return self::$handler->pull($name.'_'.$version);
    }

    /**
     * 如果不存在则写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param int       $expire  有效时间 0为永久
     * @return mixed
     */
    public static function remember($name, $value, $expire = null)
    {
        $version = \think\Request::instance()->param('version');
        self::init();
        self::$readTimes++;
        return self::$handler->remember($name.'_'.$version, $value, $expire);
    }

    /**
     * 缓存标签
     * @access public
     * @param string        $name 标签名
     * @param string|array  $keys 缓存标识
     * @param bool          $overlay 是否覆盖
     * @return Driver
     */
    public static function tag($name, $keys = null, $overlay = false)
    {
        $version = \think\Request::instance()->param('version');
        self::init();
        return self::$handler->tag($name.'_'.$version, $keys, $overlay);
    }

}
