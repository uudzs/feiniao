<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\Db;
use think\facade\View;
use ZipArchive;
use think\facade\Config;
use app\admin\validate\LoginValidate;
use think\exception\ValidateException;
use think\facade\Request;

set_time_limit(0);
ini_set('memory_limit', '256M');

class Upgrade extends BaseController
{

    private static $tokenKey = 'union_token';

    public function index()
    {
        if (request()->isAjax()) {
            $param = get_params();
            if (!isset($param['id']) || empty($param['id'])) return to_assign(1, 'ID错误');
            $url = get_config('upgrade.official_api_url') . 'info' . '/' . $param['id'];
            $content = Http::doGet($url);
            if (empty($content)) return to_assign(1, '获取信息失败');
            $result = json_decode($content, true);
            if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
            if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
            $info = $result['data'];
            if (empty($info['path']) && empty($info['sql'])) return to_assign(1, '不存在升级项');
            if (!empty($info['path'])) {
                $relativepath = 'runtime' . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . $info['version'] . DIRECTORY_SEPARATOR;
                $path = app()->getRootPath() . $relativepath;
                if (!createDirectory($path)) {
                    return to_assign(1, '创建' . $path . '目录失败');
                }
                $zipfile = Http::doGet($info['path']);
                if (!$zipfile || empty($zipfile)) {
                    return to_assign(1, '读取远程升级文件错误，请检测网络！');
                }
                $filepath = $path . $info['version'] . '.zip';
                if (false === @file_put_contents($filepath, $zipfile)) {
                    return to_assign(1, '保存文件错误，请检测文件夹写入权限！');
                }
                if (!is_file($filepath)) return to_assign(1, '升级包保存失败');
                if (!class_exists('ZipArchive')) {
                    return to_assign(1, '请手动解压' . $filepath . '到根目录。');
                }
                try {
                    $zip = new ZipArchive;
                    $res = $zip->open($filepath);
                    if ($res === TRUE) {
                        $zip->extractTo($path);
                        $zip->close();
                        unlink($filepath);
                        self::get_allfiles($path, $files);
                        foreach ($files as $key => $value) {
                            $destination =  str_replace($relativepath, '', $value);
                            $destination =  str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $destination);
                            if ($this->copyfile($value, $destination)) {
                                unlink($value);
                            }
                        }
                        $this->deleteDir($path);
                    } else {
                        unlink($filepath);
                        return to_assign(1, '解压文件失败，请检查目录权限。');
                    }
                } catch (\Exception $e) {
                    return to_assign(1, $e->getMessage());
                }
            }
            if (!empty($info['sql'])) {
                $this->runsql($info['sql']);
            }
            if (!empty($info['update_role'])) {
                $this->update_role();
            }
            return to_assign(0, '升级成功');
        } else {
            $list = $this->get_system_version();
            View::assign('list', isset($list['data']) ? $list['data'] : []);
            return view();
        }
    }

    private function update_role()
    {
        $uid = get_login_admin('id');
        if (intval($uid) == 1) {
            $list = Db::name('AdminRule')->field('id')->where('status', 1)->select()->toArray();
            $ids = array_column($list, 'id');
            if ($ids) {
                $rules = implode(',', $ids);
                $newGroup['id'] = $uid;
                $newGroup['rules'] = $rules;
                Db::name('AdminGroup')->strict(false)->field(true)->update($newGroup);
            }
        }
    }

    private function copyfile($source, $destination)
    {
        $destinationDirectory = dirname($destination);
        if (!is_dir($destinationDirectory)) {
            mkdir($destinationDirectory, 0755, true);
        }
        if (false === file_put_contents($destination, file_get_contents($source))) {
            return false;
        }
        return true;
    }

    private static function get_allfiles($path, &$files)
    {
        if (is_dir($path)) {
            $dp = dir($path);
            while ($file = $dp->read()) {
                if ($file !== "." && $file !== "..") {
                    self::get_allfiles($path . DIRECTORY_SEPARATOR . $file, $files);
                }
            }
            $dp->close();
        }
        if (is_file($path)) {
            $files[] =  $path;
        }
    }

    private function deleteDir($folder)
    {
        if (is_dir($folder)) {
            try {
                $files = scandir($folder);
                foreach ($files as $file) {
                    if ($file != "." && $file != "..") {
                        $file = $folder . "/" . $file;
                        if (is_dir($file)) {
                            $this->deleteDir($file);
                        } else {
                            unlink($file);
                        }
                    }
                }
                rmdir($folder);
            } catch (\Exception $e) {
                return to_assign(1, $e->getMessage());
            }
        }
        return true;
    }

    private function copy_addone_file($addone, $info)
    {
        $relativepath = 'runtime' . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . $addone['name'] . DIRECTORY_SEPARATOR;
        $path = app()->getRootPath() . $relativepath;
        if (!createDirectory($path)) {
            return to_assign(1, '创建' . $path . '目录失败');
        }
        $zipfile = Http::doGet($info['path']);
        if (!$zipfile || empty($zipfile)) {
            return to_assign(1, '读取远程升级文件错误，请检测网络！');
        }
        $filepath = $path . $addone['name'] . '.zip';
        if (false === @file_put_contents($filepath, $zipfile)) {
            return to_assign(1, '保存文件错误，请检测文件夹写入权限！');
        }
        if (!is_file($filepath)) return to_assign(1, '升级包保存失败');
        if (!class_exists('ZipArchive')) {
            return to_assign(1, '请手动解压' . $filepath . '到根目录。');
        }
        try {
            $zip = new ZipArchive;
            $res = $zip->open($filepath);
            if ($res === TRUE) {
                $zip->extractTo($path);
                $zip->close();
                unlink($filepath);
                self::get_allfiles($path, $files);
                $file = app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $addone['name'] . DIRECTORY_SEPARATOR . 'info.ini';
                $config_file = app()->getRootPath() . 'addons' . DIRECTORY_SEPARATOR . $addone['name'] . DIRECTORY_SEPARATOR . 'config.php';
                foreach ($files as $key => $value) {
                    $destination =  str_replace('runtime' . DIRECTORY_SEPARATOR . 'upgrade', 'addons', $value);
                    if (basename($value) == 'config.php' && is_file($config_file)) {
                        $new_config = include_once $value;
                        $old_config = include_once $config_file;
                        $config = array_replace_recursive($old_config, $new_config);
                        if ($config) {
                            file_put_contents($config_file, '<?php' . "\n" . 'return ' . var_export($config, true) . ';');
                        }
                        continue;
                    }
                    if (basename($value) == 'info.ini' && is_file($file)) {
                        // 拼接要写入的数据
                        $str = '';
                        foreach ($addone as $k => $v) {
                            if ($k == 'version') {
                                $v = $info['version'];
                            }
                            $str .= $k . " = " . $v . "\n";
                        }
                        if ($handle = fopen($file, 'w')) {
                            fwrite($handle, $str);
                            fclose($handle);
                        }
                        continue;
                    }
                    if ($this->copyfile($value, $destination)) {
                        unlink($value);
                    }
                }
                $this->deleteDir($path);
            } else {
                unlink($filepath);
                return to_assign(1, '解压文件失败，请检查目录权限。');
            }
        } catch (\Exception $e) {
            return to_assign(1, $e->getMessage());
        }
        return true;
    }

    private function runsql($sql)
    {
        if (empty($sql)) return false;
        $sql = str_replace("\r", "\n", $sql);
        $sql = explode("\n", $sql);
        $templine = '';
        foreach ($sql as $line) {
            if (substr($line, 0, 2) == '--' || $line == '' || substr($line, 0, 2) == '/*')
                continue;
            $templine .= $line;
            if (substr(trim($line), -1, 1) == ';') {
                // 不区分大小写替换前缀
                $templine = str_ireplace('__PREFIX__', Config::get('database.connections.mysql.prefix'), $templine);
                // 忽略数据库中已经存在的数据
                $templine = str_ireplace('INSERT INTO ', 'INSERT IGNORE INTO ', $templine);
                try {
                    Db::execute($templine);
                } catch (\PDOException $e) {
                    //$e->getMessage();
                }
                $templine = '';
            }
        }
        return true;
    }

    public function check_system_upgrade()
    {
        if (request()->isAjax()) {
            return table_assign(0, '', $this->get_system_version());
        }
    }

    public function plugin_check()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $name = isset($param['name']) ? trim($param['name']) : '';
            if (empty($name)) {
                return to_assign(1, '插件信息不存在');
            }
            if (!get_addons_is_enable($name)) return to_assign(1, '插件没有安装或启用');
            $info = get_addons_info($name);
            $url = get_config('upgrade.official_api_url') . 'plugincheck' . '/' . $name . '/' . $info['version'];
            $content = Http::doGet($url);
            if (empty($content)) return to_assign(1, '获取信息失败');
            $result = json_decode($content, true);
            if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
            if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
            if (!isset($result['data']['update'])) return to_assign(1, '数据错误');
            return to_assign(0, '检查成功', $result['data']['update']);
        }
    }

    public function plugin_update()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $name = isset($param['name']) ? trim($param['name']) : '';
            if (empty($name)) {
                return to_assign(1, '插件信息不存在');
            }
            if (!get_addons_is_enable($name)) return to_assign(1, '插件没有安装或启用');
            $addoneinfo = get_addons_info($name);
            $url = get_config('upgrade.official_api_url') . 'pluginupgrade' . '/' . $name;
            $token = get_cache(self::$tokenKey);
            if (empty($token)) {
                return to_assign(1, '请先登录联盟账号');
            }
            $content = Http::doGet($url);
            if (empty($content)) return to_assign(1, '获取信息失败');
            $result = json_decode($content, true);
            if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
            if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
            $info = $result['data'];
            if (empty($info['path']) && empty($info['sql'])) return to_assign(1, '不存在升级项');
            if (!empty($info['path'])) {
                $this->copy_addone_file($addoneinfo, $info);
            }
            if (!empty($info['sql'])) {
                $this->runsql($info['sql']);
            }
            return to_assign(0, '升级成功');
        }
    }

    public function plugin_install()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $name = isset($param['name']) ? trim($param['name']) : '';
            if (empty($name)) {
                return to_assign(1, '插件信息不存在');
            }
            $token = get_cache(self::$tokenKey);
            if (empty($token)) {
                return to_assign(1, '请先登录联盟账号');
            }
            $list = get_addons_list();
            $plugin = array_column($list, null, 'name');
            if (isset($plugin[$name]) && $plugin[$name]) return to_assign(1, '已经安装过此插件了');
            $url = get_config('upgrade.official_api_url') . 'pluginupgrade' . '/' . $name;
            $content = Http::doGet($url);
            if (empty($content)) return to_assign(1, '获取信息失败');
            $result = json_decode($content, true);
            if (!empty($result['code'])) return to_assign($result['code'], $result['msg'] ?? '请求错误', $result['data'] ?? []);
            if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
            $info = $result['data'];
            if (empty($info['path']) && empty($info['sql'])) return to_assign(1, '插件无效');
            if (!empty($info['path'])) {
                $this->copy_addone_file(['name' => $name], $info);
            }
            if (!empty($info['sql'])) {
                $this->runsql($info['sql']);
            }
            return to_assign(0, '安装成功');
        }
    }

    public function union_plugin()
    {
        $url = get_config('upgrade.official_api_url') . 'plugin';
        $content = Http::doGet($url);
        if (empty($content)) return to_assign(1, '获取信息失败');
        $result = json_decode($content, true);
        if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
        if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
        return view('union_plugin', ['list' => $result['data']]);
    }

    public function theme_market()
    {
        $url = get_config('upgrade.official_api_url') . 'theme';
        $content = Http::doGet($url);
        if (empty($content)) return to_assign(1, '获取信息失败');
        $result = json_decode($content, true);
        if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
        if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
        return view('theme_market', ['list' => $result['data']]);
    }

    public function theme_install()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $name = isset($param['name']) ? trim($param['name']) : '';
            if (empty($name)) {
                return to_assign(1, '主题信息不存在');
            }
            $token = get_cache(self::$tokenKey);
            if (empty($token)) {
                return to_assign(1, '请先登录联盟账号');
            }
            $list = list_dir('template');
            if (in_array($name, $list)) {
                return to_assign(1, '已经安装过此主题了');
            }
            $url = get_config('upgrade.official_api_url') . 'themeupgrade' . '/' . $name;
            $content = Http::doGet($url);
            if (empty($content)) return to_assign(1, '获取信息失败');
            $result = json_decode($content, true);
            if (!empty($result['code'])) return to_assign($result['code'], $result['msg'] ?? '请求错误', $result['data'] ?? []);
            if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
            $info = $result['data'];
            if (empty($info['path']) && empty($info['sql'])) return to_assign(1, '主题无效');
            if (strtolower($info['platform']) == 'separate') $this->separate($info); //独立版
            if (!empty($info['path'])) {
                $relativepath = 'runtime' . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR;
                $path = app()->getRootPath() . $relativepath;
                if (!createDirectory($path)) {
                    return to_assign(1, '创建' . $path . '目录失败');
                }
                $zipfile = Http::doGet($info['path']);
                if (!$zipfile || empty($zipfile)) {
                    return to_assign(1, '读取远程文件错误，请检测网络！');
                }
                $filepath = $path . $info['name'] . '.zip';
                if (false === @file_put_contents($filepath, $zipfile)) {
                    return to_assign(1, '保存文件错误，请检测文件夹写入权限！');
                }
                if (!is_file($filepath)) return to_assign(1, '文件保存失败');
                if (!class_exists('ZipArchive')) {
                    return to_assign(1, '请手动解压' . $filepath . '到根目录。');
                }
                try {
                    $zip = new ZipArchive;
                    $res = $zip->open($filepath);
                    if ($res === TRUE) {
                        $zip->extractTo($path);
                        $zip->close();
                        unlink($filepath);
                        self::get_allfiles($path, $files);
                        foreach ($files as $key => $value) {
                            if (strpos($value, 'public') !== false) {
                                $destination =  str_replace('runtime' . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR, '', $value);
                            } else {
                                $destination =  str_replace('runtime' . DIRECTORY_SEPARATOR . 'upgrade', 'template', $value);
                            }
                            if ($this->copyfile($value, $destination)) {
                                unlink($value);
                            }
                        }
                        $this->deleteDir($path);
                    } else {
                        unlink($filepath);
                        return to_assign(1, '解压文件失败，请检查目录权限。');
                    }
                } catch (\Exception $e) {
                    return to_assign(1, $e->getMessage());
                }
            }
            if (!empty($info['sql'])) {
                $this->runsql($info['sql']);
            }
            $path = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $name;
            if (is_dir($path)) {
                $public = $path . DIRECTORY_SEPARATOR . 'public';
                if (is_dir($public)) $this->deleteDir($public);
                return to_assign(0, '安装成功');
            } else {
                return to_assign(1, '安装失败');
            }
        }
    }

    private function separate($info)
    {
        $path = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'h5' . DIRECTORY_SEPARATOR;
        if (!createDirectory($path)) {
            return to_assign(1, '创建' . $path . '目录失败');
        }
        $zipfile = Http::doGet($info['path']);
        if (!$zipfile || empty($zipfile)) {
            return to_assign(1, '读取远程文件错误，请检测网络！');
        }
        $filepath = $path . $info['name'] . '.zip';
        if (false === @file_put_contents($filepath, $zipfile)) {
            return to_assign(1, '保存文件错误，请检测文件夹写入权限！');
        }
        if (!is_file($filepath)) return to_assign(1, '文件保存失败');
        if (!class_exists('ZipArchive')) {
            return to_assign(1, '请手动解压' . $filepath . '到根目录。');
        }
        $zip = new ZipArchive;
        $res = $zip->open($filepath);
        if ($res === TRUE) {
            $zip->extractTo($path);
            $zip->close();
            unlink($filepath);
        }
        if (!empty($info['sql'])) {
            $this->runsql($info['sql']);
        }
        $indexPath = $path . 'index.html';
        if (file_exists($indexPath)) {
            $index_content = file_get_contents($indexPath);
            $title = get_seo_str('home', 'home_title');
            $html = preg_replace('/<title\b[^>]*>[\s\S]*?<\/title>/i', '<title>' . $title . '</title>', $index_content);
            $conf = get_system_config('web');
            if (isset($conf['code']) && $conf['code']) {
                $html = preg_replace('/<script>\s*\/\*BAIDU_STAT\*\/\s*<\/script>/i', $conf['code'], $html);
            }
            file_put_contents($indexPath, $html);
            if (isset($conf['logo']) && $conf['logo']) {
                $logopath = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . $conf['logo'];
                if (file_exists($logopath)) {
                    $save_path = $path . 'static' . DIRECTORY_SEPARATOR . 'logo.png';
                    $this->copyfile($logopath, $save_path);
                }
            }
            $js_dir = $path . 'static' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR;
            if (!is_dir($js_dir)) return to_assign(1, '安装失败');
            self::get_allfiles($js_dir, $files);
            foreach ($files as $key => $value) {
                $content = file_get_contents($value);
                if (mb_strpos($content, '飞鸟阅读') !== false) {
                    $content = str_replace(['飞鸟阅读'], [$conf['title']], $content);
                    file_put_contents($value, $content);
                }
            }
            $js_conf_path = $path . 'static' . DIRECTORY_SEPARATOR . 'config.js';
            if (file_exists($js_conf_path)) {
                $content = file_get_contents($js_conf_path);
                if (strpos($content, 'apiUrl') !== false) {
                    $newUrl = '/api/v1';
                    $pattern = '/var apiUrl = `https:\/\/[^`]+`;/';
                    $replacement = 'var apiUrl = `' . $newUrl . '`;';
                    $content = preg_replace($pattern, $replacement, $content);
                    file_put_contents($js_conf_path, $content);
                }
            }
            $config = get_config('theme');
            $config['template_separate'] = $info['name'];
            $config_file = app()->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'theme.php';
            file_put_contents($config_file, '<?php' . "\n" . 'return ' . var_export($config, true) . ';');
            return to_assign(0, '安装成功');
        } else {
            return to_assign(1, '安装失败');
        }
    }

    public function theme_pay()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $ordersn = isset($param['ordersn']) ? trim($param['ordersn']) : '';
            if (empty($ordersn)) {
                return to_assign(1, '订单号不存在！');
            }
            $paynumber = isset($param['paynumber']) ? trim($param['paynumber']) : '';
            if (empty($paynumber)) {
                return to_assign(1, '订单号不存在！');
            }
            $token = get_cache(self::$tokenKey);
            if (empty($token)) {
                return to_assign(1, '请先登录联盟账号');
            }
            $url = get_config('upgrade.official_api_url') . 'themepay' . '/' . $ordersn . '/' . $paynumber;
            $content = Http::doGet($url);
            if (empty($content)) return to_assign(1, '获取信息失败');
            $result = json_decode($content, true);
            if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
            return to_assign(0, '购买成功，审核中。');
        }
    }

    public function theme_check()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $name = isset($param['name']) ? trim($param['name']) : '';
            $version = isset($param['version']) ? trim($param['version']) : '';
            if (empty($name) || empty($version)) {
                return to_assign(1, '主题参数不存在');
            }
            $url = get_config('upgrade.official_api_url') . 'themecheck' . '/' . $name . '/' . $version;
            $content = Http::doGet($url);
            if (empty($content)) return to_assign(1, '获取信息失败');
            $result = json_decode($content, true);
            if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
            if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
            if (!isset($result['data']['update'])) return to_assign(1, '数据错误');
            return to_assign(0, '检查成功', $result['data']['update']);
        }
    }

    public function theme_update()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $name = isset($param['name']) ? trim($param['name']) : '';
            if (empty($name)) {
                return to_assign(1, '主题信息不存在');
            }
            $url = get_config('upgrade.official_api_url') . 'themeupgrade' . '/' . $name;
            $token = get_cache(self::$tokenKey);
            if (empty($token)) {
                return to_assign(1, '请先登录联盟账号');
            }
            $content = Http::doGet($url);
            if (empty($content)) return to_assign(1, '获取信息失败');
            $result = json_decode($content, true);
            if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
            if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
            $info = $result['data'];
            if (empty($info['path']) && empty($info['sql'])) return to_assign(1, '不存在升级项');
            if (strtolower($info['platform']) == 'separate') $this->separate($info); //独立版
            if (!empty($info['path'])) {
                $relativepath = 'runtime' . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR;
                $path = app()->getRootPath() . $relativepath;
                if (!createDirectory($path)) {
                    return to_assign(1, '创建' . $path . '目录失败');
                }
                $zipfile = Http::doGet($info['path']);
                if (!$zipfile || empty($zipfile)) {
                    return to_assign(1, '读取远程文件错误，请检测网络！');
                }
                $filepath = $path . $info['name'] . '.zip';
                if (false === @file_put_contents($filepath, $zipfile)) {
                    return to_assign(1, '保存文件错误，请检测文件夹写入权限！');
                }
                if (!is_file($filepath)) return to_assign(1, '文件保存失败');
                if (!class_exists('ZipArchive')) {
                    return to_assign(1, '请手动解压' . $filepath . '到根目录。');
                }
                try {
                    $zip = new ZipArchive;
                    $res = $zip->open($filepath);
                    if ($res === TRUE) {
                        $zip->extractTo($path);
                        $zip->close();
                        unlink($filepath);
                        self::get_allfiles($path, $files);
                        foreach ($files as $key => $value) {
                            if (strpos($value, 'public') !== false) {
                                $destination =  str_replace('runtime' . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . $info['name'] . DIRECTORY_SEPARATOR, '', $value);
                            } else {
                                $destination =  str_replace('runtime' . DIRECTORY_SEPARATOR . 'upgrade', 'template', $value);
                            }
                            if ($this->copyfile($value, $destination)) {
                                unlink($value);
                            }
                        }
                        $this->deleteDir($path);
                    } else {
                        unlink($filepath);
                        return to_assign(1, '解压文件失败，请检查目录权限。');
                    }
                } catch (\Exception $e) {
                    return to_assign(1, $e->getMessage());
                }
            }
            if (!empty($info['sql'])) {
                $this->runsql($info['sql']);
            }
            $path = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $name;
            if (is_dir($path)) {
                $public = $path . DIRECTORY_SEPARATOR . 'public';
                if (is_dir($public)) $this->deleteDir($public);
                return to_assign(0, '升级成功');
            } else {
                return to_assign(1, '升级失败');
            }
        }
    }

    public function theme_release()
    {
        if (request()->isAjax()) {
            $param = get_params();
            $name = isset($param['name']) ? trim($param['name']) : '';
            if (empty($name)) {
                return to_assign(1, '请选择主题！');
            }
            $token = get_cache(self::$tokenKey);
            if (empty($token)) {
                return to_assign(1, '请先登录联盟账号');
            }
            $path = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $name;
            if (!is_dir($path)) return to_assign(1, '此主题不存在！');
            $default = ['default', 'default_pc', 'default_mobile'];
            if (in_array($name, $default)) return to_assign(1, '默认主题不可提交！');
            $copyrightPath = $path . DIRECTORY_SEPARATOR . 'copyright.xml';
            if (!file_exists($copyrightPath)) return to_assign(1, 'copyright.xml配置文件不存在！');
            $xmlFile = file_get_contents($copyrightPath);
            $ob = simplexml_load_string($xmlFile);
            $json = json_encode($ob);
            $config = json_decode($json, true);
            if (empty($config)) return to_assign(1, '配置文件错误！');
            $coverPath = $path . DIRECTORY_SEPARATOR . 'cover.jpg';
            if (!file_exists($coverPath)) return to_assign(1, 'cover.jpg封面文件不存在！');
            $zippath = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $name . '.zip';
            if (file_exists($zippath)) {
                unlink($zippath);
            }
            $zip = new ZipArchive;
            if ($zip->open($zippath, ZipArchive::CREATE) === TRUE) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($files as $file) {
                    if ($file->isDir()) {
                        continue;
                    }
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($path) + 1);
                    $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
                }
                $zip->close();
                if (!file_exists($zippath)) return to_assign(1, '压缩文件不存在！');
                $data = ['file' => $zippath];
                $url = get_config('upgrade.official_api_url') . 'upload/permanent?type=package';
                $content = Http::doPost($url, $data, 60, [], true);
                if (empty($content)) return to_assign(1, '上传失败');
                $result = json_decode($content, true);
                if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
                if (!isset($result['data']) || empty($result['data'])) return to_assign(1, '数据不存在');
                $info = $result['data'];
                if (!isset($info['path']) || empty($info['path'])) return to_assign(1, '上传失败');
                $url = get_config('upgrade.official_api_url') . 'themerelease';
                $content = Http::doPost($url, [
                    'name' => $name,
                    'path' => $info['path'],
                ]);
                if (empty($content)) return to_assign(1, '提交失败');
                $result = json_decode($content, true);
                if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '提交错误');
                unlink($zippath);
                return to_assign(0, '提交成功。');
            } else {
                return to_assign(1, '压缩失败。');
            }
        } else {
            return view('theme_release');
        }
    }

    private function get_system_version()
    {
        $url = get_config('upgrade.official_api_url') . 'version';
        $version = get_config('upgrade.version');
        if ($version) {
            $url = $url . '/' . $version;
        }
        $content = Http::doGet($url);
        $upArray = json_decode($content, true);
        if (!empty($upArray['code'])) {
            return ['code' => 1, 'msg' => '无更新', 'data' => ''];
        }
        return $upArray;
    }

    public function union_login()
    {
        if (request()->isAjax()) {
            $param = get_params();
            try {
                validate(LoginValidate::class)->check($param);
            } catch (ValidateException $e) {
                // 验证失败 输出错误信息
                return to_assign(1, $e->getError());
            }
            $url = get_config('upgrade.official_api_url') . 'login/login';
            $data = [
                'account' => $param['username'],
                'password' => $param['password'],
                'scene' => 'account',
            ];
            $content = Http::doPost($url, $data);
            if (empty($content)) return to_assign(1, '获取信息失败');
            $result = json_decode($content, true);
            if (!empty($result['code'])) return to_assign(1, $result['msg'] ?? '请求错误');
            if (!isset($result['data']['token']) || empty($result['data']['token'])) return to_assign(1, '登录失败');
            set_cache(self::$tokenKey, $result['data']['token'], 604800);
            return to_assign(0, '登录成功');
        } else {
            return view('union_login', ['official_url' => str_replace('api/', '', get_config('upgrade.official_api_url')), 'islogin' => get_cache(self::$tokenKey) ? 1 : 0]);
        }
    }
}

class Http
{
    static public $way = 0;
    private static $tokenKey = 'union_token';

    //手动设置访问方式
    static public function setWay($way)
    {
        self::$way = intval($way);
    }

    static public function getSupport()
    {
        //如果指定访问方式，则按指定的方式去访问
        if (isset(self::$way) && in_array(self::$way, [1, 2, 3]))
            return self::$way;

        //自动获取最佳访问方式	
        if (function_exists('curl_init')) //curl方式
        {
            return 1;
        } else if (function_exists('fsockopen')) //socket
        {
            return 2;
        } else if (function_exists('file_get_contents')) //php系统函数file_get_contents
        {
            return 3;
        } else {
            return 0;
        }
    }

    //通过get方式获取数据
    static public function doGet($url, $timeout = 60, $header = "", $proxy = "")
    {
        if (empty($url) || empty($timeout))
            return false;
        if (!preg_match('/^(http|https)/is', $url))
            $url = "http://" . $url;
        $code = self::getSupport();
        switch ($code) {
            case 1:
                return self::curlGet($url, $timeout, $header, $proxy);
                break;
            case 2:
                return self::socketGet($url, $timeout, $header, $proxy);
                break;
            case 3:
                return self::phpGet($url, $timeout, $header, $proxy);
                break;
            default:
                return false;
        }
    }

    //通过POST方式发送数据
    static public function doPost($url, $post_data = [], $timeout = 60, $header = "", $post_file = false)
    {
        if (empty($url) || empty($post_data) || empty($timeout))
            return false;
        if (!preg_match('/^(http|https)/is', $url))
            $url = "http://" . $url;
        $code = self::getSupport();
        switch ($code) {
            case 1:
                return self::curlPost($url, $post_data, $timeout, $header, $post_file);
                break;
            case 2:
                return self::socketPost($url, $post_data, $timeout, $header, $post_file);
                break;
            case 3:
                return self::phpPost($url, $post_data, $timeout, $header, $post_file);
                break;
            default:
                return false;
        }
    }

    //通过POST方式发送数据
    static public function doFile($url, $file, $timeout = 120, $header = "")
    {
        if (empty($url) || empty($file) || empty($timeout))
            return false;
        if (!preg_match('/^(http|https)/is', $url))
            $url = "http://" . $url;
        $code = self::getSupport();
        switch ($code) {
            case 1:
                return self::curlPost($url, ['file' => $file], $timeout, $header, true);
                break;
            case 2:
                return self::socketPost($url, ['file' => $file], $timeout, $header, true);
                break;
            case 3:
                return self::phpPost($url, ['file' => $file], $timeout, $header, true);
                break;
            default:
                return false;
        }
    }

    //通过curl get数据
    static public function curlGet($url, $timeout = 60, $header = "", $proxy = "")
    {
        $header = empty($header) ? explode("\r\n", self::defaultHeader()) : (is_array($header) ? $header : [$header]);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        if (!empty($proxy)) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, $proxy['type']);
            curl_setopt($ch, CURLOPT_PROXY, $proxy['url']);
        }
        if (self::hasHttps($url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code == 200) {
            return $result;
        } else {
            return false;
        }
    }

    //通过curl post数据
    static public function curlPost($url, $post_data = [], $timeout = 60, $header = "", $post_file = false)
    {
        $header = empty($header) ? explode("\r\n", self::defaultHeader()) : (is_array($header) ? $header : [$header]);
        if ($post_file) {
            if (isset($post_data['file']) && $post_data['file']) {
                $fileData = $post_data['file'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $fileData);
                finfo_close($finfo);
                unset($post_data['file']);
                if (class_exists('\CURLFile')) {
                    $post_string = array('file' => new \CURLFile($fileData, $mime_type, basename($fileData)), 'data' => http_build_query($post_data));
                } else {
                    $post_string = array(
                        'file' => '@' . $fileData . ";type=" . $mime_type . ";filename=" . basename($fileData),
                        'data' => http_build_query($post_data),
                    );
                }
            } else {
                $post_string = http_build_query($post_data);
            }
        } else {
            $post_string = http_build_query($post_data);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); //模拟的header头
        if (self::hasHttps($url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code == 200) {
            return $result;
        } else {
            return false;
        }
    }

    //通过socket get数据
    static public function socketGet($url, $timeout = 60, $header = "", $proxy = "")
    {
        $header = empty($header) ? self::defaultHeader() : $header . "\r\n";
        $url2 = parse_url($url);
        if (!empty($proxy)) {
            $proxy_url = explode(':', $proxy['url']);
            $host_ip = $proxy_url[0];
            $url2['port'] = $proxy_url[1];
            $request = $url;
        } else {
            $url2["path"] = isset($url2["path"]) ? $url2["path"] : "/";
            $url2["query"] = isset($url2["query"]) ? "?" . $url2["query"] : "";
            $host_ip = @gethostbyname($url2["host"]);
            if (self::hasHttps($url)) {
                $host_ip = 'ssl://' . $url2['host'];
                $url2["port"] = isset($url2["port"]) ? $url2["port"] : 443;
            } else {
                $url2["port"] = isset($url2["port"]) ? $url2["port"] : 80;
            }
            $request =  $url2["path"] . $url2["query"];
        }
        if (($fsock = fsockopen($host_ip, $url2['port'], $errno, $errstr, $timeout)) < 0) {
            return false;
        }
        $in  = "GET " . $request . " HTTP/1.0\r\n";
        if (false === strpos($header, "Host:")) {
            $in .= "Host: " . $url2["host"] . "\r\n";
        }
        $in .= $header;
        $in .= "Connection: Close\r\n\r\n";
        if (!@fwrite($fsock, $in, strlen($in))) {
            @fclose($fsock);
            return false;
        }
        return self::GetHttpContent($fsock);
    }

    //通过socket post数据
    static public function socketPost($url, $post_data = [], $timeout = 60, $header = "", $post_file = false)
    {
        $header = empty($header) ? self::defaultHeader() : $header . "\r\n";
        if ($post_file) {
            $multipart_boundary = '---------------------------' . microtime(true);
            $header .= "Content-Type: multipart/form-data; boundary=" . $multipart_boundary . "\r\n";
            $file_contents = file_get_contents(realpath(substr($post_data['file'], 1)));
            $post_string =  "--" . $multipart_boundary . "\r\n" .
                "Content-Disposition: form-data; name=\"file\"; filename=\"" . basename($post_data['file']) . "\"\r\n" .
                "Content-Type: " . mime_content_type(realpath(substr($post_data['file'], 1))) . "\r\n\r\n" . $file_contents . "\r\n";
            $post_string .= "--" . $multipart_boundary . "--\r\n";
        } else {
            $header .= "Content-type: application/x-www-form-urlencoded\r\n";
            $post_string = http_build_query($post_data);
        }
        $url2 = parse_url($url);
        $url2["path"] = isset($url2["path"]) ? $url2["path"] : "/";
        $host_ip = @gethostbyname($url2["host"]);
        if (self::hasHttps($url)) {
            $host_ip = 'ssl://' . $url2['host'];
            $url2["port"] = isset($url2["port"]) ? $url2["port"] : 443;
        } else {
            $url2["port"] = isset($url2["port"]) ? $url2["port"] : 80;
        }
        if (($fsock = fsockopen($host_ip, $url2['port'], $errno, $errstr, $timeout)) < 0) {
            return false;
        }
        $request =  $url2["path"] . (!empty($url2["query"]) ? "?" . $url2["query"] : "");
        $in  = "POST " . $request . " HTTP/1.0\r\n";
        $in .= "Host: " . $url2["host"] . "\r\n";
        $in .= $header;
        $in .= "Content-Length: " . strlen($post_string) . "\r\n";
        $in .= "Connection: Close\r\n\r\n";
        $in .= $post_string . "\r\n\r\n";
        unset($post_string);
        if (!@fwrite($fsock, $in, strlen($in))) {
            @fclose($fsock);
            return false;
        }
        return self::GetHttpContent($fsock);
    }

    //通过file_get_contents函数get数据
    static public function phpGet($url, $timeout = 60, $header = "", $proxy = "")
    {
        $header = empty($header) ? self::defaultHeader() : $header;
        $opts = [
            'http' => [
                'protocol_version' => '1.0', //http协议版本(若不指定php5.2系默认为http1.0)
                'method' => "GET", //获取方式
                'timeout' => $timeout, //超时时间
                'header' => $header
            ]
        ];
        if (!empty($proxy)) {
            $opts['http']['proxy'] = 'tcp://' . $proxy['url'];
            $opts['http']['request_fulluri'] = true;
        }
        $context = stream_context_create($opts);
        return  @file_get_contents($url, false, $context);
    }

    //通过file_get_contents 函数post数据
    static public function phpPost($url, $post_data = [], $timeout = 60, $header = "", $post_file = false)
    {
        $header = empty($header) ? self::defaultHeader() : $header . "\r\n";
        if ($post_file) {
            $multipart_boundary = '---------------------------' . microtime(true);
            $header .= "Content-Type: multipart/form-data; boundary=" . $multipart_boundary;
            $file_contents = file_get_contents(realpath(substr($post_data['file'], 1)));
            $post_string =  "--" . $multipart_boundary . "\r\n" .
                "Content-Disposition: form-data; name=\"file\"; filename=\"" . basename($post_data['file']) . "\"\r\n" .
                "Content-Type: " . mime_content_type(realpath(substr($post_data['file'], 1))) . "\r\n\r\n" . $file_contents . "\r\n";
            $post_string .= "--" . $multipart_boundary . "--\r\n";
        } else {
            $post_string = http_build_query($post_data);
            $header .= "Content-length: " . strlen($post_string);
        }
        $opts = [
            'http' => [
                'protocol_version' => '1.0', //http协议版本(若不指定php5.2系默认为http1.0)
                'method' => "POST", //获取方式
                'timeout' => $timeout, //超时时间 
                'header' => $header,
                'content' => $post_string
            ]
        ];
        $context = stream_context_create($opts);
        return  @file_get_contents($url, false, $context);
    }

    //默认模拟的header头
    static private function defaultHeader()
    {
        $token = get_cache(self::$tokenKey);
        $header = "User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36\r\n";
        $header .= "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n";
        $header .= "Referer:" . Request::domain() . "\r\n";
        $header .= "token:" . $token . "\r\n";
        return $header;
    }

    //获取通过socket方式get和post页面的返回数据
    static private function GetHttpContent($fsock = null)
    {
        $out = null;
        while ($buff = @fgets($fsock, 2048)) {
            $out .= $buff;
        }
        fclose($fsock);
        $pos = strpos($out, "\r\n\r\n");
        $head = substr($out, 0, $pos);    //http head
        $status = substr($head, 0, strpos($head, "\r\n"));    //http status line
        $body = substr($out, $pos + 4, strlen($out) - ($pos + 4)); //page body
        if (preg_match("/^HTTP\/\d\.\d\s([\d]+)\s.*$/", $status, $matches)) {
            if (intval($matches[1]) / 100 == 2) {
                return $body;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    static private function hasHttps($url)
    {
        $matches = parse_url($url);
        if ($matches['scheme'] == 'https') {
            return true;
        } else {
            return false;
        }
    }
}
