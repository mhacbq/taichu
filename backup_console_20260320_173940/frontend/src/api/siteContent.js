import request from './request'

/**
 * 网站内容API - 前端使用
 */

/**
 * 获取首页完整内容
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
 * 获取用户评价列表
 */
export function getTestimonials() {
  return request({
    url: '/api/site/testimonials',
    method: 'get'
  })
}

/**
 * 获取FAQ列表
 * @param {string} category - 分类
 */
export function getFaqs(category) {
  return request({
    url: '/api/site/faqs',
    method: 'get',
    params: { category }
  })
}

/**
 * 获取塔罗牌阵列表
 */
export function getSpreads() {
  return request({
    url: '/api/site/spreads',
    method: 'get'
  })
}

/**
 * 获取问题模板
 * @param {string} category - 分类
 */
export function getQuestions(category) {
  return request({
    url: '/api/site/questions',
    method: 'get',
    params: { category }
  })
}