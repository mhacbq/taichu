<template>
  <div class="liuyao-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <BackButton fallback="/" />
        <div class="page-header-content">
          <h1 class="page-title">
            <el-icon class="title-icon"><MagicStick /></el-icon>
            六爻占卜
          </h1>
          <p class="page-subtitle">传统周易六爻，为您解答心中疑惑</p>
        </div>
      </div>

      <!-- 占卜结果 -->
      <div v-if="result" class="result-section">
        <div class="result-card">
          <div class="result-header">
            <h2>占卜结果</h2>
            <span v-if="result.is_first" class="first-free-badge">首次免费</span>
          </div>

          <!-- 问题 -->
          <div class="question-box">
            <span class="label">占问：</span>
            <span class="question-text">{{ result.question }}</span>
          </div>

          <!-- 卦象展示 -->
          <div class="gua-display">
            <div class="gua-decoration">
              <el-icon><MagicStick /></el-icon>
            </div>
            <div class="gua-info">
              <h3 class="gua-name">{{ result.gua.name }}</h3>
              <p class="gua-code">卦象代码：{{ result.gua.code }}</p>
            </div>

            <!-- 六爻图形 -->
            <div class="yao-container">
              <div
                v-for="(yao, index) in result.yao_result"
                :key="index"
                class="yao-line"
                :class="{ moving: isMovingYao(yao), yang: isYangYao(yao), yin: !isYangYao(yao) }"
              >
                <!-- 伏神显示 -->
                <div v-if="result.fushen && result.fushen[index]" class="fushen-box">
                   <span class="fushen-label">伏</span>
                   <span class="fushen-name">{{ result.fushen[index].name }}</span>
                   <span class="fushen-ganzhi">{{ result.fushen[index].ganzhi }}</span>
                </div>
                <span class="yao-mark">{{ getYaoMark(yao) }}</span>
                <span class="yao-bar"></span>
                <span class="yao-name">{{ result.yao_names[index] }}</span>
              </div>
            </div>
          </div>


          <!-- 卦辞 -->
          <div class="gua-ci-section">
            <h4>卦辞</h4>
            <p class="gua-ci">{{ result.gua.gua_ci }}</p>
          </div>

          <!-- 解读 -->
          <div class="interpretation-section">
            <h4>卦象解读</h4>
            <pre class="interpretation-text">{{ result.interpretation }}</pre>
          </div>

          <!-- AI分析 -->
          <div v-if="result.ai_analysis" class="ai-section">
            <h4>
              <el-icon><MagicStick /></el-icon>
              AI深度分析
            </h4>
            <div class="ai-content">{{ result.ai_analysis.content }}</div>
          </div>

          <!-- 消耗信息 -->
          <div class="points-info">
            <span v-if="result.points_cost > 0">消耗 {{ result.points_cost }} 积分</span>
            <span v-else>本次免费</span>
            <span v-if="shouldShowRemainingPoints">剩余 {{ result.remaining_points }} 积分</span>
          </div>



          <!-- 操作按钮 -->
          <div class="action-buttons">
            <el-button type="info" round size="large" @click="resetForm">
              <el-icon><RefreshRight /></el-icon> 再次占卜
            </el-button>
            <div class="saved-status" role="status" aria-live="polite">
              <el-icon><CircleCheckFilled /></el-icon>
              <span>已自动保存到历史记录</span>
            </div>
            <el-button v-if="history.length > 0" round size="large" @click="showHistory = true">
              <el-icon><Collection /></el-icon> 查看历史
            </el-button>
          </div>
        </div>
      </div>

      <!-- 占卜表单 -->
      <div v-else class="form-section">
        <div class="form-card">
          <h2>心诚则灵</h2>
          <p class="form-tip">请静心思考您要询问的问题，问题越具体，占卜结果越准确</p>

          <div class="form-group">
            <label>您的问题 <span class="required">*</span></label>
            <el-input
              v-model="form.question"
              type="textarea"
              :rows="4"
              placeholder="例如：
我最近的考试能通过吗？
这份工作适合我吗？
我和TA的感情发展如何？
这个项目能成功吗？"
              maxlength="100"
              show-word-limit
            />
          </div>

          <div class="form-group">
            <label>起卦方式</label>
            <el-radio-group v-model="form.method" class="method-group">
              <el-radio-button v-for="option in methodOptions" :key="option.value" :label="option.value">
                {{ option.label }}
              </el-radio-button>
            </el-radio-group>
            <p class="form-helper">{{ currentMethodDescription }}</p>
          </div>

          <div v-if="form.method === 'time'" class="helper-card">
            <p class="helper-card__title">时间起卦</p>
            <p class="helper-card__desc">将按当前北京时间 {{ currentBeijingTime }} 自动起卦，无需额外输入数字或摇卦结果。</p>
          </div>

          <div v-else-if="form.method === 'number'" class="helper-card">
            <p class="helper-card__title">数字起卦</p>
            <p class="helper-card__desc">请输入 1-999 的数字。单数字可只填第一个，双数字会按上下卦分别计算。</p>
            <div class="input-grid input-grid--double">
              <div class="input-grid__item">
                <label>第一个数字</label>
                <el-input-number v-model="form.numbers[0]" :min="1" :max="999" :step="1" :precision="0" controls-position="right" class="full-width" />
              </div>
              <div class="input-grid__item">
                <label>第二个数字（可选）</label>
                <el-input-number v-model="form.numbers[1]" :min="1" :max="999" :step="1" :precision="0" controls-position="right" class="full-width" />
              </div>
            </div>
          </div>

          <div v-else class="helper-card">
            <p class="helper-card__title">手动摇卦</p>
            <p class="helper-card__desc">请按从初爻到上爻的顺序，依次录入 6 次摇卦结果。</p>
            <div class="manual-grid">
              <div v-for="(label, index) in yaoLineLabels" :key="label" class="manual-grid__item">
                <label>{{ label }}</label>
                <el-select v-model="form.yaoResults[index]" placeholder="请选择爻象" class="full-width">
                  <el-option v-for="option in yaoValueOptions" :key="option.value" :label="option.label" :value="option.value" />
                </el-select>
              </div>
            </div>
          </div>

          <div class="advanced-card">
            <div class="advanced-card__header">
              <h3>专业参数</h3>
              <p>补齐问事类型、性别与日辰信息，让六爻结果更贴近真实问卦流程。</p>
            </div>
            <div class="advanced-grid">
              <div class="form-group">
                <label>问事类型</label>
                <el-select v-model="form.questionType" class="full-width">
                  <el-option v-for="option in questionTypeOptions" :key="option" :label="option" :value="option" />
                </el-select>
              </div>
              <div class="form-group">
                <label>求测者性别</label>
                <el-radio-group v-model="form.gender">
                  <el-radio-button label="男" />
                  <el-radio-button label="女" />
                </el-radio-group>
              </div>
              <div class="form-group">
                <label>日辰天干（可选）</label>
                <el-select v-model="form.riGan" clearable placeholder="默认自动推算" class="full-width">
                  <el-option v-for="option in tianGanOptions" :key="option" :label="option" :value="option" />
                </el-select>
              </div>
              <div class="form-group">
                <label>日辰地支（可选）</label>
                <el-select v-model="form.riZhi" clearable placeholder="默认自动推算" class="full-width">
                  <el-option v-for="option in diZhiOptions" :key="option" :label="option" :value="option" />
                </el-select>
              </div>
            </div>
          </div>

          <div class="options-section">
            <el-checkbox v-model="form.useAi" label="使用AI深度分析（更准确、更详细）" />
          </div>

          <!-- 定价信息 -->
          <div class="pricing-info" v-if="pricingLoading || pricing || pricingError">
            <div v-if="pricingLoading" class="pricing-loading">
              <span>正在同步当前占卜价格...</span>
            </div>
            <template v-else-if="pricing">
              <div v-if="pricing.is_first_free" class="pricing-free">
                <span><el-icon><Present /></el-icon> 首次占卜免费</span>
              </div>
              <div v-else-if="pricing.is_vip_free" class="pricing-vip">
                <span><el-icon><Trophy /></el-icon> VIP免费</span>
              </div>
              <div v-else class="pricing-normal">
                <span>本次消耗 {{ pricing.cost }} 积分</span>
              </div>
              <p v-if="pricing.reason" class="pricing-reason">{{ pricing.reason }}</p>
            </template>
            <div v-else class="pricing-error">
              <p class="pricing-reason pricing-reason--error">{{ pricingError }}</p>
              <el-button type="primary" link @click="loadPricing">重新获取价格</el-button>
            </div>
          </div>

          <el-button
            type="primary"
            size="large"
            class="btn-submit"
            @click="submitDivination"
            :disabled="isLoading || !canSubmit"
            :loading="isLoading"
          >
            <template #icon v-if="!isLoading">
              <el-icon class="btn-icon"><MagicStick /></el-icon>
            </template>
            {{ isLoading ? '正在占卜...' : submitButtonText }}
          </el-button>


          <div class="history-link" v-if="history.length > 0">
            <a @click="showHistory = true">查看历史记录 ({{ history.length }}条)</a>
          </div>
        </div>
      </div>

      <!-- 历史记录弹窗 -->
      <div v-if="showHistory" class="modal-overlay" @click.self="showHistory = false">
        <div class="modal-content">
          <div class="modal-header">
            <h3>历史记录</h3>
            <button class="close-btn" @click="showHistory = false">
              <el-icon><Close /></el-icon>
            </button>
          </div>
          <div class="history-list">
            <div v-for="item in history" :key="item.id" class="history-item" @click="loadHistoryDetail(item)">
              <div class="history-main">
                <p class="history-question">{{ item.question }}</p>
                <p class="history-gua">{{ item.gua?.name || '卦象待定' }} · {{ formatDate(item.created_at) }}</p>
              </div>
              <button class="delete-btn" type="button" @click.stop="deleteRecord(item.id)">
                <el-icon><Delete /></el-icon>
                <span class="delete-label">删除</span>
              </button>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getLiuyaoPricing, liuyaoDivination, getLiuyaoHistory, deleteLiuyaoRecord } from '../api'
import { RefreshRight, Delete, MagicStick, Present, Trophy, Close, CircleCheckFilled, Collection } from '@element-plus/icons-vue'
import BackButton from '../components/BackButton.vue'

const methodOptions = [
  { label: '时间起卦', value: 'time', description: '按当前北京时间起卦，适合快速问事。' },
  { label: '数字起卦', value: 'number', description: '通过数字拆分上下卦，适合已有灵感数字时使用。' },
  { label: '手动摇卦', value: 'manual', description: '录入 6 次摇卦结果，满足标准六爻问卦流程。' },
]

const questionTypeOptions = ['求财', '感情', '事业', '健康', '学业', '出行', '其他']
const yaoLineLabels = ['初爻（下）', '二爻', '三爻', '四爻', '五爻', '上爻（上）']
const yaoValueOptions = [
  { label: '老阴（6）', value: 6 },
  { label: '少阳（7）', value: 7 },
  { label: '少阴（8）', value: 8 },
  { label: '老阳（9）', value: 9 },
]
const tianGanOptions = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸']
const diZhiOptions = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']
const yaoNameMap = ['老阴', '少阳', '少阴', '老阳']

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
})

// 表单数据
const form = reactive(createDefaultForm())

// 状态
const isLoading = ref(false)
const result = ref(null)
const pricing = ref(null)
const pricingLoading = ref(true)
const pricingError = ref('')
const history = ref([])
const historyLoading = ref(false)
const historyLoaded = ref(false)
const historyError = ref('')
const submitError = ref('')
const showHistory = ref(false)
const currentBeijingTimestamp = ref(Date.now())
let beijingTimer = null


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

const canSubmit = computed(() => {
  if (!form.question.trim()) {
    return false
  }

  if (form.method === 'number') {
    return Number.isFinite(form.numbers[0])
  }

  if (form.method === 'manual') {
    return form.yaoResults.every((item) => Number.isFinite(item))
  }

  return true
})

const shouldShowRemainingPoints = computed(() => {
  if (!result.value || result.value.is_history) {
    return false
  }

  return result.value.remaining_points !== null && result.value.remaining_points !== undefined
})

const reportUiError = (action, error, userMessage = '') => {
  console.error(`[Liuyao] ${action}`, error)
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

const normalizeResult = (data = {}, isHistory = false) => {
  const gua = data.gua || {}
  const yaoResult = parseYaoResult(data.yao_result ?? data.yao_results, data.yao_code || gua.code || '')

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
  try {
    const response = await getLiuyaoPricing()
    if (response.code === 200) {
      pricing.value = response.data || null
    }
  } catch (error) {
    reportUiError('获取定价失败', error, '获取定价信息失败')
  }
}

// 加载历史记录
const loadHistory = async () => {
  try {
    const response = await getLiuyaoHistory({ page: 1, page_size: 50 })
    if (response.code === 200) {
      history.value = (response.data.list || []).map((item) => normalizeResult(item, true))
    }
  } catch (error) {
    reportUiError('获取历史记录失败', error)
  }
}

const buildDivinationPayload = () => {
  const payload = {
    question: form.question.trim(),
    useAi: form.useAi,
    method: form.method,
    question_type: form.questionType,
    gender: form.gender,
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
  if (!form.question.trim()) {
    ElMessage.warning('请输入占卜问题')
    return
  }

  if (form.question.trim().length < 2) {
    ElMessage.warning('问题太短了，请详细描述您的问题')
    return
  }

  if (form.method === 'number' && !Number.isFinite(form.numbers[0])) {
    ElMessage.warning('数字起卦至少需要填写第一个数字')
    return
  }

  if (form.method === 'manual' && form.yaoResults.some((item) => !Number.isFinite(item))) {
    ElMessage.warning('请完整填写 6 次摇卦结果')
    return
  }

  isLoading.value = true
  try {
    const response = await liuyaoDivination(buildDivinationPayload())

    if (response.code === 200) {
      result.value = normalizeResult(response.data, false)
      await loadHistory()
      await loadPricing()
    } else {
      ElMessage.error(response.message || '占卜失败，请重试')
    }
  } catch (error) {
    reportUiError('提交六爻占卜失败', error, '占卜失败，请重试')
  } finally {
    isLoading.value = false
  }
}

// 重置表单
const resetForm = () => {
  Object.assign(form, createDefaultForm())
  result.value = null
  loadPricing()
}



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
    if (response.code === 200) {
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

// 初始化
onMounted(() => {
  beijingTimer = window.setInterval(() => {
    currentBeijingTimestamp.value = Date.now()
  }, 1000)
  loadPricing()
  loadHistory()
})

onUnmounted(() => {
  if (beijingTimer) {
    clearInterval(beijingTimer)
    beijingTimer = null
  }
})
</script>

<style scoped>
.liuyao-page {
  padding: 40px 20px;
  min-height: 100vh;
}

.container {
  max-width: 700px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 40px;
}

.page-header-content {
  flex: 1;
}

.page-title {
  font-size: 36px;
  color: var(--text-primary);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12px;
}

.title-icon {
  font-size: 42px;
}

.page-subtitle {
  color: var(--text-secondary);
  font-size: 16px;
  margin: 0;
}

/* 表单样式 */
.form-card {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 40px;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-lg);
}

.form-card h2 {
  color: var(--text-primary);
  text-align: center;
  margin-bottom: 8px;
}

.form-tip {
  color: var(--text-secondary);
  text-align: center;
  font-size: 14px;
  margin-bottom: 30px;
}

.form-group {
  margin-bottom: 24px;
}

.form-group label {
  display: block;
  color: var(--text-secondary);
  margin-bottom: 10px;
  font-size: 14px;
}

.form-group label .required {
  color: var(--primary-color);
}

.form-group textarea {
  width: 100%;
  padding: 16px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  color: var(--text-primary);
  font-size: 15px;
  line-height: 1.6;
  resize: vertical;
  transition: all 0.3s;
  font-family: inherit;
}

.form-group textarea:focus {
  outline: none;
  border-color: var(--primary-color);
}

.form-group textarea::placeholder {
  color: var(--text-muted);
}

.char-count {
  display: block;
  text-align: right;
  color: var(--text-secondary);
  font-size: 12px;
  margin-top: 6px;
}

.full-width {
  width: 100%;
}

.method-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.form-helper {
  margin: 10px 0 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.helper-card,
.advanced-card {
  padding: 18px 20px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-light);
  border-radius: 16px;
  margin-bottom: 20px;
}

.helper-card__title {
  margin: 0 0 8px;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.helper-card__desc,
.advanced-card__header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.advanced-card__header {
  margin-bottom: 18px;
}

.advanced-card__header h3 {
  margin: 0 0 6px;
  color: var(--text-primary);
  font-size: 18px;
}

.input-grid,
.advanced-grid,
.manual-grid {
  display: grid;
  gap: 16px;
}

.input-grid--double,
.advanced-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.input-grid__item label,
.manual-grid__item label {
  display: block;
  margin-bottom: 10px;
  color: var(--text-secondary);
  font-size: 14px;
}

.manual-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 16px;
}

.options-section {
  margin: 20px 0;
}

.pricing-info {
  text-align: center;
  padding: 16px;
  background: var(--bg-secondary);
  border-radius: 16px;
  margin: 20px 0;
  border: 1px solid var(--border-light);
}

.pricing-reason {
  margin: 10px 0 0;
  color: var(--text-secondary);
  font-size: 13px;
}

.pricing-free,
.pricing-vip {
  color: var(--success-color);
  font-size: 18px;
  font-weight: 600;
}

.pricing-normal {
  color: var(--text-primary);
  font-size: 16px;
}

.btn-submit {
  width: 100%;
  padding: 18px;
  background: var(--primary-gradient);
  color: var(--text-primary);
  border: none;
  border-radius: 16px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-submit:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px var(--primary-light-40);
}

.btn-icon {
  font-size: 24px;
}

.loading {
  width: 24px;
  height: 24px;
  border: 2px solid var(--border-color);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.history-link {
  text-align: center;
  margin-top: 20px;
}

.history-link a {
  color: var(--text-secondary);
  cursor: pointer;
  text-decoration: underline;
  font-size: 14px;
}

.history-link a:hover {
  color: var(--primary-color);
}

/* 结果卡片 */
.result-section {
  margin-bottom: 40px;
}

.result-card {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 32px;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-lg);
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.result-header h2 {
  color: var(--text-primary);
  margin: 0;
}

.first-free-badge {
  background: var(--success-gradient);
  color: var(--text-primary);
  padding: 12px 24px;
  border-radius: 20px;
  font-size: 14px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
}

.question-box {
  padding: 16px 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.question-box .label {
  color: var(--primary-color);
  font-weight: 600;
}

.question-box .question-text {
  color: var(--text-primary);
  font-size: 16px;
}

/* 卦象展示 */
.gua-display {
  display: flex;
  justify-content: space-around;
  align-items: center;
  padding: 50px 40px;
  background: radial-gradient(circle at center, var(--bg-tertiary), var(--bg-primary));
  border-radius: 24px;
  margin-bottom: 40px;
  border: 1px solid var(--primary-light-20);
  position: relative;
  overflow: hidden;
  box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.6), 0 20px 40px rgba(0, 0, 0, 0.4);
}

.gua-display::before {
  content: '';
  position: absolute;
  inset: 15px;
  border: 1px solid var(--primary-light-10);
  border-radius: 20px;
  pointer-events: none;
}

.gua-display::after {
  content: '';
  position: absolute;
  inset: 20px;
  border: 2px solid transparent;
  border-image: linear-gradient(135deg, var(--primary-color), transparent, var(--primary-color)) 1;
  opacity: 0.2;
  pointer-events: none;
}

/* 装饰角 - 动态太极 */
.gua-decoration {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 320px;
  color: var(--primary-light-05);
  animation: yinYangRotate 30s linear infinite;
  pointer-events: none;
  z-index: 0;
  filter: blur(2px);
}

@keyframes yinYangRotate {
  from { transform: translate(-50%, -50%) rotate(0deg); }
  to { transform: translate(-50%, -50%) rotate(360deg); }
}

.gua-info {
  text-align: center;
  z-index: 2;
  background: var(--white-03);
  padding: 30px;
  border-radius: 24px;
  backdrop-filter: blur(10px);
  border: 1px solid var(--white-08);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.gua-name {
  color: var(--primary-color);
  font-size: 48px;
  margin-bottom: 15px;
  font-weight: 900;
  text-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
  letter-spacing: 8px;
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.gua-code {
  color: var(--white-60);
  font-size: 14px;
  letter-spacing: 4px;
  background: var(--white-05);
  padding: 6px 16px;
  border-radius: 20px;
  display: inline-block;
  font-family: monospace;
}

/* 六爻图形 */
.yao-container {
  display: flex;
  flex-direction: column-reverse; /* 从下往上排 */
  gap: 20px;
  z-index: 2;
  background: rgba(0, 0, 0, 0.4);
  padding: 30px 40px;
  border-radius: 20px;
  border: 1px solid var(--primary-light-15);
  box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.5);
}

.yao-line {
  position: relative;
  display: flex;
  align-items: center;
  gap: 25px;
  padding: 8px 20px;
  border-radius: 12px;
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  border: 1px solid transparent;
}


.yao-line:hover {
  background: var(--primary-light-10);
  transform: translateX(-10px) scale(1.05);
  border-color: var(--primary-light-30);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.yao-line.moving {
  position: relative;
  background: var(--primary-light-05);
  animation: moving-glow 2s ease-in-out infinite;
}

@keyframes moving-glow {
  0%, 100% { box-shadow: 0 0 5px var(--primary-light-20); }
  50% { box-shadow: 0 0 15px var(--primary-light-40); }
}

.yao-line.moving::before {
  content: '动';
  position: absolute;
  left: -35px;
  color: var(--primary-color);
  font-size: 12px;
  font-weight: 800;
  background: var(--primary-light-10);
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  border: 1px solid var(--primary-color);
  animation: pulse-dot 1.5s ease-in-out infinite;
}

@keyframes pulse-dot {
  0% { transform: scale(0.9); opacity: 0.7; }
  50% { transform: scale(1.1); opacity: 1; }
  100% { transform: scale(0.9); opacity: 0.7; }
}

.yao-mark {
  width: 40px;
  text-align: center;
  font-size: 28px;
  color: var(--primary-light);
  font-weight: 900;
  filter: drop-shadow(0 0 8px var(--primary-color));
}

.yao-bar {
  width: 140px;
  height: 14px;
  border-radius: 7px;
  position: relative;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
}

.yao-line.yang .yao-bar {
  background: linear-gradient(90deg, #8B6914 0%, #D4AF37 20%, #FFF3D1 50%, #D4AF37 80%, #8B6914 100%);
  border: 1px solid var(--primary-light-40);
}

.yao-line.yin .yao-bar::before,
.yao-line.yin .yao-bar::after {
  content: '';
  position: absolute;
  width: 42%;
  height: 100%;
  background: linear-gradient(90deg, #2D2209 0%, #8B6914 20%, #B8860B 50%, #8B6914 80%, #2D2209 100%);
  border-radius: 7px;
  border: 1px solid var(--primary-light-20);
}

.yao-line.yin .yao-bar::after {
  right: 0;
}

.yao-name {
  color: var(--text-secondary);
  font-size: 14px;
  min-width: 60px;
  font-weight: 500;
}

/* 伏神样式 */
.fushen-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: absolute;
  left: -80px;
  background: rgba(184, 134, 11, 0.1);
  padding: 4px 8px;
  border-radius: 8px;
  border: 1px dashed var(--primary-light-40);
  min-width: 60px;
}

.fushen-label {
  font-size: 10px;
  color: var(--primary-light);
  font-weight: bold;
}

.fushen-name {
  font-size: 12px;
  color: var(--text-primary);
}

.fushen-ganzhi {
  font-size: 11px;
  color: var(--text-tertiary);
}



/* 卦辞 */
.gua-ci-section {
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.gua-ci-section h4 {
  color: var(--primary-color);
  margin-bottom: 10px;
  font-size: 16px;
}

.gua-ci {
  color: var(--text-primary);
  line-height: 1.8;
  font-size: 15px;
  margin: 0;
}

/* 解读 */
.interpretation-section {
  margin-bottom: 24px;
}

.interpretation-section h4 {
  color: var(--text-primary);
  margin-bottom: 12px;
  font-size: 16px;
}

.interpretation-text {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 14px;
  white-space: pre-wrap;
  margin: 0;
  font-family: inherit;
}

/* AI分析 */
.ai-section {
  margin-bottom: 24px;
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  border-left: 4px solid var(--success-color);
}

.ai-section h4 {
  color: var(--success-color);
  margin-bottom: 12px;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.ai-content {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 14px;
  white-space: pre-wrap;
}

/* 积分信息 */
.points-info {
  display: flex;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.history-points-note {
  color: var(--text-muted);
}

/* 操作按钮 */

.action-buttons {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.saved-status {
  flex: 1;
  min-height: 48px;
  padding: 12px 18px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(103, 194, 58, 0.12);
  border: 1px solid rgba(103, 194, 58, 0.24);
  color: var(--success-color);
  font-size: 14px;
  font-weight: 600;
}

.btn-primary,

.btn-secondary {
  flex: 1;
  padding: 14px 24px;
  border-radius: 25px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s;
  border: none;
  min-height: 44px;
}

.btn-primary {
  background: var(--primary-gradient);
  color: var(--text-primary);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px var(--primary-light-40);
}

.btn-secondary {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-light);
}

.btn-secondary:hover {
  background: var(--bg-hover);
}

/* 弹窗 */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: var(--bg-card);
  border-radius: 16px;
  width: 100%;
  max-width: 500px;
  max-height: 80vh;
  overflow: hidden;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-xl);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid var(--border-light);
}

.modal-header h3 {
  color: var(--text-primary);
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 20px;
  cursor: pointer;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.close-btn:hover {
  color: var(--text-primary);
  background: var(--bg-hover);
  border-radius: 50%;
}

.history-list {
  max-height: 60vh;
  overflow-y: auto;
  padding: 10px;
}

.history-state {
  padding: 20px 16px;
  text-align: center;
  color: var(--text-secondary);
}

.history-state p {
  margin: 0 0 8px;
  color: var(--text-primary);
  font-weight: 600;
}

.history-state--error {
  border-radius: 14px;
  border: 1px solid rgba(245, 108, 108, 0.18);
  background: rgba(245, 108, 108, 0.06);
}

.history-item {

  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
}

.history-item:hover {
  background: var(--bg-secondary);
}

.history-main {
  flex: 1;
  min-width: 0;
}

.history-question {
  color: var(--text-primary);
  margin: 0 0 6px 0;
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.history-gua {
  color: var(--text-secondary);
  margin: 0;
  font-size: 12px;
}

.delete-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 36px;
  padding: 8px 12px;
  background: var(--error-bg);
  border: 1px solid rgba(239, 68, 68, 0.18);
  border-radius: 999px;
  color: var(--error-color);
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  opacity: 1;
  transition: all 0.3s;
}

.delete-btn:hover {
  background: rgba(239, 68, 68, 0.14);
  border-color: rgba(239, 68, 68, 0.3);
}

.delete-label {
  line-height: 1;
}

@media (prefers-reduced-motion: reduce) {
  .loading,
  .gua-decoration,
  .yao-line,
  .yao-line.moving,
  .yao-line.moving::before,
  .btn-submit,
  .delete-btn {
    animation: none !important;
    transition: none !important;
  }

  .yao-line:hover,
  .btn-submit:not(:disabled):hover {
    transform: none !important;
  }
}

/* 响应式 */

@media (max-width: 768px) {
  .page-header {
    align-items: stretch;
  }

  .page-title {
    font-size: 28px;
  }

  .form-card,
  .result-card {
    padding: 24px;
  }

  .input-grid--double,
  .advanced-grid,
  .manual-grid {
    grid-template-columns: 1fr;
  }

  .gua-display {
    flex-direction: column;
    gap: 20px;
    overflow: visible;
  }

  .yao-container {
    width: 100%;
    padding: 18px 16px;
    gap: 14px;
  }

  .yao-line {
    gap: 12px;
    padding: 12px 14px;
    flex-wrap: wrap;
    row-gap: 10px;
  }

  .yao-line:hover {
    transform: none;
  }

  .yao-line.moving::before {
    left: auto;
    right: 12px;
    top: 10px;
  }

  .fushen-box {
    position: static;
    flex-direction: row;
    flex-basis: 100%;
    justify-content: flex-start;
    gap: 6px;
    min-width: 0;
    padding: 6px 10px;
    margin-bottom: 2px;
  }

  .fushen-label,
  .fushen-name,
  .fushen-ganzhi {
    font-size: 12px;
  }

  .yao-mark {
    width: 24px;
    font-size: 22px;
  }

  .yao-bar {
    flex: 1;
    min-width: 96px;
    width: auto;
  }

  .yao-name {
    min-width: auto;
    margin-left: auto;
    text-align: right;
    font-size: 13px;
  }

  .action-buttons {
    flex-direction: column;
  }
}

</style>
