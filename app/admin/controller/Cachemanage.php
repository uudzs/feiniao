<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\service\ThinkCacheDetectionService;
use app\service\CacheConfigService;
use think\facade\Config;
use think\facade\View;
use think\Response;

/**
 * ThinkPHP缓存管理控制器
 * 提供ThinkPHP默认缓存类型的配置管理和状态监控功能
 */
class Cachemanage extends BaseController
{
    /**
     * 缓存管理首页
     */
    public function index()
    {
        // 检测所有缓存类型
        $cacheTypes = ThinkCacheDetectionService::detectAllCacheTypes();

        // 获取当前缓存配置
        $currentConfig = CacheConfigService::getCurrentConfig();

        // 获取推荐的缓存类型
        $recommendedType = ThinkCacheDetectionService::getRecommendedCacheType();

        View::assign([
            'cache_types' => $cacheTypes,
            'current_config' => $currentConfig,
            'recommended_type' => $recommendedType,
            'page_title' => '缓存管理'
        ]);

        return View::fetch();
    }

    /**
     * 检测缓存类型
     */
    public function detect()
    {
        $param = get_params();
        $type = $param['type'] ?? 'all';
        try {
            if ($type === 'all') {
                $result = ThinkCacheDetectionService::detectAllCacheTypes();
            } else {
                $result = ThinkCacheDetectionService::detectCacheType($type);
            }
            return json(['code' => 0, 'msg' => '检测完成', 'data' => $result]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 测试缓存配置
     */
    public function testconfig()
    {
        $param = get_params();

        // 验证参数
        $validationResult = CacheConfigService::validateConfig($param['cache_type'] ?? '', $param);

        if (!$validationResult['success']) {
            return to_assign(1, $validationResult['message'], $validationResult['errors']);
        }

        try {
            $cacheType = $param['cache_type'];
            $config = $this->buildCacheConfig($cacheType, $param);

            $testResult = ThinkCacheDetectionService::testCacheConfig($cacheType, $config);

            if ($testResult['success']) {
                return json(['code' => 0, 'msg' => $testResult['message']]);
            } else {
                return json(['code' => 1, 'msg' => $testResult['message']]);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 保存缓存配置
     */
    public function saveconfig()
    {
        $param = get_params();
        // 验证参数
        $validationResult = CacheConfigService::validateConfig($param['cache_type'] ?? '', $param);
        if (!$validationResult['success']) {
            return to_assign(1, $validationResult['message'], $validationResult['errors']);
        }
        try {
            $cacheType = $param['cache_type'];
            unset($param['s']);
            // 先测试配置是否可用
            $config = $this->buildCacheConfig($cacheType, $param);
            $testResult = ThinkCacheDetectionService::testCacheConfig($cacheType, $config);
            if (!$testResult['success']) {
                return json(['code' => 1, 'msg' => '配置测试失败：' . $testResult['message']]);
            }
            // 保存配置
            $saveResult = CacheConfigService::updateConfig($cacheType, $param);
            if ($saveResult['success']) {
                return json(['code' => 0, 'msg' => '缓存配置保存成功']);
            } else {
                return json(['code' => 1, 'msg' => $saveResult['message']]);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '保存失败：' . $e->getMessage()]);
        }
    }

    /**
     * 切换缓存类型
     */
    public function switchcache()
    {
        $param = get_params();
        $cacheType = $param['cache_type'] ?? '';

        if (empty($cacheType)) {
            return to_assign(1, '请指定缓存类型');
        }

        try {
            // 检查缓存类型是否支持
            $detection = ThinkCacheDetectionService::detectCacheType($cacheType);
            if (!$detection['available']) {
                return json(['code' => 1, 'msg' => '缓存类型不可用：' . $detection['error']]);
            }

            // 切换缓存类型
            $result = CacheConfigService::switchDefaultCache($cacheType);

            if ($result['success']) {
                return json(['code' => 0, 'msg' => '缓存类型切换成功，新配置已生效']);
            } else {
                return to_assign(1, $result['message']);
            }
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '切换失败：' . $e->getMessage()]);
        }
    }

     /**
     * 获取缓存类型的配置字段
     */
    public function getconfigfields()
    {
        $param = get_params();
        $cacheType = $param['cache_type'] ?? '';
        if (empty($cacheType)) {
            return to_assign(1, '请指定缓存类型');
        }
        try {
            $typeInfo = ThinkCacheDetectionService::getCacheTypeInfo($cacheType);
            if (!$typeInfo) {
                return json(['code' => 1, 'msg' => '不支持的缓存类型']);
            }
            // 获取当前配置值
            $currentConfig = Config::get("cache.stores.{$cacheType}", []);
            // 合并默认值和当前值
            $configFields = $typeInfo['config_fields'];
            foreach ($configFields as $field => &$fieldConfig) {
                $fieldConfig['current_value'] = $currentConfig[$field] ?? ($fieldConfig['default'] ?? '');
            }
            return json(['code' => 0, 'data' => [
                'config_fields' => $configFields,
                'current_config' => $currentConfig
            ]]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 构建缓存配置数组
     * @param string $cacheType
     * @param array $params
     * @return array
     */
    private function buildCacheConfig(string $cacheType, array $params): array
    {
        $config = [];

        switch ($cacheType) {
            case 'file':
                $config = [
                    'type' => 'File',
                    'path' => $params['path'] ?? '',
                    'prefix' => $params['prefix'] ?? '',
                    'expire' => intval($params['expire'] ?? 0),
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
                    'persistent' => !empty($params['persistent']),
                    'prefix' => $params['prefix'] ?? '',
                    'expire' => intval($params['expire'] ?? 0),
                ];
                break;

            case 'memcache':
                $config = [
                    'type' => 'memcache',
                    'host' => $params['host'] ?? '127.0.0.1',
                    'port' => intval($params['port'] ?? 11211),
                    'timeout' => intval($params['timeout'] ?? 1),
                    'prefix' => $params['prefix'] ?? '',
                    'expire' => intval($params['expire'] ?? 0),
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
                    'prefix' => $params['prefix'] ?? '',
                    'expire' => intval($params['expire'] ?? 0),
                ];
                break;

            case 'wincache':
                $config = [
                    'type' => 'wincache',
                    'prefix' => $params['prefix'] ?? '',
                    'expire' => intval($params['expire'] ?? 0),
                ];
                break;
        }

        return $config;
    }

    /**
     * 屏蔽敏感数据
     * @param array $data
     * @return array
     */
    private function maskSensitiveData(array $data): array
    {
        $sensitiveFields = ['password', 'redis_password', 'memcache_password', 'memcached_password'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = str_repeat('*', strlen($data[$field]));
            }
        }

        return $data;
    }
}