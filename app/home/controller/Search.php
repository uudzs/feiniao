<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\View;

class Search extends BaseController
{
    public function index()
    {
        $param = get_params();
        $keyword = isset($param['keyword']) ? $param['keyword'] : '';
        View::assign('keyword', $keyword);
        return view();
    }
}
