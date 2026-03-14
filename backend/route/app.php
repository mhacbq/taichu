<?php

use think\facade\Route;

// CORS预检
Route::options('api/:any', function() {
    return response('', 204);
});

// API路由组
Route::group('api', function () {
    
    // 健康检查
    Route::get('health', function() {
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'status' => 'ok',
                'time' => date('Y-m-d H:i:s'),
            ],
        ]);
    });
    
    // 认证相关
    Route::group('auth', function () {
        Route::post('login', 'Auth/login');
        Route::get('userinfo', 'Auth/userinfo');
        Route::put('profile', 'Auth/updateProfile');
    });
    
    // 排盘相关
    Route::group('paipan', function () {
        Route::post('bazi', 'Paipan/bazi');
        Route::get('history', 'Paipan/history');
    });
    
    // 塔罗相关
    Route::group('tarot', function () {
        Route::post('draw', 'Tarot/draw');
        Route::post('interpret', 'Tarot/interpret');
    });
    
    // 每日运势
    Route::group('daily', function () {
        Route::get('fortune', 'Daily/fortune');
        Route::get('luck', 'Daily/luck');
    });
    
    // 积分系统
    Route::group('points', function () {
        Route::get('balance', 'Points/balance');
        Route::get('history', 'Points/history');
        Route::post('consume', 'Points/consume');
        Route::post('recharge', 'Points/recharge');
    });
    
    // 用户反馈
    Route::group('feedback', function () {
        Route::post('submit', 'Feedback/submit');
        Route::get('my-list', 'Feedback/myList');
    });
    
})->middleware([\app\middleware\Cors::class]);

// 404处理
Route::miss(function() {
    return json([
        'code' => 404,
        'message' => '接口不存在',
        'data' => null,
    ], 404);
});
