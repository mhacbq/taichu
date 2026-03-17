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
            <button class="btn-upgrade" @click="unlockPremium">
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
            <div class="analysis-content" v-html="sanitizeHtml(premiumResult.hehun.detail_analysis)"></div>
          </div>
          
          <!-- AI分析 -->
          <div class="ai-section" v-if="premiumResult.ai_analysis">
            <h3><el-icon><Cpu /></el-icon> AI深度解读</h3>
            <div class="ai-content" v-html="sanitizeHtml(premiumResult.ai_analysis)"></div>
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
            <div class="form-row">
              <div class="form-group">
                <label>出生日期 <span class="required">*</span></label>
                <input 
                  v-model="form.maleBirthDate" 
                  type="datetime-local"
                  required
                />
              </div>
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
            <div class="form-row">
              <div class="form-group">
                <label>出生日期 <span class="required">*</span></label>
                <input 
                  v-model="form.femaleBirthDate" 
                  type="datetime-local"
                  required
                />
              </div>
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
          <div class="pricing-info" v-if="normalizedPricing">
            <div class="pricing-row">
              <span>本次消耗：</span>
              <span class="points">{{ pricingDisplayText }}</span>
              <span v-if="normalizedPricing.discount > 0" class="discount">-{{ normalizedPricing.discount }}%</span>
            </div>
            <p v-if="normalizedPricing.reason" class="pricing-reason">{{ normalizedPricing.reason }}</p>
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
      <div class="history-section" v-if="history.length > 0">
        <h3>历史记录</h3>
        <div class="history-list">
          <div 
            v-for="item in history" 
            :key="item.id"
            class="history-item"
            @click="loadHistoryDetail(item)"
          >
            <div class="history-info">
              <span class="history-names">{{ item.male_name }} & {{ item.female_name }}</span>
              <span class="history-date">{{ formatDate(item.created_at) }}</span>
            </div>
            <div class="history-score">{{ item.score }}分</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import DOMPurify from 'dompurify'
import { Male, Female, Unlock, Link, RefreshRight, Document, Collection, Present, Cpu } from '@element-plus/icons-vue'
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

// 表单数据
const form = reactive({
  maleName: '',
  maleBirthDate: '',
  femaleName: '',
  femaleBirthDate: '',
  useAi: true,
})

// 状态
const isLoading = ref(false)
const exporting = ref(false)
const freeResult = ref(null)
const premiumResult = ref(null)
const pricing = ref(null)
const history = ref([])

// 维度名称映射
const dimensionNames = {
  personality: '性格匹配',
  wuxing: '五行互补',
  shengxiao: '生肖配对',
  rizhu: '日主关系',
  liunian: '流年运势',
}

// 表单验证
const isFormValid = computed(() => {
  return form.maleBirthDate && form.femaleBirthDate
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
const pricingDisplayText = computed(() => {
  if (!normalizedPricing.value) {
    return '--'
  }

  return normalizedPricing.value.final > 0 ? `${normalizedPricing.value.final} 积分` : 'VIP 免费'
})

const canExportReport = computed(() => Boolean(premiumResult.value?.id))
const premiumUnlockMessage = computed(() => {
  const points = normalizedPricing.value?.final ?? 80
  if (points <= 0) {
    return form.useAi
      ? '您当前可免费解锁详细报告，并启用 AI 深度分析，是否继续？'
      : '您当前可免费解锁详细报告，是否继续？'
  }

  return form.useAi
    ? `解锁详细报告将消耗 ${points} 积分，并启用 AI 深度分析，是否继续？`
    : `解锁详细报告将消耗 ${points} 积分，是否继续？`
})


// 获取定价信息
const loadPricing = async () => {
  try {
    const response = await getHehunPricing()
    if (response.code === 200) {
      pricing.value = response.data
    }
  } catch (error) {
    console.error('获取定价失败:', error)
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
    const response = await calculateHehun({
      maleName: form.maleName || '男方',
      maleBirthDate: form.maleBirthDate,
      femaleName: form.femaleName || '女方',
      femaleBirthDate: form.femaleBirthDate,
      tier: 'free',
      useAi: false,
    })
    
    if (response.code === 200) {
      freeResult.value = response.data
      loadHistory() // 刷新历史记录
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
    const response = await calculateHehun({
      maleName: form.maleName || '男方',
      maleBirthDate: form.maleBirthDate,
      femaleName: form.femaleName || '女方',
      femaleBirthDate: form.femaleBirthDate,
      tier: 'premium',
      useAi: form.useAi,
    })
    
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
  form.maleName = ''
  form.maleBirthDate = ''
  form.femaleName = ''
  form.femaleBirthDate = ''
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
  try {
    const response = await getHehunHistory({ page: 1, page_size: 5 })
    if (response.code === 200) {
      history.value = response.data.list || []
    }
  } catch (error) {
    console.error('获取历史记录失败:', error)
  }
}

// 安全解析JSON
const safeJsonParse = (jsonStr, defaultVal = null) => {
  if (!jsonStr) return defaultVal
  try {
    return JSON.parse(jsonStr)
  } catch (e) {
    console.warn('JSON解析失败:', jsonStr, e)
    return defaultVal
  }
}

// 加载历史记录详情
const loadHistoryDetail = async (item) => {
  try {
    // 填充表单
    form.maleName = item.male_name
    form.femaleName = item.female_name
    form.maleBirthDate = item.male_birth_date || ''
    form.femaleBirthDate = item.female_birth_date || ''
    // 显示结果 - 使用安全解析，每个字段独立处理
    const hehunData = safeJsonParse(item.result, {})
    const aiAnalysisData = safeJsonParse(item.ai_analysis, null)
    const maleBaziData = safeJsonParse(item.male_bazi, {})
    const femaleBaziData = safeJsonParse(item.female_bazi, {})

    // 检查必要数据是否存在
    if (!hehunData || Object.keys(hehunData).length === 0) {
      ElMessage.warning('合婚结果数据不完整')
      return
    }

    premiumResult.value = {
      id: item.id,
      hehun: hehunData,
      ai_analysis: aiAnalysisData,
      male_bazi: maleBaziData,
      female_bazi: femaleBaziData,
    }

    freeResult.value = null
  } catch (error) {
    console.error('加载历史记录失败:', error)
    ElMessage.error('历史记录数据格式错误，无法加载')
  }
}

// 格式化日期
const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN')
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
  height: 44px;
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
  box-shadow: 0 0 0 3px var(--primary-light-10);
}

.required {
  color: var(--primary-color);
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

.btn-submit {
  width: 100%;
  padding: 16px;
  min-height: 48px;
  background: var(--primary-gradient);
  color: var(--text-primary);
  border: none;
  border-radius: var(--radius-md);
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: var(--shadow-md);
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

.btn-upgrade {
  padding: 14px 32px;
  min-height: 44px;
  background: var(--primary-gradient);
  color: var(--text-primary);
  border: none;
  border-radius: var(--radius-btn);
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s;
  box-shadow: var(--shadow-md);
}

.btn-upgrade:hover {
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
  background: rgba(0, 0, 0, 0.2);
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
  min-height: 44px;
  border-radius: var(--radius-md);
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
  color: var(--text-primary);
  box-shadow: var(--shadow-md);
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

.history-section h3 {
  color: var(--text-primary);
  margin-bottom: 16px;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: var(--bg-card);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all 0.3s;
  border: 1px solid var(--border-light);
}

.history-item:hover {
  background: var(--bg-secondary);
  transform: translateX(4px);
  border-color: var(--primary-light-30);
  box-shadow: var(--shadow-md);
}

.history-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.history-names {
  color: var(--text-primary);
  font-weight: 500;
}

.history-date {
  color: var(--text-tertiary);
  font-size: 13px;
}

.history-score {
  font-size: 20px;
  font-weight: bold;
  color: var(--star-color);
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
  
  .form-card {
    padding: 24px;
  }

  .option-item {
    padding: 14px 16px;
  }
  
  .bazi-compare {
    flex-direction: column;
    gap: 20px;
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
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>
