import request from './request'

export function getSettings() {
  return request({
    url: '/system/settings',
    method: 'get'
  })
}

export function saveSettings(data) {
  return request({
    url: '/system/settings',
    method: 'put',
    data
  })
}

export function getSensitiveWords(params) {
  return request({
    url: '/system/sensitive',
    method: 'get',
    params
  })
}

export function addSensitiveWord(data) {
  return request({
    url: '/system/sensitive',
    method: 'post',
    data
  })
}

export function updateSensitiveWord(id, data) {
  return request({
    url: `/system/sensitive/${id}`,
    method: 'put',
    data
  })
}

export function deleteSensitiveWord(id) {
  return request({
    url: `/system/sensitive/${id}`,
    method: 'delete'
  })
}

export function importSensitiveWords(data) {
  return request({
    url: '/system/sensitive/import',
    method: 'post',
    data
  })
}

export function getNotices(params) {
  return request({
    url: '/system/notices',
    method: 'get',
    params
  })
}

export function saveNotice(data) {
  return request({
    url: '/system/notices',
    method: 'post',
    data
  })
}

export function deleteNotice(id) {
  return request({
    url: `/system/notices/${id}`,
    method: 'delete'
  })
}

export function getAdminUsers(params) {
  return request({
    url: '/system/admins',
    method: 'get',
    params
  })
}

export function saveAdminUser(data) {
  return request({
    url: '/system/admins',
    method: 'post',
    data
  })
}

export function deleteAdminUser(id) {
  return request({
    url: `/system/admins/${id}`,
    method: 'delete'
  })
}
