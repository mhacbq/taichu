<?php

// 生成强随机密钥的命令: openssl rand -base64 32
$secret = env('JWT_SECRET');

// 强制检查JWT_SECRET配置
if (empty($secret)) {
    throw new \Exception('JWT_SECRET must be configured in .env file. Generate one with: openssl rand -base64 32');
}

// 密钥最小长度检查（至少256位）
if (strlen($secret) < 32) {
    throw new \Exception('JWT_SECRET must be at least 32 characters long for security');
}

// 生产环境强制检查不能使用默认值
$defaultSecrets = [
    'your-secret-key-here-change-in-production',
    'change-this-secret-key',
    'secret',
    'jwt-secret',
    '123456',
    'password',
];

if (in_array($secret, $defaultSecrets, true)) {
    throw new \Exception('JWT_SECRET cannot use default/demo value in any environment');
}

// 生产环境额外检查
if (env('APP_ENV') === 'production') {
    // 检查密钥复杂度（至少包含大小写字母、数字、特殊字符中的3种）
    $hasLower = preg_match('/[a-z]/', $secret);
    $hasUpper = preg_match('/[A-Z]/', $secret);
    $hasDigit = preg_match('/[0-9]/', $secret);
    $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $secret);
    
    $complexity = ($hasLower ? 1 : 0) + ($hasUpper ? 1 : 0) + ($hasDigit ? 1 : 0) + ($hasSpecial ? 1 : 0);
    
    if ($complexity < 3) {
        throw new \Exception('JWT_SECRET in production must contain at least 3 of: lowercase, uppercase, digits, special characters');
    }
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
