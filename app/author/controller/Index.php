<?php
declare(strict_types=1);

namespace app\author\controller;

use think\facade\Route;

class Index
{
    //首页
    public function index()
    {
        if (!get_system_config('power', 'author_open')) {
            throw new \think\exception\HttpException(404, '作者功能未开启！');
        }
        if (!empty(get_login_author('id'))) {
            $url = (string) Route::buildUrl('user/index');
            redirect($url)->send();
        } else {
            $url = (string) Route::buildUrl('login/index');
            redirect($url)->send();
        }
    }
}
