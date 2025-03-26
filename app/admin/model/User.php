<?php

namespace app\admin\model;

use think\Model;

class User extends Model
{
    // 定义与 Third 模型的关联
    public function third()
    {
        return $this->hasMany(Third::class, 'user_id');
    }
}
