<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Db;
use think\facade\View;

class Book extends BaseController
{

    /**
     * 作品分类
     * Summary of cate
     * @return \think\response\View
     */
    public function cate()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        $category = Db::name('category')->where(['id' => $id])->find();
        View::assign('category', $category);
        View::assign('catid', $id);
        View::assign('id', $id);
        return view();
    }

    /**
     * 列表
     * Summary of list
     */
    public function list()
    {
        return view();
    }

    /**
     * 作品详情
     * Summary of detail
     */
    public function detail()
    {
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        View::assign('bid', $id);
        return view();
    }
}
