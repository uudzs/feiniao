<?php

namespace app\common\model;

use think\Model;
use app\service\CacheService;

class Chapter extends Model
{
    protected $name = 'chapter';

    // 自动时间
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 章节状态常
    const STATUS_DRAFT = 0;     // 草稿
    const STATUS_PUBLISHED = 1; // 发布
    const STATUS_BLOCKED = 2;   // 屏蔽

    /**
     * 关联小说模型
     */
    public function book()
    {
        return $this->belongsTo(Novel::class, 'bookid');
    }

    /**
     * 获取章节详情（带缓存）
     * @param int $id
     * @return array|null
     */
    public static function getChapterDetail($id)
    {
        $cacheKey = 'chapter_' . $id;
        return CacheService::remember($cacheKey, function () use ($id) {
            return self::where('id', $id)->find();
        });
    }
}
