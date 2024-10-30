<?php

declare (strict_types = 1);

namespace app\home\middleware;

class Install
{
    public function handle($request, \Closure $next)
    {
        if (!is_installed()) {
            return $request->isAjax() ? to_assign(1, '请先完成系统安装引导') : redirect((string) url('/install/index'));
        }

        return $next($request);
    }
}
