import request from './request'

export function login(data) {
  return request({
    url: '/auth/login',
    method: 'post',
    data
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

export function getUserList(params) {
  return request({
    url: '/users',
    method: 'get',
    params
  })
}

export function getUserDetail(id) {
  return request({
    url: `/users/${id}`,
    method: 'get'
  })
}

export function updateUserStatus(id, status) {
  return request({
    url: `/users/${id}/status`,
    method: 'put',
    data: { status }
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
