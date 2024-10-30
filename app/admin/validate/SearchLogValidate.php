<?php
namespace app\admin\validate;
use think\Validate;

class SearchLogValidate extends Validate
{
    protected $rule = [
    'type' => 'require',
];

    protected $message = [
    'type.require' => '类型不能为空',
];
}