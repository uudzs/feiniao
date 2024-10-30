<?php

declare(strict_types=1);

namespace app\admin\controller;

use think\captcha\facade\Captcha;

class Verify
{
    public function verify()
    {
        return Captcha::create();
    }
}
