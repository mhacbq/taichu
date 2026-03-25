import request from './request'

export function login(data, options = {}) {
  return request({
    url: '/auth/login',
    method: 'post',
    data,
    ...options
  })
}


export function getInfo() {
  return request({
    url: '/auth/info',
    method: 'get'
  })
}

export function logout() {
  return request({
    url: '/auth/logout',
    method: 'post'
  })
}

export function getUserList(params, options = {}) {
  return request({
    url: '/users',
    method: 'get',
    params,
    ...options
  })
}


export function getUserDetail(id, options = {}) {
  return request({
    url: `/users/${id}`,
    method: 'get',
    ...options
  })
}

export function updateUserProfile(id, data, options = {}) {
  return request({
    url: `/users/${id}`,
    method: 'put',
    data,
    ...options
  })
}

export function updateUserStatus(id, status, options = {}) {

  return request({
    url: `/users/${id}/status`,
    method: 'put',
    data: { status },
    ...options
  })
}

export function batchUpdateUserStatus(ids, status, options = {}) {
  return request({
    url: '/users/batch-status',
    method: 'put',
    data: { ids, status },
    ...options
  })
}

export function adjustPoints(data, options = {}) {
  return request({
    url: '/points/adjust',
    method: 'post',
    data,
    ...options
  })
}

export function adjustUserPoints(id, data, options = {}) {
  return request({
    url: `/users/${id}/adjust-points`,
    method: 'post',
    data,
    ...options
  })
}

export function getUserBehavior(params) {
  return request({
    url: '/users/behavior',
    method: 'get',
    params
  })
}

export function exportUsers(params) {
  return request({
    url: '/users/export',
    method: 'get',
    params,
    responseType: 'blob'
  })
}
