import request from './request'

export {
  getBaziRecords,
  getBaziDetail,
  deleteBaziRecord,
  getTarotRecords,
  getTarotDetail,
  deleteTarotRecord,
  getDailyFortuneList,
  createDailyFortune,
  updateDailyFortune,
  deleteDailyFortune,
} from './content'

export function getSeoStats(params) {
  return request({
    url: '/seo/stats',
    method: 'get',
    params,
  })
}

