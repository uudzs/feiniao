<?php

namespace app\admin\validate;

use think\Validate;
use think\facade\Db;

class AuthorValidate extends Validate
{

    // 验证笔名
    protected function checkOne($value, $rule, $data = [])
    {
        $count = Db::name('author')->where([['nickname', '=', $data['nickname']], ['id', '<>', $data['id']]])->count();
        return $count == 0 ? true : false;
    }

    // 验证手机
    protected function checkMobileOne($value, $rule, $data = [])
    {
        $count = Db::name('author')->where([['mobile', '=', $data['mobile']], ['id', '<>', $data['id']]])->count();
        return $count == 0 ? true : false;
    }

    protected $rule = [
        'nickname' => 'require|checkOne',
        'mobile' => 'require|checkMobileOne',
    ];

    protected $message = [
        'nickname.require' => '笔名不能为空',
        'nickname.checkOne' => '此笔名已经存在',
        'mobile.require' => '手机不能为空',
        'mobile.checkMobileOne' => '此手机已经存在',
    ];
}