import request from './request'

// 八字记录
export function getBaziRecords(params) {
  return request({
    url: '/content/bazi',
    method: 'get',
    params
  })
}

export function getBaziDetail(id) {
  return request({
    url: `/content/bazi/${id}`,
    method: 'get'
  })
}

export function deleteBaziRecord(id) {
  return request({
    url: `/content/bazi/${id}`,
    method: 'delete'
  })
}

// 塔罗记录
export function getTarotRecords(params) {
  return request({
    url: '/content/tarot',
    method: 'get',
    params
  })
}

export function getTarotDetail(id) {
  return request({
    url: `/content/tarot/${id}`,
    method: 'get'
  })
}

export function deleteTarotRecord(id) {
  return request({
    url: `/content/tarot/${id}`,
    method: 'delete'
  })
}

// 每日运势
export function getDailyFortuneList(params) {
  return request({
    url: '/content/daily',
    method: 'get',
    params
  })
}

export function createDailyFortune(data) {
  return request({
    url: '/content/daily',
    method: 'post',
    data
  })
}

export function updateDailyFortune(id, data) {
  return request({
    url: `/content/daily/${id}`,
    method: 'put',
    data
  })
}

export function deleteDailyFortune(id) {
  return request({
    url: `/content/daily/${id}`,
    method: 'delete'
  })
}

// 黄历管理
export function getAlmanacList(params) {
  return request({
    url: '/content/almanac',
    method: 'get',
    params
  })
}

export function createAlmanac(data) {
  return request({
    url: '/content/almanac',
    method: 'post',
    data
  })
}

export function updateAlmanac(id, data) {
  return request({
    url: `/content/almanac/${id}`,
    method: 'put',
    data
  })
}

export function deleteAlmanac(id) {
  return request({
    url: `/content/almanac/${id}`,
    method: 'delete'
  })
}

// 神煞管理
export function getShenshaList(params) {
  return request({
    url: '/system/shensha',
    method: 'get',
    params
  })
}

export function getShenshaOptions() {
  return request({
    url: '/system/shensha/options',
    method: 'get'
  })
}

export function saveShensha(data) {
  const isUpdate = !!data.id
  return request({
    url: isUpdate ? `/system/shensha/${data.id}` : '/system/shensha',
    method: isUpdate ? 'put' : 'post',
    data
  })
}

export function deleteShensha(id) {
  return request({
    url: `/system/shensha/${id}`,
    method: 'delete'
  })
}
