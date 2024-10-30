<?php

namespace app\admin\validate;
use think\Validate;

class BankCardValidate extends Validate
{
    protected $rule = [
    'card_no' => 'require',
    'user_id' => 'require',
];

    protected $message = [
    'card_no.require' => '卡号不能为空',
    'user_id.require' => '用户id不能为空',
];
}