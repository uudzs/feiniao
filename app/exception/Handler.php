<?php

namespace app\exception;

use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use InvalidArgumentException;
use think\template\exception\TemplateNotFoundException;
use think\Response;
use Throwable;
use think\exception\HttpResponseException;
use app\common\model\Category;

class Handler extends Handle
{
    public function render($request, Throwable $e): Response
    {
        // 处理HttpResponseException
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        // 处理模板不存在异常
        if ($e instanceof TemplateNotFoundException) {
            return $this->handleTemplateNotFound($request);
        }

        if ($e instanceof InvalidArgumentException) {
            return Response::create([
                'code' => 1,
                'msg' => $e->getMessage()
            ], 'json');
        }

        // 处理验证异常
        if ($e instanceof ValidateException) {
            return Response::create([
                'code' => 422,
                'msg' => $e->getError()
            ], 'json');
        }

        // 处理HTTP异常
        if ($e instanceof HttpException) {
            return $this->handleHttpException($request, $e);
        }

        // 其他异常交由父类处理
        return parent::render($request, $e);
    }

    /**
     * 处理模板不存在异常
     */
    protected function handleTemplateNotFound($request)
    {
        $currentPath = trim($request->pathinfo(), '/');
        $theme = get_config('theme');
        // PC端非微信访问处理
        if (!$request->isMobile() && !isWeChat() && $theme['template_pc'] == 'tadu_pc') {
            // 分类模板重定向逻辑
            if (preg_match('/^cate-([a-z-]+)\.html$/i', $currentPath, $matches)) {
                return $this->handleCategoryRedirect($matches[1] ?? '');
            }
            // 特殊页面重定向
            return $this->handleSpecialPages($currentPath);
        }
        // 默认错误提示
        return $this->defaultTemplateResponse();
    }

    /**
     * 处理分类页面重定向
     */
    protected function handleCategoryRedirect($category)
    {
        if ($cate = Category::where('key', $category)->find()) {
            $params = [
                'status'  => 'a',
                'word'    => 0,
                'order'   => 'a',
                'page'    => 1,
                'cid'     => 0,
                'mode'    => 1
            ];
            if ($cate->id == Category::FEMALE_CATEGORY_ID) {
                $params['channel'] = 2;
            } elseif ($cate->pid == Category::FEMALE_CATEGORY_ID) {
                $params['channel'] = 2;
                $params['cat'] = $cate->id;
            } else {
                $params['channel'] = 1;
                $params['cat'] = $cate->pid > 0 ? $cate->pid : $cate->id;
                $params['cid'] = $cate->pid > 0 ? $cate->id : 0;
            }
            return redirect(url('novelfilter', $params))->code(301);
        }
        return $this->defaultTemplateResponse();
    }

    /**
     * 处理特殊页面重定向
     */
    protected function handleSpecialPages($path)
    {
        switch ($path) {
            case 'quanben.html':
                return redirect(url('novelfilter', [
                    'channel' => 1,
                    'status'  => 'f',
                    'cat'     => 0,
                ]))->code(301);

            case 'rank.html':
                return redirect(url('top_main', [
                    'channel' => 'male',
                    'cid'     => 'all'
                ]))->code(301);

            case 'shuku.html':
                return redirect(url('novel'))->code(301);
        }
        return $this->defaultTemplateResponse();
    }

    /**
     * 处理HTTP异常
     */
    protected function handleHttpException($request, $e)
    {
        // AJAX请求返回精简响应
        if ($request->isAjax()) {
            return Response::create([
                'code' => $e->getStatusCode(),
                'msg' => $e->getMessage()
            ], 'json');
        }
        // 可在此添加其他HTTP异常处理逻辑
        return parent::render($request, $e);
    }

    /**
     * 默认模板响应
     */
    protected function defaultTemplateResponse()
    {
        return Response('<div style=" display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  height: 100vh;
}">模板文件不存在，或主题被禁用！</div>');
    }
}
