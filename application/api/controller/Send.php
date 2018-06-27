<?php
namespace app\api\controller;
use think\Db;
use think\Config;
use think\Validate;
use ucpass\Ucpaas;
use app\common\controller\ApiBase;
use app\common\service\UserService;
use app\api\service\RongCloudService;
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
class Send extends ApiBase {
    /**
     * 发送短信验证码接口
     * @parameter string mobile 手机号
     * @method POST
     */
    public function send(){
        // 数据监测
        $this->_sendBefore();

        $username = trim($this->param['mobile']);
        if(!isMobile($username)){
            $this->wrong(0, '请输入正确的手机号');
        }
	
	if($username == '18501360496'){
            $this->wrong(0, '该手机号码为测试账户');
        }

        $result = $this->sendVerify($username);
        if(!$result){
            $this->wrong(0, '发送失败，请稍后再试');
        }
        
        // 发送成功立即注册用户
        if(!Db::name('users')->where('login_name',$username)->count()){
            $user_uniq = uniqid($username);
            $password = $username;
            $password  = make_password($password,$user_uniq);
            $data = [
                'user_uniq'=>$user_uniq,
                'login_name'=>$username,
                'nick_name'=>strEncode($username),
                'login_pass'=>$password,
                'created_at'=>time()
            ];
            $user_id = Db::name('users')->insertGetId($data);
            if(!$user_id){
                $this->wrong(0, '网络太慢，请稍后再试');
            }
            $ext_data['uid'] = $user_id;
            Db::name('users_ext')->insertGetId($ext_data);
            // 为用户注册相应的融云账号 不属于任何一个群组
            $res = RongCloudService::self()->getToken($user_id, $username, Config::get('user.avatar0'));
            if($res['code'] != 200){
                $this->wrong(0, '注册聊天账号失败，请联系管理员处理');
            }
            Db::name('users')->where(['uid'=>$user_id])->update(['ry_token'=>$res['token']]);
        }

        $this->response([], 1, '发送成功');
    }
    private function _sendBefore(){
        
        if(!isset($this->param['mobile'])){
            $this->wrong(0, '非法请求，请输入手机号');
        }

        $username = $this->param['mobile'];
        $rule = ['mobile'  => 'require|number|length:11'];
        $msg = [
                'mobile.require'=>'请输入手机号',
                'mobile.number'=>'请输入正确的手机号',
                'mobile.length'=>'请输入正确的手机号',
                ];
        $data['mobile'] = trimAll($username);
        $validate = new Validate($rule,$msg);
        $result   = $validate->check($data);
        if(!$result){
            $this->wrong(0, $validate->getError());
        }
    }
    /**
     * 验证码有效时长 5分钟
     */
    private function sendVerify($to,$time = 180){//三分钟内有效
        $code = mt_rand(100000,999999);
        $code = UserService::logVerifyCode($to,$code,$time); // 记录验证码，需要redis数据库
        if(!is_int($code)){
            $this->wrong(0, $code);
        }
        return $this->sendMessage($to,$code);
    }
    // TODO: Send Message
    private function sendMessage($to,$code)
    {
        // 配置文件 是否真实发送短信
        $sms_code = \think\Env::get('send.sms_code');
        if(!$sms_code){
            return true;
        }
        
        if(!$to || !$code){
            return false;
        }
        //初始化 $options必填
        $options['accountsid'] = Config::get('ucpass.accountSid');
        $options['token'] = Config::get('ucpass.token');
        $ucpass = new Ucpaas($options);
        $appId = Config::get('ucpass.appId');
        $templateId = Config::get('ucpass.templateId');
        //短信验证码（模板短信）
        $result =  $ucpass->templateSMS($appId,$to,$templateId,$code);
        $result = json_decode($result);
        if($result->resp->respCode == '000000'){
            return true;
        }
        return false;
    }

}
