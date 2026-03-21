import request from './request'

/**
 * 获取充值数据分析
 */
export function getPaymentAnalysis(params) {
  return request({
    url: '/analysis/payment',
    method: 'get',
    params
  })
}

/**
 * 获取用户增长分析
 */
export function getUserAnalysis(params) {
  return request({
    url: '/analysis/user',
    method: 'get',
    params
  })
}

/**
 * 获取测算数据统计
 */
export function getResultAnalysis(params) {
  return request({
    url: '/analysis/result',
    method: 'get',
    params
  })
}
