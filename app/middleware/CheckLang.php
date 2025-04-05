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

        // 优先级：URL参数 > Cookie > 浏览器语言 > 默认配置
        $lang = $request->param('lang');
        $supportedLangs = explode(',', env('LANG.lang_allow_list', 'zh-cn,en-us'));
        $defaultLang = env('LANG.default_lang', 'zh-cn');

        // 从Cookie获取语言
        if (empty($lang) && $request->cookie('think_lang')) {
            $lang = $request->cookie('think_lang');
        }

        // 从浏览器语言检测
        if (empty($lang)) {
            $acceptLang = $request->header('accept-language');
            $lang = $this->detectBrowserLanguage($acceptLang, $supportedLangs);
        }

        // 验证语言是否在允许列表中
        $lang = in_array(strtolower($lang), $supportedLangs) ? $lang : $defaultLang;

        // 保存到Cookie（有效期30天）
        cookie('think_lang', $lang, 86400 * 30);

        // 设置语言（使用正确的方法名）
        Lang::setLangSet($lang);

        return $next($request);
    }


    /**
     * 检测浏览器语言偏好
     */
    protected function detectBrowserLanguage($acceptLang, $supportedLangs): string
    {
        if (empty($acceptLang)) {
            return '';
        }
        $languages = explode(',', $acceptLang);
        foreach ($languages as $langItem) {
            $langItem = explode(';', $langItem)[0]; // 移除权重部分（如 `en;q=0.9`）
            $langCode = strtolower(trim($langItem));
            // 精确匹配（如 zh-cn）
            if (in_array($langCode, $supportedLangs)) {
                return $langCode;
            }
            // 模糊匹配（如 zh 匹配 zh-cn）
            $primaryLang = explode('-', $langCode)[0];
            foreach ($supportedLangs as $supported) {
                if (str_starts_with($supported, $primaryLang)) {
                    return $supported;
                }
            }
        }
        return '';
    }
}
