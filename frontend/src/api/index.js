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

// 八字排盘
export const calculateBazi = (data) => request.post('/paipan/bazi', data)
export const getBaziHistory = (params) => request.get('/paipan/history', { params })
export const setBaziSharePublic = (data) => request.post('/paipan/set-share-public', data)
export const deleteBaziRecord = (data) => request.post('/paipan/delete-record', data)
export const getBaziShare = (params) => request.get('/bazi/share', { params })

// 塔罗占卜
export const drawTarot = (data) => request.post('/tarot/draw', data)
export const interpretTarot = (data) => request.post('/tarot/interpret', data)
export const saveTarotRecord = (data) => request.post('/tarot/save-record', data)
export const getTarotHistory = (params) => request.get('/tarot/history', { params })
export const getTarotDetail = (params) => request.get('/tarot/detail', { params })
export const deleteTarotRecord = (data) => request.post('/tarot/delete-record', data)
export const setTarotPublic = (data) => request.post('/tarot/set-public', data)
export const getTarotShare = (params) => request.get('/tarot/share', { params })

// 每日运势
export const getDailyFortune = () => request.get('/daily/fortune')
export const getTodayLuck = () => request.get('/daily/luck')
export const dailyCheckin = () => request.post('/daily/checkin')
export const getCheckinStatus = (config = {}) => request.get('/daily/checkin-status', config)


// 积分系统
export const getPointsBalance = () => request.get('/points/balance')
export const getPointsHistory = () => request.get('/points/history')
export const consumePoints = (data) => request.post('/points/consume', data)

// 用户反馈
export const submitFeedback = (data) => request.post('/feedback/submit', data)

// 运势分析（积分消耗功能）
export const getFortunePointsCost = (config = {}) => request.get('/fortune/points-cost', config)

export const getYearlyFortune = (data) => request.post('/fortune/yearly', data)
export const getYearlyTrend = (params) => request.get('/fortune/yearly-trend', { params })
export const getDayunAnalysis = (data) => request.post('/fortune/dayun-analysis', data)
export const getDayunChart = (data) => request.post('/fortune/dayun-chart', data)

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

// 八字合婚
export const getHehunPricing = () => request.get('/hehun/pricing')
export const calculateHehun = (data) => request.post('/hehun/calculate', data)
export const getHehunHistory = (params) => request.get('/hehun/history', { params })
export const exportHehunReport = (data) => request.post('/hehun/export', data)

// 取名建议
export const suggestNames = (data) => request.post('/qiming/suggest', data)
export const getQimingHistory = (params) => request.get('/qiming/history', { params })

// 吉日查询
export const queryJiri = (data) => request.post('/jiri/query', data)

// 六爻占卜
export const getLiuyaoPricing = () => request.get('/liuyao/pricing')
export const liuyaoDivination = (data) => request.post('/liuyao/divination', data)
export const getLiuyaoHistory = (params) => request.get('/liuyao/history', { params })
export const deleteLiuyaoRecord = (data) => request.post('/liuyao/delete', data)
