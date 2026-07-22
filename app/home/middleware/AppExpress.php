<?php

declare (strict_types = 1);

namespace app\home\middleware;

class AppExpress
{
    public function handle($request, \Closure $next)
    {
        // home 是默认应用，生成 URL 时不再附加 /home 前缀。
        app('http')->setBind();

        return $next($request);
    }
}
