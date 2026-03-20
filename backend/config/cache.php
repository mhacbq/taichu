<?php

// Redis缓存配置
return [
    // 默认缓存驱动
    'default' => env('CACHE_DRIVER', 'redis'),

    // 缓存存储配置
    'stores' => [
        // Redis缓存
        'redis' => [
            'type' => 'redis',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', ''),
            'select' => env('REDIS_DB', 0),
            'timeout' => 0,
            'expire' => 0,
            'persistent' => false,
            'prefix' => 'taichu:',
            'tag_prefix' => 'tag:',
            'serialize' => ['think\cache\driver\Serialize', 'serialize'],
            'options' => [
                \Redis::OPT_CONNECT_TIMEOUT => 2,
                \Redis::OPT_READ_TIMEOUT => 2,
                \Redis::OPT_SERIALIZER => \Redis::SERIALIZER_PHP,
            ],
        ],

        // 文件缓存（开发环境）
        'file' => [
            'type' => 'file',
            'path' => runtime_path() . 'cache' . DIRECTORY_SEPARATOR,
            'prefix' => '',
            'expire' => 0,
        ],
    ],

    // 缓存键前缀
    'prefix' => 'taichu:',
];
