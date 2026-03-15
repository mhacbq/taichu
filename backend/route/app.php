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
                'version' => '1.0.0',
            ],
        ]);
    });
    
    // 统计数据（公开）
    Route::group('stats', function () {
        Route::get('', 'Stats/index');
        Route::get('home', 'Stats/home');
    });
    
    // 认证相关
    Route::group('auth', function () {
        Route::post('login', 'Auth/login');
        Route::post('phone-login', 'Auth/phoneLogin');
        Route::post('phone-register', 'Auth/phoneRegister');
        Route::get('userinfo', 'Auth/userinfo');
        Route::put('profile', 'Auth/updateProfile');
    });
    
    // 短信相关
    Route::group('sms', function () {
        Route::post('send-code', 'Sms/sendCode');
        Route::post('verify-code', 'Sms/verifyCode');
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
        Route::post('checkin', 'Daily/checkin');
        Route::get('checkin-status', 'Daily/checkinStatus');
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
    
    // 支付相关
    Route::group('payment', function () {
        Route::get('options', 'Payment/getRechargeOptions');
        Route::post('create-order', 'Payment/createOrder');
        Route::get('query-order', 'Payment/queryOrder');
        Route::get('history', 'Payment/getUserRechargeHistory');
    });
    
})->middleware([
    \app\middleware\Cors::class,
    \app\middleware\RateLimit::class,
]);

// 支付回调（不需要认证）
Route::post('api/payment/notify', 'Payment/notify');

// 404处理
Route::miss(function() {
    return json([
        'code' => 404,
        'message' => '接口不存在',
        'data' => null,
    ], 404);
});
