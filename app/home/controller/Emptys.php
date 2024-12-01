<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Request;
use think\facade\View;

class Emptys extends BaseController
{
    public function miss()
    {
        if (strpos(Request::url(), 'sitemap.xml') !== false) {
            return View('sitemap');
        }
        return view('404');
    }
}