<?php

declare(strict_types=1);

namespace app\common;

/**
 * 自定义文件缓存类
 * 基于ThinkPHP6，缓存文件将存储于 runtime/cache 目录下
 */
class FileCache
{
    /**
     * 缓存目录路径
     * @var string
     */
    protected $cachePath;

    /**
     * 构造函数，初始化缓存路径
     */
    public function __construct()
    {
        // 设置缓存目录为 runtime/cache
        $this->cachePath = root_path() . 'runtime' . DIRECTORY_SEPARATOR . 'feiniao' . DIRECTORY_SEPARATOR;

        // 确保缓存目录存在且可写
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * 设置缓存
     *
     * @param string $key 缓存键名
     * @param mixed $data 缓存数据
     * @param int $expire 有效期（秒），0为永久缓存
     * @return bool
     */
    public function set(string $key, $data, int $expire = 3600): bool
    {
        // 生成安全的缓存文件名
        $filename = $this->getFilename($key);

        // 准备缓存内容：包含过期时间和数据
        $cacheContent = [
            'expire_time' => ($expire > 0) ? (time() + $expire) : 0,
            'data' => $data
        ];

        // 序列化数据并写入文件[7,8](@ref)
        $serializedData = serialize($cacheContent);

        // 使用文件锁确保写入的原子性[8](@ref)
        if ($handle = @fopen($filename, 'w')) {
            flock($handle, LOCK_EX);
            fwrite($handle, $serializedData);
            flock($handle, LOCK_UN);
            fclose($handle);
            return true;
        }

        return false;
    }

    /**
     * 获取缓存
     *
     * @param string $key 缓存键名
     * @return mixed 缓存数据，失败或过期返回null
     */
    public function get(string $key)
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return null;
        }

        // 读取文件内容[7](@ref)
        $serializedData = file_get_contents($filename);
        if ($serializedData === false) {
            return null;
        }

        $cacheContent = unserialize($serializedData);
        if ($cacheContent === false) {
            return null;
        }

        // 检查缓存是否过期[7](@ref)
        if ($cacheContent['expire_time'] > 0 && $cacheContent['expire_time'] < time()) {
            $this->delete($key); // 过期则删除缓存文件
            return null;
        }

        return $cacheContent['data'];
    }

    /**
     * 删除缓存
     *
     * @param string $key 缓存键名
     * @return bool
     */
    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true; // 文件不存在视为删除成功
    }

    /**
     * 清空所有缓存
     *
     * @return bool
     */
    public function clear(): bool
    {
        $files = glob($this->cachePath . '*.cache');
        $result = true;

        foreach ($files as $file) {
            if (is_file($file)) {
                if (!unlink($file)) {
                    $result = false;
                }
            }
        }

        return $result;
    }

    /**
     * 检查缓存是否存在且未过期
     *
     * @param string $key 缓存键名
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * 生成缓存文件路径
     *
     * @param string $key 缓存键名
     * @return string
     */
    protected function getFilename(string $key): string
    {
        // 对键名进行MD5处理以避免文件名无效字符问题[7](@ref)
        $safeKey = md5($key);
        return $this->cachePath . $safeKey . '.cache';
    }
}
