<?php

namespace app;

use think\exception\Handle;
use think\Response;
use Throwable;
use GuzzleHttp\Exception\ConnectException;
/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        if ($e instanceof ConnectException) {
            return json(['code' => 1, 'msg' => '请求超时']);
        }
        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
