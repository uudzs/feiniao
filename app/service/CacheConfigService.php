<?php

namespace app\service;

use think\facade\Config;
use Exception;

/**
 * 缓存配置管理服务
 * 负责缓存配置的读取、写入和验证
 */
class CacheConfigService
{
    /**
     * 获取当前缓存配置
     * @return array
     */
    public static function getCurrentConfig(): array
    {
        $config = Config::get('cache');
        
        return [
            'default' => $config['default'] ?? 'file',
            'stores' => $config['stores'] ?? [],
            'current_type' => $config['default'] ?? 'file',
            'config_source' => 'file'
        ];
    }

    /**
     * 更新缓存配置文件
     * @param string $cacheType 缓存类型
     * @param array $config 配置参数
     * @return array
     */
    public static function updateConfig(string $cacheType, array $config): array
    {
        try {
            $configPath = config_path() . 'cache.php';
            
            if (!is_writable($configPath)) {
                return ['success' => false, 'message' => '配置文件不可写：' . $configPath];
            }

            // 读取现有配置
            $currentConfig = include $configPath;
            
            // 构建新的配置
            $newStoreConfig = self::buildStoreConfig($cacheType, $config);
            
            // 更新配置
            $currentConfig['stores'][$cacheType] = $newStoreConfig;
            
            // 写入配置文件
            $result = self::writeConfigFile($configPath, $currentConfig);
            
            if ($result['success']) {
                // 重新加载配置 - 更新内存中的配置
                $currentCacheConfig = Config::get('cache', []);
                if (!isset($currentCacheConfig['stores'])) {
                    $currentCacheConfig['stores'] = [];
                }
                $currentCacheConfig['stores'][$cacheType] = $newStoreConfig;
                Config::set($currentCacheConfig, 'cache');
            }
            
            return $result;

        } catch (Exception $e) {
            return ['success' => false, 'message' => '更新配置失败：' . $e->getMessage()];
        }
    }

    /**
     * 切换默认缓存类型
     * @param string $cacheType
     * @return array
     */
    public static function switchDefaultCache(string $cacheType): array
    {
        try {
            $configPath = config_path() . 'cache.php';
            
            if (!is_writable($configPath)) {
                return ['success' => false, 'message' => '配置文件不可写：' . $configPath];
            }

            // 读取现有配置
            $currentConfig = include $configPath;
            
            // 检查缓存类型是否存在
            if (!isset($currentConfig['stores'][$cacheType])) {
                return ['success' => false, 'message' => '缓存类型配置不存在：' . $cacheType];
            }
            
            // 更新默认缓存类型
            $currentConfig['default'] = $cacheType;
            
            // 写入配置文件
            $result = self::writeConfigFile($configPath, $currentConfig);
            
            if ($result['success']) {
                // 重新加载配置 - 更新内存中的配置
                $currentCacheConfig = Config::get('cache', []);
                if (!isset($currentCacheConfig['default'])) {
                    $currentCacheConfig['default'] = 'file';
                }
                if (!isset($currentCacheConfig['stores'])) {
                    $currentCacheConfig['stores'] = [];
                }
                $currentCacheConfig['default'] = $cacheType;
                Config::set($currentCacheConfig, 'cache');
                
                // 清除现有缓存实例
                self::clearCacheInstances();
            }
            
            return $result;

        } catch (Exception $e) {
            return ['success' => false, 'message' => '切换缓存失败：' . $e->getMessage()];
        }
    }

    /**
     * 构建存储配置
     * @param string $cacheType
     * @param array $params
     * @return array
     */
    private static function buildStoreConfig(string $cacheType, array $params): array
    {
        $config = ['type' => ucfirst($cacheType)];
        
        switch ($cacheType) {
            case 'file':
                $config = [
                    'type' => 'File',
                    'path' => $params['path'] ?? '',
                    'prefix' => $params['prefix'] ?? '',
                    'expire' => intval($params['expire'] ?? 0),
                    'tag_prefix' => 'tag:',
                    'serialize' => [],
                ];
                break;
                
            case 'redis':
                $config = [
                    'type' => 'redis',
                    'host' => $params['host'] ?? '127.0.0.1',
                    'port' => intval($params['port'] ?? 6379),
                    'password' => $params['password'] ?? '',
                    'select' => intval($params['select'] ?? 0),
                    'timeout' => intval($params['timeout'] ?? 0),
                    'expire' => intval($params['expire'] ?? 0),
                    'persistent' => !empty($params['persistent']),
                    'prefix' => $params['prefix'] ?? '',
                    'tag_prefix' => 'tag:',
                    'serialize' => [],
                ];
                break;
                
            case 'memcache':
                $config = [
                    'type' => 'memcache',
                    'host' => $params['host'] ?? '127.0.0.1',
                    'port' => intval($params['port'] ?? 11211),
                    'timeout' => intval($params['timeout'] ?? 1),
                    'expire' => intval($params['expire'] ?? 0),
                    'prefix' => $params['prefix'] ?? '',
                    'tag_prefix' => 'tag:',
                    'serialize' => [],
                ];
                break;
                
            case 'memcached':
                $config = [
                    'type' => 'memcached',
                    'host' => $params['host'] ?? '127.0.0.1',
                    'port' => intval($params['port'] ?? 11211),
                    'username' => $params['username'] ?? '',
                    'password' => $params['password'] ?? '',
                    'timeout' => intval($params['timeout'] ?? 1),
                    'expire' => intval($params['expire'] ?? 0),
                    'prefix' => $params['prefix'] ?? '',
                    'tag_prefix' => 'tag:',
                    'serialize' => [],
                ];
                break;
                
            case 'wincache':
                $config = [
                    'type' => 'wincache',
                    'prefix' => $params['prefix'] ?? '',
                    'expire' => intval($params['expire'] ?? 0),
                    'tag_prefix' => 'tag:',
                    'serialize' => [],
                ];
                break;
        }
        
        return $config;
    }

    /**
     * 写入配置文件
     * @param string $configPath
     * @param array $config
     * @return array
     */
    private static function writeConfigFile(string $configPath, array $config): array
    {
        try {
            // 备份原配置文件
            $backupPath = $configPath . '.backup.' . date('YmdHis');
            if (file_exists($configPath) && !copy($configPath, $backupPath)) {
                return ['success' => false, 'message' => '无法备份原配置文件'];
            }
            
            // 生成配置文件内容
            $content = self::generateConfigContent($config);
            
            // 验证生成的内容是否有效
            if (empty($content)) {
                return ['success' => false, 'message' => '生成的配置内容为空'];
            }
            
            // 写入新配置
            $result = file_put_contents($configPath, $content, LOCK_EX);
            if ($result === false) {
                // 恢复备份
                if (file_exists($backupPath)) {
                    copy($backupPath, $configPath);
                }
                return ['success' => false, 'message' => '写入配置文件失败'];
            }
            
            // 验证写入的文件是否可以正常解析
            $testConfig = null;
            try {
                ob_start();
                $testConfig = include $configPath;
                ob_end_clean();
            } catch (Exception $e) {
                // 如果解析失败，恢复备份
                if (file_exists($backupPath)) {
                    copy($backupPath, $configPath);
                }
                return ['success' => false, 'message' => '配置文件格式错误：' . $e->getMessage()];
            }
            
            if (!is_array($testConfig) || !isset($testConfig['default']) || !isset($testConfig['stores'])) {
                // 配置结构不正确，恢复备份
                if (file_exists($backupPath)) {
                    copy($backupPath, $configPath);
                }
                return ['success' => false, 'message' => '配置文件结构不正确'];
            }
            
            // 清理备份文件（保留最近5个）
            self::cleanupBackupFiles(dirname($configPath), 'cache.php.backup.');
            
            return ['success' => true, 'message' => '配置更新成功'];

        } catch (Exception $e) {
            // 如果有备份文件，尝试恢复
            if (isset($backupPath) && file_exists($backupPath)) {
                copy($backupPath, $configPath);
            }
            return ['success' => false, 'message' => '写入配置失败：' . $e->getMessage()];
        }
    }

    /**
     * 生成配置文件内容
     * @param array $config
     * @return string
     */
    private static function generateConfigContent(array $config): string
    {
        $header = "<?php\n\n";
        $header .= "// +----------------------------------------------------------------------\n";
        $header .= "// | 缓存设置\n";
        $header .= "// +----------------------------------------------------------------------\n";
        $header .= "// | 缓存配置文件 - 支持动态配置管理\n";
        $header .= "// | 支持的缓存类型：file、redis、memcache、memcached、sqlite、wincache\n";
        $header .= "// +----------------------------------------------------------------------\n";
        $header .= "// | 最后更新时间：" . date('Y-m-d H:i:s') . "\n";
        $header .= "// +----------------------------------------------------------------------\n\n";
        
        // 使用自定义格式化而不是var_export
        $content = $header . "return [\n";
        
        // 格式化default配置
        $content .= "    // 默认缓存驱动\n";
        $content .= "    'default' => '" . $config['default'] . "',\n\n";
        
        // 格式化stores配置
        $content .= "    // 缓存连接方式配置\n";
        $content .= "    'stores'  => [\n";
        
        foreach ($config['stores'] as $storeName => $storeConfig) {
            $content .= "        // " . self::getCacheTypeDisplayName($storeName) . "缓存\n";
            $content .= "        '" . $storeName . "' => [\n";
            
            foreach ($storeConfig as $key => $value) {
                $content .= "            '" . $key . "'" . str_repeat(' ', max(1, 12 - strlen($key))) . "=> ";
                
                if (is_string($value)) {
                    $content .= "'" . addslashes($value) . "'";
                } elseif (is_bool($value)) {
                    $content .= $value ? 'true' : 'false';
                } elseif (is_array($value)) {
                    $content .= $value ? '[' . implode(', ', array_map(function($v) { return "'" . addslashes($v) . "'"; }, $value)) . ']' : '[]';
                } else {
                    $content .= $value;
                }
                
                $content .= ",\n";
            }
            
            $content .= "        ],\n";
            $storeNames = array_keys($config['stores']);
            if ($storeName !== end($storeNames)) {
                $content .= "\n";
            }
        }
        
        $content .= "    ],\n";
        $content .= "];\n";
        
        return $content;
    }
    
    /**
     * 获取缓存类型显示名称
     * @param string $type
     * @return string
     */
    private static function getCacheTypeDisplayName(string $type): string
    {
        $names = [
            'file' => '文件',
            'redis' => 'Redis',
            'memcache' => 'Memcache',
            'memcached' => 'Memcached',
            'wincache' => 'WinCache（Windows系统专用）'
        ];
        
        return $names[$type] ?? ucfirst($type);
    }

    /**
     * 清理备份文件
     * @param string $dir
     * @param string $prefix
     * @param int $keep
     */
    private static function cleanupBackupFiles(string $dir, string $prefix, int $keep = 5): void
    {
        $files = glob($dir . '/' . $prefix . '*');
        if (count($files) > $keep) {
            // 按时间排序，保留最新的
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // 删除多余的备份文件
            for ($i = $keep; $i < count($files); $i++) {
                @unlink($files[$i]);
            }
        }
    }

    /**
     * 清除缓存实例
     */
    private static function clearCacheInstances(): void
    {
        try {
            // 清除ThinkPHP缓存实例
            if (class_exists('think\facade\Cache')) {
                \think\facade\Cache::clear();
            }
            
            // 清除opcache
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
        } catch (Exception $e) {
            // 忽略清除错误
        }
    }

    /**
     * 验证配置参数
     * @param string $cacheType
     * @param array $params
     * @return array
     */
    public static function validateConfig(string $cacheType, array $params): array
    {
        $typeInfo = ThinkCacheDetectionService::getCacheTypeInfo($cacheType);
        if (!$typeInfo) {
            return ['success' => false, 'message' => '不支持的缓存类型', 'errors' => []];
        }

        // 检查config_fields键是否存在
        if (!isset($typeInfo['config_fields']) || !is_array($typeInfo['config_fields'])) {
            return ['success' => false, 'message' => '缓存类型配置信息不完整', 'errors' => []];
        }

        $errors = [];
        $configFields = $typeInfo['config_fields'];

        foreach ($configFields as $field => $fieldConfig) {
            // 确保fieldConfig是数组
            if (!is_array($fieldConfig)) {
                continue;
            }
            
            $value = $params[$field] ?? null;
            
            // 检查必填字段
            if (($fieldConfig['required'] ?? false) && empty($value)) {
                $errors[$field] = ($fieldConfig['label'] ?? $field) . '不能为空';
                continue;
            }

            // 类型验证
            if (!empty($value)) {
                switch ($fieldConfig['type'] ?? 'text') {
                    case 'number':
                        if (!is_numeric($value)) {
                            $errors[$field] = ($fieldConfig['label'] ?? $field) . '必须是数字';
                        } elseif (isset($fieldConfig['min']) && $value < $fieldConfig['min']) {
                            $errors[$field] = ($fieldConfig['label'] ?? $field) . '不能小于' . $fieldConfig['min'];
                        } elseif (isset($fieldConfig['max']) && $value > $fieldConfig['max']) {
                            $errors[$field] = ($fieldConfig['label'] ?? $field) . '不能大于' . $fieldConfig['max'];
                        }
                        break;
                        
                    case 'text':
                        if (isset($fieldConfig['max_length']) && strlen($value) > $fieldConfig['max_length']) {
                            $errors[$field] = ($fieldConfig['label'] ?? $field) . '长度不能超过' . $fieldConfig['max_length'] . '个字符';
                        }
                        break;
                }
            }
        }

        return [
            'success' => empty($errors),
            'message' => empty($errors) ? '验证通过' : '参数验证失败',
            'errors' => $errors
        ];
    }
}