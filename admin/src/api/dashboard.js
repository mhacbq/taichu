import request from './request'

export function getStatistics() {
  return request({
    url: '/dashboard/statistics',
    method: 'get'
  })
}

export function getTrendData(params) {
  return request({
    url: '/dashboard/trend',
    method: 'get',
    params
  })
}

export function getRealtimeData() {
  return request({
    url: '/dashboard/realtime',
    method: 'get'
  })
}

export function getChartData(type, params) {
  return request({
    url: `/dashboard/chart/${type}`,
    method: 'get',
    params
  })
}

export function getPendingFeedback() {
  return request({
    url: '/dashboard/pending-feedback',
    method: 'get'
  })
}
