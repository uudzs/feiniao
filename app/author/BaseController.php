<?php

declare(strict_types=1);

namespace app\author;

use think\App;
use think\facade\View;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Route;
use think\Response;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
        if (!get_system_config('power', 'author_open')) {
            throw new \think\exception\HttpException(404, '作者功能未开启！');
        }
        $params = [
            'module' => \think\facade\App::initialize()->http->getName(),
            'controller' => app('request')->controller(),
            'action' => app('request')->action(),
            'isLogin' => 0,
            'uid' => 0,
            'nickname' => '',
            'version' => get_config('upgrade.version'),
        ];
        $info = $this->checkLogin();
        if ($info) {
            $params['isLogin'] = 1;
            $userInfo = Db::name('author')->where(['id' => $info['id']])->find();
            $userInfo['nickname'] = empty($userInfo['nickname']) ? $userInfo['mobile'] : $userInfo['nickname'];
            $userInfo['gender'] = $userInfo['sex'];
            $userInfo['sex'] = ($userInfo['sex'] == 1) ? '男' : '女';
            View::assign('userInfo', $userInfo);
        } else {
            $url = (string) Route::buildUrl('login/index');
            redirect($url)->send();
        }
        $domain_bind = get_config('app.domain_bind');
        $params['domain_bind'] = $domain_bind ? array_flip($domain_bind) : [];
        View::assign('params', $params);
    }

    // 检测用户登录状态
    protected function checkLogin()
    {
        $session_user = get_config('app.session_author');
        $login_user = \think\facade\Session::get($session_user);
        if ($login_user && is_array($login_user)) {
            return $login_user;
        } else {
            return false;
        }
    }

     /**
     * 成功跳转方法
     * @param string $msg 提示信息
     * @param string $url 跳转地址
     * @param int $wait 等待时间（秒）
     */
    protected function success($msg = '', $url = null, $wait = 3)
    {
        $this->jumpTemplate(1, $msg, $url, $wait);
    }

    /**
     * 错误跳转方法
     * @param string $msg 提示信息
     * @param string $url 跳转地址
     * @param int $wait 等待时间（秒）
     */
    protected function error($msg = '', $url = null, $wait = 0)
    {
        $this->jumpTemplate(0, $msg, $url, $wait);
    }

    /**
     * 通用跳转模板处理
     */
    private function jumpTemplate($code, $msg, $url, $wait)
    {
        $url = $url ? url($url)->build() : 'javascript:history.back(-1);';
        $msg = lang((string)$msg);
        $result = [
            'code'  => $code,
            'msg'  => $msg,
            'url'  => $url,
            'wait' => $wait,
        ];
        $response = Response::create(View::fetch(get_config('app.dispatch_error_tmpl'), $result));
        throw new HttpResponseException($response);
    }

    //页面跳转方法
    public function redirectTo(...$args)
    {
        throw new HttpResponseException(redirect(...$args));
    }
}
