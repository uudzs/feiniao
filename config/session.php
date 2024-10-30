<?php
// +----------------------------------------------------------------------
// | 会话设置
// +----------------------------------------------------------------------

return [
    // session name
    'name'           => 'PHPSESSID',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // session save handler, support file|cookie|database|redis
    'type'           => 'file',
    // session gc max lifetime
    'auto_start'     => false,
    // session cookie parameters
    'cookie'         => ['prefix' => '', 'expire' => 0],
    // session save path, support file|cookie|database|redis
    'store'          => 'file',
    // session db options
    'db'             => '1',
    // session handler config
    'handler'        => null,
    // session cookie domain
    'domain'         => '',
    // session cookie secure
    'secure'         => false,
    // session expire time
    'expire'         => 86400,
    // session prefix
    'prefix'         => '',
];
