<?php

namespace app\common\model;

use think\Model;

class SearchLog extends Model
{
    protected $name = 'search_log';

    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 搜索类型常量
    const TYPE_NOVEL = 1;
    const TYPE_AUTHOR = 2;

    // 客户端类型常量
    const CLIENT_APP = 1;
    const CLIENT_WEB = 2;
    const CLIENT_AUTHOR = 3;
    const CLIENT_ADMIN = 4;

    /**
     * 获取最近一周热门搜索词
     * @param int $limit 返回数量
     * @return array
     */
    public static function getHotKeywords($limit = 10)
    {
        $startTime = strtotime('-7 days');

        return self::where('create_time', '>=', $startTime)
            ->where('type', self::TYPE_NOVEL)
            ->group('keyword')
            ->field('keyword, COUNT(*) as search_count')
            ->order('search_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
