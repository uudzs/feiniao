<?php

namespace app\common\model;

use think\Model;
use app\common\model\Category;
use think\Paginator;
use app\service\CacheService;

class Rank extends Model
{

    protected $name = 'book';
    // 榜单类型配置
    const RANK_CONFIG = [
        'hits' => [
            'name' => '人气榜',
            'field' => 'hits',
            'order' => 'hits DESC',
            'cache' => 300 // 5分钟
        ],
        'new' => [
            'name' => '新书榜',
            'field' => 'GREATEST(create_time, update_time) as new_time',
            'order' => 'new_time DESC',
            'cache' => 600 // 10分钟
        ],
        'comments' => [
            'name' => '点评榜',
            'field' => 'comments',
            'order' => 'comments DESC',
            'cache' => 1800 // 30分钟
        ],
        'chapters' => [
            'name' => '章节榜',
            'order' => 'chapters DESC',
            'cache' => 3600 // 1小时
        ],
        'finish' => [
            'name' => '完本榜',
            'where' => ['isfinish' => 2],
            'order' => 'finishtime DESC',
            'cache' => 3600 // 1小时
        ],
        'words' => [
            'name' => '字数榜',
            'order' => 'words DESC',
            'cache' => 86400 // 24小时
        ]
    ];

    /**
     * 获取总榜所有榜单的Top10数据
     */
    public function getMainRankData(string $channel, int $cid): array
    {
        $cacheKey = "main_rank_{$channel}_{$cid}";
        return CacheService::remember($cacheKey, function () use ($channel, $cid) {
            $data = [];
            foreach (array_keys(self::RANK_CONFIG) as $type) {
                $data[$type] = $this->getRankQuery($channel, $type, $cid, 0)
                    ->limit(10)
                    ->select()
                    ->each(function ($item) {
                        $item->cover = get_file($item->cover);
                        return $item;
                    })
                    ->toArray();
            }
            return $data;
        }, 0);
    }

    /**
     * 获取单个榜单分页数据
     */
    public function getRankDetail(string $channel, string $type, int $cid): Paginator
    {
        $page = request()->param('page', 1);
        if ($page > 3) $page = 3;
        $cacheKey = "rank_detail_{$channel}_{$type}_{$cid}_{$page}";
        return CacheService::remember($cacheKey, function () use ($channel, $type, $cid, $page) {
            return $this->getRankQuery($channel, $type, $cid, 1)
                ->paginate(
                    10,
                    true,
                    [
                        'var_page' => 'page',
                        'page' => $page,
                    ]
                );
        }, 0);
    }

    /**
     * 构建基础查询
     */
    private function getRankQuery(string $channel, string $type, int $cid, int $isremark)
    {
        $query = $this->where('status', 1);
        // 频道筛选
        if ($channel === 'female') {
            if ($cid > 0) {
                $query->where('subgenre', $cid);
            } else {
                $query->where('genre', Category::FEMALE_CATEGORY_ID);
            }
        } else {
            if ($cid > 0) {
                $query->where('genre', $cid);
            } else {
                $query->where('genre', '<>', Category::FEMALE_CATEGORY_ID);
            }
        }
        if ($isremark > 0) {
            $baseFields = 'id,title,author,authorid,filename,genre,subgenre,cover,hits,words,comments,chapters,isfinish,finishtime,create_time,remark';
        } else {
            $baseFields = 'id,title,author,authorid,filename,genre,subgenre,cover,hits,words,comments,chapters,isfinish,finishtime,create_time';
        }
        $config = self::RANK_CONFIG[$type];
        $query->field($baseFields);
        if (!empty($config['field'])) {
            $query->field($config['field'], true);
        }
        if (!empty($config['where'])) {
            $query->where($config['where']);
        }
        $query->order($config['order']);
        return $query;
    }

    /**
     * 获取实时热门小说（按点击量）
     * @param int $limit
     * @param string $timeRange
     * @return array
     */
    public function getHotBooksRealtime(int $limit = 10, string $timeRange = 'today'): array
    {
        $cacheKey = "hot_realtime_{$timeRange}_{$limit}";
        return CacheService::remember($cacheKey, function () use ($limit, $timeRange) {
            $where = ['status' => 1];
            // 根据时间范围添加条件
            switch ($timeRange) {
                case 'today':
                    $where[] = ['update_time', '>=', strtotime('today')];
                    break;
                case 'week':
                    $where[] = ['update_time', '>=', strtotime('-1 week')];
                    break;
                case 'month':
                    $where[] = ['update_time', '>=', strtotime('-1 month')];
                    break;
            }
            return $this->where($where)
                ->field('id,title,author,authorid,filename,cover,hits,words,chapters,update_time')
                ->order('hits desc, update_time desc')
                ->limit($limit)
                ->select()
                ->each(function ($item) {
                    $item->cover = get_file($item->cover);
                    return $item;
                })
                ->toArray();
        }, 0);
    }

    /**
     * 获取分类热门小说
     * @param int $categoryId
     * @param int $limit
     * @return array
     */
    public function getCategoryHotBooks(int $categoryId, int $limit = 10): array
    {
        $cacheKey = "category_hot_{$categoryId}_{$limit}";
        return CacheService::remember($cacheKey, function () use ($categoryId, $limit) {
            return $this->where([
                'status' => 1,
                'genre' => $categoryId
            ])
                ->field('id,title,author,authorid,filename,cover,hits,words,chapters')
                ->order('hits desc')
                ->limit($limit)
                ->select()
                ->each(function ($item) {
                    $item->cover = get_file($item->cover);
                    return $item;
                })
                ->toArray();
        }, 0);
    }
}
