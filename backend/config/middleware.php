<?php

// 中间件配置文件
// 全局中间件
return [
    // 跨域处理
    \app\middleware\Cors::class,

    // 响应标准化
    \app\middleware\ApiResponse::class,

    // 敏感数据过滤
    \app\middleware\SensitiveDataFilter::class,

    // 限流
    \app\middleware\RateLimit::class,
];

// 路由组中间件
return [
    'auth' => \app\middleware\JwtAuth::class,
    'admin' => \app\middleware\AdminAuth::class,
];
