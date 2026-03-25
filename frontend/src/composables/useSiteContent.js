import { ref, onMounted } from 'vue'
import {
  getHomeContent,
  getPageContent,
  getTestimonials,
  getFaqs,
  getSpreads,
  getQuestions
} from '@/api/siteContent'

/**
 * 网站内容组合式函数
 * 用于在组件中获取动态内容
 */

const truncateSiteContentMessage = (message) => {
  const normalizedMessage = typeof message === 'string' ? message.trim() : ''
  if (!normalizedMessage) {
    return 'unknown'
  }

  return normalizedMessage.length > 160 ? `${normalizedMessage.slice(0, 157)}...` : normalizedMessage
}

const reportSiteContentError = (action, error, extra = {}) => {
  if (!import.meta.env.DEV) {
    return
  }

  console.error('[SiteContent]', {
    action,
    error_type: error?.name || typeof error,
    message: truncateSiteContentMessage(error?.message || String(error ?? '')),
    ...extra
  })
}

const createContentLoader = (action, request, applyData, buildExtra = () => ({})) => {
  const loading = ref(false)
  const error = ref(null)

  const load = async () => {
    loading.value = true
    error.value = null

    try {
      const res = await request()
      if (res.code === 0) {
        applyData(res.data)
      }
    } catch (err) {
      error.value = err
      reportSiteContentError(action, err, buildExtra())
    } finally {
      loading.value = false
    }
  }

  onMounted(load)

  return {
    loading,
    error,
    refresh: load
  }
}

/**
 * 获取首页内容
 */
export function useHomeContent() {
  const content = ref({})
  const testimonials = ref([])
  const { loading, error, refresh } = createContentLoader(
    'load_home_content',
    () => getHomeContent(),
    (data = {}) => {
      content.value = data.content || {}
      testimonials.value = data.testimonials || []
    }
  )

  return {
    content,
    testimonials,
    loading,
    error,
    refresh
  }
}

/**
 * 获取页面内容
 * @param {string} page - 页面标识
 */
export function usePageContent(page) {
  const content = ref({})
  const { loading, error, refresh } = createContentLoader(
    'load_page_content',
    () => getPageContent(page),
    (data = {}) => {
      content.value = data || {}
    },
    () => ({ page })
  )

  return {
    content,
    loading,
    error,
    refresh
  }
}

/**
 * 获取用户评价
 */
export function useTestimonials() {
  const testimonials = ref([])
  const { loading, error, refresh } = createContentLoader(
    'load_testimonials',
    () => getTestimonials(),
    (data = []) => {
      testimonials.value = data || []
    }
  )

  return {
    testimonials,
    loading,
    error,
    refresh
  }
}

/**
 * 获取FAQ
 * @param {string} category - 分类
 */
export function useFaqs(category = null) {
  const faqs = ref([])
  const { loading, error, refresh } = createContentLoader(
    'load_faqs',
    () => getFaqs(category),
    (data = []) => {
      faqs.value = data || []
    },
    () => ({ category: category || 'all' })
  )

  return {
    faqs,
    loading,
    error,
    refresh
  }
}

/**
 * 获取塔罗牌阵
 */
export function useSpreads() {
  const spreads = ref([])
  const { loading, error, refresh } = createContentLoader(
    'load_spreads',
    () => getSpreads(),
    (data = []) => {
      spreads.value = data || []
    }
  )

  return {
    spreads,
    loading,
    error,
    refresh
  }
}

/**
 * 获取问题模板
 * @param {string} category - 分类
 */
export function useQuestions(category = null) {
  const questions = ref([])
  const { loading, error, refresh } = createContentLoader(
    'load_questions',
    () => getQuestions(category),
    (data = []) => {
      questions.value = data || []
    },
    () => ({ category: category || 'all' })
  )

  return {
    questions,
    loading,
    error,
    refresh
  }
}
