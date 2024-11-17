<?php

declare(strict_types=1);

namespace app\home\controller;

use app\home\BaseController;
use EasyWeChat\Factory;
use think\facade\Cookie;
use think\facade\View;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Request;
use think\facade\App;

class Index extends BaseController
{
    public function index()
    {
        if (isWeChat()) {
            $wechat = get_system_config('wechat');
            if (intval($wechat['official_open']) == 1) {
                $config = get_config('wechat');
                $session_user = get_config('app.session_user');
                JWT::$leeway = 60; //当前时间减去60，把时间留点余地
                $time = time(); //当前时间
                if (!Cookie::has($session_user)) {
                    $config = get_config('wechat');
                    $config['app_id'] = $wechat['appid'];
                    $config['secret'] = $wechat['appsecret'];
                    $config['token'] = $wechat['token'];
                    $config['aes_key'] = $wechat['aes_key'];
                    $app = Factory::officialAccount($config);
                    $oauth = $app->oauth;
                    $redirectUrl = $oauth->redirect();
                    redirect($redirectUrl)->send();
                } else {
                    $config = get_system_config('token');
                    try {
                        $token = Cookie::get($session_user);
                        if (count(explode('.', $token)) != 3) {
                            Cookie::delete($session_user);
                            return view('login/wechat', ['token' => '', 'code' => 1, 'msg' => '非法请求', 'url' => furl('/', [], true, 'home')]);
                        }
                        $decoded = JWT::decode($token, new Key($config['secrect'], 'HS256')); //HS256方式，这里要和签发的时候对应
                        $data = json_decode(json_encode($decoded), TRUE);
                        $jwt_data = $data['data'];
                        $uid = $jwt_data['userid'];
                    } catch (\Firebase\JWT\SignatureInvalidException $e) {  //签名不正确
                        Cookie::delete($session_user);
                        return view('login/wechat', ['token' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                    } catch (\Firebase\JWT\BeforeValidException $e) {  // 签名在某个时间点之后才能用
                        Cookie::delete($session_user);
                        return view('login/wechat', ['token' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                    } catch (\Firebase\JWT\ExpiredException $e) {  // token过期
                        Cookie::delete($session_user);
                        return view('login/wechat', ['token' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                    } catch (\Exception $e) {  //其他错误
                        Cookie::delete($session_user);
                        return view('login/wechat', ['token' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                    } catch (\UnexpectedValueException $e) {  //其他错误
                        Cookie::delete($session_user);
                        return view('login/wechat', ['token' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                    } catch (\DomainException $e) {  //其他错误
                        Cookie::delete($session_user);
                        return view('login/wechat', ['token' => '', 'code' => 1, 'msg' => $e->getMessage(), 'url' => furl('/', [], true, 'home')]);
                    }
                }
            }
        }
        if (Request::isMobile() || isWeChat()) {
            return View();
        } else {
            $addonscnf = [];
            $config_file = App::getRootPath() . 'addons' . DIRECTORY_SEPARATOR . 'makehtml' . DIRECTORY_SEPARATOR . 'config.php';
            if (is_file($config_file)) {
                $addonscnf = (array) include $config_file;
            }
            if (isset($addonscnf['open']['value']) && intval($addonscnf['open']['value']) == 1) {
                if (isset($addonscnf['autouptime']['value']) && intval($addonscnf['autouptime']['value']) > 1) {
                    $domain_bind = get_config('app.domain_bind');
                    $domain_bind = $domain_bind ? array_flip($domain_bind) : [];
                    $index_file = app()->getRootPath() . '/public/index_pc.' . config('view.view_suffix');
                    $home_file = app()->getRootPath() . '/public/home/index.' . config('view.view_suffix');
                    $home_index = false;
                    if ($domain_bind && isset($domain_bind['home']) && $domain_bind['home']) {
                        $home_index = true;
                    }
                    if (is_file($index_file)) {
                        $file_time =  filectime($index_file);
                        if ($file_time !== false) {
                            if ((time() - $file_time) > (intval($addonscnf['autouptime']['value']) * 60)) {
                                $content = View::fetch();
                                $File = new \think\template\driver\File();
                                $File->write($index_file, $content);
                                if (!$home_index) {
                                    $File->write($home_file, $content);
                                }
                            }
                        }
                        $content = file_get_contents($index_file);
                        echo $content;
                        exit;
                    } else {
                        $content = View::fetch();
                        $File = new \think\template\driver\File();
                        $File->write($index_file, $content);
                        if (!$home_index) {
                            $File->write($home_file, $content);
                        }
                        echo $content;
                        exit;
                    }
                }
            }
            return View();
        }
    }
}
