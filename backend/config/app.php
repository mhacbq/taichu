<?php

return [
    // 应用名称
    'app_name' => '太初命理',
    
    // 应用地址
    'app_host' => env('APP_HOST', ''),
    
    // 应用调试模式
    'app_debug' => env('APP_DEBUG', false),
    
    // 应用Trace
    'app_trace' => env('APP_TRACE', false),
    
    // 应用模式（web / api）
    'app_mode' => 'api',
    
    // 是否支持多模块
    'app_multi_module' => false,
    
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    
    // 默认语言
    'default_lang' => 'zh-cn',
    
    // 默认JSONP格式返回处理
    'var_jsonp_handler' => 'callback',
    
    // 默认JSONP处理方法
    'default_jsonp_handler' => 'jsonpReturn',
    
    // 默认AJAX返回数据格式
    'default_ajax_return' => 'json',
];
