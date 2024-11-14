<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\BaseController;
use app\api\middleware\Auth;

class Contrab extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['run', 'caiji']]
    ];

    /**
     * Summary of caiji
     * 采集任务
     * @return void
     */
    public static function caiji()
    {
        $result = hook("caijiHook");
    }

    /**
     * Summary of run
     * 计划任务
     * @return void
     */
    public static function run() {

    }
}
