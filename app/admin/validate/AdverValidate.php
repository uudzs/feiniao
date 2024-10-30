<?php

namespace app\admin\validate;
use think\Validate;

class AdverValidate extends Validate
{
    protected $rule = [
    'title' => 'require',
    'channel' => 'require',
    'type' => 'require',
];

    protected $message = [
    'title.require' => '名称不能为空',
    'channel.require' => '展示频道不能为空',
    'type.require' => '类型不能为空',
];
}