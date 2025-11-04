<?php

namespace http;

/**
 * HTTP页面抓取扩展类
 * 支持GET、POST、PUT、DELETE等请求类型，支持代理和防封措施
 */

class HttpFetcher
{
    // 默认配置
    private $config = [
        'timeout' => 30,                 // 请求超时时间（秒）
        'connect_timeout' => 10,         // 连接超时时间（秒）
        'retry_times' => 3,              // 失败重试次数
        'retry_delay' => 1,              // 重试间隔（秒）
        'user_agent_pool' => [],         // User-Agent池
        'random_delay' => [0, 2],        // 随机延迟范围（秒）
        'log_file' => '',                // 日志文件路径
        'verify_ssl' => false,           // 是否验证SSL证书
        'ssl_cert' => '',                // SSL证书路径
        'ssl_key' => ''                  // SSL密钥路径
    ];

    // 初始化配置
    public function __construct($config = [])
    {
        // 合并配置
        $this->config = array_merge($this->config, $config);

        // 设置默认User-Agent池
        if (empty($this->config['user_agent_pool'])) {
            $this->config['user_agent_pool'] = [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Firefox/119.0',
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/119.0',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ];
        }
    }

    /**
     * 抓取网页内容
     * @param string $url 目标URL
     * @param array $options 请求选项
     * @return array [success => bool, content => string, error => string, info => array]
     */
    public function fetch($url, $options = [])
    {
        try {
            // 验证URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \Exception('Invalid URL: ' . $url);
            }

            // 合并选项
            $options = array_merge([
                'method' => 'GET',
                'params' => [],
                'headers' => [],
                'proxy' => '',
                'proxy_auth' => '',
                'data' => null,
                'json' => false,
                'cookies' => []
            ], $options);

            // 执行随机延迟
            $this->randomDelay();

            // 重试机制
            $retryCount = 0;
            $maxRetries = isset($options['retry_times']) ? $options['retry_times'] : $this->config['retry_times'];
            $retryDelay = isset($options['retry_delay']) ? $options['retry_delay'] : $this->config['retry_delay'];

            while ($retryCount <= $maxRetries) {
                try {
                    $result = $this->executeRequest($url, $options);
                    if ($result['success']) {
                        return $result;
                    }
                } catch (\Exception $e) {
                    $error = $e->getMessage();
                }

                $retryCount++;
                if ($retryCount <= $maxRetries) {
                    $this->log('Retry ' . $retryCount . ' for URL: ' . $url . ' after error: ' . $error);
                    sleep($retryDelay);
                }
            }

            throw new \Exception('Maximum retries (' . $maxRetries . ') exceeded: ' . $error);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            $this->log('Error fetching URL: ' . $url . ' - ' . $error);
            return [
                'success' => false,
                'data' => '',
                'error' => $error,
                'info' => []
            ];
        }
    }

    /**
     * 执行HTTP请求
     * @param string $url 目标URL
     * @param array $options 请求选项
     * @return array 响应结果
     */
    private function executeRequest($url, $options)
    {
        // 初始化curl
        $ch = curl_init();
        if (!$ch) {
            throw new \Exception('Failed to initialize cURL');
        }

        try {
            // 设置基础选项
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, isset($options['timeout']) ? $options['timeout'] : $this->config['timeout']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, isset($options['connect_timeout']) ? $options['connect_timeout'] : $this->config['connect_timeout']);

            // SSL设置
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, isset($options['verify_ssl']) ? $options['verify_ssl'] : $this->config['verify_ssl']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, isset($options['verify_ssl']) && $options['verify_ssl'] ? 2 : 0);

            if (isset($options['ssl_cert']) && $options['ssl_cert']) {
                curl_setopt($ch, CURLOPT_SSLCERT, $options['ssl_cert']);
            } elseif ($this->config['ssl_cert']) {
                curl_setopt($ch, CURLOPT_SSLCERT, $this->config['ssl_cert']);
            }

            if (isset($options['ssl_key']) && $options['ssl_key']) {
                curl_setopt($ch, CURLOPT_SSLKEY, $options['ssl_key']);
            } elseif ($this->config['ssl_key']) {
                curl_setopt($ch, CURLOPT_SSLKEY, $this->config['ssl_key']);
            }

            // 设置请求方法
            $method = strtoupper($options['method']);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            // 设置请求头
            $headers = $this->prepareHeaders($options['headers']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // 设置代理
            if ($options['proxy']) {
                curl_setopt($ch, CURLOPT_PROXY, $options['proxy']);
                if ($options['proxy_auth']) {
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $options['proxy_auth']);
                }
            }

            // 处理查询参数（GET请求）
            if ($method === 'GET' && !empty($options['params'])) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($options['params']);
                curl_setopt($ch, CURLOPT_URL, $url);
            }

            // 处理请求体（POST, PUT, DELETE等）
            if ($method !== 'GET' && $method !== 'HEAD' && isset($options['data'])) {
                if ($options['json'] && is_array($options['data'])) {
                    $data = json_encode($options['data'], JSON_UNESCAPED_UNICODE);
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                } else {
                    //如果是上传文件
                    if (isset($options['data']['file']) && $options['data']['file']) {
                        $fileData = $options['data']['file'];
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $fileData);
                        finfo_close($finfo);
                        unset($options['data']['file']);
                        if (class_exists('\CURLFile')) {
                            $data = array('file' => new \CURLFile($fileData, $mime_type, basename($fileData)), 'data' => is_array($options['data']) ? http_build_query($options['data']) : $options['data']);
                        } else {
                            $data = array(
                                'file' => '@' . $fileData . ";type=" . $mime_type . ";filename=" . basename($fileData),
                                'data' => is_array($options['data']) ? http_build_query($options['data']) : $options['data'],
                            );
                        }
                    } else {
                        $data = is_array($options['data']) ? http_build_query($options['data']) : $options['data'];
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }

            // 处理Cookie
            if (!empty($options['cookies'])) {
                $cookieString = '';
                foreach ($options['cookies'] as $name => $value) {
                    $cookieString .= $name . '=' . urlencode($value) . '; ';
                }
                curl_setopt($ch, CURLOPT_COOKIE, rtrim($cookieString, '; '));
            }

            // 获取响应头
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, false);

            // 执行请求
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);

            if ($response === false) {
                throw new \Exception('cURL error: ' . curl_error($ch) . ' (Code: ' . curl_errno($ch) . ')');
            }

            // 分离响应头和响应体
            $headerSize = $info['header_size'];
            $headers = substr($response, 0, $headerSize);
            $body = substr($response, $headerSize);

            // 检查HTTP状态码
            $statusCode = $info['http_code'];
            if ($statusCode >= 400) {
                throw new \Exception('HTTP error: ' . $statusCode);
            }

            return [
                'success' => true,
                'data' => $body,
                'error' => '',
                'info' => [
                    'http_code' => $statusCode,
                    'headers' => $this->parseHeaders($headers),
                    'total_time' => $info['total_time'],
                    'url' => $info['url']
                ]
            ];
        } finally {
            curl_close($ch);
        }
    }

    /**
     * 准备请求头
     * @param array $customHeaders 自定义请求头
     * @return array 完整请求头数组
     */
    private function prepareHeaders($customHeaders = [])
    {
        $headers = [];

        // 添加随机User-Agent
        $userAgents = isset($customHeaders['User-Agent']) ? [$customHeaders['User-Agent']] : $this->config['user_agent_pool'];
        $headers[] = 'User-Agent: ' . $userAgents[array_rand($userAgents)];
        unset($customHeaders['User-Agent']);

        // 添加默认请求头
        $defaultHeaders = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Cache-Control: no-cache'
        ];

        $headers = array_merge($headers, $defaultHeaders);

        // 添加自定义请求头
        foreach ($customHeaders as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        return $headers;
    }

    /**
     * 解析响应头
     * @param string $rawHeaders 原始响应头
     * @return array 解析后的响应头
     */
    private function parseHeaders($rawHeaders)
    {
        $headers = [];
        $lines = explode("\r\n", $rawHeaders);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (strpos($line, ': ') !== false) {
                list($key, $value) = explode(': ', $line, 2);
                $headers[strtolower($key)] = $value;
            }
        }

        return $headers;
    }

    /**
     * 执行随机延迟
     */
    private function randomDelay()
    {
        $min = $this->config['random_delay'][0] * 1000000; // 转换为微秒
        $max = $this->config['random_delay'][1] * 1000000;

        if ($min >= 0 && $max > $min) {
            // 使用整数运算生成随机延迟
            $delay = mt_rand($min, $max);
            usleep($delay);
        }
    }

    /**
     * 记录日志
     * @param string $message 日志消息
     */
    private function log($message)
    {
        if (empty($this->config['log_file'])) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";

        try {
            error_log($logMessage, 3, $this->config['log_file']);
        } catch (\Exception $e) {
            // 日志写入失败不影响程序运行
        }
    }

    /**
     * GET请求快捷方法
     * @param string $url 目标URL
     * @param array $params 查询参数
     * @param array $options 其他选项
     * @return array 响应结果
     */
    public function get($url, $params = [], $options = [])
    {
        $options['method'] = 'GET';
        $options['params'] = $params;
        return $this->fetch($url, $options);
    }

    /**
     * POST请求快捷方法
     * @param string $url 目标URL
     * @param mixed $data POST数据
     * @param array $options 其他选项
     * @return array 响应结果
     */
    public function post($url, $data = [], $options = [])
    {
        $options['method'] = 'POST';
        $options['data'] = $data;
        return $this->fetch($url, $options);
    }

    /**
     * JSON POST请求快捷方法
     * @param string $url 目标URL
     * @param array $data POST数据
     * @param array $options 其他选项
     * @return array 响应结果
     */
    public function postJson($url, $data = [], $options = [])
    {
        $options['method'] = 'POST';
        $options['data'] = $data;
        $options['json'] = true;
        return $this->fetch($url, $options);
    }

    /**
     * PUT请求快捷方法
     * @param string $url 目标URL
     * @param mixed $data PUT数据
     * @param array $options 其他选项
     * @return array 响应结果
     */
    public function put($url, $data = [], $options = [])
    {
        $options['method'] = 'PUT';
        $options['data'] = $data;
        return $this->fetch($url, $options);
    }

    /**
     * DELETE请求快捷方法
     * @param string $url 目标URL
     * @param array $options 选项
     * @return array 响应结果
     */
    public function delete($url, $options = [])
    {
        $options['method'] = 'DELETE';
        return $this->fetch($url, $options);
    }

    /**
     * HEAD请求快捷方法
     * @param string $url 目标URL
     * @param array $options 选项
     * @return array 响应结果
     */
    public function head($url, $options = [])
    {
        $options['method'] = 'HEAD';
        return $this->fetch($url, $options);
    }

    /**
     * 设置代理
     * @param string $proxy 代理地址
     * @param string $auth 代理认证信息
     * @return $this
     */
    public function setProxy($proxy, $auth = '')
    {
        $this->config['proxy'] = $proxy;
        $this->config['proxy_auth'] = $auth;
        return $this;
    }

    /**
     * 设置配置
     * @param string|array $key 配置键名或数组
     * @param mixed $value 配置值
     * @return $this
     */
    public function setConfig($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->config[$k] = $v;
            }
        } else {
            $this->config[$key] = $value;
        }
        return $this;
    }

    /**
     * 获取配置
     * @param string|null $key 配置键名
     * @return mixed 配置值
     */
    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }
}
