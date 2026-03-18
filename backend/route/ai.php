<?php
use think\facade\Route;

// AI解盘路由
Route::group('api/ai', function () {
    // 非流式解盘
    Route::post('analyze/bazi', 'AiAnalysis/analyzeBazi');
    // 流式解盘（SSE）
    Route::post('analyze/bazi/stream', 'AiAnalysis/analyzeBaziStream');
});

// 后台AI配置管理
Route::group('api/admin/ai', function () {
    Route::get('config', 'AiAnalysis/getConfig');
    Route::post('config', 'AiAnalysis/saveConfig');
    Route::post('test', 'AiAnalysis/testConnection');
})->middleware([\app\middleware\AdminAuth::class, \app\middleware\RateLimit::class]);

