<?php

namespace app\common\model;

use think\Model;
use app\common\model\Category;
use app\common\model\SearchLog;
use app\service\CacheService;

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

    /**
     * 获取小说详情（带缓存）
     * @param int $id
     * @return array|null
     */
    public static function getBookDetail($id)
    {
        $cacheKey = 'book_' . $id;
        if (ctype_digit((string)$id)) {
            $result = CacheService::remember($cacheKey, function () use ($id) {
                return self::where('id', intval($id))->find();
            });
        } else {
            $result = CacheService::remember($cacheKey, function () use ($id) {
                return self::where('filename', $id)->find();
            });
        }
        if ($result instanceof \think\Model) {
            return $result->toArray();
        }
        return $result ?: [];
    }

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
        if ($filter['word'] > 0) {
            $query->whereBetween('words', self::CONDITION_MAP['word_range'][$filter['word']]['value']);
        }

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
     * 搜索小说（改进版：优先精确匹配）
     */
    public static function search($keyword, $page = 1, $limit = 20)
    {
        $query = self::where('status', 1)
            ->field('id,title,author,authorid,cover,style,ending,genre,subgenre,isfinish,finishtime,chapters,label,label_custom,hits,words,status,editor,editorid,issign,create_time,update_time,remark,filename');

        $keywords = self::splitKeywords($keyword);

        // 构建查询条件：优先精确匹配整个关键词
        $query->where(function ($q) use ($keywords, $keyword) {
            // 1. 首先尝试完整关键词精确匹配（权重最高）
            $q->whereOr([
                ['title', 'like', "%{$keyword}%"],
                ['author', 'like', "%{$keyword}%"]
            ]);

            // 2. 然后添加分词后的模糊匹配（权重较低）
            if (count($keywords) > 1) { // 只有当分词结果多于1个时才添加
                foreach ($keywords as $word) {
                    // 排除分词后的单字（避免噪声）
                    if (mb_strlen($word, 'UTF-8') <= 1) {
                        continue;
                    }
                    $q->whereOr([
                        ['title', 'like', "%{$word}%"],
                        ['author', 'like', "%{$word}%"]
                    ]);
                }
            }
        });

        $total = $query->count();

        // 自定义排序：优先完整匹配，其次按分词匹配度，最后按点击量
        $query->orderRaw("
        CASE 
            WHEN title LIKE ? THEN 1000
            WHEN author LIKE ? THEN 900
            ELSE 0
        END + 
        CASE 
            WHEN title LIKE ? THEN 100
            WHEN author LIKE ? THEN 90
            ELSE 0
        END DESC, 
        hits DESC
    ", [
            "%{$keyword}%", // 整个关键词匹配标题
            "%{$keyword}%", // 整个关键词匹配作者
            "%" . implode("%", $keywords) . "%", // 所有分词都在标题中
            "%" . implode("%", $keywords) . "%"  // 所有分词都在作者中
        ]);

        $paginator = $query->paginate($limit, true, [
            'list_rows' => $limit,
            'page' => $page,
            'path' => (string) url('search')
        ]);

        $paginator->appends(['keyword' => $keyword]);

        // 高亮处理
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
                'resnum' => $total,
                'create_time' => time()
            ]);
        } catch (\Exception $e) {
            // 记录日志但不中断流程
        }

        return [
            'total' => $total,
            'list' => $paginator,
            'keyword' => $keyword
        ];
    }

    /**
     * 搜索小说（支持标题和作者模糊匹配）
     * @param string $keyword 搜索关键词
     * @param int $page 当前页码
     * @param int $limit 每页条数
     * @return array 包含分页数据和高亮结果
     */
    public static function search_old($keyword, $page = 1, $limit = 20)
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
            // 初始化结果数组，第一个元素是完整关键词
            $result = [$keyword];

            // 获取字符串的字符长度
            $length = mb_strlen($keyword, 'UTF-8');

            // 循环遍历，每次取2个字符
            for ($i = 0; $i < $length - 1; $i++) {
                $result[] = mb_substr($keyword, $i, 2, 'UTF-8'); // 注意明确指定编码为UTF-8
            }

            return $result;
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
