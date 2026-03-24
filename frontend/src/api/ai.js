import request from './request'

/**
 * AI分析API
 */

/**
 * AI文化测算分析（非流式）
 * @param {Object} cultural_data - 文化数据
 * @param {string} prompt - 自定义提示（可选）
 * @param {AbortSignal} signal - 取消信号（可选）
 */
export function analyzeCulturalAi(cultural_data, prompt = '', signal = null) {
  return request({
    url: '/api/ai/analyze/cultural',
    method: 'post',
    data: { cultural_data, prompt },
    signal
  })
}

/**
 * AI八字分析（非流式）- analyzeCulturalAi 的别名，供 Bazi.vue 使用
 * @param {Object} bazi - 八字数据
 * @param {string} prompt - 自定义提示（可选）
 * @param {AbortSignal} signal - 取消信号（可选）
 * @param {Array} dayun - 大运数据（可选，本地算法排出）
 * @param {number} recordId - 排盘记录ID（可选，用于缓存AI结果）
 */
export function analyzeBaziAi(bazi, prompt = '', signal = null, dayun = [], recordId = 0) {
  return request({
    url: '/api/ai/analyze',
    method: 'post',
    data: { bazi, prompt, dayun, record_id: recordId },
    signal
  })
}

/**
 * AI八字分析（流式SSE）- analyzeCulturalAiStream 的别名，供 Bazi.vue 使用
 * @param {Object} bazi - 八字数据
 * @param {string} prompt - 自定义提示（可选）
 * @param {AbortSignal} signal - 取消信号（可选）
 * @param {Array} dayun - 大运数据（可选，本地算法排出）
 * @param {number} recordId - 排盘记录ID（可选，用于缓存AI结果）
 */
export function analyzeBaziAiStream(bazi, prompt = '', signal = null, dayun = [], recordId = 0) {
  const token = localStorage.getItem('token') || ''
  return fetch('/api/ai/analyze-stream', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : ''
    },
    body: JSON.stringify({ bazi, prompt, dayun, record_id: recordId }),
    signal
  })
}

/**
 * AI大运评分（排盘时同步调用）
 * @param {Object} bazi - 八字数据
 * @param {Array} dayun - 本地排出的大运数据
 * @param {AbortSignal} signal - 取消信号（可选）
 * @param {number} recordId - 排盘记录ID（可选，用于缓存评分）
 */
export function scoreDayunAi(bazi, dayun, signal = null, recordId = 0) {
  return request({
    url: '/api/ai/score-dayun',
    method: 'post',
    data: { bazi, dayun, record_id: recordId },
    signal
  })
}

/**
 * 获取排盘记录的 AI 分析缓存
 * @param {number} recordId - 排盘记录ID
 */
export function getAiRecord(recordId) {
  return request({
    url: '/api/ai/record',
    method: 'get',
    params: { id: recordId }
  })
}
