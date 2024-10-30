<?php

namespace app\admin\validate;

use think\Validate;

class PositionCheck extends Validate
{	
    protected $rule = [
        'title' => 'require|unique:position',
        'work_price' => 'require|number',
        'id' => 'require'
    ];

    protected $message = [
        'title.require' => '岗位名称不能为空',
        'title.unique' => '同样的岗位名称已经存在',
        'work_price.require' => '岗位工时单价不能为空',
        'work_price.number' => '岗位工时单价只能是整数',
        'id.require' => '缺少更新条件',
    ];

    protected $scene = [
        'add' => ['title', 'work_price', 'group_id'],
        'edit' => ['title', 'work_price', 'group_id', 'id'],
    ];
}
