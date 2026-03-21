import request from '@/utils/request'

export function getTodoList() {
  return request({
    url: '/admin/todo/list',
    method: 'get'
  })
}

export function getVipExpiringUsers() {
  return request({
    url: '/admin/todo/vip-expiring',
    method: 'get'
  })
}

export function getInactiveUsers() {
  return request({
    url: '/admin/todo/inactive-users',
    method: 'get'
  })
}

export function dismissTodo(id) {
  return request({
    url: `/admin/todo/${id}/dismiss`,
    method: 'post'
  })
}
