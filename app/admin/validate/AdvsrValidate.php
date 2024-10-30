<?php

namespace app\admin\validate;

use think\Validate;
use think\facade\Db;

class AdvsrValidate extends Validate
{

    // 自定义验证规则
    protected function checkOne($value, $rule, $data = [])
    {
        if (intval($data['books']) > 0) {
            $count = Db::name('advsr')->where(['type' => $data['type'], 'adver_id' => $data['adver_id'], 'books' => $data['books'], ['id', '<>', $data['id']]])->count();
            return $count == 0 ? true : false;
        } else {
            return true;
        }
    }

    protected $rule = [
        'adver_id' => 'require',
        'books' => 'checkOne',
    ];

    protected $message = [
        'adver_id.require' => '广告位置不能为空',
        'books.checkOne' => '作品重复',
    ];
}
