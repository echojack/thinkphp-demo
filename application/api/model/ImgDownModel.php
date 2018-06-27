<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api\model;

use think\Model;
// use think\File;
/**
 * 远程图片下载
 * 依赖http_down函数
 */
class ImgDownModel{
    //下载地址
    private $url = '';
    private $path = '';

    /**
     * @var array 资源类型
     */
    protected $mimeType = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpg,image/jpeg,image/pjpeg',
        'gif'  => 'image/gif',
    ];
    /**
     * 
     * @param string  $url  图片地址
     * @param boolean $replace 是否覆盖
     */
    function __construct($url, $replace = false) {
        $this->path = ROOT_PATH . 'public' . DS;
        $this->url = trim(urldecode($url));
        // 获取图片mime 类型
        $headers = get_headers($url, true);
        $ext = '';
        if($headers){
            $content_type = $headers['Content-Type'];
            foreach ($this->mimeType as $key => $mime) {
                if(strpos($mime, $content_type)){
                    $ext = $key;
                    continue;
                }
            }
        }
        // 下载图片
        $filename = $this->path . 'uploads' . DS . date('Y/m/d') . DS . md5($this->url) . '.' . $ext;
        if (!is_file($filename) || $replace) {
            if (http_down($this->url, $filename) === false) {
                $this->error = '下载文件失败';
            }
        }

        // 获取数据并保存
        $contents=file_get_contents($this->url);
        if(file_put_contents($filename , $contents)){
            $this->filename = $filename;    
        }
    }
    /**
     * 检测是否合法的下载文件
     * @return bool
     */
    public function isValid() {
        return is_file($this->filename);
    }
    /**
     * 获取文件名
     * @param boolean $realpath 是否返回绝对路径
     * @return false|string
     */
    public function getFileName($realpath = false) {

        // 检测合法性
        if (!$this->isValid()) {
            $this->error = '非法下载文件';
            return false;
        }
        // // 验证下载
        // if (!$this->check()) {
        //     return false;
        // }
        // if (!empty($this->error)) {
        //     return false;
        // }

        $this->filename = str_replace('\\', '/', $this->filename);
        return $realpath ? $this->filename : substr($this->filename, strlen($this->path));
    }
}