<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use think\facade\Config;
use think\facade\View;

class Logmanage extends BaseController
{
    /**
     * 日志管理页面
     * @return mixed
     */
    public function index()
    {
        // 获取当前日志配置状态
        $logConfig = Config::get('log');
        $isEnabled = $logConfig['enabled'] ?? false;

        // 获取日志统计信息
        $logStats = $this->getLogStats();

        View::assign('is_enabled', $isEnabled);
        View::assign('log_stats', $logStats);
        return View::fetch();
    }

    /**
     * 保存日志配置
     * @return mixed
     */
    public function saveconfig()
    {
        if (!$this->request->isPost()) {
            return json(['code' => 1, 'msg' => '请求方式错误']);
        }

        $enabled = $this->request->param('enabled', 0);

        try {
            // 更新配置文件
            $this->updateLogConfig((int)$enabled);

            return json(['code' => 0, 'msg' => '配置保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '配置保存失败：' . $e->getMessage()]);
        }
    }

    /**
     * 清理日志文件
     * @return mixed
     */
    public function cleanlogs()
    {
        try {
            $runtimePath = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;
            $logPath = $runtimePath . 'log';

            // 删除日志目录下的所有文件
            if (is_dir($logPath)) {
                $this->deleteDir($logPath);
            }

            // 删除各模块下的日志目录
            $modules = ['admin', 'api', 'home', 'install'];
            foreach ($modules as $module) {
                $moduleLogPath = $runtimePath . $module . DIRECTORY_SEPARATOR . 'log';
                if (is_dir($moduleLogPath)) {
                    $this->deleteDir($moduleLogPath);
                }
            }

            return json(['code' => 0, 'msg' => '日志清理成功']);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '日志清理失败：' . $e->getMessage()]);
        }
    }

    /**
     * 查看日志文件列表
     * @return mixed
     */
    public function loglist()
    {
        $logs = [];
        $runtimePath = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;

        // 获取主日志目录
        $logPath = $runtimePath . 'log';
        if (is_dir($logPath)) {
            $logs = array_merge($logs, $this->getLogFiles($logPath, 'main'));
        }

        // 获取各模块日志目录
        $modules = ['admin', 'api', 'home'];
        foreach ($modules as $module) {
            $moduleLogPath = $runtimePath . $module . DIRECTORY_SEPARATOR . 'log';
            if (is_dir($moduleLogPath)) {
                $logs = array_merge($logs, $this->getLogFiles($moduleLogPath, $module));
            }
        }

        return json(['code' => 0, 'data' => $logs]);
    }

    /**
     * 获取日志统计信息
     * @return array
     */
    private function getLogStats()
    {
        $stats = [
            'total_size' => 0,
            'file_count' => 0,
            'module_stats' => []
        ];

        $runtimePath = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR;

        // 统计主日志目录
        $logPath = $runtimePath . 'log';
        if (is_dir($logPath)) {
            $stat = $this->getDirStats($logPath);
            $stats['total_size'] += $stat['size'];
            $stats['file_count'] += $stat['count'];
            $stats['module_stats']['main'] = $stat;
        }

        // 统计各模块日志目录
        $modules = ['admin', 'api', 'home'];
        foreach ($modules as $module) {
            $moduleLogPath = $runtimePath . $module . DIRECTORY_SEPARATOR . 'log';
            if (is_dir($moduleLogPath)) {
                $stat = $this->getDirStats($moduleLogPath);
                $stats['total_size'] += $stat['size'];
                $stats['file_count'] += $stat['count'];
                $stats['module_stats'][$module] = $stat;
            }
        }

        // 格式化总大小
        $stats['total_size_formatted'] = format_bytes($stats['total_size']);

        return $stats;
    }

    /**
     * 获取目录统计信息
     * @param string $dir
     * @return array
     */
    private function getDirStats(string $dir): array
    {
        $size = 0;
        $count = 0;

        if (!is_dir($dir)) {
            return ['size' => 0, 'count' => 0];
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'log') {
                $size += $file->getSize();
                $count++;
            }
        }

        return [
            'size' => $size,
            'size_formatted' => format_bytes($size),
            'count' => $count
        ];
    }

    /**
     * 更新日志配置
     * @param int $enabled
     * @return void
     */
    private function updateLogConfig(int $enabled)
    {
        $configFile = config_path() . 'log.php';
        if (!file_exists($configFile)) {
            throw new \Exception('配置文件不存在');
        }

        $content = file_get_contents($configFile);

        // 更新enabled配置
        if ($enabled) {
            $content = preg_replace(
                "/'enabled'\s*=>\s*false/",
                "'enabled' => true",
                $content
            );
        } else {
            $content = preg_replace(
                "/'enabled'\s*=>\s*true/",
                "'enabled' => false",
                $content
            );
        }

        file_put_contents($configFile, $content);
    }

    /**
     * 递归获取日志文件列表
     * @param string $dir
     * @param string $module
     * @param array $logs
     * @return array
     */
    private function getLogFiles(string $dir, string $module, array $logs = []): array
    {
        if (!is_dir($dir)) {
            return $logs;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $logs = $this->getLogFiles($path, $module, $logs);
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'log') {
                $logs[] = [
                    'name' => $file,
                    'path' => str_replace(app()->getRuntimePath(), '', $path),
                    'size' => format_bytes(filesize($path)),
                    'size_raw' => filesize($path),
                    'modified' => date('Y-m-d H:i:s', filemtime($path)),
                    'module' => $module
                ];
            }
        }

        return $logs;
    }

    /**
     * 递归删除目录
     * @param string $dir
     * @return bool
     */
    private function deleteDir(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}
