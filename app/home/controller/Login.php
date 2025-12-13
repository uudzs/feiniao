<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\View;

class Login extends BaseController
{
    //登录
    public function index()
    {
        // 获取来路URL
        $cookieKey = 'refererKey';
        $refererUrl = Request::instance()->server('HTTP_REFERER', '');
        if (!empty($refererUrl) && strpos($refererUrl, 'register') === false && strpos($refererUrl, 'login') === false) {
            Cookie::set($cookieKey, $refererUrl);
        }
        if (Cookie::has($cookieKey)) $refererUrl = Cookie::get($cookieKey);
        if (!empty($refererUrl) && (strpos($refererUrl, 'register') !== false || strpos($refererUrl, 'login') !== false)) {
            $refererUrl = furl('/', [], true, 'home');
        }
        $refererUrl = $refererUrl ? $refererUrl : furl('/', [], true, 'home');
        View::assign('refererUrl', $refererUrl);
        add_user_log('view', '登录页面');
        return View();
    }

    public function register()
    {
        return View();
    }
}
