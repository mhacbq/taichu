<?php

return [
    // JWT密钥
    'secret' => env('JWT_SECRET', 'your-secret-key-here-change-in-production'),
    
    // Token有效期（秒）- 7天
    'ttl' => 604800,
    
    // 刷新时间（秒）- 1天
    'refresh_ttl' => 86400,
    
    // 签发者
    'issuer' => 'taichu-api',
    
    // 受众
    'audience' => 'taichu-app',
];
