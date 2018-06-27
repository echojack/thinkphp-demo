<?php
/**
 * @author: zym
 * @email: 979030158@qq.com
 * @datetime: 2017/5/22
 */
namespace app\common\model;
use think\Db;
use think\Model;
use think\Config;

class ServiceModel extends Model
{
    protected $name = "services";

    public static function self()
    {
        return new self();
    }

    /**
     * 列表
     */
    public function lists($param = [], $per_page = 10)
    {
        $where = $this->_list_where($param);
        $lists = $this->field('services.*')
            ->where($where)
            ->order('services.id DESC')
            ->paginate($per_page);
        return $lists;
    }

    /**
     * 列表 统计数据
     */
    public function lists_count($param = [])
    {
        $where = $this->_list_where($param);
        $count = $this->field('services.*')
            ->where($where)->count();
        return $count;
    }

    /**
     * 搜索条件组装
     */
    private function _list_where($param = [])
    {
        $where['services.is_del'] = 0;
        if (!empty($param['key'])) {
            $where['from_base64(services.title)'] = ['LIKE', '%' . $param['key'] . '%'];
        }
        if (!empty($param['status'])) {
            $where['services.status'] = $param['status'];
        }
        if (!empty($param['type'])) {
            $where['services.type'] = $param['type'];
        }
        return $where;
    }

    /**
     * 列表搜索
     */
    public function detail($where = [], $fields = '*')
    {
        $detail = $this->field($fields)
            ->where($where)
            ->find();
        $detail = json_decode(json_encode($detail), true);
        return $detail;
    }

    /**
     * 发布服务
     * 编辑服务
     */
    public function post_service($param = [], $id = '', $user = [])
    {
        $data['title'] = strEncode($param['title']);
        $data['price'] = floatval($param['price']);
        $data['price_unit'] = floatval($param['price_unit']);
        $data['is_online'] = isset($param['is_online']) ? intval($param['is_online']) : 2;
        $data['intro'] = strEncode($param['intro']);
        $data['skills'] = string(implode(',', array_filter(explode(',', str_replace('，', ',', @$param['skills'])))));
        $data['type'] = 1;
        $data['attaches'] = isset($param['attaches']) ? serialize($param['attaches']) : '';
        $data['sounds'] = isset($param['sounds']) ? $param['sounds'] : '';
        $data['sounds_length'] = isset($param['sounds_length']) ? $param['sounds_length'] : 0;
        $data['status'] = 2;//编辑过后需要重新审核
        $data['category_id'] = $param['category_id'];
        $data['time_type'] = isset($param['time_id']) ? $param['time_id'] : 0;
        $data['city_id'] = isset($param['city_id']) ? $param['city_id'] : 0;
        if ($id) {
            $data['update_at'] = time();
        } else {
            $data['created_uid'] = $user['uid'];
            $data['created_at'] = time();
        }
        // 启动事务
        Db::startTrans();
        try {
            if ($id) {
                unset($data['category_id']);
                // 编辑之前存储未编辑时的信息
                $this->copy_service($id);
                $res = $this->where(['id' => $id])->update($data);
            } else {
                $res = $id = $this->insertGetId($data);
                // 统计初始数据添加
                $s_data = ['source_id' => $id, 'comment_count' => 0, 'avg_star' => 0, 'hot' => 0];
                $res = Db::table('services_data')->insert($s_data);
            }

            if (!$res) {
                Db::rollback();
                return false;
            }
            // 提交事务
            Db::commit();
            return $id;
        } catch (\Exception $error) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

    /**
     * 复制邀约服务
     */
    public function copy_service($id = '')
    {
        $detail = $this->where(['id' => $id])->find();
        $data = json_decode(json_encode($detail), true);
        $data['source_id'] = $data['id'];
        unset($data['id']);
        unset($data['created_at']);
        unset($data['update_at']);
        $data['created_at'] = time();
        return Db::table('services_copy')->insertGetId($data);
    }

    /**
     * 服务列表
     */
    public function lists_for_api($param = [], $page = 1, $limit = 10)
    {
        // 排序问题
        $sort = isset($param['sort']) ? $param['sort'] : '';
        switch ($sort) {
            case 19://人气最高
                $sort = 'sd.hot DESC';
                break;
            case 20://评价最高
                $sort = 'sd.avg_star DESC';
                break;
            case 21://低价优先
                $sort = 'services.price ASC';
                break;
            default:
                $sort = 'services.id DESC';
                break;
        }

        $where = $this->_lists_for_api_where($param);
        $lists = Db::table('services')->field('services.id')
            ->join('services_data sd', 'sd.source_id = services.id', 'left')
            ->join('users_ext ue', 'ue.uid = services.created_uid', 'left')
            ->where($where)
            ->order($sort)
            ->page($page, $limit)->select();
        return array_column($lists, 'id');
    }

    /**
     * 搜索条件组装
     */
    private function _lists_for_api_where($param = [])
    {
        // 黑名单过滤
        if (isset($param['blacklist_uids']) && $param['blacklist_uids']) {
            $where['services.created_uid'] = ['NOT IN', $param['blacklist_uids']];
        }

        $where['services.is_del'] = 0;
        if (!empty($param['category_id'])) {
            $category = $param['category_id'];
            if (!is_array($param['category_id'])) {
                $category = explode(',', str_replace('，', ',', $param['category_id']));
            }
            if ($category) {
                $where['services.category_id'] = ['IN', $category];
            }
        }
        if (!empty($param['time_id'])) {
            $where['services.time_type'] = $param['time_id'];
        }
        if (!empty($param['sex'])) {
            $where['ue.sex'] = $param['sex'];
        }
        if (!empty($param['key'])) {
            $where['from_base64(services.title)'] = ['LIKE', '%' . $param['key'] . '%'];
        }
        if (!empty($param['type'])) {
            $where['services.type'] = $param['type'];
        }
        if (!empty($param['status'])) {
            $where['services.status'] = $param['status'];
        }
        if (!empty($param['created_uid'])) {
            $where['services.created_uid'] = $param['created_uid'];
        }
        if (!empty($param['city_id']) && $param['city_id']) {
            $where['services.city_id'] = $param['city_id'];
        }
        // 黑名单数据过滤
        if (isset($param['blacklist_uids']) && $param['blacklist_uids']) {
            $where['services.created_uid'] = ['NOT IN', $param['blacklist_uids']];
        }
        return $where;
    }

    /**
     * 删除服务
     */
    public function del_service($ids = [], $uid)
    {
        $where['id'] = ['in', $ids];
        $where['created_uid'] = $uid;
        $save['is_del'] = 1;
        $save['update_at'] = time();
        return $this->where($where)->update($save);
    }

    /**
     * 关闭服务
     */
    public function close_service($ids = [], $uid)
    {
        $where['id'] = ['in', $ids];
        $where['created_uid'] = $uid;
        $save['status'] = 3;
        $save['update_at'] = time();
        return $this->where($where)->update($save);
    }

    /**
     * 打开服务
     */
    public function open_service($ids = [], $uid)
    {
        $where['id'] = ['in', $ids];
        $where['created_uid'] = $uid;
        $save['status'] = 2;
        $save['update_at'] = time();
        return $this->where($where)->update($save);
    }

    /**
     * 发布邀约
     */
    public function post_demand($param = [], $id = '', $user = [])
    {
        $data['title'] = strEncode($param['title']);
        $data['province_id'] = intval($param['province_id']);
        $data['city_id'] = intval($param['city_id']);
        $data['area_id'] = intval($param['area_id']);
        $data['address'] = isset($param['address']) ? string($param['address']) : '';
        $data['gender'] = intval($param['gender']);
        $data['pay_way'] = intval($param['pay_way']);
        $data['intro'] = isset($param['intro']) ? strEncode($param['intro']) : '';
        $data['attaches'] = serialize($param['attaches']);
        $data['sounds'] = isset($param['sounds']) ? $param['sounds'] : '';
        $data['sounds_length'] = isset($param['sounds_length']) ? $param['sounds_length'] : 0;
        $data['type'] = 2;
        $data['status'] = 1;//默认审核通过
        $data['category_id'] = $param['category_id'];
        $data['time_type'] = $param['time_type'];
        $data['date_time'] = @strtotime($param['date_time']);
        $data['time_long'] = @floatval($param['time_long']);
        if ($id) {
            // 编辑之前存储未编辑时的信息
            $this->copy_service($id);
            $data['update_at'] = time();
            $res = $this->where(['id' => $id])->update($data);
        } else {
            $data['created_uid'] = $param['uid'];
            $data['created_at'] = time();
            $res = $id = $this->insertGetId($data);
        }
        return $id;
    }

    /**
     * 列表搜索
     * 分类只针对 邀约有效，服务是多个分类的
     * sort 排序标识 ，暂未添加
     * sort 标识  19：按人气最高；20：按评价最高；21：按价格最低
     */
    public function demand_lists($param = [], $page = 1, $limit = 10)
    {
        $sort = isset($param['sort']) ? $param['sort'] : '';
        switch ($sort) {
            case 28://最新发布
                $sort = 'services.id DESC';
                break;
            case 29://近期约会
                $sort = 'services.date_time DESC';
                break;
            default:
                $sort = 'services.id DESC';
                break;
        }
        // 搜搜数据
        $where = $this->_lists_for_api_where($param);

        $lists = $this->field('services.id')
            ->join('users_ext ue', 'ue.uid = services.created_uid', 'left')
            ->where($where)
            ->order($sort)
            ->page($page, $limit)->select();
        return array_column($lists, 'id');
    }

    /**
     * 邀约详情
     */
    public function demand_detail($id = '')
    {
        $where['services.id'] = intval($id);
        $where['services.type'] = 2;
        // $where['services.status'] = 1;
        // $where['services.is_del'] = 0;
        $demand = $this->field('*')->where($where)->find();
        return json_decode(json_encode($demand), true);
    }

    /**
     * NEW 首页列表
     */
    public function index_list($page = 1, $limit = 10)
    {
        $post_list = $this->field('services.id')
            ->join('users u', 'u.uid = services.created_uid', 'left')
            ->join('users_ext ext', 'ext.uid = u.uid', 'left')
            ->join('configs c', 'services.category_id = c.configs_id')
            ->order('services.id DESC')
            ->page($page, $limit)
            ->select();
        return array_column($post_list, 'id');
    }

    public function index_list_tmp($page = 1, $limit = 10)
    {
        $post_list = $this->field('services.id,services.title,services.price_unit,services.attaches,services.type,services.category_id,u.nick_name,ext.sex,c.value,services.created_uid')
            ->join('users u', 'u.uid = services.created_uid', 'left')
            ->join('users_ext ext', 'ext.uid = u.uid', 'left')
            ->join('configs c', 'services.category_id = c.configs_id')
            ->order('services.id DESC')
            ->page($page, $limit)
            ->select();
        return json_decode(json_encode($post_list), true);
    }

    /**
     * @param string $category_id 服务分类
     * @return array|false|\PDOStatement|string|Model
     */
    public function services_type_data($category_id = '')
    {
        $tmp_lists = $this->services_type_data_ids($category_id);
        return $tmp_lists;
    }

    public function services_type_data_ids($category_id = '')
    {
        $where['configs_id'] = ['=', $category_id];
        $data = Db::table('configs')->field('value')->where($where)->find();
        return $data;
    }

    /**
     *首页分类        Model-获取列表id，Service -foreach遍历id循环调用显示详情内容
     * 193：游戏
     */
    public function index_type($type = '', $category_id = '', $page = 1, $limit = 10, $sex = '', $time = '', $order = '')
    {
        if (!empty($category_id)) {
            $where['services.category_id'] = $category_id;
        }
        if (!empty($sex)) {
            $where['s.sex'] = $sex;
        }
        if (!empty($time)) {
            $where['services.time_type'] = $time;
        }
        //排序
        switch ($order) {
            case 1://人气
                $order = 'sd.hot DESC';
                break;
            case 2://评价最高
                $order = 'sd.avg_star DESC';
                break;
            case 3://价低优先
                $order = 'services.price ASC';
                break;
            default:
                $order = 'services.id DESC';
                break;
        }
        switch ($type) {
            case 1:
                $where['services.type'] = 1;
                $post_list = $this->field('services.id')
                    ->join('users_ext s', 'services.created_uid = s.uid', 'left')
                    ->join('services_data sd', 'sd.source_id = services.id', 'left')
                    ->where($where)
                    ->order($order)
                    ->page($page, $limit)
                    ->select();
                return array_column($post_list, 'id');
                break;
            case 193:
                $where['services.type'] = 193;
                $post_list = $this->field('services.id')
                    ->join('users_ext s', 'services.created_uid = s.uid', 'left')
                    ->where($where)
                    ->order('id DESC')
                    ->page($page, $limit)
                    ->select();
                return array_column($post_list, 'id');
                break;
            case 194:
                $where['services.type'] = 194;
                $post_list = $this->field('services.id')
                    ->join('users_ext s', 'services.created_uid = s.uid', 'left')
                    ->where($where)
                    ->order('id DESC')
                    ->page($page, $limit)
                    ->select();
                return array_column($post_list, 'id');
                break;
            case 195:
                $where['services.type'] = 195;
                $post_list = $this->field('services.id')
                    ->join('users_ext s', 'services.created_uid = s.uid', 'left')
                    ->where($where)
                    ->order('id DESC')
                    ->page($page, $limit)
                    ->select();
                return array_column($post_list, 'id');
                break;
        }

    }


}