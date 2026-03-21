import request from '@/utils/request'

/**
 * 获取充值数据分析
 */
export function getPaymentAnalysis(params) {
  return request({
    url: '/api/admin/analysis/payment',
    method: 'get',
    params
  })
}

/**
 * 获取用户增长分析
 */
export function getUserAnalysis(params) {
  return request({
    url: '/api/admin/analysis/user',
    method: 'get',
    params
  })
}

/**
 * 获取测算数据统计
 */
export function getResultAnalysis(params) {
  return request({
    url: '/api/admin/analysis/result',
    method: 'get',
    params
  })
}
