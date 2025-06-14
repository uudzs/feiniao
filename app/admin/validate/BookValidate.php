<?php

namespace app\admin\validate;

use think\Validate;
use think\facade\Db;

class BookValidate extends Validate
{
    // 自定义验证规则
    protected function checkOne($value, $rule, $data = [])
    {
        $count = Db::name('book')->where([['title', '=', $data['title']], ['author', '=', $data['author']], ['id', '<>', $data['id']]])->count();
        return $count == 0 ? true : false;
    }

    protected $rule = [
        'title' => 'require|checkOne',
    ];

    protected $message = [
        'title.require' => '作品名称不能为空',
        'title.checkOne' => '作品名称已存在',
    ];
}
