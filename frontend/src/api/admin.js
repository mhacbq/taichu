import request from './request'

// 功能开关
export const getFeatureSwitches = () => request.get('/admin/config/features')
export const updateFeature = (feature, enabled) => request.post('/admin/config/update-feature', { feature, enabled })
export const updateFeatures = (features) => request.post('/admin/config/update-features', { features })

// VIP配置
export const getVipConfig = () => request.get('/admin/config/vip')
export const updateVipConfig = (data) => request.post('/admin/config/update-vip', data)

// 积分配置
export const getPointsConfig = () => request.get('/admin/config/points')
export const updatePointsConfig = (data) => request.post('/admin/config/update-points', data)

// 营销配置
export const getMarketingConfig = () => request.get('/admin/config/marketing')
export const updateMarketingConfig = (data) => request.post('/admin/config/update-marketing', data)

// 刷新缓存
export const refreshConfigCache = () => request.post('/admin/config/refresh-cache')

// 黄历管理
export const getAlmanacList = (params) => request.get('/admin/almanac/list', { params })
export const getAlmanacDetail = (date) => request.get('/admin/almanac/detail', { params: { date } })
export const saveAlmanac = (data) => request.post('/admin/almanac/save', data)
export const deleteAlmanac = (date) => request.post('/admin/almanac/delete', { date })
export const generateAlmanacMonth = (year, month) => request.post('/admin/almanac/generate-month', { year, month })
export const getAlmanacMonths = () => request.get('/admin/almanac/months')

// SEO管理
export const getSeoConfigs = () => request.get('/admin/seo/configs')
export const getSeoStats = (params) => request.get('/admin/seo/stats', { params })
export const saveSeoConfig = (data) => request.post('/admin/seo/save', data)
export const deleteSeoConfig = (route) => request.post('/admin/seo/delete', { route })
export const getRobotsConfig = () => request.get('/admin/seo/robots')
export const saveRobotsConfig = (content) => request.post('/admin/seo/robots', { content })
export const generateSitemap = () => request.post('/admin/seo/sitemap-generate')

// 神煞管理
export const getShenshaList = (params) => request.get('/admin/shensha/list', { params })
export const saveShensha = (data) => request.post('/admin/shensha/save', data)
export const deleteShenshaApi = (id) => request.post(`/admin/shensha/delete/${id}`)
export const toggleShenshaStatus = (id, status) => request.post('/admin/shensha/toggle-status', { id, status })

// 知识库管理 - 文章
export const getArticleList = (params) => request.get('/admin/knowledge/articles', { params })
export const getArticleDetail = (id) => request.get(`/admin/knowledge/articles/${id}`)
export const saveArticle = (data) => request.post('/admin/knowledge/articles', data)
export const updateArticle = (id, data) => request.put(`/admin/knowledge/articles/${id}`, data)
export const deleteArticle = (id) => request.delete(`/admin/knowledge/articles/${id}`)

// 知识库管理 - 分类
export const getArticleCategories = (params) => request.get('/admin/knowledge/categories', { params })
export const saveArticleCategory = (data) => request.post('/admin/knowledge/categories', data)
export const deleteArticleCategory = (id) => request.delete(`/admin/knowledge/categories/${id}`)

// 仪表板
export const getDashboardStats = () => request.get('/admin/dashboard/statistics')
export const getDashboardTrend = (params) => request.get('/admin/dashboard/trend', { params })
export const getDashboardChart = (type) => request.get(`/admin/dashboard/chart/${type}`)
export const getDashboardRealtime = () => request.get('/admin/dashboard/realtime')
export const getPendingFeedback = () => request.get('/admin/dashboard/pending-feedback')

// 用户管理
export const getUserList = (params) => request.get('/admin/users', { params })
export const getUserDetail = (id) => request.get(`/admin/users/${id}`)
export const updateUserProfile = (id, data) => request.put(`/admin/users/${id}`, data)
export const toggleUserStatus = (id, status) => request.put(`/admin/users/${id}/status`, { status })
export const batchUpdateUserStatus = (ids, status) => request.put('/admin/users/batch-status', { ids, status })
export const exportUsers = (params) => request.get('/admin/users/export', { params, responseType: 'blob' })
export const getUserBehavior = (id) => request.get('/admin/users/behavior', { params: { id } })

// 积分管理
export const getPointsRecords = (params) => request.get('/admin/points/records', { params })
export const adjustUserPoints = (data) => request.post('/admin/points/adjust', data)
export const getPointsRules = () => request.get('/admin/points/rules')
export const savePointsRules = (data) => request.put('/admin/points/rules', data)
export const savePointsRule = (data) => request.post('/admin/points/rules', data)
export const deletePointsRule = (id) => request.delete(`/admin/points/rules/${id}`)

// 定时任务
export const getTaskList = () => request.get('/admin/tasks')
export const saveTaskItem = (data) => request.post('/admin/tasks', data)
export const deleteTaskItem = (id) => request.delete(`/admin/tasks/${id}`)
export const runTaskNow = (id) => request.post(`/admin/tasks/${id}/run`)
export const getTaskLogs = (params) => request.get('/admin/tasks/logs', { params })
export const getPointsStats = (params) => request.get('/admin/points/stats', { params })

// 支付/订单管理
export const getPaymentOrders = (params) => request.get('/admin/payment/orders', { params })
export const getPaymentOrderDetail = (id) => request.get(`/admin/payment/orders/${id}`)
export const exportPaymentOrders = (params) => request.get('/admin/payment/orders/export', { params, responseType: 'blob' })
export const updateOrderStatus = (id, status) => request.put(`/admin/payment/orders/${id}/status`, { status })
export const refundOrder = (id, data) => request.post(`/admin/payment/orders/${id}/refund`, data)
export const manualCompleteOrder = (id) => request.post(`/admin/payment/orders/${id}/complete`)
export const cancelOrder = (id) => request.post(`/admin/payment/orders/${id}/cancel`)
export const getPaymentStats = () => request.get('/admin/payment/stats')
export const getPaymentTrend = (params) => request.get('/admin/payment/trend', { params })

// 反馈管理
export const getFeedbackList = (params) => request.get('/admin/feedback', { params })
export const getFeedbackDetail = (id) => request.get(`/admin/feedback/${id}`)
export const replyFeedback = (id, content) => request.post(`/admin/feedback/${id}/reply`, { content })
export const updateFeedbackStatus = (id, status) => request.put(`/admin/feedback/${id}/status`, { status })
export const deleteFeedback = (id) => request.delete(`/admin/feedback/${id}`)
export const getFeedbackCategories = () => request.get('/admin/feedback/categories')

// 系统设置
export const getSystemSettings = () => request.get('/admin/system/settings')
export const saveSystemSettings = (data) => request.put('/admin/system/settings', data)

// 操作日志
export const getOperationLogs = (params) => request.get('/admin/logs/operation', { params })
export const getLoginLogs = (params) => request.get('/admin/logs/login', { params })
export const getApiLogs = (params) => request.get('/admin/logs/api', { params })
export const clearLogs = (type) => request.delete(`/admin/logs/${type}/clear`)
export const exportLogs = (type, params) => request.get(`/admin/logs/${type}/export`, { params, responseType: 'blob' })

// 反作弊
export const getRiskEvents = (params) => request.get('/admin/anticheat/events', { params })
export const getRiskEventDetail = (id) => request.get(`/admin/anticheat/events/${id}`)
export const handleRiskEvent = (id, data) => request.put(`/admin/anticheat/events/${id}/handle`, data)
export const getRiskRules = () => request.get('/admin/anticheat/rules')

// 通知配置
export const getNotificationConfig = () => request.get('/admin/system/notification/config')
export const saveNotificationConfig = (data) => request.put('/admin/system/notification/config', data)
export const sendNotificationTest = (data) => request.post('/admin/system/notification/test', data)

// 管理员管理
export const getAdminUsers = () => request.get('/admin/system/admins')
export const saveAdminUser = (data) => request.post('/admin/system/admins', data)
export const deleteAdminUser = (id) => request.delete(`/admin/system/admins/${id}`)


// 内容记录 - 八字
export const getBaziRecords = (params) => request.get('/admin/content/bazi', { params })
export const getBaziDetail = (id) => request.get(`/admin/content/bazi/${id}`)
export const deleteBaziRecord = (id) => request.delete(`/admin/content/bazi/${id}`)

// 内容记录 - 塔罗
export const getTarotRecords = (params) => request.get('/admin/content/tarot', { params })
export const getTarotDetail = (id) => request.get(`/admin/content/tarot/${id}`)
export const deleteTarotRecord = (id) => request.delete(`/admin/content/tarot/${id}`)

// 内容记录 - 每日运势
export const getDailyFortuneList = (params) => request.get('/admin/content/daily', { params })
export const createDailyFortune = (data) => request.post('/admin/content/daily', data)
export const updateDailyFortune = (id, data) => request.put(`/admin/content/daily/${id}`, data)
export const deleteDailyFortune = (id) => request.delete(`/admin/content/daily/${id}`)

// 公告管理
export const getNotices = (params) => request.get('/admin/system/notices', { params })
export const saveNotice = (data) => request.post('/admin/system/notices', data)
export const deleteNotice = (id) => request.delete(`/admin/system/notices/${id}`)

// 敏感词管理
export const getSensitiveWords = (params) => request.get('/admin/system/sensitive', { params })
export const createSensitiveWord = (data) => request.post('/admin/system/sensitive', data)
export const updateSensitiveWord = (id, data) => request.put(`/admin/system/sensitive/${id}`, data)
export const deleteSensitiveWord = (id) => request.delete(`/admin/system/sensitive/${id}`)
export const importSensitiveWords = (data) => request.post('/admin/system/sensitive/import', data)

// 短信管理
export const getSmsConfig = () => request.get('/admin/sms/config')
export const saveSmsConfig = (data) => request.post('/admin/sms/config', data)
export const testSmsSend = (data) => request.post('/admin/sms/test', data)
export const getSmsStats = () => request.get('/admin/sms/stats')
export const getSmsRecords = (params) => request.get('/admin/sms/records', { params })

// 角色管理
export const getRoles = () => request.get('/admin/system/roles')
export const createRole = (data) => request.post('/admin/system/roles', data)
export const updateRole = (id, data) => request.put(`/admin/system/roles/${id}`, data)
export const deleteRole = (id) => request.delete(`/admin/system/roles/${id}`)

// 套餐管理
export const getPackages = () => request.get('/admin/order/packages')
export const savePackage = (data) => request.post('/admin/order/save-package', data)

// 反作弊 - 规则管理
export const saveRiskRule = (data) => request.post('/admin/anticheat/rules', data)
export const updateRiskRule = (id, data) => request.put(`/admin/anticheat/rules/${id}`, data)
export const deleteRiskRule = (id) => request.delete(`/admin/anticheat/rules/${id}`)
// 反作弊 - 设备指纹
export const getDeviceFingerprints = (params) => request.get('/admin/anticheat/devices', { params })
export const blockDevice = (id, data) => request.put(`/admin/anticheat/devices/${id}/block`, data)

// 支付配置
export const getPaymentConfig = () => request.get('/admin/payment/config')
export const savePaymentConfig = (data) => request.post('/admin/payment/config', data)

// 权限管理
export const getPermissions = () => request.get('/admin/system/permissions')
export const getRolePermissions = (roleId) => request.get(`/admin/system/roles/${roleId}/permissions`)
export const updateRolePermissions = (roleId, permissions) => request.post(`/admin/system/roles/${roleId}/permissions`, { permissions })

// 数据字典
export const getDictTypes = () => request.get('/admin/system/dict/types')
export const createDictType = (data) => request.post('/admin/system/dict/types', data)
export const updateDictType = (id, data) => request.put(`/admin/system/dict/types/${id}`, data)
export const deleteDictType = (id) => request.delete(`/admin/system/dict/types/${id}`)
export const getDictData = (params) => request.get('/admin/system/dict/data', { params })
export const saveDictData = (data) => request.post('/admin/system/dict/data', data)
export const deleteDictData = (id) => request.delete(`/admin/system/dict/data/${id}`)

// 定时任务补充
export const getTaskDetail = (id) => request.get(`/admin/tasks/${id}`)
export const updateTaskItem = (id, data) => request.put(`/admin/tasks/${id}`, data)
export const toggleTaskStatus = (id, status) => request.put(`/admin/tasks/${id}/status`, { status })
export const getTaskScripts = () => request.get('/admin/tasks/scripts')
export const saveTaskScript = (data) => request.post('/admin/tasks/scripts', data)
export const deleteTaskScript = (id) => request.delete(`/admin/tasks/scripts/${id}`)

// 仪表板补充
export const refreshDashboardStats = () => request.post('/admin/dashboard/refresh-stats')
export const exportDashboardRealtime = (params) => request.get('/admin/dashboard/export-realtime', { params, responseType: 'blob' })
