import request from './adminRequest'

// 鍔熻兘寮€鍏?
export const getFeatureSwitches = () => request.get('/maodou/config/features')
export const updateFeature = (feature, enabled) => request.post('/maodou/config/update-feature', { feature, enabled })
export const updateFeatures = (features) => request.post('/maodou/config/update-features', { features })

// VIP閰嶇疆
export const getVipConfig = () => request.get('/maodou/config/vip')
export const updateVipConfig = (data) => request.post('/maodou/config/update-vip', data)

// 绉垎閰嶇疆
export const getPointsConfig = () => request.get('/maodou/config/points')
export const updatePointsConfig = (data) => request.post('/maodou/config/update-points', data)

// 钀ラ攢閰嶇疆
export const getMarketingConfig = () => request.get('/maodou/config/marketing')
export const updateMarketingConfig = (data) => request.post('/maodou/config/update-marketing', data)

// 鍒锋柊缂撳瓨
export const refreshConfigCache = () => request.post('/maodou/config/refresh-cache')

// 榛勫巻绠＄悊
export const getAlmanacList = (params) => request.get('/maodou/almanac/list', { params })
export const getAlmanacDetail = (date) => request.get('/maodou/almanac/detail', { params: { date } })
export const saveAlmanac = (data) => request.post('/maodou/almanac/save', data)
export const deleteAlmanac = (date) => request.post('/maodou/almanac/delete', { date })
export const generateAlmanacMonth = (year, month) => request.post('/maodou/almanac/generate-month', { year, month })
export const getAlmanacMonths = () => request.get('/maodou/almanac/months')

// SEO管理
export const getSeoConfigs = () => request.get('/maodou/seo/configs')
export const getSeoStats = (params) => request.get('/maodou/seo/stats', { params })
export const saveSeoConfig = (data) => request.post('/maodou/seo/save', data)
export const deleteSeoConfig = (route) => request.post('/maodou/seo/delete', { route })
export const getRobotsConfig = () => request.get('/maodou/seo/robots')
export const saveRobotsConfig = (content) => request.post('/maodou/seo/robots', { content })
export const generateSitemap = () => request.post('/maodou/seo/sitemap-generate')
export const getSeoPageTypes = () => request.get('/maodou/seo/page-types')
export const batchUpdateSeoStatus = (ids, status) => request.post('/maodou/seo/batch-status', { ids, status })

// 神煞管理
export const getShenshaList = (params) => request.get('/maodou/shensha/list', { params })
export const getShenshaOptions = () => request.get('/maodou/system/shensha/options')
export const saveShensha = (data) => request.post('/maodou/shensha/save', data)
export const deleteShenshaApi = (id) => request.post(`/maodou/shensha/delete/${id}`)
export const toggleShenshaStatus = (id, status) => request.post('/maodou/shensha/toggle-status', { id, status })

// 塔罗牌管理
export const getTarotCardList = (params) => request.get('/maodou/tarot-cards', { params })
export const getTarotCardDetail = (id) => request.get(`/maodou/tarot-cards/${id}`)
export const updateTarotCard = (id, data) => request.put(`/maodou/tarot-cards/${id}`, data)
export const toggleTarotCardStatus = (id) => request.post(`/maodou/tarot-cards/${id}/toggle-status`)
export const batchUpdateTarotCardStatus = (ids, status) => request.post('/maodou/tarot-cards/batch-status', { ids, status })
export const getTarotCardStats = () => request.get('/maodou/tarot-cards/stats')

// 鐭ヨ瘑搴撶鐞?- 鏂囩珷
export const getArticleList = (params) => request.get('/maodou/knowledge/articles', { params })
export const getArticleDetail = (id) => request.get(`/maodou/knowledge/articles/${id}`)
export const saveArticle = (data) => request.post('/maodou/knowledge/articles', data)
export const updateArticle = (id, data) => request.put(`/maodou/knowledge/articles/${id}`, data)
export const deleteArticle = (id) => request.delete(`/maodou/knowledge/articles/${id}`)

// 鐭ヨ瘑搴撶鐞?- 鍒嗙被
export const getArticleCategories = (params) => request.get('/maodou/knowledge/categories', { params })
export const saveArticleCategory = (data) => request.post('/maodou/knowledge/categories', data)
export const deleteArticleCategory = (id) => request.delete(`/maodou/knowledge/categories/${id}`)

// 浠〃鏉?
export const getDashboardStats = () => request.get('/maodou/dashboard/statistics')
export const getDashboardTrend = (params) => request.get('/maodou/dashboard/trend', { params })
export const getDashboardChart = (type) => request.get(`/maodou/dashboard/chart/${type}`)
export const getDashboardRealtime = () => request.get('/maodou/dashboard/realtime')
export const getPendingFeedback = () => request.get('/maodou/dashboard/pending-feedback')

// 鐢ㄦ埛绠＄悊
export const getUserList = (params) => request.get('/maodou/users', { params })
export const getUserDetail = (id) => request.get(`/maodou/users/${id}`)
export const updateUserProfile = (id, data) => request.put(`/maodou/users/${id}`, data)
export const toggleUserStatus = (id, status) => request.put(`/maodou/users/${id}/status`, { status })
export const batchUpdateUserStatus = (ids, status) => request.put('/maodou/users/batch-status', { ids, status })
export const exportUsers = (params) => request.get('/maodou/users/export', { params, responseType: 'blob' })
export const getUserBehavior = (id) => request.get('/maodou/users/behavior', { params: { id } })

// 绉垎绠＄悊
export const getPointsRecords = (params) => request.get('/maodou/points/records', { params })
export const adjustUserPoints = (data) => request.post('/maodou/points/adjust', data)
export const getPointsRules = () => request.get('/maodou/points/rules')
export const getPointsStats = (params) => request.get('/maodou/points/stats', { params })

// 瀹氭椂浠诲姟
export const getTaskList = () => request.get('/maodou/tasks')
export const saveTaskItem = (data) => request.post('/maodou/tasks', data)
export const deleteTaskItem = (id) => request.delete(`/maodou/tasks/${id}`)
export const runTaskNow = (id) => request.post(`/maodou/tasks/${id}/run`)
export const getTaskLogs = (params) => request.get('/maodou/tasks/logs', { params })

// 鏀粯/璁㈠崟绠＄悊
export const getPaymentOrders = (params) => request.get('/maodou/payment/orders', { params })
export const getPaymentOrderDetail = (id) => request.get(`/maodou/payment/orders/${id}`)
export const exportPaymentOrders = (params) => request.get('/maodou/payment/orders/export', { params, responseType: 'blob' })
export const updateOrderStatus = (id, status) => request.put(`/maodou/payment/orders/${id}/status`, { status })
export const refundOrder = (id, data) => request.post(`/maodou/payment/orders/${id}/refund`, data)
export const manualCompleteOrder = (id) => request.post(`/maodou/payment/orders/${id}/complete`)
export const cancelOrder = (id) => request.post(`/maodou/payment/orders/${id}/cancel`)
export const getPaymentStats = () => request.get('/maodou/payment/stats')
export const getPaymentTrend = (params) => request.get('/maodou/payment/trend', { params })

// 鍙嶉绠＄悊
export const getFeedbackList = (params) => request.get('/maodou/feedback', { params })
export const getFeedbackDetail = (id) => request.get(`/maodou/feedback/${id}`)
export const replyFeedback = (id, content) => request.post(`/maodou/feedback/${id}/reply`, { content })
export const updateFeedbackStatus = (id, status) => request.put(`/maodou/feedback/${id}/status`, { status })
export const deleteFeedback = (id) => request.delete(`/maodou/feedback/${id}`)
export const getFeedbackCategories = () => request.get('/maodou/feedback/categories')

// 绯荤粺璁剧疆
export const getSystemSettings = () => request.get('/maodou/system/settings')
export const saveSystemSettings = (data) => request.put('/maodou/system/settings', data)

// 鎿嶄綔鏃ュ織
export const getOperationLogs = (params) => request.get('/maodou/logs/operation', { params })
export const getLoginLogs = (params) => request.get('/maodou/logs/login', { params })
export const getApiLogs = (params) => request.get('/maodou/logs/api', { params })
export const clearLogs = (type) => request.delete(`/maodou/logs/${type}/clear`)
export const exportLogs = (type, params) => request.get(`/maodou/logs/${type}/export`, { params, responseType: 'blob' })

// 鍙嶄綔寮?
export const getRiskEvents = (params) => request.get('/maodou/anticheat/events', { params })
export const getRiskEventDetail = (id) => request.get(`/maodou/anticheat/events/${id}`)
export const handleRiskEvent = (id, data) => request.put(`/maodou/anticheat/events/${id}/handle`, data)
export const getRiskRules = () => request.get('/maodou/anticheat/rules')

// 閫氱煡閰嶇疆
export const getNotificationConfig = () => request.get('/maodou/system/notification/config')
export const saveNotificationConfig = (data) => request.put('/maodou/system/notification/config', data)
export const sendNotificationTest = (data) => request.post('/maodou/system/notification/test', data)

// 绠＄悊鍛樼鐞?
export const getAdminUsers = () => request.get('/maodou/system/admins')
export const saveAdminUser = (data) => request.post('/maodou/system/admins', data)
export const deleteAdminUser = (id) => request.delete(`/maodou/system/admins/${id}`)


// 鍐呭璁板綍 - 鍏瓧
export const getBaziRecords = (params) => request.get('/maodou/content/bazi', { params })
export const getBaziDetail = (id) => request.get(`/maodou/content/bazi/${id}`)
export const deleteBaziRecord = (id) => request.delete(`/maodou/content/bazi/${id}`)

// 鍐呭璁板綍 - 濉旂綏
export const getTarotRecords = (params) => request.get('/maodou/content/tarot', { params })
export const getTarotDetail = (id) => request.get(`/maodou/content/tarot/${id}`)
export const deleteTarotRecord = (id) => request.delete(`/maodou/content/tarot/${id}`)

// 鍐呭璁板綍 - 姣忔棩杩愬娍
export const getDailyFortuneList = (params) => request.get('/maodou/content/daily', { params })
export const createDailyFortune = (data) => request.post('/maodou/content/daily', data)
export const updateDailyFortune = (id, data) => request.put(`/maodou/content/daily/${id}`, data)
export const deleteDailyFortune = (id) => request.delete(`/maodou/content/daily/${id}`)

// 鍏憡绠＄悊
export const getNotices = (params) => request.get('/maodou/system/notices', { params })
export const saveNotice = (data) => request.post('/maodou/system/notices', data)
export const deleteNotice = (id) => request.delete(`/maodou/system/notices/${id}`)

// 鏁忔劅璇嶇鐞?
export const getSensitiveWords = (params) => request.get('/maodou/system/sensitive', { params })
export const createSensitiveWord = (data) => request.post('/maodou/system/sensitive', data)
export const updateSensitiveWord = (id, data) => request.put(`/maodou/system/sensitive/${id}`, data)
export const deleteSensitiveWord = (id) => request.delete(`/maodou/system/sensitive/${id}`)
export const importSensitiveWords = (data) => request.post('/maodou/system/sensitive/import', data)

// 鐭俊绠＄悊
export const getSmsConfig = () => request.get('/maodou/sms/config')
export const saveSmsConfig = (data) => request.post('/maodou/sms/config', data)
export const testSms = (data) => request.post('/maodou/sms/test', data)
export const testSmsSend = (data) => request.post('/maodou/sms/test', data)
export const getSmsStats = () => request.get('/maodou/sms/stats')
export const getSmsRecords = (params) => request.get('/maodou/sms/records', { params })

// 鏁版嵁鍒嗘瀽
export const getAnalysisUser = (params) => request.get('/maodou/analysis/user', { params })

// 瑙掕壊绠＄悊
export const getRoles = () => request.get('/maodou/system/roles')
export const createRole = (data) => request.post('/maodou/system/roles', data)
export const updateRole = (id, data) => request.put(`/maodou/system/roles/${id}`, data)
export const deleteRole = (id) => request.delete(`/maodou/system/roles/${id}`)

// 濂楅绠＄悊
export const getPackages = () => request.get('/maodou/order/packages')
export const savePackage = (data) => request.post('/maodou/order/save-package', data)

// 鍙嶄綔寮?- 瑙勫垯绠＄悊
export const saveRiskRule = (data) => request.post('/maodou/anticheat/rules', data)
export const updateRiskRule = (id, data) => request.put(`/maodou/anticheat/rules/${id}`, data)
export const deleteRiskRule = (id) => request.delete(`/maodou/anticheat/rules/${id}`)
// 鍙嶄綔寮?- 璁惧鎸囩汗
export const getDeviceFingerprints = (params) => request.get('/maodou/anticheat/devices', { params })
export const blockDevice = (id, data) => request.put(`/maodou/anticheat/devices/${id}/block`, data)

// 鏀粯閰嶇疆
export const getPaymentConfig = () => request.get('/maodou/payment/config')
export const savePaymentConfig = (data) => request.post('/maodou/payment/config', data)

// VIP套餐管理
export const getVipPackages = (params) => request.get('/maodou/vip-packages/list', { params })
export const getVipPackageDetail = (id) => request.get(`/maodou/vip-packages/detail/${id}`)
export const saveVipPackage = (data) => request.post('/maodou/vip-packages/save', data)
export const deleteVipPackage = (id) => request.delete(`/maodou/vip-packages/${id}`)
export const batchUpdateVipPackageStatus = (ids, status) => request.post('/maodou/vip-packages/batch-status', { ids, status })
export const updateVipPackageSort = (sortData) => request.post('/maodou/vip-packages/update-sort', { sort_data: sortData })
export const getVipPackageStats = () => request.get('/maodou/vip-packages/stats')

// 鏉冮檺绠＄悊
export const getPermissions = () => request.get('/maodou/system/permissions')
export const getRolePermissions = (roleId) => request.get(`/maodou/system/roles/${roleId}/permissions`)
export const updateRolePermissions = (roleId, permissions) => request.post(`/maodou/system/roles/${roleId}/permissions`, { permissions })

// 鏁版嵁瀛楀吀
export const getDictTypes = () => request.get('/maodou/system/dict/types')
export const createDictType = (data) => request.post('/maodou/system/dict/types', data)
export const updateDictType = (id, data) => request.put(`/maodou/system/dict/types/${id}`, data)
export const deleteDictType = (id) => request.delete(`/maodou/system/dict/types/${id}`)
export const getDictData = (params) => request.get('/maodou/system/dict/data', { params })
export const saveDictData = (data) => request.post('/maodou/system/dict/data', data)
export const deleteDictData = (id) => request.delete(`/maodou/system/dict/data/${id}`)

// 瀹氭椂浠诲姟琛ュ厖
export const getTaskDetail = (id) => request.get(`/maodou/tasks/${id}`)
export const updateTaskItem = (id, data) => request.put(`/maodou/tasks/${id}`, data)
export const toggleTaskStatus = (id, status) => request.put(`/maodou/tasks/${id}/status`, { status })
export const getTaskScripts = () => request.get('/maodou/tasks/scripts')
export const saveTaskScript = (data) => request.post('/maodou/tasks/scripts', data)
export const deleteTaskScript = (id) => request.delete(`/maodou/tasks/scripts/${id}`)

// 浠〃鏉胯ˉ鍏?
export const refreshDashboardStats = () => request.post('/maodou/dashboard/refresh-stats')
export const exportDashboardRealtime = (params) => request.get('/maodou/dashboard/export-realtime', { params, responseType: 'blob' })

