import { ref, onMounted, onUnmounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { getHomeStats, getPointsBalance } from '../../api'
import {
  Sunrise,
  Sunny,
  Moon,
  Coin,
  Cherry,
  Calendar,
  Star,
  Check,
  UserFilled,
  DataLine,
  ChatLineRound, MagicStick, Present, ArrowRight
} from '@element-plus/icons-vue'

export function useHome() {
const statIconMap = {
  UserFilled,
  DataLine,
  ChatLineRound,
}

const createFallbackStats = (caption = '数据更新中') => [
  { number: '--', label: '服务用户', icon: UserFilled, caption, isLive: false },
  { number: '--', label: '分析次数', icon: DataLine, caption, isLive: false },
  { number: '--', label: '好评率', icon: ChatLineRound, caption, isLive: false },
]

const hasDisplayValue = (value) => value !== undefined && value !== null && `${value}`.trim() !== ''

const extractPointsBalance = (payload = {}) => {
  const source = payload && typeof payload === 'object' ? payload : {}
  const candidates = [
    source.balance,
    source.points,
    source.remaining_points,
    source.available_points,
    source.current_points,
  ]

  for (const candidate of candidates) {
    const normalized = Number(candidate)
    if (Number.isFinite(normalized)) {
      return normalized
    }
  }

  return null
}

const getStoredPoints = () => {
  try {
    const userInfo = JSON.parse(localStorage.getItem('userInfo') || 'null')
    const normalized = Number(userInfo?.points)
    return Number.isFinite(normalized) ? normalized : null
  } catch {
    return null
  }
}

const formatDisplayValue = (value) => {
  if (!hasDisplayValue(value)) {
    return '--'
  }

  if (typeof value === 'number') {
    return value.toLocaleString('zh-CN')
  }

  const numericValue = Number(value)
  return Number.isFinite(numericValue) ? numericValue.toLocaleString('zh-CN') : value
}

const resolveStatIcon = (icon, fallbackIcon = UserFilled) => {
  if (typeof icon === 'object' || typeof icon === 'function') {
    return icon
  }

  return statIconMap[icon] || fallbackIcon
}

const buildStats = (incomingStats = []) => {
  const fallbackStats = createFallbackStats()

  return fallbackStats.map((fallback, index) => {
    const item = incomingStats[index]

    if (!item) {
      return fallback
    }

    return {
      ...fallback,
      ...item,
      number: hasDisplayValue(item.number) ? formatDisplayValue(item.number) : '--',
      label: item.label || fallback.label,
      icon: resolveStatIcon(item.icon, fallback.icon),
      caption: hasDisplayValue(item.number) ? '' : fallback.caption,
      isLive: hasDisplayValue(item.number),
    }
  })
}

const stats = ref(createFallbackStats('统计同步中'))
const statsLoading = ref(true)
const statsError = ref(false)

const isLoggedIn = ref(false)
const userPoints = ref(null)
const userCount = ref(null)
const isFirstBaziEligible = ref(null)

const baziOfferState = computed(() => {
  if (!isLoggedIn.value) {
    return 'guest'
  }

  if (isFirstBaziEligible.value == null) {
    return 'loading'
  }

  return isFirstBaziEligible.value ? 'free' : 'priced'
})

const formattedUserPoints = computed(() => formatDisplayValue(userPoints.value))
const registerIntentRoute = { path: '/login', query: { intent: 'register' } }
const currentYear = new Date().getFullYear()
const yearlyBannerBadge = computed(() => `${currentYear} 年度运势`)
const yearlyBannerTitle = computed(() => `${currentYear} 流年运势深度解析`)

const heroPointsCardNote = computed(() => {
  if (!isLoggedIn.value) {
    return '登录后即可同步积分权益与八字首测资格。'
  }

  if (!hasDisplayValue(userPoints.value)) {
    return '积分正在同步中，也可以先去个人中心确认今日签到状态。'
  }

  if (baziOfferState.value === 'free') {
    return `当前可用 ${formattedUserPoints.value} 积分，你的八字首测资格仍在，适合先从一次免费排盘开始。`
  }

  if (baziOfferState.value === 'priced') {
    return `当前可用 ${formattedUserPoints.value} 积分，八字首测资格已使用；想补充额度时，可去签到页领取今日积分。`
  }

  return `当前可用 ${formattedUserPoints.value} 积分，可继续用于排盘、占卜与后续深入解读。`
})


const heroProofItems = computed(() => [
  {
    key: 'users',
    icon: UserFilled,
    label: hasDisplayValue(userCount.value) ? `${formatDisplayValue(userCount.value)}+ 用户已体验` : '真实用户使用反馈',
    description: '通过八字排盘了解自身五行格局，用塔罗探索当下困惑，每日运势提供行动参考。'
  },
  {
    key: 'services',
    icon: MagicStick,
    label: 'AI辅助传统解读',
    description: 'AI结合专业命理知识，提供八字、塔罗、六爻等服务的深度分析与建议，帮助更好理解信息。'
  },
  {
    key: 'clarity',
    icon: ChatLineRound,
    label: '透明计费体系',
    description: '每项服务积分消耗明确标注，注册送积分每日签到领积分，清楚知道每笔花费在哪里。'
  }
])

const heroAccessItems = computed(() => [
  {
    key: 'entry',
    icon: isLoggedIn.value ? Check : Present,
    title: isLoggedIn.value ? '各项服务已解锁' : '登录后即可体验',
    detail: isLoggedIn.value ? '登录后八字排盘、塔罗占卜、六爻问事等功能均可正常使用。' : '登录即领积分，解锁八字排盘、塔罗占卜、六爻问事等功能。'
  },
  {
    key: 'daily',
    icon: Star,
    title: '每日运势即时查看',
    detail: '输入出生信息生成八字，即可查看每日宜忌、幸运提示与节奏建议。'
  },
  {
    key: 'points',
    icon: Coin,
    title: isLoggedIn.value ? '当前积分余额' : '注册即领启动积分',
    detail: isLoggedIn.value ? `可用 ${formattedUserPoints.value} 积分，用于八字排盘、塔罗占卜、六爻问事等服务。` : '新用户注册送100积分，可体验八字首测、塔罗占卜等服务。'
  }
])

const heroTrustItems = computed(() => [
  {
    key: 'clarity',
    icon: Check,
    text: '出生信息即输即出，快速查看八字排盘结果'
  },
  {
    key: 'benefits',
    icon: Present,
    text: '抽取塔罗牌，AI分析牌面与问题关联'
  },
  {
    key: 'rhythm',
    icon: Star,
    text: '结合八字信息，查看个性化每日运势建议'
  }
])

const guestBenefits = [
  {
    key: 'points',
    icon: Present,
    title: '登录即领 100 积分',
    description: '先拿到体验额度，再决定最想优先探索哪一项服务。'
  },
  {
    key: 'free',
    icon: Star,
    title: '八字首测免费',
    description: '第一次排盘零门槛，适合先看看自己的整体节奏。'
  },
  {
    key: 'services',
    icon: MagicStick,
    title: '多种方式探索自己',
    description: '八字、塔罗与每日运势等入口现在都从首页顺着往下走。'
  }
]

// 问候语数据
const currentHour = ref(new Date().getHours())
let greetingRefreshTimer = null

const syncCurrentHour = () => {
  currentHour.value = new Date().getHours()
}

const scheduleGreetingRefresh = () => {
  syncCurrentHour()

  if (greetingRefreshTimer) {
    window.clearTimeout(greetingRefreshTimer)
  }

  const now = new Date()
  const nextHour = new Date(now)
  nextHour.setHours(now.getHours() + 1, 0, 0, 0)
  greetingRefreshTimer = window.setTimeout(() => {
    scheduleGreetingRefresh()
  }, Math.max(1000, nextHour.getTime() - now.getTime() + 1000))
}

const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    scheduleGreetingRefresh()
  }
}

const greetingIcon = computed(() => {
  if (currentHour.value < 12) return 'morning'
  if (currentHour.value < 18) return 'afternoon'
  return 'evening'
})

const greetingText = computed(() => {
  if (currentHour.value < 12) return '早上好，愿你今天充满希望'
  if (currentHour.value < 18) return '下午好，愿你的努力都有收获'
  return '晚上好，愿你今夜好梦'
})

// 每日暖心语录
const quotes = [
  '"迷茫不是软弱，而是你在认真思考人生"',
  '"每一次困惑，都是重新认识自己的机会"',
  '"相信自己的直觉，你比想象中更有力量"',
  '"生活不会辜负每一个认真的人"',
  '"今天的迷茫，是为了明天更坚定的选择"',
  '"慢慢来，没关系，每个人都在自己的时区"',
]

const dailyQuote = computed(() => {
  const dayOfYear = Math.floor((new Date() - new Date(new Date().getFullYear(), 0, 0)) / (1000 * 60 * 60 * 24))
  return quotes[dayOfYear % quotes.length]
})

// 用户心声示例 - 以体验故事形式展示，避免与实时评价混淆
const testimonials = ref([
  {
    name: '小雨',
    avatar: '雨',
    avatarColor: 'var(--primary-light-20)',
    rating: 5,
    ratingLabel: '4.9 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '毕业转行期 · 职业方向迷茫',
    content: '毕业后一直很迷茫，不知道自己适合什么工作。排盘后看到自己的优势节奏和适合的发展方向，至少先知道下一步该往哪走。',
    outcome: '更适合用来梳理职业方向',
    note: '示例反馈',
    service: '八字排盘'
  },
  {
    name: '阿杰',
    avatar: '杰',
    avatarColor: 'var(--warning-light)',
    rating: 5,
    ratingLabel: '4.8 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '关系调整期 · 想看清真实需求',
    content: '感情遇到瓶颈期时，塔罗没有替我做决定，而是帮我把真正纠结的点拆开来看，最后更清楚自己到底在意什么。',
    outcome: '更适合梳理关系里的优先级',
    note: '示例反馈',
    service: '塔罗占卜'
  },
  {
    name: '小陈',
    avatar: '陈',
    avatarColor: 'var(--success-light)',
    rating: 4,
    ratingLabel: '4.7 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '高压上班族 · 需要每日提醒',
    content: '工作压力大的时候，我更在意有没有一个轻量提醒告诉我今天该冲还是该缓。每日运势给我的价值，是让我在忙乱里停一下。',
    outcome: '适合做日常节奏提醒',
    note: '示例反馈',
    service: '每日运势'
  },
  {
    name: '琳琳',
    avatar: '琳',
    avatarColor: 'var(--primary-light-15)',
    rating: 5,
    ratingLabel: '4.9 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '自我探索期 · 容易反复内耗',
    content: '以前总觉得自己想太多，八字分析反而让我先理解自己的性格底色。被看见之后，很多自我怀疑就没那么重了。',
    outcome: '适合建立更稳定的自我认知',
    note: '示例反馈',
    service: '八字排盘'
  },
  {
    name: '大鹏',
    avatar: '鹏',
    avatarColor: 'var(--info-light)',
    rating: 4,
    ratingLabel: '4.7 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '跳槽决策期 · 需要理清取舍',
    content: '一直纠结要不要换工作，塔罗最大的帮助不是"准不准"，而是把风险、期待和顾虑都摆到了明面上，决策时没那么乱。',
    outcome: '更适合辅助做阶段性判断',
    note: '示例反馈',
    service: '塔罗占卜'
  },
  {
    name: '思思',
    avatar: '思',
    avatarColor: 'var(--warning-light)',
    rating: 5,
    ratingLabel: '4.8 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '长期规划期 · 想看未来节奏',
    content: '第一次接触时本来只是抱着试试看的心态，但长周期分析给我的感觉是：至少能把未来几年要留意的节点先放进心里。',
    outcome: '适合做长期规划参考',
    note: '示例反馈',
    service: '八字排盘'
  }
])


const loadStats = async () => {
  statsLoading.value = true
  statsError.value = false

  try {
    const response = await getHomeStats()

    if (response.code !== 0) {
      throw new Error(response.message || '加载统计数据失败')
    }

    const incomingStats = Array.isArray(response.data?.stats) ? response.data.stats : []
    stats.value = buildStats(incomingStats)
    userCount.value = hasDisplayValue(response.data?.userCount) ? response.data.userCount : null

    if (!incomingStats.length && !hasDisplayValue(userCount.value)) {
      statsError.value = true
      stats.value = createFallbackStats('数据更新中')
    }
  } catch (error) {
    stats.value = createFallbackStats('数据更新中')
    userCount.value = null
    statsError.value = true
  } finally {
    statsLoading.value = false
  }
}


const resolveFirstBaziFlag = (flag) => {
  if (flag === null || flag === undefined) return null
  if (typeof flag === 'boolean') return flag
  if (typeof flag === 'number') return flag === 1
  if (typeof flag === 'string') return flag === '1' || flag.toLowerCase() === 'true'
  return null
}

const loadUserPoints = async () => {
  const token = localStorage.getItem('token')
  if (!token) {
    isLoggedIn.value = false
    userPoints.value = null
    isFirstBaziEligible.value = null
    return
  }

  isLoggedIn.value = true
  try {
    const response = await getPointsBalance()
    if (response.code === 0) {
      userPoints.value = extractPointsBalance(response.data) ?? getStoredPoints()
      isFirstBaziEligible.value = resolveFirstBaziFlag(response.data?.first_bazi)
    } else {
      userPoints.value = null
      isFirstBaziEligible.value = null
    }
  } catch (error) {
    userPoints.value = null
    isFirstBaziEligible.value = null
  }
}

const refreshHomeAccountState = () => {
  loadUserPoints()
}

const handleReserve = (type) => {
  if (!isLoggedIn.value) {
    ElMessage.warning('请先登录后再预约')
    return
  }

  const featureName = type === 'qiming' ? '取名建议' : '吉日查询'
  ElMessage.success(`已成功预约「${featureName}」功能，上线后将第一时间通知您！`)
}

onMounted(() => {
  loadStats()
  loadUserPoints()
  scheduleGreetingRefresh()
  window.addEventListener('points-updated', refreshHomeAccountState)
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

onUnmounted(() => {
  window.removeEventListener('points-updated', refreshHomeAccountState)
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  if (greetingRefreshTimer) {
    window.clearTimeout(greetingRefreshTimer)
    greetingRefreshTimer = null
  }
})

return {
  // 状态
  stats,
  statsLoading,
  statsError,
  isLoggedIn,
  userPoints,
  userCount,
  isFirstBaziEligible,
  testimonials,
  currentHour,

  // 计算属性
  currentYear,
  yearlyBannerBadge,
  yearlyBannerTitle,
  baziOfferState,
  formattedUserPoints,
  registerIntentRoute,
  heroPointsCardNote,
  heroProofItems,
  heroAccessItems,
  heroTrustItems,
  guestBenefits,
  greetingIcon,
  greetingText,
  dailyQuote,

  // 方法
  loadStats,
  handleReserve,
}
} // end useHome
