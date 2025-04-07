<?php

declare(strict_types=1);

namespace app\admin\model;

use think\model;

class CommentLike extends Model
{

    // 定义关联类型常量
    const TARGET_COMMENT = 1; // 主评论
    const TARGET_CHAPTER_COMMENT = 2; // 章节评论
    const TARGET_REPLY = 3; // 回复内容

    protected $morph = [
        'target_type' => 'type', // 类型字段名
        'target_id'   => 'id'    // 关联ID字段名
    ];

    // 定义自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    /**
     * 多态关联（兼容PHPStorm智能提示）
     * @return \think\model\relation\MorphTo
     */
    public function target()
    {
        return $this->morphTo(null, [
            self::TARGET_COMMENT => Comment::class,
            self::TARGET_CHAPTER_COMMENT => Comment::class,
            self::TARGET_REPLY => CommentReply::class
        ]);
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
