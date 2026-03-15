/**
 * 埋点分析系统
 * 支持页面浏览、事件追踪、转化漏斗分析
 */

class Analytics {
  constructor() {
    this.sessionId = this.generateSessionId()
    this.userId = null
    this.startTime = Date.now()
    this.pageStartTime = null
    this.queue = []
    this.isReady = false
    this.config = {
      endpoint: '/api/analytics/track',
      batchSize: 10,
      flushInterval: 5000,
      enablePerformance: true,
      enableErrorTracking: true
    }
    
    this.init()
  }

  init() {
    // 从localStorage获取用户信息
    const userInfo = localStorage.getItem('userInfo')
    if (userInfo) {
      try {
        const user = JSON.parse(userInfo)
        this.userId = user.id
      } catch (e) {}
    }

    // 自动追踪页面浏览
    this.trackPageView()
    
    // 监听路由变化
    this.listenRouteChange()
    
    // 监听性能指标
    if (this.config.enablePerformance) {
      this.trackPerformance()
    }
    
    // 监听错误
    if (this.config.enableErrorTracking) {
      this.trackErrors()
    }
    
    // 定时批量发送
    setInterval(() => this.flush(), this.config.flushInterval)
    
    // 页面关闭前发送剩余数据
    window.addEventListener('beforeunload', () => this.flush())
    
    this.isReady = true
  }

  generateSessionId() {
    return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
  }

  // 获取设备信息
  getDeviceInfo() {
    return {
      userAgent: navigator.userAgent,
      platform: navigator.platform,
      language: navigator.language,
      screenSize: `${window.screen.width}x${window.screen.height}`,
      viewport: `${window.innerWidth}x${window.innerHeight}`,
      referrer: document.referrer
    }
  }

  // 获取通用属性
  getCommonProps() {
    return {
      timestamp: Date.now(),
      sessionId: this.sessionId,
      userId: this.userId,
      url: window.location.href,
      path: window.location.pathname,
      device: this.getDeviceInfo()
    }
  }

  /**
   * 追踪事件
   * @param {string} eventName - 事件名称
   * @param {object} properties - 事件属性
   * @param {string} eventType - 事件类型: page/click/submit/custom
   */
  track(eventName, properties = {}, eventType = 'custom') {
    const event = {
      eventName,
      eventType,
      properties: {
        ...this.getCommonProps(),
        ...properties
      }
    }

    this.queue.push(event)
    
    // 达到批量大小立即发送
    if (this.queue.length >= this.config.batchSize) {
      this.flush()
    }

    // 开发环境打印日志
    if (process.env.NODE_ENV === 'development') {
      console.log('[Analytics]', eventName, properties)
    }
  }

  /**
   * 页面浏览追踪
   */
  trackPageView(pageName = null, properties = {}) {
    // 记录上一页面停留时间
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

  /**
   * 点击事件追踪
   */
  trackClick(elementName, properties = {}) {
    this.track('element_click', {
      element: elementName,
      ...properties
    }, 'click')
  }

  /**
   * 按钮点击追踪（便捷方法）
   */
  trackButtonClick(buttonName, properties = {}) {
    this.track('button_click', {
      button: buttonName,
      ...properties
    }, 'click')
  }

  /**
   * 表单提交追踪
   */
  trackFormSubmit(formName, properties = {}) {
    this.track('form_submit', {
      form: formName,
      ...properties
    }, 'submit')
  }

  /**
   * 转化事件追踪
   */
  trackConversion(conversionName, value = 0, properties = {}) {
    this.track('conversion', {
      conversion: conversionName,
      value,
      ...properties
    }, 'conversion')
  }

  /**
   * 购买事件追踪
   */
  trackPurchase(orderId, amount, products = [], properties = {}) {
    this.track('purchase', {
      orderId,
      amount,
      currency: 'CNY',
      products,
      ...properties
    }, 'purchase')
  }

  /**
   * 用户属性设置
   */
  setUserProperties(properties) {
    this.track('user_properties', properties, 'user')
  }

  /**
   * 监听路由变化
   */
  listenRouteChange() {
    // Vue Router钩子
    if (window.__VUE_ROUTER__) {
      window.__VUE_ROUTER__.afterEach((to) => {
        this.trackPageView(to.name || to.path, {
          route: to.fullPath,
          params: to.params,
          query: to.query
        })
      })
    }
  }

  /**
   * 性能指标追踪
   */
  trackPerformance() {
    // 等待页面完全加载
    window.addEventListener('load', () => {
      setTimeout(() => {
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
    const firstPaint = paints.find(p => p.name === 'first-paint')
    return firstPaint ? firstPaint.startTime : null
  }

  getFirstContentfulPaint() {
    const paints = performance.getEntriesByType('paint')
    const fcp = paints.find(p => p.name === 'first-contentful-paint')
    return fcp ? fcp.startTime : null
  }

  /**
   * 错误追踪
   */
  trackErrors() {
    window.addEventListener('error', (event) => {
      this.track('js_error', {
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        stack: event.error?.stack
      }, 'error')
    })

    window.addEventListener('unhandledrejection', (event) => {
      this.track('promise_rejection', {
        reason: event.reason?.message || String(event.reason),
        stack: event.reason?.stack
      }, 'error')
    })
  }

  /**
   * 批量发送数据
   */
  async flush() {
    if (this.queue.length === 0) return

    const events = [...this.queue]
    this.queue = []

    try {
      // 优先使用sendBeacon（页面关闭时也能发送）
      if (navigator.sendBeacon) {
        const blob = new Blob([JSON.stringify({ events })], {
          type: 'application/json'
        })
        navigator.sendBeacon(this.config.endpoint, blob)
      } else {
        // 降级使用fetch
        await fetch(this.config.endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ events }),
          keepalive: true
        })
      }
    } catch (error) {
      console.error('Analytics flush failed:', error)
      // 发送失败，重新加入队列
      this.queue.unshift(...events)
    }
  }
}

// 创建全局实例
const analytics = new Analytics()

// Vue指令：自动追踪点击
export const vTrack = {
  mounted(el, binding) {
    const { event, properties = {} } = binding.value || {}
    el.addEventListener('click', () => {
      analytics.trackClick(event || el.dataset.trackName || el.innerText, properties)
    })
  }
}

// 组合式函数
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
