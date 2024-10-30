<?php
namespace app\admin\validate;

use think\Validate;
use think\facade\Db;

class UserCheck extends Validate
{

    // 验证昵称
    protected function checkOne($value, $rule, $data = [])
    {
        $count = Db::name('user')->where([['nickname', '=', $data['nickname']], ['id', '<>', $data['id']]])->count();
        return $count == 0 ? true : false;
    }

    // 验证手机
    protected function checkMobileOne($value, $rule, $data = [])
    {
        $count = Db::name('user')->where([['mobile', '=', $data['mobile']], ['id', '<>', $data['id']]])->count();
        return $count == 0 ? true : false;
    }

    protected $rule = [
        'mobile' => 'require|checkMobileOne',
        'nickname' => 'require|checkOne',
    ];

    protected $message = [
        'mobile.require' => '手机不能为空',
        'mobile.checkMobileOne' => '同样的手机已经存在',
        'nickname.require' => '昵称不能为空',
        'nickname.checkOne' => '同样的昵称已经存在',
    ];
}
