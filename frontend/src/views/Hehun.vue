<template>
  <div class="hehun-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <BackButton fallback="/" />
        <div class="page-header-content">
          <h1 class="page-title">
            <el-icon class="title-icon" :size="36"><Link /></el-icon>
            八字合婚
          </h1>
          <p class="page-subtitle">通过双方八字，分析婚姻匹配度与缘分</p>
        </div>
      </div>

      <!-- 免费预览结果 -->
      <div v-if="freeResult" class="result-section">
        <div class="result-card card-hover">
          <div class="result-header">
            <h2>合婚基础分析</h2>
            <div class="score-display">
              <span class="score-number">{{ freeResult.hehun.score }}</span>
              <span class="score-label">匹配分</span>
            </div>
          </div>

          <div v-if="hasReducedPrecision" class="precision-summary-card precision-summary-card--result">
            <div class="precision-summary-header">
              <el-icon><WarningFilled /></el-icon>
              <div>
                <strong>本次结果包含低精度出生时刻</strong>
                <p>当前结论更适合参考整体关系趋势；如需更细的时柱判断，请补充准确出生时间后重新测算。</p>
              </div>
            </div>
          </div>
          
          <div class="result-level" :class="freeResult.hehun.level">
            {{ freeResult.hehun.level_text }}
          </div>
          
          <p class="result-comment">{{ freeResult.hehun.comment }}</p>
          
          <div class="bazi-compare">
            <div class="bazi-side">
              <h4>男方八字</h4>
              <div class="bazi-pillars">
                <span class="pillar">{{ freeResult.male_bazi.year }}</span>
                <span class="pillar">{{ freeResult.male_bazi.month }}</span>
                <span class="pillar">{{ freeResult.male_bazi.day }}</span>
                <span class="pillar">{{ freeResult.male_bazi.hour }}</span>
              </div>
              <p class="day-master">日主：{{ freeResult.male_bazi.day_master }}</p>
            </div>
            <div class="bazi-divider"><el-icon :size="24"><Link /></el-icon></div>
            <div class="bazi-side">
              <h4>女方八字</h4>
              <div class="bazi-pillars">
                <span class="pillar">{{ freeResult.female_bazi.year }}</span>
                <span class="pillar">{{ freeResult.female_bazi.month }}</span>
                <span class="pillar">{{ freeResult.female_bazi.day }}</span>
                <span class="pillar">{{ freeResult.female_bazi.hour }}</span>
              </div>
              <p class="day-master">日主：{{ freeResult.female_bazi.day_master }}</p>
            </div>
          </div>
          
          <div class="suggestion-box">
            <h4><el-icon><Collection /></el-icon> 建议</h4>
            <p>{{ freeResult.hehun.suggestions[0] }}</p>
          </div>
          
          <div class="upgrade-prompt" v-if="!isLoading">
            <p>{{ freeResult.preview_hint }}</p>
            <p class="upgrade-note">当前基础分析未启用 AI；若需 AI 深度解读，请保持当前勾选并解锁完整版。</p>
            <p v-if="pricingStatusText" class="pricing-status" :class="{ 'pricing-status--error': Boolean(pricingError), 'pricing-status--loading': pricingLoading }">
              {{ pricingStatusText }}
            </p>
            <button class="btn-upgrade" :disabled="!canUnlockPremium" @click="unlockPremium">
              <el-icon><Unlock /></el-icon>
              解锁详细报告
              <span class="points-tag">{{ pricingDisplayText }}</span>
            </button>

          </div>
        </div>
      </div>

      <!-- 付费详细结果 -->
      <div v-else-if="premiumResult" class="result-section">
        <div class="result-card premium card-hover">
          <div class="result-header">
            <h2>详细合婚报告</h2>
            <div class="premium-badge">完整版</div>
          </div>

          <div v-if="hasReducedPrecision" class="precision-summary-card precision-summary-card--result">
            <div class="precision-summary-header">
              <el-icon><WarningFilled /></el-icon>
              <div>
                <strong>本次详细报告包含低精度出生时刻</strong>
                <p>系统已尽量保留合婚核心趋势，但部分细分维度仍建议在补齐准确时辰后再复核一次。</p>
              </div>
            </div>
          </div>
          
          <!-- 综合评分 -->
          <div class="score-section">
            <div class="main-score">
              <span class="score-number">{{ premiumResult.hehun.score }}</span>
              <span class="score-label">综合匹配分</span>
              <span class="score-level" :class="premiumResult.hehun.level">
                {{ premiumResult.hehun.level_text }}
              </span>
            </div>
            
            <!-- 五维度评分 -->
            <div class="dimension-scores">
              <div v-for="(score, key) in premiumResult.hehun.dimensions" :key="key" class="dimension-item">
                <span class="dim-name">{{ dimensionNames[key] }}</span>
                <div class="dim-bar">
                  <div class="dim-fill" :style="{ width: score + '%' }"></div>
                </div>
                <span class="dim-score">{{ score }}分</span>
              </div>
            </div>
          </div>
          
          <!-- 详细分析 -->
          <div class="analysis-section">
            <h3>详细分析</h3>
            <div class="analysis-content rich-content" v-html="sanitizeHtml(premiumResult.hehun.detail_analysis)"></div>
          </div>
          
          <!-- AI分析 -->
          <div class="ai-section" v-if="premiumResult.ai_analysis">
            <h3><el-icon><Cpu /></el-icon> AI深度解读</h3>
            <div class="ai-content rich-content" v-html="premiumAiAnalysisHtml"></div>
          </div>

          
          <!-- 化解建议 -->
          <div class="solution-section" v-if="premiumResult.hehun.solutions">
            <h3><el-icon><Present /></el-icon> 化解建议</h3>
            <ul class="solution-list">
              <li v-for="(solution, idx) in premiumResult.hehun.solutions" :key="idx">
                {{ solution }}
              </li>
            </ul>
          </div>
          
          <!-- 操作按钮 -->
          <div class="action-buttons">
            <button class="btn-secondary" @click="resetForm">
              <el-icon><RefreshRight /></el-icon> 重新测算
            </button>
            <button class="btn-primary" @click="exportReport" :disabled="exporting || !canExportReport">
              <el-icon><Document /></el-icon> {{ exporting ? '导出中...' : '导出报告' }}
            </button>

          </div>
        </div>
      </div>

      <!-- 输入表单 -->
      <div v-else class="form-section">
        <div class="form-card card-hover">
          <h2>输入双方出生信息</h2>
          
          <!-- 男方信息 -->
          <div class="person-section card-hover">
            <h3 class="person-title">
              <el-icon class="gender-icon"><Male /></el-icon>
              男方信息
            </h3>
            <div class="form-row">
              <div class="form-group">
                <label>姓名（可选）</label>
                <input 
                  v-model="form.maleName" 
                  type="text" 
                  placeholder="输入姓名"
                  maxlength="10"
                />
              </div>
            </div>
            <div class="birth-precision-panel">
              <label class="precision-heading">出生时刻精度</label>
              <div class="precision-options">
                <button
                  v-for="option in birthPrecisionOptions"
                  :key="`male-${option.value}`"
                  type="button"
                  class="precision-option"
                  :class="{ active: form.maleBirthPrecision === option.value }"
                  @click="form.maleBirthPrecision = option.value"
                >
                  <span class="precision-option-title">{{ option.label }}</span>
                  <span class="precision-option-desc">{{ option.desc }}</span>
                </button>
              </div>
              <p class="precision-helper">{{ getBirthPrecisionHint(form.maleBirthPrecision) }}</p>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>{{ getBirthInputLabel(form.maleBirthPrecision) }} <span class="required">*</span></label>
                <input 
                  v-model="form.maleBirthDate"
                  :type="getBirthInputType(form.maleBirthPrecision)"
                  required
                />
                <p class="field-helper">{{ getBirthFieldHelper(form.maleBirthPrecision) }}</p>
              </div>
            </div>
            <div v-if="form.maleBirthPrecision === 'range'" class="time-range-panel">
              <label class="time-range-label">大概出生时段</label>
              <div class="time-range-options">
                <button
                  v-for="option in birthTimeRangeOptions"
                  :key="`male-range-${option.value}`"
                  type="button"
                  class="time-range-chip"
                  :class="{ active: form.maleBirthTimeRange === option.value }"
                  @click="form.maleBirthTimeRange = option.value"
                >
                  <span>{{ option.label }}</span>
                  <small>{{ option.hint }}</small>
                </button>
              </div>
            </div>
            <div class="precision-confidence" :class="`precision-confidence--${form.maleBirthPrecision}`">
              <span class="confidence-badge">{{ getBirthPrecisionBadge(form.maleBirthPrecision) }}</span>
              <p>{{ getBirthConfidenceCopy(form.maleBirthPrecision, '男方') }}</p>
            </div>
          </div>
          
          <!-- 女方信息 -->
          <div class="person-section">
            <h3 class="person-title">
              <el-icon class="gender-icon"><Female /></el-icon>
              女方信息
            </h3>
            <div class="form-row">
              <div class="form-group">
                <label>姓名（可选）</label>
                <input 
                  v-model="form.femaleName" 
                  type="text" 
                  placeholder="输入姓名"
                  maxlength="10"
                />
              </div>
            </div>
            <div class="birth-precision-panel">
              <label class="precision-heading">出生时刻精度</label>
              <div class="precision-options">
                <button
                  v-for="option in birthPrecisionOptions"
                  :key="`female-${option.value}`"
                  type="button"
                  class="precision-option"
                  :class="{ active: form.femaleBirthPrecision === option.value }"
                  @click="form.femaleBirthPrecision = option.value"
                >
                  <span class="precision-option-title">{{ option.label }}</span>
                  <span class="precision-option-desc">{{ option.desc }}</span>
                </button>
              </div>
              <p class="precision-helper">{{ getBirthPrecisionHint(form.femaleBirthPrecision) }}</p>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>{{ getBirthInputLabel(form.femaleBirthPrecision) }} <span class="required">*</span></label>
                <input 
                  v-model="form.femaleBirthDate"
                  :type="getBirthInputType(form.femaleBirthPrecision)"
                  required
                />
                <p class="field-helper">{{ getBirthFieldHelper(form.femaleBirthPrecision) }}</p>
              </div>
            </div>
            <div v-if="form.femaleBirthPrecision === 'range'" class="time-range-panel">
              <label class="time-range-label">大概出生时段</label>
              <div class="time-range-options">
                <button
                  v-for="option in birthTimeRangeOptions"
                  :key="`female-range-${option.value}`"
                  type="button"
                  class="time-range-chip"
                  :class="{ active: form.femaleBirthTimeRange === option.value }"
                  @click="form.femaleBirthTimeRange = option.value"
                >
                  <span>{{ option.label }}</span>
                  <small>{{ option.hint }}</small>
                </button>
              </div>
            </div>
            <div class="precision-confidence" :class="`precision-confidence--${form.femaleBirthPrecision}`">
              <span class="confidence-badge">{{ getBirthPrecisionBadge(form.femaleBirthPrecision) }}</span>
              <p>{{ getBirthConfidenceCopy(form.femaleBirthPrecision, '女方') }}</p>
            </div>
          </div>
          
          <!-- 选项 -->
          <div class="options-section">
            <div class="option-plan-card">
              <div class="plan-badge-row">
                <span class="plan-badge plan-badge--free">免费预览</span>
                <span class="plan-badge plan-badge--premium">完整版</span>
              </div>
              <p class="plan-summary">免费预览仅返回基础匹配分与简要建议；AI 深度分析只在解锁完整版时生效。</p>
            </div>
            <label class="option-item" :class="{ active: form.useAi }">
              <input type="checkbox" v-model="form.useAi" />
              <span class="option-copy">
                <span class="option-title">解锁完整版时启用 AI 深度分析</span>
                <span class="option-desc">当前免费预览固定不启用 AI，勾选仅影响后续详细报告。</span>
              </span>
            </label>
          </div>
          
          <!-- 定价信息 -->
          <div class="pricing-info" v-if="pricingLoading || normalizedPricing || pricingError">
            <div class="pricing-row">
              <span>本次消耗：</span>
              <span class="points">{{ pricingDisplayText }}</span>
              <span v-if="normalizedPricing?.discount > 0" class="discount">-{{ normalizedPricing.discount }}%</span>
            </div>
            <p v-if="pricingStatusText" class="pricing-reason">{{ pricingStatusText }}</p>
            <p v-else-if="normalizedPricing?.reason" class="pricing-reason">{{ normalizedPricing.reason }}</p>
          </div>

          <div v-if="hasReducedPrecision" class="precision-summary-card">
            <div class="precision-summary-header">
              <el-icon><WarningFilled /></el-icon>
              <div>
                <strong>当前为低精度合婚输入</strong>
                <p>可以先看关系趋势，但涉及时柱的细节判断会更保守，请尽量补充更准确的出生时间。</p>
              </div>
            </div>
            <div class="precision-summary-list">
              <div v-for="item in precisionSummaryList" :key="item.role" class="precision-summary-item">
                <span class="summary-role">{{ item.role }}</span>
                <div class="summary-copy">
                  <strong>{{ item.modeLabel }}</strong>
                  <span>{{ item.detail }}</span>
                </div>
                <span class="summary-trust">{{ item.confidence }}</span>
              </div>
            </div>
          </div>

          
          <!-- 提交按钮 -->
          <button 
            class="btn-submit" 
            @click="submitForm"
            :disabled="isLoading || !isFormValid"
          >
            <div v-if="isLoading" class="loading-taiji mini"></div>
            <span v-else><el-icon><Link /></el-icon> 开始合婚分析</span>
          </button>
          
          <p class="form-hint">
            <el-icon><Collection /></el-icon> 首次查看基础分析免费，详细报告需消耗积分或开通VIP
          </p>
        </div>
      </div>

      <!-- 历史记录 -->
      <div class="history-section" v-if="historyLoaded || historyLoading || historyError">
        <div class="history-header">
          <h3>历史记录</h3>
          <el-button v-if="historyError" type="primary" link @click="loadHistory">重新加载</el-button>
        </div>
        <div v-if="historyLoading" class="history-state">
          <p>正在加载历史记录...</p>
          <span>最近的合婚分析会在这里展示。</span>
        </div>
        <div v-else-if="historyError" class="history-state history-state--error">
          <p>{{ historyError }}</p>
          <span>可以稍后重试，或重新做一次合婚分析生成新记录。</span>
        </div>
        <div v-else-if="history.length === 0" class="history-state">
          <p>还没有合婚记录</p>
          <span>完成一次分析后，这里会展示最近的 5 条记录。</span>
        </div>
        <div v-else class="history-list">
          <button
            v-for="item in history"
            :key="item.id"
            type="button"
            class="history-item"
            :class="{ 'is-active': activeHistoryId === item.id }"
            @click="loadHistoryDetail(item)"
          >
            <div class="history-main">
              <div class="history-topline">
                <span class="history-names">{{ formatHistoryNames(item) }}</span>
                <div class="history-badges">
                  <span class="history-badge history-badge--tier" :class="`history-badge--${item.tier}`">
                    <el-icon><Lock v-if="item.is_premium" /><Unlock v-else /></el-icon>
                    {{ item.typeLabel }}
                  </span>
                  <span class="history-badge history-badge--ai" :class="{ 'history-badge--muted': !item.hasAiAnalysis }">
                    <el-icon><CircleCheckFilled v-if="item.hasAiAnalysis" /><Cpu v-else /></el-icon>
                    {{ item.hasAiAnalysis ? '含AI解读' : '未启用AI' }}
                  </span>
                </div>
              </div>
              <div class="history-meta">
                <span><el-icon><Calendar /></el-icon>{{ formatDate(item.created_at) }}</span>
                <span><el-icon><StarFilled /></el-icon>{{ item.score }}分{{ item.level_text ? ` · ${item.level_text}` : '' }}</span>
                <span>{{ item.accessLabel }}</span>
              </div>
              <p class="history-summary">{{ item.summary }}</p>
            </div>
            <span class="history-action">
              {{ item.ctaLabel }}
              <el-icon><ArrowRight /></el-icon>
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import DOMPurify from 'dompurify'
import { Male, Female, Unlock, Lock, Link, RefreshRight, Document, Collection, Present, Cpu, WarningFilled, Calendar, ArrowRight, StarFilled, CircleCheckFilled } from '@element-plus/icons-vue'
import { getHehunPricing, calculateHehun, getHehunHistory, exportHehunReport } from '../api'
import BackButton from '../components/BackButton.vue'

/**
 * HTML净化函数 - 防止XSS攻击
 * 使用DOMPurify库进行专业清理
 */
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

const defaultBirthTimeRange = 'forenoon'
const birthTimeRangeMap = birthTimeRangeOptions.reduce((acc, option) => {
  acc[option.value] = option
  return acc
}, {})

// 表单数据
const form = reactive({
  maleName: '',
  maleBirthDate: '',
  maleBirthPrecision: 'exact',
  maleBirthTimeRange: defaultBirthTimeRange,
  femaleName: '',
  femaleBirthDate: '',
  femaleBirthPrecision: 'exact',
  femaleBirthTimeRange: defaultBirthTimeRange,
  useAi: true,
})

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
const getBirthInputType = (precision) => (precision === 'exact' ? 'datetime-local' : 'date')

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
    return '先选择生日，再补充一个大概出生时段。'
  }
  if (precision === 'unknown') {
    return '仅用生日先看趋势，涉及时柱的结论会保守处理。'
  }
  return '建议尽量填写准确时间，减少时柱偏差。'
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

  const matchedRange = birthTimeRangeMap[timeRange] || birthTimeRangeMap[defaultBirthTimeRange]
  return `${dateOnly} ${matchedRange.time}`
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
      timeRange: defaultBirthTimeRange,
    }
  }

  const trimmed = String(birthDate).trim()

  if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
    return {
      value: trimmed,
      precision: 'unknown',
      timeRange: defaultBirthTimeRange,
    }
  }

  const match = trimmed.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})(?::\d{2})?$/)
  if (!match) {
    return {
      value: trimmed,
      precision: 'exact',
      timeRange: defaultBirthTimeRange,
    }
  }

  const [, date, hour, minute] = match
  return {
    value: `${date}T${hour}:${minute}`,
    precision: 'exact',
    timeRange: resolveTimeRangeByClock(`${hour}:${minute}`),
  }
}

const precisionSummaryList = computed(() => ([
  {
    role: '男方',
    modeLabel: getBirthPrecisionLabel(form.maleBirthPrecision),
    confidence: getBirthPrecisionBadge(form.maleBirthPrecision),
    detail: getBirthConfidenceCopy(form.maleBirthPrecision, '男方'),
  },
  {
    role: '女方',
    modeLabel: getBirthPrecisionLabel(form.femaleBirthPrecision),
    confidence: getBirthPrecisionBadge(form.femaleBirthPrecision),
    detail: getBirthConfidenceCopy(form.femaleBirthPrecision, '女方'),
  },
]))

const hasReducedPrecision = computed(() => {
  return form.maleBirthPrecision !== 'exact' || form.femaleBirthPrecision !== 'exact'
})

const buildHehunPayload = ({ tier, useAi }) => ({
  maleName: form.maleName || '男方',
  maleBirthDate: resolveBirthDatePayload(form.maleBirthDate, form.maleBirthPrecision, form.maleBirthTimeRange),
  maleBirthPrecision: form.maleBirthPrecision,
  maleBirthTimeRange: form.maleBirthTimeRange,
  femaleName: form.femaleName || '女方',
  femaleBirthDate: resolveBirthDatePayload(form.femaleBirthDate, form.femaleBirthPrecision, form.femaleBirthTimeRange),
  femaleBirthPrecision: form.femaleBirthPrecision,
  femaleBirthTimeRange: form.femaleBirthTimeRange,
  tier,
  useAi,
})

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
const canUnlockPremium = computed(() => Boolean(freeResult.value) && Boolean(normalizedPricing.value) && !isLoading.value)
const premiumUnlockMessage = computed(() => {
  const points = normalizedPricing.value?.final
  if (!Number.isFinite(points)) {
    return '完整版价格暂未确认，请稍后再试。'
  }

  if (points <= 0) {
    return form.useAi
      ? '您当前可免费解锁详细报告，并启用 AI 深度分析，是否继续？'
      : '您当前可免费解锁详细报告，是否继续？'
  }

  return form.useAi
    ? `解锁详细报告将消耗 ${points} 积分，并启用 AI 深度分析，是否继续？`
    : `解锁详细报告将消耗 ${points} 积分，是否继续？`
})

const clearUnlockFeedback = () => {
  unlockError.value = null
  unlockLoading.value = false
}

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

  const traditionalMethods = normalizeObjectField(hehun.traditional_methods, {})
  const traditionalEntries = Object.entries(traditionalMethods).filter(([, value]) => value && typeof value === 'object')
  if (traditionalEntries.length) {
    sections.push(`
      <h4>传统合婚补充</h4>
      <ul>${traditionalEntries.map(([key, value]) => `<li><strong>${escapeHtml(key === 'sanyuan' ? '三元宫位' : key === 'jiugong' ? '九宫关系' : key)}</strong>：${escapeHtml(value.type || value.meaning || value.description || JSON.stringify(value))}</li>`).join('')}</ul>
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

const normalizePremiumResultData = (payload = {}) => ({
  ...payload,
  tier: payload.tier || 'premium',
  hehun: normalizeHehunData(payload.hehun),
  ai_analysis: normalizeAiField(payload.ai_analysis),
  male_bazi: normalizeObjectField(payload.male_bazi, {}),
  female_bazi: normalizeObjectField(payload.female_bazi, {}),
})

const premiumAiAnalysisHtml = computed(() => sanitizeHtml(formatAiAnalysisHtml(premiumResult.value?.ai_analysis)))

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

const buildHistorySummary = (tier, hasAiAnalysis, pointsCost) => {
  if (tier === 'free') {
    return '保留基础匹配分与简评，可继续解锁完整版查看五维分析与化解建议。'
  }

  const accessCopy = tier === 'vip'
    ? '会员完整版记录'
    : pointsCost > 0
      ? `${pointsCost} 积分解锁记录`
      : '已解锁完整版记录'

  return `${accessCopy}，${hasAiAnalysis ? '包含 AI 深度解读' : '未启用 AI 扩展'}，点击可回看完整内容。`
}

const normalizeHistoryItem = (item = {}) => {
  const tier = resolveHistoryTier(item)
  const aiAnalysis = normalizeAiField(item.ai_analysis)
  const resultData = normalizeObjectField(item.result, {})
  const pointsCost = Number(item.points_cost ?? 0)
  const createdAt = item.create_time || item.created_at || ''
  const hasAiAnalysis = Boolean(item.is_ai_analysis) || hasAiContent(aiAnalysis)

  const inputMeta = resultData.input_meta || {}

  return {
    ...item,
    result: resultData,
    ai_analysis: aiAnalysis,
    male_bazi: normalizeObjectField(item.male_bazi, {}),
    female_bazi: normalizeObjectField(item.female_bazi, {}),
    male_birth_precision: item.male_birth_precision || inputMeta.male_birth_precision || '',
    female_birth_precision: item.female_birth_precision || inputMeta.female_birth_precision || '',
    male_birth_time_range: item.male_birth_time_range || inputMeta.male_birth_time_range || defaultBirthTimeRange,
    female_birth_time_range: item.female_birth_time_range || inputMeta.female_birth_time_range || defaultBirthTimeRange,
    male_birth_time: item.male_birth_time || '',
    female_birth_time: item.female_birth_time || '',
    score: Number(item.score ?? resultData.score ?? 0),
    level: item.level || resultData.level || '',
    level_text: item.level_text || resultData.level_text || '',
    points_cost: pointsCost,
    tier,
    is_premium: tier !== 'free',
    hasAiAnalysis,
    typeLabel: historyTierCopy[tier]?.label || '历史记录',
    ctaLabel: historyTierCopy[tier]?.cta || '查看记录',
    accessLabel: resolveHistoryAccessLabel(tier, pointsCost),
    summary: buildHistorySummary(tier, hasAiAnalysis, pointsCost),
    created_at: createdAt,
    create_time: createdAt,
  }
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
    console.error('获取定价失败:', error)
  } finally {
    pricingLoading.value = false
  }
}

// 提交表单（免费预览）
const submitForm = async () => {
  if (!isFormValid.value) {
    ElMessage.warning('请填写双方出生日期')
    return
  }
  
  isLoading.value = true
  try {
    const response = await calculateHehun(buildHehunPayload({
      tier: 'free',
      useAi: false,
    }))
    
    if (response.code === 200) {
      premiumResult.value = null
      freeResult.value = response.data
      clearUnlockFeedback()
      ElMessage.success('基础合婚分析完成')

      try {
        await loadHistory() // 刷新历史记录
      } catch (historyError) {
        console.warn('合婚结果已生成，但历史记录刷新失败:', historyError)
      }
    } else {
      ElMessage.error(response.message)
    }

  } catch (error) {
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
    const response = await calculateHehun(buildHehunPayload({
      tier: 'premium',
      useAi: form.useAi,
    }))
    
    if (response.code === 200) {
      freeResult.value = null
      premiumResult.value = response.data
      ElMessage.success('解锁成功！')
    } else {
      if (response.code === 403) {
        ElMessage.error('积分不足，请先充值')
      } else {
        ElMessage.error(response.message)
      }
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('解锁失败，请重试')
    }
  } finally {
    isLoading.value = false
  }
}

// 重置表单
const resetForm = () => {
  freeResult.value = null
  premiumResult.value = null
  activeHistoryId.value = null
  clearUnlockFeedback()
  form.maleName = ''
  form.maleBirthDate = ''
  form.maleBirthPrecision = 'exact'
  form.maleBirthTimeRange = defaultBirthTimeRange
  form.femaleName = ''
  form.femaleBirthDate = ''
  form.femaleBirthPrecision = 'exact'
  form.femaleBirthTimeRange = defaultBirthTimeRange
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
      link.download = `合婚报告_${form.maleName}_${form.femaleName}.pdf`
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
      history.value = resolveHistoryList(response.data).map(normalizeHistoryItem)
      if (activeHistoryId.value && !history.value.some((item) => item.id === activeHistoryId.value)) {
        activeHistoryId.value = null
      }
    } else {
      history.value = []
      historyError.value = response.message || '历史记录加载失败，请稍后重试'
    }
  } catch (error) {
    history.value = []
    historyError.value = '历史记录加载失败，请稍后重试'
    console.error('获取历史记录失败:', error)
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
  const maleLabel = getHistoryPersonLabel(item.male_name, item.male_birth_date, '男方')
  const femaleLabel = getHistoryPersonLabel(item.female_name, item.female_birth_date, '女方')
  return `${maleLabel} & ${femaleLabel}`
}

const buildHistoryFreeResult = (item, hehunData, maleBaziData, femaleBaziData) => normalizeFreeResultData({
  ...item,
  tier: 'free',
  hehun: {
    ...hehunData,
    suggestions: Array.isArray(hehunData.suggestions) && hehunData.suggestions.length
      ? hehunData.suggestions
      : ['可先查看基础匹配趋势，若需要五维分析和 AI 解读，可继续解锁完整版。'],
  },
  male_bazi: maleBaziData,
  female_bazi: femaleBaziData,
  preview_hint: '这是你之前保存的免费预览记录；如需五维分析和 AI 解读，请重新解锁完整版。',
})

const buildHistoryPremiumResult = (item, hehunData, aiAnalysisData, maleBaziData, femaleBaziData) => normalizePremiumResultData({
  id: item.id,
  tier: item.tier,
  hehun: hehunData,
  ai_analysis: aiAnalysisData,
  male_bazi: maleBaziData,
  female_bazi: femaleBaziData,
})

const toDatetimeLocalValue = (value = '') => {
  const trimmed = String(value || '').trim()
  const match = trimmed.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})/)
  if (!match) {
    return trimmed
  }

  return `${match[1]}T${match[2]}:${match[3]}`
}

const resolveHistoryBirthState = (item, role) => {
  const birthValue = String(item?.[`${role}_birth_date`] || '').trim()
  const precision = item?.[`${role}_birth_precision`] || ''
  const timeRange = item?.[`${role}_birth_time_range`] || defaultBirthTimeRange

  if (precision === 'exact') {
    return {
      value: toDatetimeLocalValue(birthValue),
      precision: 'exact',
      timeRange,
    }
  }

  if (precision === 'range' || precision === 'unknown') {
    return {
      value: birthValue.slice(0, 10),
      precision,
      timeRange,
    }
  }

  return hydrateBirthState(birthValue)
}

// 加载历史记录详情
const loadHistoryDetail = (item) => {
  const normalizedItem = normalizeHistoryItem(item)

  try {
    activeHistoryId.value = normalizedItem.id

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
  } catch (error) {
    console.error('加载历史记录失败:', error)
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
  loadPricing()
  loadHistory()
})
</script>

<style scoped>
.hehun-page {
  padding: 40px 20px;
  min-height: 100vh;
}

.container {
  max-width: 800px;
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
  border-radius: var(--radius-lg);
  padding: 40px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
}

.form-card h2 {
  color: var(--text-primary);
  text-align: center;
  margin-bottom: 30px;
}

.person-section {
  margin-bottom: 30px;
  padding: 24px;
  background: var(--primary-light-05);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-light);
  transition: all 0.3s ease;
}

.person-section:hover {
  background: var(--primary-light-10);
  border-color: var(--primary-light-20);
}

.person-title {
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  font-size: 18px;
}

.gender-icon {
  font-size: 24px;
  color: var(--primary-color);
}

.form-row {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  color: var(--text-secondary);
  margin-bottom: 8px;
  font-size: 14px;
}

.form-group input {
  width: 100%;
  padding: 14px 16px;
  min-height: 48px;
  background: var(--bg-tertiary);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-md);
  color: var(--text-primary);
  font-size: 15px;
  transition: all 0.3s;
}

.form-group input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: var(--focus-ring);
}

.required {
  color: var(--primary-color);
}

.field-helper {
  margin-top: 8px;
  color: var(--text-tertiary);
  font-size: var(--font-caption);
  line-height: 1.6;
}

.birth-precision-panel {
  margin-bottom: 18px;
}

.precision-heading,
.time-range-label {
  display: block;
  margin-bottom: 10px;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.precision-options {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.precision-option {
  min-height: 88px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: var(--bg-secondary);
  color: var(--text-secondary);
  text-align: left;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 8px;
  cursor: pointer;
  transition: transform 0.25s ease, border-color 0.25s ease, background-color 0.25s ease, box-shadow 0.25s ease;
}

.precision-option:hover,
.precision-option.active {
  transform: translateY(-2px);
  border-color: var(--primary-light-30);
  background: rgba(var(--primary-rgb), 0.08);
  box-shadow: var(--shadow-sm);
}

.precision-option-title {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.precision-option-desc {
  color: var(--text-tertiary);
  font-size: 12px;
  line-height: 1.6;
}

.precision-helper {
  margin-top: 10px;
  color: var(--text-tertiary);
  font-size: 13px;
  line-height: 1.7;
}

.time-range-panel {
  margin-bottom: 18px;
}

.time-range-options {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.time-range-chip {
  min-height: 56px;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: var(--bg-secondary);
  color: var(--text-primary);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  gap: 4px;
  cursor: pointer;
  transition: transform 0.25s ease, border-color 0.25s ease, background-color 0.25s ease, box-shadow 0.25s ease;
}

.time-range-chip small {
  color: var(--text-tertiary);
  font-size: 11px;
}

.time-range-chip:hover,
.time-range-chip.active {
  transform: translateY(-2px);
  border-color: var(--primary-light-30);
  background: rgba(var(--primary-rgb), 0.08);
  box-shadow: var(--shadow-sm);
}

.precision-confidence {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: rgba(255, 255, 255, 0.02);
}

.precision-confidence--exact {
  border-color: rgba(103, 194, 58, 0.18);
  background: rgba(103, 194, 58, 0.08);
}

.precision-confidence--range {
  border-color: rgba(230, 162, 60, 0.2);
  background: rgba(230, 162, 60, 0.08);
}

.precision-confidence--unknown {
  border-color: rgba(245, 108, 108, 0.18);
  background: rgba(245, 108, 108, 0.08);
}

.precision-confidence p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.confidence-badge {
  min-width: 72px;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(var(--primary-rgb), 0.18);
  color: var(--text-primary);
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.options-section {
  margin: 20px 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}


.option-plan-card {
  padding: 16px 18px;
  border-radius: var(--radius-md);
  background: linear-gradient(135deg, var(--primary-light-10), rgba(var(--primary-rgb), 0.04));
  border: 1px solid var(--primary-light-20);
}

.plan-badge-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 10px;
}

.plan-badge {
  display: inline-flex;
  align-items: center;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.plan-badge--free {
  background: rgba(16, 185, 129, 0.14);
  color: var(--success-color);
}

.plan-badge--premium {
  background: rgba(var(--primary-rgb), 0.14);
  color: var(--primary-color);
}

.plan-summary {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.option-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 16px 18px;
  border-radius: var(--radius-md);
  background: var(--bg-secondary);
  border: 1px solid var(--border-light);
  transition: all 0.3s ease;
}

.option-item.active {
  border-color: var(--primary-light-30);
  background: rgba(var(--primary-rgb), 0.08);
  box-shadow: var(--shadow-sm);
}

.option-item input {
  width: 18px;
  height: 18px;
  margin-top: 2px;
  accent-color: var(--primary-color);
}

.option-copy {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.option-title {
  color: var(--text-primary);
  font-weight: 600;
}

.option-desc {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.6;
}

.pricing-info {
  text-align: center;
  padding: 16px;
  background: var(--primary-light-10);
  border-radius: var(--radius-md);
  margin: 20px 0;
  border: 1px solid var(--primary-light-20);
}

.pricing-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: var(--text-primary);
}

.pricing-row .points {
  font-size: 24px;
  font-weight: bold;
  color: var(--star-color);
}

.discount {
  background: var(--primary-color);
  color: var(--text-primary);
  padding: 4px 10px;
  border-radius: var(--radius-xl);
  font-size: 12px;
}

.pricing-reason {
  color: var(--text-tertiary);
  font-size: 13px;
  margin-top: 8px;
}

.precision-summary-card {
  padding: 18px 20px;
  border-radius: var(--radius-lg);
  border: 1px solid rgba(230, 162, 60, 0.22);
  background: linear-gradient(135deg, rgba(230, 162, 60, 0.12), rgba(184, 134, 11, 0.06));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.precision-summary-card--result {
  margin-bottom: 20px;
}

.precision-summary-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.precision-summary-header .el-icon {
  margin-top: 2px;
  color: var(--warning-color);
  font-size: 18px;
  flex-shrink: 0;
}

.precision-summary-header strong {
  display: block;
  color: var(--text-primary);
  margin-bottom: 6px;
}

.precision-summary-header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.precision-summary-list {
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.precision-summary-item {
  display: grid;
  grid-template-columns: 56px minmax(0, 1fr) auto;
  gap: 12px;
  align-items: center;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: rgba(0, 0, 0, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.04);
}

.summary-role {
  color: var(--text-primary);
  font-weight: 600;
}

.summary-copy {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.summary-copy strong {
  color: var(--text-primary);
  font-size: 14px;
}

.summary-copy span {
  color: var(--text-secondary);
  font-size: 12px;
  line-height: 1.6;
}

.summary-trust {
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(230, 162, 60, 0.18);
  color: var(--text-primary);
  font-size: 12px;
  font-weight: 700;
}

.btn-submit {
  width: 100%;
  padding: 16px;
  min-height: 48px;
  background: var(--primary-gradient);
  color: var(--text-accent-contrast);
  border: 1px solid var(--primary-light-30);
  border-radius: var(--radius-btn);
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 12px 28px rgba(var(--primary-rgb), 0.24);
}


.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-submit:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(184, 134, 11, 0.4);
}

.form-hint {
  text-align: center;
  color: var(--text-secondary);
  font-size: 13px;
  margin-top: 16px;
}

/* 结果卡片 */
.result-section {
  margin-bottom: 40px;
}

.result-card {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-lg);
  padding: 32px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
}

.result-card.premium {
  border-color: var(--primary-light-30);
  background: linear-gradient(135deg, var(--bg-card), rgba(184, 134, 11, 0.05));
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

.premium-badge {
  background: var(--primary-gradient);
  color: var(--text-primary);
  padding: 6px 16px;
  border-radius: var(--radius-xl);
  font-size: 12px;
  font-weight: 600;
}

.score-display {
  text-align: center;
}

.score-number {
  display: block;
  font-size: 48px;
  font-weight: bold;
  color: var(--star-color);
  line-height: 1;
}

.score-label {
  color: var(--text-tertiary);
  font-size: 14px;
}

.result-level {
  text-align: center;
  padding: 12px 24px;
  border-radius: var(--radius-xl);
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 20px;
}

.result-level.excellent {
  background: var(--success-light);
  color: var(--success-color);
}

.result-level.good {
  background: rgba(24, 144, 255, 0.2);
  color: #1890ff;
}

.result-level.average {
  background: var(--warning-light);
  color: var(--warning-color);
}

.result-level.poor {
  background: var(--danger-light);
  color: var(--danger-color);
}

.result-comment {
  color: var(--text-secondary);
  text-align: center;
  font-size: 16px;
  line-height: 1.6;
  margin-bottom: 24px;
}

/* 八字对比 */
.bazi-compare {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 30px;
  padding: 24px;
  background: var(--bg-tertiary);
  border-radius: var(--radius-md);
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.bazi-side {
  text-align: center;
}

.bazi-side h4 {
  color: var(--text-tertiary);
  margin-bottom: 12px;
  font-size: 14px;
}

.bazi-pillars {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
}

.pillar {
  padding: 8px 12px;
  background: var(--bg-card);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-weight: 500;
  border: 1px solid var(--border-light);
}

.day-master {
  color: var(--primary-color);
  font-size: 13px;
}

.bazi-divider {
  font-size: 32px;
  color: var(--primary-light-60);
}

/* 建议框 */
.suggestion-box {
  background: var(--success-light);
  border-left: 4px solid var(--success-color);
  padding: 16px 20px;
  border-radius: 0 var(--radius-md) var(--radius-md) 0;
  margin-bottom: 24px;
}

.suggestion-box h4 {
  color: var(--success-color);
  margin-bottom: 8px;
}

.suggestion-box p {
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.6;
}

/* 升级提示 */
.upgrade-prompt {
  text-align: center;
  padding: 24px;
  background: linear-gradient(135deg, var(--primary-light-10), var(--primary-light-05));
  border-radius: var(--radius-md);
  border: 1px dashed var(--primary-light-30);
}

.upgrade-prompt p {
  color: var(--text-secondary);
  margin-bottom: 16px;
}

.upgrade-note {
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: rgba(var(--primary-rgb), 0.08);
  border: 1px solid var(--primary-light-20);
  color: var(--text-primary);
  font-size: 13px;
  line-height: 1.6;
}

.pricing-status {
  margin: 0 0 16px;
  font-size: 13px;
  line-height: 1.6;
  color: var(--text-secondary);
}

.pricing-status--loading {
  color: var(--text-secondary);
}

.pricing-status--error {
  color: var(--danger-color);
}

.btn-upgrade {
  padding: 14px 32px;
  min-height: 48px;
  background: var(--primary-gradient);
  color: var(--text-accent-contrast);
  border: 1px solid var(--primary-light-30);
  border-radius: var(--radius-btn);
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s;
  box-shadow: 0 12px 28px rgba(var(--primary-rgb), 0.24);
}

.btn-upgrade:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-upgrade:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px var(--primary-light-40);
}

.points-tag {
  background: var(--white-20);
  padding: 4px 10px;
  border-radius: var(--radius-xl);
  font-size: 12px;
}

/* 详细报告样式 */
.score-section {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 40px;
  padding: 24px;
  background: linear-gradient(180deg, var(--surface-raised), rgba(var(--primary-rgb), 0.06));
  border-radius: var(--radius-md);
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}


.main-score {
  text-align: center;
  padding: 20px;
  border-right: 1px solid var(--border-light);
}

.main-score .score-number {
  font-size: 64px;
}

.main-score .score-level {
  display: inline-block;
  padding: 8px 20px;
  border-radius: var(--radius-xl);
  font-size: 16px;
  font-weight: 600;
  margin-top: 12px;
}

.dimension-scores {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.dimension-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dim-name {
  width: 80px;
  color: var(--text-secondary);
  font-size: 14px;
}

.dim-bar {
  flex: 1;
  height: 8px;
  background: var(--bg-tertiary);
  border-radius: 4px;
  overflow: hidden;
}

.dim-fill {
  height: 100%;
  background: var(--primary-gradient);
  border-radius: 4px;
  transition: width 0.5s ease;
}

.dim-score {
  width: 50px;
  text-align: right;
  color: var(--text-primary);
  font-weight: 600;
}

/* 分析内容 */
.analysis-section,
.ai-section,
.solution-section {
  margin-bottom: 24px;
}

.analysis-section h3,
.ai-section h3,
.solution-section h3 {
  color: var(--text-primary);
  margin-bottom: 16px;
  font-size: 18px;
}

.analysis-content,
.ai-content {
  color: var(--text-secondary);
  line-height: 1.8;
}

.rich-content {
  padding: 22px 24px;
  border-radius: 18px;
  border: 1px solid var(--border-light);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(184, 134, 11, 0.06));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04), 0 14px 30px rgba(0, 0, 0, 0.18);
}

.rich-content :deep(h1),
.rich-content :deep(h2),
.rich-content :deep(h3),
.rich-content :deep(h4),
.rich-content :deep(h5),
.rich-content :deep(h6) {
  margin: 1.6em 0 0.7em;
  color: var(--text-primary);
  font-weight: 700;
  line-height: 1.35;
}

.rich-content :deep(h1),
.rich-content :deep(h2) {
  font-size: 20px;
}

.rich-content :deep(h3),
.rich-content :deep(h4) {
  font-size: 17px;
}

.rich-content :deep(h1:first-child),
.rich-content :deep(h2:first-child),
.rich-content :deep(h3:first-child),
.rich-content :deep(h4:first-child),
.rich-content :deep(h5:first-child),
.rich-content :deep(h6:first-child),
.rich-content :deep(p:first-child) {
  margin-top: 0;
}

.rich-content :deep(p) {
  margin: 0 0 1em;
  color: var(--text-secondary);
  line-height: 1.9;
}

.rich-content :deep(strong) {
  color: var(--text-primary);
  font-weight: 700;
}

.rich-content :deep(ul),
.rich-content :deep(ol) {
  margin: 0 0 1.1em;
  padding-left: 1.4em;
}

.rich-content :deep(li) {
  margin-bottom: 0.7em;
  color: var(--text-secondary);
  line-height: 1.8;
}

.rich-content :deep(li:last-child),
.rich-content :deep(p:last-child) {
  margin-bottom: 0;
}


.solution-list {
  list-style: none;
  padding: 0;
}

.solution-list li {
  color: var(--text-secondary);
  padding: 12px 0;
  padding-left: 24px;
  position: relative;
  border-bottom: 1px solid var(--border-light);
}

.solution-list li:before {
  content: '';
  position: absolute;
  left: 0;
  top: 18px;
  width: 6px;
  height: 6px;
  background: var(--primary-color);
  border-radius: 1px;
}

/* 操作按钮 */
.action-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
  margin-top: 32px;
}

.btn-primary,
.btn-secondary {
  padding: 12px 32px;
  min-height: 48px;
  border-radius: var(--radius-btn);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s;
  border: none;
}

.btn-primary {
  background: var(--primary-gradient);
  color: var(--text-accent-contrast);
  border: 1px solid var(--primary-light-30);
  box-shadow: 0 12px 28px rgba(var(--primary-rgb), 0.24);
}


.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px var(--primary-light-40);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--bg-tertiary);
  color: var(--text-primary);
  border: 1px solid var(--border-light);
}

.btn-secondary:hover {
  background: var(--bg-hover);
  border-color: var(--primary-color);
}

/* 历史记录 */
.history-section {
  margin-top: 40px;
}

.history-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.history-section h3 {
  color: var(--text-primary);
  margin: 0;
}

.history-state {
  padding: 18px 20px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: var(--bg-card);
  box-shadow: var(--shadow-sm);
}

.history-state p {
  margin: 0 0 8px;
  color: var(--text-primary);
  font-weight: 600;
}

.history-state span {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.history-state--error {
  border-color: rgba(245, 108, 108, 0.2);
  background: rgba(245, 108, 108, 0.08);
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.history-item {
  width: 100%;
  padding: 18px 20px;
  border-radius: var(--radius-lg);
  appearance: none;
  font: inherit;
  color: inherit;
  border: 1px solid var(--border-light);
  background: linear-gradient(135deg, var(--bg-card), rgba(var(--primary-rgb), 0.03));
  box-shadow: var(--shadow-sm);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  text-align: left;
  cursor: pointer;
  transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
}

.history-item:hover {
  transform: translateY(-2px);
  border-color: var(--primary-light-30);
  box-shadow: var(--shadow-md);
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), var(--bg-card));
}

.history-item.is-active {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 1px rgba(var(--primary-rgb), 0.16), var(--shadow-md);
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.14), var(--bg-card));
}

.history-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.history-topline {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.history-names {
  color: var(--text-primary);
  font-weight: 600;
  line-height: 1.6;
}

.history-badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.history-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid transparent;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.history-badge--tier {
  color: var(--text-primary);
}

.history-badge--free {
  background: rgba(var(--primary-rgb), 0.12);
  border-color: var(--primary-light-20);
}

.history-badge--premium {
  background: rgba(230, 162, 60, 0.14);
  border-color: rgba(230, 162, 60, 0.24);
}

.history-badge--vip {
  background: rgba(103, 194, 58, 0.14);
  border-color: rgba(103, 194, 58, 0.24);
}

.history-badge--ai {
  background: var(--bg-secondary);
  border-color: var(--border-light);
  color: var(--text-secondary);
}

.history-badge--muted {
  opacity: 0.82;
}

.history-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 16px;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.6;
}

.history-meta span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.history-summary {
  margin: 0;
  color: var(--text-tertiary);
  font-size: 13px;
  line-height: 1.7;
}

.history-action {
  min-height: 44px;
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.08);
  color: var(--primary-color);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
  align-self: center;
}

/* 响应式 */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 14px;
  }

  .page-title {
    font-size: 28px;
  }
  
  .form-card,
  .result-card {
    padding: 24px;
  }

  .precision-options,
  .time-range-options {
    grid-template-columns: 1fr;
  }

  .precision-option,
  .time-range-chip {
    min-height: 52px;
  }

  .precision-confidence,
  .precision-summary-item {
    grid-template-columns: 1fr;
    flex-direction: column;
    align-items: flex-start;
  }

  .precision-summary-item {
    display: flex;
  }

  .summary-trust {
    margin-left: 0;
  }

  .option-item {
    padding: 14px 16px;
  }
  
  .bazi-compare {
    flex-direction: column;
    gap: 20px;
  }

  .bazi-pillars {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .bazi-divider {
    transform: rotate(90deg);
  }
  
  .score-section {
    grid-template-columns: 1fr;
    gap: 24px;
  }
  
  .main-score {
    border-right: none;
    border-bottom: 1px solid var(--white-10);
    padding-bottom: 24px;
  }

  .pricing-row {
    flex-wrap: wrap;
  }

  .btn-upgrade,
  .btn-primary,
  .btn-secondary {
    width: 100%;
  }

  .rich-content {
    padding: 18px 18px 20px;
  }

  .rich-content :deep(h1),
  .rich-content :deep(h2) {
    font-size: 18px;
  }

  .rich-content :deep(h3),
  .rich-content :deep(h4) {
    font-size: 16px;
  }

  .rich-content :deep(ul),
  .rich-content :deep(ol) {
    padding-left: 1.2em;
  }
  
  .action-buttons {
    flex-direction: column;
  }

  .history-item,
  .history-item.is-active {
    padding: 16px;
  }

  .history-item {
    flex-direction: column;
    align-items: stretch;
  }

  .history-topline {
    flex-direction: column;
  }

  .history-badges {
    justify-content: flex-start;
  }

  .history-action {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .history-meta {
    flex-direction: column;
    gap: 8px;
  }

  .history-badge {
    width: 100%;
    justify-content: center;
  }
}


</style>
