<?php

declare(strict_types=1);

// 应用入口文件

// 安全读取请求方法，兼容 CLI 等无 REQUEST_METHOD 的场景
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';

// 读取可用跨域来源，优先使用环境变量（与应用中间件保持一致）
$allowedOrigins = getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:3000,http://localhost:5173,http://localhost:8080,http://127.0.0.1:3000,http://127.0.0.1:5173,http://127.0.0.1:8080';
$allowedOriginList = array_map('trim', explode(',', $allowedOrigins));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '' && in_array($origin, $allowedOriginList, true)) {
    // 为所有响应提前写入CORS头，避免异常/重定向场景丢失跨域头
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, X-Token, Accept, Origin');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

// 处理 CORS 预检请求（在框架加载前处理，确保跨域正常）
if ($requestMethod === 'OPTIONS') {
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
