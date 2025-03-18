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
            'current_url' => Request::url(false), // 获取当前的url，不包括域名
            'module' => \think\facade\App::initialize()->http->getName(),
            'controller' => app('request')->controller(),
            'action' => app('request')->action(),
            'version' => get_config('upgrade.version')
        ];
        $domain_bind = get_config('app.domain_bind');
        $params['domain_bind'] = $domain_bind ? array_flip($domain_bind) : [];
        if (Request::isMobile() || isWeChat()) {
            View::config(['view_path' => CMS_ROOT . 'template/' . get_config('theme.template_mobile') . '/']);
        } else {
            View::config(['view_path' => CMS_ROOT . 'template/' . get_config('theme.template_pc') . '/']);
        }
        if (isWeChat()) {
            // $config = get_config('wechat');
            // $app = Factory::officialAccount($config);
            // $appconfig = $app->jssdk->buildConfig(array('updateAppMessageShareData', 'updateTimelineShareData'), false, false, false);
            // View::assign('appconfig', $appconfig);
        }
        View::assign('params', $params);
    }

    public function usecache()
    {
        $addonscnf = [];
        $rootPath = app()->getRootPath();
        $config_file = $rootPath . 'addons' . DIRECTORY_SEPARATOR . 'makehtml' . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config_file)) {
            $addonscnf = (array) include $config_file;
        }
        if (!isset($addonscnf['open']['value']) || intval($addonscnf['open']['value']) != 1) return false;
        if (!isset($addonscnf['autouptime']['value']) || intval($addonscnf['autouptime']['value']) <= 0) return false;
        if (Request::isMobile()) {
            $path = $rootPath . 'runtime/html/mobile/';
        } else {
            $path = $rootPath . 'runtime/html/pc/';
        }
        $view_suffix = get_config('view.view_suffix');
        $current_url = Request::url();
        if ($current_url) {
            $filename = '';
            $parts = parse_url($current_url);
            $pathinfo = pathinfo($current_url);
            if (isset($parts['path']) && $parts['path']) {
                if ($parts['path'] == '/' || $parts['path'] == '/home/') {
                    $pathinfo['basename'] = 'index.html';
                    $pathinfo['extension'] = $view_suffix;
                }
            }
            if (isset($pathinfo['extension']) && $pathinfo['extension'] != $view_suffix) {
                return false;
            }
            if ($pathinfo['dirname']) {
                $filename = $path . $pathinfo['dirname'] . '/' . $pathinfo['basename'];
            }
            if (is_file($filename)) {
                $file_time =  filectime($filename);
                if ($file_time !== false) {
                    if ((time() - $file_time) < (intval($addonscnf['autouptime']['value']) * 60)) {
                        echo file_get_contents($filename);
                        exit;
                    }
                }
            }
        }
        return true;
    }

    public function makecache($content)
    {
        $addonscnf = [];
        $rootPath = app()->getRootPath();
        $config_file = $rootPath . 'addons' . DIRECTORY_SEPARATOR . 'makehtml' . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config_file)) {
            $addonscnf = (array) include $config_file;
        }
        if (!isset($addonscnf['open']['value']) || intval($addonscnf['open']['value']) != 1) return false;
        if (!isset($addonscnf['autouptime']['value']) || intval($addonscnf['autouptime']['value']) <= 0) return false;
        if (Request::isMobile()) {
            $path = $rootPath . 'runtime/html/mobile/';
        } else {
            $path = $rootPath . 'runtime/html/pc/';
        }
        $view_suffix = get_config('view.view_suffix');
        if (!empty($content)) {
            $current_url = Request::url();
            if ($current_url) {
                $filename = '';
                $parts = parse_url($current_url);
                $pathinfo = pathinfo($current_url);
                if (isset($parts['path']) && $parts['path']) {
                    if ($parts['path'] == '/' || $parts['path'] == '/home/') {
                        $pathinfo['basename'] = 'index.html';
                        $pathinfo['extension'] = $view_suffix;
                    }
                }
                if (isset($pathinfo['extension']) && $pathinfo['extension'] != $view_suffix) {
                    return false;
                }
                if ($pathinfo['dirname']) {
                    if (!createDirectory($path . $pathinfo['dirname'] . '/')) {
                        return false;
                    }
                    $filename = $path . $pathinfo['dirname'] . '/' . $pathinfo['basename'];
                }
                $File = new \think\template\driver\File();
                $File->write($filename, $content);
                echo $content;
                exit;
            }
        }
    }

    //页面跳转方法
    public function redirectTo(...$args)
    {
        throw new HttpResponseException(redirect(...$args));
    }
}
