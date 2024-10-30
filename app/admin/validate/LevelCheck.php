<?php

namespace app\admin\validate;

use think\Validate;

class LevelCheck extends Validate
{
    protected $rule = [
        'title' => 'require|unique:user_level',
        'id' => 'require',
    ];

    protected $message = [
        'title.require' => '模块名称不能为空',
        'title.unique' => '同样的等级名称已经存在',
        'id.require' => '缺少更新条件',
    ];

    protected $scene = [
        'add' => ['title'],
        'edit' => ['id','title'],
    ];
}
