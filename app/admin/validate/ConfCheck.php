<?php

namespace app\admin\validate;

use think\Validate;

class ConfCheck extends Validate
{
    protected $rule = [
        'title' => 'require|unique:config,title^status',
        'name' => 'require|alphaDash|unique:config,name^status',
    ];

    protected $message = [
        'title.require' => '配置名称不能为空',
        'title.unique' => '同样的配置名称已经存在',
        'name.require' => '配置标识不能为空',
        'name.alphaDash' => '配置标识只能是字母和数字，下划线_及破折号-',
        'name.unique' => '同样的配置标识已经存在',
    ];
}
