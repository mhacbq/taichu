const SENSITIVE_KEYWORDS = ['password', 'pwd', 'token', 'secret', 'authorization', 'phone', 'mobile', 'email', 'cookie']

function truncateText(value, maxLength = 160) {
  const text = typeof value === 'string' ? value.trim() : ''
  if (!text) {
    return ''
  }

  return text.length > maxLength ? `${text.slice(0, maxLength)}...` : text
}

function maskPhone(value) {
  return value.replace(/\b1\d{10}\b/g, (match) => `${match.slice(0, 3)}****${match.slice(-4)}`)
}

function maskEmail(value) {
  return value.replace(/\b([A-Z0-9._%+-]{1,2})[A-Z0-9._%+-]*@([A-Z0-9.-]+\.[A-Z]{2,})\b/gi, (_, prefix, domain) => `${prefix}***@${domain}`)
}

function maskAuthorization(value) {
  return value.replace(/Bearer\s+[^\s]+/gi, 'Bearer ***')
}

function sanitizeString(value) {
  return truncateText(maskAuthorization(maskEmail(maskPhone(value))))
}

function isSensitiveKey(key) {
  const normalized = String(key || '').toLowerCase()
  return SENSITIVE_KEYWORDS.some((keyword) => normalized.includes(keyword))
}

function sanitizeExtra(value, key = '') {
  if (value == null) {
    return value
  }

  if (Array.isArray(value)) {
    return value.map((item) => sanitizeExtra(item, key))
  }

  if (typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value).map(([entryKey, entryValue]) => [
        entryKey,
        sanitizeExtra(entryValue, entryKey)
      ])
    )
  }

  if (typeof value === 'string') {
    if (isSensitiveKey(key)) {
      return '***'
    }

    return sanitizeString(value)
  }

  if (isSensitiveKey(key)) {
    return '***'
  }

  return value
}

export function sanitizeAdminErrorMessage(error, fallback = 'unknown') {
  const message = typeof error?.message === 'string' ? error.message : String(error ?? '')
  return sanitizeString(message) || fallback
}

export function reportAdminUiError(scope, action, error, extra = {}) {
  if (!import.meta.env.DEV) {
    return
  }

  console.error(`[Admin:${scope}]`, {
    action,
    error_type: error?.name || typeof error,
    message: sanitizeAdminErrorMessage(error),
    ...sanitizeExtra(extra)
  })
}
