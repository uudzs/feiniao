<?php

namespace app\service;

use think\facade\Config;
use think\facade\Cache;
use Exception;
use PDO;

/**
 * ThinkPHP缓存类型检测服务
 * 支持ThinkPHP默认的所有缓存类型：file、redis、memcache、memcached、wincache
 */
class ThinkCacheDetectionService
{
    // ThinkPHP支持的缓存类型配置
    const CACHE_TYPES = [
        'file' => [
            'name' => '文件缓存',
            'description' => '基于文件系统的缓存，无需额外配置，适合单机部署',
            'required_extensions' => [],
            'performance' => 'low',
            'recommended' => false,
            'config_fields' => [
                'path' => ['type' => 'text', 'label' => '缓存目录', 'required' => false, 'placeholder' => '默认使用runtime/cache'],
                'prefix' => ['type' => 'text', 'label' => '缓存前缀', 'required' => false],
                'expire' => ['type' => 'number', 'label' => '默认过期时间(秒)', 'required' => false, 'default' => 0],
            ]
        ],
        'redis' => [
            'name' => 'Redis缓存',
            'description' => '高性能内存缓存，支持数据持久化和集群，适合高并发场景',
            'required_extensions' => ['redis'],
            'performance' => 'high',
            'recommended' => true,
            'config_fields' => [
                'host' => ['type' => 'text', 'label' => '服务器地址', 'required' => true, 'default' => '127.0.0.1'],
                'port' => ['type' => 'number', 'label' => '端口', 'required' => true, 'default' => 6379],
                'password' => ['type' => 'password', 'label' => '密码', 'required' => false],
                'select' => ['type' => 'number', 'label' => '数据库编号', 'required' => false, 'default' => 0, 'min' => 0, 'max' => 15],
                'timeout' => ['type' => 'number', 'label' => '连接超时(秒)', 'required' => false, 'default' => 5],
                'persistent' => ['type' => 'checkbox', 'label' => '持久连接', 'required' => false],
                'prefix' => ['type' => 'text', 'label' => '缓存前缀', 'required' => false],
                'expire' => ['type' => 'number', 'label' => '默认过期时间(秒)', 'required' => false, 'default' => 0],
            ]
        ],
        'memcache' => [
            'name' => 'Memcache缓存',
            'description' => '轻量级内存缓存系统，简单高效',
            'required_extensions' => ['memcache'],
            'performance' => 'medium',
            'recommended' => false,
            'config_fields' => [
                'host' => ['type' => 'text', 'label' => '服务器地址', 'required' => true, 'default' => '127.0.0.1'],
                'port' => ['type' => 'number', 'label' => '端口', 'required' => true, 'default' => 11211],
                'timeout' => ['type' => 'number', 'label' => '连接超时(秒)', 'required' => false, 'default' => 1],
                'prefix' => ['type' => 'text', 'label' => '缓存前缀', 'required' => false],
                'expire' => ['type' => 'number', 'label' => '默认过期时间(秒)', 'required' => false, 'default' => 0],
            ]
        ],
        'memcached' => [
            'name' => 'Memcached缓存',
            'description' => '增强Memcache，支持更多特性和更好的性能',
            'required_extensions' => ['memcached'],
            'performance' => 'medium',
            'recommended' => false,
            'config_fields' => [
                'host' => ['type' => 'text', 'label' => '服务器地址', 'required' => true, 'default' => '127.0.0.1'],
                'port' => ['type' => 'number', 'label' => '端口', 'required' => true, 'default' => 11211],
                'username' => ['type' => 'text', 'label' => '用户名', 'required' => false],
                'password' => ['type' => 'password', 'label' => '密码', 'required' => false],
                'timeout' => ['type' => 'number', 'label' => '连接超时(秒)', 'required' => false, 'default' => 1],
                'prefix' => ['type' => 'text', 'label' => '缓存前缀', 'required' => false],
                'expire' => ['type' => 'number', 'label' => '默认过期时间(秒)', 'required' => false, 'default' => 0],
            ]
        ],
        'wincache' => [
            'name' => 'WinCache缓存',
            'description' => 'Windows系统专用的高性能内存缓存',
            'required_extensions' => ['wincache'],
            'performance' => 'high',
            'recommended' => false,
            'platform_specific' => 'windows',
            'config_fields' => [
                'prefix' => ['type' => 'text', 'label' => '缓存前缀', 'required' => false],
                'expire' => ['type' => 'number', 'label' => '默认过期时间(秒)', 'required' => false, 'default' => 0],
            ]
        ],
    ];

    /**
     * 检测所有支持的缓存类型
     * @return array
     */
    public static function detectAllCacheTypes(): array
    {
        $results = [];
        
        foreach (self::CACHE_TYPES as $type => $config) {
            $results[$type] = self::detectCacheType($type);
        }
        
        return $results;
    }

    /**
     * 检测指定缓存类型是否可用
     * @param string $type 缓存类型
     * @return array
     */
    public static function detectCacheType(string $type): array
    {
        if (!isset(self::CACHE_TYPES[$type])) {
            return [
                'type' => $type,
                'available' => false,
                'error' => '不支持的缓存类型'
            ];
        }

        $config = self::CACHE_TYPES[$type];
        $result = [
            'type' => $type,
            'name' => $config['name'],
            'description' => $config['description'],
            'available' => false,
            'version' => '',
            'extensions_check' => [],
            'connection_test' => false,
            'error' => '',
            'performance' => $config['performance'],
            'recommended' => $config['recommended'],
            'config_fields' => $config['config_fields'],
            'platform_specific' => $config['platform_specific'] ?? null
        ];

        try {
            // 平台检查
            if (isset($config['platform_specific'])) {
                if ($config['platform_specific'] === 'windows' && PHP_OS_FAMILY !== 'Windows') {
                    $result['error'] = '仅支持Windows系统';
                    return $result;
                }
            }

            // 检查PHP扩展
            $extensionCheck = self::checkExtensions($config['required_extensions']);
            $result['extensions_check'] = $extensionCheck;
            
            if (!$extensionCheck['all_available']) {
                $result['error'] = '缺少必需的PHP扩展：' . implode(', ', $extensionCheck['missing']);
                return $result;
            }

            // 针对不同类型进行特定检测
            switch ($type) {
                case 'file':
                    $result = self::detectFileCache($result);
                    break;
                case 'redis':
                    $result = self::detectRedisCache($result);
                    break;
                case 'memcache':
                    $result = self::detectMemcacheCache($result);
                    break;
                case 'memcached':
                    $result = self::detectMemcachedCache($result);
                    break;
                case 'wincache':
                    $result = self::detectWincacheCache($result);
                    break;
            }

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * 检查PHP扩展
     * @param array $extensions
     * @return array
     */
    private static function checkExtensions(array $extensions): array
    {
        $result = [
            'required' => $extensions,
            'available' => [],
            'missing' => [],
            'all_available' => true
        ];

        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $result['available'][] = $ext;
            } else {
                $result['missing'][] = $ext;
                $result['all_available'] = false;
            }
        }

        return $result;
    }

    /**
     * 检测文件缓存
     * @param array $result
     * @return array
     */
    private static function detectFileCache(array $result): array
    {
        try {
            $cacheDir = runtime_path() . 'cache';
            
            if (!is_dir($cacheDir)) {
                if (!mkdir($cacheDir, 0755, true)) {
                    $result['error'] = '无法创建缓存目录';
                    return $result;
                }
            }

            if (!is_writable($cacheDir)) {
                $result['error'] = '缓存目录不可写：' . $cacheDir;
                return $result;
            }

            // 测试写入
            $testFile = $cacheDir . '/test_' . uniqid() . '.cache';
            if (file_put_contents($testFile, 'test') === false) {
                $result['error'] = '无法写入缓存文件';
                return $result;
            }

            // 清理测试文件
            @unlink($testFile);

            $result['available'] = true;
            $result['connection_test'] = true;
            $result['version'] = 'File System';

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * 检测Redis缓存
     * @param array $result
     * @return array
     */
    private static function detectRedisCache(array $result): array
    {
        try {
            $redis = new \Redis();
            
            // 尝试连接
            if (!$redis->connect('127.0.0.1', 6379, 5)) {
                $result['error'] = 'Redis连接失败';
                return $result;
            }

            // 获取版本信息
            $info = $redis->info();
            $result['version'] = $info['redis_version'] ?? 'Unknown';
            $result['server_version'] = $info['redis_version'] ?? 'Unknown';

            // 测试基本操作
            $testKey = 'test_' . uniqid();
            $redis->set($testKey, 'test_value', 10);
            $value = $redis->get($testKey);
            $redis->del($testKey);

            if ($value !== 'test_value') {
                $result['error'] = 'Redis基本操作测试失败';
                return $result;
            }

            $result['available'] = true;
            $result['connection_test'] = true;
            $redis->close();

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * 检测Memcache缓存
     * @param array $result
     * @return array
     */
    private static function detectMemcacheCache(array $result): array
    {
        try {
            $memcache = new \Memcache();
            
            if (!$memcache->connect('127.0.0.1', 11211)) {
                $result['error'] = 'Memcache连接失败';
                return $result;
            }

            // 获取版本信息
            $version = $memcache->getVersion();
            $result['version'] = $version ?: 'Unknown';

            // 测试基本操作
            $testKey = 'test_' . uniqid();
            $memcache->set($testKey, 'test_value', 0, 10);
            $value = $memcache->get($testKey);
            $memcache->delete($testKey);

            if ($value !== 'test_value') {
                $result['error'] = 'Memcache基本操作测试失败';
                return $result;
            }

            $result['available'] = true;
            $result['connection_test'] = true;
            $memcache->close();

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * 检测Memcached缓存
     * @param array $result
     * @return array
     */
    private static function detectMemcachedCache(array $result): array
    {
        try {
            $memcached = new \Memcached();
            $memcached->addServer('127.0.0.1', 11211);

            // 获取版本信息
            $version = $memcached->getVersion();
            $result['version'] = is_array($version) ? reset($version) : 'Unknown';

            // 测试基本操作
            $testKey = 'test_' . uniqid();
            $memcached->set($testKey, 'test_value', 10);
            $value = $memcached->get($testKey);
            $memcached->delete($testKey);

            if ($value !== 'test_value') {
                $result['error'] = 'Memcached基本操作测试失败';
                return $result;
            }

            $result['available'] = true;
            $result['connection_test'] = true;

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * 检测WinCache缓存
     * @param array $result
     * @return array
     */
    private static function detectWincacheCache(array $result): array
    {
        try {
            if (!function_exists('wincache_ucache_get')) {
                $result['error'] = 'WinCache用户缓存功能不可用';
                return $result;
            }

            // 获取版本信息
            if (function_exists('wincache_get_version')) {
                $result['version'] = wincache_get_version();
            }

            // 测试基本操作
            $testKey = 'test_' . uniqid();
            wincache_ucache_set($testKey, 'test_value', 10);
            $value = wincache_ucache_get($testKey);
            wincache_ucache_delete($testKey);

            if ($value !== 'test_value') {
                $result['error'] = 'WinCache基本操作测试失败';
                return $result;
            }

            $result['available'] = true;
            $result['connection_test'] = true;

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * 测试缓存配置
     * @param string $cacheType
     * @param array $config
     * @return array
     */
    public static function testCacheConfig(string $cacheType, array $config): array
    {
        try {
            // 创建临时缓存实例进行测试
            $testKey = 'test_config_' . uniqid();
            $testValue = 'test_value_' . time();

            switch ($cacheType) {
                case 'file':
                    return self::testFileConfig($config, $testKey, $testValue);
                case 'redis':
                    return self::testRedisConfig($config, $testKey, $testValue);
                case 'memcache':
                    return self::testMemcacheConfig($config, $testKey, $testValue);
                case 'memcached':
                    return self::testMemcachedConfig($config, $testKey, $testValue);
                case 'wincache':
                    return self::testWincacheConfig($config, $testKey, $testValue);
                default:
                    return ['success' => false, 'message' => '不支持的缓存类型'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 测试文件缓存配置
     */
    private static function testFileConfig(array $config, string $testKey, string $testValue): array
    {
        $path = $config['path'] ?: runtime_path() . 'cache';
        
        if (!is_dir($path) && !mkdir($path, 0755, true)) {
            return ['success' => false, 'message' => '无法创建缓存目录'];
        }
        
        if (!is_writable($path)) {
            return ['success' => false, 'message' => '缓存目录不可写'];
        }

        return ['success' => true, 'message' => '文件缓存配置测试通过'];
    }

    /**
     * 测试Redis缓存配置
     */
    private static function testRedisConfig(array $config, string $testKey, string $testValue): array
    {
        $redis = new \Redis();
        
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $timeout = $config['timeout'] ?? 5;
        
        if (!$redis->connect($host, $port, $timeout)) {
            return ['success' => false, 'message' => 'Redis连接失败'];
        }

        if (!empty($config['password'])) {
            if (!$redis->auth($config['password'])) {
                return ['success' => false, 'message' => 'Redis认证失败'];
            }
        }

        if (isset($config['select'])) {
            $redis->select($config['select']);
        }

        $redis->set($testKey, $testValue, 10);
        $result = $redis->get($testKey);
        $redis->del($testKey);
        $redis->close();

        if ($result !== $testValue) {
            return ['success' => false, 'message' => 'Redis读写测试失败'];
        }

        return ['success' => true, 'message' => 'Redis配置测试通过'];
    }

    /**
     * 测试Memcache缓存配置
     */
    private static function testMemcacheConfig(array $config, string $testKey, string $testValue): array
    {
        $memcache = new \Memcache();
        
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 11211;
        
        if (!$memcache->connect($host, $port)) {
            return ['success' => false, 'message' => 'Memcache连接失败'];
        }

        $memcache->set($testKey, $testValue, 0, 10);
        $result = $memcache->get($testKey);
        $memcache->delete($testKey);
        $memcache->close();

        if ($result !== $testValue) {
            return ['success' => false, 'message' => 'Memcache读写测试失败'];
        }

        return ['success' => true, 'message' => 'Memcache配置测试通过'];
    }

    /**
     * 测试Memcached缓存配置
     */
    private static function testMemcachedConfig(array $config, string $testKey, string $testValue): array
    {
        $memcached = new \Memcached();
        
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 11211;
        
        $memcached->addServer($host, $port);

        if (!empty($config['username']) && !empty($config['password'])) {
            $memcached->setSaslAuthData($config['username'], $config['password']);
        }

        $memcached->set($testKey, $testValue, 10);
        $result = $memcached->get($testKey);
        $memcached->delete($testKey);

        if ($result !== $testValue) {
            return ['success' => false, 'message' => 'Memcached读写测试失败'];
        }

        return ['success' => true, 'message' => 'Memcached配置测试通过'];
    }

    /**
     * 测试WinCache缓存配置
     */
    private static function testWincacheConfig(array $config, string $testKey, string $testValue): array
    {
        wincache_ucache_set($testKey, $testValue, 10);
        $result = wincache_ucache_get($testKey);
        wincache_ucache_delete($testKey);

        if ($result !== $testValue) {
            return ['success' => false, 'message' => 'WinCache读写测试失败'];
        }

        return ['success' => true, 'message' => 'WinCache配置测试通过'];
    }

    /**
     * 获取推荐的缓存类型
     * @return string
     */
    public static function getRecommendedCacheType(): string
    {
        $types = self::detectAllCacheTypes();
        
        // 优先推荐Redis
        if ($types['redis']['available']) {
            return 'redis';
        }
        
        // 其次推荐WinCache（如果是Windows系统）
        if (PHP_OS_FAMILY === 'Windows' && $types['wincache']['available']) {
            return 'wincache';
        }
        
        // 再次推荐Memcached
        if ($types['memcached']['available']) {
            return 'memcached';
        }
        
        // 然后推荐Memcache
        if ($types['memcache']['available']) {
            return 'memcache';
        }
        
        // 最后使用文件缓存
        return 'file';
    }

    /**
     * 获取缓存类型信息
     * @param string $type
     * @return array|null
     */
    public static function getCacheTypeInfo(string $type): ?array
    {
        return self::CACHE_TYPES[$type] ?? null;
    }

    /**
     * 获取所有缓存类型的基本信息
     * @return array
     */
    public static function getAllCacheTypesInfo(): array
    {
        return self::CACHE_TYPES;
    }
}