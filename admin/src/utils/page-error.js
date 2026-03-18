export function getRequestErrorCode(error) {
  const candidates = [
    error?.code,
    error?.httpStatus,
    error?.response?.code,
    error?.response?.status,
    error?.response?.data?.code,
    error?.response?.data?.status
  ]

  for (const value of candidates) {
    const code = Number(value)
    if (Number.isFinite(code) && code > 0) {
      return code
    }
  }

  return 0
}

export function isForbiddenRequest(error) {
  const code = getRequestErrorCode(error)
  if (code === 403) {
    return true
  }

  const message = String(error?.message || '').toLowerCase()
  return message.includes('无权限') || message.includes('forbidden')
}

export function createReadonlyErrorState(error, moduleName, permissionName = '') {
  const forbidden = isForbiddenRequest(error)
  const title = forbidden ? `${moduleName}暂无访问权限` : `${moduleName}加载失败`
  const description = forbidden
    ? `接口返回 403，已自动启用只读保护。当前不会展示默认空数据，也不会允许保存、发布或删除操作。${permissionName ? `请确认当前账号具备 ${permissionName} 权限后再重试。` : '请联系管理员开通对应权限后重试。'}`
    : `当前无法获取真实${moduleName}数据，已自动启用只读保护，避免默认值误覆盖线上配置。请稍后重试。`

  return {
    code: getRequestErrorCode(error),
    forbidden,
    title,
    description
  }
}
