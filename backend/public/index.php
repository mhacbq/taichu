<?php

declare(strict_types=1);

// 应用入口文件

// 安全读取请求方法，兼容 CLI 等无 REQUEST_METHOD 的场景
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';

// 处理 CORS 预检请求（在框架加载前处理，确保跨域正常）
if ($requestMethod === 'OPTIONS') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, X-Token, Accept, Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
    http_response_code(204);
    exit;
}

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
