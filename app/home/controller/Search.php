<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use app\common\model\Novel;
use think\facade\Request;

class Search extends BaseController
{
    public function index()
    {
        $keyword = Request::get('keyword', '');
        $page = Request::get('page', 1);
        $result = Novel::search($keyword, $page);
        $result['hotNovels'] = Novel::getHotKeywordNovels(10);
        return view('index', $result);
    }
}
