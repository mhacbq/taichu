import axios from 'axios'
import { ElMessage } from 'element-plus'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'

const adminRequest = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
})

adminRequest.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('adminToken')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

adminRequest.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (!error.config) {
      return Promise.reject(error)
    }

    const status = error.response?.status
    const data = error.response?.data

    // 401 未授权：清理管理端登录态，跳转管理端登录页
    if (status === 401) {
      localStorage.removeItem('adminToken')
      localStorage.removeItem('adminUserInfo')
      window.location.href = '/maodou/login'
      return Promise.reject(error)
    }

    // 显示错误提示
    const messageMap = {
      400: data?.message || '请求参数错误',
      403: data?.message || '权限不足',
      404: '请求的资源不存在',
      429: '请求过于频繁，请稍后再试',
      500: '服务器错误，请稍后重试',
    }
    const message = messageMap[status]
      || (status >= 502 && status <= 504 ? '服务器暂时不可用，请稍后重试' : null)
      || data?.message
      || '请求失败，请稍后重试'

    if (!error.config?.skipGlobalError) {
      ElMessage.error(message)
    }

    return Promise.reject(error)
  }
)

export default adminRequest
