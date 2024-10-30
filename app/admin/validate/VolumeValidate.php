<?php

namespace app\admin\validate;
use think\Validate;

class VolumeValidate extends Validate
{
    protected $rule = [
    'bookid' => 'require',
];

    protected $message = [
    'bookid.require' => '作品ID不能为空',
];
}