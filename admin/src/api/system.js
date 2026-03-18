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

export function getNotices(params, options = {}) {
  return request({
    url: '/system/notices',
    method: 'get',
    params,
    ...options
  })
}

export function saveNotice(data, options = {}) {
  return request({
    url: '/system/notices',
    method: 'post',
    data,
    ...options
  })
}

export function deleteNotice(id, options = {}) {
  return request({
    url: `/system/notices/${id}`,
    method: 'delete',
    ...options
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

// 角色管理
export function getRoles() {
  return request({
    url: '/system/roles',
    method: 'get'
  })
}

export function createRole(data) {
  return request({
    url: '/system/roles',
    method: 'post',
    data
  })
}

export function updateRole(id, data) {
  return request({
    url: `/system/roles/${id}`,
    method: 'put',
    data
  })
}

export function deleteRole(id) {
  return request({
    url: `/system/roles/${id}`,
    method: 'delete'
  })
}

export function getPermissions() {
  return request({
    url: '/system/permissions',
    method: 'get'
  })
}

export function getRolePermissions(id) {
  return request({
    url: `/system/roles/${id}/permissions`,
    method: 'get'
  })
}

export function updateRolePermissions(id, permissionIds) {
  return request({
    url: `/system/roles/${id}/permissions`,
    method: 'post',
    data: { permission_ids: permissionIds }
  })
}

// 字典管理
export function getDictTypes() {
  return request({
    url: '/system/dict/types',
    method: 'get'
  })
}

export function createDictType(data) {
  return request({
    url: '/system/dict/types',
    method: 'post',
    data
  })
}

export function updateDictType(id, data) {
  return request({
    url: `/system/dict/types/${id}`,
    method: 'put',
    data
  })
}

export function deleteDictType(id) {
  return request({
    url: `/system/dict/types/${id}`,
    method: 'delete'
  })
}

export function getDictData(type) {
  return request({
    url: '/system/dict/data',
    params: { type },
    method: 'get'
  })
}

export function saveDictData(data) {
  return request({
    url: '/system/dict/data',
    method: 'post',
    data
  })
}

export function deleteDictData(id) {
  return request({
    url: `/system/dict/data/${id}`,
    method: 'delete'
  })
}


