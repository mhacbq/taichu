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


