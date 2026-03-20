/**
 * API请求缓存拦截器
 * 自动缓存 GET 请求，支持按规则配置 TTL
 */

import axios from 'axios'
import { cache, requestDeduper } from './cache'

// 请求缓存配置
const CACHE_CONFIG = {
  // 默认缓存时间（毫秒）
  defaultTTL: 5 * 60 * 1000, // 5分钟

  // 特定接口缓存配置
  rules: [
    // 每日运势 - 缓存1小时
    { pattern: /\/api\/daily\/fortune/, ttl: 60 * 60 * 1000 },
    // 系统配置 - 缓存30分钟
    { pattern: /\/api\/config/, ttl: 30 * 60 * 1000 },
    // 塔罗牌列表 - 缓存1天
    { pattern: /\/api\/tarot\/cards/, ttl: 24 * 60 * 60 * 1000 },
    // 用户基础信息 - 缓存10分钟
    { pattern: /\/api\/user\/profile/, ttl: 10 * 60 * 1000 },
    // VIP配置 - 缓存1小时
    { pattern: /\/api\/vip\/config/, ttl: 60 * 60 * 1000 },
    // 积分任务 - 缓存30分钟
    { pattern: /\/api\/points\/tasks/, ttl: 30 * 60 * 1000 }
  ],

  // 不缓存的请求
  exclude: [
    /\/api\/auth/,
    /\/api\/upload/,
    /\/api\/order\/create/,
    /\/api\/pay/
  ]
}

/**
 * 生成缓存键
 */
const generateCacheKey = (config) => {
  const { url = '', method = 'get', params, data } = config
  const keyData = { url, method: method.toLowerCase(), params, data }
  return `api_${url}_${JSON.stringify(keyData)}`
}

/**
 * 检查是否应该缓存
 */
const shouldCache = (config) => {
  const method = config.method?.toLowerCase() || 'get'
  if (method !== 'get') {
    return false
  }

  const url = config.url || ''
  if (!url) {
    return false
  }

  if (config.noCache) {
    return false
  }

  return !CACHE_CONFIG.exclude.some(pattern => pattern.test(url))
}

/**
 * 获取缓存时间
 */
const getCacheTTL = (url = '') => {
  const matchedRule = CACHE_CONFIG.rules.find(rule => rule.pattern.test(url))
  return matchedRule?.ttl || CACHE_CONFIG.defaultTTL
}

/**
 * 创建带缓存的 axios 实例
 */
export const createCachedAxios = (options = {}) => {
  const instance = axios.create(options)

  instance.interceptors.request.use(
    (config) => {
      if (!shouldCache(config)) {
        return config
      }

      const cacheKey = generateCacheKey(config)
      const cached = cache.get(cacheKey)

      if (cached) {
        config.adapter = () => Promise.resolve({
          data: cached,
          status: 200,
          statusText: 'OK',
          headers: {},
          config,
          request: null,
          fromCache: true
        })
        return config
      }

      config.cacheKey = cacheKey
      return config
    },
    (error) => Promise.reject(error)
  )

  instance.interceptors.response.use(
    (response) => {
      if (response.fromCache) {
        return response
      }

      const cacheKey = response.config?.cacheKey
      const url = response.config?.url || ''
      if (cacheKey && response.data?.code === 200) {
        cache.set(cacheKey, response.data, getCacheTTL(url))
      }

      return response
    },
    (error) => Promise.reject(error)
  )

  return instance
}

/**
 * 清空 API 缓存
 */
export const clearAPICache = () => {
  const before = cache.getInfo()
  cache.clear()

  return {
    before,
    after: cache.getInfo()
  }
}

/**
 * 预加载 API 数据
 */
export const preloadAPI = async (apiList = [], client = createCachedAxios()) => {
  const results = []

  for (const api of apiList) {
    const url = api?.url || ''
    const params = api?.params || {}

    if (!url) {
      results.push({
        url,
        success: false,
        error: '缺少预加载地址'
      })
      continue
    }

    const requestKey = `preload_${url}_${JSON.stringify(params)}`

    try {
      const result = await requestDeduper.dedupe(requestKey, () => client.get(url, { params }))
      results.push({ url, success: true, data: result.data })
    } catch (error) {
      results.push({
        url,
        success: false,
        error: error?.message || '预加载失败'
      })
    }
  }

  return results
}

export { CACHE_CONFIG }
export default createCachedAxios
