<?php

namespace app\crud\make;

interface ToAutoMake
{
    public function check($flag, $path);

    public function make($flag, $path, $other);
}