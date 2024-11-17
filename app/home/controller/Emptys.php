<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;

class Emptys extends BaseController
{

    public function miss()
    {
        hook("makehtml");
        return view('404');
    }

    public function pageupdate()
    {
        return hook("makehtml", ['type' => 'update']);
    }
}
