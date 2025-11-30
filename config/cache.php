<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------
// | 缓存配置文件 - 支持动态配置管理
// | 支持的缓存类型：file、redis、memcache、memcached、sqlite、wincache
// +----------------------------------------------------------------------
// | 最后更新时间：2025-11-21 11:21:13
// +----------------------------------------------------------------------

return [
    // 默认缓存驱动
    'default' => 'file',

    // 缓存连接方式配置
    'stores'  => [
        // 文件缓存
        'file' => [
            'type'        => 'file',
            'path'        => '',
            'prefix'      => '',
            'expire'      => 0,
            'tag_prefix'  => 'tag:',
            'serialize'   => [],
        ],

        // Redis缓存
        'redis' => [
            'type'        => 'redis',
            'host'        => '127.0.0.1',
            'port'        => 6379,
            'password'    => '',
            'select'      => 8,
            'timeout'     => 0,
            'expire'      => 0,
            'persistent'  => false,
            'prefix'      => 'feiniao_',
            'tag_prefix'  => 'tag:',
            'serialize'   => [],
        ],

        // Memcache缓存
        'memcache' => [
            'type'        => 'memcache',
            'host'        => '127.0.0.1',
            'port'        => 11211,
            'timeout'     => 1,
            'expire'      => 0,
            'prefix'      => '',
            'tag_prefix'  => 'tag:',
            'serialize'   => [],
        ],

        // Memcached缓存
        'memcached' => [
            'type'        => 'memcached',
            'host'        => '127.0.0.1',
            'port'        => 11211,
            'username'    => '',
            'password'    => '',
            'expire'      => 0,
            'timeout'     => 1,
            'prefix'      => '',
            'tag_prefix'  => 'tag:',
        ],

        // WinCache（Windows系统专用）缓存
        'wincache' => [
            'type'        => 'wincache',
            'prefix'      => '',
            'expire'      => 0,
            'tag_prefix'  => 'tag:',
        ],
    ],
];
