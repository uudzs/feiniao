<?php

use think\facade\Db;
use think\facade\Cache;
use think\facade\Route;

//读取导航列表，用于前台
function get_navs($name)
{
    if (!get_cache('homeNav' . $name)) {
        $nav_id = Db::name('Nav')->where(['name' => $name, 'status' => 1])->value('id');
        if (empty($nav_id)) {
            return '';
        }
        $list = Db::name('NavInfo')->where(['nav_id' => $nav_id, 'status' => 1])->order('sort desc')->select()->toArray();
        $nav = list_to_tree($list);
        Cache::tag('homeNav')->set('homeNav' . $name, $nav);
    }
    $navs = get_cache('homeNav' . $name);
    return $navs;
}


if (!function_exists('furl')) {
    /**
     * Url生成
     * @param string      $url    路由地址
     * @param array       $vars   变量
     * @param bool|string $suffix 生成的URL后缀
     * @param bool|string $domain 域名
     * @return UrlBuild
     */
    function furl(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {

        if ($domain != false) {
            $domain_bind = get_config('app.domain_bind');
            if ($domain_bind) {
                $domain_bind = $domain_bind ? array_flip($domain_bind) : [];
                if (isset($domain_bind[$domain]) && $domain_bind[$domain]) {
                    if ($domain_bind[$domain] == '*') {
                        return (string) Route::buildUrl($url, $vars)->suffix($suffix)->domain(false);
                    } else {
                        return (string) Route::buildUrl($url, $vars)->suffix($suffix)->domain($domain_bind[$domain]);
                    }
                } else {
                    $rurl = url($url, $vars, $suffix, $domain);
                    return $rurl;
                    $url = '/' . $domain . $url;
                    $domain = false;
                }
            } else {
                $rurl = (string) Route::buildUrl($url, $vars)->suffix($suffix)->domain(false);
                if (strpos($rurl, $domain) !== false) {
                    return $rurl;
                }
                $fChar = substr($url, 0, 1);
                if ($fChar != '/') {
                    $url = '/' . $url;
                }
                $url = '/' . $domain . $url;
                $domain = false;
            }
            return (string) Route::buildUrl($url, $vars)->suffix($suffix)->domain($domain);
        }
        return (string) Route::buildUrl($url, $vars)->suffix($suffix)->domain($domain);
    }
}
