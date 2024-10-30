<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Request;
use think\facade\Cookie;
use think\facade\Route;

class Invite extends BaseController
{
    /**
     * 邀请首页
     * Summary of index
     * @return \think\response\View
     */
    public function index()
    {
        //邀请
        $invite = Request::param('name');
        if (!empty($invite)) {
            $session_invite = get_config('app.session_invite');
            Cookie::set($session_invite, $invite);
            redirect((string) Route::buildUrl('/'))->send();
        }
        return view();
    }
}
