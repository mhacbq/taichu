import request from './request'

/**
 * AI提示词管理API
 */

/**
 * 获取提示词列表
 * @param {Object} params - 查询参数
 */
export function getPromptList(params) {
  return request({
    url: '/api/admin/ai-prompts/list',
    method: 'get',
    params
  })
}

/**
 * 获取提示词详情
 * @param {number} id - 提示词ID
 */
export function getPromptDetail(id) {
  return request({
    url: `/api/admin/ai-prompts/detail/${id}`,
    method: 'get'
  })
}

/**
 * 保存提示词
 * @param {Object} data - 提示词数据
 */
export function savePrompt(data) {
  return request({
    url: '/api/admin/ai-prompts/save',
    method: 'post',
    data
  })
}

/**
 * 删除提示词
 * @param {number} id - 提示词ID
 */
export function deletePrompt(id) {
  return request({
    url: `/api/admin/ai-prompts/${id}`,
    method: 'delete'
  })
}

/**
 * 设置默认提示词
 * @param {number} id - 提示词ID
 */
export function setDefaultPrompt(id) {
  return request({
    url: `/api/admin/ai-prompts/${id}/default`,
    method: 'post'
  })
}

/**
 * 预览提示词效果
 * @param {number} id - 提示词ID
 * @param {Object} variables - 测试变量
 */
export function previewPrompt(id, variables = {}) {
  return request({
    url: `/api/admin/ai-prompts/${id}/preview`,
    method: 'post',
    data: { variables }
  })
}

/**
 * 复制提示词
 * @param {number} id - 提示词ID
 */
export function duplicatePrompt(id) {
  return request({
    url: `/api/admin/ai-prompts/${id}/duplicate`,
    method: 'post'
  })
}

/**
 * 获取提示词类型列表
 */
export function getPromptTypes() {
  return request({
    url: '/api/admin/ai-prompts/types',
    method: 'get'
  })
}
