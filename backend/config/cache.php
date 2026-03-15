<?php

return [
    // 默认缓存驱动
    'default' => env('CACHE_DRIVER', 'file'),
    
    // 缓存连接配置
    'stores' => [
        'file' => [
            // 驱动方式
            'type' => 'File',
            // 缓存保存目录
            'path' => runtime_path() . 'cache' . DIRECTORY_SEPARATOR,
            // 缓存前缀
            'prefix' => '',
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize' => [],
        ],
        'redis' => [
            // 驱动方式
            'type' => 'Redis',
            // 服务器地址
            'host' => env('REDIS_HOST', '127.0.0.1'),
            // 端口
            'port' => env('REDIS_PORT', 6379),
            // 密码
            'password' => env('REDIS_PASSWORD', ''),
            // 缓存前缀
            'prefix' => 'taichu:',
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
        ],
    ],
];
