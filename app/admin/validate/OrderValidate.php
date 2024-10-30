<?php

namespace app\admin\validate;
use think\Validate;

class OrderValidate extends Validate
{
    protected $rule = [
    'order_id' => 'require',
    'user_id' => 'require',
];

    protected $message = [
    'order_id.require' => '订单号不能为空',
    'user_id.require' => '用户id不能为空',
];
}