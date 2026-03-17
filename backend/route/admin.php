<?php

use think\facade\Route;

// 后台管理API路由组
Route::group('api/admin', function () {
    // 认证相关（无需登录）
    Route::post('auth/login', 'AdminAuth/login');
    
    // 需要登录验证的路由组
    Route::group('', function () {
        // 认证
        Route::get('auth/info', 'AdminAuth/info');
        Route::post('auth/logout', 'AdminAuth/logout');
        
        // 仪表盘
        Route::get('dashboard/statistics', 'Admin/dashboard');
        Route::get('dashboard/trend', 'Admin/dashboardTrend');
        Route::get('dashboard/chart/:type', 'Admin/chartData');
        Route::get('dashboard/realtime', 'Admin/realtime');
        Route::get('dashboard/pending-feedback', 'Admin/pendingFeedback');
        
        // 用户管理
        Route::get('users', 'Admin/users');
        Route::get('users/:id', 'Admin/userDetail');
        Route::put('users/:id/status', 'Admin/updateUserStatus');
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
        
        // 积分管理
        Route::get('points/records', 'Admin/pointsRecords');
        Route::post('points/adjust', 'Admin/adjustPoints');
        Route::get('points/rules', 'Admin/getPointsRules');
        Route::put('points/rules', 'Admin/savePointsRules');
        Route::get('points/stats', 'Admin/pointsStats');
        
        // 支付管理
        Route::get('payment/config', 'AdminPayment/getConfig');
        Route::post('payment/config', 'AdminPayment/saveConfig');
        Route::get('payment/orders', 'AdminPayment/getOrders');
        Route::get('payment/orders/:id', 'AdminPayment/getOrderDetail');
        Route::post('payment/orders/:id/complete', 'AdminPayment/manualComplete');
        Route::post('payment/orders/:id/cancel', 'AdminPayment/cancelOrder');
        Route::get('payment/stats', 'AdminPayment/getStats');
        Route::get('payment/trend', 'AdminPayment/getTrend');
        
        // 短信管理
        Route::get('sms/config', 'AdminSms/getConfig');
        Route::post('sms/config', 'AdminSms/saveConfig');
        Route::post('sms/test', 'AdminSms/testSend');
        Route::get('sms/stats', 'AdminSms/getStats');
        Route::get('sms/records', 'AdminSms/getRecords');
        
        // 反馈管理
        Route::get('feedback', 'Admin/feedbackList');
        Route::get('feedback/:id', 'Admin/feedbackDetail');
        Route::post('feedback/:id/reply', 'Admin/replyFeedback');
        Route::put('feedback/:id/status', 'Admin/updateFeedbackStatus');
        Route::delete('feedback/:id', 'Admin/deleteFeedback');
        Route::get('feedback/categories', 'Admin/feedbackCategories');
        Route::post('feedback/categories', 'Admin/saveFeedbackCategory');
        Route::delete('feedback/categories/:id', 'Admin/deleteFeedbackCategory');
        
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
        Route::get('system/sensitive', 'Admin/getSensitiveWords');
        Route::post('system/sensitive', 'Admin/addSensitiveWord');
        Route::put('system/sensitive/:id', 'Admin/updateSensitiveWord');
        Route::delete('system/sensitive/:id', 'Admin/deleteSensitiveWord');

        Route::post('system/sensitive/import', 'Admin/importSensitiveWords');
        Route::get('system/notices', 'Admin/getNotices');
        Route::post('system/notices', 'Admin/saveNotice');
        Route::delete('system/notices/:id', 'Admin/deleteNotice');
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
        Route::get('almanac/list', 'Admin/almanacList');
        Route::post('almanac/save', 'Admin/saveAlmanac');
        Route::post('almanac/generate-month', 'Admin/generateAlmanacMonth');
    })->middleware(\app\middleware\AdminAuth::class);
});
