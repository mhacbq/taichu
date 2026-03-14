import request from './request'

// 用户认证
export const login = (data) => request.post('/auth/login', data)
export const getUserInfo = () => request.get('/auth/userinfo')

// 八字排盘
export const calculateBazi = (data) => request.post('/paipan/bazi', data)
export const getBaziHistory = () => request.get('/paipan/history')

// 塔罗占卜
export const drawTarot = (data) => request.post('/tarot/draw', data)
export const interpretTarot = (data) => request.post('/tarot/interpret', data)

// 每日运势
export const getDailyFortune = () => request.get('/daily/fortune')
export const getTodayLuck = () => request.get('/daily/luck')

// 积分系统
export const getPointsBalance = () => request.get('/points/balance')
export const getPointsHistory = () => request.get('/points/history')
export const consumePoints = (data) => request.post('/points/consume', data)

// 用户反馈
export const submitFeedback = (data) => request.post('/feedback/submit', data)
