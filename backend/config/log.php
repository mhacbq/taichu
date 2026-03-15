<?php

return [
    // 默认日志记录通道
    'default'      => 'file',
    
    // 日志记录级别
    'level'        => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
    
    // 日志类型记录的通道
    'type_channel' => [
        'error'     => 'error',
        'sql'       => 'sql',
    ],
    
    // 关闭全局日志写入
    'close'        => false,
    
    // 全局日志处理
    'processor'    => [],
    
    // 日志通道列表
    'channels'     => [
        // 文件日志
        'file' => [
            // 日志记录方式
            'type'          => 'File',
            // 日志保存目录
            'path'          => runtime_path() . 'log/',
            // 单文件日志写入
            'single'        => false,
            // 独立日志级别
            'apart_level'   => ['error', 'sql'],
            // 最大日志文件数量
            'max_files'     => 30,
            // 日志输出格式化
            'format'        => '[%s][%s] %s',
            // 时间格式化
            'time_format'   => 'Y-m-d H:i:s',
            // 日志级别
            'level'         => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
        ],
        
        // API请求日志
        'api' => [
            'type'          => 'File',
            'path'          => runtime_path() . 'log/api/',
            'single'        => false,
            'apart_level'   => ['warning', 'error'],
            'max_files'     => 30,
            'format'        => '[%s][%s] %s',
            'time_format'   => 'Y-m-d H:i:s',
            'level'         => ['info', 'warning', 'error'],
        ],
        
        // 业务日志
        'business' => [
            'type'          => 'File',
            'path'          => runtime_path() . 'log/business/',
            'single'        => false,
            'max_files'     => 60,
            'format'        => '[%s][%s] %s',
            'time_format'   => 'Y-m-d H:i:s',
            'level'         => ['info', 'warning', 'error'],
        ],
        
        // 错误日志
        'error' => [
            'type'          => 'File',
            'path'          => runtime_path() . 'log/error/',
            'single'        => false,
            'max_files'     => 90,
            'format'        => '[%s][%s] %s',
            'time_format'   => 'Y-m-d H:i:s',
            'level'         => ['error', 'critical', 'alert', 'emergency'],
        ],
        
        // 安全日志
        'security' => [
            'type'          => 'File',
            'path'          => runtime_path() . 'log/security/',
            'single'        => false,
            'max_files'     => 180,
            'format'        => '[%s][%s] %s',
            'time_format'   => 'Y-m-d H:i:s',
            'level'         => ['info', 'warning', 'error'],
        ],
        
        // 性能日志
        'performance' => [
            'type'          => 'File',
            'path'          => runtime_path() . 'log/performance/',
            'single'        => false,
            'max_files'     => 30,
            'format'        => '[%s][%s] %s',
            'time_format'   => 'Y-m-d H:i:s',
            'level'         => ['info', 'warning'],
        ],
        
        // 积分日志
        'points' => [
            'type'          => 'File',
            'path'          => runtime_path() . 'log/points/',
            'single'        => false,
            'max_files'     => 90,
            'format'        => '[%s][%s] %s',
            'time_format'   => 'Y-m-d H:i:s',
            'level'         => ['info', 'warning', 'error'],
        ],
        
        // 支付日志
        'payment' => [
            'type'          => 'File',
            'path'          => runtime_path() . 'log/payment/',
            'single'        => false,
            'max_files'     => 180,
            'format'        => '[%s][%s] %s',
            'time_format'   => 'Y-m-d H:i:s',
            'level'         => ['info', 'warning', 'error'],
        ],
        
        // 用户行为日志
        'action' => [
            'type'          => 'File',
            'path'          => runtime_path() . 'log/action/',
            'single'        => false,
            'max_files'     => 30,
            'format'        => '[%s][%s] %s',
            'time_format'   => 'Y-m-d H:i:s',
            'level'         => ['info'],
        ],
        
        // SQL日志
        'sql' => [
            'type'          => 'File',
            'path'          => runtime_path() . 'log/sql/',
            'single'        => false,
            'max_files'     => 30,
            'format'        => '[%s][%s] %s',
            'time_format'   => 'Y-m-d H:i:s',
            'level'         => ['sql'],
        ],
    ],
];