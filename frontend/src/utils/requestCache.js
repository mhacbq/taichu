/**
 * API请求缓存拦截器
 * 自动缓存GET请求，支持配置化
 */

import { cache, requestDeduper } from './cache'
import axios from 'axios'

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
    { pattern: /\/api\/points\/tasks/, ttl: 30 * 60 * 1000 },
  ],
  
  // 不缓存的请求
  exclude: [
    /\/api\/auth/,
    /\/api\/upload/,
    /\/api\/order\/create/,
    /\/api\/pay/,
  ]
}

/**
 * 生成缓存键
 */
const generateCacheKey = (config) => {
  const { url, method, params, data } = config
  const keyData = { url, method, params, data }
  return `api_${url}_${JSON.stringify(keyData)}`
}

/**
 * 检查是否应该缓存
 */
const shouldCache = (config) => {
  // 只缓存GET请求
  if (config.method?.toLowerCase() !== 'get') {
    return false
  }
  
  // 检查排除规则
  for (const pattern of CACHE_CONFIG.exclude) {
    if (pattern.test(config.url)) {
      return false
    }
  }
  
  // 检查是否强制不缓存
  if (config.noCache) {
    return false
  }
  
  return true
}

/**
 * 获取缓存时间
 */
const getCacheTTL = (url) => {
  for (const rule of CACHE_CONFIG.rules) {
    if (rule.pattern.test(url)) {
      return rule.ttl
    }
  }
  return CACHE_CONFIG.defaultTTL
}

/**
 * 创建带缓存的axios实例
 */
export const createCachedAxios = () => {
  const instance = axios.create()
  
  // 请求拦截器
  instance.interceptors.request.use(
    async (config) => {
      if (!shouldCache(config)) {
        return config
      }
      
      const cacheKey = generateCacheKey(config)
      
      // 检查缓存
      const cached = cache.get(cacheKey)
      if (cached) {
        // 使用适配器直接返回缓存数据
        config.adapter = () => {
          return Promise.resolve({
            data: cached,
            status: 200,
            statusText: 'OK',
            headers: {},
            config,
            fromCache: true
          })
        }
      } else {
        // 标记缓存键，供响应拦截器使用
        config.cacheKey = cacheKey
      }
      
      return config
    },
    (error) => Promise.reject(error)
  )
  
  // 响应拦截器
  instance.interceptors.response.use(
    (response) => {
      // 如果来自缓存，直接返回
      if (response.fromCache) {
        return response
      }
      
      // 缓存新数据
      if (response.config.cacheKey && response.data?.code === 200) {
        const ttl = getCacheTTL(response.config.url)
        cache.set(response.config.cacheKey, response.data, ttl)
      }
      
      return response
    },
    (error) => Promise.reject(error)
  )
  
  return instance
}

/**
 * 清除特定URL的缓存
 */
export const clearAPICache = (urlPattern) => {
  const info = cache.getInfo()
  console.log(`[Cache] Before clear: ${JSON.stringify(info)}`)
  
  // 这里可以实现更精确的缓存清除逻辑
  // 目前简单清空所有API缓存
  if (!urlPattern) {
    cache.clear()
  }
  
  console.log(`[Cache] After clear: ${JSON.stringify(cache.getInfo())}`)
}

/**
 * 预加载API数据
 */
export const preloadAPI = async (apiList) => {
  const results = []
  
  for (const api of apiList) {
    try {
      const result = await instance.get(api.url, { params: api.params })
      results.push({ url: api.url, success: true, data: result.data })
    } catch (error) {
      results.push({ url: api.url, success: false, error: error.message })
    }
  }
  
  return results
}

// 导出配置供外部使用
export { CACHE_CONFIG }
export default createCachedAxios
