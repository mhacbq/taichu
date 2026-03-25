<?php

use think\facade\Route;

// 后台管理API路由组
Route::group('api/maodou', function () {
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

        // 待办事项
        Route::get('todo/list', 'admin.Todo/list');
        Route::get('todo/vip-expiring', 'admin.Todo/vipExpiring');
        Route::get('todo/inactive-users', 'admin.Todo/inactiveUsers');
        Route::post('todo/:id/dismiss', 'admin.Todo/dismiss');

        // 平台统计数据
        Route::get('statistics/index', 'Statistics/index');
        
        // 系统配置管理
        Route::get('config/features', 'admin.Config/features');
        Route::post('config/update-feature', 'admin.Config/updateFeature');
        Route::post('config/update-features', 'admin.Config/updateFeatures');
        Route::get('config/vip', 'admin.Config/vip');
        Route::post('config/update-vip', 'admin.Config/updateVip');
        Route::get('config/points', 'admin.Config/points');
        Route::post('config/update-points', 'admin.Config/updatePoints');
        Route::get('config/marketing', 'admin.Config/marketing');
        Route::post('config/update-marketing', 'admin.Config/updateMarketing');
        Route::post('config/refresh-cache', 'admin.Config/refreshCache');
        Route::get('config', 'admin.Config/index');
        Route::post('config/update', 'admin.Config/update');
        Route::post('config/update-batch', 'admin.Config/updateBatch');

        // 统一系统配置管理
        Route::get('system-config', 'admin.SystemConfigController/index');
        Route::post('system-config/save', 'admin.SystemConfigController/save');
        Route::post('system-config/test-payment', 'admin.SystemConfigController/testPayment');
        Route::post('system-config/test-ai', 'admin.SystemConfigController/testAI');
        Route::get('system-config/export', 'admin.SystemConfigController/export');

        // 用户管理
        Route::get('users/export', 'Admin/exportUsers');
        Route::get('users/behavior', 'admin.User/behavior');
        Route::put('users/batch-status', 'admin.User/batchUpdateStatus');
        Route::get('users', 'admin.User/index');
        Route::put('users/:id/status', 'admin.User/toggleStatus');
        Route::put('users/:id', 'admin.User/updateProfile');
        Route::get('users/:id', 'admin.User/detail');
        Route::post('users/:id/adjust-points', 'admin.User/adjustPoints');
        
        // 测算结果管理
        Route::get('bazi-manage', 'admin.BaziManage/index');
        Route::get('bazi-manage/stats', 'admin.BaziManage/stats');
        Route::post('bazi-manage/batch-delete', 'admin.BaziManage/batchDelete');
        Route::get('bazi-manage/:id', 'admin.BaziManage/detail');
        Route::delete('bazi-manage/:id', 'admin.BaziManage/delete');
        
        Route::get('tarot-manage', 'admin.TarotManage/index');
        Route::get('tarot-manage/stats', 'admin.TarotManage/stats');
        Route::post('tarot-manage/batch-delete', 'admin.TarotManage/batchDelete');
        Route::get('tarot-manage/:id', 'admin.TarotManage/detail');
        Route::delete('tarot-manage/:id', 'admin.TarotManage/delete');
        
        Route::get('liuyao-manage', 'admin.LiuyaoManage/index');
        Route::get('liuyao-manage/stats', 'admin.LiuyaoManage/stats');
        Route::get('liuyao-manage/trend', 'admin.LiuyaoManage/trend');
        Route::get('liuyao-manage/hot-questions', 'admin.LiuyaoManage/hotQuestions');
        Route::post('liuyao-manage/batch-delete', 'admin.LiuyaoManage/batchDelete');
        Route::get('liuyao-manage/:id', 'admin.LiuyaoManage/detail');
        Route::delete('liuyao-manage/:id', 'admin.LiuyaoManage/delete');
        
        Route::get('hehun-manage', 'admin.HehunManage/index');
        Route::get('hehun-manage/stats', 'admin.HehunManage/stats');
        Route::post('hehun-manage/batch-delete', 'admin.HehunManage/batchDelete');
        Route::get('hehun-manage/:id', 'admin.HehunManage/detail');
        Route::delete('hehun-manage/:id', 'admin.HehunManage/delete');

        Route::get('qiming-manage', 'admin.QimingManage/index');
        Route::get('qiming-manage/stats', 'admin.QimingManage/stats');
        Route::post('qiming-manage/batch-delete', 'admin.QimingManage/batchDelete');
        Route::get('qiming-manage/:id', 'admin.QimingManage/detail');
        Route::delete('qiming-manage/:id', 'admin.QimingManage/delete');

        Route::get('yearly-fortune-manage', 'admin.YearlyFortuneManage/index');
        Route::get('yearly-fortune-manage/stats', 'admin.YearlyFortuneManage/stats');
        Route::post('yearly-fortune-manage/batch-delete', 'admin.YearlyFortuneManage/batchDelete');
        Route::get('yearly-fortune-manage/:id', 'admin.YearlyFortuneManage/detail');
        Route::delete('yearly-fortune-manage/:id', 'admin.YearlyFortuneManage/delete');

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
        Route::get('content/pages', 'Content/getPages');
        Route::post('content/page/import', 'Content/importPage');
        Route::get('content/page/:pageId', 'Content/getPage');
        Route::post('content/page/:pageId', 'Content/savePage');
        Route::delete('content/page/:pageId', 'Content/deletePage');
        Route::get('content/page/:pageId/export', 'Content/exportPage');
        Route::post('content/page/:pageId/autosave', 'Content/autoSave');
        Route::get('content/page/:pageId/draft', 'Content/getDraft');
        Route::get('content/page/:pageId/versions', 'Content/getVersions');
        Route::post('content/version/:versionId/restore', 'Content/restoreVersion');
        Route::get('content/version/:versionId/preview', 'Content/previewVersion');
        Route::get('content/block-config/:type', 'Content/getBlockConfig');
        
        // 邀请管理
        Route::get('invites', 'admin.InviteManage/index');
        Route::get('invites/stats', 'admin.InviteManage/stats');

        // 积分管理
        Route::get('points/records', 'admin.Points/getRecords');
        Route::post('points/adjust', 'admin.Points/adjust');
        Route::post('points/batch-adjust', 'admin.Points/batchAdjust');
        Route::get('points/rules', 'admin.Points/getRules');
        Route::get('points/stats', 'admin.Points/getStats');

        // VIP套餐管理
        Route::get('vip-packages/list', 'admin.VipPackages/getList');
        Route::get('vip-packages/stats', 'admin.VipPackages/getStats');
        Route::post('vip-packages/save', 'admin.VipPackages/save');
        Route::post('vip-packages/batch-status', 'admin.VipPackages/batchUpdateStatus');
        Route::post('vip-packages/update-sort', 'admin.VipPackages/updateSort');
        Route::get('vip-packages/detail/:id', 'admin.VipPackages/getDetail');
        Route::delete('vip-packages/:id', 'admin.VipPackages/delete');
        
        // 支付管理
        Route::get('payment/config', 'admin.Payment/getConfig');
        Route::post('payment/config', 'admin.Payment/saveConfig');
        Route::get('payment/orders/export', 'admin.Payment/exportOrders');
        Route::put('payment/orders/batch-status', 'admin.Payment/batchUpdateStatus');
        Route::get('payment/orders', 'admin.Payment/getOrders');
        Route::get('payment/orders/:id', 'admin.Payment/getOrderDetail');

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

        // 八字记录管理
        Route::get('bazi-records', 'BaziRecordController/index');
        Route::get('bazi-records/statistics', 'BaziRecordController/statistics');

        // VIP记录管理
        Route::get('vip-records', 'VipRecordController/index');
        Route::get('vip-records/statistics', 'VipRecordController/statistics');

        // 短信管理

        Route::get('sms/config', 'admin.Sms/getConfig');
        Route::post('sms/config', 'admin.Sms/saveConfig');
        Route::post('sms/test', 'admin.Sms/testSend');
        Route::get('sms/stats', 'admin.Sms/getStats');
        Route::get('sms/records', 'admin.Sms/getRecords');
        
        // 反馈管理
        Route::get('feedback', 'admin.Feedback/index');
        Route::get('feedback/categories', 'admin.Feedback/categories');
        Route::post('feedback/categories', 'admin.Feedback/saveCategory');
        Route::delete('feedback/categories/:id', 'admin.Feedback/deleteCategory');
        Route::get('feedback/logs', 'admin.Feedback/logs');
        
        // 反馈分配和日志
        Route::get('feedback/assign/list', 'admin.FeedbackAssign/list');
        Route::post('feedback/assign', 'admin.FeedbackAssign/assign');
        Route::get('feedback/assign/my', 'admin.FeedbackAssign/myAssignments');
        Route::post('feedback/assign/:id/status', 'admin.FeedbackAssign/updateStatus');
        
        Route::get('feedback/:id', 'admin.Feedback/detail');
        Route::post('feedback/:id/reply', 'admin.Feedback/reply');
        Route::put('feedback/:id/status', 'admin.Feedback/updateStatus');
        Route::delete('feedback/:id', 'admin.Feedback/delete');
        Route::get('system/admin-list', 'admin.System/adminList');
        
        // 数据分析
        Route::get('analysis/payment', 'admin.Analysis/payment');
        Route::get('analysis/user', 'admin.Analysis/user');
        Route::get('analysis/result', 'admin.Analysis/result');
        
        // AI管理
        Route::get('ai/config', 'admin.Ai/getConfig');
        Route::post('ai/config', 'admin.Ai/saveConfig');
        Route::post('ai/test', 'admin.Ai/testConnection');
        Route::get('ai-prompts/list', 'admin.AiPrompt/getList');
        Route::get('ai-prompts/detail/:id', 'admin.AiPrompt/getDetail');
        Route::post('ai-prompts/save', 'admin.AiPrompt/save');
        Route::delete('ai-prompts/:id', 'admin.AiPrompt/delete');
        Route::post('ai-prompts/:id/default', 'admin.AiPrompt/setDefault');
        Route::post('ai-prompts/:id/preview', 'admin.AiPrompt/preview');
        Route::post('ai-prompts/:id/duplicate', 'admin.AiPrompt/duplicate');
        Route::get('ai-prompts/types', 'admin.AiPrompt/getTypes');
        
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
        Route::get('system/notification/config', 'admin.NotificationConfig/getSettings');
        Route::put('system/notification/config', 'admin.NotificationConfig/saveSettings');
        Route::post('system/notification/test', 'admin.NotificationConfig/sendTest');
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
        // Shensha别名路由（兼容前端admin.js调用）
        Route::get('shensha/list', 'admin.Shensha/index');
        Route::post('shensha/save', 'admin.Shensha/save');
        Route::post('shensha/delete/:id', 'admin.Shensha/delete');
        Route::post('shensha/toggle-status', 'admin.Shensha/toggleStatus');
        // SEO别名路由（兼容前端admin.js调用）
        Route::post('seo/save', 'admin.Seo/saveSeoConfig');
        Route::get('seo/configs', 'admin.Seo/seoConfigList');
        Route::get('seo/robots', 'admin.Seo/seoRobots');
        Route::get('seo/stats', 'admin.Seo/seoStats');
        Route::post('seo/delete', 'admin.Seo/deleteSeoConfigByRoute');
        Route::post('seo/robots', 'admin.Seo/saveSeoRobots');
        Route::post('seo/sitemap-generate', 'admin.Seo/generateSitemap');
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


        // SEO管理
        Route::get('seo/list', 'admin.Seo/getList');
        Route::get('seo/:id', 'admin.Seo/getDetail');
        Route::post('seo/save', 'admin.Seo/save');
        Route::delete('seo/:id', 'admin.Seo/delete');
        Route::post('seo/batch-status', 'admin.Seo/batchUpdateStatus');
        Route::get('seo/page-types', 'admin.Seo/getPageTypes');

        // 日志管理
        Route::get('logs/operation', 'admin.Logs/getOperationLogs');
        Route::get('logs/login', 'admin.Logs/getLoginLogs');
        Route::get('logs/api', 'admin.Logs/getApiLogs');
        Route::delete('logs/:type/clear', 'admin.Logs/clearLogs');
        Route::get('logs/:type/export', 'admin.Logs/exportLogs');
        
        // 任务调度
        Route::get('tasks', 'Admin/taskList');
        Route::get('tasks/logs', 'Admin/taskLogs');
        Route::get('tasks/scripts', 'Admin/getTaskScripts');
        Route::post('tasks/scripts', 'Admin/saveTaskScript');
        Route::delete('tasks/scripts/:id', 'Admin/deleteTaskScript');
        Route::post('tasks', 'Admin/createTask');
        Route::get('tasks/:id', 'Admin/taskDetail');
        Route::put('tasks/:id', 'Admin/updateTask');
        Route::delete('tasks/:id', 'Admin/deleteTask');
        Route::post('tasks/:id/run', 'Admin/runTask');
        Route::put('tasks/:id/status', 'Admin/toggleTaskStatus');
        
        // 塔罗牌管理
        Route::get('tarot-cards/stats', 'admin.TarotCards/stats');
        Route::get('tarot-cards', 'admin.TarotCards/index');
        Route::get('tarot-cards/:id', 'admin.TarotCards/detail');
        Route::put('tarot-cards/:id', 'admin.TarotCards/update');
        Route::post('tarot-cards/:id/toggle-status', 'admin.TarotCards/toggleStatus');
        Route::post('tarot-cards/batch-status', 'admin.TarotCards/batchStatus');

        // FAQ管理
        Route::get('site/faqs', 'admin.FaqManage/index');
        Route::post('site/faqs', 'admin.FaqManage/save');
        Route::delete('site/faqs/:id', 'admin.FaqManage/delete');

        // 黄历管理
        Route::get('almanac/list', 'admin.Almanac/almanacList');
        Route::get('almanac/detail', 'admin.Almanac/almanacDetail');
        Route::get('almanac/months', 'admin.Almanac/almanacMonths');
        Route::post('almanac/save', 'admin.Almanac/saveAlmanac');
        Route::post('almanac/delete', 'admin.Almanac/deleteAlmanacByDate');
        Route::post('almanac/generate-month', 'admin.Almanac/generateAlmanacMonth');
    })->middleware(\app\middleware\AdminAuth::class);
})->middleware([
    \app\middleware\HttpsEnforce::class,
    \app\middleware\SecurityHeaders::class,
    \app\middleware\Cors::class,
    \app\middleware\SensitiveDataFilter::class,
    \app\middleware\RateLimit::class,
]);

