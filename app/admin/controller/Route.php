<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Route as RouteModel;
use app\admin\validate\Route as RouteValidate;
use think\exception\ValidateException;
use think\facade\View;

class Route extends BaseController
{
    // 路由列表（分页）
    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $where = array();
            if (!empty($param['keywords'])) {
                $where[] = ['title', 'like', '%' . $param['keywords'] . '%'];
            }
            $rows = empty($param['limit']) ? get_config('app.page_size') : $param['limit'];
            $list = RouteModel::where($where)
                ->order('id asc')
                ->paginate($rows, false, ['query' => $param]);
            return table_assign(0, '', $list);
        } else {
            return view();
        }
    }

    // 编辑路由
    public function edit($id)
    {
        $param = get_params();
        $id = $param['id'];
        if (request()->isAjax()) {
            try {
                validate(RouteValidate::class)->scene('edit')->check($param);
            } catch (ValidateException $e) {
                return to_assign(1, $e->getError());
            }
            $route = RouteModel::findOrFail($id);
            if ($route->save($param)) {
                return to_assign(0);
            } else {
                return to_assign(1, '更新失败');
            }
        } else {
            $detail = RouteModel::find($id);
            if (!empty($detail)) {
                View::assign('detail', $detail);
                return view();
            } else {
                throw new \think\exception\HttpException(404, '找不到页面');
            }
        }
    }

    // 切换状态
    public function status($id)
    {
        $route = RouteModel::findOrFail($id);
        $route->status = $route->status ? 0 : 1;
        $route->save();
        return json(['code' => 0, 'msg' => '状态已更新']);
    }
}
