<?php

declare(strict_types=1);

namespace app\author;

use think\App;
use think\facade\View;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\facade\Route;

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
        $params = [
            'module' => \think\facade\App::initialize()->http->getName(),
            'controller' => app('request')->controller(),
            'action' => app('request')->action(),
            'isLogin' => 0,
            'uid' => 0,
            'nickname' => '',
            'version' => get_config('webconfig.version'),
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
    //页面跳转方法
    public function redirectTo(...$args)
    {
        throw new HttpResponseException(redirect(...$args));
    }

    use \liliuwei\think\Jump;
}
