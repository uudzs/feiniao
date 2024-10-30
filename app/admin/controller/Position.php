<?php

declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\validate\PositionCheck;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Position extends BaseController
{
    public function index()
    {
        if (request()->isAjax()) {
            $list = Db::name('Position')->where('status', '>=', 0)->order('create_time asc')->select()->toArray();
            $res['data'] = $list;
            return table_assign(0, '', $res);
        } else {
            return view();
        }
    }

    //添加&编辑
    public function add()
    {
        $param = get_params();
        if (request()->isAjax()) {
            if (!empty($param['id']) && $param['id'] > 0) {
                try {
                    validate(PositionCheck::class)->scene('edit')->check($param);
                } catch (ValidateException $e) {
                    // 验证失败 输出错误信息
                    return to_assign(1, $e->getError());
                }
				$res = Db::name('Position')->where(['id' => $param['id']])->strict(false)->field(true)->update($param);
				if($res!==false){
					add_log('edit', $param['id'], $param);	
					return to_assign();
				}
				else{
					return to_assign(1, '提交失败');
				}
            } else {
                try {
                    validate(PositionCheck::class)->scene('add')->check($param);
                } catch (ValidateException $e) {
                    // 验证失败 输出错误信息
                    return to_assign(1, $e->getError());
                }
                
				$pid = Db::name('Position')->strict(false)->field(true)->insertGetId($param);
                if($pid>0){
					add_log('add', $pid, $param);	
					return to_assign();
				}
				else{
					return to_assign(1, '提交失败');
				}
            }
        }
        else{
            $id = isset($param['id']) ? $param['id'] : 0;
            if ($id > 0) {
                $detail = Db::name('Position')->where(['id' => $id])->find();
                View::assign('detail', $detail);
            }
            View::assign('id', $id);
            return view();
        }
    }

    //删除
    public function delete()
    {
        $id = get_params("id");
        if ($id == 1) {
            return to_assign(0, "超级岗位，不能删除");
        }
        $data['status'] = '-1';
        $data['id'] = $id;
        $data['update_time'] = time();
        if (Db::name('Position')->update($data) !== false) {
            add_log('delete', $id);
            return to_assign(0, "删除岗位成功");
        } else {
            return to_assign(1, "删除失败");
        }
    }
}
