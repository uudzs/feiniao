<?php

namespace app\common\model;

use think\Model;
use app\common\model\Category;
use app\common\model\SearchLog;

class Novel extends Model
{
    protected $name = 'book';

    // 预定义筛选条件映射
    const CONDITION_MAP = [
        'status' => [
            'a' => ['name' => '全部', 'value' => 0],
            't' => ['name' => '连载', 'value' => 1],
            'f' => ['name' => '完结', 'value' => 2],
            'd3' => ['name' => '三日更新', 'type' => 'time'],
            'd7' => ['name' => '七日更新', 'type' => 'time']
        ],
        'word_range' => [
            0 => ['name' => '全部', 'value' => [0, PHP_INT_MAX]],
            20 => ['name' => '30万以内', 'value' =>  [0, 300000]],
            40 => ['name' => '30万-100万', 'value' => [300000, 1000000]],
            60 => ['name' => '100万-200万', 'value' => [1000000, 2000000]],
            80 => ['name' => '200万以上', 'value' => [2000000, PHP_INT_MAX]]
        ],
        'order' => [
            'a' => ['field' => 'sort', 'name' => '默认', 'type' => 'desc'],
            'h' => ['field' => 'hits', 'name' => '周人气', 'type' => 'desc'],
            'u' => ['field' => 'update_time', 'name' => '最近更新', 'type' => 'desc'],
            'n' => ['field' => 'create_time', 'name' => '最新发布', 'type' => 'desc'],
            'w' => ['field' => 'words', 'name' => '字数最多', 'type' => 'desc']
        ]
    ];

    public static function getList($channelType, $filter)
    {
        // 获取基础查询条件
        $query = self::getBaseQuery($channelType);

        // 主分类筛选
        $query->when($filter['cat'] > 0, function ($q) use ($channelType, $filter) {
            $field = ($channelType === 2) ? 'subgenre' : 'genre';
            $q->where($field, $filter['cat']);
        });

        // 扩展分类筛选（根据cid参数）
        $query->when($filter['cid'] > 0, function ($q) use ($filter) {
            $q->where('subgenre', $filter['cid']);
        });

        // 处理状态/时间筛选
        self::applyStatusFilter($query, $filter['status']);

        // 应用字数筛选
        $query->whereBetween('words', self::CONDITION_MAP['word_range'][$filter['word']]['value']);

        // 应用排序
        $orderConfig = self::CONDITION_MAP['order'][$filter['order']];
        $query->order($orderConfig['field'], $orderConfig['type']);

        return $query->paginate(30, true, [
            'var_page' => 'page',
            'page' => $filter['page'],
            'path' => self::buildPageUrl($channelType, $filter)
        ]);
    }

    /**
     * 构建基础查询条件
     */
    private static function getBaseQuery($channelType)
    {
        // 女频使用子分类，男频使用父分类
        if ($channelType === 2) {
            return self::where('status', 1)->where('genre', Category::FEMALE_CATEGORY_ID);
        }
        return self::where('status', 1)->where('genre', '<>', Category::FEMALE_CATEGORY_ID);
    }

    private static function applyStatusFilter(&$query, $status)
    {
        $statusConfig = self::CONDITION_MAP['status'][$status];

        if (isset($statusConfig['value'])) {
            if ($statusConfig['value'] > 0) {
                $query->where('isfinish', $statusConfig['value']);
            }
        } elseif ($statusConfig['type'] === 'time') {
            $days = substr($status, 1);
            $query->whereTime('update_time', '>=', '-' . $days . ' days');
        }
    }

    private static function buildPageUrl($channelType, $filter)
    {
        return sprintf(
            "novel-%d-%s-%d-%d-%s-%d-%d-%d.html",
            $channelType,
            $filter['status'],
            $filter['cat'],
            $filter['word'],
            $filter['order'],
            $filter['page'],
            $filter['cid'],
            $filter['mode']
        );
    }

    /**
     * 搜索小说（支持标题和作者模糊匹配）
     * @param string $keyword 搜索关键词
     * @param int $page 当前页码
     * @param int $limit 每页条数
     * @return array 包含分页数据和高亮结果
     */
    public static function search($keyword, $page = 1, $limit = 20)
    {
        // 基础查询（只查有效小说）
        $query = self::where('status', 1)->field('id,title,author,filename,cover,hits,words,isfinish,remark');

        // 关键词拆分为数组，支持多词模糊匹配
        $keywords = self::splitKeywords($keyword);

        // 同时匹配 title 和 author 字段
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $word) {
                $q->whereOr([
                    ['title', 'like', "%{$word}%"],
                    ['author', 'like', "%{$word}%"]
                ]);
            }
        });

        $total = $query->count();

        $paginator = $query->order('hits', 'desc')
            ->paginate($limit, true, [
                'list_rows' => $limit,
                'page' => $page,
                'path' => (string) url('search')
            ]);

        $paginator->appends(['keyword' => $keyword]);

        // 高亮处理（遍历分页数据）
        $paginator->each(function ($item) use ($keyword) {
            $item->title = self::highlightKeyword($item->title, $keyword);
            $item->author = self::highlightKeyword($item->author, $keyword);
            return $item;
        });
        // 记录搜索日志
        try {
            SearchLog::create([
                'type' => SearchLog::TYPE_NOVEL,
                'client' => SearchLog::CLIENT_WEB,
                'keyword' => $keyword,
                'user_id' => defined('JWT_UID') ? JWT_UID : 0,
                'resnum' => $total, // 使用手动查询的总数
                'create_time' => time()
            ]);
        } catch (\Exception $e) {
        }
        return [
            'total' => $total, // 总记录数
            'list' => $paginator,           // 分页对象（含数据）
            'keyword' => $keyword
        ];
    }

    /**
     * 拆分关键词（支持中文和英文分词）
     */
    private static function splitKeywords($keyword)
    {
        // 移除多余空格和特殊字符
        $keyword = trim(preg_replace('/[^\w\x{4e00}-\x{9fa5}]+/u', ' ', $keyword));

        // 中文处理：如果无空格且长度>2，按2字一组拆分（避免单字匹配）
        if (preg_match('/^[\x{4e00}-\x{9fa5}]{2,}$/u', $keyword)) {
            return [
                $keyword, // 保留完整词（优先匹配）
                ...mb_str_split($keyword, 2) // 拆分为双字组（如"修仙传" => ["修仙", "仙传"]）
            ];
        }

        // 默认按空格分割（英文或混合词）
        return array_filter(explode(' ', $keyword));
    }

    /**
     * 高亮关键词
     */
    private static function highlightKeyword($text, $keyword)
    {
        $keywords = self::splitKeywords($keyword);
        foreach ($keywords as $word) {
            $text = preg_replace(
                "/(" . preg_quote($word, '/') . ")/iu",
                '<span class="highlight">$1</span>',
                $text
            );
        }
        return $text;
    }

    /**
     * 获取热门搜索词对应的作品列表
     * @param int $limit 每个搜索词返回的作品数量
     * @return array
     */
    public static function getHotKeywordNovels($limit = 5)
    {
        // 1. 获取热门搜索词
        $hotKeywords = SearchLog::getHotKeywords(1);

        $result = [];

        // 2. 为每个热门搜索词查询作品
        foreach ($hotKeywords as $keyword) {
            $novels = self::where('status', 1)
                ->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', '%' . $keyword['keyword'] . '%')
                        ->whereOr('author', 'like', '%' . $keyword['keyword'] . '%');
                })
                ->field('id,title,author,filename,cover,hits,words,isfinish,remark')
                ->order('hits', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();

            if (!empty($novels)) {
                $result = $novels;
            }
        }

        return $result;
    }
}
