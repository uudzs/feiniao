<?php
namespace app\admin\validate;
use think\Validate;

class FollowValidate extends Validate
{
    protected $rule = [
    'user_id' => 'require',
];

    protected $message = [
    'user_id.require' => '关注人不能为空',
];
}