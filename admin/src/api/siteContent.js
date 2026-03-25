import request from './request'

/**
 * 网站内容管理API
 */

// ============ 塔罗牌管理接口 ============

/**
 * 获取塔罗牌列表
 */
export function getTarotCardList(params) {
  return request({
    url: '/tarot-cards',
    method: 'get',
    params
  })
}

/**
 * 保存塔罗牌（新增用 POST，编辑用 PUT）
 */
export function saveTarotCard(data) {
  return request({
    url: data.id ? `/tarot-cards/${data.id}` : '/tarot-cards',
    method: data.id ? 'put' : 'post',
    data
  })
}

// ============ SEO 管理接口 ============

/**
 * 获取SEO配置列表
 */
export function getSeoConfigs(params, options = {}) {
  return request({
    url: '/system/seo/configs',
    method: 'get',
    params,
    ...options
  })
}

/**
 * 保存SEO配置
 */
export function saveSeoConfig(data, options = {}) {
  return request({
    url: data?.id ? `/system/seo/configs/${data.id}` : '/system/seo/configs',
    method: data?.id ? 'put' : 'post',
    data,
    ...options
  })
}

/**
 * 删除SEO配置
 */
export function deleteSeoConfig(id, options = {}) {
  return request({
    url: `/system/seo/configs/${id}`,
    method: 'delete',
    ...options
  })
}

/**
 * 获取Robots配置
 */
export function getRobotsConfig(options = {}) {
  return request({
    url: '/system/seo/robots',
    method: 'get',
    ...options
  })
}

/**
 * 保存Robots配置
 */
export function saveRobotsConfig(content, options = {}) {
  return request({
    url: '/system/seo/robots',
    method: 'put',
    data: { content },
    ...options
  })
}

/**
 * 提交站点地图
 */
export function generateSitemap(data = { engine: 'baidu', type: 'sitemap' }, options = {}) {
  return request({
    url: '/system/seo/submit',
    method: 'post',
    data,
    ...options
  })
}