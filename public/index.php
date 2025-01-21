<?php
// [ 应用入口文件 ]
namespace think;

if (empty(file_exists(__DIR__ . '/../vendor/autoload.php'))) {
    echo '您还未安装PHP依赖包，请输入命令安装：composer install。';
    exit;
}
require __DIR__ . '/../vendor/autoload.php';

// 定义Layui版本号
define('LAYUI_VERSION','2.9.13');

// 定义项目目录
define('CMS_ROOT', __DIR__ . '/../');

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
