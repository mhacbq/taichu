import axios from 'axios'
import { ElMessage } from 'element-plus'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'

// 重试配置
const RETRY_CONFIG = {
  // 默认关闭自动重试，避免首次失败后重复请求
  maxRetries: 0,
  retryDelay: 1000,
  retryableStatuses: [408, 429, 500, 502, 503, 504],
  retryableErrors: ['ECONNABORTED', 'ETIMEDOUT', 'ENOTFOUND', 'ECONNREFUSED', 'NETWORK_ERROR']
}

const request = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
})

request.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    // 为重试请求设置标识
    if (!config.__retryCount) {
      config.__retryCount = 0
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

request.interceptors.response.use(
  (response) => {
    return response.data
  },
  async (error) => {
    const config = error.config
    
    if (!config) {
      return Promise.reject(error)
    }
    
    // 检查是否需要重试
    const shouldRetry = checkShouldRetry(error)
    
    const enableRetry = config.retry === true

    if (enableRetry && shouldRetry && config.__retryCount < RETRY_CONFIG.maxRetries) {
      config.__retryCount++
      
      // 显示重试提示
      if (config.__retryCount === 1) {
        ElMessage.warning('网络不稳定，正在尝试重连...')
      }
      
      // 延迟重试
      const delay = RETRY_CONFIG.retryDelay * config.__retryCount
      await new Promise(resolve => setTimeout(resolve, delay))
      
      console.log(`[API Retry] ${config.url} - Attempt ${config.__retryCount}/${RETRY_CONFIG.maxRetries}`)
      
      return request(config)
    }
    
    // 处理不同类型的错误
    handleApiError(error)
    
    // 401 未授权，清除登录状态
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('userInfo')
      window.location.href = '/'
    }
    
    return Promise.reject(error)
  }
)

// 检查是否应该重试
function checkShouldRetry(error) {
  // 用户主动取消的请求不重试
  if (axios.isCancel(error)) {
    return false
  }
  
  // 检查 HTTP 状态码
  if (error.response) {
    const status = error.response.status
    return RETRY_CONFIG.retryableStatuses.includes(status)
  }
  
  // 检查网络错误类型
  if (error.code && RETRY_CONFIG.retryableErrors.includes(error.code)) {
    return true
  }
  
  // 超时错误
  if (error.message && error.message.includes('timeout')) {
    return true
  }
  
  // 网络错误
  if (error.message && error.message.includes('Network Error')) {
    return true
  }
  
  return false
}

// 处理API错误
function handleApiError(error) {
  let message = '请求失败，请稍后重试'
  
  if (error.response) {
    // 服务器返回了错误响应
    const status = error.response.status
    const data = error.response.data
    
    switch (status) {
      case 400:
        message = data?.message || '请求参数错误'
        break
      case 401:
        message = '登录已过期，请重新登录'
        break
      case 403:
        message = data?.message || '权限不足'
        break
      case 404:
        message = '请求的资源不存在'
        break
      case 429:
        message = '请求过于频繁，请稍后再试'
        break
      case 500:
        message = '服务器错误，请稍后重试'
        break
      case 502:
      case 503:
      case 504:
        message = '服务器暂时不可用，请稍后重试'
        break
      default:
        message = data?.message || `请求失败 (${status})`
    }
  } else if (error.request) {
    // 请求发出但没有收到响应
    if (error.code === 'ECONNABORTED') {
      message = '请求超时，请检查网络连接'
    } else {
      message = '网络连接失败，请检查网络设置'
    }
  } else {
    // 请求配置错误
    message = error.message || '请求配置错误'
  }
  
  // 只在非重试状态下显示错误
  if (!error.config || error.config.__retryCount === 0) {
    ElMessage.error(message)
  }
}

export default request
