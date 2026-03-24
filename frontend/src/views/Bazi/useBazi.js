import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'

import {
  calculateBazi as calculateBaziApi,
  getPointsBalance,
  getYearlyFortune,
  getDayunAnalysis,
  getDayunChart as getDayunChartApi,
  getFortunePointsCost,
  getClientConfig,
  getBaziRecord
} from '../../api'
import { analyzeBaziAi, analyzeBaziAiStream, scoreDayunAi, getAiRecord } from '../../api/ai'
import { sanitizeHtml } from '../../utils/sanitize'
import { trackPageView, trackEvent, trackSubmit, trackError } from '../../utils/tracker'

export function useBazi() {
const router = useRouter()
const route = useRoute()
const activeTab = ref('chart')

const BAZI_BASE_COST = ref(10) // 动态费用，从后端获取
const AI_ANALYSIS_DEFAULT_COST = 30
const WUXING_WEIGHT_MAX = 9.5


const estimatedTimeOptions = [
  { value: 'before-dawn', label: '凌晨（约 01:30）', time: '01:30:00', shortLabel: '凌晨' },
  { value: 'morning', label: '早晨（约 07:30）', time: '07:30:00', shortLabel: '早晨' },
  { value: 'forenoon', label: '上午（约 10:30）', time: '10:30:00', shortLabel: '上午' },
  { value: 'noon', label: '中午（约 12:00）', time: '12:00:00', shortLabel: '中午' },
  { value: 'afternoon', label: '下午（约 15:30）', time: '15:30:00', shortLabel: '下午' },
  { value: 'evening', label: '晚上（约 20:30）', time: '20:30:00', shortLabel: '晚上' },
  { value: 'unknown', label: '未知时辰（仅按生日趋势）', time: '', shortLabel: '未知时辰', mode: 'date-only' },
]

const resolveEstimatedTimeSlotByClock = (clock = '') => {
  const [hour = '12'] = String(clock || '').split(':')
  const parsedHour = Number(hour)

  if (!Number.isFinite(parsedHour)) {
    return ''
  }

  if (parsedHour < 6) return 'before-dawn'
  if (parsedHour < 9) return 'morning'
  if (parsedHour < 12) return 'forenoon'
  if (parsedHour < 14) return 'noon'
  if (parsedHour < 18) return 'afternoon'
  return 'evening'
}

const calendarType = ref('solar') // 历法类型：solar公历, lunar农历

const birthTimeAccuracy = ref('exact')
const exactBirthDate = ref('')
const estimatedBirthDate = ref('')
const estimatedTimeSlot = ref('')
const selectedEstimatedTimeOption = computed(() => {
  return estimatedTimeOptions.find((option) => option.value === estimatedTimeSlot.value) || null
})
const isEstimatedDateOnly = computed(() => selectedEstimatedTimeOption.value?.mode === 'date-only')
const birthDate = computed(() => {
  if (birthTimeAccuracy.value === 'exact') {
    return exactBirthDate.value || ''
  }

  if (!estimatedBirthDate.value || !selectedEstimatedTimeOption.value) {
    return ''
  }

  if (isEstimatedDateOnly.value) {
    return estimatedBirthDate.value
  }

  return `${estimatedBirthDate.value} ${selectedEstimatedTimeOption.value.time}`
})
const estimatedModeHint = computed(() => {
  if (!estimatedTimeSlot.value) {
    return '请选择一个大概时段，或明确选择“未知时辰”。'
  }

  if (isEstimatedDateOnly.value) {
    return '当前仅按生日趋势排盘，时柱细节会保守展示。'
  }

  return `当前按“${selectedEstimatedTimeOption.value.label}”估算时刻，结果页会同步标记为估算模式。`
})

const baziStrategyExpanded = ref(false)
const baziSubmitIssues = ref([])

const baziStrategySummary = computed(() => {
  const modeText = versionMode.value === 'pro' ? '专业版' : '简化版'
  const accuracyText = birthTimeAccuracy.value === 'exact' ? '精确时刻' : '估算时刻'
  return `先用${modeText} + ${accuracyText}完成一次排盘，再按结果决定是否继续深入。`
})

const baziStrategyDetails = computed(() => ([
  {
    key: 'mode',
    title: versionMode.value === 'pro' ? '当前选择：专业版' : '当前选择：简化版',
    description: versionMode.value === 'pro'
      ? '会补充出生地、进阶结构和后续分析入口，适合已经确定要看更完整结论时使用。'
      : '先看命局轮廓与核心提示，适合第一次体验或只想快速确认整体方向。'
  },
  {
    key: 'accuracy',
    title: birthTimeAccuracy.value === 'exact' ? '当前时间策略：精确到分钟' : '当前时间策略：估算时刻 / 未知时辰',
    description: birthTimeAccuracy.value === 'exact'
      ? '精确时间更适合看时柱、起运点和后续流年细节；如果暂时记不清，再切换估算模式即可。'
      : '估算模式适合先拿到方向参考，结果页会明确提示当前精度，避免把估算口径误读成精确结论。'
  },
  {
    key: 'pricing',
    title: '提交节奏',
    description: isFirstBazi.value
      ? '当前仍保留首次免费资格；填写完再提交，系统会在关键步骤前再次确认。'
      : '当前按单次排盘计费；若你想先确认投入节奏，可先保存结果、回看记录，再决定是否继续深入解读。'
  }
]))

const baziSubmitSummaryText = computed(() => {
  if (!baziSubmitIssues.value.length) {
    return ''
  }

  return `已整理出 ${baziSubmitIssues.value.length} 个待处理项，点一下即可定位或直接处理。`
})

const gender = ref('male')
const loading = ref(false)
const result = ref(null)
const currentPoints = ref(0)
const accountStatus = ref('loading')
const fortunePricingStatus = ref('loading')
const aiPricingStatus = ref('loading')
const aiAnalysisCost = ref(AI_ANALYSIS_DEFAULT_COST)
const confirmVisible = ref(false)

const saving = ref(false)
const versionMode = ref('pro') // 统一使用专业版（AI分析版）
const isFirstBazi = ref(true) // 是否首次排盘

const loadingStep = ref(1) // 加载步骤
const stepIntervalRef = ref(null) // 步骤动画定时器引用
const activeNames = ref(['basic']) // 折叠面板默认展开“命盘信息”

// AI解盘相关
const aiPrompt = ref('')
const aiAnalyzing = ref(false)
const aiAnalysisResult = ref(null)
const aiStreamContent = ref('')
const aiLoadingTime = ref(0)
const aiAbortController = ref(null)
const aiLoadingTimer = ref(null)

const isUnauthorizedResult = (settledResult) => {
  if (!settledResult) {
    return false
  }

  if (settledResult.status === 'fulfilled') {
    return settledResult.value?.code === 401
  }

  return settledResult.reason?.response?.status === 401
}

const syncCurrentPoints = (remainingPoints, fallbackCost = 0) => {

  const parsedRemainingPoints = Number(remainingPoints)
  if (Number.isFinite(parsedRemainingPoints)) {
    currentPoints.value = parsedRemainingPoints
  } else {
    const parsedFallbackCost = Number(fallbackCost)
    if (Number.isFinite(parsedFallbackCost) && parsedFallbackCost > 0) {
      currentPoints.value = Math.max(0, currentPoints.value - parsedFallbackCost)
    }
  }

  window.dispatchEvent(new CustomEvent('points-updated', {
    detail: {
      balance: currentPoints.value,
    },
  }))
}



// 流年运势相关
const fortunePointsCost = ref({
  yearly_fortune: null,
  dayun_analysis: null,
  dayun_chart: null,
})
const selectedYear = ref(new Date().getFullYear())
const yearlyFortuneResult = ref(null)
const yearlyFortuneLoading = ref(false)
const lastAnalyzedYear = ref(null)
const isCompactViewport = ref(false)

const updateViewportState = () => {
  isCompactViewport.value = window.innerWidth <= 520
}


// 大运分析相关
const selectedDayunIndex = ref(0)
const dayunAnalysisResult = ref(null)
const dayunAnalysisLoading = ref(false)
const lastAnalyzedDayunIndex = ref(null)
const dayunChartData = ref(null)
const dayunChartLoading = ref(false)
const dayunScoring = ref(false) // AI 大运评分 loading 状态


// 积分消耗确认对话框
const pointsConfirmVisible = ref(false)
const pointsConfirmType = ref('') // 'yearly', 'dayun', 'chart', 'ai'
const pointsConfirmData = ref({})


const resetDerivedAnalysisState = () => {
  yearlyFortuneResult.value = null
  dayunAnalysisResult.value = null
  dayunChartData.value = null
  aiAnalysisResult.value = null
  aiStreamContent.value = ''
  aiPrompt.value = ''
  lastAnalyzedYear.value = null
  lastAnalyzedDayunIndex.value = null
  selectedYear.value = new Date().getFullYear()
  selectedDayunIndex.value = 0

  activeNames.value = getDefaultActiveNames()

  if (aiAbortController.value) {
    aiAbortController.value.abort()
  }

  if (aiLoadingTimer.value) {
    clearInterval(aiLoadingTimer.value)
    aiLoadingTimer.value = null
  }

  aiAnalyzing.value = false
  aiLoadingTime.value = 0
  aiAbortController.value = null
}

const resetCurrentResult = () => {
  resetDerivedAnalysisState()
  result.value = null
  activeTab.value = 'chart'
}

// 版本提示
const versionHint = computed(() => 'AI 专业分析版：包含完整排盘、大运流年、AI 深度解读')
const resultModeLabel = computed(() => 'AI 专业版结果')
const showAdvancedResultSections = computed(() => true)
const birthTimeAccuracyLabel = computed(() => {
  if (birthTimeAccuracy.value === 'exact') {
    return '精确到分钟'
  }

  if (isEstimatedDateOnly.value) {
    return '未知时辰 · 仅生日趋势'
  }

  return selectedEstimatedTimeOption.value
    ? `估算时刻 · ${selectedEstimatedTimeOption.value.shortLabel}`
    : '待确认时段'
})

const locationContextLabel = computed(() => {
  return '默认北京时间'
})
const resultContextNote = computed(() => {
  if (birthTimeAccuracy.value === 'estimated') {
    if (isEstimatedDateOnly.value) {
      return '当前按“未知时辰”仅基于生日趋势排盘，尤其时柱、起运点与流年细节更适合做方向参考。'
    }

    if (selectedEstimatedTimeOption.value) {
      return `当前按“${selectedEstimatedTimeOption.value.label}”估算时刻排盘，尤其时柱与流年细节更适合做方向参考。`
    }
  }

  if (!showAdvancedResultSections.value) {

    return ''
  }

  return ''
})
const getDefaultActiveNames = () => (showAdvancedResultSections.value ? ['basic', 'interpretation', 'fortune'] : ['basic', 'interpretation'])

const formatWuxingScore = (value) => {
  const numericValue = Number(value)
  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  return Number(numericValue.toFixed(2)).toString()
}

const wuxingDistributionItems = computed(() => {
  const wuxingOrder = ['金', '木', '水', '火', '土']
  const stats = result.value?.bazi?.wuxing_stats || {}
  const normalizedStats = wuxingOrder.map((name) => {
    const numericValue = Number(stats[name] ?? 0)
    return {
      name,
      value: Number.isFinite(numericValue) ? Math.max(0, numericValue) : 0,
    }
  })
  const total = normalizedStats.reduce((sum, item) => sum + item.value, 0)

  return normalizedStats.map((item) => {
    const width = Math.min(100, (item.value / WUXING_WEIGHT_MAX) * 100)
    const share = total > 0 ? (item.value / total) * 100 : 0

    return {
      ...item,
      width: Number.isFinite(width) ? Number(width.toFixed(1)) : 0,
      displayValue: formatWuxingScore(item.value),
      shareText: total > 0 ? `占比 ${share.toFixed(1)}%` : '占比 0%',
    }
  })
})

const isAccountReady = computed(() => accountStatus.value === 'ready')
const isGuestAccount = computed(() => accountStatus.value === 'guest')
const isGuestFortunePricing = computed(() => fortunePricingStatus.value === 'guest')

const isFortunePricingReady = computed(() => fortunePricingStatus.value === 'ready')
const isAiPricingReady = computed(() => aiPricingStatus.value === 'ready' || aiPricingStatus.value === 'fallback')


const confirmDialogConfig = computed(() => {
  if (isFirstBazi.value) {
    return {
      title: '首次排盘确认',
      actionText: '开始排盘',
    }
  }

  return {
    title: '确认排盘',
    actionText: '确认排盘',
  }
})

const canStartBazi = computed(() => {
  if (!birthDate.value || !isAccountReady.value) {
    return false
  }

  return isFirstBazi.value || currentPoints.value >= BAZI_BASE_COST.value
})

const startBaziButtonText = computed(() => {
  if (!birthDate.value) {
    return '请选择出生日期'
  }

  if (accountStatus.value === 'loading') {
    return '账户信息查询中...'
  }

  if (accountStatus.value === 'guest') {
    return '请先登录后排盘'
  }

  if (accountStatus.value === 'error') {
    return '请先同步账户信息'
  }

  return isFirstBazi.value ? '首次免费排盘' : '开始排盘'
})

const clearBaziSubmitIssues = () => {
  baziSubmitIssues.value = []
}

const focusBaziField = async (selector) => {
  if (!selector) {
    return
  }

  await nextTick()
  const target = document.querySelector(selector)
  if (!(target instanceof HTMLElement)) {
    return
  }

  target.scrollIntoView({ behavior: 'smooth', block: 'center' })
  const focusable = target.querySelector('input, textarea, button, [tabindex]:not([tabindex="-1"])')
  if (focusable instanceof HTMLElement) {
    focusable.focus({ preventScroll: true })
  }
}

const handleBaziIssue = (issue) => {
  if (issue?.handler) {
    issue.handler()
    return
  }

  if (issue?.route) {
    router.push(issue.route)
    return
  }

  focusBaziField(issue?.selector)
}

const buildBaziSubmitIssues = () => {
  const issues = []

  if (birthTimeAccuracy.value === 'estimated') {
    if (!estimatedBirthDate.value) {
      issues.push({
        key: 'estimated-date',
        actionLabel: '补充出生日期',
        message: '估算模式下仍需先选择出生日期。',
        selector: '[data-bazi-field="birth-time"]'
      })
    }

    if (!estimatedTimeSlot.value) {
      issues.push({
        key: 'estimated-slot',
        actionLabel: '选择时段',
        message: '请选择一个大概时段，或明确标记为未知时辰。',
        selector: '[data-bazi-field="birth-time"]'
      })
    }
  } else if (!birthDate.value) {
    issues.push({
      key: 'exact-date',
      actionLabel: '填写出生时间',
      message: '请先选择精确到分钟的出生日期时间。',
      selector: '[data-bazi-field="birth-time"]'
    })
  }

  if (!gender.value) {
    issues.push({
      key: 'gender',
      actionLabel: '确认性别',
      message: '排盘前还需要确认性别。',
      selector: '[data-bazi-field="gender"]'
    })
  }

  if (isGuestAccount.value) {
    issues.push({
      key: 'guest',
      actionLabel: '先去登录',
      message: '登录后才能同步积分、免费资格和提交说明。',
      route: '/login'
    })
  }

  if (accountStatus.value === 'error') {
    issues.push({
      key: 'account-error',
      actionLabel: '重新获取账户状态',
      message: '账户信息还没同步成功，刷新后再提交更稳。',
      handler: () => loadPoints()
    })
  }

  if (!isFirstBazi.value && currentPoints.value < BAZI_BASE_COST.value) {
    issues.push({
      key: 'points',
      actionLabel: '去充值或补积分',
      message: `当前积分不足，本次排盘还需要 ${BAZI_BASE_COST.value} 积分。`,
      route: '/recharge'
    })
  }

  return issues
}

const openBaziHistoryCenter = () => {
  router.push('/profile')
}

const continueBaziJourney = async () => {
  activeTab.value = showAdvancedResultSections.value ? 'career' : 'personality'
  await nextTick()
  const selector = showAdvancedResultSections.value ? '.tools-section-wrapper, .fortune-section-wrapper' : '.ai-section-wrapper, .bazi-analysis'
  const target = document.querySelector(selector)
  if (target instanceof HTMLElement) {
    target.scrollIntoView({ behavior: 'smooth', block: 'start' })
    return
  }

  ElMessage.info(showAdvancedResultSections.value ? '已切到进阶分析区，继续往下看即可。' : '已切到性格与 AI 解读区，继续往下看即可。')
}

watch([
  birthDate,
  estimatedBirthDate,
  estimatedTimeSlot,
  birthTimeAccuracy,
  gender,
  accountStatus,
  currentPoints,
  versionMode
], () => {
  if (baziSubmitIssues.value.length) {
    clearBaziSubmitIssues()
  }
})

const getFortuneToolCost = (type) => {
  const costKeyMap = {
    yearly: 'yearly_fortune',
    dayun: 'dayun_analysis',
    chart: 'dayun_chart',
  }

  const rawCost = fortunePointsCost.value[costKeyMap[type]]
  const parsedCost = Number(rawCost)
  return Number.isFinite(parsedCost) ? parsedCost : null
}

const getPointsConfirmTitle = (type = '') => {
  const titleMap = {
    yearly: '流年运势分析',
    dayun: '大运运势评分',
    chart: '运势K线图',
    ai: 'AI 智能解盘',
  }

  return titleMap[type] || '积分功能'
}

const getPointsConfirmCost = (type = '') => {
  if (type === 'ai') {
    return aiAnalysisCost.value
  }

  const cost = getFortuneToolCost(type)
  return Number.isFinite(cost) ? cost : 0
}

const getFortuneToolTagText = (type) => {

  if (fortunePricingStatus.value === 'loading') {
    return '价格查询中'
  }

  if (isGuestAccount.value || isGuestFortunePricing.value) {
    return '登录后显示'
  }

  if (fortunePricingStatus.value === 'error') {
    return '说明稍后确认'
  }

  const cost = getFortuneToolCost(type)
  if (!Number.isFinite(cost)) {
    return '说明待确认'
  }


  return cost > 0 ? `消耗${cost}积分` : '本次免费'
}

const canUseFortuneTool = (type) => {
  if (!isAccountReady.value || !isFortunePricingReady.value) {
    return false
  }

  const cost = getFortuneToolCost(type)
  return Number.isFinite(cost) && currentPoints.value >= cost
}

const getFortuneToolActionText = (type, readyText) => {
  if (accountStatus.value === 'loading' || fortunePricingStatus.value === 'loading') {
    return '价格查询中'
  }

  if (isGuestAccount.value || isGuestFortunePricing.value) {
    return '请先登录'
  }

  if (accountStatus.value === 'error' || fortunePricingStatus.value === 'error') {
    return '请先刷新当前说明'
  }

  const cost = getFortuneToolCost(type)
  if (!Number.isFinite(cost)) {
    return '说明待确认'
  }

  if (cost > 0 && currentPoints.value < cost) {
    return `积分不足（需${cost}积分）`
  }

  return readyText
}


watch(birthTimeAccuracy, (mode) => {
  if (mode === 'estimated') {
    if (!estimatedBirthDate.value && exactBirthDate.value) {
      estimatedBirthDate.value = exactBirthDate.value.slice(0, 10)
    }

    if (!estimatedTimeSlot.value && exactBirthDate.value) {
      const clockMatch = exactBirthDate.value.match(/(\d{2}:\d{2})/)
      estimatedTimeSlot.value = clockMatch ? resolveEstimatedTimeSlotByClock(clockMatch[1]) : ''
    }
    return
  }

  if (!exactBirthDate.value && estimatedBirthDate.value && selectedEstimatedTimeOption.value && !isEstimatedDateOnly.value) {
    exactBirthDate.value = `${estimatedBirthDate.value} ${selectedEstimatedTimeOption.value.time}`
  }
})


watch(selectedYear, (newYear, oldYear) => {
  if (newYear === oldYear) {
    return
  }

  if (lastAnalyzedYear.value !== null && newYear !== lastAnalyzedYear.value) {
    yearlyFortuneResult.value = null
  }
})

watch(selectedDayunIndex, (newIndex, oldIndex) => {
  if (newIndex === oldIndex) {
    return
  }

  if (lastAnalyzedDayunIndex.value !== null && newIndex !== lastAnalyzedDayunIndex.value) {
    dayunAnalysisResult.value = null
  }
})

const resolveAiAnalysisCost = (clientConfig = {}) => {

  const costs = clientConfig?.points?.costs || {}
  const candidates = [
    costs.ai_analysis,
    costs.aiAnalysis,
    costs.bazi_ai_analysis,
    clientConfig?.ai_analysis_cost,
  ]

  for (const candidate of candidates) {
    const parsed = Number(candidate)
    if (Number.isFinite(parsed)) {
      return Math.max(0, parsed)
    }
  }

  return null
}

const aiPricingTagText = computed(() => {
  if (accountStatus.value === 'loading' || aiPricingStatus.value === 'loading') {
    return '价格查询中'
  }

  if (accountStatus.value === 'error') {
    return '账户稍后确认'
  }

  if (aiPricingStatus.value === 'fallback') {
    return `预计消耗${aiAnalysisCost.value}积分`
  }

  return aiAnalysisCost.value > 0 ? `消耗${aiAnalysisCost.value}积分` : '本次免费'
})

const canStartAiAnalysis = computed(() => {
  if (!isAccountReady.value || !isAiPricingReady.value) {
    return false
  }

  return currentPoints.value >= aiAnalysisCost.value
})

const aiActionText = computed(() => {
  if (accountStatus.value === 'loading') {
    return '账户查询中'
  }

  if (aiPricingStatus.value === 'loading') {
    return '价格查询中'
  }

  if (accountStatus.value === 'error') {
    return '请先刷新账户信息'
  }

  if (!isAiPricingReady.value) {
    return '请先刷新价格信息'
  }

  return currentPoints.value < aiAnalysisCost.value ? `积分不足（需${aiAnalysisCost.value}积分）` : '开始AI解盘'
})

const needsFortunePriceRecovery = computed(() => accountStatus.value === 'error' || fortunePricingStatus.value === 'error')

const fortunePriceRecoveryText = computed(() => {
  if (accountStatus.value === 'error') {
    return '当前账户状态还没同步成功，重新获取后即可继续查看积分与操作说明。'
  }

  if (fortunePricingStatus.value === 'error') {
    return '深度工具说明还没同步成功，刷新后即可继续流年分析、大运评分和运势 K 线。'
  }

  return ''
})
const aiNeedsAccountRecovery = computed(() => accountStatus.value === 'error')
const aiRecoveryText = computed(() => {
  return aiNeedsAccountRecovery.value
    ? 'AI 解盘依赖当前账户积分，重新获取账户状态后即可继续使用。'
    : ''
})


const refreshFortunePricing = () => {
  loadPoints()
}


// 获取当前积分和首次排盘状态
const loadPoints = async ({ silent = false } = {}) => {
  accountStatus.value = 'loading'
  fortunePricingStatus.value = 'loading'
  aiPricingStatus.value = 'loading'

  const preloadRequestConfig = {
    silent: true,
    skipGlobalError: true,
    skipAuthRedirect: true,
  }

  const [accountResult, pricingResult, clientConfigResult] = await Promise.allSettled([
    getPointsBalance(preloadRequestConfig),
    getFortunePointsCost(preloadRequestConfig),
    getClientConfig({ silent: true, skipGlobalError: true }),
  ])

  // 从 clientConfig 中提取八字排盘动态费用
  const clientConfigData = clientConfigResult.status === 'fulfilled' ? clientConfigResult.value?.data : null
  if (clientConfigData?.points?.costs?.bazi != null) {
    const dynamicBaziCost = Number(clientConfigData.points.costs.bazi)
    if (Number.isFinite(dynamicBaziCost) && dynamicBaziCost >= 0) {
      BAZI_BASE_COST.value = dynamicBaziCost
    }
  }


  const accountResponse = accountResult.status === 'fulfilled' ? accountResult.value : null
  if (accountResponse?.code === 200) {
    currentPoints.value = Number(accountResponse.data?.balance ?? 0)
    isFirstBazi.value = accountResponse.data?.first_bazi !== false
    accountStatus.value = 'ready'
  } else if (isUnauthorizedResult(accountResult)) {
    currentPoints.value = 0
    accountStatus.value = 'guest'
  } else {
    currentPoints.value = 0
    accountStatus.value = 'error'
    if (!silent) {
      ElMessage.error(accountResponse?.message || '获取账户信息失败，请尝试重新获取')
    }
  }

  const pricingResponse = pricingResult.status === 'fulfilled' ? pricingResult.value : null
  if (pricingResponse?.code === 200) {
    fortunePointsCost.value = {
      yearly_fortune: pricingResponse.data?.yearly_fortune ?? null,
      dayun_analysis: pricingResponse.data?.dayun_analysis ?? null,
      dayun_chart: pricingResponse.data?.dayun_chart ?? null,
    }
    fortunePricingStatus.value = 'ready'
  } else {
    fortunePointsCost.value = {
      yearly_fortune: null,
      dayun_analysis: null,
      dayun_chart: null,
    }

    if (isUnauthorizedResult(pricingResult)) {
      fortunePricingStatus.value = 'guest'
    } else {
      fortunePricingStatus.value = 'error'
      if (!silent) {
        ElMessage.error(pricingResponse?.message || '获取深度工具说明失败，请稍后重试')
      }
    }
  }

  const clientConfigResponse = clientConfigResult.status === 'fulfilled' ? clientConfigResult.value : null

  if (clientConfigResponse?.code === 200) {
    const resolvedAiCost = resolveAiAnalysisCost(clientConfigResponse.data || clientConfigData)
    if (Number.isFinite(resolvedAiCost)) {
      aiAnalysisCost.value = resolvedAiCost
      aiPricingStatus.value = 'ready'
    } else {
      aiAnalysisCost.value = AI_ANALYSIS_DEFAULT_COST
      aiPricingStatus.value = 'fallback'
    }
  } else {
    aiAnalysisCost.value = AI_ANALYSIS_DEFAULT_COST
    aiPricingStatus.value = 'fallback'
  }
}



// 显示积分消耗确认对话框
const showPointsConfirm = (type, data = {}) => {
  const isAiAction = type === 'ai'
  const isPricingReady = isAiAction ? isAiPricingReady.value : isFortunePricingReady.value

  if (isGuestAccount.value || (!isAiAction && isGuestFortunePricing.value)) {
    ElMessage.warning('请先登录后再继续使用深度分析')
    return
  }

  if (!isAccountReady.value || !isPricingReady) {
    ElMessage.warning(isAiAction ? 'AI 解盘说明还在同步，请稍后再试' : '当前说明还在同步，请稍后再试')
    return
  }

  const cost = getPointsConfirmCost(type)
  if (!Number.isFinite(cost)) {
    ElMessage.warning(isAiAction ? 'AI 解盘说明暂未同步完成，请稍后重试' : '当前说明暂未同步完成，请稍后重试')
    return
  }


  if (currentPoints.value < cost) {
    ElMessage.warning(`积分不足，需要${cost}积分，请先签到领取积分`)
    return
  }
  
  pointsConfirmType.value = type
  pointsConfirmData.value = data
  pointsConfirmVisible.value = true
}


// 确认消耗积分
const confirmPointsConsume = async () => {
  pointsConfirmVisible.value = false
  
  switch (pointsConfirmType.value) {
    case 'yearly':
      await getYearlyFortuneAnalysis()
      break
    case 'dayun':
      await getDayunFortuneAnalysis()
      break
    case 'chart':
      await getDayunChartData()
      break
    case 'ai':
      await startAiAnalysisCore()
      break
  }
}


// 获取流年运势分析
const getYearlyFortuneAnalysis = async () => {
  if (!result.value?.id) return
  
  yearlyFortuneLoading.value = true
  try {
    const response = await getYearlyFortune({
      bazi_id: result.value.id,
      year: selectedYear.value
    })
    
    if (response.code === 200) {
      yearlyFortuneResult.value = response.data
      lastAnalyzedYear.value = selectedYear.value
      syncCurrentPoints(response.data.remaining_points)
      ElMessage.success('流年运势分析完成！')


    } else {
      ElMessage.error(response.message || '分析失败')
    }
  } catch (error) {
    ElMessage.error('分析失败，请稍后重试')
  } finally {
    yearlyFortuneLoading.value = false
  }
}

// 获取大运运势分析
/**
 * 排盘后自动触发 AI 大运评分，更新 result.dayun 中的 score
 */
const triggerDayunAiScoring = async () => {
  if (!result.value?.dayun?.length || !result.value?.bazi) return
  dayunScoring.value = true
  try {
    const recordId = result.value?.id || 0
    const res = await scoreDayunAi(result.value.bazi, result.value.dayun, null, recordId)
    if (res.code === 200 && res.data?.scores) {
      const scores = res.data.scores
      // 将 AI 评分写入 result.dayun
      result.value.dayun = result.value.dayun.map((yun, idx) => {
        const aiScore = scores[idx]
        if (aiScore !== undefined) {
          const score = Math.max(20, Math.min(95, aiScore))
          return {
            ...yun,
            score,
            trend_level: score >= 75 ? 'positive' : score >= 50 ? 'neutral' : 'cautious'
          }
        }
        return yun
      })
    }
  } catch (e) {
    // AI 评分失败，静默处理，保留本地评分
  } finally {
    dayunScoring.value = false
  }
}

const getDayunFortuneAnalysis = async () => {
  if (!result.value?.id) return
  
  dayunAnalysisLoading.value = true
  try {
    const response = await getDayunAnalysis({
      bazi_id: result.value.id,
      dayun_index: selectedDayunIndex.value
    })
    
    if (response.code === 200) {
      dayunAnalysisResult.value = response.data
      lastAnalyzedDayunIndex.value = selectedDayunIndex.value
      syncCurrentPoints(response.data.remaining_points)
      ElMessage.success('大运运势分析完成！')


    } else {
      ElMessage.error(response.message || '分析失败')
    }
  } catch (error) {
    ElMessage.error('分析失败，请稍后重试')
  } finally {
    dayunAnalysisLoading.value = false
  }
}

// 获取大运K线图数据
const getDayunChartData = async () => {
  if (!result.value?.id) return
  
  dayunChartLoading.value = true
  try {
    const response = await getDayunChartApi({
      bazi_id: result.value.id
    })
    
    if (response.code === 200) {
      dayunChartData.value = response.data
      syncCurrentPoints(response.data.remaining_points)
      ElMessage.success('运势K线图生成完成！')

    } else {
      ElMessage.error(response.message || '生成失败')
    }
  } catch (error) {
    ElMessage.error('生成失败，请稍后重试')
  } finally {
    dayunChartLoading.value = false
  }
}

// 获取评分颜色
const getScoreColor = (score) => {
  if (score >= 80) return '#67c23a'
  if (score >= 60) return '#e6a23c'
  if (score >= 40) return '#f56c6c'
  return '#909399'
}

// 获取评分等级样式
const getScoreClass = (score) => {
  if (score >= 80) return 'excellent'
  if (score >= 60) return 'good'
  if (score >= 40) return 'average'
  return 'poor'
}

// 显示确认对话框
const showConfirm = () => {
  clearBaziSubmitIssues()
  const issues = buildBaziSubmitIssues()

  if (issues.length) {
    baziSubmitIssues.value = issues
    handleBaziIssue(issues[0])
    ElMessage.warning('提交前还有信息未完成，已帮你定位到第一个问题')
    return
  }
  
  // 积分不足前置拦截
  if (!isFirstBazi.value && currentPoints.value < BAZI_BASE_COST.value) {
    ElMessageBox.confirm(
      '当前积分不足，是否前往签到或充值获取积分？',
      '积分不足',
      {
        confirmButtonText: '去获取积分',
        cancelButtonText: '取消',
        type: 'warning',
      }
    ).then(() => {
      router.push('/profile')
    }).catch(() => {})
    return
  }

  // 首次排盘直接计算，不显示确认框
  if (isFirstBazi.value) {
    calculateBazi()
  } else {
    confirmVisible.value = true
  }
}


// 确认排盘
const confirmCalculate = async () => {
  confirmVisible.value = false
  await calculateBazi()
}

const calculateBazi = async () => {
  loading.value = true
  loadingStep.value = 1
  
  // 模拟步骤动画
  stepIntervalRef.value = setInterval(() => {
    if (loadingStep.value < 4) {
      loadingStep.value++
    }
  }, 400)
  
  try {
    const response = await calculateBaziApi({
      birthDate: birthDate.value,
      gender: gender.value,
      location: '',
      mode: versionMode.value,
      calendarType: calendarType.value,
    })
    
    clearInterval(stepIntervalRef.value)
    stepIntervalRef.value = null
    loadingStep.value = 4
    
    // 延迟一下让用户看到完成状态
    await new Promise(resolve => setTimeout(resolve, 300))
    
    if (response.code === 200) {
      trackSubmit('bazi_calculate', true, { mode: versionMode.value })
      result.value = response.data
      activeNames.value = getDefaultActiveNames()
      syncCurrentPoints(response.data.remaining_points)
      isFirstBazi.value = false
      ElMessage.success('排盘成功！为你生成详细的命理解读')
      // 排盘成功后，异步触发 AI 大运评分
      triggerDayunAiScoring()

    } else {
      trackSubmit('bazi_calculate', false, { mode: versionMode.value, error: response.message })
      ElMessage.error(response.message || '排盘失败')
      // 如果是积分不足，刷新积分
      if (response.code === 403) {
        loadPoints({ silent: true })
      }
    }
  } catch (error) {
    trackSubmit('bazi_calculate', false, { mode: versionMode.value, error: error.message })
    trackError('bazi_calculate_error', error.message)
    ElMessage.error('网络错误，请稍后重试')
  } finally {
    if (stepIntervalRef.value) {
      clearInterval(stepIntervalRef.value)
      stepIntervalRef.value = null
    }
    loading.value = false
    loadingStep.value = 1
  }
}

onMounted(() => {
  trackPageView('bazi')
  updateViewportState()
  window.addEventListener('resize', updateViewportState)
  loadPoints({ silent: true })

  if (route.query.tab && ['chart', 'personality', 'career', 'fortune'].includes(route.query.tab)) {
    activeTab.value = route.query.tab
  }

  // 从历史记录恢复排盘结果
  const recordId = parseInt(route.query.record_id, 10)
  if (recordId > 0) {
    loadHistoryRecord(recordId)
  }
})

// 从历史记录恢复排盘结果
async function loadHistoryRecord(recordId) {
  loading.value = true
  try {
    const res = await getBaziRecord(recordId)
    if (res.code === 200 && res.data) {
      result.value = res.data
      activeNames.value = getDefaultActiveNames()
      isFirstBazi.value = false
      // 如果有缓存的 AI 大运评分，合并到 dayun 中
      const scores = res.data.dayun_scores || {}
      if (res.data.dayun && Object.keys(scores).length > 0) {
        result.value.dayun = res.data.dayun.map((yun, idx) => {
          const aiScore = scores[idx]
          if (aiScore !== undefined) {
            const score = Math.max(20, Math.min(95, aiScore))
            return { ...yun, score, trend_level: score >= 75 ? 'positive' : score >= 50 ? 'neutral' : 'cautious' }
          }
          return yun
        })
      }
    } else {
      ElMessage.warning('历史记录加载失败，请重新排盘')
    }
  } catch (e) {
    ElMessage.warning('历史记录加载失败，请重新排盘')
  } finally {
    loading.value = false
  }
}

// 组件卸载时清理定时器
onUnmounted(() => {
  window.removeEventListener('resize', updateViewportState)

  if (aiLoadingTimer.value) {
    clearInterval(aiLoadingTimer.value)
    aiLoadingTimer.value = null
  }
  if (stepIntervalRef.value) {
    clearInterval(stepIntervalRef.value)
    stepIntervalRef.value = null
  }
})

// 保存结果
const saveResult = async () => {
  saving.value = true
  try {
    // 保存到本地存储
    const savedResults = JSON.parse(localStorage.getItem('bazi_saved') || '[]')
    savedResults.unshift({
      id: result.value.id,
      date: new Date().toISOString(),
      bazi: result.value.bazi,
      analysis: result.value.analysis
    })
    // 最多保存50条
    if (savedResults.length > 50) {
      savedResults.pop()
    }
    localStorage.setItem('bazi_saved', JSON.stringify(savedResults))
    ElMessage.success('已保存到当前设备；云端历史请以个人中心记录为准')
  } catch (error) {
    ElMessage.error('保存失败')
  } finally {
    saving.value = false
  }
}

// 判断是否当前大运（根据出生日期计算当前年龄）
const isCurrentDaYun = (yun) => {
  if (!birthDate.value) return false
  
  // 计算当前年龄
  const birth = new Date(birthDate.value)
  const now = new Date()
  let age = now.getFullYear() - birth.getFullYear()
  
  // 判断是否已过生日
  const monthDiff = now.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) {
    age--
  }
  
  return age >= yun.age_start && age <= yun.age_end
}

// 分享结果
const buildBaziShareText = (includeFullDetails = false) => {
  if (!includeFullDetails) {
    return [
      '我刚在太初命理完成了一次八字排盘。',
      '这次结果主要帮我梳理了性格优势、发展方向和未来节奏。',
      '如果你也想先做一版低风险参考，可以先从摘要版体验开始。',
      '快来测测你的八字吧！'
    ].join('\n')
  }

  return [
    '我在太初命理完成了一次八字排盘',
    `日主：${result.value?.bazi?.day_master || ''}（${result.value?.bazi?.day_master_wuxing || ''}）`,
    `八字：${result.value?.bazi?.year?.gan || ''}${result.value?.bazi?.year?.zhi || ''} ${result.value?.bazi?.month?.gan || ''}${result.value?.bazi?.month?.zhi || ''} ${result.value?.bazi?.day?.gan || ''}${result.value?.bazi?.day?.zhi || ''} ${result.value?.bazi?.hour?.gan || ''}${result.value?.bazi?.hour?.zhi || ''}`,
    '快来测测你的八字吧！'
  ].filter(Boolean).join('\n')
}

const shareBaziText = async (shareText, clipboardSuccessText) => {
  if (navigator.share) {
    await navigator.share({
      title: '我的八字排盘结果',
      text: shareText
    })
    return
  }

  if (!navigator.clipboard?.writeText) {
    throw new Error('clipboard-unavailable')
  }

  await navigator.clipboard.writeText(shareText)
  ElMessage.success(clipboardSuccessText)
}

const baziShareSummary = computed(() => {
  if (!result.value?.bazi) return '我在太初命理测算了八字，结果很准！'
  const dm = result.value.bazi.day_master || ''
  const dmWuxing = result.value.bazi.day_master_wuxing || ''
  return `我的日主是${dmWuxing}${dm}，快来看看你的八字命盘吧！`
})

const baziShareTags = computed(() => {
  if (!result.value?.bazi) return []
  const tags = []
  if (result.value.bazi.day_master) tags.push(`日主${result.value.bazi.day_master}`)
  if (result.value.bazi.day_master_wuxing) tags.push(`五行属${result.value.bazi.day_master_wuxing}`)
  return tags
})

// 重新排盘（原升级到专业版入口，现统一为重新排盘）
const upgradeToProVersion = () => {
  resetCurrentResult()
  ElMessage.info('请重新填写信息并提交排盘')
}

// 十神样式分类
const getShishenClass = (shishen) => {
  if (!shishen) return ''
  const classMap = {
    '比肩': 'shishen--bijian',
    '劫财': 'shishen--jiecai',
    '食神': 'shishen--shishen',
    '伤官': 'shishen--shangguan',
    '偏财': 'shishen--piancai',
    '正财': 'shishen--zhengcai',
    '七杀': 'shishen--qisha',
    '正官': 'shishen--zhengguan',
    '偏印': 'shishen--pianyin',
    '正印': 'shishen--zhengyin',
  }
  return classMap[shishen] || ''
}

// 当前年份流年数据
const currentYearLiunian = computed(() => {
  if (!result.value?.liunian) return null
  const currentYear = new Date().getFullYear()
  return result.value.liunian.find(y => y.year === currentYear) || result.value.liunian[0] || null
})

const shareResult = async () => {
  if (!result.value?.bazi) {
    ElMessage.warning('暂无排盘结果可分享')
    return
  }

  let includeFullDetails = false

  try {
    await ElMessageBox.confirm(
      '为保护隐私，默认推荐摘要分享，不包含完整八字信息。若你确认对方知情且愿意查看完整命盘，再选择“包含完整八字”。',
      '选择分享方式',
      {
        confirmButtonText: '包含完整八字',
        cancelButtonText: '仅分享摘要',
        distinguishCancelAndClose: true,
        type: 'warning',
      }
    )

    includeFullDetails = true
  } catch (actionOrError) {
    if (actionOrError === 'cancel') {
      includeFullDetails = false
    } else if (actionOrError === 'close' || actionOrError?.name === 'AbortError') {
      return
    } else {
      ElMessage.error('分享失败，请稍后重试')
      return
    }
  }

  try {
    await shareBaziText(
      buildBaziShareText(includeFullDetails),
      includeFullDetails ? '完整八字分享内容已复制到剪贴板' : '摘要分享内容已复制到剪贴板'
    )

    if (!includeFullDetails) {
      ElMessage.info('已按摘要版分享，默认省略完整八字信息')
    }
  } catch (shareError) {
    if (shareError?.name !== 'AbortError') {
      ElMessage.error(shareError?.message === 'clipboard-unavailable' ? '当前环境不支持自动复制，请手动复制分享内容' : '复制失败，请手动复制')
    }
  }
}

// AI解盘
const startAiAnalysis = () => {
  showPointsConfirm('ai')
}

const startAiAnalysisCore = async () => {
  if (isGuestAccount.value) {
    ElMessage.warning('请先登录后再使用 AI 解盘')
    return
  }

  if (!isAccountReady.value || !isAiPricingReady.value) {
    ElMessage.warning('AI 解盘说明还在同步，请稍后再试')
    return
  }


  if (currentPoints.value < aiAnalysisCost.value) {
    ElMessage.warning('积分不足，请先签到领取积分')
    return
  }

  aiAnalyzing.value = true

  aiStreamContent.value = ''
  aiLoadingTime.value = 60
  
  // 创建AbortController用于取消请求
  aiAbortController.value = new AbortController()
  
  // 启动倒计时
  aiLoadingTimer.value = setInterval(() => {
    if (aiLoadingTime.value > 0) {
      aiLoadingTime.value--
    } else {
      clearInterval(aiLoadingTimer.value)
    }
  }, 1000)
  
  try {
    // 尝试使用流式API
      const response = await analyzeBaziAiStream(result.value.bazi, aiPrompt.value, aiAbortController.value?.signal, result.value.dayun || [], result.value.id || 0)
    let streamRemainingPoints = null

    if (response.ok && response.headers.get('content-type')?.includes('text/event-stream')) {
      // 流式响应
      const reader = response.body.getReader()
      const decoder = new TextDecoder()
      
      let fullContent = ''
      
      while (true) {
        // 检查是否被取消
        if (aiAbortController.value?.signal?.aborted) {
          reader.cancel()
          break
        }
        
        const { done, value } = await reader.read()
        if (done) break
        
        const chunk = decoder.decode(value, { stream: true })
        const lines = chunk.split('\n')
        
        for (const line of lines) {
          if (line.startsWith('data: ')) {
            const data = line.slice(6)
            if (data === '[DONE]') continue
            
            try {
              const parsed = JSON.parse(data)
              const parsedRemainingPoints = Number(
                parsed?.remaining_points ?? parsed?.data?.remaining_points ?? parsed?.result?.remaining_points
              )
              if (Number.isFinite(parsedRemainingPoints)) {
                streamRemainingPoints = parsedRemainingPoints
              }

              if (parsed.choices?.[0]?.delta?.content) {
                const content = parsed.choices[0].delta.content
                fullContent += content
                aiStreamContent.value = fullContent
              }
            } catch (e) {
              // 忽略解析错误
            }
          }
        }
      }
      
      if (!aiAbortController.value?.signal?.aborted) {
        aiAnalysisResult.value = {
          analysis: fullContent,
          model: 'AI'
        }

        syncCurrentPoints(streamRemainingPoints, aiAnalysisCost.value)
      }
    } else {
      // 非流式响应
      const res = await analyzeBaziAi(result.value.bazi, aiPrompt.value, aiAbortController.value?.signal, result.value.dayun || [], result.value.id || 0)
      if (res.code === 200) {
        aiAnalysisResult.value = res.data
        syncCurrentPoints(res.data?.remaining_points, aiAnalysisCost.value)
      } else {


        ElMessage.error(res.message || 'AI解盘失败')
      }
    }
  } catch (error) {
    if (error.name === 'AbortError') {
      ElMessage.info('已取消AI分析')
    } else {
      ElMessage.error('AI解盘服务暂时不可用，请稍后重试')
    }
  } finally {
    aiAnalyzing.value = false
    clearInterval(aiLoadingTimer.value)
    aiLoadingTime.value = 0
    aiAbortController.value = null
  }
}

// 取消AI分析
const cancelAiAnalysis = () => {
  if (aiAbortController.value) {
    aiAbortController.value.abort()
  }
  aiAnalyzing.value = false
  clearInterval(aiLoadingTimer.value)
  aiLoadingTime.value = 0
  ElMessage.info('已取消AI分析')
}

// 清除AI结果
const clearAiResult = () => {
  aiAnalysisResult.value = null
  aiStreamContent.value = ''
  aiPrompt.value = ''
}

// 格式化AI内容（净化HTML并处理换行）
const formatAiContent = (content) => {
  if (!content) return ''
  // 先净化HTML，防止XSS攻击
  const cleanContent = sanitizeHtml(content, false) // 先转为纯文本
  // 再处理换行
  return cleanContent
    .replace(/\n\n/g, '</p><p>')
    .replace(/\n/g, '<br>')
    .replace(/^(.+)$/, '<p>$1</p>')
}

return {
  // 状态
  activeTab,
  calendarType,
  birthTimeAccuracy,
  exactBirthDate,
  estimatedBirthDate,
  estimatedTimeSlot,
  selectedEstimatedTimeOption,
  isEstimatedDateOnly,
  birthDate,
  estimatedModeHint,
  estimatedTimeOptions,
  baziStrategyExpanded,
  baziSubmitIssues,
  baziStrategySummary,
  baziStrategyDetails,
  baziSubmitSummaryText,
  gender,
  loading,
  result,
  currentPoints,
  accountStatus,
  fortunePricingStatus,
  aiPricingStatus,
  aiAnalysisCost,
  confirmVisible,
  saving,
  versionMode,
  isFirstBazi,
  loadingStep,
  activeNames,
  aiPrompt,
  aiAnalyzing,
  aiAnalysisResult,
  aiStreamContent,
  aiLoadingTime,

  // 流年 & 大运
  fortunePointsCost,
  selectedYear,
  yearlyFortuneResult,
  yearlyFortuneLoading,
  lastAnalyzedYear,
  isCompactViewport,
  selectedDayunIndex,
  dayunAnalysisResult,
  dayunAnalysisLoading,
  lastAnalyzedDayunIndex,
  dayunChartData,
  dayunChartLoading,
  dayunScoring,
  pointsConfirmVisible,
  pointsConfirmType,
  pointsConfirmData,

  // 计算属性
  versionHint,
  resultModeLabel,
  showAdvancedResultSections,
  birthTimeAccuracyLabel,
  locationContextLabel,
  resultContextNote,
  wuxingDistributionItems,
  isAccountReady,
  isGuestAccount,
  isGuestFortunePricing,
  isFortunePricingReady,
  isAiPricingReady,
  confirmDialogConfig,
  canStartBazi,
  startBaziButtonText,
  aiPricingTagText,
  canStartAiAnalysis,
  aiActionText,
  needsFortunePriceRecovery,
  fortunePriceRecoveryText,
  aiNeedsAccountRecovery,
  aiRecoveryText,
  baziShareSummary,
  baziShareTags,

  // 常量
  BAZI_BASE_COST,

  // 方法
  resetCurrentResult,
  handleBaziIssue,
  openBaziHistoryCenter,
  continueBaziJourney,
  getFortuneToolCost,
  getPointsConfirmTitle,
  getPointsConfirmCost,
  getFortuneToolTagText,
  canUseFortuneTool,
  getFortuneToolActionText,
  refreshFortunePricing,
  showPointsConfirm,
  confirmPointsConsume,
  getScoreColor,
  getScoreClass,
  showConfirm,
  confirmCalculate,
  saveResult,
  isCurrentDaYun,
  shareResult,
  startAiAnalysis,
  cancelAiAnalysis,
  clearAiResult,
  formatAiContent,
  formatWuxingScore,
  upgradeToProVersion,
  getShishenClass,
  currentYearLiunian,
}
} // end useBazi
