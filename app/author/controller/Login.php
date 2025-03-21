<?php

declare(strict_types=1);

namespace app\author\controller;

use app\author\validate\AuthorCheck;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Session;
use think\facade\Route;

class Login
{
    //登录
    public function index()
    {
        if (!empty(get_login_author('id'))) {
            $url = (string) Route::buildUrl('user/index');
            redirect($url)->send();
        }
        return View();
    }

    //提交登录
    public function login_submit()
    {
        $param = get_params();
        try {
            validate(AuthorCheck::class)->scene('login')->check($param);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return to_assign(1, $e->getError());
        }
        $user = Db::name('author')->where(['mobile' => $param['mobile']])->find();
        if (empty($user)) {
            return to_assign(1, '用户名或密码错误');
        }
        if (sha1(MD5(trim($param['password'])) . $user['salt']) !== $user['password']) {
            return to_assign(1, '用户名或密码错误');
        }
        // $param['pwd'] = set_password($param['password'], $user['salt']);
        // if ($param['pwd'] !== $user['password']) {
        //     return to_assign(1, '用户名或密码错误');
        // }
        if ($user['status'] !== 1) {
            return to_assign(1, '该用户禁止登录，请联系客服。');
        }
        $data = [
            'ip' => request()->ip(),
        ];
        Db::name('author')->where(['id' => $user['id']])->update($data);
        $userInfo = [
            'id' => $user['id'],
            'username' => $user['mobile'],
            'nickname' => $user['nickname'],
            'headimgurl' => $user['headimg'],
        ];
        $session_user = get_config('app.session_author');
        Session::set($session_user, $userInfo);
        $token = make_token();
        set_cache($token, $userInfo, 7200);
        $userInfo['token'] = $token;
        return to_assign(0, '登录成功', $userInfo);
    }

    //重置密码
    public function resetpass()
    {
        if (request()->isAjax()) {
            $param = get_params();
            try {
                validate(AuthorCheck::class)->scene('reg')->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            if (!self::verifysms($param['mobile'], $param['code'])) {
                return to_assign(1, '手机短信验证码错误！');
            }
            $user = Db::name('author')->where([['mobile', '=', $param['mobile']]])->find();
            if (empty($user)) {
                return to_assign(1, '未找到对应账号');
            }
            $data = array(
                'password' => sha1(MD5($param['pwd']) . $user['salt']),
                'ip' => request()->ip(),
                'update_time' => time(),
            );
            $res = Db::name('author')->where(['id' => $user['id']])->strict(false)->field(true)->update($data);
            if ($res) {
                return to_assign(0, '重置成功，请登录');
            } else {
                return to_assign(1, '重置失败');
            }
        } else {
            return View();
        }
    }

    public function resetpasssms()
    {
        $param = get_params();
        try {
            validate(AuthorCheck::class)->scene('regsendsms')->check($param);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return to_assign(1, $e->getError());
        }
        $mobile = trim($param['mobile']);
        $user = Db::name('author')->where(['mobile' => $mobile])->find();
        if (empty($user)) {
            return to_assign(1, '未找到对应账号');
        }
        $today = strtotime(date("Y-m-d"), time()); //当天0点
        $code = mt_rand(100000, 999999);
        $verif = Db::name('sms_log')->where(array('account' => $mobile))->find();
        if (!empty($verif)) {
            if ($verif['expire_time'] > time()) {
                return to_assign(1, '已发出的验证码还有效，请输入！');
            }
            if ($verif['send_time'] > $today && $verif['count'] > 20) {
                return to_assign(1, '当前发送次数已用完！');
            }
        }
        //发送过程
        if (preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            $obj = auto_run_addons('smssend', ['code' => $code, 'phone' => $mobile]);
            if ($obj) {
                $result = isset($obj[0]) ? $obj[0] : $obj;
                if (!isJson($result)) return to_assign(1, '发送失败');
                $result = json_decode($result, true);
                if (isset($result['code']) && intval($result['code']) == 0) {
                    if (!empty($verif)) {
                        $data = array(
                            'account' => $mobile,
                            'count' => $verif['count']++,
                            'send_time' => time(),
                            'expire_time' => time() + 900,
                            'code' => $code,
                        );
                        $res = Db::name('sms_log')->where(['id' => $verif['id']])->strict(false)->field(true)->update($data);
                        if ($res) {
                            return to_assign(0, '发送成功');
                        } else {
                            return to_assign(1, '发送失败');
                        }
                    } else {
                        $data = array(
                            'account' => $mobile,
                            'count' => 1,
                            'send_time' => time(),
                            'expire_time' => time() + 900,
                            'code' => $code,
                        );
                        $id = Db::name('sms_log')->strict(false)->field(true)->insertGetId($data);
                        if ($id > 0) {
                            return to_assign(0, '发送成功');
                        } else {
                            return to_assign(1, '发送失败');
                        }
                    }
                } else {
                    return to_assign(1, $result['msg']);
                }
            } else {
                return to_assign(1, '发送失败');
            }
        } else {
            return to_assign(1, '手机号格式错误');
        }
    }

    //退出登录
    public function login_out()
    {
        $session_user = get_config('app.session_author');
        Session::delete($session_user);
        return to_assign(0, "退出成功");
    }

    //注册
    public function register()
    {
        if (!empty(get_login_author('id'))) {
            $url = (string) Route::buildUrl('user/index');
            redirect($url)->send();
        }
        return View();
    }

    //发送验证码
    public function regsms()
    {
        $param = get_params();
        try {
            validate(AuthorCheck::class)->scene('regsendsms')->check($param);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return to_assign(1, $e->getError());
        }
        $mobile = trim($param['mobile']);
        $user = Db::name('author')->where(['mobile' => $mobile])->find();
        if (!empty($user)) {
            return to_assign(1, '该账户已经存在');
        }
        $today = strtotime(date("Y-m-d"), time()); //当天0点
        $code = mt_rand(100000, 999999);
        $verif = Db::name('sms_log')->where(array('account' => $mobile))->find();
        if (!empty($verif)) {
            if ($verif['expire_time'] > time()) {
                return to_assign(1, '已发出的验证码还有效，请输入！');
            }
            if ($verif['send_time'] > $today && $verif['count'] > 20) {
                return to_assign(1, '当前发送次数已用完！');
            }
        }
        //发送过程
        if (preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            $obj = auto_run_addons('smssend', ['code' => $code, 'phone' => $mobile]);
            if ($obj) {
                $result = isset($obj[0]) ? $obj[0] : $obj;
                if (!isJson($result)) return to_assign(1, '发送失败');
                $result = json_decode($result, true);
                if (isset($result['code']) && intval($result['code']) == 0) {
                    if (!empty($verif)) {
                        $data = array(
                            'account' => $mobile,
                            'count' => $verif['count']++,
                            'send_time' => time(),
                            'expire_time' => time() + 900,
                            'code' => $code,
                        );
                        $res = Db::name('sms_log')->where(['id' => $verif['id']])->strict(false)->field(true)->update($data);
                        if ($res) {
                            return to_assign(0, '发送成功');
                        } else {
                            return to_assign(1, '发送失败');
                        }
                    } else {
                        $data = array(
                            'account' => $mobile,
                            'count' => 1,
                            'send_time' => time(),
                            'expire_time' => time() + 900,
                            'code' => $code,
                        );
                        $id = Db::name('sms_log')->strict(false)->field(true)->insertGetId($data);
                        if ($id > 0) {
                            return to_assign(0, '发送成功');
                        } else {
                            return to_assign(1, '发送失败');
                        }
                    }
                } else {
                    return to_assign(1, $result['msg']);
                }
            } else {
                return to_assign(1, '发送失败');
            }
        } else {
            return to_assign(1, '手机号格式错误');
        }
    }

    //验证短信验证码
    protected static function verifysms($phone, $code)
    {
        $verif = Db::name('sms_log')->where(array('account' => $phone, 'code' => $code))->find();
        if (empty($verif)) {
            return false;
        } else {
            if ($verif['expire_time'] < time()) {
                return false;
            }
        }
        return true;
    }

    //提交注册
    public function reg_submit()
    {
        $param = get_params();
        try {
            validate(AuthorCheck::class)->scene('reg')->check($param);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            return to_assign(1, $e->getError());
        }
        if (!self::verifysms($param['mobile'], $param['code'])) {
            return to_assign(1, '手机短信验证码错误！');
        }
        $user = Db::name('author')->where([['mobile', '=', $param['mobile']]])->find();
        if (!empty($user)) {
            return to_assign(1, '此手机号已注册！');
        }
        $time = (string) time();
        $salt = substr(MD5($time), 0, 6);
        $data = array(
            'mobile' => $param['mobile'],
            'salt' => $salt,
            'password' => sha1(MD5($param['pwd']) . $salt),
            'ip' => request()->ip(),
            'create_time' => time(),
            'status' => 1,
            'nickname' => $this->randNickname(),
        );
        $uid = Db::name('author')->strict(false)->field(true)->insertGetId($data);
        $userInfo = [
            'id' => $uid,
            'username' => $data['mobile'],
            'nickname' => $data['nickname'],
            'headimgurl' => '',
        ];
        $session_user = get_config('app.session_author');
        Session::set($session_user, $userInfo);
        $token = make_token();
        set_cache($token, $userInfo, 7200);
        return to_assign(0, '注册成功');
    }

    //随机昵称
    private function randNickname()
    {
        $nickname = get_system_config('web', 'title') . '_' . set_salt(10);
        $data = Db::name('author')->where(array('nickname' => $nickname))->find();
        if (empty($data)) {
            return $nickname;
        } else {
            $this->randNickname();
        }
    }
}
