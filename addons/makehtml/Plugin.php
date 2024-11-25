<?php

namespace addons\makehtml;

use think\Addons;

/**
 * 注意名字不可以修改，只能为Plugin
 */
class Plugin extends Addons    // 需继承think\Addons类
{
    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }
}