import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { StarFilled, Coin, MagicStick, UserFilled, Lock } from '@element-plus/icons-vue'
import { getFaqs, getPageContent } from '../../api/siteContent'

export function useHelp() {
  const router = useRouter()

  // ===== 状态 =====
  const searchQuery = ref('')
  const activeNames = ref([0, 1, 2])
  const faqData = ref([])
  const pageContent = ref({})
  const searchSuggestions = ref([])
  const faqStatus = ref('loading')
  const faqError = ref(null)

  // ===== 默认内容 =====
  const defaultHelpContent = {
    pageTitle: '帮助中心',
    searchTitle: '有问题？我们来帮您',
    searchPlaceholder: '搜索问题关键词...',
    hotTags: ['积分', '八字', '登录', '塔罗', '充值'],
    contactTitle: '还有其他问题？',
    contactDesc: '如果以上问题没有解答您的疑问，欢迎联系我们',
    serviceLabel: '在线客服',
    serviceValue: '工作日 9:00-18:00',
    emailLabel: '邮箱',
    emailValue: 'support@taichu.com',
    wechatLabel: '微信公众号',
    wechatValue: '太初命理',
    feedbackButtonText: '提交反馈',
  }

  // 分类映射
  const categoryMap = {
    general: '新手指南',
    bazi: '八字分析',
    tarot: '塔罗测试',
    account: '账号相关',
    points: '积分问题',
    '新手指南': '新手指南',
    '八字分析': '八字分析',
    '塔罗测试': '塔罗测试',
    '账号相关': '账号相关',
    '积分问题': '积分问题'
  }

  // 分类图标映射
  const categoryIcons = {
    general: StarFilled,
    bazi: Coin,
    tarot: MagicStick,
    account: UserFilled,
    points: Lock,
  }

  // 分类符号映射（用于新设计）
  const categorySymbols = {
    '新手指南': '📖',
    '八字分析': '🧮',
    '塔罗测试': '🔮',
    '账号相关': '🔐',
    '积分问题': '💰',
    'general': '📖',
    'bazi': '🧮',
    'tarot': '🔮',
    'account': '🔐',
    'points': '💰'
  }

  // 联系方式符号映射
  const contactSymbols = {
    '在线客服': '💬',
    '邮箱': '📧',
    '微信公众号': '📱',
    'serviceLabel': '💬',
    'emailLabel': '📧',
    'wechatLabel': '📱'
  }

  // ===== 工具方法 =====
  const parseContentArray = (value, fallback = []) => {
    if (!value) return fallback
    if (Array.isArray(value)) return value.filter(Boolean)
    if (typeof value !== 'string') return fallback
    const trimmed = value.trim()
    if (!trimmed) return fallback
    try {
      const parsed = JSON.parse(trimmed)
      if (Array.isArray(parsed)) return parsed.map((item) => `${item}`.trim()).filter(Boolean)
    } catch {
      // noop
    }
    return trimmed.split(/[\n,，、]/).map((item) => item.trim()).filter(Boolean)
  }

  const getContentValue = (key, fallback) => {
    const value = pageContent.value?.[key]
    return typeof value === 'string' && value.trim() ? value.trim() : fallback
  }

  // ===== 新增辅助函数 =====
  const getCategoryIcon = (title) => {
    return categorySymbols[title] || '❓'
  }

  const getContactSymbol = (label) => {
    return contactSymbols[label] || '📞'
  }

  // ===== 计算属性 =====
  const helpPageTitle = computed(() => getContentValue('page_title', defaultHelpContent.pageTitle))
  const helpSearchTitle = computed(() => getContentValue('search_title', defaultHelpContent.searchTitle))
  const helpSearchPlaceholder = computed(() => getContentValue('search_placeholder', defaultHelpContent.searchPlaceholder))
  const hotTags = computed(() => parseContentArray(pageContent.value?.hot_tags, defaultHelpContent.hotTags))
  const helpContactTitle = computed(() => getContentValue('contact_title', defaultHelpContent.contactTitle))
  const helpContactDesc = computed(() => getContentValue('contact_desc', defaultHelpContent.contactDesc))
  const helpFeedbackButtonText = computed(() => getContentValue('feedback_button_text', defaultHelpContent.feedbackButtonText))

  // 更新联系方式数据
  const contactMethods = computed(() => [
    {
      icon: 'ChatDotRound',
      label: getContentValue('contact_service_label', defaultHelpContent.serviceLabel),
      value: getContentValue('contact_service_value', defaultHelpContent.serviceValue),
      symbol: '💬'
    },
    {
      icon: 'Message',
      label: getContentValue('contact_email_label', defaultHelpContent.emailLabel),
      value: getContentValue('contact_email_value', defaultHelpContent.emailValue),
      symbol: '📧'
    },
    {
      icon: 'ChatDotRound',
      label: getContentValue('contact_wechat_label', defaultHelpContent.wechatLabel),
      value: getContentValue('contact_wechat_value', defaultHelpContent.wechatValue),
      symbol: '📱'
    },
  ])

  // 按分类分组 FAQ 数据
  const groupedFaqs = computed(() => {
    const groups = {}
    faqData.value.forEach((item) => {
      if (!groups[item.category]) groups[item.category] = []
      groups[item.category].push(item)
    })
    // 按浏览量排序
    Object.keys(groups).forEach((category) => {
      groups[category].sort((a, b) => (b.view_count || 0) - (a.view_count || 0))
    })
    return groups
  })

  // 处理后的分类数据（更新版）
  const filteredCategories = computed(() => {
    const categories = Object.keys(groupedFaqs.value).map((category) => ({
      title: categoryMap[category] || category,
      icon: categoryIcons[category],
      symbol: categorySymbols[category] || '❓',
      items: groupedFaqs.value[category].map(item => ({
        ...item,
        expanded: item.expanded || false
      }))
    }))

    if (!searchQuery.value) return categories
    
    const query = searchQuery.value.toLowerCase()
    return categories
      .map((category) => ({
        ...category,
        items: category.items.filter(
          (item) =>
            item.question.toLowerCase().includes(query) ||
            item.answer.toLowerCase().includes(query)
        ),
      }))
      .filter((category) => category.items.length > 0)
  })

  // ===== 数据加载 =====
  const loadHelpContent = async () => {
    try {
      const response = await getPageContent('help')
      if (response.code === 0) {
        pageContent.value = response.data || {}
      }
    } catch {
      pageContent.value = {}
    }
  }

  const loadFaqs = async () => {
    try {
      faqStatus.value = 'loading'
      faqError.value = null
      const response = await getFaqs()
      if (response.code === 0) {
        faqData.value = response.data.map((item) => ({ ...item, expanded: false }))
        faqStatus.value = faqData.value.length > 0 ? 'success' : 'empty'
      } else {
        faqStatus.value = 'error'
        faqError.value = response.message || '加载失败'
      }
    } catch (error) {
      faqStatus.value = 'error'
      faqError.value = error.message || '网络错误，请稍后重试'
      ElMessage.error('加载帮助内容失败，请稍后重试')
    }
  }

  // ===== 交互操作 =====
  const handleSearchInput = () => {
    if (searchQuery.value.length > 1) {
      searchSuggestions.value = faqData.value
        .filter((item) => item.question.toLowerCase().includes(searchQuery.value.toLowerCase()))
        .slice(0, 5)
    } else {
      searchSuggestions.value = []
    }
  }

  const selectSuggestion = (suggestion) => {
    searchQuery.value = suggestion.question
    searchSuggestions.value = []
    const item = faqData.value.find((i) => i.id === suggestion.id)
    if (item) item.expanded = true
  }

  const toggleQuestion = (item) => {
    item.expanded = !item.expanded
  }

  const goToFeedback = () => {
    router.push({ path: '/profile', hash: '#feedback-card' })
  }

  // ===== 初始化 =====
  onMounted(() => {
    loadHelpContent()
    loadFaqs()
  })

  return {
    searchQuery,
    activeNames,
    faqStatus,
    faqError,
    searchSuggestions,
    helpPageTitle,
    helpSearchTitle,
    helpSearchPlaceholder,
    hotTags,
    helpContactTitle,
    helpContactDesc,
    helpFeedbackButtonText,
    contactMethods,
    filteredCategories,
    getCategoryIcon,
    getContactSymbol,
    loadFaqs,
    handleSearchInput,
    selectSuggestion,
    toggleQuestion,
    goToFeedback,
  }
}