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
    url: '/api/site/home',
    method: 'get'
  })
}

/**
 * 获取页面内容
 * @param {string} page - 页面标识
 */
export function getPageContent(page) {
  return request({
    url: '/api/site/page',
    method: 'get',
    params: { page }
  })
}

/**
 * 获取用户评价列表（前台）
 */
export function getTestimonials() {
  return request({
    url: '/api/site/testimonials',
    method: 'get'
  })
}

/**
 * 获取FAQ列表（前台）
 */
export function getFaqs(category) {
  return request({
    url: '/api/site/faqs',
    method: 'get',
    params: { category }
  })
}

/**
 * 获取塔罗牌阵列表（前台）
 */
export function getSpreads() {
  return request({
    url: '/api/site/spreads',
    method: 'get'
  })
}

/**
 * 获取问题模板（前台）
 */
export function getQuestions(category) {
  return request({
    url: '/api/site/questions',
    method: 'get',
    params: { category }
  })
}

/**
 * 获取所有枚举选项
 */
export function getEnums() {
  return request({
    url: '/api/site/enums',
    method: 'get'
  })
}

// ============ 后台管理接口 ============

/**
 * 获取内容列表
 */
export function getContentList(params) {
  return request({
    url: '/api/admin/site/content/list',
    method: 'get',
    params
  })
}

/**
 * 保存内容
 */
export function saveContent(data) {
  return request({
    url: '/api/admin/site/content/save',
    method: 'post',
    data
  })
}

/**
 * 批量更新页面内容
 */
export function batchUpdateContent(page, contents) {
  return request({
    url: '/api/admin/site/content/batch',
    method: 'post',
    data: { page, contents }
  })
}

/**
 * 删除内容
 */
export function deleteContent(id) {
  return request({
    url: `/api/admin/site/content/${id}`,
    method: 'delete'
  })
}

/**
 * 获取评价列表（后台）
 */
export function getTestimonialList(params) {
  return request({
    url: '/api/admin/site/testimonials',
    method: 'get',
    params
  })
}

/**
 * 保存评价
 */
export function saveTestimonial(data) {
  return request({
    url: '/api/admin/site/testimonials',
    method: 'post',
    data
  })
}

/**
 * 删除评价
 */
export function deleteTestimonial(id) {
  return request({
    url: `/api/admin/site/testimonials/${id}`,
    method: 'delete'
  })
}

/**
 * 获取FAQ列表（后台）
 */
export function getFaqList(params) {
  return request({
    url: '/api/admin/site/faqs',
    method: 'get',
    params
  })
}

/**
 * 保存FAQ
 */
export function saveFaq(data) {
  return request({
    url: '/api/admin/site/faqs',
    method: 'post',
    data
  })
}

/**
 * 删除FAQ
 */
export function deleteFaq(id) {
  return request({
    url: `/api/admin/site/faqs/${id}`,
    method: 'delete'
  })
}

/**
 * 获取塔罗牌列表
 */
export function getTarotCardList(params) {
  return request({
    url: '/api/admin/site/tarot-cards',
    method: 'get',
    params
  })
}

/**
 * 保存塔罗牌
 */
export function saveTarotCard(data) {
  return request({
    url: '/api/admin/site/tarot-cards',
    method: 'post',
    data
  })
}

/**
 * 获取牌阵列表（后台）
 */
export function getSpreadList(params) {
  return request({
    url: '/api/admin/site/spreads',
    method: 'get',
    params
  })
}

/**
 * 保存牌阵
 */
export function saveSpread(data) {
  return request({
    url: '/api/admin/site/spreads',
    method: 'post',
    data
  })
}

/**
 * 获取问题模板列表（后台）
 */
export function getQuestionList(params) {
  return request({
    url: '/api/admin/site/questions',
    method: 'get',
    params
  })
}

/**
 * 保存问题模板
 */
export function saveQuestion(data) {
  return request({
    url: '/api/admin/site/questions',
    method: 'post',
    data
  })
}

/**
 * 获取运势模板列表
 */
export function getFortuneTemplateList(params) {
  return request({
    url: '/api/admin/site/fortune-templates',
    method: 'get',
    params
  })
}

/**
 * 保存运势模板
 */
export function saveFortuneTemplate(data) {
  return request({
    url: '/api/admin/site/fortune-templates',
    method: 'post',
    data
  })
}

// ============ SEO 管理接口 ============

/**
 * 获取SEO配置列表
 */
export function getSeoConfigs() {
  return request({
    url: '/api/admin/site/seo/list',
    method: 'get'
  })
}

/**
 * 保存SEO配置
 */
export function saveSeoConfig(data) {
  return request({
    url: '/api/admin/site/seo/save',
    method: 'post',
    data
  })
}

/**
 * 删除SEO配置
 */
export function deleteSeoConfig(route) {
  return request({
    url: '/api/admin/site/seo/delete',
    method: 'post',
    data: { route }
  })
}

/**
 * 获取Robots配置
 */
export function getRobotsConfig() {
  return request({
    url: '/api/admin/site/seo/robots',
    method: 'get'
  })
}

/**
 * 保存Robots配置
 */
export function saveRobotsConfig(content) {
  return request({
    url: '/api/admin/site/seo/robots',
    method: 'post',
    data: { content }
  })
}

/**
 * 生成站点地图
 */
export function generateSitemap() {
  return request({
    url: '/api/admin/site/seo/sitemap',
    method: 'post'
  })
}
