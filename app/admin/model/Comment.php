<?php

declare(strict_types=1);

namespace app\admin\model;

use think\model;
use util\XssFilter;

class Comment extends Model
{
    // 定义类型转换
    protected $type = [
        'target_type' => 'integer',
        'status'      => 'integer',
        'like_count'  => 'integer',
        'create_time' => 'datetime' // 仅保留需要的字段
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;
    // 添加虚拟字段
    protected $append = ['comment_id'];

    // 评论类型常量
    const TYPE_NOVEL   = 1; // 小说详情评论
    const TYPE_CHAPTER = 2; // 章节内容评论

    // 状态常量
    const STATUS_PENDING = 0; // 待审核
    const STATUS_APPROVED = 1; // 已通过

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class)->field(['id', 'nickname']);
    }

    public function replies()
    {
        return $this->hasMany(CommentReply::class, 'comment_id')
            ->where('parent_id', 0)
            ->order('create_time', 'asc')
            ->with(['childReplies' => function ($query) {
                // 递归加载所有子回复
                $query->with(['childReplies' => function ($q) {
                    $q->with('childReplies');
                }]);
            }, 'user']);
    }

    // 新增主评论关联（用于查找根评论）
    public function mainComment()
    {
        return $this->belongsTo(self::class, 'comment_id');
    }

    /**
     * 关联点赞记录
     */
    public function likes()
    {
        return $this->hasMany(CommentLike::class, 'target_id')
            ->where('target_type', self::TYPE_NOVEL);
    }

    /**
     * 内容获取器 - HTML安全过滤
     */
    public function getContentAttr($value)
    {
        return XssFilter::purify($value);
    }

    /**
     * 虚拟字段获取器 - 始终返回0
     */
    public function getCommentIdAttr()
    {
        return 0;
    }
}
