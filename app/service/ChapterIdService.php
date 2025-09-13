<?php

declare(strict_types=1);

namespace app\service;

/**
 * 章节ID加密/解密服务
 * 用于隐藏真实的章节ID，提高安全性
 */
class ChapterIdService
{
    /**
     * 加密密钥 - 可在配置文件中设置
     */
    private static $secretKey = 'feiniao_chapter_key';

    /**
     * 字符映射表 - 用于混淆
     */
    private static $charMap = [
        '0' => 'q',
        '1' => 'w',
        '2' => 'e',
        '3' => 'r',
        '4' => 't',
        '5' => 'y',
        '6' => 'u',
        '7' => 'i',
        '8' => 'o',
        '9' => 'p'
    ];

    /**
     * 反向字符映射表
     */
    private static $reverseCharMap = [
        'q' => '0',
        'w' => '1',
        'e' => '2',
        'r' => '3',
        't' => '4',
        'y' => '5',
        'u' => '6',
        'i' => '7',
        'o' => '8',
        'p' => '9'
    ];

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

        // 第一步：使用固定的盐值（基于章节ID本身生成，确保结果一致）
        $salt = ($chapterId * 7 + 13) % 100; // 使用数学运算生成固定盐值
        $salted = $chapterId + $salt * 10000000; // 使用更大的乘数避免冲突

        // 第二步：简单的位运算加密
        $key = crc32(self::getSecretKey()) & 0xFFFF; // 取16位
        $encrypted = $salted ^ $key;

        // 第三步：转为字符串并进行字符映射
        $str = (string)$encrypted;
        $mapped = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            $mapped .= isset(self::$charMap[$char]) ? self::$charMap[$char] : $char;
        }

        // 第四步：添加校验位（取原ID的最后一位）
        $checksum = $chapterId % 10;
        $mapped .= chr(97 + $checksum); // a-j 对应 0-9

        // 第五步：Base64编码并移除填充符
        $encoded = base64_encode($mapped);
        return rtrim($encoded, '=');
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
            // 第一步：Base64解码
            $padding = 4 - (strlen($encryptedId) % 4);
            if ($padding !== 4) {
                $encryptedId .= str_repeat('=', $padding);
            }
            $decoded = base64_decode($encryptedId);
            if ($decoded === false) {
                return 0;
            }

            // 第二步：提取校验位
            if (strlen($decoded) < 2) {
                return 0;
            }
            $checksumChar = substr($decoded, -1);
            $expectedChecksum = ord($checksumChar) - 97;
            $mapped = substr($decoded, 0, -1);

            // 第三步：反向字符映射
            $str = '';
            for ($i = 0; $i < strlen($mapped); $i++) {
                $char = $mapped[$i];
                $str .= isset(self::$reverseCharMap[$char]) ? self::$reverseCharMap[$char] : $char;
            }

            // 第四步：解密位运算
            $encrypted = (int)$str;
            $key = crc32(self::getSecretKey()) & 0xFFFF;
            $salted = $encrypted ^ $key;

            // 第五步：移除固定的盐值（需要先计算原始章节ID）
            $originalChapterId = $salted % 10000000;

            // 验证盐值是否正确
            $expectedSalt = ($originalChapterId * 7 + 13) % 100;
            $actualSalt = intval(($salted - $originalChapterId) / 10000000);

            if ($actualSalt !== $expectedSalt) {
                return 0; // 盐值不匹配，可能是无效的加密ID
            }

            // 第六步：验证校验位
            if ($originalChapterId % 10 !== $expectedChecksum) {
                return 0;
            }

            return $originalChapterId > 0 ? $originalChapterId : 0;
        } catch (\Exception $e) {
            // 解密失败返回0
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
