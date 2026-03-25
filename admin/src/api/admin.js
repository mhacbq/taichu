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
  // 路由修正：/seo/stats 已删除，使用 /system/seo/stats
  return request({
    url: '/system/seo/stats',
    method: 'get',
    params,
  })
}

