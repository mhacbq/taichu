import request from './request'

// 统计数据
export const getStats = () => request.get('/stats')
export const getHomeStats = () => request.get('/stats/home')

// 用户认证
export const login = (data) => request.post('/auth/login', data)
export const phoneLogin = (data) => request.post('/auth/phone-login', data)
export const sendSmsCode = (data) => request.post('/sms/send-code', data)

export const getUserInfo = () => request.get('/auth/userinfo')

// 每日运势（核心接口）
export const getDailyFortune = (config = {}) => request.get('/daily/fortune', config)
export const getDailyLuck = () => request.get('/daily/luck')
export const dailyCheckin = () => request.post('/daily/checkin')
export const getCheckinStatus = (config = {}) => request.get('/daily/checkin-status', config)
// 兼容旧接口名
export const getDailyReference = getDailyFortune
export const getTodayGuidance = getDailyLuck


// 积分系统
export const getPointsBalance = () => request.get('/points/balance')
export const getPointsHistory = () => request.get('/points/history')
export const consumePoints = (data) => request.post('/points/consume', data)

// 用户反馈
export const submitFeedback = (data) => request.post('/feedback/submit', data)

// 系统配置
export const getClientConfig = () => request.get('/config/client')
export const getFeatureSwitches = () => request.get('/config/features')

// SEO配置（公共接口，供前端路由守卫使用）
export const getActiveSeoConfigs = () => request.get('/seo/active-configs')

// VIP会员
export const getVipInfo = () => request.get('/vip/info')
export const getVipBenefits = () => request.get('/vip/benefits')
export const subscribeVip = (data) => request.post('/vip/subscribe', data)
export const getVipOrders = (params) => request.get('/vip/orders', { params })

// 积分任务
export const getTaskList = () => request.get('/tasks/list')
export const completeTask = (data) => request.post('/tasks/complete', data)

// 分享与邀请
export const generatePoster = (data) => request.post('/share/generate-poster', data)
export const recordShare = (data) => request.post('/share/record', data)
export const getInviteInfo = () => request.get('/share/invite-info')

// 取名建议
export const suggestNames = (data) => request.post('/qiming/suggest', data)
export const getQimingHistory = (params) => request.get('/qiming/history', { params })

// 吉日查询
export const queryJiri = (data) => request.post('/jiri/query', data)

// ====== 六爻占卜 API ======
export const getLiuyaoPricing = (config = {}) => request.get('/liuyao/pricing', config)
export const liuyaoDivination = (data) => request.post('/liuyao/divination', data)
export const getLiuyaoHistory = (params) => request.get('/liuyao/history', { params })
export const getLiuyaoDetail = (params) => request.get('/liuyao/detail', { params })
export const deleteLiuyaoRecord = (data) => request.post('/liuyao/delete', data)
export const analyzeLiuyaoAi = (data) => request.post('/liuyao/ai-analysis', data)

// ====== 八字合婚 API ======
export const getHehunPricing = (config = {}) => request.get('/hehun/pricing', config)
export const calculateHehun = (data) => request.post('/hehun/calculate', data)
export const getHehunHistory = (params) => request.get('/hehun/history', { params })
export const getHehunDetail = (id) => request.get('/hehun/detail', { params: { id } })
export const exportHehunReport = (data) => request.post('/hehun/export', data)

// ====== 塔罗占卜 API ======
export const drawTarot = (data) => request.post('/tarot/draw', data)
export const interpretTarot = (data) => request.post('/tarot/interpret', data)
export const aiAnalyzeTarot = (data) => request.post('/tarot/ai-analysis', data)
export const saveTarotRecord = (data) => request.post('/tarot/save-record', data)
export const getTarotHistory = (params) => request.get('/tarot/history', { params })
export const getTarotDetail = (params) => request.get('/tarot/detail', { params })
export const deleteTarotRecord = (data) => request.post('/tarot/delete-record', data)
export const setTarotPublic = (data) => request.post('/tarot/set-public', data)
export const getTarotShare = (params) => request.get('/tarot/share', { params })

// ====== 八字排盘 API ======
export const calculateBazi = (data) => request.post('/paipan/bazi', data)
export const getBaziHistory = (params) => request.get('/paipan/history', { params })
export const getBaziRecord = (id) => request.get('/paipan/record', { params: { id } })
export const deleteBaziRecord = (data) => request.post('/paipan/delete-record', data)
export const setBaziSharePublic = (data) => request.post('/paipan/set-share-public', data)
export const getBaziShare = (params) => request.get('/bazi/share', { params })

// ====== 支付 API ======
export const getRechargeOptions = () => request.get('/payment/options')
export const createRechargeOrder = (data) => request.post('/payment/create-order', data)
export const queryRechargeOrder = (params) => request.get('/payment/query-order', { params })
export const getRechargeHistory = (params) => request.get('/payment/history', { params })

// ====== 个人信息更新 ======
export const updateProfile = (data) => request.put('/auth/profile', data)
export const getMyInvites = (params) => request.get('/auth/my-invites', { params })
export const getInviteLeaderboard = () => request.get('/auth/invite-leaderboard')
export const getPointsRules = () => request.get('/points/rules')

// ====== 运势分析（八字流年大运）API ======
export const getFortunePointsCost = (config = {}) => request.get('/fortune/points-cost', config)
export const getYearlyFortune = (data) => request.post('/fortune/yearly', data)
export const getYearlyFortune2026Trend = (params) => request.get('/fortune/yearly-trend', { params })
export const getDayunAnalysis = (data) => request.post('/fortune/dayun-analysis', data)
export const getDayunChart = (data) => request.post('/fortune/dayun-chart', data)
