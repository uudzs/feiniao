<?php

// 这是home公共文件

//读取导航列表，用于前台
function get_navs($name)
{
    if (!get_cache('homeNav' . $name)) {
        $nav_id = \think\facade\Db::name('Nav')->where(['name' => $name, 'status' => 1])->value('id');
        if (empty($nav_id)) {
            return '';
        }
        $list = \think\facade\Db::name('NavInfo')->where(['nav_id' => $nav_id, 'status' => 1])->order('sort asc')->select()->toArray();
		$nav = list_to_tree($list);
        \think\facade\Cache::tag('homeNav')->set('homeNav' . $name, $nav);
    }
    $navs = get_cache('homeNav' . $name);
    return $navs;
}