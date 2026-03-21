import request from './request'

export function getTodoList() {
  return request({
    url: '/todo/list',
    method: 'get'
  })
}

export function getVipExpiringUsers() {
  return request({
    url: '/todo/vip-expiring',
    method: 'get'
  })
}

export function getInactiveUsers() {
  return request({
    url: '/todo/inactive-users',
    method: 'get'
  })
}

export function dismissTodo(id) {
  return request({
    url: `/todo/${id}/dismiss`,
    method: 'post'
  })
}
