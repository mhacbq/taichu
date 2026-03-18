import request from './request'

/**
 * AI配置管理API
 */

/**
 * 获取AI配置
 */
export function getAiConfig(options = {}) {
  return request({
    url: '/ai/config',
    method: 'get',
    ...options
  })
}

/**
 * 保存AI配置
 * @param {Object} data - AI配置数据
 */
export function saveAiConfig(data, options = {}) {
  return request({
    url: '/ai/config',
    method: 'post',
    data,
    ...options
  })
}


/**
 * 测试AI连接
 * @param {Object} config - AI配置
 */
export function testAiConnection(config, options = {}) {
  return request({
    url: '/ai/test',
    method: 'post',
    data: { config },
    ...options
  })
}
