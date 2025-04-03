<?php

namespace app\middleware;

use think\facade\Lang;

class CheckLang
{
    public function handle($request, \Closure $next)
    {
        // 获取语言标识
        $lang = env('leng.default_lang', 'zh-cn');

        // 设置语言（使用正确的方法名）
        Lang::setLangSet($lang);

        return $next($request);
    }
}
