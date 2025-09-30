<?php

declare(strict_types=1);

namespace app\service;

/**
 * 章节ID加密/解密服务
 * 使用更稳健的XOR和Base64编码方案
 */
class ChapterIdService
{
    /**
     * 加密密钥 - 可在配置文件中设置
     */
    private static $secretKey = 'feiniao_chapter_key';

    /**
     * 获取加密密钥
     * @return string
     */
    private static function getSecretKey(): string
    {
        // 优先从配置文件读取
        try {
            $configKey = get_system_config('token', 'secrect') ?: null;
            return $configKey ?: self::$secretKey;
        } catch (\Exception $e) {
            return self::$secretKey;
        }
    }

    /**
     * 加密章节ID
     * @param int $chapterId 真实章节ID
     * @return string 加密后的字符串
     */
    public static function encrypt(int $chapterId): string
    {
        if ($chapterId <= 0) {
            return '';
        }

        // 使用更简单的XOR加密
        $key = crc32(self::getSecretKey());
        $encrypted = $chapterId ^ $key;

        // 转换为十六进制字符串
        $hex = dechex($encrypted);

        // 添加简单校验和（ID的最后一位数字）
        $checksum = $chapterId % 10;
        $hex .= dechex($checksum);

        // Base64编码并替换可能产生问题字符
        $encoded = base64_encode($hex);

        // 替换可能产生的问题字符
        $encoded = str_replace(['+', '/', '='], ['-', '_', ''], $encoded);

        return $encoded;
    }

    /**
     * 解密章节ID
     * @param string $encryptedId 加密的章节ID字符串
     * @return int 原始章节ID，解密失败返回0
     */
    public static function decrypt(string $encryptedId): int
    {
        if (empty($encryptedId)) {
            return 0;
        }

        try {
            // 恢复Base64替换的字符
            $encoded = str_replace(['-', '_'], ['+', '/'], $encryptedId);

            // 添加填充字符
            $padding = strlen($encoded) % 4;
            if ($padding !== 0) {
                $encoded .= str_repeat('=', 4 - $padding);
            }

            // Base64解码
            $hex = base64_decode($encoded);
            if ($hex === false) {
                return 0;
            }

            // 分离加密数据和校验和
            $data = substr($hex, 0, -1);
            $checksum = substr($hex, -1);

            // 转换回数字
            $encrypted = hexdec($data);
            $key = crc32(self::getSecretKey());

            // XOR解密
            $chapterId = $encrypted ^ $key;

            // 验证校验和
            if ($chapterId % 10 !== hexdec($checksum)) {
                return 0;
            }

            return $chapterId > 0 ? $chapterId : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 验证加密ID是否有效
     * @param string $encryptedId 加密的ID
     * @return bool
     */
    public static function isValid(string $encryptedId): bool
    {
        return self::decrypt($encryptedId) > 0;
    }

    /**
     * 批量加密章节ID
     * @param array $chapterIds 章节ID数组
     * @return array 加密后的ID数组，保持原有的键值对应关系
     */
    public static function encryptBatch(array $chapterIds): array
    {
        $encrypted = [];
        foreach ($chapterIds as $key => $id) {
            $encrypted[$key] = self::encrypt((int)$id);
        }
        return $encrypted;
    }

    /**
     * 批量解密章节ID
     * @param array $encryptedIds 加密的ID数组
     * @return array 解密后的ID数组
     */
    public static function decryptBatch(array $encryptedIds): array
    {
        $decrypted = [];
        foreach ($encryptedIds as $key => $encryptedId) {
            $decrypted[$key] = self::decrypt((string)$encryptedId);
        }
        return $decrypted;
    }
}
