<?php

namespace app\admin\validate;
use think\Validate;

class CoinLogValidate extends Validate
{
    protected $rule = [
    'user_id' => 'require',
    'amount' => 'require',
];

    protected $message = [
    'user_id.require' => '用户ID不能为空',
    'amount.require' => '数目不能为空',
];
}