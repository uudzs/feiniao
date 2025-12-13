<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\View;

class Index extends BaseController
{
    public function index()
    {
        if ($this->usecache()) $this->makecache(View::fetch('index'));
        return View('index');
    }

    public function app()
    {
        if ($this->usecache()) $this->makecache(View::fetch('app'));
        return View('app');
    }
}
