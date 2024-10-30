<?php

namespace app\admin\validate;
use think\Validate;

class ChapterVerifyValidate extends Validate
{
    protected $rule = [
    'bid' => 'require',
];

    protected $message = [
    'bid.require' => '作品ID不能为空',
];
}