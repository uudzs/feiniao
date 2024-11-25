<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Db;
use think\facade\View;
use think\facade\Request;

class Author extends BaseController
{

    /**
     * 作者详情
     * Summary of detail
     */
    public function detail()
    {
        hook("runcache");
        $param = get_params();
        $id = isset($param['id']) ? $param['id'] : 0;
        View::assign('id', $id);
        hook("makehtml", ['content' => View::fetch()]);
        return view();
    }
}
