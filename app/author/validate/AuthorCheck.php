<?php

namespace app\author\validate;

use think\Validate;
use think\facade\Db;

class AuthorCheck extends Validate
{

    // 自定义验证规则
    protected function checkOne($value, $rule, $data = [])
    {
        $count = Db::name('author')->where([['mobile', '=', $data['mobile']]])->count();
        return $count == 0 ? true : false;
    }

    protected $regex = ['checkUser' => '/^[A-Za-z]{1}[A-Za-z0-9_-]{4,19}$/', 'checkMobile' => '/^1[345789]\d{9}$/'];

    protected $rule = [
        'mobile' => 'require|regex:checkMobile',
        'password' => 'require',
        'username' => 'require|regex:checkUser|checkOne',
        'pwd' => 'require|min:6',
        //'pwd' => 'require|min:6|confirm',
        'captcha' => 'require|captcha',
        'code' => 'require|min:6|max:6',
    ];

    protected $message = [
        'mobile.require' => '账号不能为空',
        'mobile.regex' => '账号必须是手机号',
        'password.require' => '密码不能为空',
        'username.require' => '账号不能为空',
        'username.regex' => '账号必须是以字母开头，只能包含字母数字下划线和减号，5到20位',
        'username.checkOne' => '同样的登录账号已经存在',
        'pwd.require' => '密码不能为空',
        'pwd.min' => '密码必须6位以上',
        'code.require' => '手机短信验证码不能为空',
        'code.min' => '手机短信验证码必须为6位数字',
        'code.max' => '手机短信验证码必须为6位数字',
        //'pwd.confirm' => '两次密码不一致', //confirm自动相互验证
        'captcha.require' => '验证码不能为空',
        'captcha.captcha' => '验证码不正确',
    ];

    protected $scene = [
        'login' => ['mobile', 'password', 'captcha'],
        'reg' => ['mobile', 'pwd', 'code'],
        'regsendsms' => ['mobile', 'captcha'],
    ];

}
