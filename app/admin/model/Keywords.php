<?php

namespace app\admin\model;

use think\Model;

// 关键字模型
class Keywords extends Model
{
    // 关联关键字
    public function increase($keywords)
    {
        $is_exist = $this->where('title', $keywords)->find();
        if ($is_exist) {
            $res = $is_exist['id'];
        } else {
            $res = $this->strict(false)->field(true)->insertGetId(['title' => $keywords, 'create_time' => time()]);
        }
        return $res;
    }
}
