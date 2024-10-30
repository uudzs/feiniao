<?php

declare(strict_types=1);

namespace app\home;

use think\App;
use think\facade\View;
use think\exception\HttpResponseException;
use think\facade\Request;
use think\facade\Session;
use think\facade\Db;
use EasyWeChat\Factory;

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
            'current_url' => Request::url(false),// 获取当前的url，不包括域名
            'module' => \think\facade\App::initialize()->http->getName(),
            'controller' => app('request')->controller(),
            'action' => app('request')->action(),           
            'version' => get_config('webconfig.version'),
        ];        
        $domain_bind = get_config('app.domain_bind');
        $params['domain_bind'] = $domain_bind ? array_flip($domain_bind) : [];        
        if (Request::isMobile() || isWeChat()) {
            View::config(['view_path' => CMS_ROOT . 'template/' . (get_system_config('web', 'template') ?: 'default') . '/mobile/']);
        } else {
            View::config(['view_path' => CMS_ROOT . 'template/' . (get_system_config('web', 'template') ?: 'default') . '/']);
        }
        if (isWeChat()) {
            // $config = get_config('wechat');
            // $app = Factory::officialAccount($config);
            // $appconfig = $app->jssdk->buildConfig(array('updateAppMessageShareData', 'updateTimelineShareData'), false, false, false);
            // View::assign('appconfig', $appconfig);
        }
        View::assign('params', $params);
    }

    //页面跳转方法
    public function redirectTo(...$args)
    {
        throw new HttpResponseException(redirect(...$args));
    }
}