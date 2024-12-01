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