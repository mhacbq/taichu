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
    Route::get('config', 'admin.Ai/getConfig');
    Route::post('config', 'admin.Ai/saveConfig');
    Route::post('test', 'admin.Ai/testConnection');
    Route::get('prompts/list', 'admin.AiPrompt/getList');
    Route::get('prompts/detail/:id', 'admin.AiPrompt/getDetail');
    Route::post('prompts/save', 'admin.AiPrompt/save');
    Route::delete('prompts/:id', 'admin.AiPrompt/delete');
    Route::post('prompts/:id/default', 'admin.AiPrompt/setDefault');
    Route::post('prompts/:id/preview', 'admin.AiPrompt/preview');
    Route::post('prompts/:id/duplicate', 'admin.AiPrompt/duplicate');
    Route::get('prompts/types', 'admin.AiPrompt/getTypes');
})->middleware([\app\middleware\AdminAuth::class, \app\middleware\RateLimit::class]);

