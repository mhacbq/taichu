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
