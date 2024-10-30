<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\Db;
use think\facade\View;

class Index extends BaseController
{
    public function index()
    {
        $admin = get_login_admin();
        if (get_cache('menu' . $admin['id'])) {
            $list = get_cache('menu' . $admin['id']);
        } else {
            $adminGroup = Db::name('AdminGroupAccess')->where(['uid' => get_login_admin('id')])->column('group_id');
            $adminMenu = Db::name('AdminGroup')->where('id', 'in', $adminGroup)->column('rules');
            $adminMenus = [];
            foreach ($adminMenu as $k => $v) {
                $v = explode(',', $v);
                $adminMenus = array_merge($adminMenus, $v);
            }
            $menu = Db::name('AdminRule')->where(['menu' => 1, 'status' => 1])->where('id', 'in', $adminMenus)->order('sort asc')->select()->toArray();
            $list = list_to_tree($menu);
            \think\facade\Cache::tag('adminMenu')->set('menu' . $admin['id'], $list);
        }
        $domain_bind = get_config('app.domain_bind');
        $domain_bind = $domain_bind ? array_flip($domain_bind) : [];
        $skip = [
            'admin/index',
            'admin/add',
            'admin/view',
            'admin/delete'
        ];
        if (isset($domain_bind['admin']) && $domain_bind['admin']) {
            foreach ($list as $k => $v) {
                if ($v['src']) {
                    if (strpos($v['src'], "admin/") !== false) {
                        if (!in_array($v['src'], $skip, true)) {
                            $list[$k]['src'] = str_ireplace('admin/', '', $v['src']);
                        }
                    }
                }
                if (isset($v['list']) && $v['list']) {
                    foreach ($v['list'] as $k1 => $v1) {
                        if (strpos($v1['src'], "admin/") !== false) {
                            if (!in_array($v1['src'], $skip, true)) {
                                $list[$k]['list'][$k1]['src'] = str_ireplace('admin/', '', $v1['src']);
                            }
                        }
                        if (isset($v1['list']) && $v1['list']) {
                            foreach ($v1['list'] as $k2 => $v2) {
                                if (strpos($v2['src'], "admin/") !== false) {
                                    if (!in_array($v2['src'], $skip, true)) {
                                        $list[$k]['list'][$k1]['list'][$k2]['src'] = str_ireplace('admin/', '', $v2['src']);
                                    }
                                }
                                if (isset($v2['list']) && $v2['list']) {
                                    foreach ($v2['list'] as $k3 => $v3) {
                                        if (strpos($v3['src'], "admin/") !== false) {
                                            if (!in_array($v3['src'], $skip, true)) {
                                                $list[$k]['list'][$k1]['list'][$k2]['list'][$k3]['src'] = str_ireplace('admin/', '', $v3['src']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            foreach ($list as $k => $v) {
                if ($v['src']) {
                    if (strpos($v['src'], "admin/") !== false) {
                        if (in_array($v['src'], $skip, true)) {
                            $list[$k]['src'] = 'admin/' . $v['src'];
                        }
                    } else {
                        $list[$k]['src'] = 'admin/' . $v['src'];
                    }
                }
                if (isset($v['list']) && $v['list']) {
                    foreach ($v['list'] as $k1 => $v1) {
                        if (strpos($v1['src'], "admin/") !== false) {
                            if (in_array($v1['src'], $skip, true)) {
                                $list[$k]['list'][$k1]['src'] = 'admin/' . $v1['src'];
                            }
                        } else {
                            $list[$k]['list'][$k1]['src'] = 'admin/' . $v1['src'];
                        }
                        if (isset($v1['list']) && $v1['list']) {
                            foreach ($v1['list'] as $k2 => $v2) {
                                if (strpos($v2['src'], "admin/") !== false) {
                                    if (in_array($v2['src'], $skip, true)) {
                                        $list[$k]['list'][$k1]['list'][$k2]['src'] = 'admin/' . $v2['src'];
                                    }
                                } else {
                                    $list[$k]['list'][$k1]['list'][$k2]['src'] = 'admin/' . $v2['src'];
                                }
                                if (isset($v2['list']) && $v2['list']) {
                                    foreach ($v2['list'] as $k3 => $v3) {
                                        if (strpos($v3['src'], "admin/") !== false) {
                                            if (in_array($v3['src'], $skip, true)) {
                                                $list[$k]['list'][$k1]['list'][$k2]['list'][$k3]['src'] = 'admin/' . $v3['src'];
                                            }
                                        } else {
                                            $list[$k]['list'][$k1]['list'][$k2]['list'][$k3]['src'] = 'admin/' . $v3['src'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $theme = Db::name('Admin')->where('id', $admin['id'])->value('theme');
        View::assign('theme', $theme);
        View::assign('menu', $list);
        return View();
    }

    public function main()
    {
        $authorCount = Db::name('author')->count();
        $userCount = Db::name('User')->count();
        $bookCount = Db::name('book')->count();
        $orderCount = Db::name('order')->count();
        $withdrawCount = Db::name('withdraw')->count();
        $reportCount = Db::name('report')->count();
        $install = false;
        if (file_exists(CMS_ROOT . 'app/install')) {
            $install = true;
        }
        View::assign('authorCount', $authorCount);
        View::assign('userCount', $userCount);
        View::assign('bookCount', $bookCount);
        View::assign('orderCount', $orderCount);
        View::assign('withdrawCount', $withdrawCount);
        View::assign('reportCount', $reportCount);
        View::assign('install', $install);
        View::assign('TP_VERSION', \think\facade\App::version());
        return View();
    }

    //设置theme
    public function set_theme()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $admin = get_login_admin();
            Db::name('Admin')->where('id', $admin['id'])->update(['theme' => $param['theme']]);
            return to_assign();
        } else {
            return to_assign(1, '操作错误');
        }
    }
}
