import request from './request'

export function getRiskEvents(params) {
  return request({
    url: '/anticheat/events',
    method: 'get',
    params
  })
}

export function getRiskEventDetail(id) {
  return request({
    url: `/anticheat/events/${id}`,
    method: 'get'
  })
}

export function handleRiskEvent(id, data) {
  return request({
    url: `/anticheat/events/${id}/handle`,
    method: 'put',
    data
  })
}

export function getRiskRules() {
  return request({
    url: '/anticheat/rules',
    method: 'get'
  })
}

export function saveRiskRule(data) {
  return request({
    url: '/anticheat/rules',
    method: 'post',
    data
  })
}

export function updateRiskRule(id, data) {
  return request({
    url: `/anticheat/rules/${id}`,
    method: 'put',
    data
  })
}

export function deleteRiskRule(id) {
  return request({
    url: `/anticheat/rules/${id}`,
    method: 'delete'
  })
}

export function getDeviceFingerprints(params) {
  return request({
    url: '/anticheat/devices',
    method: 'get',
    params
  })
}

export function blockDevice(id) {
  return request({
    url: `/anticheat/devices/${id}/block`,
    method: 'put'
  })
}
