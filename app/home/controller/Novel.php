<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use app\common\model\Novel as NovelModel;
use app\common\model\Category;
use think\facade\View;
use think\App;

class Novel extends BaseController
{
    // 频道映射表
    static $CHANNEL_MAP;

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$CHANNEL_MAP = [
            1  => ['id' => 1,  'name' => lang('common.malefrequency'), 'type' => 'male'],
            2 => ['id' => 2, 'name' => lang('common.femalefrequency'), 'type' => 'female']
        ];
    }

    public function index($channel = 1, $status = 'a', $cat = 0, $word = 0, $order = 'a', $page = 1, $cid = 0, $mode = 1)
    {
        $ismakecache = $this->usecache();
        // 参数有效性验证
        $this->validateParams($channel, $status, $cat, $word, $order, $cid);

        // 参数解析（与模型映射保持一致）
        $filter = [
            'status' => $status,  // 直接传递原始参数，由模型处理
            'word'   => $word,
            'order'  => $order,
            'cat'    => (int)$cat,
            'cid'    => (int)$cid,
            'page'   => max(1, (int)$page),
            'mode'    => (int)$mode,
        ];

        // 获取频道信息
        $channelData = $this->getChannelInfo($channel);

        // 获取作品列表（带异常处理）
        try {
            $novels = NovelModel::getList($channelData['id'], $filter);
        } catch (\Exception $e) {
            $this->error('404');
        }
        $categorysubclass = [];
        if ($channel == 1 && $cat > 0) {
            $categorysubclass = Category::getCategorySubclass($cat);
        }
        // 获取分类数据（带缓存）
        $categories = Category::getCategoriesByChannel($channel);
        $originalHtml = $novels->render();
        $processedHtml = '';
        if ($originalHtml) {
            $processedHtml = preg_replace_callback(
                '/<li(?: class="[^"]*")?><a href="([^"]+page=(\d+))"[^>]*>(.*?)<\/a><\/li>/',
                function ($matches) {
                    // 解析原始URL
                    $urlParts = parse_url($matches[1]);
                    $path = $urlParts['path'];  // 示例：novel-1-a-0-0-a-1-0-1.html
                    $query = $urlParts['query'] ?? ''; // page=2
                    // 分解路径参数
                    $pathSegments = explode('-', rtrim($path, '.html'));
                    // 替换页码到正确位置（倒数第三个参数）
                    $pagePosition = count($pathSegments) - 3;
                    if ($pagePosition > 5) { // 确保是有效的页码位置
                        $pathSegments[$pagePosition] = $matches[2]; // 新页码
                    }
                    // 重组路径
                    $newPath = implode('-', $pathSegments) . '.html';
                    // 保留其他查询参数（如果有）
                    $newQuery = preg_replace('/&?page=\d+/', '', $query);
                    $newUrl = $newPath . ($newQuery ? '?' . $newQuery : '');
                    return '<li><a href="' . $newUrl . '">' . $matches[3] . '</a></li>';
                },
                $originalHtml
            );
        }

        if (strpos($processedHtml, 'nove.html') !== false) {
            $pagePath = url('novelfilter', array_merge($filter, ['channel' => $channel, 'page' => 2]))->build();
            $processedHtml = preg_replace('~href="([^"]+)"~', 'href="' . $pagePath . '"', $processedHtml);
        }

        if ($ismakecache) $this->makecache(View::fetch('index', [
            'channel'    => $channelData,
            'categories' => $categories,
            'subclass'   => $categorysubclass,
            'novels'     => $novels,
            'pagination' => $processedHtml,
            'param'      => $this->buildTemplateParams($filter),
        ]));
        // 视图数据组装
        return view('index', [
            'channel'    => $channelData,
            'categories' => $categories,
            'subclass'   => $categorysubclass,
            'novels'     => $novels,
            'pagination' => $processedHtml,
            'param'      => $this->buildTemplateParams($filter),
        ]);
    }

    private function validateParams($channel, $status, $cat, $word, $order, $cid)
    {
        // 验证频道有效性
        if (!isset(self::$CHANNEL_MAP[$channel])) {
            $this->error('404');
        }

        // 验证状态参数有效性
        if (!array_key_exists($status, NovelModel::CONDITION_MAP['status'])) {
            $this->error('404');
        }

        // 验证分类参数范围
        if ($cat < 0 || $cat > 9999) {
            $this->error('404');
        }
    }

    private function getChannelInfo($channel)
    {
        $info = self::$CHANNEL_MAP[$channel] ?? null;
        if (!$info) {
            $this->error('404');
        }
        $info['siblings'] = self::$CHANNEL_MAP;
        return array_merge($info, [
            'status_map'  => NovelModel::CONDITION_MAP['status'],
            'word_ranges' => NovelModel::CONDITION_MAP['word_range'],
            'order_map'   => NovelModel::CONDITION_MAP['order']
        ]);
    }

    private function buildTemplateParams($filter)
    {
        return [
            'current_status'  => $filter['status'],
            'current_word'    => $filter['word'],
            'current_order'   => $filter['order'],
            'current_cat'     => $filter['cat'],
            'current_cid'     => $filter['cid'],
            'current_page'    => $filter['page'],
            'current_mode'    => $filter['mode']
        ];
    }

    public function girls()
    {
        $ismakecache = $this->usecache();
        if ($ismakecache) $this->makecache(View::fetch('girls'));
        return view('girls');
    }
}
