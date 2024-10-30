<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Withdraw as WithdrawModel;
use app\admin\validate\WithdrawValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Withdraw extends BaseController
{
    var $model;
    var $uid;
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new WithdrawModel();
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
            $list = $this->model->getWithdrawList($where, $param);
            $list = $list ? $list->toArray() : [];
            foreach ($list['data'] as $k => $v) {
                if (!empty($v['admin_id'])) {
                    $list['data'][$k]['admin_nickname'] = Db::name('admin')->where(['id' => $v['admin_id']])->value('nickname');
                }
            }
            return table_assign(0, '', $list);
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
                validate(WithdrawValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }

            $this->model->addWithdraw($param);
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
                validate(WithdrawValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $id = isset($param['id']) ? $param['id'] : 0;
            if (empty($id)) {
                return to_assign(1, '参数错误');
            }
            $log = Db::name('withdraw')->where(['id' => $id])->find();
            if (empty($log)) {
                return to_assign(1, '记录不存在');
            }
            $status = intval($param['status']);
            $coin = intval($param['coin']);
            if ($status == 1) {
                if (intval($log['status']) == 1) {
                    return to_assign(1, '已打过款了');
                }
                if (intval($log['coin']) != $coin) {
                    return to_assign(1, '金币数量有误');
                }
                $user = Db::name('user')->where(['id' => $log['user_id']])->find();
                if (empty($user)) {
                    return to_assign(1, '用户不存');
                }
                if (intval($user['coin']) < intval($log['coin'])) {
                    return to_assign(1, '用户金币不够本次提现数量');
                }
                // 开启事务
                Db::startTrans();
                try {
                    // 执行数据库操作
                    Db::name('user')->where('id', $user['id'])->dec('coin', $log['coin'])->update();
                    add_coin_log($user['id'], $log['coin'], 0, '金币提现');
                    Db::name('withdraw')->where('id', $log['id'])->update(['status' => 1, 'admin_id' => $this->uid, 'update_time' => time()]);
                    // 提交事务
                    Db::commit();
                    return json(['code' => 0, 'msg' => '打款成功']);
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return json(['code' => 1, 'msg' => $e->getMessage()]);
                }
            }
            if ($status == 2) {
                if (empty($param['reason'])) {
                    return to_assign(1, '拒绝理由不能为空');
                }
                $param['notes'] = $param['notes'] . $param['reason'];
                unset($param['reason']);
            }
            $this->model->editWithdraw($param);
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getWithdrawById($id);
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
        $detail = $this->model->getWithdrawById($id);
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
        $type = isset($param['type']) ? $param['type'] : 0;
        $this->model->delWithdrawById($id, $type);
    }
}
