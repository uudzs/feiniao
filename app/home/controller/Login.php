<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use think\facade\Db;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\View;
use EasyWeChat\Factory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Login extends BaseController
{
    //登录
    public function index()
    {
        // 获取来路URL
        $cookieKey = 'refererKey';
        $refererUrl = Request::instance()->server('HTTP_REFERER', '');
        if (!empty($refererUrl) && strpos($refererUrl, 'register') === false) {
            Cookie::set($cookieKey, $refererUrl);
        }
        if (Cookie::has($cookieKey)) $refererUrl = Cookie::get($cookieKey);
        if (!empty($refererUrl) && strpos($refererUrl, 'register') !== false) {
            $refererUrl = furl('/', [], true, 'home');
        }
        $refererUrl = $refererUrl ? $refererUrl : furl('/', [], true, 'home');
        View::assign('refererUrl', $refererUrl);
        add_user_log('view', '登录页面');
        return View();
    }

    public function register()
    {
        return View();
    }

    //微信授权登录回调
    public function wechat_oauth_callback()
    {
        try {
            $param = get_params();
            $wechat = get_system_config('wechat');
            $config = get_config('wechat');
            if (intval($wechat['official_open']) == 1) {
                $config['app_id'] = $wechat['appid'];
                $config['secret'] = $wechat['appsecret'];
                $config['token'] = $wechat['token'];
                $config['aes_key'] = $wechat['aes_key'];
                $app = Factory::officialAccount($config);
                $oauth = $app->oauth;
                // 获取 OAuth 授权结果用户信息
                $code = $param['code'];
                $user = $oauth->userFromCode($code);
                $userinfo = $user->toArray();
                $member = [];
                if (is_array($userinfo) && $userinfo && isset($userinfo['raw']) && isset($userinfo['raw']['openid']) && $userinfo['raw']['openid']) {
                    $where = ['platform' => 'wechat'];
                    if (isset($userinfo['raw']['unionid']) && $userinfo['raw']['unionid']) {
                        $where['unionid'] = $userinfo['raw']['unionid'];
                    } else {
                        $where['openid'] = $userinfo['raw']['openid'];
                    }
                    $third = Db::name('third')->where($where)->find();
                    if (empty($third)) {
                        $session_invite = get_config('app.session_invite');
                        $invite = Cookie::get($session_invite);
                        $pid = 0;
                        if (!empty($invite)) {
                            $senior = Db::name('user')->where(['qrcode_invite' => $invite])->find();
                            if (!empty($senior)) {
                                $pid = $senior['id'];
                            }
                        }
                        $salt = set_salt(20);
                        $add = [
                            'nickname' => isset($userinfo['raw']['nickname']) ? $userinfo['raw']['nickname'] : randNickname(),
                            'inviter' => $pid,
                            'salt' => $salt,
                            'coin' => 0,
                            'mobile_status' => 0,
                            'headimgurl' => isset($userinfo['raw']['headimgurl']) ? $userinfo['raw']['headimgurl'] : '',
                            'email' => isset($userinfo['email']) ? $userinfo['email'] : '',
                            'password' => set_password(set_salt(20), $salt),
                            'register_time' => time(),
                            'qrcode_invite' => get_invite_code(),
                            'register_ip' => request()->ip(),
                            'last_login_time' => time(),
                            'last_login_ip' => request()->ip(),
                            'login_num' => 1,
                        ];
                        $uid = Db::name('user')->strict(false)->field(true)->insertGetId($add);
                        if ($uid) {
                            $member = Db::name('user')->where(['id' => $uid])->find();
                            if (!empty($member)) {
                                $data = [
                                    'user_id' => $uid,
                                    'platform' => 'wechat',
                                    'apptype' => 'mp',
                                    'unionid' => isset($userinfo['raw']['unionid']) ? $userinfo['raw']['unionid'] : '',
                                    'openid' => $userinfo['raw']['openid'],
                                    'openname' => isset($userinfo['raw']['nickname']) ? $userinfo['raw']['nickname'] : '',
                                    'access_token' => isset($userinfo['token_response']['access_token']) ? $userinfo['token_response']['access_token'] : '',
                                    'refresh_token' => isset($userinfo['token_response']['refresh_token']) ? $userinfo['token_response']['refresh_token'] : '',
                                    'expires_in' => isset($userinfo['token_response']['expires_in']) ? $userinfo['token_response']['expires_in'] : 0,
                                    'createtime' => time(),
                                    'logintime' => time(),
                                ];
                                $data['expiretime'] = time() + intval($data['expires_in']);
                                $tid = Db::name('third')->strict(false)->field(true)->insertGetId($data);
                                //邀请
                                if (!empty($invite)) {
                                    $conf = get_system_config('reward');
                                    Cookie::delete($session_invite);
                                    if ($pid > 0) {
                                        //邀请奖励
                                        if (intval($conf['invite_reward']) > 0) {
                                            Db::startTrans();
                                            try {
                                                // 执行数据库操作
                                                Db::name('user')->where('id', $pid)->inc('coin', intval($conf['invite_reward']))->update();
                                                add_coin_log($pid, intval($conf['invite_reward']), 2, '邀请一个好友奖励，好友ID：' . $uid);
                                                Db::name('task')->strict(false)->field(true)->insertGetId([
                                                    'user_id' => $pid,
                                                    'taskid' => $uid,
                                                    'type' => 3,
                                                    'status' => 1,
                                                    'title' => '邀请一个好友奖励',
                                                    'task_date' => date('Y-m-d'),
                                                    'reward' => intval($conf['invite_reward']),
                                                    'ip' => app('request')->ip(),
                                                    'create_time' => time()
                                                ]);
                                                // 提交事务
                                                Db::commit();
                                            } catch (\Exception $e) {
                                                // 回滚事务
                                                Db::rollback();
                                            }
                                        }
                                        //先生成奖励任务
                                        Db::name('task')->strict(false)->field(true)->insertGetId([
                                            'user_id' => $pid,
                                            'taskid' => $uid,
                                            'type' => 4,
                                            'status' => 0,
                                            'title' => '注册当天首次阅读章节',
                                            'task_date' => date('Y-m-d'),
                                            'reward' => intval($conf['invite_1_level']),
                                            'ip' => app('request')->ip(),
                                            'create_time' => time()
                                        ]);
                                        Db::name('task')->strict(false)->field(true)->insertGetId([
                                            'user_id' => $pid,
                                            'taskid' => $uid,
                                            'type' => 5,
                                            'status' => 0,
                                            'title' => '注册开始连续3天阅读章节',
                                            'task_date' => date('Y-m-d'),
                                            'reward' => intval($conf['invite_2_level']),
                                            'ip' => app('request')->ip(),
                                            'create_time' => time()
                                        ]);
                                        Db::name('task')->strict(false)->field(true)->insertGetId([
                                            'user_id' => $pid,
                                            'taskid' => $uid,
                                            'type' => 6,
                                            'status' => 0,
                                            'title' => '注册开始连续7天阅读章节',
                                            'task_date' => date('Y-m-d'),
                                            'reward' => intval($conf['invite_3_level']),
                                            'ip' => app('request')->ip(),
                                            'create_time' => time()
                                        ]);
                                    }
                                }
                            }
                        }
                    } else {
                        $member = Db::name('user')->where(['id' => $third['user_id']])->find();
                        if (!empty($member)) {
                            $data = [
                                'openname' => isset($userinfo['raw']['nickname']) ? $userinfo['raw']['nickname'] : '',
                                'access_token' => isset($userinfo['token_response']['access_token']) ? $userinfo['token_response']['access_token'] : '',
                                'refresh_token' => isset($userinfo['token_response']['refresh_token']) ? $userinfo['token_response']['refresh_token'] : '',
                                'expires_in' => isset($userinfo['token_response']['expires_in']) ? $userinfo['token_response']['expires_in'] : 0,
                                'updatetime' => time(),
                                'logintime' => time(),
                            ];
                            $data['expiretime'] = time() + intval($data['expires_in']);
                            Db::name('third')->where('id', $third['id'])->update($data);
                            $data = [
                                'last_login_time' => time(),
                                'last_login_ip' => request()->ip(),
                                'login_num' => $user['login_num'] + 1,
                            ];
                            $res = Db::name('user')->where(['id' => $member['id']])->update($data);
                        }
                    }
                    //登录
                    if (!empty($member)) {
                        $wechatcnf = get_system_config('token');
                        JWT::$leeway = 60; //当前时间减去60，把时间留点余地
                        $time = time(); //当前时间
                        $arr = [
                            'iss' => $wechatcnf['iss'], //签发者 可选
                            'aud' => $wechatcnf['aud'], //接收该JWT的一方，可选
                            'iat' => $time, //签发时间
                            'nbf' => $time - 1, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
                            'exp' => $time + $wechatcnf['exptime'], //过期时间,这里设置2个小时
                            'data' => [
                                //自定义信息，不要定义敏感信息
                                'userid' => $member['id'],
                            ]
                        ];
                        $token = JWT::encode($arr, $wechatcnf['secrect'], 'HS256');
                        if ($token) {
                            $session_user = get_config('app.session_user');
                            Cookie::set($session_user, $token);
                            return view('wechat', ['token' => $token, 'code' => 0, 'msg' => '跳转中', 'url' => furl('/', [], true, 'home')]);
                        } else {
                            return view('wechat', ['token' => '', 'code' => 1, 'msg' => '登录失败', 'url' => furl('/', [], true, 'home')]);
                        }
                    } else {
                        return view('wechat', ['token' => '', 'code' => 1, 'msg' => '登录失败', 'url' => furl('/', [], true, 'home')]);
                    }
                } else {
                    return view('wechat', ['token' => '', 'code' => 1, 'msg' => '微信登录失败', 'url' => furl('/', [], true, 'home')]);
                }
            } else {
                return view('wechat', ['token' => '', 'code' => 1, 'msg' => '未开启微信登录', 'url' => furl('/', [], true, 'home')]);
            }
        } catch (\EasyWeChat\Kernel\Exceptions\Exception $exception) {
            // 捕获easywechat的异常
            return view('wechat', ['token' => '', 'code' => 1, 'msg' => $exception->getMessage(), 'url' => furl('/', [], true, 'home')]);
        } catch (\Exception $e) {
            // 捕获其他PHP异常
            return view('wechat', ['token' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
        }
    }
}
