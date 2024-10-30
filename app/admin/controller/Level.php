<?php

declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\validate\LevelCheck;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Level extends BaseController
{
    public function index()
    {
        if (request()->isAjax()) {
            $level = Db::name('UserLevel')->select();
            return to_assign(0, '', $level);
        } else {
            return view();
        }
    }

    //添加新增/编辑
    public function add()
    {
        $param = get_params();
        if (request()->isAjax()) {
			$param['title'] = preg_replace('# #','',$param['title']);
            if ($param['id'] > 0) {
                try {
                    validate(LevelCheck::class)->scene('edit')->check($param);
                } catch (ValidateException $e) {
                    // 验证失败 输出错误信息
                    return to_assign(1, $e->getError());
                }
                $param['update_time'] = time();
                Db::name('UserLevel')->strict(false)->field(true)->update($param);
                add_log('edit', $param['id'], $param);
            } else {
                try {
                    validate(LevelCheck::class)->scene('add')->check($param);
                } catch (ValidateException $e) {
                    // 验证失败 输出错误信息
                    return to_assign(1, $e->getError());
                }
                $param['create_time'] = time();
                $mid = Db::name('UserLevel')->strict(false)->field(true)->insertGetId($param);
                add_log('add', $mid, $param);
            }
            return to_assign();
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            if($id>0){
                $detail = Db::name('UserLevel')->where('id',$id)->find();
                View::assign('detail', $detail);
            }
            View::assign('id', $id);
            return view();
        }
    }

    //禁用/启用
    public function disable()
    {
        $param = get_params();
		$param['update_time']= time();
		$res = Db::name('UserLevel')->strict(false)->field('status,update_time')->update($param);
		if($res!==false){
			if($param['status'] == 0){
				add_log('disable', $param['id'], $param);
			}
			else if($param['status'] == 1){
				add_log('recovery', $param['id'], $param);
			}
			return to_assign();
		}
		else{
			return to_assign(1,'操作失败');
		}
    }
}
