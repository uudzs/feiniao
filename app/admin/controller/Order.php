<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Order as OrderModel;
use app\admin\validate\OrderValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Order extends BaseController
{
    var $model;
    var $uid;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new OrderModel();
        $this->uid = get_login_admin('id');
    }

    /**
     * 数据列表
     */
    public function datalist()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = [];
            $list = $this->model->getOrderList($where, $param);
            $conf = get_system_config('vip');
            $list = $list ? $list->toArray() : [];
            foreach ($list['data'] as $k => $v) {
                $day_key = 'level_' . $v['pid'] . '_day';
                $priceKey = 'level_' . $v['pid'];
                if (isset($conf[$day_key])) {
                    $list['data'][$k]['day'] = $conf[$day_key];
                } else {
                    $list['data'][$k]['day'] = '--';
                }
                if (isset($conf[$priceKey])) {
                    $list['data'][$k]['price'] = $conf[$priceKey];
                } else {
                    $list['data'][$k]['price'] = '--';
                }
            }
            $list['totalRow']['totalpay_price'] = Db::name('order')->sum('pay_price');
            $list['totalRow']['total_price'] = Db::name('order')->sum('total_price');
            return json(['code' => 0, 'data' => $list['data'], 'count' => $list['total'], 'msg' => '', 'totalRow' => $list['totalRow']]);
        } else {
            return view();
        }
    }

    /**
     * 添加
     */
    public function add()
    {
        if (request()->isAjax()) {
            $param = get_params();

            // 检验完整性
            try {
                validate(OrderValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            if (isset($param["pay_time"])) {
                $param["pay_time"] = $param["pay_time"] ? strtotime($param["pay_time"]) : 0;
            }
            if (isset($param["refund_reason_time"])) {
                $param["refund_reason_time"] = $param["refund_reason_time"] ? strtotime($param["refund_reason_time"]) : 0;
            }

            $this->model->addOrder($param);
        } else {
            return view();
        }
    }


    /**
     * 编辑
     */
    public function edit()
    {
        $param = get_params();

        if (request()->isAjax()) {
            // 检验完整性
            try {
                validate(OrderValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            if (isset($param["pay_time"])) {
                $param["pay_time"] = $param["pay_time"] ? strtotime($param["pay_time"]) : 0;
            }
            if (isset($param["refund_reason_time"])) {
                $param["refund_reason_time"] = $param["refund_reason_time"] ? strtotime($param["refund_reason_time"]) : 0;
            }

            $this->model->editOrder($param);
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getOrderById($id);
            if (!empty($detail)) {
                View::assign('detail', $detail);
                return view();
            } else {
                throw new \think\exception\HttpException(404, '找不到页面');
            }
        }
    }


    /**
     * 查看信息
     */
    public function read()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $detail = $this->model->getOrderById($id);
        if (!empty($detail)) {
            View::assign('detail', $detail);
            return view();
        } else {
            throw new \think\exception\HttpException(404, '找不到页面');
        }
    }

    /**
     * 删除
     * type=0,逻辑删除，默认
     * type=1,物理删除
     */
    public function del()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $this->model->delOrderById($id, 1);
    }
}
