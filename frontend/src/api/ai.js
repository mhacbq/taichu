import request from './request'

/**
 * AI解盘API
 */

/**
 * AI八字解盘（非流式）
 * @param {Object} bazi - 八字数据
 * @param {string} prompt - 自定义提示（可选）
 */
export function analyzeBaziAi(bazi, prompt = '') {
  return request({
    url: '/api/ai/analyze/bazi',
    method: 'post',
    data: { bazi, prompt }
  })
}

/**
 * AI八字解盘（流式SSE）
 * @param {Object} bazi - 八字数据
 * @param {string} prompt - 自定义提示（可选）
 */
export function analyzeBaziAiStream(bazi, prompt = '') {
  const url = `${import.meta.env.VITE_API_BASE_URL || ''}/api/ai/analyze/bazi/stream`
  
  return fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${localStorage.getItem('token') || ''}`
    },
    body: JSON.stringify({ bazi, prompt })
  })
}
