<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use app\api\BaseController;
use think\Request;
use app\api\middleware\Auth;
use think\facade\Db;
use think\facade\Route;
use idwork\Idwork;
use app\admin\model\Order as OrderModel;

class Order extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => []]
    ];

    /**
     * 获取订单列表
     * Summary of index
     * @return void
     */
    public function index()
    {
        $param = get_params();
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        $where = ['user_id' => JWT_UID, 'is_del' => 0, 'is_system_del' => 0];
        if (isset($param['product_type']) && !empty($param['product_type'])) {
            $where[] = ['product_type', '=', $param['product_type']];
        }
        if (isset($param['paid']) && !empty($param['paid'])) {
            $where[] = ['paid', '=', $param['paid']];
        }
        if (isset($param['pay_type']) && !empty($param['pay_type'])) {
            $where[] = ['pay_type', '=', $param['pay_type']];
        }
        if (isset($param['status']) && !empty($param['status'])) {
            $where[] = ['status', '=', $param['status']];
        }
        if (isset($param['is_channel']) && !empty($param['is_channel'])) {
            $where[] = ['is_channel', '=', $param['is_channel']];
        }
        if (!isset($param['order']) || empty($param['order'])) {
            $param['order'] = 'add_time DESC';
        }
        if (isset($param['limit'])) {
            $param['limit'] = intval($param['limit']);
        }
        $list = (new OrderModel())->getOrderList($where, $param);
        $result = $list->toArray();
        $list = $result['data'];
        foreach ($list as $k => $v) {
            $list[$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            $list[$k]['pay_time'] = $v['pay_time'] ? date('Y-m-d H:i:s', $v['pay_time']) : '--';
        }
        $this->apiSuccess('success', $list);
    }

    /**
     * 生成订单
     * Summary of create
     * @return void
     */
    public function create()
    {
        $param = get_params();
        $type = isset($param['type']) ? trim($param['type']) : '';
        $channel_type = isset($param['channel_type']) ? trim($param['channel_type']) : 'wechat';
        $pid = isset($param['pid']) ? intval($param['pid']) : 0;
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($type) || empty($pid)) {
            $this->apiError('empty');
        }
        $user = Db::name('user')->where(['id' => JWT_UID])->find();
        if (empty($user)) {
            $this->apiError('404');
        }
        $price = $day = 0;
        if ($type == 'vip') {
            $conf = get_system_config('vip');
            if (intval($conf['open'] !== 1)) {
                $this->apiError('vip.unopened');
            }
            $level = Db::name('user_level')->where(['id' => $pid])->find();
            if (empty($level)) {
                $this->apiError('404');
            }
            if (empty($level['status'])) {
                $this->apiError('404');
            }
            $day = intval($level['duration']);
            $price = intval($level['price']);
            if ($price <= 0 || $day <= 0) {
                $this->apiError('vip.unopened');
            }
            $where = [
                'user_id' => JWT_UID,
                'pid' => $pid,
                'product_type' => 'vip',
                'status' => 0,
                'paid' => 0,
                'is_del' => 0,
                'is_system_del' => 0,
            ];
            $order = Db::name('order')->where($where)->find();
            if (empty($order)) {
                $data = [
                    'user_id' => JWT_UID,
                    'pid' => $pid,
                    'product_type' => 'vip',
                    'status' => 0,
                    'paid' => 0,
                    'order_id' => 'v_' . (new Idwork())->generateId(),
                    'total_num' => 1,
                    'total_price' => $price,
                    'total_postage' => 0,
                    'pay_price' => $price,
                    'add_time' => time(),
                    'use_integral' => 0,
                    'is_del' => 0,
                    'is_system_del' => 0,
                    'virtual_type' => 1,
                    'virtual_info' => lang('vip.virtual_info', ['price' => $price, 'day' => $day]),
                    'channel_type' => $channel_type
                ];
                $result = Db::name('order')->strict(false)->field(true)->insertGetId($data);
                if ($result != false) {
                    $data['id'] = $result;
                    $this->apiSuccess('success', $data);
                } else {
                    $this->apiError('fail');
                }
            } else {
                $this->apiSuccess('success', $order);
            }
        }
    }

    /*
    * 获取订单
    */
    public function info()
    {
        $param = get_params();
        $id = isset($param['id']) ? intval($param['id']) : 0;
        if (empty(JWT_UID)) {
            $this->apiError('common.isnotlogin', [], 99);
        }
        if (empty($id)) {
            $this->apiError('empty');
        }
        $order = Db::name('order')->where(['id' => $id])->find();
        if (empty($order)) {
            $this->apiError('404');
        }
        if ($order['user_id'] != JWT_UID) {
            $this->apiError('404');
        }
        $this->apiSuccess('success', $order);
    }
}
