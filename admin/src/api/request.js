import axios from 'axios'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/stores/user'

const service = axios.create({
  baseURL: import.meta.env.VITE_APP_BASE_API || '/api/admin',
  timeout: 30000
})

let reloginPromptOpen = false

function shouldShowErrorMessage(config) {
  return config?.showErrorMessage !== false
}

function createRequestError(message, extra = {}) {
  const error = new Error(message || '请求失败')
  Object.assign(error, extra)
  return error
}

function sanitizeRequestPath(url) {
  if (typeof url !== 'string' || !url) {
    return ''
  }

  try {
    return new URL(url, window.location.origin).pathname
  } catch {
    return url.split('?')[0]
  }
}

function truncateMessage(message, maxLength = 160) {
  const value = typeof message === 'string' ? message.trim() : ''
  if (!value) {
    return ''
  }

  return value.length > maxLength ? `${value.slice(0, maxLength)}...` : value
}

function resolveDisplayMessage(code, message, fallback = '请求失败') {
  const normalizedCode = Number(code) || 0
  const trimmedMessage = typeof message === 'string' ? message.trim() : ''

  if (normalizedCode === 401) {
    return '登录已过期，请重新登录'
  }

  if (normalizedCode >= 500) {
    return '请求失败，请稍后重试'
  }

  return trimmedMessage || fallback
}

function createDebugPayload(config, code, httpStatus, message, requestId = '') {
  return {
    code: Number(code) || 0,
    httpStatus: Number(httpStatus) || 0,
    method: (config?.method || 'get').toUpperCase(),
    path: sanitizeRequestPath(config?.url || ''),
    requestId,
    message: truncateMessage(message)
  }
}

function debugRequestError(stage, payload) {
  if (!import.meta.env.DEV) {
    return
  }

  console.error(`[admin-request] ${stage}`, payload)
}

function promptRelogin() {
  if (reloginPromptOpen) {
    return
  }

  reloginPromptOpen = true
  ElMessageBox.confirm('登录已过期，请重新登录', '提示', {
    confirmButtonText: '重新登录',
    cancelButtonText: '取消',
    type: 'warning'
  }).then(() => {
    const userStore = useUserStore()
    userStore.logout()
  }).catch(() => {
    // 用户取消时不额外提示
  }).finally(() => {
    reloginPromptOpen = false
  })
}

// 请求拦截器
service.interceptors.request.use(
  config => {
    const userStore = useUserStore()
    if (userStore.token) {
      config.headers.Authorization = 'Bearer ' + userStore.token
    }
    return config
  },
  error => {
    debugRequestError('request_setup_failed', createDebugPayload(
      error?.config,
      error?.code,
      0,
      error?.message
    ))

    return Promise.reject(createRequestError('请求发送失败，请稍后重试', {
      code: Number(error?.code) || 500,
      requestConfig: error?.config
    }))
  }
)

// 响应拦截器
service.interceptors.response.use(
  response => {
    const res = response.data

    if (res.code !== 200) {
      const requestId = response.headers?.['x-request-id'] || res?.request_id || ''
      const displayMessage = resolveDisplayMessage(res.code, res.message)

      debugRequestError('business_response_error', createDebugPayload(
        response.config,
        res.code,
        response.status,
        res.message,
        requestId
      ))

      if (shouldShowErrorMessage(response.config)) {
        ElMessage.error(displayMessage)
      }

      if (Number(res.code) === 401) {
        promptRelogin()
      }

      return Promise.reject(createRequestError(displayMessage, {
        code: Number(res.code) || 500,
        httpStatus: response.status,
        response: res,
        requestConfig: response.config,
        rawMessage: truncateMessage(res.message)
      }))
    }

    return res
  },
  error => {
    const responseData = error?.response?.data || null
    const errorCode = Number(responseData?.code || error?.response?.status || error?.code) || 500
    const requestId = error?.response?.headers?.['x-request-id'] || responseData?.request_id || ''
    const displayMessage = resolveDisplayMessage(errorCode, responseData?.message || error?.message)

    debugRequestError('http_response_error', createDebugPayload(
      error?.config,
      errorCode,
      error?.response?.status,
      responseData?.message || error?.message,
      requestId
    ))

    if (shouldShowErrorMessage(error?.config)) {
      ElMessage.error(displayMessage)
    }

    if (errorCode === 401) {
      promptRelogin()
    }

    return Promise.reject(createRequestError(displayMessage, {
      code: errorCode,
      httpStatus: Number(error?.response?.status || 0) || errorCode,
      response: responseData || error?.response,
      requestConfig: error?.config,
      rawMessage: truncateMessage(responseData?.message || error?.message)
    }))
  }
)

export default service
