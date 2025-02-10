<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Category as CategoryModel;
use app\admin\validate\CategoryValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;
use Overtrue\Pinyin\Pinyin;

class Category extends BaseController
{
    var $uid;
    var $model;
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new CategoryModel();
        $this->uid = get_login_admin('id');
    }
    /**
     * 数据列表
     */
    public function datalist()
    {
        if (request()->isAjax()) {
            $list = $this->model->where('status', 1)->order('ordernum asc')->select()->toArray();
            foreach ($list as $k => $v) {
                if (intval($v['pid']) > 0) {
                    $list[$k]['name'] = $v['name'] . '(' . Db::name('book')->where(['subgenre' => $v['id']])->count() . ')';
                } else {
                    $list[$k]['name'] = $v['name'] . '(' . Db::name('book')->where(['genre' => $v['id']])->count() . ')';
                }
            }
            return to_assign(0, '', $list);
        } else {
            return view();
        }
    }

    public function getsmallcate()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $pid = intval($param['pid']);
            $list = [];
            if (empty($pid)) {
                return to_assign(1, '参数错误');
            }
            $list = Db::name('category')->where(['pid' => $pid, 'status' => 1])->order('ordernum asc')->select()->toArray();
            return table_assign(0, '', ['data' => $list]);
        } else {
            return to_assign(1, '错误');
        }
    }

    /**
     * 添加
     */
    public function add()
    {
        $param = get_params();
        if (request()->isAjax()) {
            // 检验完整性
            try {
                validate(CategoryValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            if (empty($param['key'])) {
                $param['key'] = Pinyin::permalink($param['name'], '');
            }
            $param['create_user_id'] = $this->uid;
            $this->model->addCategory($param);
        } else {
            $pid = isset($param['pid']) ? $param['pid'] : 0;
            View::assign('pid', $pid);
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
                validate(CategoryValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $detail = $this->model->getCategoryById($param['id']);
            if (empty($param['key']) || $detail['name'] != $param['name']) {
                $param['key'] = Pinyin::permalink($param['name'], '');
            }
            if ($param['id'] == $param['pid']) {
                $param['pid'] = 0;
            }
            $param['update_user_id'] = $this->uid;
            $this->model->editCategory($param);
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getCategoryById($id);
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
        $detail = $this->model->getCategoryById($id);
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
        $this->model->delCategoryById($id);
    }
}
