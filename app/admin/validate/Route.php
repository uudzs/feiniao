<?php

namespace app\admin\validate;

use think\Validate;

class Route extends Validate
{
    protected $rule = [
        'rule'   => 'require|max:255',
        'title'  => 'require|max:50',
        'group'  => 'max:30',
        'value'  => 'max:1000',
        'status' => 'require|number|between:0,1',
        'name'   => 'require|alphaDash|max:80|unique:fn_route,name'
    ];

    protected $message = [
        'rule.require'   => '路由规则不能为空',
        'title.require'  => '路由名称不能为空',
        'name.unique'    => '路由标识已存在',
        'status.between' => '状态值必须在0到1之间'
    ];

    // 更新场景验证
    protected $scene = [
        'edit' => ['rule', 'title', 'group', 'value', 'status', 'name' => 'require|alphaDash|max:80']
    ];
}
