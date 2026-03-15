import request from './request'

export function getFeedbackList(params) {
  return request({
    url: '/feedback',
    method: 'get',
    params
  })
}

export function getFeedbackDetail(id) {
  return request({
    url: `/feedback/${id}`,
    method: 'get'
  })
}

export function replyFeedback(id, data) {
  return request({
    url: `/feedback/${id}/reply`,
    method: 'post',
    data
  })
}

export function updateFeedbackStatus(id, status) {
  return request({
    url: `/feedback/${id}/status`,
    method: 'put',
    data: { status }
  })
}

export function deleteFeedback(id) {
  return request({
    url: `/feedback/${id}`,
    method: 'delete'
  })
}

export function getFeedbackCategories() {
  return request({
    url: '/feedback/categories',
    method: 'get'
  })
}

export function saveFeedbackCategory(data) {
  return request({
    url: '/feedback/categories',
    method: 'post',
    data
  })
}

export function deleteFeedbackCategory(id) {
  return request({
    url: `/feedback/categories/${id}`,
    method: 'delete'
  })
}
