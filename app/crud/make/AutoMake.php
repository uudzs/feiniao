<?php

namespace app\crud\make;

use app\crud\make\ToAutoMake;

class AutoMake
{
    protected $create;

    public function executeText(ToAutoMake $obj)
    {
        $this->create = $obj;
    }

    public function executeCreate($flag, $path, $other)
    {
        $this->create->check($flag, $path);
        $this->create->make($flag, $path, $other);
    }
}