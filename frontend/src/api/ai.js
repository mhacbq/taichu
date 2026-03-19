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
 * AI文化测算分析（流式SSE）
 * @param {Object} cultural_data - 文化数据
 * @param {string} prompt - 自定义提示（可选）
 * @param {AbortSignal} signal - 取消信号（可选）
 */
export function analyzeCulturalAiStream(cultural_data, prompt = '', signal = null) {
  const url = `${import.meta.env.VITE_API_BASE_URL || ''}/api/ai/analyze/cultural/stream`
  
  return fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
    },
    body: JSON.stringify({ cultural_data, prompt }),
    signal
  })
}
