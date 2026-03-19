import request from './request'

// 统计数据
export const getStats = () => request.get('/stats')
export const getHomeStats = () => request.get('/stats/home')

// 用户认证
export const login = (data) => request.post('/auth/login', data)
export const wechatLogin = (data) => request.post('/auth/wechat-login', data)
export const phoneLogin = (data) => request.post('/auth/phone-login', data)
export const sendSmsCode = (data) => request.post('/sms/send-code', data)

export const getUserInfo = () => request.get('/auth/userinfo')

// 文化测算
export const calculateCultural = (data) => request.post('/paipan/cultural', data)
export const getCulturalHistory = (params) => request.get('/paipan/history', { params })
export const setCulturalSharePublic = (data) => request.post('/paipan/set-share-public', data)
export const deleteCulturalRecord = (data) => request.post('/paipan/delete-record', data)
export const getCulturalShare = (params) => request.get('/cultural/share', { params })

// 文化分析
export const drawCultural = (data) => request.post('/cultural/draw', data)
export const interpretCultural = (data) => request.post('/cultural/interpret', data)
export const saveCulturalRecord = (data) => request.post('/cultural/save-record', data)
export const getCulturalHistory = (params) => request.get('/cultural/history', { params })
export const getCulturalDetail = (params) => request.get('/cultural/detail', { params })
export const deleteCulturalRecord = (data) => request.post('/cultural/delete-record', data)
export const setCulturalPublic = (data) => request.post('/cultural/set-public', data)
export const getCulturalShare = (params) => request.get('/cultural/share', { params })

// 日常参考
export const getDailyReference = () => request.get('/daily/reference')
export const getTodayGuidance = () => request.get('/daily/guidance')
export const dailyCheckin = () => request.post('/daily/checkin')
export const getCheckinStatus = (config = {}) => request.get('/daily/checkin-status', config)


// 积分系统
export const getPointsBalance = () => request.get('/points/balance')
export const getPointsHistory = () => request.get('/points/history')
export const consumePoints = (data) => request.post('/points/consume', data)

// 用户反馈
export const submitFeedback = (data) => request.post('/feedback/submit', data)

// 决策参考（积分消耗功能）
export const getDecisionPointsCost = (config = {}) => request.get('/decision/points-cost', config)

export const getYearlyReference = (data) => request.post('/decision/yearly', data)
export const getYearlyTrend = (params) => request.get('/decision/yearly-trend', { params })
export const getPeriodAnalysis = (data) => request.post('/decision/period-analysis', data)
export const getPeriodChart = (data) => request.post('/decision/period-chart', data)

// 系统配置
export const getClientConfig = () => request.get('/config/client')
export const getFeatureSwitches = () => request.get('/config/features')

// VIP会员
export const getVipInfo = () => request.get('/vip/info')
export const getVipBenefits = () => request.get('/vip/benefits')
export const subscribeVip = (data) => request.post('/vip/subscribe', data)
export const getVipOrders = (params) => request.get('/vip/orders', { params })

// 积分任务
export const getTaskList = () => request.get('/tasks/list')
export const completeTask = (data) => request.post('/tasks/complete', data)
export const getMyTasks = () => request.get('/tasks/my-tasks')

// 分享与邀请
export const generatePoster = (data) => request.post('/share/generate-poster', data)
export const recordShare = (data) => request.post('/share/record', data)
export const getInviteInfo = () => request.get('/share/invite-info')

// 关系评估
export const getRelationshipPricing = () => request.get('/relationship/pricing')
export const assessRelationship = (data) => request.post('/relationship/assess', data)
export const getRelationshipHistory = (params) => request.get('/relationship/history', { params })
export const exportRelationshipReport = (data) => request.post('/relationship/export', data)

// 取名建议
export const suggestNames = (data) => request.post('/qiming/suggest', data)
export const getQimingHistory = (params) => request.get('/qiming/history', { params })

// 吉日查询
export const queryJiri = (data) => request.post('/jiri/query', data)

// 决策分析
export const getDecisionPricing = () => request.get('/decision/pricing')
export const decisionAnalysis = (data) => request.post('/decision/analysis', data)
export const getDecisionHistory = (params) => request.get('/decision/history', { params })
export const deleteDecisionRecord = (data) => request.post('/decision/delete', data)
