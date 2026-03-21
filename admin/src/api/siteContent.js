import request from './request'

/**
 * 网站内容管理API
 */

// ============ 前台接口 ============

/**
 * 获取首页内容
 */
export function getHomeContent() {
  return request({
    url: '/site/home',
    method: 'get'
  })
}

/**
 * 获取页面内容
 * @param {string} page - 页面标识
 */
export function getPageContent(page) {
  return request({
    url: '/site/page',
    method: 'get',
    params: { page }
  })
}

/**
 * 获取用户评价列表（前台）
 */
export function getTestimonials() {
  return request({
    url: '/site/testimonials',
    method: 'get'
  })
}

/**
 * 获取FAQ列表（前台）
 */
export function getFaqs(category) {
  return request({
    url: '/site/faqs',
    method: 'get',
    params: { category }
  })
}

/**
 * 获取塔罗牌阵列表（前台）
 */
export function getSpreads() {
  return request({
    url: '/site/spreads',
    method: 'get'
  })
}

/**
 * 获取问题模板（前台）
 */
export function getQuestions(category) {
  return request({
    url: '/site/questions',
    method: 'get',
    params: { category }
  })
}

/**
 * 获取所有枚举选项
 */
export function getEnums() {
  return request({
    url: '/site/enums',
    method: 'get'
  })
}

// ============ 后台管理接口 ============

/**
 * 获取内容列表
 */
export function getContentList(params) {
  return request({
    url: '/site/content/list',
    method: 'get',
    params
  })
}

/**
 * 保存内容
 */
export function saveContent(data) {
  return request({
    url: '/site/content/save',
    method: 'post',
    data
  })
}

/**
 * 批量更新页面内容
 */
export function batchUpdateContent(page, contents) {
  return request({
    url: '/site/content/batch',
    method: 'post',
    data: { page, contents }
  })
}

/**
 * 删除内容
 */
export function deleteContent(id) {
  return request({
    url: `/site/content/${id}`,
    method: 'delete'
  })
}

/**
 * 获取评价列表（后台）
 */
export function getTestimonialList(params) {
  return request({
    url: '/site/testimonials',
    method: 'get',
    params
  })
}

/**
 * 保存评价
 */
export function saveTestimonial(data) {
  return request({
    url: '/site/testimonials',
    method: 'post',
    data
  })
}

/**
 * 删除评价
 */
export function deleteTestimonial(id) {
  return request({
    url: `/site/testimonials/${id}`,
    method: 'delete'
  })
}

/**
 * 获取FAQ列表（后台）
 */
export function getFaqList(params) {
  return request({
    url: '/site/faqs',
    method: 'get',
    params
  })
}

/**
 * 保存FAQ
 */
export function saveFaq(data) {
  return request({
    url: '/site/faqs',
    method: 'post',
    data
  })
}

/**
 * 删除FAQ
 */
export function deleteFaq(id) {
  return request({
    url: `/site/faqs/${id}`,
    method: 'delete'
  })
}

/**
 * 获取塔罗牌列表
 */
export function getTarotCardList(params) {
  return request({
    url: '/site/tarot-cards',
    method: 'get',
    params
  })
}

/**
 * 保存塔罗牌
 */
export function saveTarotCard(data) {
  return request({
    url: '/site/tarot-cards',
    method: 'post',
    data
  })
}

/**
 * 获取牌阵列表（后台）
 */
export function getSpreadList(params) {
  return request({
    url: '/site/spreads',
    method: 'get',
    params
  })
}

/**
 * 保存牌阵
 */
export function saveSpread(data) {
  return request({
    url: '/site/spreads',
    method: 'post',
    data
  })
}

/**
 * 获取问题模板列表（后台）
 */
export function getQuestionList(params) {
  return request({
    url: '/site/questions',
    method: 'get',
    params
  })
}

/**
 * 保存问题模板
 */
export function saveQuestion(data) {
  return request({
    url: '/site/questions',
    method: 'post',
    data
  })
}

/**
 * 获取运势模板列表
 */
export function getFortuneTemplateList(params) {
  return request({
    url: '/site/fortune-templates',
    method: 'get',
    params
  })
}

/**
 * 保存运势模板
 */
export function saveFortuneTemplate(data) {
  return request({
    url: '/site/fortune-templates',
    method: 'post',
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

