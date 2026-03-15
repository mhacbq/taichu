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
