<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\api6\controller;
use app\common\controller\ApiLogin;
use app\api6\service\UserService;
use app\api6\service\ServicesService;
use app\api6\service\OrderService;
use app\api6\service\CircleService;
use app\api6\service\LogsService;
/**
 * Class Profile
 * @package app\api6\controller
 */
class Profile extends ApiLogin {
    /**
     * 获取用户信息接口
     * @method post
     * @parameter string token 必须
     */
    public function index(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $this->wrong(0, '非法请求，缺少参数');
        }
        $user = UserService::self()->detail($uid, $this->user['uid']);
        if(!$user){
            $this->wrong(0, '用户不存在');
        }
        $this->response($user);
    }

    /**
     * 更新用户信息
     * @method post
     * @parameter string nickname
     */
    public function update(){
        // 数据检测 如 昵称不能超过10个字符

        // 检测标签个数
        $count = count(array_unique(explode(',', str_replace('，', ',', @$this->param['tags']))));
        if($count > 6){
            $this->wrong(0, '最多设置6个标签');
        }
        
        $user = UserService::self()->update($this->param, $this->user['uid']);

        $this->param['intro'] = '修改用户信息';
        LogsService::self()->add($this->user['uid'], $this->user['nick_name'], UPD_ACT, 0, OTHER, SUC_ACT, $this->param);
        return $this->response($user, 1, '修改成功');
    }

    /**
     * 我的服务订单
     * purchase 购买  sell 出售
     */
    public function service_order(){
        $param['type'] = $this->request->param('type', 'purchase', 'string');
        $param['status'] = $this->request->param('status', 0, 'int');
        $lists = OrderService::self()->service_order($param, $this->user,$this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }
    /**
     * 我的邀约订单 
     * part参加  create发起
     */
    public function demand_order(){
        $param['type'] = $this->request->param('type', 'part', 'string');
        $param['status'] = $this->request->param('status', 0, 'string');
        $lists = OrderService::self()->demand_order($param, $this->user,$this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }
    /**
     * 我的收藏
     */
    public function collection(){
        $type = $this->request->param('type', 0, 'intval');
        if(!$type){
            $this->wrong(0, '请输入分类');
        }
        $lists = ServicesService::self()->collect_lists($type , $this->user, $this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }
    /**
     * 我的服务
     */
    public function myservice(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $uid = $this->user['uid'];
        }
        $lists = ServicesService::self()->myservice($uid, $this->user['uid'],$this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }

    /**
     * 我的邀约
     */
    public function mydemand(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $uid = $this->user['uid'];
        }
        $lists = ServicesService::self()->mydemand($uid, $this->user['uid'], $this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }

    /**
     * 我的钱包
     */
    public function mywallet(){
        $wallet = UserService::self()->mywallet($this->user['uid']);
        $this->response($wallet, 1, 'success');
    }
    /**
     * 流水列表
     */
    public function running(){
        $tmp_lists =UserService::self()->running($this->user['uid'], $this->page, $this->limit);
        $this->response($tmp_lists, 1, 'success');
    }
    /**
     * 绑定提现账户信息
     */
    public function bind(){
        $account = $this->request->param('account', '', 'string');
        $account_type = $this->request->param('account_type', 2, 'intval');//默认支付宝
        $res =UserService::self()->bind($this->user['uid'], $account_type, $account);
        if($res !== true){
            $this->wrong(0, $res);
        }
        $this->response([], 1, 'success');
    }
    /**
     * 获取账户信息
     */
    public function bind_list(){
        $list =UserService::self()->bind_list($this->user['uid']);
        $this->response($list, 1, 'success');
    }

    /**
     * 我可以置换的服务
     */
    public function my_zh_service(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $uid = $this->user['uid'];
        }
        $lists = ServicesService::self()->myservice($uid, 0,$this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }
    /**
     * 置换订单列表
     */
    public function my_zh_order(){
        $lists = OrderService::self()->my_zh_order($this->user['uid'],$this->page, $this->limit);
        $this->response($lists, 1, 'success');
    }
    /**
     * 我的圈子
     */
    public function my_circle(){
        // 我的圈子
        $my_circle = CircleService::self()->my_circle($this->user['uid'], $this->page, $this->limit);
        $this->response($my_circle, 1, 'success');
    }
    /**
     * 个人动态
     */
    public function my_posts(){
        $uid = $this->request->param('uid', 0, 'intval');
        if(!$uid){
            $uid = $this->user['uid'];
        }
        $param['type'] = $this->request->param('type', 1, 'intval');
        $param['uid'] = $uid;
        $my_posts = CircleService::self()->my_posts($param, $this->page, $this->limit);
        $this->response($my_posts, 1, 'success');
    }


}
