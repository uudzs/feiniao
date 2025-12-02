<?php
namespace app\admin\facade;

use think\Facade;

class ThinkAddons extends Facade
{
    protected static function getFacadeClass()
    {
        return 'app\service\ThinkAddonsService';
    }
}