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
        Route::get('invite-leaderboard', 'Auth/inviteLeaderboard');
        Route::get('my-invites', 'Auth/myInvites');
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
    
    // 运势分析（积分消耗功能）
    Route::group('fortune', function () {
        Route::get('points-cost', 'Fortune/getYearlyFortunePoints');
        Route::post('yearly', 'Fortune/yearlyFortune');
        Route::get('yearly-trend', 'Fortune/yearlyTrend');
        Route::post('dayun-analysis', 'Fortune/dayunAnalysis');
        Route::post('dayun-chart', 'Fortune/dayunChart');
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
    
    // 积分商城
    Route::group('shop', function () {
        Route::get('home', 'PointsShop/home');
        Route::get('products', 'PointsShop/products');
        Route::get('product-detail', 'PointsShop/productDetail');
        Route::post('exchange', 'PointsShop/exchange');
        Route::get('my-exchanges', 'PointsShop/myExchanges');
        Route::get('exchange-detail', 'PointsShop/exchangeDetail');
        Route::post('fill-address', 'PointsShop/fillAddress');
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
    
    // 系统配置（公开接口）
    Route::group('config', function () {
        Route::get('client', 'Config/clientConfig');
        Route::get('features', 'Config/featureSwitches');
    });
    
    // AI分析
    Route::group('ai', function () {
        Route::post('analyze', 'AiAnalysis/analyze');
        Route::post('analyze-stream', 'AiAnalysis/analyzeStream');
        Route::get('history', 'AiAnalysis/history');
    });
    
    // VIP会员
    Route::group('vip', function () {
        Route::get('info', 'Vip/info');
        Route::get('benefits', 'Vip/benefits');
        Route::post('subscribe', 'Vip/subscribe');
        Route::get('orders', 'Vip/orders');
    });
    
    // 积分任务
    Route::group('tasks', function () {
        Route::get('list', 'Task/list');
        Route::post('complete', 'Task/complete');
        Route::post('checkin', 'Task/checkin');
        Route::get('checkin-status', 'Task/checkinStatus');
        Route::get('stats', 'Task/stats');
    });
    
    // 分享与邀请
    Route::group('share', function () {
        Route::post('generate-poster', 'Share/generatePoster');
        Route::post('record', 'Share/recordShare');
        Route::get('invite-info', 'Share/inviteInfo');
    });
    
    // 八字合婚
    Route::group('hehun', function () {
        Route::get('pricing', 'Hehun/getPricing');      // 获取定价配置
        Route::post('calculate', 'Hehun/calculate');     // 合婚计算
        Route::get('history', 'Hehun/history');          // 合婚历史
        Route::post('export', 'Hehun/export');           // 导出报告
    });
    
    // 取名建议
    Route::group('qiming', function () {
        Route::post('suggest', 'Qiming/suggest');
        Route::get('history', 'Qiming/history');
    });
    
    // 吉日查询
    Route::group('jiri', function () {
        Route::post('query', 'Jiri/query');
    });
    
})->middleware([
    \app\middleware\HttpsEnforce::class,
    \app\middleware\Cors::class,
    \app\middleware\SensitiveDataFilter::class,
    \app\middleware\RateLimit::class,
]);

// 支付回调（不需要认证）
Route::post('api/payment/notify', 'Payment/notify');
Route::post('api/alipay/notify', 'Alipay/notify');
Route::get('api/alipay/return', 'Alipay/return');

// 后台管理路由
Route::group('api/admin', function () {
    // 仪表盘
    Route::group('dashboard', function () {
        Route::get('statistics', 'admin.Dashboard/statistics');
        Route::get('trend', 'admin.Dashboard/trend');
        Route::get('realtime', 'admin.Dashboard/realtime');
        Route::get('chart/:type', 'admin.Dashboard/chart');
        Route::get('pending-feedback', 'admin.Dashboard/pendingFeedback');
    });
    
    // 配置管理
    Route::group('config', function () {
        Route::get('', 'admin.Config/index');
        Route::get('features', 'admin.Config/features');
        Route::post('update', 'admin.Config/update');
        Route::post('update-batch', 'admin.Config/updateBatch');
        Route::post('update-feature', 'admin.Config/updateFeature');
        Route::post('update-features', 'admin.Config/updateFeatures');
        Route::get('vip', 'admin.Config/vip');
        Route::post('update-vip', 'admin.Config/updateVip');
        Route::get('points', 'admin.Config/points');
        Route::post('update-points', 'admin.Config/updatePoints');
        Route::get('marketing', 'admin.Config/marketing');
        Route::post('update-marketing', 'admin.Config/updateMarketing');
        Route::post('refresh-cache', 'admin.Config/refreshCache');
    });
    
})->middleware([
    \app\middleware\HttpsEnforce::class,
    \app\middleware\Cors::class,
    \app\middleware\Auth::class,  // 需要管理员认证
]);

// 404处理
Route::miss(function() {
    return json([
        'code' => 404,
        'message' => '接口不存在',
        'data' => null,
    ], 404);
});
