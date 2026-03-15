import request from './request'

// 获取充值选项
export const getRechargeOptions = () => request.get('/payment/options')

// 创建充值订单
export const createRechargeOrder = (data) => request.post('/payment/create-order', data)

// 查询订单状态
export const queryRechargeOrder = (params) => request.get('/payment/query-order', { params })

// 获取充值记录
export const getRechargeHistory = () => request.get('/payment/history')
