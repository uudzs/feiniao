<?php
namespace app\admin\validate;
use think\Validate;

class LikeLogValidate extends Validate
{
    protected $rule = [
    'user_id' => 'require',
    'book_id' => 'require',
    'chapter_id' => 'require',
];

    protected $message = [
    'user_id.require' => '用户ID不能为空',
    'book_id.require' => '作品ID不能为空',
    'chapter_id.require' => '章节ID不能为空',
];
}