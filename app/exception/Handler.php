<?php

namespace app\exception;

use think\exception\Handle;
use think\template\exception\TemplateNotFoundException;
use think\Response;

class Handler extends Handle
{
    public function render($request, \Throwable $e): Response
    {
        if ($e instanceof TemplateNotFoundException) {
            return Response('<div style="display:flex;justify-content: center;align-items: center;height: 100vh;text-align: center;line-height: 2rem;">😔<br/>模板文件不存在，或主题被禁用！</div>');
        }
        return parent::render($request, $e);
    }
}