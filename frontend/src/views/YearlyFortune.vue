<template>
  <div class="yearly-fortune-page">
    <div class="container">
      <PageHeroHeader
        title="2026 流年运势深度解析"
        subtitle="结合个人八字，AI深度解析事业、财富、感情、健康四大运势，提供专属开运建议与每月吉凶提醒。"
        :icon="Calendar"
      />

      <!-- 暖心提示 -->
      <div class="warm-tip card" v-if="!result">
        <el-icon class="tip-icon"><StarFilled /></el-icon>
        <div class="tip-content">
          <p class="tip-title">流年运势解析能帮你了解什么？</p>
          <p class="tip-desc">全年运势总览 · 每月吉凶提醒 · 事业发展建议 · 财运时机把握 · 感情关系指引 · 健康养生提示</p>
        </div>
      </div>

      <!-- 八字表单 -->
      <div class="bazi-form card" v-if="!result">
        <div class="form-group form-group--time" data-bazi-field="birth-time">
          <div class="form-group__header">
            <label>出生日期与时间</label>
          </div>

          <!-- 出生日期时间选择 -->
          <el-date-picker
            v-model="birthDateTime"
            type="datetime"
            placeholder="请选择出生日期和时间"
            format="YYYY-MM-DD HH:mm"
            value-format="YYYY-MM-DD HH:mm"
            class="form-input--datetime"
            size="default"
            :disabled-date="disabledDate"
          />
        </div>

        <!-- 性别选择 -->
        <div class="form-group" data-bazi-field="gender">
          <label>性别</label>
          <div class="gender-options">
            <div
              class="gender-option"
              :class="{ 'gender-option--active': gender === 'male' }"
              @click="gender = 'male'"
            >
              <el-icon><Male /></el-icon>
              <span>男</span>
            </div>
            <div
              class="gender-option"
              :class="{ 'gender-option--active': gender === 'female' }"
              @click="gender = 'female'"
            >
              <el-icon><Female /></el-icon>
              <span>女</span>
            </div>
          </div>
        </div>

        <!-- 提交按钮 -->
        <div class="form-actions">
          <el-button
            type="primary"
            size="default"
            @click="handleCalculate"
            :loading="calculating"
            class="btn-submit"
          >
            <el-icon v-if="!calculating"><MagicStick /></el-icon>
            {{ calculating ? '正在分析...' : '开始解析流年运势' }}
          </el-button>
          <p class="points-notice">消耗 {{ pointsCost }} 积分</p>
        </div>
      </div>

      <!-- 结果展示 -->
      <div class="result-section" v-if="result">
        <div class="result-header card">
          <h2 class="result-title">2026 丙午年流年运势解析</h2>
          <p class="result-subtitle">基于您的八字命盘，AI 深度分析全年运势</p>
        </div>

        <!-- 四大运势 -->
        <div class="fortune-categories">
          <div class="category-card card" v-for="category in fortuneCategories" :key="category.key">
            <div class="category-icon" :class="`category-icon--${category.key}`">
              <el-icon :size="32"><component :is="category.icon" /></el-icon>
            </div>
            <h3 class="category-title">{{ category.title }}</h3>
            <div class="category-score">
              <span class="score-label">综合评分</span>
              <span class="score-value">{{ result[category.key]?.score || 75 }}</span>
              <span class="score-max">/100</span>
            </div>
            <p class="category-desc">{{ result[category.key]?.summary || category.defaultSummary }}</p>
            <ul class="category-tips" v-if="result[category.key]?.tips">
              <li v-for="(tip, index) in result[category.key]?.tips" :key="index">
                {{ tip }}
              </li>
            </ul>
          </div>
        </div>

        <!-- 每月运势 -->
        <div class="monthly-fortune card" v-if="monthlyFortune && monthlyFortune.length > 0">
          <h3 class="section-title">每月运势详情</h3>
          <div class="months-grid">
            <div
              class="month-card"
              v-for="month in monthlyFortune"
              :key="month.month"
              :class="`month-card--${month.level}`"
            >
              <span class="month-number">{{ month.month }}月</span>
              <span class="month-level">{{ month.levelText }}</span>
              <p class="month-desc">{{ month.description }}</p>
            </div>
          </div>
        </div>

        <!-- AI 深度分析 -->
        <div class="ai-analysis card" v-if="aiAnalysis">
          <h3 class="section-title">
            <el-icon><ChatLineRound /></el-icon>
            AI 深度解读
          </h3>
          <div class="analysis-content" v-html="aiAnalysis"></div>
        </div>

        <!-- AI 分析按钮 -->
        <div class="ai-action card" v-if="result && !aiAnalysis">
          <el-button type="primary" @click="getAiAnalysis" :loading="aiLoading" size="default">
            <el-icon><MagicStick /></el-icon>
            获取AI深度解读
          </el-button>
          <p class="ai-tip">消耗 {{ aiPointsCost }} 积分</p>
        </div>

        <!-- 重新计算按钮 -->
        <div class="result-actions">
          <el-button @click="resetForm" size="default">重新测算</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Calendar, MagicStick, Male, Female, StarFilled, ChatLineRound } from '@element-plus/icons-vue'
import { useUserPoints } from '../composables/useUserPoints'
import { getFortunePointsCost, getYearlyFortune } from '../api'
import PageHeroHeader from '../components/PageHeroHeader.vue'

const { userPoints, isLoggedIn, loadUserPoints } = useUserPoints()

// 表单数据
const birthDateTime = ref('')
const gender = ref('male')
const calculating = ref(false)
const result = ref(null)
const monthlyFortune = ref([])
const aiAnalysis = ref('')
const aiLoading = ref(false)
const pointsCost = ref(50)
const aiPointsCost = ref(30)

// 四大运势分类
const fortuneCategories = [
  {
    key: 'career',
    title: '事业运势',
    icon: 'Briefcase',
    defaultSummary: '今年事业运势平稳发展，建议抓住机遇，稳步前进。'
  },
  {
    key: 'wealth',
    title: '财富运势',
    icon: 'Coin',
    defaultSummary: '财运整体尚可，正财稳定，偏财需谨慎。'
  },
  {
    key: 'love',
    title: '感情运势',
    icon: 'Heart',
    defaultSummary: '感情生活有望改善，单身者有机会遇见良缘。'
  },
  {
    key: 'health',
    title: '健康运势',
    icon: 'Operation',
    defaultSummary: '注意劳逸结合，保持良好的作息习惯。'
  }
]

// 禁用未来日期
const disabledDate = (time) => {
  return time.getTime() > Date.now()
}

// 加载积分配置
const loadPointsConfig = async () => {
  try {
    const response = await getFortunePointsCost()
    if (response.code === 200) {
      pointsCost.value = response.data.yearly || 50
      aiPointsCost.value = response.data.ai_analysis || 30
    }
  } catch (error) {
    console.error('加载积分配置失败:', error)
  }
}

// 组件挂载时加载积分和配置
onMounted(() => {
  loadUserPoints()
  loadPointsConfig()
})

// 计算流年运势
const handleCalculate = async () => {
  if (!birthDateTime.value) {
    ElMessage.warning('请选择出生日期和时间')
    return
  }

  // 检查登录状态
  if (!isLoggedIn.value) {
    ElMessage.warning('请先登录')
    return
  }

  // 检查积分
  if (userPoints.value === null) {
    await loadUserPoints()
  }

  if (userPoints.value < pointsCost.value) {
    ElMessage.warning(`积分不足，需要${pointsCost.value}积分`)
    return
  }

  calculating.value = true

  try {
    const response = await getYearlyFortune({
      birthDateTime: birthDateTime.value,
      gender: gender.value
    })

    if (response.code === 200) {
      result.value = response.data.fortune || response.data
      monthlyFortune.value = response.data.monthly || []
      aiAnalysis.value = response.data.aiAnalysis || ''
      
      ElMessage.success('解析成功！')
      
      // 扣除积分（实际应该在API成功后扣除）
      userPoints.value -= pointsCost.value
    } else {
      throw new Error(response.message || '解析失败')
    }
  } catch (error) {
    ElMessage.error('解析失败，请重试')
    console.error(error)
  } finally {
    calculating.value = false
  }
}

// 重置表单
const resetForm = () => {
  birthDateTime.value = ''
  gender.value = 'male'
  result.value = null
  monthlyFortune.value = []
  aiAnalysis.value = ''
}

// 获取AI分析
const getAiAnalysis = async () => {
  if (!result.value) {
    ElMessage.warning('请先计算流年运势')
    return
  }

  // 检查积分
  if (userPoints.value < aiPointsCost.value) {
    ElMessage.warning(`积分不足，需要${aiPointsCost.value}积分`)
    return
  }

  aiLoading.value = true

  try {
    const response = await fetch('/api/ai/analyze', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        type: 'yearly_fortune',
        data: result.value,
        birthDateTime: birthDateTime.value,
        gender: gender.value
      })
    })
    const data = await response.json()

    if (data.code === 200) {
      aiAnalysis.value = data.data.analysis || data.data.content || ''
      // 更新每月运势（如果AI返回了更详细的每月分析）
      if (data.data.monthly) {
        monthlyFortune.value = data.data.monthly
      }
      ElMessage.success('AI分析完成')
      
      // 扣除积分
      userPoints.value -= aiPointsCost.value
    } else {
      throw new Error(data.message || '分析失败')
    }
  } catch (error) {
    ElMessage.error('AI分析失败，请重试')
    console.error(error)
  } finally {
    aiLoading.value = false
  }
}
</script>

<style scoped>
.yearly-fortune-page {
  min-height: 100vh;
  max-width: 960px;
  margin: auto;
  padding: 20px 0;
}

.container {
  max-width: 960px;
  margin: 0 auto;
}

.warm-tip {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 20px;
  margin-bottom: 20px;
  background: linear-gradient(135deg, #fff7e6 0%, #fff0cc 100%);
}

.tip-icon {
  color: #d4af37;
  font-size: 24px;
}

.tip-content .tip-title {
  margin: 0 0 8px 0;
  font-size: 16px;
  font-weight: 600;
  color: #d4af37;
}

.tip-content .tip-desc {
  margin: 0;
  font-size: 14px;
  color: #666;
  line-height: 1.6;
}

.bazi-form {
  padding: 30px;
}

.form-group {
  margin-bottom: 24px;
}

.form-group__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.form-group__header label {
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.form-group__header--time .form-group__status {
  font-size: 14px;
  color: #999;
}

.form-input--datetime {
  width: 100%;
}

.gender-options {
  display: flex;
  gap: 16px;
}

.gender-option {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  border: 2px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 14px;
}

.gender-option--active {
  border-color: #d4af37;
  background: #fff7e6;
}

.form-actions {
  text-align: center;
  margin-top: 24px;
}

.btn-submit {
  width: 100%;
  max-width: 240px;
  padding: 12px 24px;
  font-size: 15px;
}

.points-notice {
  margin-top: 12px;
  font-size: 14px;
  color: #999;
}

.result-section {
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.result-header {
  text-align: center;
  padding: 30px;
  margin-bottom: 24px;
}

.result-title {
  margin: 0 0 8px 0;
  font-size: 28px;
  color: #d4af37;
}

.result-subtitle {
  margin: 0;
  font-size: 16px;
  color: #666;
}

.fortune-categories {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.category-card {
  padding: 28px 24px;
  text-align: center;
  background: linear-gradient(135deg, var(--bg-card), var(--surface-raised));
  border: 2px solid var(--border-light);
  border-radius: 20px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.category-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(var(--primary-rgb), 0.15);
  border-color: rgba(var(--primary-rgb), 0.3);
}

.category-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  color: white;
  font-size: 32px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.category-icon--career {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.category-icon--wealth {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.category-icon--love {
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
}

.category-icon--health {
  background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

.category-title {
  margin: 0 0 16px 0;
  font-size: 22px;
  color: var(--text-primary);
  font-weight: 700;
}

.category-score {
  margin-bottom: 16px;
}

.score-label {
  font-size: 15px;
  color: var(--text-secondary);
  margin-right: 8px;
  font-weight: 500;
}

.score-value {
  font-size: 36px;
  font-weight: bold;
  color: var(--primary-color);
}

.score-max {
  font-size: 18px;
  color: var(--text-secondary);
}

.category-desc {
  margin-bottom: 16px;
  font-size: 14px;
  color: #666;
  line-height: 1.6;
}

.category-tips {
  list-style: none;
  padding: 0;
  margin: 0;
  text-align: left;
}

.category-tips li {
  padding: 8px 0;
  padding-left: 20px;
  position: relative;
  font-size: 14px;
  color: #666;
}

.category-tips li::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #d4af37;
}

.monthly-fortune {
  padding: 32px 28px;
  margin-bottom: 32px;
  background: linear-gradient(135deg, var(--bg-card), var(--surface-raised));
  border: 2px solid var(--border-light);
  border-radius: 20px;
}

.section-title {
  margin: 0 0 24px 0;
  font-size: 24px;
  color: var(--primary-color);
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
}

.months-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.month-card {
  padding: 20px 16px;
  border-radius: 16px;
  text-align: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.month-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
}

.month-card--优 {
  background: linear-gradient(135deg, #e0f2fe, #bae6fd);
  border: 3px solid #0ea5e9;
}

.month-card--良 {
  background: linear-gradient(135deg, #dcfce7, #bbf7d0);
  border: 3px solid #22c55e;
}

.month-card--中 {
  background: linear-gradient(135deg, #fef9c3, #fde047);
  border: 3px solid #eab308;
}

.month-number {
  display: block;
  font-size: 22px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 6px;
}

.month-level {
  display: block;
  font-size: 15px;
  color: var(--text-secondary);
  margin-bottom: 12px;
  font-weight: 600;
}

.month-desc {
  margin: 0;
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.6;
}

.ai-analysis {
  padding: 32px 28px;
  margin-bottom: 32px;
  background: linear-gradient(135deg, var(--bg-card), var(--surface-raised));
  border: 2px solid var(--border-light);
  border-radius: 20px;
}

.analysis-content {
  line-height: 1.9;
  color: var(--text-primary);
  font-size: 15px;
}

.analysis-content p {
  margin-bottom: 20px;
}

.analysis-content strong {
  color: var(--primary-color);
  font-weight: 700;
}

.result-actions {
  text-align: center;
  padding: 24px;
}

.ai-action {
  text-align: center;
  padding: 36px 28px;
  margin-bottom: 24px;
  background: linear-gradient(135deg, var(--bg-card), var(--surface-raised));
  border: 2px solid var(--border-light);
  border-radius: 20px;
}

.ai-action .el-button {
  padding: 18px 48px;
  font-size: 17px;
  font-weight: 600;
}

.ai-tip {
  margin-top: 16px;
  font-size: 15px;
  color: var(--text-secondary);
}
</style>