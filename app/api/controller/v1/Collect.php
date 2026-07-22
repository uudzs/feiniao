<?php

declare(strict_types=1);

namespace app\api\controller\v1;

use think\Request;
use think\facade\Db;
use content\Content;

/**
 * 小说采集API入库控制器
 * 
 * @author <NAME> <<EMAIL>>
 */
class Collect
{
    /**
     * 验证API密钥（用于POST提交）
     */
    protected function verifyApiKey(): void
    {
        $request = request();
        $key = (string)($request->header('X-API-Key') ?: $request->param('api_key', ''));
        $apiKey = (string)config('reverse_collect.collector_api_key', '');

        if ($apiKey === '') {
            $this->error('采集入库API密钥未配置', 503)->send();
            exit;
        }

        if ($key === '' || !hash_equals($apiKey, $key)) {
            $this->error('API密钥无效或未提供', 401)->send();
            exit;
        }
    }

    /**
     * 单个作品入库（含章节）
     * 
     * @param Request $request 请求对象
     */
    public function submit(Request $request)
    {
        $this->verifyApiKey();

        try {
            // 用 file_get_contents 直接读取，ThinkPHP 不会消费 text/plain 类型的 php://input
            $rawInput = file_get_contents('php://input');

            // 检查是否为 ZIP 压缩格式
            $dataFormat = $request->header('X-Data-Format', 'json');

            if ($dataFormat === 'zip') {
                // ZIP 压缩格式 (使用 ZipArchive 类，兼容 PHP 8.0+)
                $tempFile = tempnam(sys_get_temp_dir(), 'collect_');
                $zip = new \ZipArchive();
                $result = $zip->open($tempFile, \ZipArchive::RDONLY);
                if ($result !== true) {
                    unlink($tempFile);
                    return $this->error('ZIP 文件无效', 400);
                }

                $data = null;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if ($name === 'data.json') {
                        $content = $zip->getFromIndex($i);
                        $data = json_decode($content, true);
                        break;
                    }
                }
                $zip->close();
                unlink($tempFile);

                if ($data === null) {
                    return $this->error('ZIP 中未找到 data.json 文件', 400);
                }
            } else {
                // 普通 JSON 格式
                $data = json_decode($rawInput, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->error("JSON 解析错误: " . json_last_error_msg(), 500);
                }
            }
            //file_put_contents('./data_' . time() . '.json', json_encode($data, JSON_PRETTY_PRINT));
            if (empty($data)) {
                return $this->error('请求数据为空', 400);
            }

            // 处理字段映射 - 同时支持嵌套格式 {"work":{...}} 和扁平格式 {...}
            $workData = $data['work'] ?? ($data['title'] ?? null ? $data : []);
            $mappedData = $this->mapWorkFields($workData);

            // 必填验证
            if (empty($mappedData['title']) || empty($mappedData['author'])) {
                return $this->error('缺少title或author参数', 400);
            }

            // 检查作品是否已存在
            $existingBook = $this->checkBookIfExist(['title' => $mappedData['title'], 'author' => $mappedData['author']]);

            Db::startTrans();

            try {
                // 处理作者
                $mappedData['authorid'] = $this->handleAuthor($mappedData['author']);

                $filename = $this->generateFilename($mappedData['title']);
                if (!empty($filename)) {
                    $mappedData['filename'] = $filename;
                }

                // 处理分类
                $mappedData['genre'] = $mappedData['genre'] ?? '';
                $mappedData['subgenre'] = $mappedData['subgenre'] ?? '';

                if (!empty($mappedData['genre']) || !empty($mappedData['subgenre'])) {
                    $this->processGenre($mappedData);
                }

                $mappedData['status'] = 1;
                $mappedData['isfinish'] = $mappedData['isfinish'] ?? 1;
                $mappedData['update_time'] = $this->parseUpdateTime($mappedData['update_time'] ?? null);
                $is_download_cover = $mappedData['download_cover'] ?? false;
                unset($mappedData['download_cover']);

                // 处理封面图片
                $cover_base64 = $mappedData['cover_base64'] ?? null;
                unset($mappedData['cover_base64']);

                // 如果有 base64 封面图片，保存到本地
                if (!empty($cover_base64)) {
                    $localCover = $this->saveBase64Cover($cover_base64);
                    if ($localCover) {
                        $mappedData['cover'] = $localCover;
                        $mappedData['localcover'] = 1;
                    } else {
                        // base64 保存失败，保留原始 URL
                        $mappedData['cover'] = $mappedData['cover'] ?? '';
                    }
                } else {
                    $mappedData['cover'] = $mappedData['cover'] ?? '';
                }

                if (!empty($mappedData['remark'])) {
                    list($wordnum, $remark) = countWordsAndContent($mappedData['remark'], true);
                    $mappedData['remark'] = $remark ?? '';
                }

                if ($existingBook) {
                    // 更新作品
                    Db::name('book')->where('id', $existingBook['id'])->update($mappedData);
                    $bookId = $existingBook['id'];
                    $action = 'updated';
                } else {
                    // 新增作品
                    $mappedData['create_time'] = $mappedData['update_time'] ?? time();
                    $bookId = Db::name('book')->insertGetId($mappedData);
                    $action = 'created';
                }

                // 获取章节数据
                $chapters = $data['chapters'] ?? [];
                $bookUpdateinfo = [];

                // 处理章节
                if (!empty($chapters) && is_array($chapters)) {
                    $results = $this->saveChapters($bookId, $mappedData['authorid'], $chapters);
                    $wordnumsum = array_sum(array_column($results, 'wordnum')) ?? 0;
                    if ($wordnumsum > 0) {
                        $bookUpdateinfo['words'] = $wordnumsum;                        
                    }
                    $bookUpdateinfo['chapters'] = count($results);
                }

                $sourceUrl = (string)($workData['source_url'] ?? $data['source_url'] ?? '');
                $collectorTaskId = (int)($data['task_id'] ?? $workData['task_id'] ?? 0);
                if ($sourceUrl !== '' && $collectorTaskId > 0) {
                    $this->syncSourceMappings($bookId, $collectorTaskId, $sourceUrl, $results ?? []);
                }

                // 处理封面图片（仅当没有 base64 数据时才通过 URL 下载）
                $has_base64_cover = !empty($data['work']['cover_base64'] ?? null);
                if (!$has_base64_cover && !empty($mappedData['cover']) && $is_download_cover) {
                    $localCover = $this->handleCoverImage($mappedData['cover'], $bookId);
                    if ($localCover) {
                        $bookUpdateinfo['cover'] = $localCover;
                        $bookUpdateinfo['localcover'] = 1;
                    }
                }

                // 更新作品信息
                if (!empty($bookUpdateinfo)) {
                    Db::name('book')->where('id', $bookId)->update($bookUpdateinfo);
                }

                Db::commit();

                return $this->success([
                    'book_id' => $bookId,
                    'action' => $action
                ], $action === 'created' ? '作品创建成功' : '作品更新成功');
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Throwable $e) {
            return $this->error('入库失败: ' . $e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine(), 500);
        }
    }

    /**
     * 保存章节列表
     */
    protected function saveChapters(int $bookId, int $authorid, array $chapters): array
    {
        $results = [];

        // 获取或创建默认分卷
        $volumeId = $this->createDefaultVolume($bookId);

        // 获取已存在的章节
        $existingChapters = Db::name('chapter')->where('bookid', $bookId)->column('id', 'title');

        foreach ($chapters as $idx => $chapter) {
            try {

                if (empty($chapter['title']) && empty($chapter['name'])) {
                    continue;
                }

                $title = $chapter['title'] ?? $chapter['name'] ?? '第' . ($idx + 1) . '章';
                $sort = $chapter['sort'] ?? $chapter['index'] ?? ($idx + 1);

                // 检查章节是否已存在
                $existingChapterId = $existingChapters[$title] ?? null;

                $content = $chapter['content'] ?? '';
                list($wordnum, $content) = countWordsAndContent($content, true);

                $chapterData = [
                    'bookid' => $bookId,
                    'volumeid' => $volumeId,
                    'authorid' => $authorid,
                    'title' => $title,
                    'verify' => $chapter['verify'] ?? 1,
                    'draft' => $chapter['draft'] ?? 0,
                    'wordnum' => $wordnum ?? 0,
                    'chaps' => $sort,
                    'status' => $chapter['status'] ?? 1,
                ];

                if ($existingChapterId) {
                    // 更新章节（保留原ID，不删除再创建）
                    $chapterData['update_time'] = time();
                    Db::name('chapter')->where('id', $existingChapterId)->update($chapterData);
                    $chapterId = $existingChapterId;
                    $action = 'updated';
                } else {
                    // 新增章节                     
                    $chapterData['create_time'] = time();
                    $chapterId = Db::name('chapter')->insertGetId($chapterData);
                    $action = 'created';
                }

                // 处理章节内容
                if (!empty($content) && $chapterId) {
                    // 保存章节内容
                    Content::add(
                        $bookId,
                        $chapterId,
                        $content
                    );
                }

                unset($existingChapters[$title]);

                $results[] = [
                    'chapter_id' => $chapterId,
                    'wordnum' => $wordnum,
                    'sort' => $sort,
                    'action' => $action,
                    'source_url' => (string)($chapter['source_url'] ?? $chapter['url'] ?? $chapter['link'] ?? ''),
                    'external_id' => (string)($chapter['external_id'] ?? ''),
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        // 处理被删除的章节
        foreach ($existingChapters as $existingTitle => $existingChapterId) {
            try {
                // 先删除章节
                Db::name('chapter')->where('id', $existingChapterId)->delete();
                // 再删除章节内容
                Content::delete($bookId, $existingChapterId);
            } catch (\Exception $e) {
                continue;
            }
        }

        return $results;
    }

    /** Receive a signed result from the standalone collector. */
    public function reverseCallback(Request $request)
    {
        $raw = file_get_contents('php://input');
        $timestamp = (string)$request->header('X-Collect-Timestamp', '');
        $signature = (string)$request->header('X-Collect-Signature', '');
        $secret = (string)config('reverse_collect.callback_secret');
        if (
            !$secret || !ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 300
            || !hash_equals(hash_hmac('sha256', $timestamp . '.' . $raw, $secret), $signature)
        ) {
            return $this->error('Invalid callback signature', 401);
        }
        $input = json_decode($raw, true);
        if (!is_array($input) || empty($input['request_id']) || empty($input['mode'])) return $this->error('Invalid callback payload', 400);
        if (!\app\service\ReverseCollectService::tablesInstalled()) return $this->error('Reverse collection tables are not installed', 503);
        $job = Db::name('collect_job')->where('request_id', $input['request_id'])->find();
        if (!$job) return $this->error('Unknown request_id', 404);
        if ($job['status'] === 'success') return $this->success(['request_id' => $input['request_id']], 'Already processed');
        if (isset($input['success']) && !$input['success']) {
            Db::name('collect_job')->where('id', $job['id'])->update([
                'status' => 'failed',
                'last_error' => mb_substr((string)($input['error'] ?? 'Collector failed'), 0, 1000),
                'next_retry_at' => time() + 60,
                'update_time' => time(),
            ]);
            return $this->success(['request_id' => $input['request_id']], 'Failure recorded');
        }

        $data = $input['data'] ?? [];
        try {
            Db::startTrans();
            if ($input['mode'] === 'work_catalog') {
                $book = Db::name('book')->where('id', $job['book_id'])->find();
                if (!$book) throw new \RuntimeException('Book not found');
                $update = [];
                if (!empty($data['summary'])) $update['remark'] = strip_tags((string)$data['summary']);
                if (isset($data['update_status'])) $update['isfinish'] = (int)$data['update_status'];
                if ($update) {
                    $update['update_time'] = time();
                    Db::name('book')->where('id', $book['id'])->update($update);
                }
                if (empty($data['chapters']) || !is_array($data['chapters'])) throw new \RuntimeException('Collector returned an empty catalog');
                $results = $this->saveChapters((int)$book['id'], (int)$book['authorid'], $data['chapters']);
                $source = Db::name('collect_source')->where('book_id', $book['id'])->find();
                $this->syncSourceMappings((int)$book['id'], (int)$input['task_id'], (string)$input['source_url'], $results);
                Db::name('collect_source')->where('book_id', $book['id'])->update(['last_catalog_sync_at' => time(), 'last_error' => '', 'update_time' => time()]);
            } elseif ($input['mode'] === 'chapter_content') {
                $content = trim((string)($data['content'] ?? ''));
                if ($content === '') throw new \RuntimeException('Collector returned empty chapter content');
                $chapter = Db::name('chapter')->where(['id' => $job['chapter_id'], 'bookid' => $job['book_id']])->find();
                if (!$chapter) throw new \RuntimeException('Chapter does not belong to book');
                list($wordnum, $content) = countWordsAndContent($content, true);
                Content::add((int)$job['book_id'], (int)$job['chapter_id'], $content);
                Db::name('chapter')->where('id', $job['chapter_id'])->update(['wordnum' => $wordnum, 'update_time' => time()]);
                Db::name('collect_chapter_source')->where('chapter_id', $job['chapter_id'])->update(['last_content_sync_at' => time(), 'last_error' => '', 'update_time' => time()]);
            } else {
                throw new \RuntimeException('Unsupported callback mode');
            }
            Db::name('collect_job')->where('id', $job['id'])->update(['status' => 'success', 'last_error' => '', 'update_time' => time()]);
            Db::commit();
            return $this->success(['request_id' => $input['request_id']], 'Callback processed');
        } catch (\Throwable $e) {
            Db::rollback();
            Db::name('collect_job')->where('id', $job['id'])->update(['status' => 'failed', 'last_error' => mb_substr($e->getMessage(), 0, 1000), 'update_time' => time()]);
            return $this->error($e->getMessage(), 500);
        }
    }

    public function chapterJobStatus(Request $request)
    {
        $chapterId = (int)$request->param('chapter_id', 0);
        if ($chapterId <= 0) return $this->error('Invalid chapter_id', 400);
        return $this->success(\app\service\ReverseCollectService::chapterJobStatus($chapterId));
    }

    protected function syncSourceMappings(int $bookId, int $taskId, string $sourceUrl, array $chapters): void
    {
        if (!\app\service\ReverseCollectService::tablesInstalled()) throw new \RuntimeException('Reverse collection tables are not installed');
        $now = time();
        $source = Db::name('collect_source')->where('book_id', $bookId)->find();
        $values = ['book_id' => $bookId, 'collector_task_id' => $taskId, 'source_url' => $sourceUrl, 'source_hash' => md5($sourceUrl), 'update_time' => $now];
        if ($source) Db::name('collect_source')->where('id', $source['id'])->update($values);
        else {
            $values['create_time'] = $now;
            $values['last_catalog_sync_at'] = $now;
            $sourceId = Db::name('collect_source')->insertGetId($values);
            $source = ['id' => $sourceId];
        }
        foreach ($chapters as $chapter) {
            if (empty($chapter['chapter_id']) || empty($chapter['source_url'])) continue;
            $sourceHash = md5($chapter['source_url']);
            $chapterRow = Db::name('collect_chapter_source')->where('chapter_id', $chapter['chapter_id'])->find();
            $sourceRow = Db::name('collect_chapter_source')->where('source_hash', $sourceHash)->find();
            $item = ['book_id' => $bookId, 'chapter_id' => $chapter['chapter_id'], 'collect_source_id' => $source['id'], 'external_id' => $chapter['external_id'], 'source_url' => $chapter['source_url'], 'source_hash' => $sourceHash, 'update_time' => $now];
            if ($sourceRow) {
                if ($chapterRow && $chapterRow['id'] != $sourceRow['id']) {
                    Db::name('collect_chapter_source')->where('id', $chapterRow['id'])->delete();
                }
                Db::name('collect_chapter_source')->where('id', $sourceRow['id'])->update($item);
            } elseif ($chapterRow) {
                Db::name('collect_chapter_source')->where('id', $chapterRow['id'])->update($item);
            } else {
                $item['create_time'] = $now;
                Db::name('collect_chapter_source')->insert($item);
            }
        }
        $validChapterIds = Db::name('chapter')->where('bookid', $bookId)->column('id');
        if (!empty($validChapterIds)) {
            Db::name('collect_chapter_source')->where('book_id', $bookId)->whereNotIn('chapter_id', $validChapterIds)->delete();
        }
    }

    /**
     * 处理封面下载
     */
    private function handleCoverImage($coverPath, $bookId)
    {
        // 简单的URL验证
        if (!filter_var($coverPath, FILTER_VALIDATE_URL)) {
            return '';
        }

        // 检查是否需要下载
        $extension = pathinfo($coverPath, PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'wepb', 'webp'];
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            return '';
        }

        $upload_path = app()->getRootPath() . 'public/storage/bookcover/';

        // 用bookId哈希取模分散到不同目录，相同bookId始终在同一目录
        $hash = md5((string) $bookId);
        $dirIndex = hexdec(substr($hash, -2)) % 100; // 取后2位转10进制再取模100
        $subDir = str_pad((string) $dirIndex, 2, '0', STR_PAD_LEFT);
        $targetDir = $upload_path . $subDir . '/';

        // 创建目录
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = $bookId . '.' . $extension;
        $targetPath = $targetDir . $fileName;

        // 下载图片 - 使用与Python采集器相同的反Cloudflare机制
        try {
            // 解析目标域名
            $parsedUrl = parse_url($coverPath);
            $baseDomain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            $baseReferer = $baseDomain . '/';

            // 使用curl下载，添加真实浏览器请求头（与Python采集器一致）
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $coverPath);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            // 模拟浏览器 headers（简洁版，避免过多指纹）
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Referer: ' . $baseReferer,
                'DNT: 1',
            ]);

            // 保存 cookies 以支持 Cloudflare
            $cookieFile = tempnam(sys_get_temp_dir(), 'cookie_');
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);

            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            @unlink($cookieFile);

            // 检查是否为 Cloudflare 拦截页面
            if ($httpCode === 403 || $httpCode === 429) {
                return '';
            }

            // 检查内容是否为 HTML/Cloudflare 页面（被拦截的响应）
            if (!empty($content)) {
                $contentLower = strtolower($content);
                $blockedKeywords = [
                    'cloudflare',
                    'ray id',
                    'ddos protection',
                    'checking your browser',
                    'captcha',
                    'access denied',
                    'please wait',
                    '验证',
                    '访问过于频繁'
                ];
                foreach ($blockedKeywords as $keyword) {
                    if (stripos($contentLower, $keyword) !== false) {
                        return '';
                    }
                }
            }

            // 内容太短或为空，可能是错误响应
            if (strlen($content) < 500) {
                return '';
            }

            // 检查是否为有效的图片 MIME 类型
            if (!empty($contentType) && strpos($contentType, 'image/') === false) {
                return '';
            }

            if (empty($content)) {
                return '';
            }

            // 保存文件
            file_put_contents($targetPath, $content);

            // 验证是否为图片
            if (!$this->isValidImage($targetPath)) {
                @unlink($targetPath);
                return '';
            }

            // 返回相对路径
            return '/storage/bookcover/' . $subDir . '/' . $fileName;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 保存 base64 编码的封面图片
     * @param string $base64String base64编码的图片 (data:image/jpeg;base64,/9j/4AAQ...)
     * @param int $bookId 作品ID
     * @return string 相对路径，失败返回空字符串
     */
    private function saveBase64Cover($base64String, $bookId = null)
    {
        if (empty($base64String)) {
            return '';
        }

        // 解析 base64 数据
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $base64String, $matches)) {
            $extension = $matches[1];
            $imageData = base64_decode($matches[2]);
        } else {
            // 尝试直接解码（可能是纯 base64）
            $imageData = base64_decode($base64String);
            if (!$imageData) {
                return '';
            }
            $extension = 'jpg';
        }

        if (!$imageData || strlen($imageData) < 500) {
            return '';
        }

        // 生成 bookId（如果未提供则用时间戳）
        $id = $bookId ?? time() . '_' . mt_rand(1000, 9999);

        // 确定保存目录
        $upload_path = app()->getRootPath() . 'public/storage/bookcover/';
        $hash = md5((string) $id);
        $dirIndex = hexdec(substr($hash, -2)) % 100;
        $subDir = str_pad((string) $dirIndex, 2, '0', STR_PAD_LEFT);
        $targetDir = $upload_path . $subDir . '/';

        // 创建目录
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 映射扩展名
        $extMap = ['jpeg' => 'jpg', 'png' => 'png', 'gif' => 'gif', 'webp' => 'webp'];
        $saveExt = $extMap[$extension] ?? 'jpg';
        $fileName = $id . '.' . $saveExt;
        $targetPath = $targetDir . $fileName;

        // 保存文件
        if (file_put_contents($targetPath, $imageData) === false) {
            return '';
        }

        // 验证是否为图片
        if (!$this->isValidImage($targetPath)) {
            @unlink($targetPath);
            return '';
        }

        // 返回相对路径
        return '/storage/bookcover/' . $subDir . '/' . $fileName;
    }

    /**
     * 验证是否为有效图片（兼容fileinfo未安装的情况）
     */
    private function isValidImage($filePath)
    {
        if (!file_exists($filePath) || !is_file($filePath)) {
            return false;
        }

        // 方法1：尝试使用 getimagesize（需要gd扩展）
        if (function_exists('getimagesize')) {
            $info = @getimagesize($filePath);
            if ($info !== false && in_array($info[2], [1, 2, 3, 4, 6, 9, 13, 15, 16, 18])) { // IMAGETYPE_XXX 常量
                return true;
            }
        }

        // 方法2：使用 finfo（需要fileinfo扩展）
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = @finfo_file($finfo, $filePath);
                finfo_close($finfo);
                if ($mimeType && in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'])) {
                    return true;
                }
            }
        }

        // 方法3：检查文件头（PNG, JPG, GIF, BMP, WebP）
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            $header = fread($handle, 16);
            fclose($handle);

            if ($header) {
                // 检查常见图片格式的文件头
                $png = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
                $jpg = "\xFF\xD8\xFF";
                $gif1 = "GIF87a";
                $gif2 = "GIF89a";
                $bmp = "BM";
                $webp1 = "RIFF";
                $webp2 = "WEBP";

                if (
                    strpos($header, $png) === 0 ||
                    strpos($header, $jpg) === 0 ||
                    strpos($header, $gif1) === 0 ||
                    strpos($header, $gif2) === 0 ||
                    strpos($header, $bmp) === 0 ||
                    (strpos($header, $webp1) === 0 && strpos($header, $webp2) === 8)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 将各种时间字符串格式统一转换为11位数字时间戳
     * 支持: ISO8601, Y-m-d H:i:s, Y-m-d, Y/m/d, 中文日期等
     */
    protected function parseUpdateTime($value): int
    {
        if (empty($value)) {
            return time();
        }

        // 已是纯数字时间戳（10位或11位）
        if (is_numeric($value)) {
            $t = (int)$value;
            if ($t > 1000000000 && $t < 99999999999) {
                return $t;
            }
        }

        if (is_string($value)) {
            $value = trim($value);

            // 尝试 strtotime 解析常见格式
            $ts = strtotime($value);
            if ($ts !== false && $ts > 0) {
                return $ts;
            }

            // 正则匹配 YYYY-MM-DD 或 YYYY/MM/DD 等
            if (preg_match('/(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $value, $m)) {
                $ts = mktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
                if ($ts !== false) {
                    return $ts;
                }
            }

            // 中文日期: 2026年5月22日
            if (preg_match('/(\d{4})\s*年\s*(\d{1,2})\s*月\s*(\d{1,2})\s*日/', $value, $m)) {
                return mktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
            }
        }

        // 无法解析，返回当前时间戳
        return time();
    }

    /**
     * 字段映射
     */
    protected function mapWorkFields(array $data): array
    {
        $fieldMapping = [
            'title' => 'title',
            'genre' => 'genre',
            'subgenre' => 'subgenre',
            'style' => 'style',
            'ending' => 'ending',
            'cover' => 'cover',
            'author' => 'author',
            'hits' => 'hits',
            'status' => 'status',
            'update_status' => 'isfinish',
            'updated_time' => 'update_time',
            'word_count' => 'words',
            'download_cover' => 'download_cover',
            'cover_base64' => 'cover_base64',
            'tags' => 'label',
            'description' => 'remark',
            'sort' => 'sort',
        ];

        $mapped = [];
        foreach ($fieldMapping as $sourceField => $targetField) {
            if (isset($data[$sourceField])) {
                $val = $data[$sourceField];

                // 处理数组类型：author和tags可能是数组
                if (is_array($val)) {
                    $val = implode(',', array_filter($val));
                }

                $mapped[$targetField] = $val ?? '';
            }
        }

        // 处理update_status：确保是0或1
        if (isset($mapped['isfinish'])) {
            $mapped['isfinish'] = $this->normalizeUpdateStatus($mapped['isfinish']);
        }

        return $mapped;
    }

    /**
     * 规范化连载状态为0或1
     */
    protected function normalizeUpdateStatus($status): int
    {
        if (is_numeric($status)) {
            return intval($status) == 2 ? 2 : 1;
        }

        // 处理字符串类型
        $complete = ['2', '完结', '已完成', '已完结', '完结', '全本', '完本'];
        $statusStr = is_string($status) ? $status : '';

        return in_array($statusStr, $complete) ? 2 : 1;
    }

    /**
     * 检查是否存在
     */
    protected function checkBookIfExist(array $book): array
    {
        if (empty($book)) {
            return [];
        }
        return Db::name('book')->where('title', $book['title'])->where('author', $book['author'])->find() ?? [];
    }

    /**
     * 创建默认分卷
     */
    protected function createDefaultVolume(int $bookId): int
    {
        if (empty($bookId)) {
            return 0;
        }

        $volume = Db::name('volume')->where('bookid', $bookId)
            ->where('title', '默认分卷')
            ->find();

        if (!$volume) {
            $volume = [
                'bookid' => $bookId,
                'title' => '默认分卷',
                'sort' => 1,
                'create_time' => time()
            ];
            $volume['id'] = Db::name('volume')->insertGetId($volume);
        }

        return $volume['id'] ?? 0;
    }

    /**
     * 生成随机盐
     */
    private function set_salt($length = 6)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $salt = '';
        for ($i = 0; $i < $length; $i++) {
            $salt .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $salt;
    }

    /**
     * 处理作者信息
     */
    private function handleAuthor($authorName)
    {
        // 按作者名称查询作者表
        $author = Db::name('author')
            ->where('nickname', $authorName)
            ->find();

        if ($author) {
            return $author['id'];
        } else {
            // 作者不存在，创建新作者
            $time = (string) time();
            $salt = substr(md5($time), 0, 6);
            $password = $this->set_salt(20);
            $authorId = Db::name('author')->insertGetId([
                'nickname' => $authorName,
                'salt' => $salt,
                'password' => sha1(md5($password) . $salt),
                'ip' => request()->ip(),
                'create_time' => $time,
                'status' => 1,
                'authstate' => 0,
                'bankstate' => 0,
                'issign' => 0
            ]);
            return $authorId;
        }
    }

    /**
     * 生成文件名拼音
     */
    private static function generateFilename($title)
    {
        if (empty($title)) {
            return uniqid();
        }
        try {
            // 使用拼音插件生成拼音文件名
            $Pinyin = new \Overtrue\Pinyin\Pinyin;
            $filename = $Pinyin->permalink($title, '');
            // 如果生成的拼音为空，使用备选方案
            if (empty($filename)) {
                $filename = preg_replace('/[^\w]/', '', $title);
                if (empty($filename)) {
                    $filename = uniqid();
                }
            }
            return $filename;
        } catch (\Exception $e) {
            // 如果拼音转换失败，使用备选方案
            $filename = preg_replace('/[^\w]/', '', $title);
            return empty($filename) ? uniqid() : $filename;
        }
        $filename = preg_replace('/[^\w]/', '', $title);
        return empty($filename) ? uniqid() : $filename;
    }

    /**
     * 处理分类信息
     */
    private function processGenre(&$book_info)
    {
        // 处理subgenre字段
        if (isset($book_info['subgenre']) && !empty($book_info['subgenre'])) {
            $subgenre_value = $book_info['subgenre'];
            $category = null;
            // 判断是数字还是字符
            if (intval($subgenre_value) == $subgenre_value) {
                // 数字，查询id
                $category = Db::name('category')
                    ->where('id', (int)$subgenre_value)
                    ->find();
            } else {
                $result = self::getCategoryByWordSegmentation($subgenre_value);
                $book_info['genre'] = $result['genre'] ?? 0;
                $book_info['subgenre'] = $result['subgenre'] ?? 0;
                return true;
            }
            if ($category) {
                // 找到分类
                if ($category['pid'] > 0) {
                    // 子类，设置genre为父类id，subgenre为当前id
                    $book_info['genre'] = $category['pid'];
                    $book_info['subgenre'] = $category['id'];
                    return true;
                } else {
                    // 父类，设置genre为当前id，清空subgenre
                    $book_info['genre'] = $category['id'];
                    $book_info['subgenre'] = '';
                    return true;
                }
            } else {
                // 未找到分类，清空subgenre
                $book_info['subgenre'] = '';
            }
        } else {
            $book_info['subgenre'] = '';
        }
        // 处理genre字段
        if (isset($book_info['genre']) && !empty($book_info['genre'])) {
            $genre_value = $book_info['genre'];
            $category = null;
            // 判断是数字还是字符
            if (intval($genre_value) == $genre_value) {
                // 数字，查询id
                $category = Db::name('category')
                    ->where('id', (int)$genre_value)
                    ->find();
            } else {
                $result = $this->getCategoryByWordSegmentation($genre_value);
                $book_info['genre'] = $result['genre'] ?? 0;
                $book_info['subgenre'] = $result['subgenre'] ?? 0;
                return true;
            }
            if ($category) {
                // 找到分类
                if ($category['pid'] > 0) {
                    // 子类，设置genre为父类id，subgenre为当前id
                    $book_info['genre'] = $category['pid'];
                    $book_info['subgenre'] = $category['id'];
                    return true;
                } else {
                    // 父类，只设置genre
                    $book_info['genre'] = $category['id'];
                }
            }
        }
        if (!isset($book_info['genre'])) $book_info['genre'] = 14;
        if (!isset($book_info['subgenre'])) $book_info['subgenre'] = '';
        if (!ctype_digit((string)$book_info['genre'])) {
            $book_info['genre'] = 14;
        }
        if (!ctype_digit((string)$book_info['subgenre'])) {
            $book_info['subgenre'] = '';
        }
    }

    /**
     * 通过分词匹配分类
     */
    private function getCategoryByWordSegmentation($categoryName)
    {
        $category = Db::name('category')
            ->where('name', $categoryName)
            ->find();
        if (!empty($category)) {
            if ($category['pid'] > 0) {
                return ['genre' => $category['pid'], 'subgenre' => $category['id']];
            } else {
                return ['genre' => $category['id'], 'subgenre' => 0];
            }
        }
        // 分词逻辑
        $words = $this->segmentWords($categoryName);
        // 按词长倒序排列，优先匹配长词
        usort($words, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });
        // 逐个尝试匹配
        foreach ($words as $word) {
            if (mb_strlen($word) < 2) { // 跳过单字词
                continue;
            }
            $category = Db::name('category')
                ->where('name', $word)
                ->find();
            if ($category) {
                if ($category['pid'] > 0) {
                    return ['genre' => $category['pid'], 'subgenre' => $category['id']];
                } else {
                    return ['genre' => $category['id'], 'subgenre' => 0];
                }
            }
        }
        return ['genre' => 14, 'subgenre' => 0];
    }

    /**
     * 简单的中文分词函数
     * 可以根据需要替换为更专业的分词库
     */
    private  function segmentWords($text)
    {
        $words = [];
        $length = mb_strlen($text, 'UTF-8');
        // 生成所有可能的词（从2字到整个字符串）
        for ($i = 0; $i < $length; $i++) {
            for ($j = 2; $j <= $length - $i; $j++) {
                $word = mb_substr($text, $i, $j, 'UTF-8');
                $words[] = $word;
            }
        }
        // 去重并返回
        return array_unique($words);
    }

    /**
     * 返回成功响应
     */
    protected function success(array $data, string $message = '操作成功', int $code = 200)
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time(),
        ]);
    }

    /**
     * 返回错误响应
     */
    protected function error(string $message, int $code = 400)
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => null,
            'timestamp' => time(),
        ], $code);
    }
}
