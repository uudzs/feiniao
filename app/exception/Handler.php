<?php

namespace app\exception;

use think\exception\Handle;
use think\template\exception\TemplateNotFoundException;
use think\Response;
use app\common\model\Category;

class Handler extends Handle
{
    public function render($request, \Throwable $e): Response
    {
        if ($e instanceof TemplateNotFoundException) {
            // 获取标准化路径（去除前后斜杠）
            $currentPath = trim($request->pathinfo(), '/');
            $theme = get_config('theme');
            if (preg_match('/^cate-([a-z-]+)\.html$/i', $currentPath, $matches)) {
                $category = $matches[1] ?? '';
                if ($category && !app('request')->isMobile() && !isWeChat()) {
                    if ($theme['template_pc'] == 'tadu_pc') {
                        $cate = Category::where('key', $category)->find();
                        if ($cate) {
                            if ($cate->id == Category::FEMALE_CATEGORY_ID) {
                                $url = url('novelfilter', [
                                    'channel' => 2,
                                    'status'  => 'a',
                                    'cat'     => 0,
                                    'word'    => 0,
                                    'order'   => 'a',
                                    'page'    => 1,
                                    'cid'     => 0,
                                    'mode'    => 1
                                ]);
                            } elseif ($cate->pid == Category::FEMALE_CATEGORY_ID) {
                                $url = url('novelfilter', [
                                    'channel' => 2,
                                    'status'  => 'a',
                                    'cat'     => $cate->id,
                                    'word'    => 0,
                                    'order'   => 'a',
                                    'page'    => 1,
                                    'cid'     => 0,
                                    'mode'    => 1
                                ]);
                            } else {
                                $url = url('novelfilter', [
                                    'channel' => 1,
                                    'status'  => 'a',
                                    'cat'     => $cate->pid > 0 ? $cate->pid : $cate->id,
                                    'word'    => 0,
                                    'order'   => 'a',
                                    'page'    => 1,
                                    'cid'     => $cate->pid > 0 ? $cate->id : 0,
                                    'mode'    => 1
                                ]);
                            }
                            if ($url) {
                                return redirect($url)->code(301);
                            }
                        }
                    }
                }
            } else {
                if ($currentPath && !app('request')->isMobile() && !isWeChat()) {
                    if ($theme['template_pc'] == 'tadu_pc') {
                        switch ($currentPath) {
                            case 'quanben.html':
                                $url = url('novelfilter', [
                                    'channel' => 1,
                                    'status'  => 'f',
                                    'cat'     => 0,
                                    'word'    => 0,
                                    'order'   => 'a',
                                    'page'    => 1,
                                    'cid'     => 0,
                                    'mode'    => 1
                                ]);
                                return redirect($url)->code(301);
                                break;
                            case 'rank.html':
                                $url = url('top_main', [
                                    'channel' => 'male',
                                    'cid'  => 'all'
                                ]);
                                return redirect($url)->code(301);
                                break;
                            case 'shuku.html':
                                $url = url('novel');
                                return redirect($url)->code(301);
                                break;
                        }
                    }
                }
            }
            // 默认通用提示
            return Response('<div style="...">😔<br/>模板文件不存在，或主题被禁用！</div>');
        }
        return parent::render($request, $e);
    }
}
