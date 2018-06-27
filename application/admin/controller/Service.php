<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\admin\controller;

use app\admin\controller\MY_admin;
use app\admin\service\ServiceService;
use app\admin\service\LogsService;
use app\admin\service\PushService;
/**
 * 后台登录首页
 */
class Service extends MY_admin{
    /**
     * 服务分类
     */
    public function category(){
        return $this->fetch('category');
    }

    /**
     * 服务列表
     */
    public function lists(){
        $status = $this->request->param('status', 0, 'intval');
        $key = $this->request->param('key', '', 'string');
        $param['type'] = 1;
        $param['status'] = $status;
        $param['key'] = $key;
        $data = ServiceService::self()->lists($param);
        return $this->fetch('lists', $data);
    }
    /**
     * 服务详情
     */
    public function detail(){
        $id = $this->request->param('id', '', 'intval');
        $data = ServiceService::self()->service_detail($id);
        return $this->fetch('detail', $data);
    }

    /**
     * 审核操作
     */
    public function audit(){
        $id = $this->request->param('id', '', 'intval');
        $status = $this->request->param('status', '', 'intval');
        $res = ServiceService::self()->audit($id, $status);

        $remark = ['id'=>$id, 'status'=>$status];
        
        if(!$res){
            LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT,  $id, SERVICE, ERR_ACT, $remark);
            $this->wrong(0, '操作失败，请刷新再试');
        }
        PushService::self()->push_audit_msg($id);
        LogsService::self()->add($this->uid, $this->nick_name, UPD_ACT, $id, SERVICE, SUC_ACT, $remark);
        $this->response([], 1, '操作成功');
    }
}
