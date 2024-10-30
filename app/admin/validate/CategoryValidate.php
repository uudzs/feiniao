<?php

namespace app\admin\validate;
use think\Validate;

class CategoryValidate extends Validate
{
    protected $rule = [
    'name' => 'require',
];

    protected $message = [
    'name.require' => '标题不能为空',
];
}