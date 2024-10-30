<?php

use think\facade\Route;

return [
    'debug' => true,
    'app_id'  => 'wx04cb2c041d07ddd5',         // AppID
    'secret'  => '2ddf2ebf5c569d8561c20bcd2a9ffdb1',     // AppSecret
    'token'   => '',          // Token
    'aes_key' => '',                    // EncodingAESKey，兼容与安全模式下请一定要填写！！
    'payment' => [
        'mch_id' => '',
        'key'                => 'key-for-signature',
        'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
        'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
        'notify_url'         => '默认的订单回调地址',     // 你也可以在下单时单独设置来想覆盖它
    ],
    // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
    'response_type' => 'array',
    /**
     * OAuth 配置
     * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
     * callback：OAuth授权完成后的回调页地址
     */
    'oauth' => [
        'scopes'   => ['snsapi_userinfo'],
        'callback' =>  (string) Route::buildUrl('wechat_oauth_callback')->domain(true),
    ],
    /**
     * 日志配置
     *
     * level: 日志级别, 可选为：
     *         debug/info/notice/warning/error/critical/alert/emergency
     * path：日志文件位置(绝对路径!!!)，要求可写权限
     */
    'log' => [
        'default' => 'dev', // 默认使用的 channel，生产环境可以改为下面的 prod
        'channels' => [
            // 测试环境
            'dev' => [
                'driver' => 'single',
                'path' => app()->getRootPath() . '/runtime/log/easywechat.log',
                'level' => 'debug',
            ],
            // 生产环境
            'prod' => [
                'driver' => 'daily',
                'path' =>  app()->getRootPath() . '/runtime/log/easywechat.log',
                'level' => 'info',
            ],
        ],
    ],
    /**
     * 接口请求相关配置，超时时间等，具体可用参数请参考：
     * http://docs.guzzlephp.org/en/stable/request-config.html
     *
     * - retries: 重试次数，默认 1，指定当 http 请求失败时重试的次数。
     * - retry_delay: 重试延迟间隔（单位：ms），默认 500
     * - log_template: 指定 HTTP 日志模板，请参考：https://github.com/guzzle/guzzle/blob/master/src/MessageFormatter.php
     */
    'http' => [
        'max_retries' => 1,
        'retry_delay' => 500,
        'timeout' => 5.0,
    ],
];
