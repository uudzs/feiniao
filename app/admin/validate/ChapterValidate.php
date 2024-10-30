<?php

namespace app\admin\validate;
use think\Validate;

class ChapterValidate extends Validate
{
    protected $rule = [
    'bookid' => 'require',
    'title' => 'require',
    'authorid' => 'require',
];

    protected $message = [
    'bookid.require' => '作品ID不能为空',
    'title.require' => '章节标题不能为空',
    'authorid.require' => '作者ID不能为空',
];
}