<?php

namespace app\service;

use think\facade\Config;
use think\facade\Db;

class ReverseCollectService
{
    /** @var bool */
    private static $tablesReady;

    public static function enabled(): bool
    {
        return (bool) Config::get('reverse_collect.enabled')
            && Config::get('reverse_collect.collector_base_url')
            && Config::get('reverse_collect.collector_api_key')
            && Config::get('reverse_collect.callback_base_url')
            && Config::get('reverse_collect.callback_secret');
    }

    public static function tablesInstalled(): bool
    {
        if (self::$tablesReady !== null) return self::$tablesReady;
        try {
            $prefix = config('database.connections.mysql.prefix');
            foreach (['collect_source', 'collect_chapter_source', 'collect_job'] as $table) {
                if (empty(Db::query("SHOW TABLES LIKE '" . addslashes($prefix . $table) . "'"))) {
                    return self::$tablesReady = false;
                }
            }
            return self::$tablesReady = true;
        } catch (\Throwable $e) {
            return self::$tablesReady = false;
        }
    }

    public static function installTables(): void
    {
        if (self::tablesInstalled()) return;
        Db::execute("CREATE TABLE IF NOT EXISTS `" . config('database.connections.mysql.prefix') . "collect_source` (
          `id` int unsigned NOT NULL AUTO_INCREMENT, `book_id` int unsigned NOT NULL,
          `collector_task_id` int unsigned NOT NULL, `source_url` varchar(1000) NOT NULL,
          `source_hash` char(32) NOT NULL, `last_catalog_request_at` int unsigned NOT NULL DEFAULT 0,
          `last_catalog_sync_at` int unsigned NOT NULL DEFAULT 0, `last_error` varchar(1000) NOT NULL DEFAULT '',
          `create_time` int unsigned NOT NULL, `update_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`), UNIQUE KEY `uniq_book` (`book_id`), UNIQUE KEY `uniq_source` (`source_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        Db::execute("CREATE TABLE IF NOT EXISTS `" . config('database.connections.mysql.prefix') . "collect_chapter_source` (
          `id` int unsigned NOT NULL AUTO_INCREMENT, `book_id` int unsigned NOT NULL, `chapter_id` int unsigned NOT NULL,
          `collect_source_id` int unsigned NOT NULL, `external_id` varchar(255) NOT NULL DEFAULT '',
          `source_url` varchar(1000) NOT NULL, `source_hash` char(32) NOT NULL,
          `last_content_request_at` int unsigned NOT NULL DEFAULT 0, `last_content_sync_at` int unsigned NOT NULL DEFAULT 0,
          `last_error` varchar(1000) NOT NULL DEFAULT '', `create_time` int unsigned NOT NULL, `update_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`), UNIQUE KEY `uniq_chapter` (`chapter_id`), UNIQUE KEY `uniq_source` (`source_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        Db::execute("CREATE TABLE IF NOT EXISTS `" . config('database.connections.mysql.prefix') . "collect_job` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT, `request_id` char(36) NOT NULL, `job_type` varchar(32) NOT NULL,
          `book_id` int unsigned NOT NULL DEFAULT 0, `chapter_id` int unsigned NOT NULL DEFAULT 0,
          `collect_source_id` int unsigned NOT NULL DEFAULT 0, `source_url` varchar(1000) NOT NULL,
          `status` varchar(20) NOT NULL DEFAULT 'pending', `attempts` tinyint unsigned NOT NULL DEFAULT 0,
          `priority` smallint unsigned NOT NULL DEFAULT 30,
          `max_attempts` tinyint unsigned NOT NULL DEFAULT 5, `next_retry_at` int unsigned NOT NULL DEFAULT 0,
          `collector_job_id` varchar(100) NOT NULL DEFAULT '', `last_error` varchar(1000) NOT NULL DEFAULT '',
          `create_time` int unsigned NOT NULL, `update_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`), UNIQUE KEY `uniq_request` (`request_id`), KEY `idx_dispatch` (`status`,`next_retry_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        self::$tablesReady = true;
    }

    public static function enqueueBook(int $bookId): bool
    {
        if (!self::enabled() || !self::tablesInstalled()) return false;
        $source = Db::name('collect_source')->where('book_id', $bookId)->find();
        $now = time();
        if (!$source || $source['last_catalog_request_at'] + Config::get('reverse_collect.catalog_refresh_seconds') > $now) return false;
        return self::enqueue('work_catalog', $source, $bookId, 0, $now, 'last_catalog_request_at', 'collect_source', 30);
    }

    public static function enqueueChapter(int $chapterId, int $bookId, bool $missingContent = false): bool
    {
        if (!self::enabled() || !self::tablesInstalled()) return false;
        $source = Db::name('collect_chapter_source')->where(['chapter_id' => $chapterId, 'book_id' => $bookId])->find();
        $now = time();
        if (!$source) return false;
        if (!$missingContent && $source['last_content_request_at'] + Config::get('reverse_collect.chapter_refresh_seconds') > $now) return false;
        $parent = Db::name('collect_source')->where('id', $source['collect_source_id'])->find();
        if (!$parent) return false;
        $source['collector_task_id'] = $parent['collector_task_id'];
        return self::enqueue('chapter_content', $source, $bookId, $chapterId, $now, 'last_content_request_at', 'collect_chapter_source', $missingContent ? 100 : 80);
    }

    private static function enqueue(string $type, array $source, int $bookId, int $chapterId, int $now, string $touch, string $table = 'collect_source', int $priority = 30): bool
    {
        $active = Db::name('collect_job')->where(['job_type' => $type, 'book_id' => $bookId, 'chapter_id' => $chapterId])->whereIn('status', ['pending','dispatching','accepted','running','failed'])->order('id desc')->find();
        if ($active) {
            $update = [];
            if ((int)$active['priority'] < $priority) $update['priority'] = $priority;
            if ($type === 'chapter_content' && $priority >= 100 && $active['status'] === 'failed') {
                $update['next_retry_at'] = $now;
            }
            if ($update) { $update['update_time'] = $now; Db::name('collect_job')->where('id', $active['id'])->update($update); }
            return false;
        }
        $requestId = self::uuid();
        Db::transaction(function () use ($type, $source, $bookId, $chapterId, $now, $touch, $table, $requestId, $priority) {
            Db::name('collect_job')->insert([
                'request_id' => $requestId, 'job_type' => $type, 'book_id' => $bookId, 'chapter_id' => $chapterId,
                'collect_source_id' => $source['id'], 'source_url' => $source['source_url'], 'status' => 'pending',
                'priority' => $priority,
                'max_attempts' => Config::get('reverse_collect.max_attempts'), 'next_retry_at' => $now,
                'create_time' => $now, 'update_time' => $now,
            ]);
            Db::name($table)->where('id', $source['id'])->update([$touch => $now, 'update_time' => $now]);
        });
        return true;
    }

    public static function prefetchNextChapters(int $bookId, int $currentChaps, int $limit = 2): void
    {
        if (!self::enabled()) return;
        $chapters = Db::name('chapter')->field('id,bookid')->where('bookid', $bookId)
            ->where('status', 1)->whereIn('verify', [0, 1])->where('chaps', '>', $currentChaps)
            ->order('chaps asc')->limit($limit)->select()->toArray();
        foreach ($chapters as $chapter) {
            $content = \content\Content::get((int)$bookId, (int)$chapter['id']);
            self::enqueueChapter((int)$chapter['id'], $bookId, empty($content));
        }
    }

    public static function chapterJobStatus(int $chapterId): array
    {
        if (!self::enabled()) return ['status' => 'disabled', 'ready' => false];
        if (!self::tablesInstalled()) return ['status' => 'not_installed', 'ready' => false];
        $chapter = Db::name('chapter')->field('id,bookid')->where('id', $chapterId)->find();
        if (!$chapter) return ['status' => 'not_found', 'ready' => false];
        $content = \content\Content::get((int)$chapter['bookid'], $chapterId);
        if (!empty($content)) return ['status' => 'success', 'ready' => true];
        $job = Db::name('collect_job')->where(['job_type' => 'chapter_content', 'chapter_id' => $chapterId])->order('id desc')->find();
        if (!$job) {
            $hasSource = Db::name('collect_chapter_source')->where('chapter_id', $chapterId)->count() > 0;
            return ['status' => $hasSource ? 'pending' : 'no_source', 'ready' => false, 'error' => ''];
        }
        return ['status' => $job['status'], 'ready' => false];
    }

    public static function dispatch(int $limit = 20): array
    {
        if (!self::tablesInstalled()) self::installTables();
        if (!self::enabled() || !self::tablesInstalled()) return ['accepted' => 0, 'failed' => 0];
        $jobs = Db::name('collect_job')->whereIn('status', ['pending','failed'])->where('next_retry_at', '<=', time())->order('priority desc,id asc')->limit($limit)->select()->toArray();
        $result = ['accepted' => 0, 'failed' => 0];
        foreach ($jobs as $job) {
            $source = Db::name('collect_source')->where('book_id', $job['book_id'])->find();
            if (!$source) continue;
            $chapterTitle = '';
            if (!empty($job['chapter_id'])) {
                $chapterTitle = (string)Db::name('chapter')->where('id', $job['chapter_id'])->value('title');
            }
            Db::name('collect_job')->where('id', $job['id'])->update(['status' => 'dispatching', 'attempts' => $job['attempts'] + 1, 'update_time' => time()]);
            $body = json_encode([
                'request_id' => $job['request_id'], 'mode' => $job['job_type'], 'task_id' => (int)$source['collector_task_id'],
                'source_url' => $job['source_url'], 'callback_url' => Config::get('reverse_collect.callback_base_url') . '/api/v1/collectCallback',
                'callback_secret' => Config::get('reverse_collect.callback_secret'),
                'payload' => ['book_id' => (int)$job['book_id'], 'chapter_id' => (int)$job['chapter_id'], 'chapter_title' => $chapterTitle],
                'api_key' => Config::get('reverse_collect.collector_api_key'),
            ], JSON_UNESCAPED_UNICODE);
            [$ok, $response, $error] = self::post(Config::get('reverse_collect.collector_base_url') . '/api/collector/jobs', $body);
            if ($ok && !empty($response['success'])) {
                Db::name('collect_job')->where('id', $job['id'])->update(['status' => 'accepted', 'collector_job_id' => (string)($response['job_id'] ?? ''), 'last_error' => '', 'update_time' => time()]);
                $result['accepted']++;
            } else {
                $attempts = $job['attempts'] + 1;
                $status = $attempts >= $job['max_attempts'] ? 'cancelled' : 'failed';
                Db::name('collect_job')->where('id', $job['id'])->update(['status' => $status, 'last_error' => mb_substr($error ?: json_encode($response), 0, 1000), 'next_retry_at' => time() + min(3600, 30 * (2 ** $attempts)), 'update_time' => time()]);
                $result['failed']++;
            }
        }
        return $result;
    }

    private static function post(string $url, string $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => Config::get('reverse_collect.dispatch_timeout')]);
        $raw = curl_exec($ch); $error = curl_error($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        return [$raw !== false && $code >= 200 && $code < 300, json_decode((string)$raw, true) ?: [], $error ?: "HTTP {$code}"];
    }

    private static function uuid(): string
    {
        $d = random_bytes(16); $d[6] = chr((ord($d[6]) & 0x0f) | 0x40); $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
