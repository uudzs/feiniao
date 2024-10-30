<?php
namespace app\admin\validate;
use think\Validate;

class ReportValidate extends Validate
{
    protected $rule = [
    'introduce' => 'require',
];

    protected $message = [
    'introduce.require' => '举报内容不能为空',
];
}