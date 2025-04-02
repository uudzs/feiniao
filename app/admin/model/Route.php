<?php

namespace app\admin\model;

use think\Model;

class Route extends Model
{
    protected $pk = 'id';

    // 字段类型转换
    protected $type = [
        'status' => 'integer'
    ];

    // 状态获取器
    public function getStatusTextAttr($value, $data)
    {
        $status = [0 => '禁用', 1 => '启用'];
        return $status[$data['status']];
    }
}
