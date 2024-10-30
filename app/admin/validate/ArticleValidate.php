<?php

namespace app\admin\validate;
use think\Validate;

class ArticleValidate extends Validate
{
    protected $rule = [
    'cate_id' => 'require',
    'title' => 'require',
    //'thumb' => 'require',
    'content' => 'require',
];

    protected $message = [
    'cate_id.require' => '所属分类不能为空',
    'title.require' => '文章标题不能为空',
    //'thumb.require' => '缩略图不能为空',
    'content.require' => '文章内容不能为空',
];
}