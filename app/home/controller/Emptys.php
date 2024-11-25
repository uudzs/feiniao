<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;

class Emptys extends BaseController
{

    public function miss()
    {
        return view('404');
    }
}
