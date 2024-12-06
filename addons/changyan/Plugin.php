<?php

namespace addons\changyan;

use think\facade\App;
use think\Addons;
use think\facade\Db;
use think\facade\Cookie;

/**
 * 注意名字不可以修改，只能为Plugin
 */
class Plugin extends Addons    // 需继承think\Addons类
{
    private $admin_list = [
        [
            'pid' => 120, //父id
            "src" => "addons/changyan/index/site", //方法名称
            'title'  => '畅言插件-站点列表', //菜单标题
            'name'   => '畅言插件-站点列表', //名称
            'icon'   => '', //图标
            "menu" => 2, //是否是菜单,1是,2不是
            'sort'   => 0, //默认排序                
            'status' => 1, //状态，1是显示，0是不显示
        ],
        [
            'pid' => 120, //父id
            "src" => "addons/changyan/index/add", //方法名称
            'title'  => '畅言插件-站点添加', //菜单标题
            'name'   => '畅言插件-站点添加', //名称
            'icon'   => '', //图标
            "menu" => 2, //是否是菜单,1是,2不是
            'sort'   => 0, //默认排序                
            'status' => 1, //状态，1是显示，0是不显示
        ],
        [
            'pid' => 120, //父id
            "src" => "addons/changyan/index/edit", //方法名称
            'title'  => '畅言插件-站点查看', //菜单标题
            'name'   => '畅言插件-站点查看', //名称
            'icon'   => '', //图标
            "menu" => 2, //是否是菜单,1是,2不是
            'sort'   => 0, //默认排序                
            'status' => 1, //状态，1是显示，0是不显示
        ],
        [
            'pid' => 120, //父id
            "src" => "addons/changyan/index/comments", //方法名称
            'title'  => '畅言插件-站点评论', //菜单标题
            'name'   => '畅言插件-站点评论', //名称
            'icon'   => '', //图标
            "menu" => 2, //是否是菜单,1是,2不是
            'sort'   => 0, //默认排序                
            'status' => 1, //状态，1是显示，0是不显示
        ],
        [
            'pid' => 120, //父id
            "src" => "addons/changyan/index/enable", //方法名称
            'title'  => '畅言插件-站点启用', //菜单标题
            'name'   => '畅言插件-站点启用', //名称
            'icon'   => '', //图标
            "menu" => 2, //是否是菜单,1是,2不是
            'sort'   => 0, //默认排序                
            'status' => 1, //状态，1是显示，0是不显示
        ],
        [
            'pid' => 120, //父id
            "src" => "addons/changyan/index/sync", //方法名称
            'title'  => '畅言插件-畅言设置', //菜单标题
            'name'   => '畅言插件-畅言设置', //名称
            'icon'   => '', //图标
            "menu" => 2, //是否是菜单,1是,2不是
            'sort'   => 0, //默认排序                
            'status' => 1, //状态，1是显示，0是不显示
        ],
        [
            'pid' => 120, //父id
            "src" => "addons/changyan/index/read", //方法名称
            'title'  => '畅言插件-查看评论', //菜单标题
            'name'   => '畅言插件-查看评论', //名称
            'icon'   => '', //图标
            "menu" => 2, //是否是菜单,1是,2不是
            'sort'   => 0, //默认排序                
            'status' => 1, //状态，1是显示，0是不显示
        ],
        [
            'pid' => 120, //父id
            "src" => "addons/changyan/index/changyan2local", //方法名称
            'title'  => '畅言插件-同步评论', //菜单标题
            'name'   => '畅言插件-同步评论', //名称
            'icon'   => '', //图标
            "menu" => 2, //是否是菜单,1是,2不是
            'sort'   => 0, //默认排序                
            'status' => 1, //状态，1是显示，0是不显示
        ],
    ];

    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        foreach ($this->admin_list as $key => $value) {
            if (Db::name('admin_rule')->where(['src' => $value['src']])->count()) {
                continue;
            }
            $value['create_time'] = time();
            $rid = Db::name('admin_rule')->strict(false)->field(true)->insertGetId($value);
            $group = Db::name('AdminGroup')->find(1);
            if (!empty($group)) {
                $newGroup['id'] = 1;
                $newGroup['rules'] = $group['rules'] . ',' . $rid;
                Db::name('AdminGroup')->strict(false)->field(true)->update($newGroup);
            }
        }
        clear_cache('adminRules');
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        foreach ($this->admin_list as $key => $value) {
            if (Db::name('admin_rule')->where(['src' => $value['src']])->count()) {
                Db::name('admin_rule')->where('src', $value['src'])->delete();
            }
        }
        clear_cache('adminRules');
        return true;
    }

    public function commentsHook($param = [])
    {
        $info = $this->getInfo();
        if ($info['install'] == 0 || $info['status'] == 0) {
            return false;
        }
        $config = [];
        $rootPath = App::getRootPath();
        $config_file = $rootPath . 'addons' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config_file)) {
            $config = (array) include $config_file;
        }
        if (empty($config)) return false;
        if (empty($config['siteid']['value'])) return false;
        $arr = ['sid' => $config['siteid']['value']];
        $session_user = get_config('app.session_user');
        if (Cookie::has($session_user)) {
            $arr['uid'] = Cookie::get($session_user);
        }
        if (isset($param['bookid'])) {
            $arr['bid'] = $param['bookid'];
        }
        if (isset($param['chapterid'])) {
            $arr['cid'] = $param['chapterid'];
        }
        $sid = base64_encode(http_build_query($arr));
        if (isset($param['terminal']) && $param['terminal'] && isset($config[$param['terminal']]['value']) && $config[$param['terminal']]['value']) {
            return '<div id="SOHUCS" sid="' . $sid . '"></div>' . "\n" . base64_decode($config[$param['terminal']]['value']);
        }
        return '<div id="SOHUCS" sid="' . $sid . '"></div>'  . "\n" . base64_decode($config['adaptive_script']['value']);
    }

    public function commentsNum($param = [])
    {
        $info = $this->getInfo();
        if ($info['install'] == 0 || $info['status'] == 0) {
            return false;
        }
        $config = [];
        $rootPath = App::getRootPath();
        $config_file = $rootPath . 'addons' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config_file)) {
            $config = (array) include $config_file;
        }
        if (empty($config)) return false;
        if (empty($config['siteid']['value'])) return false;
        $siteid = $config['siteid']['value'];
        if (isset($param['bookid']) && !isset($param['chapterid'])) {
            return Db::name('addons_changyan_comments')->where(['siteid' => $siteid, 'bookid' => $param['bookid'], 'chapterid' => 0])->count();
        }
        if (isset($param['bookid']) && isset($param['chapterid'])) {
            return Db::name('addons_changyan_comments')->where(['siteid' => $siteid, 'chapterid' => $param['chapterid']])->count();
        }
    }
}
