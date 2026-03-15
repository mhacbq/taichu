<?php

// 生成强随机密钥的命令: openssl rand -base64 32
$secret = env('JWT_SECRET');

// 生产环境检查
if (empty($secret) || $secret === 'your-secret-key-here-change-in-production') {
    if (env('APP_DEBUG') === 'false') {
        throw new \Exception('JWT_SECRET未配置或使用了默认值，请在.env中设置安全的密钥');
    }
    // 调试模式下使用临时密钥（仅用于开发）
    $secret = bin2hex(random_bytes(32));
}

return [
    // JWT密钥 - 必须从环境变量读取
    'secret' => $secret,
    
    // Token有效期（秒）- 7天
    'ttl' => 604800,
    
    // 刷新时间（秒）- 1天
    'refresh_ttl' => 86400,
    
    // 签发者
    'issuer' => env('JWT_ISSUER', 'taichu-api'),
    
    // 受众
    'audience' => env('JWT_AUDIENCE', 'taichu-app'),
];
