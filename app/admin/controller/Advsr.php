<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Advsr as AdvsrModel;
use app\admin\validate\AdvsrValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;
use think\facade\Route;

class Advsr extends BaseController
{

    var $uid;
    var $model;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new AdvsrModel();
        $this->uid = get_login_admin('id');
    }
    /**
     * 数据列表
     */
    public function datalist()
    {
        $param = get_params();
        if (request()->isAjax()) {
            $where = [];
            if (!empty($param['keywords'])) {
                $where[] = ['a.title', 'like', '%' . $param['keywords'] . '%'];
            }
            if (!empty($param['pid'])) {
                $where[] = ['a.adver_id', '=', $param['pid']];
            }
            $param['order'] = 'a.level desc';
            $list = $this->model->getAdvsrList($where, $param);
            return table_assign(0, '', $list);
        } else {
            View::assign('pid', $param['pid']);
            return view();
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
                validate(AdvsrValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $adver = Db::name('adver')->where(['id' => $param['adver_id']])->find();
            if (empty($adver)) {
                return to_assign(1, '广告位不存在');
            }
            if (intval($adver['status']) != 1) {
                return to_assign(1, '广告位已禁用');
            }
            // $use = 0;
            // $list = Db::name('advsr')->where(['adver_id' => $adver['id'], 'status' => 1])->select()->toArray();
            // foreach ($list as $k => $v) {
            //     if (intval($v['end_time']) <= 0) {
            //         $use++;
            //         continue;
            //     }
            //     if (intval($v['end_time']) > 0 && $v['end_time'] > time()) {
            //         $use++;
            //         continue;
            //     }
            // }
            //$use = Db::name('advsr')->where(['adver_id' => $adver['id'], 'status' => 1, ['start_time', '<=', time()], ['end_time', '>', time()]])->count();
            // $viewnum = intval($adver['viewnum']);
            // if ($viewnum > 0 && $use > $viewnum) {
            //     return to_assign(1, '广告总数已超过广告位最大限制');
            // }
            if (isset($param["start_time"])) {
                $param["start_time"] = $param["start_time"] ? strtotime($param["start_time"]) : 0;
            }
            if (isset($param["end_time"])) {
                $param["end_time"] = $param["end_time"] ? strtotime($param["end_time"]) : 0;
            }
            if (intval($param['type']) == 1 && empty($param['books'])) {
                return to_assign(1, '请选择作品');
            }
            if (intval($param['type']) == 3 && empty($param['link'])) {
                return to_assign(1, '请填写链接地址');
            }
            if (intval($param['books']) > 0) {
                $book = Db::name('book')->where(['id' => intval($param['books'])])->find();
                if (empty($book)) {
                    return to_assign(1, '作品不存在');
                }
                if (empty($param['images'])) {
                    $param['images'] = $book['cover'];
                }
            }
            $param['introduction'] = mb_substr($param['introduction'], 0, 200, "UTF-8");
            // if (intval($param['type']) == 1) {
            //     $param['link'] = '/book-' . $param['books'] . '.html';
            // }
            unset($param['file']);
            $this->model->addAdvsr($param);
        } else {
            View::assign('pid', $param['pid']);
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
                validate(AdvsrValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $adver = Db::name('adver')->where(['id' => $param['adver_id']])->find();
            if (empty($adver)) {
                return to_assign(1, '广告位不存在');
            }
            if (intval($adver['status']) != 1) {
                return to_assign(1, '广告位已禁用');
            }
            // $use = 0;
            // $list = Db::name('advsr')->where(['adver_id' => $adver['id'], 'status' => 1])->select()->toArray();
            // foreach ($list as $k => $v) {
            //     if (intval($v['end_time']) <= 0) {
            //         $use++;
            //         continue;
            //     }
            //     if (intval($v['end_time']) > 0 && $v['end_time'] > time()) {
            //         $use++;
            //         continue;
            //     }
            // }
            // $viewnum = intval($adver['viewnum']);
            // if ($viewnum > 0 && $use > $viewnum) {
            //     return to_assign(1, '广告总数已超过广告位最大限制');
            // }
            if (isset($param["start_time"])) {
                $param["start_time"] = $param["start_time"] ? strtotime($param["start_time"]) : 0;
            }
            if (isset($param["end_time"])) {
                $param["end_time"] = $param["end_time"] ? strtotime($param["end_time"]) : 0;
            }
            if (intval($param['type']) == 1 && empty($param['books'])) {
                return to_assign(1, '请选择作品');
            }
            if (intval($param['type']) == 3 && empty($param['link'])) {
                return to_assign(1, '请填写链接地址');
            }
            if (intval($param['books']) > 0) {
                $book = Db::name('book')->where(['id' => intval($param['books'])])->find();
                if (empty($book)) {
                    return to_assign(1, '作品不存在');
                }
                if (empty($param['images'])) {
                    $param['images'] = $book['cover'];
                }
            }
            $param['introduction'] = mb_substr($param['introduction'], 0, 200, "UTF-8");
            // if (intval($param['type']) == 1) {
            //     $param['link'] = '/book-' . $param['books'] . '.html';
            // }
            unset($param['file']);
            $this->model->editAdvsr($param);
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getAdvsrById($id);
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
        $detail = $this->model->getAdvsrById($id);
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
        $this->model->delAdvsrById($id, 1);
    }
}
