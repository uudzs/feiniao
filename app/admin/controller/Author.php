<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\model\Author as AuthorModel;
use app\admin\validate\AuthorValidate;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\View;

class Author extends BaseController
{

    var $uid;
    var $model;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new AuthorModel();
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
            if (!empty($param['keywords'])) {
                $where[] = ['nickname|true_name|mobile', 'like', '%' . $param['keywords'] . '%'];
            }
            //按时间检索
            $start_time = isset($param['start_time']) ? strtotime(urldecode($param['start_time'])) : 0;
            $end_time = isset($param['end_time']) ? strtotime(urldecode($param['end_time'])) : 0;

            if ($start_time > 0 && $end_time > 0) {
                if ($start_time === $end_time) {
                    $where[] = ['create_time', '=', $start_time];
                } else {
                    $where[] = ['create_time', '>=', $start_time];
                    $where[] = ['create_time', '<=', $end_time];
                }
            } elseif ($start_time > 0 && $end_time == 0) {
                $where[] = ['create_time', '>=', $start_time];
            } elseif ($start_time == 0 && $end_time > 0) {
                $where[] = ['create_time', '<=', $end_time];
            }
            $list = $this->model->getAuthorList($where, $param);
            return table_assign(0, '', $list);
        } else {
            return view();
        }
    }

    public function authorlist()
    {
        return view();
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
                validate(AuthorValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            if (isset($param["birth"])) {
                $param["birth"] = $param["birth"] ? strtotime($param["birth"]) : 0;
            }
            if (empty($param['password'])) {
                return to_assign(1, '请填写密码！');
            }
            $time = (string) time();
            $salt = substr(MD5($time), 0, 6);
            $param['salt'] = $salt;
            $param['password'] = sha1(MD5($param['password']) . $salt);
            $this->model->addAuthor($param);
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
                validate(AuthorValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            if (isset($param["birth"])) {
                $param["birth"] = $param["birth"] ? strtotime($param["birth"]) : 0;
            }
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getAuthorById($id);
            if (empty($detail)) {
                return to_assign(1, '信息不存在');
            }
            if (!empty($param['password'])) {
                $param['password'] = sha1(MD5($param['password']) . $detail['salt']);
            } else {
                unset($param['password']);
            }
            if ($detail['nickname'] != $param['nickname']) {
                $param['update_time'] = time();
                Db::name('author')->where('id', $id)->strict(false)->field(true)->update($param);
                Db::name('book')->where('authorid', $id)->update(['author' => $param['nickname']]);
                return to_assign(0, '笔名修改成功');
            } else {
                unset($param['file']);
                $this->model->editAuthor($param);
            }
        } else {
            $id = isset($param['id']) ? $param['id'] : 0;
            $detail = $this->model->getAuthorById($id);
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
        $detail = $this->model->getAuthorById($id);
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

        $this->model->delAuthorById($id, $type);
    }
}
