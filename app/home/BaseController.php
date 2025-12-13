<?php

declare(strict_types=1);

namespace app\home;

use think\App;
use think\facade\View;
use think\exception\HttpResponseException;
use think\facade\Request;
use think\Response;
use think\facade\Cookie;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Event;

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
            'version' => get_config('upgrade.version'),
            'girlgenre' => app('app\common\model\Category')::FEMALE_CATEGORY_ID
        ];
        $domain_bind = get_config('app.domain_bind');
        $params['domain_bind'] = $domain_bind ? array_flip($domain_bind) : [];
        View::config(['view_path' => $this->view_path()]);
        $this->auth();
        View::assign('params', $params);
    }

    protected function view_path()
    {
        if (get_addons_is_enable('sitegroup')) {
            $result = hook('siteGroupHook');
            if ($result && isJson($result)) {
                $result = json_decode($result, true);
                if ($result && is_array($result)) {
                    if (isset($result['seo']) && $result['seo']) {
                        app()->instance('feiniaoseo', $result['seo']);
                        unset($result['seo']);
                    }
                    app()->instance('feiniaowebconfig', $result);
                    if (Request::isMobile() || isWeChat()) {
                        if (isset($result['template_mobile']) && $result['template_mobile']) {
                            $dir = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $result['template_mobile'] . DIRECTORY_SEPARATOR;
                            if (is_dir($dir)) {
                                return $dir;
                            }
                        }
                    } else {
                        if (isset($result['template_pc']) && $result['template_pc']) {
                            $dir = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $result['template_pc'] . DIRECTORY_SEPARATOR;
                            if (is_dir($dir)) {
                                return $dir;
                            }
                        }
                    }
                } else {
                    app()->instance('feiniaoseo', []);
                    app()->instance('feiniaowebconfig', []);
                }
            } else {
                app()->instance('feiniaoseo', []);
                app()->instance('feiniaowebconfig', []);
            }
        }
        $h5domain = get_system_config('web', 'h5domain');
        if (!empty($h5domain)) {
            if (request()->host() === trim($h5domain)) {
                return app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . get_config('theme.template_mobile') . DIRECTORY_SEPARATOR;
            }
        }
        if (Request::isMobile() || isWeChat()) {
            return app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . get_config('theme.template_mobile') . DIRECTORY_SEPARATOR;
        } else {
            return app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . get_config('theme.template_pc') . DIRECTORY_SEPARATOR;
        }
    }

    protected function auth()
    {
        try {
            $secrect = get_system_config('token', 'secrect');
            $token = Cookie::get(get_config('app.session_user'));
            if ($token) {
                $decoded = JWT::decode($token, new Key($secrect, 'HS256'));
                if ($decoded) {
                    $decoded_array = json_decode(json_encode($decoded), TRUE);
                    if ($decoded_array && isset($decoded_array)) {
                        $jwt_data = $decoded_array['data'];
                        if (isset($jwt_data['userid']) && !defined('JWT_UID')) {
                            define('JWT_UID', $jwt_data['userid']);
                            View::assign('JWT_UID', JWT_UID);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }

    public function usecache()
    {
        $addonscnf = [];
        $rootPath = app()->getRootPath();
        $config_file = $rootPath . 'addons' . DIRECTORY_SEPARATOR . 'makehtml' . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config_file)) {
            $addonscnf = (array) include $config_file;
        }
        if (!isset($addonscnf['open']['value']) || intval($addonscnf['open']['value']) !== 1) return false;
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
        if (!isset($addonscnf['open']['value']) || intval($addonscnf['open']['value']) !== 1) return false;
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
