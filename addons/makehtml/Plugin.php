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

    public function makehtml($param = '')
    {
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
        $path = App::getRootPath() . 'public/';
        $view_suffix = get_config('view.view_suffix');
        //首次
        if (!empty($param) && isset($param['content']) && $param['content']) {
            $current_url = Request::url();
            if ($current_url) {                
                $filename = '';
                $pathinfo = pathinfo($current_url);
                if (isset($pathinfo['extension']) && $pathinfo['extension'] != $view_suffix) {
                    return false;
                }
                if ($pathinfo['dirname']) {
                    if (!self::createDirectory($path . $pathinfo['dirname'] . '/')) {
                        return false;
                    }
                    $filename = $path . $pathinfo['dirname'] . '/' . $pathinfo['basename'];
                }
                if ($filename) {
                    $File = new \think\template\driver\File();
                    $File->write($filename, $param['content']);
                }
            }
            return true;
        }
        //更新        
        if (!empty($param) && isset($param['type']) && $param['type'] == 'update') {
            $refererUrl = Request::instance()->server('HTTP_REFERER', ''); //来路            
            if (!empty($refererUrl)) {
                $parts = parse_url($refererUrl);
                if (isset($parts['path']) && $parts['path']) {
                    if ($parts['path'] != '/' && $parts['path'] != '/home/') {
                        $pathinfo = pathinfo($parts['path']);
                        if (isset($pathinfo['extension']) && $pathinfo['extension'] != $view_suffix) {
                            return false;
                        }
                        $filename = $path . $parts['path'];
                        if (is_dir($filename)) {
                            $filename . 'index.' . $view_suffix;
                        }
                        if (is_file($filename)) {
                            $file_time =  filectime($filename);
                            if ($file_time !== false) {
                                if ((time() - $file_time) > (intval($config['autouptime']['value']) * 60)) {
                                    unlink($filename);
                                }
                            }
                        }
                    }
                }
            }
            return true;
        }
    }

    private static function createDirectory($dir)
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                return false;
            }
        }
        return true;
    }
}
