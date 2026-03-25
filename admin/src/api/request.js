import axios from 'axios'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/stores/user'

const service = axios.create({
  baseURL: import.meta.env.VITE_APP_BASE_API || '/api/maodou',
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

function isAuthLoginRequest(config) {
  return sanitizeRequestPath(config?.url || '').endsWith('/auth/login')
}

function isTimeoutError(code, message = '') {
  const normalizedCode = String(code || '').toUpperCase()
  const normalizedMessage = String(message || '').toLowerCase()

  return normalizedCode === 'ECONNABORTED'
    || normalizedCode === 'ETIMEDOUT'
    || normalizedMessage.includes('timeout')
}

function resolveDisplayMessage(code, message, fallback = '请求失败', options = {}) {
  const normalizedCode = Number(code) || 0
  const trimmedMessage = typeof message === 'string' ? message.trim() : ''
  const isLoginRequest = options.isLoginRequest === true
  const isNetworkError = options.isNetworkError === true
  const httpStatus = Number(options.httpStatus) || 0
  const transportCode = options.transportCode || ''

  if (normalizedCode === 401) {
    return isLoginRequest ? (trimmedMessage || '用户名或密码错误') : '登录已过期，请重新登录'
  }

  if (normalizedCode === 422) {
    return trimmedMessage || '请先补全请求信息'
  }

  if (isNetworkError) {
    if (isTimeoutError(transportCode, trimmedMessage)) {
      return '请求超时，请检查后台服务或网络后重试'
    }

    return '无法连接后台服务，请检查代理配置或确认 backend 已启动'
  }

  if (httpStatus === 502 || httpStatus === 503 || httpStatus === 504) {
    return '后台网关异常，请检查代理或服务状态'
  }

  if (normalizedCode >= 500 || httpStatus >= 500) {
    return trimmedMessage || '后台服务异常，请稍后重试'
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

/**
 * 开发环境：反向检测 API 调用是否来自非 api 层（views / components / stores / pages）
 * 只有调用栈中出现这些"非 api 层"帧时才触发警告，避免 utils 封装等场景误报
 */
const NON_API_LAYER_PATTERNS = [
  '/src/views/',
  '/src/components/',
  '/src/stores/',
  '/src/pages/',
  '/src/layouts/',
]

function warnIfCalledOutsideApiLayer(url) {
  if (!import.meta.env.DEV) return
  try {
    const stack = new Error().stack || ''
    const calledFromNonApiLayer = NON_API_LAYER_PATTERNS.some(p => stack.includes(p))
    if (calledFromNonApiLayer) {
      console.warn(
        `[admin-request] ⚠️ API 路径 "${url}" 在组件/页面/Store 中直接调用。\n` +
        '请将请求封装到 admin/src/api/ 对应文件中，禁止在组件/页面里硬编码路径。\n' +
        '参考 AGENTS.md 第 7 节「常见陷阱」第 7 条。',
        stack
      )
    }
  } catch {
    // 忽略 stack 解析失败
  }
}

// 请求拦截器
service.interceptors.request.use(
  config => {
    warnIfCalledOutsideApiLayer(config.url)
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
      code: 0,
      transportCode: typeof error?.code === 'string' ? error.code : '',
      requestConfig: error?.config,
      isNetworkError: false
    }))

  }
)

// 响应拦截器
service.interceptors.response.use(
  response => {
    const res = response.data

    if (res.code !== 200) {
      const requestId = response.headers?.['x-request-id'] || res?.request_id || ''
      const businessCode = Number(res.code) || 0
      const isLoginRequest = isAuthLoginRequest(response.config)
      const displayMessage = resolveDisplayMessage(res.code, res.message, '请求失败', {
        isLoginRequest,
        httpStatus: response.status
      })

      debugRequestError('business_response_error', createDebugPayload(
        response.config,
        businessCode,
        response.status,
        res.message,
        requestId
      ))

      if (shouldShowErrorMessage(response.config)) {
        ElMessage.error(displayMessage)
      }

      if (businessCode === 401 && !isLoginRequest) {
        promptRelogin()
      }

      return Promise.reject(createRequestError(displayMessage, {
        code: businessCode,
        businessCode,
        httpStatus: response.status,
        response: res,
        requestConfig: response.config,
        rawMessage: truncateMessage(res.message),
        requestId,
        transportCode: '',
        isNetworkError: false
      }))
    }


    return res
  },
  error => {
    const responseData = error?.response?.data || null
    const httpStatus = Number(error?.response?.status || 0) || 0
    const businessCode = Number(responseData?.code || 0) || 0
    const transportCode = typeof error?.code === 'string' ? error.code : ''
    const errorCode = businessCode || httpStatus || 0
    const requestId = error?.response?.headers?.['x-request-id'] || responseData?.request_id || ''
    const isLoginRequest = isAuthLoginRequest(error?.config)
    const isNetworkError = !error?.response
    const displayMessage = resolveDisplayMessage(errorCode, responseData?.message || error?.message, '请求失败', {
      isLoginRequest,
      isNetworkError,
      httpStatus,
      transportCode
    })

    debugRequestError('http_response_error', createDebugPayload(
      error?.config,
      errorCode,
      httpStatus,
      responseData?.message || error?.message,
      requestId
    ))

    if (shouldShowErrorMessage(error?.config)) {
      ElMessage.error(displayMessage)
    }

    if (errorCode === 401 && !isLoginRequest) {
      promptRelogin()
    }

    return Promise.reject(createRequestError(displayMessage, {
      code: errorCode,
      businessCode,
      httpStatus,
      response: responseData || error?.response,
      requestConfig: error?.config,
      rawMessage: truncateMessage(responseData?.message || error?.message),
      requestId,
      transportCode,
      isNetworkError
    }))
  }
)


export default service
