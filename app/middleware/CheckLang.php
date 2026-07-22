<?php

namespace app\middleware;

use think\facade\Lang;

class CheckLang
{
    public function handle($request, \Closure $next)
    {
        // 如果未开启多语言，直接跳过
        if (!env('LANG.lang_switch_on', false)) {
            return $next($request);
        }

        // 统一使用 .env 配置的默认语言
        $lang = env('LANG.default_lang', 'zh-cn');
        $supportedLangs = explode(',', env('LANG.lang_allow_list', 'zh-cn,en-us'));

        // 验证语言是否在允许列表中
        $lang = in_array(strtolower($lang), $supportedLangs) ? $lang : 'zh-cn';

        // 强制加载 .env 配置的默认语言包
        if ($lang !== Lang::getLangSet()) {
            Lang::switchLangSet($lang);
        }

        return $next($request);
    }
}
