<?php
namespace app\admin\validate;
use think\Validate;

class WithdrawValidate extends Validate
{
    protected $rule = [
    'user_id' => 'require',
    'money' => 'require',
    'coin' => 'require',
];

    protected $message = [
    'user_id.require' => '提现人不能为空',
    'money.require' => '提现额不能为空',
    'coin.require' => '金币不能为空',
];
}