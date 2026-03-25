import { ref, reactive, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getLiuyaoPricing, liuyaoDivination, getLiuyaoHistory, getLiuyaoDetail, deleteLiuyaoRecord, analyzeLiuyaoAi } from '../../api'

import guaData from '../../utils/liuyao.json'





import { trackPageView, trackSubmit, trackError, trackLiuyaoMethodChange, trackLiuyaoSubmitStart, trackLiuyaoSubmitSuccess, trackLiuyaoSubmitFail, trackLiuyaoResultView } from '../../utils/tracker'





export function useLiuyao() {
const methodOptions = [
  { label: '时间起卦', value: 'time', description: '按当前北京时间起卦，适合快速问事。', recommend: true, audience: '新手推荐·快速问事', highlight: '最便捷' },
  { label: '数字起卦', value: 'number', description: '通过数字拆分上下卦，适合已有灵感数字时使用。', audience: '有灵感数字时', highlight: '更有针对性' },
  { label: '手动摇卦', value: 'manual', description: '录入 6 次摇卦结果，满足标准六爻问卦流程。', audience: '传统方式·问卦严谨', highlight: '最传统' },
]

const questionTypeOptions = ['求财', '感情', '事业', '健康', '学业', '出行', '其他']
const quickQuestions = ['我的事业发展如何？', '近期感情运势怎么样？', '这笔投资能成功吗？', '身体健康有什么需要注意吗？', '学业考试能顺利通过吗？']
const yaoLineLabels = ['初爻（下）', '二爻', '三爻', '四爻', '五爻', '上爻（上）']
const yaoResultLineLabels = ['初爻', '二爻', '三爻', '四爻', '五爻', '上爻']
const yaoValueOptions = [

  { label: '老阴（6）', value: 6 },
  { label: '少阳（7）', value: 7 },
  { label: '少阴（8）', value: 8 },
  { label: '老阳（9）', value: 9 },
]
const tianGanOptions = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸']
const diZhiOptions = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']
const yaoNameMap = ['老阴', '少阳', '少阴', '老阳']
const router = useRouter()
const route = useRoute()

const createDefaultForm = () => ({
  question: '',
  useAi: true,
  method: 'time',
  questionType: '其他',
  gender: '男',
  numbers: [null, null],
  yaoResults: [null, null, null, null, null, null],
  riGan: '',
  riZhi: '',
  version: 'professional', // 固定使用AI分析专业版
})

// 表单数据
const form = reactive(createDefaultForm())

// 状态
const isLoading = ref(false)
const isSubmitting = ref(false)
const loadingStep = ref(0) // 0=idle 1=起卦中 2=卦象成形 3=AI解读中
const result = ref(null)
const pricing = ref(null)
const pricingLoading = ref(true)
const pricingError = ref('')
const history = ref([])
const historyLoading = ref(false)
const historyLoaded = ref(false)
const historyError = ref('')
const submitErrors = ref([])
const showHistory = ref(false)
const showAdvancedSettings = ref(false)
const historyListRef = ref(null)
const currentBeijingTimestamp = ref(Date.now())

// AI分析相关状态
const aiAnalyzing = ref(false)

// 监听起卦方式变化
watch(() => form.method, (newMethod) => {
  trackLiuyaoMethodChange(newMethod)
})

let beijingTimer = null
let historyTriggerEl = null



const currentBeijingTime = computed(() => new Intl.DateTimeFormat('zh-CN', {
  timeZone: 'Asia/Shanghai',
  year: 'numeric',
  month: '2-digit',
  day: '2-digit',
  hour: '2-digit',
  minute: '2-digit',
  second: '2-digit',
  hour12: false,
}).format(new Date(currentBeijingTimestamp.value)))


const currentMethodDescription = computed(() => {
  return methodOptions.find((item) => item.value === form.method)?.description || ''
})

const currentMethodAudience = computed(() => {
  return methodOptions.find((item) => item.value === form.method)?.audience || ''
})

const clearSubmitErrors = () => {
  submitErrors.value = []
}

const focusLiuyaoField = async (selector) => {
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

const handleSubmitIssue = (issue) => {
  if (issue?.handler) {
    issue.handler()
    return
  }

  if (issue?.route) {
    router.push(issue.route)
    return
  }

  focusLiuyaoField(issue?.selector)
}

const buildSubmitIssues = () => {
  const issues = []
  const trimmedQuestion = form.question.trim()

  if (pricingLoading.value) {
    issues.push({
      key: 'pricing-loading',
      actionLabel: '等待价格同步',
      message: '正在同步当前占卜价格，等价格卡片刷新后再提交更稳。',
      selector: '[data-liuyao-field="pricing"]'
    })
  } else if (pricingError.value || !pricing.value) {
    issues.push({
      key: 'pricing-error',
      actionLabel: '重新获取价格',
      message: pricingError.value || '当前价格信息还没同步成功，先刷新后再提交。',
      handler: () => loadPricing(),
      selector: '[data-liuyao-field="pricing"]'
    })
  }

  if (!trimmedQuestion) {
    issues.push({
      key: 'question-empty',
      actionLabel: '先写下你的问题',
      message: '六爻更适合问一件具体的事，先把问题补充完整。',
      selector: '[data-liuyao-field="question"]'
    })
  } else if (trimmedQuestion.length < 2) {
    issues.push({
      key: 'question-short',
      actionLabel: '把问题写具体一点',
      message: '问题至少写到 2 个字，越具体越容易得到可判断的结果。',
      selector: '[data-liuyao-field="question"]'
    })
  }

  if (form.method === 'number' && !Number.isFinite(form.numbers[0])) {
    issues.push({
      key: 'number-method',
      actionLabel: '补第一个数字',
      message: '数字起卦至少需要先填写第一个 1-999 的数字。',
      selector: '[data-liuyao-field="number-method"]'
    })
  }

  if (form.method === 'manual' && form.yaoResults.some((item) => !Number.isFinite(item))) {
    issues.push({
      key: 'manual-method',
      actionLabel: '补齐 6 次摇卦结果',
      message: '手动摇卦需要从初爻到上爻依次填满 6 次结果。',
      selector: '[data-liuyao-field="manual-method"]'
    })
  }

  return issues
}

const submitSummaryText = computed(() => {
  if (!submitErrors.value.length) {
    return ''
  }

  return `已整理出 ${submitErrors.value.length} 个待处理项，点一下即可直接定位。`
})


const submitButtonText = computed(() => {
  if (pricingLoading.value) {
    return '正在同步价格...'
  }

  if (pricingError.value || !pricing.value) {
    return '请先重新获取价格'
  }

  if (pricing.value.is_first_free) {
    return '首次免费起卦'
  }

  if (pricing.value.is_vip_free) {
    return 'VIP免费起卦'
  }

  const cost = Number(pricing.value.cost)
  if (Number.isFinite(cost) && cost > 0) {
    return `确认并消耗${cost}积分`
  }

  return '开始占卜'
})


const shouldShowRemainingPoints = computed(() => {

  if (!result.value || result.value.is_history) {
    return false
  }

  return result.value.remaining_points !== null && result.value.remaining_points !== undefined
})




const savedStatusText = computed(() => (result.value?.is_history ? '来自历史记录' : '已自动保存到历史记录'))
const historyTriggerText = computed(() => (
  history.value.length > 0 ? `查看历史记录 (${history.value.length}条)` : '查看历史记录'
))

const openHistoryDialog = (event) => {
  historyTriggerEl = event?.currentTarget instanceof HTMLElement ? event.currentTarget : null
  showHistory.value = true
}

const restoreHistoryTriggerFocus = () => {
  if (historyTriggerEl instanceof HTMLElement) {
    historyTriggerEl.focus()
  }
  historyTriggerEl = null
}

const focusHistoryDialogPrimaryAction = async () => {
  await nextTick()
  const target = historyListRef.value?.querySelector('.history-select, .delete-btn, .el-button')
  if (target instanceof HTMLElement) {
    target.focus()
  }
}

watch(showHistory, (visible) => {
  if (visible) {
    focusHistoryDialogPrimaryAction()
  }
})

watch([
  () => form.question,
  () => form.method,
  () => form.numbers[0],
  () => form.numbers[1],
  () => form.yaoResults.join(','),
  pricingLoading,
  pricingError
], () => {
  if (submitErrors.value.length) {
    clearSubmitErrors()
  }
})

const formatDateTime = (dateStr) => {


  const rawValue = typeof dateStr === 'string' ? dateStr.trim() : ''
  if (!rawValue) {
    return ''
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




const reportUiError = (action, error, userMessage = '') => {


  if (userMessage) {
    ElMessage.error(userMessage)
  }
}

const isMovingYao = (yao) => Number(yao) === 0 || Number(yao) === 3
const isYangYao = (yao) => Number(yao) === 1 || Number(yao) === 3

const getYaoName = (yao) => {
  const value = Number(yao)
  return yaoNameMap[value] || '未知'
}

// 爻标记
const getYaoMark = (yao) => {
  const value = Number(yao)
  if (value === 0) return '×' // 老阴
  if (value === 3) return '○' // 老阳
  return '' // 少阴少阳
}

// 获取卦象符号
const getGuaSymbol = (guaCode) => {
  if (!guaCode) return ''
  const gua = guaData.find(item => item.symbol === guaCode || item.name.includes(guaCode))
  return gua ? gua.symbol : ''
}

const parseYaoResult = (value, fallback = '') => {
  if (Array.isArray(value)) {
    return value.map((item) => normalizeYaoCode(item))
  }

  if (typeof value === 'string' && value.trim()) {
    const trimmed = value.trim()
    const parsed = safeJsonParse(trimmed, null)
    if (Array.isArray(parsed)) {
      return parsed.map((item) => normalizeYaoCode(item))
    }

    if (trimmed.includes(',')) {
      return trimmed.split(',').map((item) => normalizeYaoCode(item))
    }

    if (/^[0-3]{6}$/.test(trimmed) || /^[6-9]{6}$/.test(trimmed)) {
      return trimmed.split('').map((item) => normalizeYaoCode(item))
    }
  }

  if (typeof fallback === 'string' && /^[0-3]{6}$/.test(fallback)) {
    return fallback.split('').map((item) => normalizeYaoCode(item))
  }

  return []
}

const normalizeYaoCode = (value) => {
  const numeric = Number(value)
  if (Number.isNaN(numeric)) {
    return 1
  }

  if (numeric >= 0 && numeric <= 3) {
    return numeric
  }

  return ({ 6: 0, 7: 1, 8: 2, 9: 3 })[numeric] ?? 1
}

const safeJsonParse = (value, fallback = null) => {
  if (typeof value !== 'string') {
    return fallback
  }

  try {
    return JSON.parse(value)
  } catch {
    return fallback
  }
}

const normalizeAiAnalysis = (value) => {
  if (!value) {
    return null
  }

  if (typeof value === 'string') {
    return { content: value }
  }

  if (typeof value === 'object' && value.content) {
    return value
  }

  return null
}

const normalizeFushen = (value) => {
  if (!value || typeof value !== 'object') {
    return null
  }

  const name = String(value.name || '').trim()
  if (!name) {
    return null
  }

  return {
    name,
    element: value.element || '',
    relation: value.relation || '',
    status: value.status || ''
  }
}




const getYinYangLabel = (value) => (isYangYao(value) ? '阳爻' : '阴爻')

const describeLineChange = (line = {}) => {
  const fromName = line.name || getYaoName(line.value)
  const toName = line.bian_name || getYaoName(line.bian_value ?? line.value)
  return line.is_moving ? `${fromName} → ${toName}` : '静爻不变'
}

const formatMovingLineMeta = (line = {}) => {
  return [
    line.liuqin ? `六亲：${line.liuqin}` : '',
    line.liushen ? `六神：${line.liushen}` : '',
    line.di_zhi ? `纳甲：${line.di_zhi}` : '',
  ].filter(Boolean).join(' ｜ ')
}

const normalizeLineDetail = (line = {}, index = 0, fallbackValue = 1, liuqinMap = {}, liushenMap = {}, shiYing = {}, movingPositions = []) => {
  const position = Number(line.position || index + 1)
  const value = normalizeYaoCode(line.value ?? fallbackValue)
  const bianValue = normalizeYaoCode(line.bian_value ?? value)
  const isMoving = line.is_moving !== undefined ? Boolean(line.is_moving) : movingPositions.includes(position)
  const normalized = {
    position,
    value,
    name: line.name || getYaoName(value),
    yin_yang: line.yin_yang || getYinYangLabel(value),
    is_yang: line.is_yang !== undefined ? Boolean(line.is_yang) : isYangYao(value),
    liuqin: line.liuqin || liuqinMap[String(position)] || liuqinMap[position] || '',
    liushen: line.liushen || liushenMap[String(position)] || liushenMap[position] || '',
    di_zhi: line.di_zhi || '',
    bian_value: bianValue,
    bian_name: line.bian_name || getYaoName(bianValue),
    bian_yin_yang: line.bian_yin_yang || getYinYangLabel(bianValue),
    bian_is_yang: line.bian_is_yang !== undefined ? Boolean(line.bian_is_yang) : isYangYao(bianValue),
    change_summary: line.change_summary || '',
    is_moving: isMoving,
    is_shi: line.is_shi !== undefined ? Boolean(line.is_shi) : Number(shiYing.shi || 0) === position,
    is_ying: line.is_ying !== undefined ? Boolean(line.is_ying) : Number(shiYing.ying || 0) === position,
    fushen: normalizeFushen(line.fushen),
  }
  normalized.change_summary = normalized.change_summary || describeLineChange(normalized)
  return normalized
}

const normalizeResult = (rawData = {}, isHistory = false) => {
  const data = rawData || {}
  const gua = data.gua || {}
  const bianGua = data.bian_gua || {}
  const huGua = data.hu_gua || {}
  const yaoResult = parseYaoResult(data.yao_result ?? data.yao_results, data.yao_code || gua.code || '')
  const liuqinMap = (data.liuqin && typeof data.liuqin === 'object') ? data.liuqin : {}
  const liushenMap = (data.liushen && typeof data.liushen === 'object') ? data.liushen : {}
  const shiYing = (data.shi_ying && typeof data.shi_ying === 'object') ? data.shi_ying : {}
  const dongYao = Array.isArray(bianGua.dong_yao) ? bianGua.dong_yao.map((item) => Number(item)) : []
  const lineDetails = Array.isArray(data.line_details) && data.line_details.length
    ? data.line_details.map((line, index) => normalizeLineDetail(line, index, yaoResult[index] ?? 1, liuqinMap, liushenMap, shiYing, dongYao))
    : yaoResult.map((item, index) => normalizeLineDetail({ value: item }, index, item, liuqinMap, liushenMap, shiYing, dongYao))
  const movingLineDetails = Array.isArray(data.moving_line_details) && data.moving_line_details.length
    ? data.moving_line_details.map((line, index) => normalizeLineDetail(line, index, line.value ?? yaoResult[index] ?? 1, liuqinMap, liushenMap, shiYing, dongYao))
    : lineDetails.filter((line) => line.is_moving)

  return {

    id: data.id,
    question: data.question || '',
    method: data.method || '',
    method_label: data.method_label || '',
    time_info: data.time_info || null,
    created_at: data.created_at || data.createdAt || '',
    yao_result: yaoResult,
    yao_names: Array.isArray(data.yao_names) && data.yao_names.length === yaoResult.length
      ? data.yao_names
      : yaoResult.map((item) => getYaoName(item)),
    gua: {
      name: gua.name || data.gua_name || data.main_gua || '',
      code: gua.code || data.gua_code || data.clean_gua_code || data.yao_code || '',
      gua_ci: gua.gua_ci || data.gua_ci || data.gua_info?.main?.gua_ci || data.gua_info?.main?.general_meaning || '',
    },
    bian_gua: {
      name: bianGua.name || data.bian_gua_name || '',
      code: bianGua.code || data.bian_gua_code || '',
      dong_yao: dongYao.filter((item) => Number.isFinite(item) && item > 0),
    },
    hu_gua: {
      name: huGua.name || data.hu_gua_name || '',
      code: huGua.code || data.hu_gua_code || '',
    },
    gong: data.gong || '',
    shi_ying: shiYing,
    liuqin: liuqinMap,
    liushen: liushenMap,
    yong_shen: (data.yong_shen && typeof data.yong_shen === 'object')
      ? data.yong_shen
      : { liuqin: data.yongshen || '' },
    line_details: lineDetails,
    moving_line_details: movingLineDetails,
    interpretation: data.interpretation || '',

    ai_analysis: normalizeAiAnalysis(data.ai_analysis || data.ai_interpretation),
    points_cost: Number(data.points_cost ?? data.consumed_points ?? 0) || 0,
    remaining_points: data.remaining_points ?? null,
    is_first: Boolean(data.is_first),
    is_history: isHistory,
    fushen: data.fushen || null,
  }
}

// 获取定价
const loadPricing = async () => {
  pricingLoading.value = true
  pricingError.value = ''

  try {
    const response = await getLiuyaoPricing()
    if (response.code === 0) {
      pricing.value = response.data || null
      return
    }

    pricing.value = null
    pricingError.value = response.message || '获取占卜价格失败，请稍后重试'
  } catch (error) {
    pricing.value = null
    pricingError.value = '获取占卜价格失败，请稍后重试'
    reportUiError('获取定价失败', error)
  } finally {
    pricingLoading.value = false
  }
}

// 加载历史记录
const loadHistory = async () => {
  historyLoading.value = true
  historyError.value = ''

  try {
    const response = await getLiuyaoHistory({ page: 1, page_size: 50 })
    if (response.code === 0) {
      history.value = (response.data.list || []).map((item) => normalizeResult(item, true))
      historyLoaded.value = true
      return
    }

    history.value = []
    historyLoaded.value = false
    historyError.value = response.message || '获取历史记录失败，请稍后重试。'
  } catch (error) {
    history.value = []
    historyLoaded.value = false
    historyError.value = '获取历史记录失败，请稍后重试。'
    reportUiError('获取历史记录失败', error)
  } finally {
    historyLoading.value = false
  }
}


const buildDivinationPayload = () => {
  const payload = {
    question: form.question.trim(),
    useAi: form.useAi,
    method: form.method,
    question_type: form.questionType,
    gender: form.gender,
    version: form.version, // 添加版本选择：basic（简单版）/ professional（专业版）
  }

  if (form.riGan) {
    payload.ri_gan = form.riGan
  }

  if (form.riZhi) {
    payload.ri_zhi = form.riZhi
  }

  if (form.method === 'number') {
    payload.numbers = form.numbers.filter((item) => Number.isFinite(item))
  }

  if (form.method === 'manual') {
    payload.yao_results = [...form.yaoResults]
  }

  return payload
}

// 提交占卜
const submitDivination = async () => {
  clearSubmitErrors()
  const issues = buildSubmitIssues()

  if (issues.length) {
    submitErrors.value = issues
    handleSubmitIssue(issues[0])
    return
  }

  // 积分不足前置拦截
  if (pricing.value && !pricing.value.is_free && pricing.value.balance < pricing.value.cost) {
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

  isLoading.value = true
  isSubmitting.value = true
  loadingStep.value = 1

  // 分步文案：1.5s 后切到第二步
  const stepTimer = setTimeout(() => { loadingStep.value = 2 }, 1500)

  // 埋点：提交开始
  const payload = buildDivinationPayload()
  trackLiuyaoSubmitStart({
    method: payload.method,
    useAi: payload.use_ai,
    questionType: payload.question_type
  })

  try {
    const response = await liuyaoDivination(payload)

    if (response.code === 0) {
      loadingStep.value = 3
      trackSubmit('liuyao_divination', true, { method: payload.method })
      trackLiuyaoSubmitSuccess({
        method: payload.method,
        useAi: payload.use_ai,
        hasAiAnalysis: !!response.data.ai_analysis
      })
      clearSubmitErrors()
      result.value = normalizeResult(response.data, false)
      await loadHistory()
      await loadPricing()
      trackLiuyaoResultView(!!response.data.ai_analysis)
    } else {
      trackSubmit('liuyao_divination', false, { method: payload.method, error: response.message })
      trackLiuyaoSubmitFail({
        method: payload.method,
        useAi: payload.use_ai,
        error: response.message
      })
      ElMessage.error(response.message || '占卜失败，请重试')
    }
  } catch (error) {
    trackSubmit('liuyao_divination', false, { error: error.message })
    trackError('liuyao_divination_error', error.message)
    trackLiuyaoSubmitFail({
      method: payload.method,
      useAi: payload.use_ai,
      error: error.message
    })
    reportUiError('提交六爻占卜失败', error, '占卜失败，请重试')
  } finally {
    clearTimeout(stepTimer)
    isLoading.value = false
    isSubmitting.value = false
    loadingStep.value = 0
  }
}


// 重置表单
const resetForm = () => {
  clearSubmitErrors()
  Object.assign(form, createDefaultForm())
  result.value = null
  loadPricing()
}

// 开始AI分析
const startAiAnalysis = async () => {
  if (!result.value?.id) {
    ElMessage.error('无效的占卜记录')
    return
  }

  // 检查积分
  if (pricing.value && !pricing.value.is_free && pricing.value.balance < pricing.value.cost) {
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

  aiAnalyzing.value = true

  try {
    const response = await analyzeLiuyaoAi({
      divination_id: result.value.id
    })

    if (response.code === 0) {
      // 更新result中的AI分析结果
      if (response.data?.ai_analysis) {
        result.value.ai_analysis = response.data.ai_analysis
        ElMessage.success('AI解卦成功')
      }
      
      // 更新积分
      if (response.data?.remaining_points !== undefined) {
        if (pricing.value) {
          pricing.value.balance = response.data.remaining_points
        }
      }
      
      // 重新加载定价信息
      await loadPricing()
    } else {
      ElMessage.error(response.message || 'AI解卦失败，请重试')
    }
  } catch (error) {
    console.error('AI解卦错误:', error)
    ElMessage.error('AI解卦服务暂时不可用，请稍后重试')
  } finally {
    aiAnalyzing.value = false
  }
}


// AI分析内容结构化解析：将纯文本按段落/标题/要点拆分
const aiAnalysisParagraphs = computed(() => {
  const content = result.value?.ai_analysis?.content
  if (!content) return []

  const lines = content.split('\n').map(l => l.trim()).filter(Boolean)
  return lines.map(line => {
    // 以【】或「」或数字+. 开头的视为标题段
    if (/^[【「\d]/.test(line) && line.length < 30) {
      return { type: 'heading', text: line.replace(/^[\d]+[.、]\s*/, '') }
    }
    // 以-、•、·、※ 开头的视为要点
    if (/^[-•·※★✦]/.test(line)) {
      return { type: 'bullet', text: line.replace(/^[-•·※★✦]\s*/, '') }
    }
    return { type: 'text', text: line }
  })
})

// 变卦爻线图：根据本卦yao_result和动爻位置推算变卦爻
const bianGuaYaoResult = computed(() => {
  if (!result.value?.bian_gua?.name) return []
  const yaoResult = result.value.yao_result
  if (!yaoResult?.length) return []
  const dongYao = result.value.bian_gua.dong_yao || []
  // 从下到上（初爻=1），反转后展示（上爻在上）
  return [...yaoResult].reverse().map((yao, reversedIdx) => {
    const position = 6 - reversedIdx // 上爻=6, 初爻=1
    if (dongYao.includes(position)) {
      // 动爻：阴阳互换
      return isYangYao(yao) ? 2 : 1 // 2=少阴(阴), 1=少阳(阳)
    }
    return yao
  })
})

const shouldShowLiuyaoRechargeAction = computed(() => {
  const remaining = Number(result.value?.remaining_points)
  const cost = Number(pricing.value?.cost ?? 0)
  return Number.isFinite(remaining) && Number.isFinite(cost) && cost > 0 && remaining < cost
})

const liuyaoResultHighlights = computed(() => {
  const highlights = [
    {
      key: 'saved-status',
      label: savedStatusText.value,
      tone: result.value?.is_history ? '' : 'success',
    },
    {
      key: 'cost',
      label: result.value?.points_cost > 0 ? `本次消耗 ${result.value.points_cost} 积分` : '本次免费',
      tone: result.value?.points_cost > 0 ? 'warning' : '',
    },
  ]

  if (shouldShowRemainingPoints.value) {
    highlights.push({
      key: 'remaining',
      label: `剩余 ${result.value.remaining_points} 积分`,
      tone: shouldShowLiuyaoRechargeAction.value ? 'danger' : '',
    })
  }

  if (result.value?.ai_analysis) {
    highlights.push({
      key: 'ai',
      label: '含 AI 深度分析',
    })
  }

  return highlights
})

const liuyaoResultActions = computed(() => {
  return [
    historyLoaded.value || historyLoading.value || historyError.value
      ? {
          key: 'history',
          label: historyTriggerText.value,
          type: 'primary',
          onClick: () => openHistoryDialog(),
        }
      : null,
    {
      key: 'profile',
      label: '查看我的积分',
      to: '/profile',
    },
    shouldShowLiuyaoRechargeAction.value
      ? {
          key: 'recharge',
          label: '去充值 / 补积分',
          plain: true,
          to: '/recharge',
        }
      : null,
    {
      key: 'retry',
      label: '再次占卜',
      plain: true,
      onClick: resetForm,
    },
  ].filter(Boolean)
})

const liuyaoRelatedRecommendations = computed(() => {
  return [
    {
      key: 'tarot',
      title: '换成塔罗再看一层',
      description: '如果你想把当前问事换成更偏情绪与关系的视角，可以顺手切到塔罗继续问。',
      to: '/tarot',
      badge: '相关推荐',
    },
    {
      key: 'daily',
      title: '看看今日运势',
      description: '把六爻判断和当天整体节奏放一起看，方便决定是马上行动还是先等等。',
      to: '/daily',
      badge: '继续承接',
    },
  ]
})

const liuyaoShareSummary = computed(() => {
  if (!result.value) return '我在太初命理测算了六爻，结果很准！'
  const guaName = result.value.gua?.name || ''
  const bianGuaName = result.value.bian_gua?.name || ''
  if (bianGuaName) {
    return `我卜得【${guaName}】变【${bianGuaName}】，快来看看你的运势吧！`
  }
  return `我卜得【${guaName}】，快来看看你的运势吧！`
})

const liuyaoShareTags = computed(() => {
  if (!result.value) return []
  const tags = []
  if (result.value.gua?.name) tags.push(`本卦${result.value.gua.name}`)
  if (result.value.bian_gua?.name) tags.push(`变卦${result.value.bian_gua.name}`)
  return tags
})

// 加载历史记录详情

const loadHistoryDetail = (item) => {
  result.value = normalizeResult(item, true)
  showHistory.value = false
}

// 删除记录
const deleteRecord = async (id) => {
  try {
    await ElMessageBox.confirm('确定要删除这条记录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })

    const response = await deleteLiuyaoRecord({ id })
    if (response.code === 0) {
      ElMessage.success('删除成功')
      await loadHistory()
      if (result.value?.id === id) {
        result.value = null
      }
      await loadPricing()
    } else {
      ElMessage.error(response.message)
    }
  } catch (error) {
    if (error !== 'cancel') {
      reportUiError('删除六爻历史记录失败', error, '删除失败')
    }
  }
}

// 格式化日期
const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) {
    return dateStr || '--'
  }
  return date.toLocaleDateString('zh-CN')
}

// 从历史记录跳转加载详情
async function loadRecordById(recordId) {
  isLoading.value = true
  try {
    const res = await getLiuyaoDetail({ id: recordId })
    if (res.code === 0 && res.data) {
      result.value = normalizeResult(res.data, true)
    } else {
      ElMessage.warning('历史记录加载失败，请重新占卜')
    }
  } catch (e) {
    ElMessage.warning('历史记录加载失败，请重新占卜')
  } finally {
    isLoading.value = false
  }
}

// 初始化
onMounted(() => {
  trackPageView('liuyao')
  beijingTimer = window.setInterval(() => {
    currentBeijingTimestamp.value = Date.now()
  }, 1000)
  loadPricing()
  loadHistory()

  // 从历史记录跳转时，自动加载对应记录
  const recordId = parseInt(route.query.record_id, 10)
  if (recordId > 0) {
    loadRecordById(recordId)
  }
})

onUnmounted(() => {
  if (beijingTimer) {
    clearInterval(beijingTimer)
    beijingTimer = null
  }
})

return {
  // 常量
  methodOptions, questionTypeOptions, quickQuestions, yaoLineLabels, yaoResultLineLabels,
  yaoValueOptions, tianGanOptions, diZhiOptions, yaoNameMap,

  // 状态
  form, isLoading, isSubmitting, loadingStep, result, pricing, pricingLoading, pricingError,
  history, historyLoading, historyLoaded, historyError, submitErrors,
  showHistory, showAdvancedSettings, historyListRef, currentBeijingTimestamp,
  aiAnalyzing,

  // 计算属性
  currentBeijingTime, currentMethodDescription, currentMethodAudience,
  submitSummaryText, submitButtonText, shouldShowRemainingPoints,
  savedStatusText, historyTriggerText, shouldShowLiuyaoRechargeAction,
  liuyaoResultHighlights, liuyaoResultActions, liuyaoRelatedRecommendations,
  liuyaoShareSummary, liuyaoShareTags,
  aiAnalysisParagraphs, bianGuaYaoResult,

  // 方法
  createDefaultForm, clearSubmitErrors, focusLiuyaoField, handleSubmitIssue,
  buildSubmitIssues, openHistoryDialog,
  restoreHistoryTriggerFocus, focusHistoryDialogPrimaryAction,
  formatDateTime, reportUiError,
  isMovingYao, isYangYao, getYaoName, getYaoMark, getGuaSymbol,
  parseYaoResult, normalizeYaoCode, safeJsonParse, normalizeAiAnalysis,
  normalizeFushen, getYinYangLabel, describeLineChange,
  formatMovingLineMeta, normalizeLineDetail, normalizeResult,
  loadPricing, loadHistory, buildDivinationPayload,
  submitDivination, resetForm, startAiAnalysis,
  loadHistoryDetail, deleteRecord, formatDate,
}
} // end useLiuyao
