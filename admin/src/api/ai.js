import request from './request'

/**
 * AI配置管理API
 */

/**
 * 获取AI配置
 */
export function getAiConfig() {
  return request({
    url: '/api/admin/ai/config',
    method: 'get'
  })
}

/**
 * 保存AI配置
 * @param {Object} data - AI配置数据
 */
export function saveAiConfig(data) {
  return request({
    url: '/api/admin/ai/config',
    method: 'post',
    data
  })
}

/**
 * 测试AI连接
 * @param {Object} config - AI配置
 */
export function testAiConnection(config) {
  return request({
    url: '/api/admin/ai/test',
    method: 'post',
    data: { config }
  })
}
