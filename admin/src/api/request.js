import axios from 'axios'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useUserStore } from '@/stores/user'

const service = axios.create({
  baseURL: import.meta.env.VITE_APP_BASE_API || '/api/admin',
  timeout: 30000
})

function shouldShowErrorMessage(config) {
  return config?.showErrorMessage !== false
}

function createRequestError(message, extra = {}) {
  const error = new Error(message || '请求失败')
  Object.assign(error, extra)
  return error
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
    console.error(error)
    return Promise.reject(error)
  }
)

// 响应拦截器
service.interceptors.response.use(
  response => {
    const res = response.data

    if (res.code !== 200) {
      if (shouldShowErrorMessage(response.config)) {
        ElMessage.error(res.message || '请求失败')
      }

      if (res.code === 401) {
        ElMessageBox.confirm('登录已过期，请重新登录', '提示', {
          confirmButtonText: '重新登录',
          cancelButtonText: '取消',
          type: 'warning'
        }).then(() => {
          const userStore = useUserStore()
          userStore.logout()
        })
      }

      return Promise.reject(createRequestError(res.message || '请求失败', {
        code: Number(res.code) || 500,
        httpStatus: response.status,
        response: res,
        requestConfig: response.config
      }))
    }

    return res
  },
  error => {
    console.error('err', error)

    const responseData = error?.response?.data || null
    const message = responseData?.message || error.message || '请求失败'
    const errorCode = Number(responseData?.code || error?.response?.status || error.code) || 500

    if (shouldShowErrorMessage(error.config)) {
      ElMessage.error(message)
    }

    return Promise.reject(createRequestError(message, {
      code: errorCode,
      httpStatus: Number(error?.response?.status || 0) || errorCode,
      response: responseData || error.response,
      requestConfig: error.config
    }))
  }
)

export default service
