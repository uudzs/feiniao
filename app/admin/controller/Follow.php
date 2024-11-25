<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Follow as FollowModel;
use app\admin\validate\FollowValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Follow extends BaseController
{

    var $uid;
    var $model;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new FollowModel();
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
            $list = $this->model->getFollowList($where, $param);
            $list = $list ? $list->toArray() : [];
            foreach ($list['data'] as $k => $v) {
                if (intval($v['type']) == 1) {
                    $list['data'][$k]['from_name'] = Db::name('author')->where('id', $v['from_id'])->value('nickname');
                } else {
                    $list['data'][$k]['from_name'] = Db::name('user')->where('id', $v['from_id'])->value('nickname');
                }
                $list['data'][$k]['ninkname'] = Db::name('user')->where('id', $v['user_id'])->value('nickname');
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
                validate(FollowValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }

            $this->model->addFollow($param);
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
                validate(FollowValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }

            $this->model->editFollow($param);
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getFollowById($id);
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
        $detail = $this->model->getFollowById($id);
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
        $type = isset($param['type']) ? $param['type'] : 1;
        $this->model->delFollowById($id, $type);
    }
}
