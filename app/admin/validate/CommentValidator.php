<?php

declare(strict_types=1);

namespace app\admin\validate;

use think\Validate;
use app\admin\model\Book;
use app\admin\model\User;
use app\admin\model\Chapter;
use util\XssFilter;

class CommentValidator extends Validate
{
    protected $rule = [
        'user_id'     => 'require|number|checkUser',
        'target_type' => 'require|in:1,2', // 1-小说 2-章节
        'target_id'   => 'require|checkTarget',
        'content'     => 'require|min:5|max:500|checkContent',
    ];

    protected $message = [
        'user_id.require'     => '用户必须登录',
        'target_type.require' => '评论类型必须指定',
        'target_type.in'      => '非法评论类型',
        'target_id.require'   => '必须指定评论目标',
        'content.require'     => '评论内容不能为空',
        'content.min'         => '评论至少5个字',
        'content.max'         => '评论最多500字',
    ];

    // 验证目标有效性
    protected function checkTarget($value, $rule, $data)
    {
        switch ($data['target_type']) {
            case 1: // 小说
                return Book::where('id', $value)->count() > 0;
            case 2: // 章节
                return Chapter::where('id', $value)->count() > 0;
            default:
                return false;
        }
    }

    // 内容安全验证
    protected function checkContent($value)
    {
        // 调用XSS过滤服务
        $cleanContent = XssFilter::purify($value);
        return $cleanContent === $value;
    }

    // 用户有效性验证（示例）
    protected function checkUser($value)
    {
        return User::where('id', $value)->count() > 0;
    }
}
