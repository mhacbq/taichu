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
 */
export function analyzeBaziAi(bazi, prompt = '', signal = null) {
  return analyzeCulturalAi(bazi, prompt, signal)
}

/**
 * AI八字分析（流式SSE）- analyzeCulturalAiStream 的别名，供 Bazi.vue 使用
 * @param {Object} bazi - 八字数据
 * @param {string} prompt - 自定义提示（可选）
 * @param {AbortSignal} signal - 取消信号（可选）
 */
export function analyzeBaziAiStream(bazi, prompt = '', signal = null) {
  return analyzeCulturalAiStream(bazi, prompt, signal)
}

