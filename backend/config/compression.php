<?php

/**
 * 内容压缩中间件配置
 * 支持Gzip和Brotli压缩
 */

return [
    // 是否启用压缩
    'enabled' => env('COMPRESSION_ENABLED', true),

    // 压缩算法（按优先级）
    'algorithms' => [
        'br' => 'Brotli',    // Brotli压缩（推荐，压缩率更高）
        'gzip' => 'Gzip',    // Gzip压缩（兼容性更好）
    ],

    // 最小压缩大小（字节）
    'min_length' => 1024,

    // 压缩级别（1-9，数字越大压缩率越高但CPU消耗越大）
    'level' => [
        'br' => 4,
        'gzip' => 6,
    ],

    // 不压缩的MIME类型
    'excluded_types' => [
        'image/*',
        'video/*',
        'audio/*',
        'application/zip',
        'application/x-gzip',
        'application/x-brotli',
    ],

    // 不压缩的文件扩展名
    'excluded_extensions' => [
        '.jpg', '.jpeg', '.png', '.gif', '.webp',
        '.mp4', '.webm', '.ogg',
        '.mp3', '.wav',
        '.zip', '.rar', '.7z',
    ],
];
