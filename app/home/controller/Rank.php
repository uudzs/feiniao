<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use app\common\model\Rank as RankModel;
use app\common\model\Category;
use think\facade\View;

class Rank extends BaseController
{
    /**
     * 总榜页面（展示6个榜单的Top10）
     */
    public function index(string $channel = 'male', string $cid = '')
    {
        $ismakecache = $this->usecache();
        $cid = $cid == 'all' ? '' : $cid;
        if ($cid) {
            $cid = (int)Category::where('key', $cid)->value('id');
        } else {
            $cid = 0;
        }
        $rankModel = new RankModel();
        $categories = Category::getCategoriesByChannel($channel === 'male' ? 1 : 2);
        if ($ismakecache) $this->makecache(View::fetch('rank/index', [
            'categories'    => $categories,
            'cid'           => $cid,
            'channel'       => $channel,
            'ranks'         => $rankModel->getMainRankData($channel, $cid),
            'rankTypes'     => RankModel::RANK_CONFIG
        ]));
        return View::fetch('rank/index', [
            'categories'    => $categories,
            'cid'           => $cid,
            'channel'       => $channel,
            'ranks'         => $rankModel->getMainRankData($channel, $cid),
            'rankTypes'     => RankModel::RANK_CONFIG
        ]);
    }

    /**
     * 单个榜单详细页
     */
    public function detail(string $channel, string $type, string $cid = '', int $page = 1)
    {
        $ismakecache = $this->usecache();
        $cid = $cid == 'all' ? '' : $cid;
        if ($cid) {
            $cid = (int)Category::where('key', $cid)->value('id');
        } else {
            $cid = 0;
        }
        $this->validateParams($channel, $type);
        $rankModel = new RankModel();
        $paginator = $rankModel->getRankDetail($channel, $type, $cid);
        $categories = Category::getCategoriesByChannel($channel === 'male' ? 1 : 2);
        $originalHtml = $paginator->render();
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
                    $pagePosition = count($pathSegments) - 1;
                    if ($pagePosition > 3) { // 确保是有效的页码位置
                        $pathSegments[$pagePosition] = $matches[2] > 3 ? 3 : $matches[2]; // 新页码
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
        if ($ismakecache) $this->makecache(View::fetch('rank/index', [
            'categories'    => $categories,
            'cid'           => $cid,
            'list'          => $paginator->items(),
            'page'          => $page,
            'pagination'    => $processedHtml,
            'channel'       => $channel,
            'type'          => $type,
            'rankTypes'     => RankModel::RANK_CONFIG,
            'typeInfo'      => RankModel::RANK_CONFIG[$type],
          ]));
        return View::fetch('rank/index', [
            'categories'    => $categories,
            'cid'           => $cid,
            'list'          => $paginator->items(),
            'page'          => $page,
            'pagination'    => $processedHtml,
            'channel'       => $channel,
            'type'          => $type,
            'rankTypes'     => RankModel::RANK_CONFIG,
            'typeInfo'      => RankModel::RANK_CONFIG[$type],
        ]);
    }

    /**
     * 参数验证
     */
    private function validateParams(string $channel, string $type): void
    {
        if (!in_array($channel, ['male', 'female'])) {
            $this->error('paramerror');
        }
        if (!isset(RankModel::RANK_CONFIG[$type])) {
            $this->error('paramerror');
        }
    }
}
