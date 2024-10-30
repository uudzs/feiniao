<?php
namespace app\admin\validate;
use think\Validate;

class SignLogValidate extends Validate
{
    protected $rule = [
    'user_id' => 'require',
];

    protected $message = [
    'user_id.require' => '用户ID不能为空',
];
}