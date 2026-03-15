import request from './request'

export function getPointsRecords(params) {
  return request({
    url: '/points/records',
    method: 'get',
    params
  })
}

export function adjustPoints(data) {
  return request({
    url: '/points/adjust',
    method: 'post',
    data
  })
}

export function getPointsRules() {
  return request({
    url: '/points/rules',
    method: 'get'
  })
}

export function savePointsRules(data) {
  return request({
    url: '/points/rules',
    method: 'put',
    data
  })
}

export function getPointsStats(params) {
  return request({
    url: '/points/stats',
    method: 'get',
    params
  })
}
