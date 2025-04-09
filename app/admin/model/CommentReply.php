<?php

declare(strict_types=1);

namespace app\admin\model;

use think\model;

class CommentReply extends Model
{
    protected $type = [
        'parent_id'   => 'integer',
        'status'      => 'integer',
        'like_count'  => 'integer',
        'create_time' => 'datetime'
    ];

    protected $allowedFields = ['comment_id', 'user_id', 'parent_id', 'content'];
    protected $updateTime = false;
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';

    // 状态常量
    const STATUS_PENDING = 0; // 待审核
    const STATUS_APPROVED = 1; // 已通过

    /**
     * 关联主评论
     */
    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function childReplies()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->order('create_time', 'asc')
            ->with(['childReplies' => function ($query) {
                $query->with(['user', 'childReplies']);
            }, 'user']);
    }

    // 简化关联方法（移除重复定义）
    public function parentReply()
    {
        return $this->belongsTo(self::class, 'parent_id')->with(['user']); // 加载父回复的用户信息
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class)->field(['id', 'nickname']);
    }

    /**
     * 父级回复
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 子回复
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->order('create_time', 'asc');
    }
}
