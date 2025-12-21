<?php

namespace app\service;

use think\facade\Cache;
use think\facade\Config;

/**
 * 缓存服务类
 * 提供业务级缓存操作接口，专注于键管理和业务逻辑
 * 基础缓存操作请直接使用 Cache 门面
 */
class CacheService
{

    /**
     * 清空指定前缀的缓存
     * @param string $prefix 缓存前缀
     * @param string $store 缓存存储器
     * @return bool
     */
    public static function clearByPrefix(string $prefix, string $store = null): bool
    {
        try {
            if ($store === null) {
                $store = Config::get('cache.default', 'file');
            }
            return Cache::store($store)->clear($prefix . '*');
        } catch (\Exception $e) {
            trace('Cache clearByPrefix failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 缓存或获取数据（缓存穿透保护）
     * @param string $key 缓存键
     * @param callable $callback 数据获取回调
     * @param int $expire 过期时间
     * @param string $store 缓存存储器
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $expire = null, string $store = null)
    {
        try {
            if ($store === null) {
                $store = Config::get('cache.default', 'file');
            }

            // 修复 expire 处理逻辑
            if ($expire === null) {
                $storeConfig = Config::get("cache.stores.{$store}", []);
                $configExpire = $storeConfig['expire'] ?? null;

                if ($configExpire !== null) {
                    $expire = $configExpire;
                } else {
                    $expire = 86400; // 默认 1 小时
                }
            }

            // 尝试从缓存获取
            $data = Cache::store($store)->get($key, '__CACHE_MISS__');

            if ($data !== '__CACHE_MISS__') {
                return $data;
            }

            // 缓存未命中，执行回调
            $data = $callback();

            // 即使返回 null 也要缓存，防止缓存穿透
            Cache::store($store)->set($key, $data, $expire);

            return $data;
        } catch (\Exception $e) {
            try {
                return $callback();
            } catch (\Exception $e2) {
                trace('Cache remember callback also failed: ' . $e2->getMessage(), 'error');
                return null;
            }
        }
    }

    /**
     * 设置列表缓存
     * @param string $key 缓存键
     * @param array $list 列表数据
     * @param int $expire 过期时间
     * @param string $store 缓存存储器
     * @return bool
     */
    public static function setList(string $key, array $list, int $expire = null, string $store = null): bool
    {
        if ($store === null) {
            $store = Config::get('cache.default', 'file');
        }
        // 如果没有传入expire参数，则尝试从配置中获取
        if ($expire === null) {
            // 获取当前存储类型的配置
            $storeConfig = Config::get("cache.stores.{$store}", []);
            $configExpire = $storeConfig['expire'] ?? 0;
            // 如果配置中的expire大于0，则使用配置值
            if ($configExpire > 0) {
                $expire = $configExpire;
            } else {
                // 否则使用默认的类常量
                $expire = 86400;
            }
        }
        return Cache::store($store)->set($key, json_encode($list), $expire);
    }

    /**
     * 获取列表缓存
     * @param string $key 缓存键
     * @param array $default 默认值
     * @param string $store 缓存存储器
     * @return array
     */
    public static function getList(string $key, array $default = [], string $store = null): array
    {
        if ($store === null) {
            $store = Config::get('cache.default', 'file');
        }
        $data = Cache::store($store)->get($key, null);
        if ($data === null) {
            return $default;
        }
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : $default;
    }
}
