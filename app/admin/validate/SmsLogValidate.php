<?php
namespace app\admin\validate;
use think\Validate;

class SmsLogValidate extends Validate
{
    protected $rule = [
    'code' => 'require',
    'account' => 'require',
];

    protected $message = [
    'code.require' => '最后发送成功的验证码不能为空',
    'account.require' => '手机号或者邮箱不能为空',
];
}