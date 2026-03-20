/**
 * 缓存管理工具
 * 支持localStorage和内存缓存，带过期时间
 */

class CacheManager {
  constructor() {
    this.memoryCache = new Map()
    this.defaultTTL = 5 * 60 * 1000 // 默认5分钟
  }

  /**
   * 设置缓存
   * @param {string} key - 缓存键
   * @param {any} value - 缓存值
   * @param {number} ttl - 过期时间（毫秒）
   * @param {string} type - 缓存类型：'memory' | 'storage' | 'both'
   */
  set(key, value, ttl = this.defaultTTL, type = 'both') {
    const expires = Date.now() + ttl
    const data = { value, expires }

    if (type === 'memory' || type === 'both') {
      this.memoryCache.set(key, data)
    }

    if (type === 'storage' || type === 'both') {
      try {
        localStorage.setItem(`cache_${key}`, JSON.stringify(data))
      } catch (e) {
        console.warn('localStorage缓存失败:', e)
      }
    }
  }

  /**
   * 获取缓存
   * @param {string} key - 缓存键
   * @param {string} type - 优先从哪获取：'memory' | 'storage'
   * @returns {any|null}
   */
  get(key, type = 'memory') {
    // 优先从内存获取
    if (type === 'memory') {
      const memoryData = this.memoryCache.get(key)
      if (memoryData && memoryData.expires > Date.now()) {
        return memoryData.value
      }
      this.memoryCache.delete(key)
    }

    // 从localStorage获取
    try {
      const storageData = localStorage.getItem(`cache_${key}`)
      if (storageData) {
        const parsed = JSON.parse(storageData)
        if (parsed.expires > Date.now()) {
          // 同步到内存
          this.memoryCache.set(key, parsed)
          return parsed.value
        }
        localStorage.removeItem(`cache_${key}`)
      }
    } catch (e) {
      console.warn('localStorage读取失败:', e)
    }

    return null
  }

  /**
   * 删除缓存
   * @param {string} key - 缓存键
   */
  delete(key) {
    this.memoryCache.delete(key)
    try {
      localStorage.removeItem(`cache_${key}`)
    } catch (e) {
      console.warn('localStorage删除失败:', e)
    }
  }

  /**
   * 清空所有缓存
   */
  clear() {
    this.memoryCache.clear()
    try {
      const keys = Object.keys(localStorage)
      keys.forEach(key => {
        if (key.startsWith('cache_')) {
          localStorage.removeItem(key)
        }
      })
    } catch (e) {
      console.warn('localStorage清空失败:', e)
    }
  }

  /**
   * 获取缓存信息
   */
  getInfo() {
    let storageCount = 0
    try {
      const keys = Object.keys(localStorage)
      storageCount = keys.filter(key => key.startsWith('cache_')).length
    } catch (e) {}

    return {
      memory: this.memoryCache.size,
      storage: storageCount
    }
  }
}

// 创建全局缓存实例
export const cache = new CacheManager()

/**
 * API响应缓存装饰器
 * @param {number} ttl - 缓存时间（毫秒）
 * @param {Function} keyGenerator - 缓存键生成函数
 */
export const withCache = (ttl = 60000, keyGenerator = null) => {
  return function (target, propertyKey, descriptor) {
    const originalMethod = descriptor.value

    descriptor.value = async function (...args) {
      const cacheKey = keyGenerator 
        ? keyGenerator(...args) 
        : `${propertyKey}_${JSON.stringify(args)}`

      // 尝试从缓存获取
      const cached = cache.get(cacheKey)
      if (cached !== null) {
        return cached
      }

      // 执行原始方法
      const result = await originalMethod.apply(this, args)
      
      // 缓存结果
      cache.set(cacheKey, result, ttl)

      return result
    }

    return descriptor
  }
}

/**
 * 请求去重工具
 * 防止相同请求并发发送
 */
class RequestDeduper {
  constructor() {
    this.pendingRequests = new Map()
  }

  async dedupe(key, requestFn) {
    // 如果有进行中的相同请求，返回其Promise
    if (this.pendingRequests.has(key)) {
      return this.pendingRequests.get(key)
    }

    // 创建新请求
    const promise = requestFn().finally(() => {
      this.pendingRequests.delete(key)
    })

    this.pendingRequests.set(key, promise)
    return promise
  }
}

export const requestDeduper = new RequestDeduper()

export default cache
