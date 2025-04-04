<?php

namespace app;

use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ErrorException;
use think\Response;
use Throwable;

class ExceptionHandle extends Handle
{
    public function render($request, Throwable $e): Response
    {
        // 记录所有错误日志
        $this->recordErrorLog($e);

        // 生产环境下返回标准化响应
        if (!env('APP_DEBUG')) {
            return $this->buildProductionResponse($e, $request);
        }

        // 调试模式下显示详细错误
        return parent::render($request, $e);
    }

    /**
     * 构建生产环境响应
     */
    protected function buildProductionResponse(Throwable $e, $request)
    {
        // 判断是否为API请求（根据Header或路由前缀）
        $isApiRequest = $request->isAjax() || strpos($request->pathinfo(), 'api/') === 0;

        // 默认错误信息
        $errorData = [
            'code'    => 500,
            'message' => 'Internal Server Error',
            'request_id' => $request->requestId() // 如果有请求ID跟踪
        ];

        // 可识别异常的特殊处理（扩展点）
        if ($e instanceof HttpException) {
            $errorData['code'] = $e->getStatusCode();
            $errorData['message'] = 'Resource Not Found';
        }

        // 返回格式处理
        return $isApiRequest
            ? json($errorData, $errorData['code'])
            : view(app()->getRootPath() . 'public/tpl/500.html', $errorData);
    }

    /**
     * 记录错误日志
     */
    protected function recordErrorLog(Throwable $e): void
    {
        $log = sprintf(
            "%s:%d %s\nTrace:\n%s",
            $e->getFile(),
            $e->getLine(),
            $e->getMessage(),
            $e->getTraceAsString()
        );

        // 写入日志
        \think\facade\Log::error($log);
    }
}
