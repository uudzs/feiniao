<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\service\EnvConfigService;
use app\service\ReverseCollectService;
use think\facade\Cache;
use think\facade\View;

class Envmanage extends BaseController
{
    public function index()
    {
        try {
            $env = (new EnvConfigService())->all();
            $error = '';
        } catch (\Throwable $e) {
            $env = [];
            $error = $e->getMessage();
        }
        View::assign('config', $this->formConfig($env));
        View::assign('languages', $this->languages());
        View::assign('tables_installed', ReverseCollectService::tablesInstalled());
        View::assign('env_error', $error);
        return View::fetch();
    }

    public function saveconfig()
    {
        if (!$this->request->isPost()) return json(['code' => 1, 'msg' => '请求方式错误']);
        try {
            $data = $this->validatedInput();
            $current = (new EnvConfigService())->all();
            if ($data['reverse_enabled']) {
                if (!ReverseCollectService::tablesInstalled()) {
                    throw new \RuntimeException('请先初始化反向采集数据表');
                }
                $wasEnabled = filter_var((string)($current['REVERSE_COLLECT.ENABLED'] ?? 'false'), FILTER_VALIDATE_BOOLEAN);
                $changed = !$wasEnabled || !$this->reverseMatches($data, $current);
                if ($changed && !$this->consumeTestToken((string)$this->request->param('reverse_test_token', ''), $data)) {
                    throw new \RuntimeException('反向采集配置已变更，请先点击“测试采集器”，测试通过后再保存');
                }
            }
            $languages = array_keys($this->languages());
            (new EnvConfigService())->update([
                'APP_DEBUG' => $data['app_debug'] ? 'true' : 'false',
                'LANG.LANG_SWITCH_ON' => $data['lang_switch_on'] ? 'true' : 'false',
                'LANG.LANG_ALLOW_LIST' => implode(',', $languages),
                'LANG.DEFAULT_LANG' => $data['default_lang'],
                'TTS.BASE_URL' => $data['tts_base_url'],
                'TTS.AUTH_ENABLED' => $data['tts_auth_enabled'] ? 'true' : 'false',
                'REVERSE_COLLECT.ENABLED' => $data['reverse_enabled'] ? 'true' : 'false',
                'REVERSE_COLLECT.COLLECTOR_BASE_URL' => $data['collector_base_url'],
                'REVERSE_COLLECT.COLLECTOR_API_KEY' => $data['collector_api_key'],
                'REVERSE_COLLECT.CALLBACK_BASE_URL' => $data['callback_base_url'],
                'REVERSE_COLLECT.CALLBACK_SECRET' => $data['callback_secret'],
                'REVERSE_COLLECT.CATALOG_REFRESH_SECONDS' => (string)$data['catalog_refresh_seconds'],
                'REVERSE_COLLECT.CHAPTER_REFRESH_SECONDS' => (string)$data['chapter_refresh_seconds'],
                'REVERSE_COLLECT.DISPATCH_TIMEOUT' => (string)$data['dispatch_timeout'],
                'REVERSE_COLLECT.MAX_ATTEMPTS' => (string)$data['max_attempts'],
            ]);
            return json(['code' => 0, 'msg' => '配置已保存；常驻进程需重启后才会读取新配置']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    public function testcollector()
    {
        if (!$this->request->isPost()) return json(['code' => 1, 'msg' => '请求方式错误']);
        try {
            $data = $this->validatedInput(false);
            $url = rtrim($data['collector_base_url'], '/') . '/api/collector/test';
            $body = json_encode(['api_key' => $data['collector_api_key']], JSON_UNESCAPED_UNICODE);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => max(3, $data['dispatch_timeout']),
            ]);
            $raw = curl_exec($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $result = json_decode((string)$raw, true);
            if ($raw === false || $status < 200 || $status >= 300 || empty($result['success'])) {
                $detail = $error ?: (string)($result['error'] ?? ('HTTP ' . $status));
                throw new \RuntimeException('连接或鉴权失败：' . $detail);
            }
            $token = bin2hex(random_bytes(24));
            Cache::set('reverse_collect_test_' . $token, $this->reverseHash($data), 600);
            return json(['code' => 0, 'msg' => '采集器运行正常，API 密钥验证通过', 'data' => ['token' => $token]]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
    }

    public function testtts()
    {
        if (!$this->request->isPost()) return json(['code' => 1, 'msg' => '请求方式错误'], 405);
        $baseUrl = rtrim(trim((string)$this->request->param('tts_base_url', '')), '/');
        $authEnabled = (int)$this->request->param('tts_auth_enabled', 0) === 1;
        if (!$this->validUrl($baseUrl)) return json(['code' => 1, 'msg' => '请先填写有效的 TTS BASE_URL'], 400);
        $payload = ['text' => '您好，这是一段小说朗读功能的测试语音。', 'voice' => 'zh-CN-XiaoxiaoNeural', 'rate' => 'normal', 'pitch' => 'normal'];
        try {
            if ($authEnabled) {
                list($tokenOk, $tokenData, $tokenError) = $this->postJson($baseUrl . '/token', $payload, 30);
                if (!$tokenOk || empty($tokenData['token'])) throw new \RuntimeException('获取 TTS Token 失败：' . $tokenError);
                $payload['token'] = $tokenData['token'];
            }
            $ch = curl_init($baseUrl . '/tts');
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: audio/*'], CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 60,
            ]);
            $audio = curl_exec($ch); $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE); curl_close($ch);
            if ($audio === false || $status !== 200 || $audio === '') {
                throw new \RuntimeException('TTS 生成失败：' . ($error ?: $this->ttsErrorMessage((string)$audio, $status)));
            }
            if (stripos($contentType, 'audio/') === false && stripos($contentType, 'application/octet-stream') === false) {
                throw new \RuntimeException('TTS 返回的不是音频：' . $this->ttsErrorMessage((string)$audio, $status));
            }
            return \think\Response::create($audio, 'html')->header([
                'Content-Type' => $contentType ?: 'audio/mpeg', 'Content-Length' => strlen($audio), 'Cache-Control' => 'no-store',
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => $e->getMessage()], 502);
        }
    }

    public function installtables()
    {
        if (!$this->request->isPost()) return json(['code' => 1, 'msg' => '请求方式错误']);
        try {
            ReverseCollectService::installTables();
            return json(['code' => 0, 'msg' => '反向采集数据表初始化完成']);
        } catch (\Throwable $e) {
            return json(['code' => 1, 'msg' => '初始化失败：' . $e->getMessage()]);
        }
    }

    private function validatedInput(bool $validateLanguage = true): array
    {
        $data = [
            'app_debug' => (int)$this->request->param('app_debug', 0) === 1,
            'lang_switch_on' => (int)$this->request->param('lang_switch_on', 0) === 1,
            'default_lang' => strtolower(trim((string)$this->request->param('default_lang', 'zh-cn'))),
            'tts_base_url' => rtrim(trim((string)$this->request->param('tts_base_url', '')), '/'),
            'tts_auth_enabled' => (int)$this->request->param('tts_auth_enabled', 0) === 1,
            'reverse_enabled' => (int)$this->request->param('reverse_enabled', 0) === 1,
            'collector_base_url' => rtrim(trim((string)$this->request->param('collector_base_url', '')), '/'),
            'collector_api_key' => trim((string)$this->request->param('collector_api_key', '')),
            'callback_base_url' => rtrim(trim((string)$this->request->param('callback_base_url', '')), '/'),
            'callback_secret' => trim((string)$this->request->param('callback_secret', '')),
            'catalog_refresh_seconds' => (int)$this->request->param('catalog_refresh_seconds', 21600),
            'chapter_refresh_seconds' => (int)$this->request->param('chapter_refresh_seconds', 86400),
            'dispatch_timeout' => (int)$this->request->param('dispatch_timeout', 5),
            'max_attempts' => (int)$this->request->param('max_attempts', 5),
        ];
        if ($validateLanguage && !isset($this->languages()[$data['default_lang']])) throw new \InvalidArgumentException('默认语言不在语言包列表中');
        if ($data['tts_base_url'] !== '' && !$this->validUrl($data['tts_base_url'])) throw new \InvalidArgumentException('TTS BASE_URL 必须是有效的 http/https 地址');
        if ($data['reverse_enabled'] || !$validateLanguage) {
            if (!$this->validUrl($data['collector_base_url'])) throw new \InvalidArgumentException('采集器地址无效');
            if (strlen($data['collector_api_key']) < 12) throw new \InvalidArgumentException('COLLECTOR_API_KEY 至少需要 12 个字符');
            if (!$this->validUrl($data['callback_base_url'])) throw new \InvalidArgumentException('回调地址无效');
            if (strlen($data['callback_secret']) < 12) throw new \InvalidArgumentException('CALLBACK_SECRET 至少需要 12 个字符');
            if ($data['catalog_refresh_seconds'] < 60 || $data['chapter_refresh_seconds'] < 60) throw new \InvalidArgumentException('刷新间隔不能小于 60 秒');
            if ($data['dispatch_timeout'] < 1 || $data['dispatch_timeout'] > 60) throw new \InvalidArgumentException('请求超时必须在 1-60 秒之间');
            if ($data['max_attempts'] < 1 || $data['max_attempts'] > 20) throw new \InvalidArgumentException('最大重试次数必须在 1-20 之间');
        }
        return $data;
    }

    private function languages(): array
    {
        $result = [];
        foreach (glob(app()->getRootPath() . 'app' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $code = strtolower(pathinfo($file, PATHINFO_FILENAME));
            $result[$code] = $code === 'zh-cn' ? '简体中文' : ($code === 'en-us' ? 'English (US)' : $code);
        }
        return $result;
    }

    private function formConfig(array $env): array
    {
        $get = function ($key, $default = '') use ($env) { return isset($env[$key]) ? $env[$key] : $default; };
        $bool = function ($key, $default = false) use ($get) { return filter_var($get($key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN); };
        return [
            'app_debug' => $bool('APP_DEBUG'), 'lang_switch_on' => $bool('LANG.LANG_SWITCH_ON'),
            'default_lang' => $get('LANG.DEFAULT_LANG', 'zh-cn'), 'tts_base_url' => $get('TTS.BASE_URL'),
            'tts_auth_enabled' => $bool('TTS.AUTH_ENABLED'), 'reverse_enabled' => $bool('REVERSE_COLLECT.ENABLED'),
            'collector_base_url' => $get('REVERSE_COLLECT.COLLECTOR_BASE_URL'), 'collector_api_key' => $get('REVERSE_COLLECT.COLLECTOR_API_KEY'),
            'callback_base_url' => $get('REVERSE_COLLECT.CALLBACK_BASE_URL'), 'callback_secret' => $get('REVERSE_COLLECT.CALLBACK_SECRET'),
            'catalog_refresh_seconds' => (int)$get('REVERSE_COLLECT.CATALOG_REFRESH_SECONDS', 21600),
            'chapter_refresh_seconds' => (int)$get('REVERSE_COLLECT.CHAPTER_REFRESH_SECONDS', 86400),
            'dispatch_timeout' => (int)$get('REVERSE_COLLECT.DISPATCH_TIMEOUT', 5), 'max_attempts' => (int)$get('REVERSE_COLLECT.MAX_ATTEMPTS', 5),
        ];
    }

    private function validUrl(string $url): bool
    {
        return (bool)filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function reverseHash(array $data): string
    {
        $keys = ['collector_base_url','collector_api_key','callback_base_url','callback_secret','dispatch_timeout'];
        $values = []; foreach ($keys as $key) $values[$key] = $data[$key];
        return hash('sha256', json_encode($values, JSON_UNESCAPED_SLASHES));
    }

    private function consumeTestToken(string $token, array $data): bool
    {
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) return false;
        $key = 'reverse_collect_test_' . $token;
        $hash = Cache::get($key); Cache::delete($key);
        return is_string($hash) && hash_equals($hash, $this->reverseHash($data));
    }

    private function reverseMatches(array $data, array $current): bool
    {
        $old = [
            'collector_base_url' => rtrim((string)($current['REVERSE_COLLECT.COLLECTOR_BASE_URL'] ?? ''), '/'),
            'collector_api_key' => (string)($current['REVERSE_COLLECT.COLLECTOR_API_KEY'] ?? ''),
            'callback_base_url' => rtrim((string)($current['REVERSE_COLLECT.CALLBACK_BASE_URL'] ?? ''), '/'),
            'callback_secret' => (string)($current['REVERSE_COLLECT.CALLBACK_SECRET'] ?? ''),
            'dispatch_timeout' => (int)($current['REVERSE_COLLECT.DISPATCH_TIMEOUT'] ?? 5),
        ];
        return $this->reverseHash($data) === $this->reverseHash($old);
    }

    private function postJson(string $url, array $payload, int $timeout): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => $timeout,
        ]);
        $raw = curl_exec($ch); $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $data = json_decode((string)$raw, true);
        $message = $error ?: (string)($data['detail'] ?? $data['error'] ?? $data['msg'] ?? ('HTTP ' . $status));
        return [$raw !== false && $status >= 200 && $status < 300, is_array($data) ? $data : [], $message];
    }

    private function ttsErrorMessage(string $body, int $status): string
    {
        $data = json_decode($body, true);
        if (is_array($data)) return (string)($data['detail'] ?? $data['error'] ?? $data['msg'] ?? ('HTTP ' . $status));
        return $body !== '' ? mb_substr(strip_tags($body), 0, 200) : ('HTTP ' . $status);
    }
}
