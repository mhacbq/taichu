<template>
  <div class="hehun-page">
    <div class="container">
      <!-- 页面标题 -->
      <div class="page-header">
        <h1 class="page-title">
          <el-icon class="title-icon" :size="36"><Link /></el-icon>
          八字合婚
        </h1>
        <p class="page-subtitle">通过双方八字，分析婚姻匹配度与缘分</p>
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
            <h4><el-icon><Lightbulb /></el-icon> 建议</h4>
            <p>{{ freeResult.hehun.suggestions[0] }}</p>
          </div>
          
          <div class="upgrade-prompt" v-if="!isLoading">
            <p>{{ freeResult.preview_hint }}</p>
            <button class="btn-upgrade" @click="unlockPremium">
              <el-icon><Unlock /></el-icon>
              解锁详细报告
              <span class="points-tag">{{ pricing.final }}积分</span>
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
            <button class="btn-primary" @click="exportReport" :disabled="exporting">
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
            <label class="option-item">
              <input type="checkbox" v-model="form.useAi" />
              <span>使用AI深度分析（更准确）</span>
            </label>
          </div>
          
          <!-- 定价信息 -->
          <div class="pricing-info" v-if="pricing">
            <div class="pricing-row">
              <span>本次消耗：</span>
              <span class="points">{{ pricing.final }} 积分</span>
              <span v-if="pricing.discount > 0" class="discount">-{{ pricing.discount }}%</span>
            </div>
            <p v-if="pricing.reason" class="pricing-reason">{{ pricing.reason }}</p>
          </div>
          
          <!-- 提交按钮 -->
          <button 
            class="btn-submit" 
            @click="submitForm"
            :disabled="isLoading || !isFormValid"
          >
            <span v-if="isLoading" class="loading"></span>
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
import { Male, Female, Unlock, Link, RefreshRight, Document, Collection, Present, Cpu, Lightbulb } from '@element-plus/icons-vue'
import { getHehunPricing, calculateHehun, getHehunHistory, exportHehunReport } from '../api'

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
      `解锁详细报告将消耗 ${pricing.value?.final || 80} 积分，是否继续？`,
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
  if (!premiumResult.value) return
  
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
  text-align: center;
  margin-bottom: 40px;
}

.page-title {
  font-size: 36px;
  color: var(--text-primary);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.title-icon {
  font-size: 42px;
}

.page-subtitle {
  color: var(--text-secondary);
  font-size: 16px;
}

/* 表单样式 */
.form-card {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 40px;
  border: 1px solid var(--border-color);
}

.form-card h2 {
  color: var(--text-primary);
  text-align: center;
  margin-bottom: 30px;
}

.person-section {
  margin-bottom: 30px;
  padding: 24px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 16px;
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
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  color: var(--text-primary);
  font-size: 15px;
  transition: all 0.3s;
}

.form-group input:focus {
  outline: none;
  border-color: var(--primary-color, #B8860B);
}

.required {
  color: var(--primary-color, #B8860B);
}

.options-section {
  margin: 20px 0;
}

.option-item {
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgba(255, 255, 255, 0.8);
  cursor: pointer;
}

.option-item input {
  width: 18px;
  height: 18px;
  accent-color: var(--primary-color, #B8860B);
}

.pricing-info {
  text-align: center;
  padding: 16px;
  background: rgba(184, 134, 11, 0.1);
  border-radius: 12px;
  margin: 20px 0;
}

.pricing-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #fff;
}

.pricing-row .points {
  font-size: 24px;
  font-weight: bold;
  color: #ffd700;
}

.discount {
  background: var(--primary-color, #B8860B);
  color: #fff;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
}

.pricing-reason {
  color: rgba(255, 255, 255, 0.6);
  font-size: 13px;
  margin-top: 8px;
}

.btn-submit {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #B8860B, #D4AF37);
  color: #fff;
  border: none;
  border-radius: 12px;
  font-size: 16px;
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
  box-shadow: 0 10px 30px rgba(184, 134, 11, 0.4);
}

.loading {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
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
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 32px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.result-card.premium {
  border-color: rgba(184, 134, 11, 0.3);
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.result-header h2 {
  color: #fff;
  margin: 0;
}

.premium-badge {
  background: linear-gradient(135deg, #ffd700, #ffb700);
  color: #000;
  padding: 6px 16px;
  border-radius: 20px;
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
  color: #ffd700;
  line-height: 1;
}

.score-label {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

.result-level {
  text-align: center;
  padding: 12px 24px;
  border-radius: 30px;
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 20px;
}

.result-level.excellent {
  background: rgba(82, 196, 26, 0.2);
  color: #52c41a;
}

.result-level.good {
  background: rgba(24, 144, 255, 0.2);
  color: #1890ff;
}

.result-level.average {
  background: rgba(250, 173, 20, 0.2);
  color: #faad14;
}

.result-level.poor {
  background: rgba(255, 77, 79, 0.2);
  color: #ff4d4f;
}

.result-comment {
  color: rgba(255, 255, 255, 0.8);
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
  background: var(--bg-secondary);
  border-radius: 16px;
  margin-bottom: 24px;
}

.bazi-side {
  text-align: center;
}

.bazi-side h4 {
  color: rgba(255, 255, 255, 0.6);
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
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: #fff;
  font-weight: 500;
}

.day-master {
  color: var(--primary-color, #B8860B);
  font-size: 13px;
}

.bazi-divider {
  font-size: 32px;
}

/* 建议框 */
.suggestion-box {
  background: rgba(103, 194, 58, 0.1);
  border-left: 4px solid #67c23a;
  padding: 16px 20px;
  border-radius: 0 12px 12px 0;
  margin-bottom: 24px;
}

.suggestion-box h4 {
  color: #67c23a;
  margin-bottom: 8px;
}

.suggestion-box p {
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
  line-height: 1.6;
}

/* 升级提示 */
.upgrade-prompt {
  text-align: center;
  padding: 24px;
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.1), rgba(212, 175, 55, 0.1));
  border-radius: 16px;
  border: 1px dashed rgba(184, 134, 11, 0.3);
}

.upgrade-prompt p {
  color: var(--text-secondary);
  margin-bottom: 16px;
}

.btn-upgrade {
  padding: 14px 32px;
  background: linear-gradient(135deg, #B8860B, #D4AF37);
  color: #fff;
  border: none;
  border-radius: 30px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s;
}

.btn-upgrade:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(184, 134, 11, 0.4);
}

.points-tag {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
}

/* 详细报告样式 */
.score-section {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 40px;
  padding: 24px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 16px;
  margin-bottom: 24px;
}

.main-score {
  text-align: center;
  padding: 20px;
  border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.main-score .score-number {
  font-size: 64px;
}

.main-score .score-level {
  display: inline-block;
  padding: 8px 20px;
  border-radius: 20px;
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
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
}

.dim-bar {
  flex: 1;
  height: 8px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  overflow: hidden;
}

.dim-fill {
  height: 100%;
  background: linear-gradient(90deg, #B8860B, #D4AF37);
  border-radius: 4px;
  transition: width 0.5s ease;
}

.dim-score {
  width: 50px;
  text-align: right;
  color: #fff;
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
  color: #fff;
  margin-bottom: 16px;
  font-size: 18px;
}

.analysis-content,
.ai-content {
  color: rgba(255, 255, 255, 0.8);
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
  border-bottom: 1px solid var(--border-color);
}

.solution-list li:before {
  content: '•';
  position: absolute;
  left: 0;
  color: var(--primary-color);
  font-weight: bold;
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
  padding: 14px 32px;
  border-radius: 30px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #B8860B, #D4AF37);
  color: #fff;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(184, 134, 11, 0.4);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* 历史记录 */
.history-section {
  margin-top: 40px;
}

.history-section h3 {
  color: #fff;
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
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
}

.history-item:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateX(4px);
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
  color: rgba(255, 255, 255, 0.5);
  font-size: 13px;
}

.history-score {
  font-size: 20px;
  font-weight: bold;
  color: #ffd700;
}

/* 响应式 */
@media (max-width: 768px) {
  .page-title {
    font-size: 28px;
  }
  
  .form-card {
    padding: 24px;
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
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 24px;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>
