<?php
use think\facade\Route;

// AI提示词管理路由（后台）
Route::group('api/admin/ai-prompts', function () {
    Route::get('list', 'AiPrompt/getList');
    Route::get('detail/:id', 'AiPrompt/getDetail');
    Route::post('save', 'AiPrompt/save');
    Route::delete(':id', 'AiPrompt/delete');
    Route::post(':id/default', 'AiPrompt/setDefault');
    Route::post(':id/preview', 'AiPrompt/preview');
    Route::post(':id/duplicate', 'AiPrompt/duplicate');
    Route::get('types', 'AiPrompt/getTypes');
})->middleware(\app\middleware\Auth::class);

// 前台获取提示词（用于AI解盘）
Route::group('api/ai-prompts', function () {
    Route::get('default/:type', 'AiPrompt/getDefaultPrompt');
});
