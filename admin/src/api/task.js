import request from './request'

export function getTaskList(params) {
  return request({
    url: '/tasks',
    method: 'get',
    params
  })
}

export function getTaskDetail(id) {
  return request({
    url: `/tasks/${id}`,
    method: 'get'
  })
}

export function createTask(data) {
  return request({
    url: '/tasks',
    method: 'post',
    data
  })
}

export function updateTask(id, data) {
  return request({
    url: `/tasks/${id}`,
    method: 'put',
    data
  })
}

export function deleteTask(id) {
  return request({
    url: `/tasks/${id}`,
    method: 'delete'
  })
}

export function runTask(id) {
  return request({
    url: `/tasks/${id}/run`,
    method: 'post'
  })
}

export function toggleTaskStatus(id, status) {
  return request({
    url: `/tasks/${id}/status`,
    method: 'put',
    data: { status }
  })
}

export function getTaskLogs(params) {
  return request({
    url: '/tasks/logs',
    method: 'get',
    params
  })
}

export function getTaskScripts() {
  return request({
    url: '/tasks/scripts',
    method: 'get'
  })
}

export function saveTaskScript(data) {
  return request({
    url: '/tasks/scripts',
    method: 'post',
    data
  })
}

export function deleteTaskScript(id) {
  return request({
    url: `/tasks/scripts/${id}`,
    method: 'delete'
  })
}
