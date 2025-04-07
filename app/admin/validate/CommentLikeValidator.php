<?php

declare(strict_types=1);

namespace app\admin\validate;

use think\Validate;
use app\admin\model\{
    User,
    Comment,
    CommentReply,
    CommentLike
};

class CommentLikeValidator extends Validate
{
    protected $rule = [
        'user_id'     => 'require|checkUser',
        'target_type' => 'require|in:1,2',
        'target_id'   => 'require|checkTarget|checkDuplicate',
    ];

    protected $message = [
        'user_id.require'     => '用户必须登录',
        'user_id.checkUser'   => '用户不存在',
        'target_type.require' => '点赞类型必须指定',
        'target_type.in'      => '非法点赞类型',
        'target_id.require'   => '必须指定点赞目标',
        'target_id.checkTarget' => '目标不存在或不可点赞',
        'target_id.checkDuplicate' => '不能重复点赞',
    ];

    protected $scene = [
        'create' => ['user_id', 'target_type', 'target_id'],
    ];

    protected function checkUser($value)
    {
        return User::where('id', $value)->count() > 0;
    }

    protected function checkTarget($value, $rule, $data)
    {
        switch ($data['target_type']) {
            case 1: // 评论
                return Comment::where('id', $value)
                    ->where('status', 1)
                    ->count() > 0;
            case 2: // 回复
                return CommentReply::where('id', $value)
                    ->where('status', 1)
                    ->count() > 0;
            default:
                return false;
        }
    }

    protected function checkDuplicate($value, $rule, $data)
    {
        return CommentLike::where([
            'user_id'     => $data['user_id'],
            'target_type' => $data['target_type'],
            'target_id'   => $data['target_id']
        ])->count() === 0;
    }
}
