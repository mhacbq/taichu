/**
 * 埋点分析系统
 * 支持页面浏览、事件追踪、转化漏斗分析
 */

const SENSITIVE_KEYS = [
  'password',
  'pwd',
  'token',
  'secret',
  'authorization',
  'cookie',
  'phone',
  'mobile',
  'email',
  'name',
  'realname',
  'idcard',
  'invitecode',
  'smscode',
  'code'
]

const MAX_STRING_LENGTH = 160
const MAX_OBJECT_KEYS = 20
const MAX_ARRAY_ITEMS = 10

const normalizeKey = (key = '') => String(key).toLowerCase().replace(/[^a-z0-9]/g, '')

const isSensitiveKey = (key = '') => {
  const normalized = normalizeKey(key)
  return SENSITIVE_KEYS.some(item => normalized.includes(item))
}

const truncateText = (value, maxLength = MAX_STRING_LENGTH) => {
  if (typeof value !== 'string') {
    return value
  }

  return value.length > maxLength ? `${value.slice(0, maxLength)}...` : value
}

const maskValue = (value) => {
  if (value == null) {
    return value
  }

  if (typeof value === 'string') {
    const trimmed = value.trim()
    if (!trimmed) {
      return ''
    }

    if (trimmed.length <= 6) {
      return '***'
    }

    return `${trimmed.slice(0, 2)}***${trimmed.slice(-2)}`
  }

  if (Array.isArray(value)) {
    return `[${value.length} items]`
  }

  if (typeof value === 'object') {
    return '[masked]'
  }

  return '***'
}

const sanitizeValue = (key, value, depth = 0) => {
  if (value == null) {
    return value
  }

  if (isSensitiveKey(key)) {
    return maskValue(value)
  }

  if (typeof value === 'string') {
    return truncateText(value)
  }

  if (typeof value === 'number' || typeof value === 'boolean') {
    return value
  }

  if (Array.isArray(value)) {
    if (depth >= 2) {
      return `[${value.length} items]`
    }

    return value.slice(0, MAX_ARRAY_ITEMS).map((item, index) => sanitizeValue(`${key}_${index}`, item, depth + 1))
  }

  if (typeof value === 'object') {
    if (depth >= 2) {
      return '[nested object]'
    }

    return Object.entries(value)
      .slice(0, MAX_OBJECT_KEYS)
      .reduce((acc, [childKey, childValue]) => {
        acc[childKey] = sanitizeValue(childKey, childValue, depth + 1)
        return acc
      }, {})
  }

  return String(value)
}

const sanitizeProperties = (properties = {}) => {
  return Object.entries(properties).reduce((acc, [key, value]) => {
    acc[key] = sanitizeValue(key, value)
    return acc
  }, {})
}

const extractPathname = (url = '') => {
  if (!url) {
    return ''
  }

  try {
    return new URL(url, window.location.origin).pathname
  } catch (error) {
    return truncateText(String(url).split('?')[0])
  }
}

const getReferrerHost = () => {
  if (!document.referrer) {
    return ''
  }

  try {
    return new URL(document.referrer).hostname
  } catch (error) {
    return truncateText(document.referrer)
  }
}

class Analytics {
  constructor() {
    this.sessionId = this.generateSessionId()
    this.userId = null
    this.startTime = Date.now()
    this.pageStartTime = null
    this.currentPage = null
    this.queue = []
    this.isReady = false
    this.isFlushing = false
    this.flushTimer = null
    this.config = {
      endpoint: import.meta.env.VITE_ANALYTICS_ENDPOINT || '/api/analytics/track',
      batchSize: 10,
      flushInterval: 5000,
      enablePerformance: true,
      enableErrorTracking: true
    }

    this.init()
  }

  init() {
    const userInfo = localStorage.getItem('userInfo')
    if (userInfo) {
      try {
        const user = JSON.parse(userInfo)
        this.userId = user.id || null
      } catch (error) {
        this.userId = null
      }
    }

    this.trackPageView()
    this.listenRouteChange()

    if (this.config.enablePerformance) {
      this.trackPerformance()
    }

    if (this.config.enableErrorTracking) {
      this.trackErrors()
    }

    this.flushTimer = window.setInterval(() => {
      this.flush()
    }, this.config.flushInterval)

    window.addEventListener('beforeunload', () => {
      this.flush({ useBeacon: true })
    })

    this.isReady = true
  }

  generateSessionId() {
    return `sess_${Date.now()}_${Math.random().toString(36).slice(2, 11)}`
  }

  getDeviceInfo() {
    return {
      platform: navigator.platform,
      language: navigator.language,
      screenSize: `${window.screen.width}x${window.screen.height}`,
      viewport: `${window.innerWidth}x${window.innerHeight}`,
      referrerHost: getReferrerHost()
    }
  }

  getCommonProps() {
    return {
      timestamp: Date.now(),
      sessionId: this.sessionId,
      userId: this.userId,
      path: window.location.pathname,
      device: this.getDeviceInfo()
    }
  }

  track(eventName, properties = {}, eventType = 'custom') {
    const event = {
      eventName,
      eventType,
      properties: sanitizeProperties({
        ...this.getCommonProps(),
        ...properties
      })
    }

    this.queue.push(event)

    if (this.queue.length >= this.config.batchSize) {
      this.flush()
    }

    if (import.meta.env.DEV) {
      console.debug('[Analytics]', {
        eventName,
        eventType,
        propertyKeys: Object.keys(event.properties)
      })
    }
  }

  trackPageView(pageName = null, properties = {}) {
    if (this.pageStartTime) {
      const duration = Date.now() - this.pageStartTime
      this.track('page_stay_duration', {
        page: this.currentPage,
        duration
      }, 'page')
    }

    this.currentPage = pageName || window.location.pathname
    this.pageStartTime = Date.now()

    this.track('page_view', {
      page: this.currentPage,
      title: document.title,
      ...properties
    }, 'page')
  }

  trackClick(elementName, properties = {}) {
    this.track('element_click', {
      element: elementName,
      ...properties
    }, 'click')
  }

  trackButtonClick(buttonName, properties = {}) {
    this.track('button_click', {
      button: buttonName,
      ...properties
    }, 'click')
  }

  trackFormSubmit(formName, properties = {}) {
    this.track('form_submit', {
      form: formName,
      ...properties
    }, 'submit')
  }

  trackConversion(conversionName, value = 0, properties = {}) {
    this.track('conversion', {
      conversion: conversionName,
      value,
      ...properties
    }, 'conversion')
  }

  trackPurchase(orderId, amount, products = [], properties = {}) {
    this.track('purchase', {
      orderId,
      amount,
      currency: 'CNY',
      products,
      ...properties
    }, 'purchase')
  }

  setUserProperties(properties) {
    this.track('user_properties', properties, 'user')
  }

  listenRouteChange() {
    if (window.__VUE_ROUTER__) {
      window.__VUE_ROUTER__.afterEach((to) => {
        this.trackPageView(to.name || to.path, {
          route: to.path || extractPathname(to.fullPath),
          routeParamKeys: Object.keys(to.params || {}),
          routeQueryKeys: Object.keys(to.query || {})
        })
      })
    }
  }

  trackPerformance() {
    window.addEventListener('load', () => {
      window.setTimeout(() => {
        const timing = performance.timing

        this.track('performance', {
          dnsTime: timing.domainLookupEnd - timing.domainLookupStart,
          tcpTime: timing.connectEnd - timing.connectStart,
          ttfb: timing.responseStart - timing.requestStart,
          downloadTime: timing.responseEnd - timing.responseStart,
          domParseTime: timing.domComplete - timing.domLoading,
          loadTime: timing.loadEventEnd - timing.navigationStart,
          firstPaint: this.getFirstPaint(),
          firstContentfulPaint: this.getFirstContentfulPaint()
        }, 'performance')
      }, 0)
    })
  }

  getFirstPaint() {
    const paints = performance.getEntriesByType('paint')
    const firstPaint = paints.find(paint => paint.name === 'first-paint')
    return firstPaint ? firstPaint.startTime : null
  }

  getFirstContentfulPaint() {
    const paints = performance.getEntriesByType('paint')
    const fcp = paints.find(paint => paint.name === 'first-contentful-paint')
    return fcp ? fcp.startTime : null
  }

  sanitizeStack(stack) {
    if (!stack) {
      return ''
    }

    return truncateText(
      stack
        .split('\n')
        .slice(0, 3)
        .map(line => line.replace(/https?:\/\/[^\s)]+/g, matched => extractPathname(matched)))
        .join(' | '),
      240
    )
  }

  trackErrors() {
    window.addEventListener('error', (event) => {
      this.track('js_error', {
        message: event.message,
        filename: extractPathname(event.filename),
        lineno: event.lineno,
        colno: event.colno,
        stackPreview: this.sanitizeStack(event.error?.stack)
      }, 'error')
    })

    window.addEventListener('unhandledrejection', (event) => {
      const reason = event.reason
      this.track('promise_rejection', {
        reason: reason?.message || truncateText(String(reason)),
        reasonType: reason?.name || typeof reason,
        stackPreview: this.sanitizeStack(reason?.stack)
      }, 'error')
    })
  }

  async flush(options = {}) {
    if (this.queue.length === 0 || this.isFlushing || !this.config.endpoint) {
      return
    }

    const events = [...this.queue]
    this.queue = []
    this.isFlushing = true

    try {
      if (options.useBeacon && navigator.sendBeacon) {
        const blob = new Blob([JSON.stringify({ events })], {
          type: 'application/json'
        })

        const sent = navigator.sendBeacon(this.config.endpoint, blob)
        if (!sent) {
          throw new Error('sendBeacon returned false')
        }

        return
      }

      const response = await fetch(this.config.endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ events }),
        keepalive: true
      })

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`)
      }
    } catch (error) {
      if (import.meta.env.DEV) {
        console.warn('[Analytics] flush failed', {
          message: error?.message || 'unknown error'
        })
      }

      this.queue.unshift(...events)
    } finally {
      this.isFlushing = false
    }
  }
}

const analytics = new Analytics()

export const vTrack = {
  mounted(el, binding) {
    const { event, properties = {} } = binding.value || {}
    el.addEventListener('click', () => {
      analytics.trackClick(event || el.dataset.trackName || el.innerText, properties)
    })
  }
}

export const useAnalytics = () => {
  return {
    track: analytics.track.bind(analytics),
    trackPageView: analytics.trackPageView.bind(analytics),
    trackClick: analytics.trackClick.bind(analytics),
    trackButtonClick: analytics.trackButtonClick.bind(analytics),
    trackFormSubmit: analytics.trackFormSubmit.bind(analytics),
    trackConversion: analytics.trackConversion.bind(analytics),
    trackPurchase: analytics.trackPurchase.bind(analytics),
    setUserProperties: analytics.setUserProperties.bind(analytics)
  }
}

export default analytics
