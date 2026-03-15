import request from './request'

/**
 * AI提示词API（前端使用）
 */

/**
 * 获取八字解盘的可用提示词列表
 */
export function getBaziPrompts() {
  return request({
    url: '/api/ai-prompts/list',
    method: 'get',
    params: { type: 'bazi', is_enabled: 1 }
  })
}

/**
 * 获取默认提示词
 * @param {string} type - 提示词类型
 */
export function getDefaultPrompt(type) {
  return request({
    url: `/api/ai-prompts/default/${type}`,
    method: 'get'
  })
}
