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

/**
 * 获取首页内容
 */
export function useHomeContent() {
  const content = ref({})
  const testimonials = ref([])
  const loading = ref(false)
  const error = ref(null)

  const loadContent = async () => {
    loading.value = true
    error.value = null
    try {
      const res = await getHomeContent()
      if (res.code === 0) {
        content.value = res.data.content || {}
        testimonials.value = res.data.testimonials || []
      }
    } catch (err) {
      error.value = err
      console.error('加载首页内容失败:', err)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    loadContent()
  })

  return {
    content,
    testimonials,
    loading,
    error,
    refresh: loadContent
  }
}

/**
 * 获取页面内容
 * @param {string} page - 页面标识
 */
export function usePageContent(page) {
  const content = ref({})
  const loading = ref(false)
  const error = ref(null)

  const loadContent = async () => {
    loading.value = true
    error.value = null
    try {
      const res = await getPageContent(page)
      if (res.code === 0) {
        content.value = res.data || {}
      }
    } catch (err) {
      error.value = err
      console.error(`加载${page}页面内容失败:`, err)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    loadContent()
  })

  return {
    content,
    loading,
    error,
    refresh: loadContent
  }
}

/**
 * 获取用户评价
 */
export function useTestimonials() {
  const testimonials = ref([])
  const loading = ref(false)
  const error = ref(null)

  const loadTestimonials = async () => {
    loading.value = true
    error.value = null
    try {
      const res = await getTestimonials()
      if (res.code === 0) {
        testimonials.value = res.data || []
      }
    } catch (err) {
      error.value = err
      console.error('加载用户评价失败:', err)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    loadTestimonials()
  })

  return {
    testimonials,
    loading,
    error,
    refresh: loadTestimonials
  }
}

/**
 * 获取FAQ
 * @param {string} category - 分类
 */
export function useFaqs(category = null) {
  const faqs = ref([])
  const loading = ref(false)
  const error = ref(null)

  const loadFaqs = async () => {
    loading.value = true
    error.value = null
    try {
      const res = await getFaqs(category)
      if (res.code === 0) {
        faqs.value = res.data || []
      }
    } catch (err) {
      error.value = err
      console.error('加载FAQ失败:', err)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    loadFaqs()
  })

  return {
    faqs,
    loading,
    error,
    refresh: loadFaqs
  }
}

/**
 * 获取塔罗牌阵
 */
export function useSpreads() {
  const spreads = ref([])
  const loading = ref(false)
  const error = ref(null)

  const loadSpreads = async () => {
    loading.value = true
    error.value = null
    try {
      const res = await getSpreads()
      if (res.code === 0) {
        spreads.value = res.data || []
      }
    } catch (err) {
      error.value = err
      console.error('加载牌阵失败:', err)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    loadSpreads()
  })

  return {
    spreads,
    loading,
    error,
    refresh: loadSpreads
  }
}

/**
 * 获取问题模板
 * @param {string} category - 分类
 */
export function useQuestions(category = null) {
  const questions = ref([])
  const loading = ref(false)
  const error = ref(null)

  const loadQuestions = async () => {
    loading.value = true
    error.value = null
    try {
      const res = await getQuestions(category)
      if (res.code === 0) {
        questions.value = res.data || []
      }
    } catch (err) {
      error.value = err
      console.error('加载问题模板失败:', err)
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    loadQuestions()
  })

  return {
    questions,
    loading,
    error,
    refresh: loadQuestions
  }
}