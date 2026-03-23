import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import DOMPurify from 'dompurify'
import { Male, Female, UserFilled } from '@element-plus/icons-vue'

import { getHehunPricing, calculateHehun, getHehunHistory, exportHehunReport } from '../../api'



import { trackPageView, trackEvent, trackSubmit, trackError } from '../../utils/tracker'



export function useHehun() {
/**
 * HTML净化函数 - 防止XSS攻击
 * 使用DOMPurify库进行专业清理
 */
const router = useRouter()
const HEHUN_LOCAL_FREE_PREVIEW_STORAGE_KEY = 'hehun_local_free_preview_v1'

const sanitizeHtml = (html) => {

  if (!html) return ''
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'u', 'p', 'br', 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li'],
    ALLOWED_ATTR: ['class', 'style']
  })
}


const birthPrecisionOptions = [
  { value: 'exact', label: '精确时分', desc: '已知具体出生时间，结果最完整' },
  { value: 'range', label: '大概时段', desc: '记得是早上或晚上，可先按时段估算' },
  { value: 'unknown', label: '未知时辰', desc: '只有生日，也能先看合婚趋势' },
]

const birthTimeRangeOptions = [
  { value: 'before-dawn', label: '凌晨', hint: '00:00-05:59', time: '03:30' },
  { value: 'morning', label: '早晨', hint: '06:00-08:59', time: '07:30' },
  { value: 'forenoon', label: '上午', hint: '09:00-11:59', time: '10:30' },
  { value: 'noon', label: '中午', hint: '12:00-13:59', time: '12:30' },
  { value: 'afternoon', label: '下午', hint: '14:00-17:59', time: '15:30' },
  { value: 'evening', label: '晚上', hint: '18:00-23:59', time: '19:30' },
]

const birthTimeRangeMap = birthTimeRangeOptions.reduce((acc, option) => {
  acc[option.value] = option
  return acc
}, {})

// 表单数据
const form = reactive({
  maleName: '',
  maleBirthDate: '',
  maleBirthPrecision: 'exact',
  maleBirthTimeRange: '',
  femaleName: '',
  femaleBirthDate: '',
  femaleBirthPrecision: 'exact',
  femaleBirthTimeRange: '',
  useAi: true,
})

const hehunStrategyExpanded = ref(false)
const hehunSubmitIssues = ref([])

const roleCopyMap = {
  male: {
    short: '男方',
    panel: '男方信息',
    bazi: '男方八字',
    namePlaceholder: '输入男方姓名',
  },
  female: {
    short: '女方',
    panel: '女方信息',
    bazi: '女方八字',
    namePlaceholder: '输入女方姓名',
  },
}

const getRoleCopy = (role) => roleCopyMap[role] || roleCopyMap.male
const getRoleLabel = (role) => getRoleCopy(role).short
const getRolePanelTitle = (role) => getRoleCopy(role).panel
const getRoleBaziTitle = (role) => getRoleCopy(role).bazi
const getRoleNamePlaceholder = (role) => getRoleCopy(role).namePlaceholder
const resolveRoleIcon = (role) => {
  return role === 'female' ? Female : Male
}


// 状态

const isLoading = ref(false)
const exporting = ref(false)
const freeResult = ref(null)
const premiumResult = ref(null)
const pricing = ref(null)
const pricingLoading = ref(true)
const pricingError = ref('')
const unlockLoading = ref(false)
const unlockError = ref(null)
const history = ref([])
const historyLoading = ref(false)
const historyLoaded = ref(false)
const historyError = ref('')
const activeHistoryId = ref(null)
const historySectionRef = ref(null)
const localFreePreview = ref(null)
const showAllFreeSuggestions = ref(false)


const historyTierCopy = {

  free: { label: '免费预览', cta: '查看基础预览' },
  premium: { label: '完整版', cta: '查看完整报告' },
  vip: { label: 'VIP完整版', cta: '查看会员报告' },
}

// 维度名称映射
const dimensionNames = {
  year: '生肖契合',
  day: '日柱关系',
  wuxing: '五行互补',
  hechong: '干支配合',
  nayin: '纳音互感',
  shensha: '神煞互补',
  traditional: '传统合婚',
}

const getBirthPrecisionLabel = (precision) => {
  if (precision === 'range') return '大概时段'
  if (precision === 'unknown') return '未知时辰'
  return '精确时分'
}

const getBirthPrecisionBadge = (precision) => {
  if (precision === 'range') return '中可信'
  if (precision === 'unknown') return '趋势参考'
  return '高可信'
}

const getBirthInputLabel = (precision) => (precision === 'exact' ? '出生日期与时间' : '出生日期')
const getBirthPickerType = (precision) => (precision === 'exact' ? 'datetime' : 'date')
const getBirthPickerPlaceholder = (precision) => (precision === 'exact' ? '选择出生日期时间（精确到分钟）' : '选择出生日期')
const getBirthPickerFormat = (precision) => (precision === 'exact' ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD')
const getBirthPickerValueFormat = (precision) => (precision === 'exact' ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD')


const getBirthPrecisionHint = (precision) => {
  if (precision === 'range') {
    return '若只记得大概是清晨、下午或晚上，可先选择时段；系统会用代表时刻估算时柱。'
  }
  if (precision === 'unknown') {
    return '若完全不清楚出生时辰，也可以只填生日，系统会按中午排盘并降低可信度提示。'
  }
  return '填写到分钟可获得更完整的时柱、流年和婚配细节判断。'
}

const getBirthFieldHelper = (precision) => {
  if (precision === 'range') {
    return '先选择生日，再显式选择一个大概出生时段。'
  }
  if (precision === 'unknown') {
    return '仅用生日先看趋势，涉及时柱的结论会保守处理。'
  }
  return '建议尽量填写准确时间，减少时柱偏差。'
}

const normalizeBirthInputValue = (value, nextPrecision) => {
  const trimmed = String(value || '').trim()
  if (!trimmed) {
    return ''
  }

  const match = trimmed.match(/^(\d{4}-\d{2}-\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/)
  if (!match) {
    return nextPrecision === 'exact' ? trimmed : trimmed.slice(0, 10)
  }

  const [, date, hour, minute] = match
  if (nextPrecision === 'exact') {
    return hour && minute ? `${date} ${hour}:${minute}` : ''
  }


  return date
}

const resolveStoredTimeRange = (birthValue = '', fallback = '') => {
  const trimmed = String(birthValue || '').trim()
  const match = trimmed.match(/^\d{4}-\d{2}-\d{2}[ T](\d{2}):(\d{2})/)
  if (!match) {
    return fallback
  }

  return resolveTimeRangeByClock(`${match[1]}:${match[2]}`)
}

const handleBirthPrecisionChange = (role, nextPrecision) => {
  const birthDateKey = `${role}BirthDate`
  const precisionKey = `${role}BirthPrecision`
  const timeRangeKey = `${role}BirthTimeRange`
  const currentValue = form[birthDateKey]

  form[precisionKey] = nextPrecision
  form[birthDateKey] = normalizeBirthInputValue(currentValue, nextPrecision)
  form[timeRangeKey] = ''
}


const getBirthConfidenceCopy = (precision, roleLabel) => {
  if (precision === 'range') {
    return `${roleLabel}当前按大概时段估算，适合先看关系趋势；涉及时柱的细项判断会保守处理。`
  }
  if (precision === 'unknown') {
    return `${roleLabel}当前只提供生日，系统会默认按中午排盘，结论更适合做方向参考。`
  }
  return `${roleLabel}已使用精确时间输入，合婚结果可信度最高。`
}

const resolveBirthDatePayload = (value, precision, timeRange) => {
  if (!value) {
    return ''
  }

  if (precision === 'exact') {
    return value.replace('T', ' ')
  }

  const dateOnly = value.slice(0, 10)
  if (precision === 'unknown') {
    return dateOnly
  }

  const matchedRange = birthTimeRangeMap[timeRange]
  return matchedRange ? `${dateOnly} ${matchedRange.time}` : ''
}


const resolveTimeRangeByClock = (clock = '') => {
  const [hour = '12'] = clock.split(':')
  const parsedHour = Number(hour)

  if (parsedHour < 6) return 'before-dawn'
  if (parsedHour < 9) return 'morning'
  if (parsedHour < 12) return 'forenoon'
  if (parsedHour < 14) return 'noon'
  if (parsedHour < 18) return 'afternoon'
  return 'evening'
}

const hydrateBirthState = (birthDate) => {
  if (!birthDate) {
    return {
      value: '',
      precision: 'exact',
      timeRange: '',
    }
  }

  const trimmed = String(birthDate).trim()

  if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
    return {
      value: trimmed,
      precision: 'unknown',
      timeRange: '',
    }
  }

  const match = trimmed.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})(?::\d{2})?$/)
  if (!match) {
    return {
      value: normalizeBirthInputValue(trimmed, 'exact'),
      precision: 'exact',
      timeRange: '',
    }
  }

  const [, date, hour, minute] = match
  return {
    value: `${date} ${hour}:${minute}`,
    precision: 'exact',
    timeRange: resolveTimeRangeByClock(`${hour}:${minute}`),
  }
}



const precisionSummaryList = computed(() => ([
  {
    role: getRoleLabel('male'),
    modeLabel: getBirthPrecisionLabel(form.maleBirthPrecision),
    confidence: getBirthPrecisionBadge(form.maleBirthPrecision),
    detail: getBirthConfidenceCopy(form.maleBirthPrecision, getRoleLabel('male')),
  },
  {
    role: getRoleLabel('female'),
    modeLabel: getBirthPrecisionLabel(form.femaleBirthPrecision),
    confidence: getBirthPrecisionBadge(form.femaleBirthPrecision),
    detail: getBirthConfidenceCopy(form.femaleBirthPrecision, getRoleLabel('female')),
  },
]))

const hasReducedPrecision = computed(() => {
  return form.maleBirthPrecision !== 'exact' || form.femaleBirthPrecision !== 'exact'
})

const buildHehunPayload = ({ tier, useAi }) => ({
  maleName: form.maleName || getRoleLabel('male'),
  maleBirthDate: resolveBirthDatePayload(form.maleBirthDate, form.maleBirthPrecision, form.maleBirthTimeRange),
  maleBirthPrecision: form.maleBirthPrecision,
  maleBirthTimeRange: form.maleBirthTimeRange,
  femaleName: form.femaleName || getRoleLabel('female'),
  femaleBirthDate: resolveBirthDatePayload(form.femaleBirthDate, form.femaleBirthPrecision, form.femaleBirthTimeRange),
  femaleBirthPrecision: form.femaleBirthPrecision,
  femaleBirthTimeRange: form.femaleBirthTimeRange,
  tier,
  useAi,
})

const normalizeFingerprintText = (value = '') => String(value || '').trim().toLowerCase()
const buildHehunFingerprint = ({ maleName = '', maleBirthDate = '', femaleName = '', femaleBirthDate = '', score = 0, level = '' } = {}) => ([
  normalizeFingerprintText(maleName),
  normalizeFingerprintText(maleBirthDate),
  normalizeFingerprintText(femaleName),
  normalizeFingerprintText(femaleBirthDate),
  Number(score || 0),
  normalizeFingerprintText(level),
].join('|'))

const readLocalFreePreview = () => {
  try {
    const rawValue = localStorage.getItem(HEHUN_LOCAL_FREE_PREVIEW_STORAGE_KEY)
    if (!rawValue) {
      return null
    }

    const parsedValue = JSON.parse(rawValue)
    const record = parsedValue?.record || parsedValue
    return record && typeof record === 'object' ? record : null
  } catch (error) {
    return null
  }
}

const persistLocalFreePreview = (record) => {
  localFreePreview.value = record

  try {
    localStorage.setItem(HEHUN_LOCAL_FREE_PREVIEW_STORAGE_KEY, JSON.stringify({
      version: 1,
      record,
    }))
  } catch (error) {
  }
}

const clearLocalFreePreview = () => {
  localFreePreview.value = null

  try {
    localStorage.removeItem(HEHUN_LOCAL_FREE_PREVIEW_STORAGE_KEY)
  } catch (error) {
  }
}

const buildLocalFreePreviewRecord = (freePayload) => {
  const maleBirthDate = resolveBirthDatePayload(form.maleBirthDate, form.maleBirthPrecision, form.maleBirthTimeRange)
  const femaleBirthDate = resolveBirthDatePayload(form.femaleBirthDate, form.femaleBirthPrecision, form.femaleBirthTimeRange)
  const score = Number(freePayload?.hehun?.score ?? 0)
  const level = freePayload?.hehun?.level || ''
  const createdAt = freePayload?.created_at || freePayload?.create_time || new Date().toISOString()

  return {
    id: freePayload?.id || `local-free-${Date.now()}`,
    tier: 'free',
    is_local_only: true,
    male_name: form.maleName || '',
    female_name: form.femaleName || '',
    male_birth_date: maleBirthDate,
    female_birth_date: femaleBirthDate,
    male_birth_precision: form.maleBirthPrecision,
    female_birth_precision: form.femaleBirthPrecision,
    male_birth_time_range: form.maleBirthTimeRange,
    female_birth_time_range: form.femaleBirthTimeRange,
    score,
    level,
    level_text: freePayload?.hehun?.level_text || '',
    result: freePayload?.hehun || {},
    male_bazi: freePayload?.male_bazi || {},
    female_bazi: freePayload?.female_bazi || {},
    pricing: freePayload?.pricing || null,
    created_at: createdAt,
    create_time: createdAt,
    fingerprint: buildHehunFingerprint({
      maleName: form.maleName || '',
      maleBirthDate,
      femaleName: form.femaleName || '',
      femaleBirthDate,
      score,
      level,
    }),
  }
}

const isBirthInputComplete = (role) => {

  const birthDateValue = form[`${role}BirthDate`]
  const precision = form[`${role}BirthPrecision`]

  if (!birthDateValue) {
    return false
  }

  if (precision === 'range') {
    return Boolean(form[`${role}BirthTimeRange`])
  }

  return true
}

// 表单验证
const isFormValid = computed(() => {
  return isBirthInputComplete('male') && isBirthInputComplete('female')
})

const hehunStrategySummary = computed(() => {
  const accuracyText = hasReducedPrecision.value ? '当前包含估算输入' : '当前为双精确输入'
  const unlockText = form.useAi ? '解锁时优先走 AI 深度分析' : '解锁时走规则版完整版'
  return `${accuracyText}，先看趋势，再决定是否继续解锁；${unlockText}。`
})

const hehunStrategyDetails = computed(() => ([
  {
    key: 'precision',
    title: '出生时间怎么填最合适',
    description: '知道具体出生时间请选择"精确时分"，结果最准确；只记得大概时段（如早晨、晚上）也可以，系统会自动标注可信度供参考。'
  },
  {
    key: 'flow',
    title: '如何选择分析方案',
    description: '免费预览先展示基础匹配趋势和简单建议，确认值得深入了解后，再解锁完整版获取详细的性格、家庭、事业等五维分析。'
  },
  {
    key: 'ai',
    title: 'AI 深度分析说明',
    description: form.useAi
      ? '已开启 AI 深度分析，解锁后将优先调用 AI 提供个性化解读；如 AI 服务暂不可用，系统会自动切换为传统规则解读并明确提示。'
      : '未开启 AI 深度分析，解锁后将提供传统规则版详细分析；如需更个性化的 AI 解读，可在解锁后重新开启。'
  }
]))

const hehunShareSummary = computed(() => {
  if (!premiumResult.value) return '我在太初命理测算了八字合婚，结果很准！'
  const score = premiumResult.value.hehun?.score || 0
  const level = premiumResult.value.hehun?.level_text || ''
  return `我们的合婚匹配度高达${score}分（${level}），快来看看你们的缘分吧！`
})

const hehunShareTags = computed(() => {
  if (!premiumResult.value) return []
  const tags = []
  if (premiumResult.value.hehun?.score) tags.push(`匹配度${premiumResult.value.hehun.score}分`)
  if (premiumResult.value.hehun?.level_text) tags.push(premiumResult.value.hehun.level_text)
  return tags
})

// 格式化日期时间

const hehunSubmitSummaryText = computed(() => {
  if (!hehunSubmitIssues.value.length) {
    return ''
  }

  return `已整理出 ${hehunSubmitIssues.value.length} 个待处理项，点一下即可直接定位。`
})

const normalizePricingData = (rawPricing) => {
  if (!rawPricing) {
    return null
  }

  if (typeof rawPricing.final === 'number') {
    return {
      final: rawPricing.final,
      original: rawPricing.original ?? rawPricing.final,
      discount: rawPricing.discount ?? 0,
      reason: rawPricing.reason || '',
      isVip: Boolean(rawPricing.is_vip),
    }
  }

  if (typeof rawPricing.unlock_points === 'number') {
    return {
      final: rawPricing.unlock_points,
      original: rawPricing.unlock_points,
      discount: rawPricing.discount_info?.percent ?? 0,
      reason: rawPricing.discount_info?.reason || '',
      isVip: Boolean(rawPricing.is_vip),
    }
  }

  const premiumTier = rawPricing.tier?.premium
  if (!premiumTier) {
    return null
  }

  return {
    final: Number(premiumTier.price ?? 0),
    original: Number(premiumTier.original_price ?? premiumTier.price ?? 0),
    discount: Number(premiumTier.discount?.percent ?? 0),
    reason: premiumTier.discount?.reason || '',
    isVip: Boolean(rawPricing.user_status?.is_vip),
  }
}

const normalizedPricing = computed(() => normalizePricingData(freeResult.value?.pricing || pricing.value))
const pricingStatusText = computed(() => {
  if (normalizedPricing.value) {
    return ''
  }

  if (pricingLoading.value) {
    return '完整版价格加载中，请稍候后再解锁。'
  }

  return pricingError.value || '完整版价格暂时不可用，请稍后重试。'
})

const pricingDisplayText = computed(() => {
  if (normalizedPricing.value) {
    return normalizedPricing.value.final > 0 ? `${normalizedPricing.value.final} 积分` : 'VIP 免费'
  }

  if (pricingLoading.value) {
    return '加载中...'
  }

  return '价格待确认'
})

const canExportReport = computed(() => Boolean(premiumResult.value?.id))
const canUnlockPremium = computed(() => Boolean(freeResult.value) && Boolean(normalizedPricing.value) && !isLoading.value && !unlockLoading.value)
const freeResultRecordButtonText = computed(() => freeResult.value?.is_local_only ? '查看本机暂存结果' : '查看我的记录')
const freeSuggestionList = computed(() => {

  const suggestions = freeResult.value?.hehun?.suggestions
  return Array.isArray(suggestions) ? suggestions.filter((item) => typeof item === 'string' && item.trim()) : []
})
const visibleFreeSuggestions = computed(() => {
  return showAllFreeSuggestions.value ? freeSuggestionList.value : freeSuggestionList.value.slice(0, 3)
})
const hasMoreFreeSuggestions = computed(() => freeSuggestionList.value.length > visibleFreeSuggestions.value.length)
const premiumUnlockMessage = computed(() => {


  const points = normalizedPricing.value?.final
  if (!Number.isFinite(points)) {
    return '完整版价格暂未确认，请稍后再试。'
  }

  if (points <= 0) {
    return form.useAi
      ? '您当前可免费解锁详细报告，并优先启用 AI 深度分析；若 AI 暂不可用，将自动切换为规则解读并明确标注，是否继续？'
      : '您当前可免费解锁详细报告，是否继续？'
  }

  return form.useAi
    ? `解锁详细报告将消耗 ${points} 积分，并优先启用 AI 深度分析；若 AI 暂不可用，将自动切换为规则解读并明确标注，是否继续？`
    : `解锁详细报告将消耗 ${points} 积分，是否继续？`
})

const clearUnlockFeedback = () => {
  unlockError.value = null
  unlockLoading.value = false
}

const hasUnsavedDraft = computed(() => {
  const hasFilledValue = [
    form.maleName,
    form.maleBirthDate,
    form.maleBirthTimeRange,
    form.femaleName,
    form.femaleBirthDate,
    form.femaleBirthTimeRange,
  ].some((value) => String(value || '').trim())

  return hasFilledValue || form.maleBirthPrecision !== 'exact' || form.femaleBirthPrecision !== 'exact'
})

const clearHehunSubmitIssues = () => {
  hehunSubmitIssues.value = []
}

const focusHehunField = async (selector) => {
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

const buildHehunSubmitIssues = () => {
  const issues = []

  if (!form.maleBirthDate) {
    issues.push({
      key: 'male-birth',
      actionLabel: `补充${getRoleLabel('male')}生日`,
      message: `${getRoleLabel('male')}还缺出生日期信息。`,
      selector: '[data-hehun-field="male-birth"]'
    })
  }

  if (form.maleBirthPrecision === 'range' && !form.maleBirthTimeRange) {
    issues.push({
      key: 'male-range',
      actionLabel: `选择${getRoleLabel('male')}时段`,
      message: `当前是大概时段模式，还需要明确选择${getRoleLabel('male')}的出生时段。`,
      selector: '[data-hehun-field="male-range"]'
    })
  }

  if (!form.femaleBirthDate) {
    issues.push({
      key: 'female-birth',
      actionLabel: `补充${getRoleLabel('female')}生日`,
      message: `${getRoleLabel('female')}还缺出生日期信息。`,
      selector: '[data-hehun-field="female-birth"]'
    })
  }

  if (form.femaleBirthPrecision === 'range' && !form.femaleBirthTimeRange) {
    issues.push({
      key: 'female-range',
      actionLabel: `选择${getRoleLabel('female')}时段`,
      message: `当前是大概时段模式，还需要明确选择${getRoleLabel('female')}的出生时段。`,
      selector: '[data-hehun-field="female-range"]'
    })
  }

  return issues
}

const handleHehunIssue = (issue) => {
  focusHehunField(issue?.selector)
}

const openHehunRecords = () => {
  router.push('/profile')
}

const openRechargeCenter = () => {
  router.push('/recharge')
}

const openDailySuggestion = () => {
  router.push('/daily')
}

const scrollToHistorySection = async () => {
  await nextTick()
  historySectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const restoreLocalFreePreview = async () => {
  if (!localFreePreviewRecord.value) {
    return
  }

  applyHistoryDetail(localFreePreviewRecord.value)
  await nextTick()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleFreeResultRecordAction = async () => {
  if (freeResult.value?.is_local_only) {
    await scrollToHistorySection()
    ElMessage.info('这条免费预览当前仅保存在本机，可在本页历史区继续查看。')
    return
  }

  openHehunRecords()
}

watch([

  () => form.maleBirthDate,
  () => form.maleBirthTimeRange,
  () => form.maleBirthPrecision,
  () => form.femaleBirthDate,
  () => form.femaleBirthTimeRange,
  () => form.femaleBirthPrecision,
  () => form.useAi
], () => {
  if (hehunSubmitIssues.value.length) {
    clearHehunSubmitIssues()
  }
})

const escapeHtml = (value = '') => String(value)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;')

const hasAiContent = (value) => {
  if (!value) {
    return false
  }

  if (typeof value === 'string') {
    return value.trim() !== ''
  }

  if (Array.isArray(value)) {
    return value.length > 0
  }

  if (typeof value === 'object') {
    return Object.keys(value).length > 0
  }

  return false
}

const normalizeObjectField = (value, fallback = {}) => {
  if (!value) {
    return fallback
  }

  if (Array.isArray(value)) {
    return value
  }

  if (typeof value === 'object') {
    return value
  }

  if (typeof value !== 'string') {
    return fallback
  }

  const trimmed = value.trim()
  if (!trimmed) {
    return fallback
  }

  try {
    const parsed = JSON.parse(trimmed)
    return parsed && typeof parsed === 'object' ? parsed : fallback
  } catch (error) {
    return fallback
  }
}

const normalizeAiField = (value) => {
  if (!value) {
    return null
  }

  if (typeof value === 'string') {
    const trimmed = value.trim()
    if (!trimmed) {
      return null
    }

    try {
      return JSON.parse(trimmed)
    } catch (error) {
      return trimmed
    }
  }

  if (Array.isArray(value) || typeof value === 'object') {
    return value
  }

  return null
}

const isTruthyFlag = (value) => value === true || value === 1 || value === '1'

const normalizeAnalysisMeta = (value, aiAnalysis = null) => {
  const rawMeta = normalizeObjectField(value, {})
  const normalizedAi = normalizeAiField(aiAnalysis)
  const aiObject = normalizedAi && typeof normalizedAi === 'object' && !Array.isArray(normalizedAi) ? normalizedAi : {}
  const requested = isTruthyFlag(rawMeta.ai_requested)
  const actualAi = isTruthyFlag(rawMeta.is_ai_generated) || isTruthyFlag(aiObject.is_ai_generated)
  let engine = typeof rawMeta.analysis_engine === 'string' ? rawMeta.analysis_engine.trim().toLowerCase() : ''

  if (!['none', 'ai', 'rules'].includes(engine)) {
    if (actualAi) {
      engine = 'ai'
    } else if (requested || normalizedAi) {
      engine = 'rules'
    } else {
      engine = 'none'
    }
  }

  return {
    ai_requested: requested,
    is_ai_generated: actualAi,
    analysis_engine: engine,
    provider: actualAi ? String(rawMeta.provider || aiObject.provider || '').trim() : '',
    fallback_note: !requested || actualAi ? '' : String(rawMeta.fallback_note || aiObject.note || '').trim(),
  }
}

const resolveAnalysisState = (meta = {}) => {
  if (meta.analysis_engine === 'ai') return 'ai'
  if (meta.analysis_engine === 'rules') return 'rules'
  return 'none'
}

const buildAnalysisPresentation = (meta = {}) => {
  const state = resolveAnalysisState(meta)

  if (state === 'ai') {
    return {
      state,
      title: 'AI深度解读',
      note: meta.provider ? `本次由 ${meta.provider} 模型生成。` : '本次由 AI 生成。',
      badgeText: 'AI解读',
      summaryText: '包含 AI 深度解读',
    }
  }

  if (state === 'rules') {
    return {
      state,
      title: '智能解读（规则引擎）',
      note: meta.fallback_note || 'AI 暂不可用，本次已自动切换为规则解读。',
      badgeText: '规则解读',
      summaryText: '本次为规则解读',
    }
  }

  return {
    state,
    title: '未启用 AI',
    note: '',
    badgeText: '未启用AI',
    summaryText: '未启用 AI 扩展',
  }
}

const formatAiAnalysisHtml = (analysis) => {
  const normalized = normalizeAiField(analysis)
  if (!normalized) {
    return ''
  }

  if (typeof normalized === 'string') {
    return `<p>${escapeHtml(normalized)}</p>`
  }

  if (Array.isArray(normalized)) {
    return normalized
      .filter(Boolean)
      .map((item) => `<p>${escapeHtml(item)}</p>`)
      .join('')
  }

  const sections = []

  if (normalized.note) {
    sections.push(`<p class="analysis-note">${escapeHtml(normalized.note)}</p>`)
  }

  if (normalized.summary) {
    sections.push(`<p>${escapeHtml(normalized.summary)}</p>`)
  }

  if (normalized.personality_match) {
    const personality = normalized.personality_match
    const personalityLines = [
      personality.male_personality,
      personality.female_personality,
      personality.match_analysis,
    ].filter(Boolean)

    if (personalityLines.length) {
      sections.push(`
        <h4>性格匹配</h4>
        <p>${escapeHtml(personalityLines.join(' '))}</p>
      `)
    }
  }

  if (normalized.marriage_prospect) {
    sections.push(`<h4>婚姻前景</h4><p>${escapeHtml(normalized.marriage_prospect)}</p>`)
  }

  if (normalized.career_wealth) {
    sections.push(`<h4>事业与财运</h4><p>${escapeHtml(normalized.career_wealth)}</p>`)
  }

  if (normalized.children_fate) {
    sections.push(`<h4>家庭与子女缘</h4><p>${escapeHtml(normalized.children_fate)}</p>`)
  }

  if (Array.isArray(normalized.suggestions) && normalized.suggestions.length) {
    sections.push(`
      <h4>AI建议</h4>
      <ul>${normalized.suggestions.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>
    `)
  }

  if (normalized.auspicious_info) {
    const auspiciousInfo = normalized.auspicious_info
    const lines = [
      Array.isArray(auspiciousInfo.best_years) && auspiciousInfo.best_years.length ? `更适合推进关系的年份：${auspiciousInfo.best_years.join('、')}` : '',
      Array.isArray(auspiciousInfo.auspicious_months) && auspiciousInfo.auspicious_months.length ? `顺势月份：${auspiciousInfo.auspicious_months.join('、')}` : '',
      auspiciousInfo.notes ? `提醒：${auspiciousInfo.notes}` : '',
    ].filter(Boolean)

    if (lines.length) {
      sections.push(`
        <h4>顺势提醒</h4>
        <ul>${lines.map((line) => `<li>${escapeHtml(line)}</li>`).join('')}</ul>
      `)
    }
  }

  if (!sections.length) {
    const fallbackLines = Object.entries(normalized)
      .filter(([, value]) => value !== null && value !== undefined && value !== '')
      .map(([key, value]) => `${key}：${Array.isArray(value) ? value.join('、') : typeof value === 'object' ? JSON.stringify(value) : value}`)

    return fallbackLines.map((line) => `<p>${escapeHtml(line)}</p>`).join('')
  }

  return sections.join('')
}

const hehunDetailSectionLabels = {
  year: '生肖契合',
  day: '日柱关系',
  wuxing: '五行互补',
  hechong: '干支配合',
  nayin: '纳音互感',
}

const buildHehunDetailHtml = (hehun) => {
  const sections = []

  if (hehun.comment) {
    sections.push(`<p>${escapeHtml(hehun.comment)}</p>`)
  }

  const detailEntries = Object.entries(normalizeObjectField(hehun.details, {})).filter(([, value]) => Boolean(value))
  if (detailEntries.length) {
    sections.push(`
      <h4>核心分析</h4>
      <ul>${detailEntries.map(([key, value]) => `<li><strong>${escapeHtml(hehunDetailSectionLabels[key] || key)}</strong>：${escapeHtml(value)}</li>`).join('')}</ul>
    `)
  }

  if (Array.isArray(hehun.highlights) && hehun.highlights.length) {
    sections.push(`
      <h4>关系亮点</h4>
      <ul>${hehun.highlights.map((item) => `<li>${escapeHtml(item?.text || item)}</li>`).join('')}</ul>
    `)
  }

  const traditionalRisk = normalizeObjectField(hehun.traditional_risk, {})
  if (traditionalRisk.warning) {
    sections.push(`
      <h4>传统风险提示</h4>
      <p>${escapeHtml(traditionalRisk.warning)}</p>
    `)
  }

  const traditionalMethods = normalizeObjectField(hehun.traditional_methods, {})
  const traditionalEntries = Object.entries(traditionalMethods).filter(([, value]) => value && typeof value === 'object')
  if (traditionalEntries.length) {
    sections.push(`
      <h4>传统合婚补充</h4>
      <ul>${traditionalEntries.map(([key, value]) => {
        const label = key === 'sanyuan' ? '三元宫位' : key === 'jiugong' ? '九宫关系' : key
        const summary = [value.grade, value.relation?.type || value.type || value.meaning, value.description, value.suggestion].filter(Boolean).join(' · ')
        return `<li><strong>${escapeHtml(label)}</strong>：${escapeHtml(summary || JSON.stringify(value))}</li>`
      }).join('')}</ul>
    `)
  }

  return sections.join('')
}

const normalizeHehunData = (hehun) => {
  const normalized = normalizeObjectField(hehun, {})
  const rawDimensions = normalizeObjectField(normalized.dimensions, {})
  const rawScores = normalizeObjectField(normalized.scores, {})
  const solutions = Array.isArray(normalized.solutions) && normalized.solutions.length
    ? normalized.solutions
    : Array.isArray(normalized.suggestions)
      ? normalized.suggestions
      : []

  return {
    ...normalized,
    dimensions: {
      year: Number(rawDimensions.year ?? rawScores.year ?? 0),
      day: Number(rawDimensions.day ?? rawScores.day ?? 0),
      wuxing: Number(rawDimensions.wuxing ?? rawScores.wuxing ?? 0),
      hechong: Number(rawDimensions.hechong ?? rawScores.hechong ?? 0),
      nayin: Number(rawDimensions.nayin ?? rawScores.nayin ?? 0),
      shensha: Number(rawDimensions.shensha ?? rawScores.shensha ?? 0),
      traditional: Number(rawDimensions.traditional ?? rawScores.traditional ?? 0),
    },
    detail_analysis: normalized.detail_analysis || buildHehunDetailHtml(normalized),
    solutions,
    suggestions: solutions,
  }
}

const normalizeFreeResultData = (payload = {}) => ({
  ...payload,
  tier: payload.tier || 'free',
  hehun: normalizeHehunData(payload.hehun),
  male_bazi: normalizeObjectField(payload.male_bazi, {}),
  female_bazi: normalizeObjectField(payload.female_bazi, {}),
})

const normalizePremiumResultData = (payload = {}) => {
  const aiAnalysis = normalizeAiField(payload.ai_analysis)
  return {
    ...payload,
    tier: payload.tier || 'premium',
    hehun: normalizeHehunData(payload.hehun),
    ai_analysis: aiAnalysis,
    analysis_meta: normalizeAnalysisMeta(payload.analysis_meta || payload.hehun?.analysis_meta, aiAnalysis),
    male_bazi: normalizeObjectField(payload.male_bazi, {}),
    female_bazi: normalizeObjectField(payload.female_bazi, {}),
  }
}

const premiumAiAnalysisHtml = computed(() => sanitizeHtml(formatAiAnalysisHtml(premiumResult.value?.ai_analysis)))
const premiumAnalysisPresentation = computed(() => buildAnalysisPresentation(premiumResult.value?.analysis_meta || {}))

const resolveHistoryTier = (item = {}) => {
  const explicitTier = typeof item.tier === 'string' ? item.tier.trim().toLowerCase() : ''
  if (['free', 'premium', 'vip'].includes(explicitTier)) {
    return explicitTier
  }

  const isPremium = item.is_premium === true || item.is_premium === 1 || item.is_premium === '1'
  const isFree = item.is_premium === false || item.is_premium === 0 || item.is_premium === '0'

  if (isFree) {
    return 'free'
  }

  const pointsCost = Number(item.points_cost ?? 0)
  if (isPremium || pointsCost > 0) {
    return pointsCost > 0 ? 'premium' : 'vip'
  }

  const resultData = normalizeObjectField(item.result, {})
  if (item.is_ai_analysis || hasAiContent(item.ai_analysis) || resultData.detail_analysis || resultData.details || resultData.solutions) {
    return 'vip'
  }

  return 'free'
}

const resolveHistoryAccessLabel = (tier, pointsCost) => {
  if (tier === 'vip') {
    return '会员权益解锁'
  }

  if (tier === 'premium') {
    return pointsCost > 0 ? `${pointsCost} 积分解锁` : '已解锁完整版'
  }

  return '可继续升级完整版'
}

const buildHistorySummary = (tier, analysisPresentation, pointsCost) => {
  if (tier === 'free') {
    return '保留基础匹配分与简评，可继续解锁完整版查看五维分析与化解建议。'
  }

  const accessCopy = tier === 'vip'
    ? '会员完整版记录'
    : pointsCost > 0
      ? `${pointsCost} 积分解锁记录`
      : '已解锁完整版记录'

  return `${accessCopy}，${analysisPresentation.summaryText}，点击可回看完整内容。`
}

const normalizeHistoryItem = (item = {}) => {
  const tier = resolveHistoryTier(item)
  const aiAnalysis = normalizeAiField(item.ai_analysis)
  const resultData = normalizeObjectField(item.result, {})
  const pointsCost = Number(item.points_cost ?? 0)
  const createdAt = item.create_time || item.created_at || ''
  const analysisMeta = normalizeAnalysisMeta(item.analysis_meta || resultData.analysis_meta, aiAnalysis)
  const analysisPresentation = buildAnalysisPresentation(analysisMeta)
  const inputMeta = resultData.input_meta || {}
  const score = Number(item.score ?? resultData.score ?? 0)
  const level = item.level || resultData.level || ''
  const isLocalOnly = Boolean(item.is_local_only)
  const maleBirthDate = item.male_birth_date || inputMeta.male_birth_date || ''
  const femaleBirthDate = item.female_birth_date || inputMeta.female_birth_date || ''
  const fingerprint = item.fingerprint || buildHehunFingerprint({
    maleName: item.male_name || inputMeta.male_name || '',
    maleBirthDate,
    femaleName: item.female_name || inputMeta.female_name || '',
    femaleBirthDate,
    score,
    level,
  })

  return {
    ...item,
    result: resultData,
    ai_analysis: aiAnalysis,
    analysis_meta: analysisMeta,
    male_bazi: normalizeObjectField(item.male_bazi, {}),
    female_bazi: normalizeObjectField(item.female_bazi, {}),
    male_birth_date: maleBirthDate,
    female_birth_date: femaleBirthDate,
    male_birth_precision: item.male_birth_precision || inputMeta.male_birth_precision || '',
    female_birth_precision: item.female_birth_precision || inputMeta.female_birth_precision || '',
    male_birth_time_range: item.male_birth_time_range || inputMeta.male_birth_time_range || '',
    female_birth_time_range: item.female_birth_time_range || inputMeta.female_birth_time_range || '',
    male_birth_time: item.male_birth_time || '',
    female_birth_time: item.female_birth_time || '',
    score,
    level,
    level_text: item.level_text || resultData.level_text || '',
    points_cost: pointsCost,
    tier,
    is_local_only: isLocalOnly,
    fingerprint,
    is_premium: isLocalOnly ? false : tier !== 'free',
    hasAiAnalysis: analysisPresentation.state === 'ai',
    analysisState: analysisPresentation.state,
    analysisBadgeText: isLocalOnly ? '暂存预览' : analysisPresentation.badgeText,
    typeLabel: isLocalOnly ? '本机暂存' : (historyTierCopy[tier]?.label || '历史记录'),
    ctaLabel: isLocalOnly ? '继续查看暂存结果' : (historyTierCopy[tier]?.cta || '查看记录'),
    accessLabel: isLocalOnly ? '仅当前设备暂存' : resolveHistoryAccessLabel(tier, pointsCost),
    summary: isLocalOnly
      ? '云端历史暂未生成，这条免费预览已临时保存在当前设备；点击后可继续查看或升级完整版。'
      : buildHistorySummary(tier, analysisPresentation, pointsCost),
    created_at: createdAt,
    create_time: createdAt,
  }
}

const localFreePreviewRecord = computed(() => {
  if (!localFreePreview.value) {
    return null
  }

  return normalizeHistoryItem(localFreePreview.value)
})

const mergeLocalFreePreviewIntoHistory = (items = []) => {
  if (!localFreePreviewRecord.value) {
    return items
  }

  const hasSyncedRecord = items.some((item) => item.fingerprint === localFreePreviewRecord.value.fingerprint && !item.is_local_only)
  if (hasSyncedRecord) {
    clearLocalFreePreview()
    return items
  }

  return [localFreePreviewRecord.value, ...items.filter((item) => item.id !== localFreePreviewRecord.value.id)]
}

const resolveHistoryList = (payload) => {
  if (Array.isArray(payload)) {
    return payload
  }

  if (Array.isArray(payload?.list)) {
    return payload.list
  }

  return []
}


// 获取定价信息
const loadPricing = async () => {
  pricingLoading.value = true
  pricingError.value = ''

  try {
    const response = await getHehunPricing()
    if (response.code === 200) {
      pricing.value = response.data
      return
    }

    pricing.value = null
    pricingError.value = response.message || '完整版价格加载失败，请稍后重试。'
  } catch (error) {
    pricing.value = null
    pricingError.value = '完整版价格加载失败，请稍后重试。'
  } finally {
    pricingLoading.value = false
  }
}

const syncHistorySelection = async (preferredId = null) => {
  await loadHistory()

  if (!history.value.length) {
    activeHistoryId.value = null
    return null
  }

  const matchedItem = preferredId ? history.value.find((item) => item.id === preferredId) : null
  const targetItem = matchedItem || history.value[0]
  activeHistoryId.value = targetItem?.id || null
  return targetItem || null
}

// 提交表单（免费预览）
const submitForm = async () => {
  clearHehunSubmitIssues()
  const issues = buildHehunSubmitIssues()

  if (issues.length) {
    hehunSubmitIssues.value = issues
    handleHehunIssue(issues[0])
    ElMessage.warning('提交前还有信息未完成，已帮你定位到第一个问题')
    return
  }

  isLoading.value = true
  try {
    const payload = buildHehunPayload({
      tier: 'free',
      useAi: false,
    })
    const response = await calculateHehun(payload)

    if (response.code === 200) {
      trackSubmit('hehun_calculate', true, { tier: 'free' })
      const normalizedFreeResult = normalizeFreeResultData(response.data)
      const localPreviewRecord = buildLocalFreePreviewRecord(normalizedFreeResult)
      persistLocalFreePreview(localPreviewRecord)

      premiumResult.value = null
      freeResult.value = buildHistoryFreeResult(localPreviewRecord, normalizedFreeResult.hehun, normalizedFreeResult.male_bazi, normalizedFreeResult.female_bazi)
      showAllFreeSuggestions.value = false

      clearUnlockFeedback()

      ElMessage.success('基础合婚分析完成')

      try {
        const preferredHistoryId = normalizedFreeResult.id || localPreviewRecord.id
        const matchedHistoryItem = await syncHistorySelection(preferredHistoryId)
        if (matchedHistoryItem) {
          applyHistoryDetail(matchedHistoryItem)
        }
      } catch (historyError) {
      }
    } else {
      trackSubmit('hehun_calculate', false, { tier: 'free', error: response.message })
      ElMessage.error(response.message)
    }

  } catch (error) {
    trackSubmit('hehun_calculate', false, { tier: 'free', error: error.message })
    trackError('hehun_calculate_error', error.message)
    ElMessage.error('合婚分析失败，请重试')
  } finally {
    isLoading.value = false
  }
}

// 解锁付费报告
const unlockPremium = async () => {
  if (!canUnlockPremium.value) {
    ElMessage.warning(pricingStatusText.value || '完整版价格暂未就绪，请稍后再试')
    return
  }

  // 积分不足前置拦截
  if (pricing.value && pricing.value.balance < pricing.value.cost) {
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

  unlockError.value = null

  try {
    await ElMessageBox.confirm(
      premiumUnlockMessage.value,
      '确认解锁',
      {
        confirmButtonText: '确认解锁',
        cancelButtonText: '取消',
        type: 'info',
      }
    )
    
    isLoading.value = true
    unlockLoading.value = true
    const payload = buildHehunPayload({
      tier: 'premium',
      useAi: form.useAi,
    })
    const response = await calculateHehun(payload)
    
    if (response.code === 200) {
      trackSubmit('hehun_calculate', true, { tier: 'premium' })
      const normalizedPremiumResult = normalizePremiumResultData(response.data)
      clearLocalFreePreview()
      freeResult.value = null
      premiumResult.value = normalizedPremiumResult
      window.dispatchEvent(new Event('points-updated'))


      try {
        await syncHistorySelection(normalizedPremiumResult.id)
      } catch (historyError) {
      }

      ElMessage.success('解锁成功！')
    } else {
      trackSubmit('hehun_calculate', false, { tier: 'premium', error: response.message })
      unlockError.value = response.code === 403
        ? '积分不足，请先充值后再解锁详细报告。'
        : (response.message || '解锁失败，请重试。')
      ElMessage.error(unlockError.value)
    }
  } catch (error) {
    if (error !== 'cancel') {
      trackSubmit('hehun_calculate', false, { tier: 'premium', error: error.message })
      trackError('hehun_calculate_error', error.message)
      unlockError.value = '解锁失败，请重试。'
      ElMessage.error(unlockError.value)
    }
  } finally {
    unlockLoading.value = false
    isLoading.value = false
  }
}


const returnToForm = () => {
  freeResult.value = null
  premiumResult.value = null
  showAllFreeSuggestions.value = false
  clearUnlockFeedback()
}


// 重置表单
const resetForm = () => {
  freeResult.value = null
  premiumResult.value = null
  activeHistoryId.value = null
  showAllFreeSuggestions.value = false
  clearUnlockFeedback()

  form.maleName = ''
  form.maleBirthDate = ''
  form.maleBirthPrecision = 'exact'
  form.maleBirthTimeRange = ''
  form.femaleName = ''
  form.femaleBirthDate = ''
  form.femaleBirthPrecision = 'exact'
  form.femaleBirthTimeRange = ''
}




// 导出报告
const exportReport = async () => {
  if (!premiumResult.value?.id) {
    ElMessage.warning('当前历史记录缺少导出标识，请重新加载后再试')
    return
  }
  
  exporting.value = true
  try {
    const response = await exportHehunReport({
      record_id: premiumResult.value.id,
    })

    
    if (response.code === 200) {
      // 下载PDF
      const link = document.createElement('a')
      link.href = response.data.download_url
      const exportMaleName = form.maleName || getRoleLabel('male')
      const exportFemaleName = form.femaleName || getRoleLabel('female')
      link.download = `合婚报告_${exportMaleName}_${exportFemaleName}.pdf`
      link.click()

      ElMessage.success('报告导出成功')
    } else {
      ElMessage.error(response.message)
    }
  } catch (error) {
    ElMessage.error('导出失败，请重试')
  } finally {
    exporting.value = false
  }
}

// 加载历史记录
const loadHistory = async () => {
  historyLoading.value = true
  historyError.value = ''

  try {
    const response = await getHehunHistory({ limit: 5 })
    if (response.code === 200) {
      const normalizedHistory = resolveHistoryList(response.data).map(normalizeHistoryItem)
      history.value = mergeLocalFreePreviewIntoHistory(normalizedHistory)
      if (activeHistoryId.value && !history.value.some((item) => item.id === activeHistoryId.value)) {
        activeHistoryId.value = null
      }
    } else {
      history.value = mergeLocalFreePreviewIntoHistory([])
      historyError.value = response.message || '历史记录加载失败，请稍后重试'
    }
  } catch (error) {
    history.value = mergeLocalFreePreviewIntoHistory([])
    historyError.value = '历史记录加载失败，请稍后重试'
  } finally {

    historyLoading.value = false
    historyLoaded.value = true
  }
}

const getHistoryBirthLabel = (birthDate) => {
  if (!birthDate) {
    return ''
  }

  const match = String(birthDate).trim().match(/^(\d{4}-\d{2}-\d{2})/)
  return match ? match[1] : String(birthDate).trim()
}

const getHistoryPersonLabel = (name, birthDate, roleLabel) => {
  const trimmedName = typeof name === 'string' ? name.trim() : ''
  if (trimmedName) {
    return trimmedName
  }

  const birthLabel = getHistoryBirthLabel(birthDate)
  return birthLabel ? `${roleLabel} ${birthLabel}` : roleLabel
}

const formatHistoryNames = (item = {}) => {
  const maleLabel = getHistoryPersonLabel(item.male_name, item.male_birth_date, getRoleLabel('male'))
  const femaleLabel = getHistoryPersonLabel(item.female_name, item.female_birth_date, getRoleLabel('female'))
  return `${maleLabel} & ${femaleLabel}`
}


const buildHistoryFreeResult = (item, hehunData, maleBaziData, femaleBaziData) => normalizeFreeResultData({
  ...item,
  tier: 'free',
  is_local_only: Boolean(item.is_local_only),
  hehun: {
    ...hehunData,
    suggestions: Array.isArray(hehunData.suggestions) && hehunData.suggestions.length
      ? hehunData.suggestions
      : ['可先查看基础匹配趋势，若需要五维分析和 AI 解读，可继续解锁完整版。'],
  },
  male_bazi: maleBaziData,
  female_bazi: femaleBaziData,
  pricing: item.pricing || null,
  preview_hint: item.is_local_only
    ? '云端历史暂未生成，这次免费预览已临时保存在当前设备；稍后回到本页仍可继续查看。'
    : '这是你之前保存的免费预览记录；如需五维分析和 AI 解读，请重新解锁完整版。',
})


const buildHistoryPremiumResult = (item, hehunData, aiAnalysisData, maleBaziData, femaleBaziData) => normalizePremiumResultData({
  id: item.id,
  tier: item.tier,
  hehun: hehunData,
  ai_analysis: aiAnalysisData,
  analysis_meta: item.analysis_meta,
  male_bazi: maleBaziData,
  female_bazi: femaleBaziData,
})

const toDatetimeLocalValue = (value = '') => {
  const trimmed = String(value || '').trim()
  const match = trimmed.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})/)
  if (!match) {
    return trimmed
  }

  return `${match[1]} ${match[2]}:${match[3]}`
}

const resolveHistoryBirthState = (item, role) => {
  const birthValue = String(item?.[`${role}_birth_date`] || '').trim()
  const precision = item?.[`${role}_birth_precision`] || ''
  const storedTimeRange = item?.[`${role}_birth_time_range`] || ''

  if (precision === 'exact') {
    return {
      value: toDatetimeLocalValue(birthValue),
      precision: 'exact',
      timeRange: resolveStoredTimeRange(birthValue, storedTimeRange),
    }
  }

  if (precision === 'range') {
    return {
      value: birthValue.slice(0, 10),
      precision: 'range',
      timeRange: storedTimeRange || resolveStoredTimeRange(birthValue, ''),
    }
  }

  if (precision === 'unknown') {
    return {
      value: birthValue.slice(0, 10),
      precision: 'unknown',
      timeRange: '',
    }
  }

  return hydrateBirthState(birthValue)
}


// 加载历史记录详情
const applyHistoryDetail = (normalizedItem) => {
  activeHistoryId.value = normalizedItem.id
  showAllFreeSuggestions.value = false

  // 填充表单

  form.maleName = normalizedItem.male_name || ''
  form.femaleName = normalizedItem.female_name || ''

  const maleBirthState = resolveHistoryBirthState(normalizedItem, 'male')
  form.maleBirthDate = maleBirthState.value
  form.maleBirthPrecision = maleBirthState.precision
  form.maleBirthTimeRange = maleBirthState.timeRange

  const femaleBirthState = resolveHistoryBirthState(normalizedItem, 'female')
  form.femaleBirthDate = femaleBirthState.value
  form.femaleBirthPrecision = femaleBirthState.precision
  form.femaleBirthTimeRange = femaleBirthState.timeRange

  const hehunData = normalizeHehunData(normalizedItem.result)
  const aiAnalysisData = normalizeAiField(normalizedItem.ai_analysis)
  const maleBaziData = normalizeObjectField(normalizedItem.male_bazi, {})
  const femaleBaziData = normalizeObjectField(normalizedItem.female_bazi, {})

  if (!hehunData || Object.keys(hehunData).length === 0) {
    ElMessage.warning('合婚结果数据不完整')
    return
  }

  if (normalizedItem.tier === 'free' || !normalizedItem.is_premium) {
    freeResult.value = buildHistoryFreeResult(normalizedItem, hehunData, maleBaziData, femaleBaziData)
    premiumResult.value = null
    clearUnlockFeedback()
    return
  }

  premiumResult.value = buildHistoryPremiumResult(normalizedItem, hehunData, aiAnalysisData, maleBaziData, femaleBaziData)
  freeResult.value = null
  clearUnlockFeedback()
}

const loadHistoryDetail = async (item) => {
  const normalizedItem = normalizeHistoryItem(item)

  try {
    if (!freeResult.value && !premiumResult.value && hasUnsavedDraft.value && activeHistoryId.value !== normalizedItem.id) {
      await ElMessageBox.confirm(
        '当前正在填写的双方信息会被这条历史记录直接覆盖，是否继续载入？',
        '载入历史记录',
        {
          confirmButtonText: '载入历史记录',
          cancelButtonText: '继续填写',
          type: 'warning',
          distinguishCancelAndClose: true,
        }
      )
    }

    applyHistoryDetail(normalizedItem)
  } catch (error) {
    if (error === 'cancel' || error === 'close') {
      return
    }

    ElMessage.error('历史记录数据格式错误，无法加载')
  }
}


// 格式化日期
const formatDate = (dateStr) => {
  const rawValue = typeof dateStr === 'string' ? dateStr.trim() : ''
  if (!rawValue) {
    return '时间待补充'
  }

  const normalizedValue = rawValue.includes('T') ? rawValue : rawValue.replace(' ', 'T')
  const date = new Date(normalizedValue)
  if (Number.isNaN(date.getTime())) {
    return rawValue
  }

  return new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

// 初始化
onMounted(() => {
  trackPageView('hehun')
  localFreePreview.value = readLocalFreePreview()
  loadPricing()
  loadHistory()
})

return {
  // 表单与状态
  form,
  hehunStrategyExpanded,
  hehunSubmitIssues,
  isLoading,
  exporting,
  freeResult,
  premiumResult,
  pricing,
  pricingLoading,
  pricingError,
  unlockLoading,
  unlockError,
  history,
  historyLoading,
  historyLoaded,
  historyError,
  activeHistoryId,
  historySectionRef,
  localFreePreview,
  showAllFreeSuggestions,

  // 常量
  birthPrecisionOptions,
  birthTimeRangeOptions,
  dimensionNames,

  // 计算属性
  precisionSummaryList,
  hasReducedPrecision,
  isFormValid,
  hehunStrategySummary,
  hehunStrategyDetails,
  hehunShareSummary,
  hehunShareTags,
  hehunSubmitSummaryText,
  normalizedPricing,
  pricingStatusText,
  pricingDisplayText,
  canExportReport,
  canUnlockPremium,
  freeResultRecordButtonText,
  freeSuggestionList,
  visibleFreeSuggestions,
  hasMoreFreeSuggestions,
  premiumUnlockMessage,
  hasUnsavedDraft,
  premiumAiAnalysisHtml,
  premiumAnalysisPresentation,
  localFreePreviewRecord,

  // 方法
  sanitizeHtml,
  getRoleLabel,
  getRolePanelTitle,
  getRoleBaziTitle,
  getRoleNamePlaceholder,
  resolveRoleIcon,
  getBirthPrecisionHint,
  getBirthFieldHelper,
  getBirthInputLabel,
  getBirthPickerType,
  getBirthPickerPlaceholder,
  getBirthPickerFormat,
  getBirthPickerValueFormat,
  getBirthPrecisionBadge,
  getBirthConfidenceCopy,
  handleBirthPrecisionChange,
  handleHehunIssue,
  submitForm,
  unlockPremium,
  resetForm,
  exportReport,
  loadHistory,
  loadHistoryDetail,
  formatDate,
  formatHistoryNames,
  restoreLocalFreePreview,
  scrollToHistorySection,
  handleFreeResultRecordAction,
  openHehunRecords,
  openRechargeCenter,
  openDailySuggestion,
  returnToForm,
}
} // end useHehun

