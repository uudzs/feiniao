<?php
namespace app\admin\validate;
use think\Validate;

class VipLogValidate extends Validate
{
    protected $rule = [
    'user_id' => 'require',
    'order_id' => 'require',
];

    protected $message = [
    'user_id.require' => '用户ID不能为空',
    'order_id.require' => '订单ID不能为空',
];
}