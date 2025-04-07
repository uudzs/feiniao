<?php

declare(strict_types=1);

namespace app\admin\validate;

use think\Validate;
use app\admin\model\{Comment, CommentReply};

class CommentReplyValidator extends Validate
{
    protected $rule = [
        'reply_type' => 'require|in:1,2',
        'parent_id'  => 'requireIf:reply_type,2|checkParent',
        'comment_id' => 'require|checkCommentExist|checkRateLimit'
    ];

    protected $message = [
        'reply_type.require' => '回复类型必须指定',
        'reply_type.in'      => '非法回复类型',
        'parent_id.requireIf' => '回复子评论必须指定父回复',
        'comment_id.checkCommentExist' => '指定评论不存在',
        'comment_id.checkRateLimit' => '操作过于频繁，请5分钟后再试'
    ];

    // 验证父回复是否存在（当reply_type=2时）
    protected function checkParent($value, $rule, $data)
    {
        if ($data['reply_type'] == 2) {
            return CommentReply::where('id', $value)->find() !== null;
        }
        return true;
    }

    protected function checkCommentExist($value)
    {
        return Comment::where('id', $value)->count() > 0;
    }

    protected function checkRateLimit($value, $rule, $data)
    {
        $count = CommentReply::where('user_id', $data['user_id'])
            ->whereTime('create_time', '>=', date('Y-m-d H:i:s', strtotime('-5 minutes')))
            ->count();

        return $count < 10;
    }
}
