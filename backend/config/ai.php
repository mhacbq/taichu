<?php
/**
 * AI解盘配置
 */
return [
    // API配置
    'api_key' => env('AI_API_KEY', ''),
    'api_url' => env('AI_API_URL', 'https://aiping.cn/api/v1/chat/completions'),
    'model' => env('AI_MODEL', 'DeepSeek-V3.2'),
    
    // 功能开关
    'enable_bazi_analysis' => env('AI_ENABLE_BAZI', true),
    'enable_tarot_analysis' => env('AI_ENABLE_TAROT', true),
    
    // 流式输出
    'enable_streaming' => env('AI_ENABLE_STREAMING', true),
    'enable_thinking' => env('AI_ENABLE_THINKING', false),
    
    // 请求限制
    'max_tokens' => (int) env('AI_MAX_TOKENS', 4096),
    'timeout' => (int) env('AI_TIMEOUT', 60),
    
    // 积分消耗
    'cost_points' => (int) env('AI_COST_POINTS', 30),
];
