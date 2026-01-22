<?php

namespace content;

use think\facade\Db;
use think\Exception;
use RuntimeException;

class Content
{
    protected static $config;
    private static $initialized = false;
    protected static $compressors = [
        'gzip'   => ['compress' => 'gzcompress', 'uncompress' => 'gzuncompress'],
        'zlib'   => ['compress' => 'gzdeflate', 'uncompress' => 'gzinflate'],
        'brotli' => ['compress' => 'brotli_compress', 'uncompress' => 'brotli_uncompress'],
    ];

    private static function initialize()
    {
        if (!self::$initialized) {
            // 执行初始化逻辑（例如加载配置、连接数据库等）
            $config = [
                //章节保存类型
                'chapter_save_type' => 1, // 1数据库，2txt
                //是否开启压缩
                'chapter_compress_open' => 0, // 1开启，0关闭
                // 基础配置
                'base_path'       => app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'chapters', // 存储根目录
                'dir_depth'       => 3,               // 目录分片层级
                'file_extension'  => 'txt',           // 文件扩展名
                // 压缩配置
                'compress' => [
                    'enabled'     => true,            // 是否启用压缩
                    'algorithm'   => 'gzip',          // 算法 (gzip/zlib/brotli)
                    'threshold'   => 1024,            // 压缩阈值（字节）
                    'level'       =>  6,              // 压缩级别 1-9
                ],
                // 更新检测
                'update_check' => [
                    'enabled'     => true,            // 启用更新检测
                    'hash_algo'   => 'xxh128',        // 哈希算法 (md5/sha1/xxh128)
                    'quick_check' => 'size',          // 快速检测方式 (size/mtime)
                ],
            ];
            $validAlgos = hash_algos();
            if (!in_array($config['update_check']['hash_algo'], $validAlgos)) {
                $config['update_check']['hash_algo'] = 'sha256'; // 默认回退
            }
            $conf = get_system_config('content');
            if (isset($conf['chapter_save_type'])) {
                $config['chapter_save_type'] = intval($conf['chapter_save_type']);
            }
            if (isset($conf['chapter_compress_open'])) {
                $config['chapter_compress_open'] = intval($conf['chapter_compress_open']);
                if ($config['chapter_compress_open']) {
                    if (isset($conf['compress_algorithm']) && $conf['compress_algorithm']) {
                        $config['compress']['algorithm'] = $conf['compress_algorithm'];
                    }
                    if (isset($conf['compress_level']) && $conf['compress_level']) {
                        $config['compress']['level'] = $conf['compress_level'];
                    }
                } else {
                    $config['compress']['enabled'] = false;
                }
            } else {
                $config['compress']['enabled'] = false;
            }
            self::$config = $config;
            self::$initialized = true;
        }
    }

    public static function add($bookId, $chapterId, $content)
    {
        self::initialize();
        if (self::$config['chapter_save_type'] == 1) {
            $chaptertable = calc_hash_db($bookId);    
            $data = Db::name($chaptertable)->where(['sid' => $chapterId])->find();
            if (!empty($data)) {
                return self::update($bookId, $chapterId, $content);
            }
            $compressed = self::compress($content);
            $content = $compressed ?: $content;
            return Db::name($chaptertable)->strict(false)->field(true)->insertGetId(['sid' => $chapterId, 'info' => $content]);
        } else {
            return self::writeSafe($chapterId, $content);
        }
    }

    public static function get($bookId, $chapterId)
    {
        self::initialize();
        $content = '';
        if (self::$config['chapter_save_type'] == 1) {
            $content = self::database($bookId, $chapterId);
            if (empty($content)) {
                $content = self::file($chapterId);
                if (!empty($content)) {
                    $chaptertable = calc_hash_db($bookId);
                    list($wordnum, $content) = countWordsAndContent($content, true);
                    $compressed = self::compress($content);
                    $content = $compressed ?: $content;
                    if (Db::name($chaptertable)->strict(false)->field(true)->insertGetId(['sid' => $chapterId, 'info' => $content])) {
                        $path = self::getPath($chapterId);
                        @unlink($path);
                    }
                }
            }
            return self::uncompress($content);
        } else {
            $content = self::file($chapterId);
            if (empty($content)) {
                $content = self::database($bookId, $chapterId);
                if (!empty($content)) {
                    list($wordnum, $content) = countWordsAndContent($content, true);
                    if (self::write($chapterId, $content)) {
                        $chaptertable = calc_hash_db($bookId);
                        Db::name($chaptertable)->where(['sid' => $chapterId])->delete();
                    }
                }
            }
            return $content;
        }
        return $content;
    }

    public static function update($bookId, $chapterId, $content)
    {
        self::initialize();
        if (self::$config['chapter_save_type'] == 1) {
            $chaptertable = calc_hash_db($bookId);
            $compressed = self::compress($content);
            $content  = $compressed ?: $content;
            return Db::name($chaptertable)->where(['sid' => $chapterId])->strict(false)->field(true)->update(['info' => $content]);
        } else {
            return self::writeSafe($chapterId, $content);
        }
    }

    public static function delete($bookId, $chapterId)
    {
        self::initialize();
        if (self::$config['chapter_save_type'] == 1) {
            $chaptertable = calc_hash_db($bookId);
            Db::name('chapter')->where(['id' => $chapterId])->delete();
            Db::name('chapter_draft')->where(['cid' => $chapterId])->delete(); //草稿箱
            Db::name('chapter_verify')->where(['cid' => $chapterId])->delete(); //审核库
            return Db::name($chaptertable)->where(['sid' => $chapterId])->delete();
        } else {
            $path = self::getPath($chapterId);
            if (!file_exists($path)) {
                $chaptertable = calc_hash_db($bookId);
                Db::name('chapter')->where(['id' => $chapterId])->delete();
                Db::name('chapter_draft')->where(['cid' => $chapterId])->delete(); //草稿箱
                Db::name('chapter_verify')->where(['cid' => $chapterId])->delete(); //审核库
                return Db::name($chaptertable)->where(['sid' => $chapterId])->delete();
            }
            return unlink($path);
        }
    }

    protected static function file($chapterId)
    {
        $content = '';
        try {
            try {
                $content = self::read($chapterId);
            } catch (RuntimeException $e) {
                throw new RuntimeException();
            } catch (Exception $e) {
                throw new Exception();
            }
            return $content;
        } catch (\Exception $e) {
            return $content;
        }
    }

    protected static function database($bookId, $chapterId)
    {
        $content = '';
        $chaptertable = calc_hash_db($bookId);
        $content = Db::name($chaptertable)->where(['sid' => $chapterId])->value('info');  
        return $content;
    }

    public static function read($chapterId)
    {
        $path = self::getPath($chapterId);
        if (!file_exists($path)) {
            throw new Exception();
        }
        try {
            $data = file_get_contents($path);
            return self::uncompress($data);
        } catch (RuntimeException $e) {
            throw new RuntimeException();
        } catch (Exception $e) {
            throw new Exception();
        }
    }

    public static function getPath($chapterId)
    {
        self::initialize();
        try {
            self::validateId($chapterId);
        } catch (\InvalidArgumentException $e) {
            return '';
        }
        $idStr = str_pad($chapterId, 9, '0', STR_PAD_LEFT);
        $segments = str_split($idStr, 3);
        $depth = min(self::$config['dir_depth'], count($segments));
        $path = rtrim(self::$config['base_path'], '/') . '/';
        for ($i = 0; $i < $depth - 1; $i++) {
            $path .= $segments[$i] . '/';
        }
        return $path . end($segments) . '.' . self::$config['file_extension'];
    }

    private static function validateId($id)
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException(lang('paramerror'));
        }
    }

    public static function compress($content)
    {
        self::initialize();
        $conf = self::$config['compress'];
        if (!$conf['enabled'] || strlen($content) < $conf['threshold']) {
            return $content;
        }
        $algorithm = strtolower($conf['algorithm']);
        if (!isset(self::$compressors[$algorithm])) {
            throw new RuntimeException(lang("empty"));
        }
        $compressed = call_user_func(
            self::$compressors[$algorithm]['compress'],
            $content,
            $conf['level']
        );
        return $compressed !== false ? $compressed : $content;
    }

    public static function uncompress($data)
    {
        foreach (self::$compressors as $algo => $func) {
            // 添加扩展检测
            if (!function_exists($func['uncompress'])) {
                continue;
            }
            $decompressed = @call_user_func($func['uncompress'], $data);
            if ($decompressed !== false) {
                return $decompressed;
            }
        }
        return $data;
    }

    public static function needUpdate($chapterId, $newContent)
    {
        self::initialize();
        $conf = self::$config['update_check'];
        if (!$conf['enabled']) return true;
        $path = self::getPath($chapterId);
        if (!file_exists($path)) return true;
        if ($conf['quick_check'] === 'size') {
            try {
                $newSize = strlen(self::compress($newContent));
                if (filesize($path) != $newSize) return true;
            } catch (RuntimeException $e) {
                throw new RuntimeException();
            } catch (Exception $e) {
                throw new Exception();
            }
        } elseif ($conf['quick_check'] === 'mtime') {
            if (filemtime($path) < time() - 5) return true;
        }
        return self::calcHash($newContent) !== self::getFileHash($path);
    }

    public static function writeSafe($chapterId, $content)
    {
        if (!self::needUpdate($chapterId, $content)) {
            return false;
        }
        return self::write($chapterId, $content);
    }

    public static function write($chapterId, $content)
    {
        $path = self::getPath($chapterId);
        $dir  = dirname($path);
        createDirectory($dir);
        $compressed = self::compress($content);
        $result = file_put_contents($path, $compressed, LOCK_EX);
        return $result !== false;
    }

    private static function getFileHash($path)
    {
        try {
            $content = self::uncompress(file_get_contents($path));
            return self::calcHash($content);
        } catch (RuntimeException $e) {
            throw new RuntimeException();
        } catch (Exception $e) {
            throw new Exception();
        }
    }

    private static function calcHash($content)
    {
        self::initialize();
        $algo = self::$config['update_check']['hash_algo'];
        return hash($algo, $content);
    }
}
