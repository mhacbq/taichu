<?php

use think\facade\Route;

// 后台管理API路由组
Route::group('api/admin', function () {
    // 认证相关（无需登录）
    Route::post('auth/login', 'admin.Auth/login');
    
    // 需要登录验证的路由组
    Route::group('', function () {
        // 认证
        Route::get('auth/info', 'admin.Auth/info');
        Route::post('auth/logout', 'admin.Auth/logout');
        
        // 仪表盘
        Route::get('dashboard/statistics', 'admin.Dashboard/index');
        Route::get('dashboard/trend', 'admin.Dashboard/trend');
        Route::post('dashboard/refresh-stats', 'admin.Dashboard/updateStats');
        Route::get('dashboard/chart/:type', 'Admin/chartData');

        Route::get('dashboard/realtime', 'Admin/realtime');
        Route::get('dashboard/export-realtime', 'Admin/exportRealtime');
        Route::get('dashboard/pending-feedback', 'admin.Feedback/pendingSummary');
        
        // 用户管理
        Route::get('users', 'admin.User/index');
        Route::get('users/:id', 'admin.User/detail');
        Route::put('users/:id/status', 'admin.User/toggleStatus');
        Route::put('users/batch-status', 'admin.User/batchUpdateStatus');
        Route::get('users/behavior', 'Admin/userBehavior');
        Route::get('users/export', 'Admin/exportUsers');
        
        // 内容管理
        Route::get('content/bazi', 'Admin/baziRecords');
        Route::get('content/bazi/:id', 'Admin/baziDetail');
        Route::delete('content/bazi/:id', 'Admin/deleteBaziRecord');
        Route::get('content/tarot', 'Admin/tarotRecords');
        Route::get('content/tarot/:id', 'Admin/tarotDetail');
        Route::delete('content/tarot/:id', 'Admin/deleteTarotRecord');
        Route::get('content/daily', 'Admin/dailyFortuneList');
        Route::post('content/daily', 'Admin/createDailyFortune');
        Route::put('content/daily/:id', 'Admin/updateDailyFortune');
        Route::delete('content/daily/:id', 'Admin/deleteDailyFortune');
        Route::get('content/almanac', 'admin.Almanac/almanacList');
        Route::post('content/almanac', 'admin.Almanac/saveAlmanac');
        Route::put('content/almanac/:id', 'admin.Almanac/updateAlmanac');
        Route::delete('content/almanac/:id', 'admin.Almanac/deleteAlmanac');
        
        // 积分管理
        Route::get('points/records', 'Admin/pointsRecords');
        Route::post('points/adjust', 'admin.User/adjustPoints');
        Route::get('points/rules', 'Admin/getPointsRules');
        Route::put('points/rules', 'Admin/savePointsRules');
        Route::get('points/stats', 'Admin/pointsStats');
        
        // 支付管理
        Route::get('payment/config', 'admin.Payment/getConfig');
        Route::post('payment/config', 'admin.Payment/saveConfig');
        Route::get('payment/orders', 'admin.Payment/getOrders');
        Route::get('payment/orders/export', 'admin.Payment/exportOrders');
        Route::get('payment/orders/:id', 'admin.Payment/getOrderDetail');
        Route::put('payment/orders/batch-status', 'admin.Payment/batchUpdateStatus');
        Route::put('payment/orders/:id/status', 'admin.Payment/updateOrderStatus');
        Route::post('payment/orders/:id/refund', 'admin.Payment/refundOrder');
        Route::post('payment/orders/:id/complete', 'admin.Payment/manualComplete');
        Route::post('payment/orders/:id/cancel', 'admin.Payment/cancelOrder');
        Route::get('payment/stats', 'admin.Payment/getStats');
        Route::get('payment/trend', 'admin.Payment/getTrend');

        // VIP订单管理
        Route::get('order', 'admin.Order/index');
        Route::get('order/:id', 'admin.Order/detail');
        Route::post('order/refund', 'admin.Order/refund');
        Route::get('order/packages', 'admin.Order/packages');
        Route::post('order/save-package', 'admin.Order/savePackage');

        // 短信管理

        Route::get('sms/config', 'admin.Sms/getConfig');
        Route::post('sms/config', 'admin.Sms/saveConfig');
        Route::post('sms/test', 'admin.Sms/testSend');
        Route::get('sms/stats', 'admin.Sms/getStats');
        Route::get('sms/records', 'admin.Sms/getRecords');
        
        // 反馈管理
        Route::get('feedback', 'admin.Feedback/index');
        Route::get('feedback/:id', 'admin.Feedback/detail');
        Route::post('feedback/:id/reply', 'admin.Feedback/reply');
        Route::put('feedback/:id/status', 'admin.Feedback/updateStatus');
        Route::delete('feedback/:id', 'admin.Feedback/delete');
        Route::get('feedback/categories', 'admin.Feedback/categories');
        Route::post('feedback/categories', 'admin.Feedback/saveCategory');
        Route::delete('feedback/categories/:id', 'admin.Feedback/deleteCategory');
        
        // 知识库管理
        Route::get('knowledge/articles', 'admin.Knowledge/articleList');
        Route::get('knowledge/articles/:id', 'admin.Knowledge/articleDetail');
        Route::post('knowledge/articles', 'admin.Knowledge/saveArticle');
        Route::put('knowledge/articles/:id', 'admin.Knowledge/updateArticle');
        Route::delete('knowledge/articles/:id', 'admin.Knowledge/deleteArticle');
        Route::get('knowledge/categories', 'admin.Knowledge/articleCategories');
        Route::post('knowledge/categories', 'admin.Knowledge/saveArticleCategory');
        Route::delete('knowledge/categories/:id', 'admin.Knowledge/deleteArticleCategory');

        
        // 反作弊系统
        Route::get('anticheat/events', 'Admin/riskEvents');
        Route::get('anticheat/events/:id', 'Admin/riskEventDetail');
        Route::put('anticheat/events/:id/handle', 'Admin/handleRiskEvent');
        Route::get('anticheat/rules', 'Admin/riskRules');
        Route::post('anticheat/rules', 'Admin/saveRiskRule');
        Route::put('anticheat/rules/:id', 'Admin/updateRiskRule');
        Route::delete('anticheat/rules/:id', 'Admin/deleteRiskRule');
        Route::get('anticheat/devices', 'Admin/deviceFingerprints');
        Route::put('anticheat/devices/:id/block', 'Admin/blockDevice');
        
        // 系统设置
        Route::get('system/settings', 'Admin/getSettings');
        Route::put('system/settings', 'Admin/saveSettings');
        Route::get('system/sensitive', 'admin.SensitiveWord/index');
        Route::post('system/sensitive', 'admin.SensitiveWord/create');
        Route::put('system/sensitive/:id', 'admin.SensitiveWord/update');
        Route::delete('system/sensitive/:id', 'admin.SensitiveWord/delete');

        Route::post('system/sensitive/import', 'admin.SensitiveWord/import');
        Route::get('system/notices', 'admin.Notice/getNotices');
        Route::post('system/notices', 'admin.Notice/saveNotice');
        Route::delete('system/notices/:id', 'admin.Notice/deleteNotice');
        Route::get('system/shensha', 'admin.Shensha/index');
        Route::get('system/shensha/options', 'admin.Shensha/options');
        Route::post('system/shensha', 'admin.Shensha/save');
        Route::put('system/shensha/:id', 'admin.Shensha/save');
        Route::delete('system/shensha/:id', 'admin.Shensha/delete');
        Route::get('system/seo/configs', 'admin.Seo/seoConfigList');
        Route::post('system/seo/configs', 'admin.Seo/saveSeoConfig');
        Route::put('system/seo/configs/:id', 'admin.Seo/saveSeoConfig');
        Route::delete('system/seo/configs/:id', 'admin.Seo/deleteSeoConfig');
        Route::get('system/seo/stats', 'admin.Seo/seoStats');
        Route::get('system/seo/robots', 'admin.Seo/seoRobots');
        Route::put('system/seo/robots', 'admin.Seo/saveSeoRobots');
        Route::post('system/seo/submit', 'admin.Seo/seoSubmit');
        Route::get('system/admins', 'Admin/getAdminUsers');
        Route::post('system/admins', 'Admin/saveAdminUser');
        Route::delete('system/admins/:id', 'Admin/deleteAdminUser');
        
        // 角色和字典管理
        Route::get('system/roles', 'admin.System/getRoles');
        Route::post('system/roles', 'admin.System/createRole');
        Route::put('system/roles/:id', 'admin.System/updateRole');
        Route::delete('system/roles/:id', 'admin.System/deleteRole');
        Route::get('system/permissions', 'admin.System/getPermissions');
        Route::get('system/roles/:id/permissions', 'admin.System/getRolePermissions');
        Route::post('system/roles/:id/permissions', 'admin.System/updateRolePermissions');
        Route::get('system/dict/types', 'admin.System/getDictTypes');
        Route::post('system/dict/types', 'admin.System/createDictType');
        Route::put('system/dict/types/:id', 'admin.System/updateDictType');
        Route::delete('system/dict/types/:id', 'admin.System/deleteDictType');
        Route::get('system/dict/data', 'admin.System/getDictData');
        Route::post('system/dict/data', 'admin.System/saveDictData');
        Route::delete('system/dict/data/:id', 'admin.System/deleteDictData');


        
        // 日志管理
        Route::get('logs/operation', 'Admin/operationLogs');
        Route::get('logs/login', 'Admin/loginLogs');
        Route::get('logs/api', 'Admin/apiLogs');
        Route::delete('logs/:type/clear', 'Admin/clearLogs');
        Route::get('logs/:type/export', 'Admin/exportLogs');
        
        // 任务调度
        Route::get('tasks', 'Admin/taskList');
        Route::get('tasks/:id', 'Admin/taskDetail');
        Route::post('tasks', 'Admin/createTask');
        Route::put('tasks/:id', 'Admin/updateTask');
        Route::delete('tasks/:id', 'Admin/deleteTask');
        Route::post('tasks/:id/run', 'Admin/runTask');
        Route::put('tasks/:id/status', 'Admin/toggleTaskStatus');
        Route::get('tasks/logs', 'Admin/taskLogs');
        Route::get('tasks/scripts', 'Admin/getTaskScripts');
        Route::post('tasks/scripts', 'Admin/saveTaskScript');
        Route::delete('tasks/scripts/:id', 'Admin/deleteTaskScript');
        
        // 黄历管理
        Route::get('almanac/list', 'admin.Almanac/almanacList');
        Route::post('almanac/save', 'admin.Almanac/saveAlmanac');
        Route::post('almanac/generate-month', 'admin.Almanac/generateAlmanacMonth');
    })->middleware(\app\middleware\AdminAuth::class);
})->middleware([
    \app\middleware\HttpsEnforce::class,
    \app\middleware\Cors::class,
    \app\middleware\RateLimit::class,
]);
