<?php

namespace addons\makehtml;

use think\facade\App;
use think\Addons;
use think\facade\Request;

/**
 * 注意名字不可以修改，只能为Plugin
 */
class Plugin extends Addons    // 需继承think\Addons类
{
    /**
     * 插件安装方法
     * @return bool
     */
    public function install()
    {
        return true;
    }

    /**
     * 插件卸载方法
     * @return bool
     */
    public function uninstall()
    {
        return true;
    }

    public function runcache()
    {
        if (isWeChat()) return false;
        $info = $this->getInfo();
        // 插件禁用后不再进行上传
        if ($info['install'] == 0 || $info['status'] == 0) {
            return false;
        }
        $config = [];
        $rootPath = App::getRootPath();
        // 获取插件目录路径
        $config_file = $rootPath . 'addons' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config_file)) {
            $config = (array) include $config_file;
        }
        if (empty($config)) return false;
        if (!isset($config['open']['value']) || intval($config['open']['value']) != 1) return false;
        if (!isset($config['autouptime']['value']) || intval($config['autouptime']['value']) <= 0) return false;
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
                    if ((time() - $file_time) > (intval($config['autouptime']['value']) * 60)) {
                        return -1;
                    } else {
                        echo file_get_contents($filename);
                        exit;
                    }
                }
            } else {
                return -2;
            }
        }
        return false;
    }

    public function makehtml($param = '')
    {
        if (isWeChat()) return false;
        $info = $this->getInfo();
        // 插件禁用后不再进行上传
        if ($info['install'] == 0 || $info['status'] == 0) {
            return false;
        }
        $config = [];
        $rootPath = App::getRootPath();
        // 获取插件目录路径
        $config_file = $rootPath . 'addons' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config_file)) {
            $config = (array) include $config_file;
        }
        if (empty($config)) return false;
        if (!isset($config['open']['value']) || intval($config['open']['value']) != 1) return false;
        if (!isset($config['autouptime']['value']) || intval($config['autouptime']['value']) <= 0) return false;
        if (Request::isMobile()) {
            $path = $rootPath . 'runtime/html/mobile/';
        } else {
            $path = $rootPath . 'runtime/html/pc/';
        }
        $view_suffix = get_config('view.view_suffix');
        $File = new \think\template\driver\File();
        if (!empty($param) && isset($param['content']) && $param['content']) {
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
                    if (!self::createDirectory($path . $pathinfo['dirname'] . '/')) {
                        return false;
                    }
                    $filename = $path . $pathinfo['dirname'] . '/' . $pathinfo['basename'];
                }
                $File->write($filename, $param['content']);
                echo $param['content'];
                exit;
            }
        }
        return false;
    }

    private static function createDirectory($dir)
    {
        try {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
