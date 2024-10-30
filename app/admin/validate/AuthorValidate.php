<?php

namespace app\admin\validate;
use think\Validate;

class AuthorValidate extends Validate
{
    protected $rule = [
    'nickname' => 'require',
    'mobile' => 'require',
];

    protected $message = [
    'nickname.require' => '昵称不能为空',
    'mobile.require' => '手机不能为空',
];
}