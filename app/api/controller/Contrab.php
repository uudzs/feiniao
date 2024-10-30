<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\BaseController;
use app\api\middleware\Auth;
use think\facade\Db;

class Contrab extends BaseController
{

    /**
     * 控制器中间件 [不需要鉴权]
     * @var array
     */
    protected $middleware = [
        Auth::class => ['except' => ['run']]
    ];

    /**
     * Summary of run
     * 计划任务
     * @return void
     */
    public static function run()
    {
        // 计划任务
    }
}
