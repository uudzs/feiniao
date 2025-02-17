<?php
namespace app\admin\validate;
use think\Validate;

class AppVersionValidate extends Validate
{
    protected $rule = [
    'edition_url' => 'require',
    'edition_number' => 'require',
];

    protected $message = [
    'edition_url.require' => '包地址不能为空',
    'edition_number.require' => '版本号不能为空',
];
}