<?php

declare(strict_types=1);

// 应用入口文件

// 定义应用目录
define('APP_PATH', __DIR__ . '/../app/');

// 定义运行目录
define('RUNTIME_PATH', __DIR__ . '/../runtime/');

// 加载框架引导文件
require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new \think\App())->http;

$response = $http->run();

$response->send();

$http->end($response);
