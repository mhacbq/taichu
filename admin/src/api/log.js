import request from './request'

export function getOperationLogs(params) {
  return request({
    url: '/logs/operation',
    method: 'get',
    params
  })
}

export function getLoginLogs(params) {
  return request({
    url: '/logs/login',
    method: 'get',
    params
  })
}

export function getApiLogs(params) {
  return request({
    url: '/logs/api',
    method: 'get',
    params
  })
}

export function clearLogs(type) {
  return request({
    url: `/logs/${type}/clear`,
    method: 'delete'
  })
}

export function exportLogs(type, params) {
  return request({
    url: `/logs/${type}/export`,
    method: 'get',
    params,
    responseType: 'blob'
  })
}
