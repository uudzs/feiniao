<?php

namespace app\admin\model;

use think\Model;

class Third extends Model
{
    // 定义与 User 模型的关联
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
