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
          <div class="form-group__header form-group__header--time">
            <label>出生日期与时间</label>
          </div>

          <!-- 历法类型切换 -->
          <div class="calendar-type-switch">
            <div
              class="calendar-type-btn"
              :class="{ 'calendar-type-btn--active': calendarType === 'solar' }"
              @click="calendarType = 'solar'"
            >
              公历
            </div>
            <div
              class="calendar-type-btn"
              :class="{ 'calendar-type-btn--active': calendarType === 'lunar' }"
              @click="calendarType = 'lunar'"
            >
              农历
            </div>
          </div>

          <!-- 出生日期时间选择 -->
          <el-date-picker
            v-model="birthDateTime"
            type="datetime"
            placeholder="请选择出生日期和时间"
            format="YYYY-MM-DD HH:mm"
            value-format="YYYY-MM-DD HH:mm"
            class="form-input--datetime"
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
            size="large"
            @click="handleCalculate"
            :loading="calculating"
            class="btn-submit"
          >
            <el-icon v-if="!calculating"><MagicStick /></el-icon>
            {{ calculating ? '正在分析...' : '开始解析流年运势' }}
          </el-button>
          <p class="points-notice">消耗 50 积分</p>
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
        <div class="monthly-fortune card">
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
          <el-button type="primary" @click="getAiAnalysis" :loading="aiLoading" size="large">
            <el-icon><MagicStick /></el-icon>
            获取AI深度解读
          </el-button>
          <p class="ai-tip">消耗 {{ aiPointsCost }} 积分</p>
        </div>

        <!-- 重新计算按钮 -->
        <div class="result-actions">
          <el-button @click="resetForm" size="large">重新测算</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { Calendar, MagicStick, Male, Female, StarFilled, ChatLineRound } from '@element-plus/icons-vue'
import { useUserPoints } from '../composables/useUserPoints'
import PageHeroHeader from '../components/PageHeroHeader.vue'

const { userPoints, isLoggedIn } = useUserPoints()

// 表单数据
const birthDateTime = ref('')
const calendarType = ref('solar')
const gender = ref('male')
const calculating = ref(false)
const result = ref(null)
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

// 每月运势
const monthlyFortune = computed(() => {
  const months = []
  const levels = ['优', '良', '中', '良', '优', '中', '良', '优', '中', '良', '优', '良']
  const levelTexts = {
    '优': '运势上佳',
    '良': '运势平稳',
    '中': '需多注意'
  }
  
  for (let i = 1; i <= 12; i++) {
    const level = levels[i - 1]
    months.push({
      month: i,
      level: level,
      levelText: levelTexts[level],
      description: `${i}月运势${level}，${level === '优' ? '宜积极行动' : level === '良' ? '保持现状' : '谨慎行事'}。`
    })
  }
  return months
})

// 禁用未来日期
const disabledDate = (time) => {
  return time.getTime() > Date.now()
}

// 计算流年运势
const handleCalculate = async () => {
  if (!birthDateTime.value) {
    ElMessage.warning('请选择出生日期和时间')
    return
  }

  // 检查积分
  if (userPoints.value < 50) {
    ElMessage.warning('积分不足，请先充值')
    return
  }

  calculating.value = true

  try {
    // TODO: 调用后端API
    // const response = await fetch('/api/yearly-fortune/calculate', {
    //   method: 'POST',
    //   headers: { 'Content-Type': 'application/json' },
    //   body: JSON.stringify({
    //     birthDateTime: birthDateTime.value,
    //     calendarType: calendarType.value,
    //     gender: gender.value
    //   })
    // })
    // const data = await response.json()
    
    // 模拟结果
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    result.value = {
      career: {
        score: 82,
        summary: '今年事业运势强劲，有升职加薪的机会。建议大胆展现自己的能力，主动争取项目。',
        tips: ['3-5月是事业发展黄金期', '9月注意人际关系', '年底有望获得晋升机会']
      },
      wealth: {
        score: 78,
        summary: '正财稳定，偏财运也不错。可以考虑投资理财，但要控制风险。',
        tips: ['6月和10月财运较旺', '避免高风险投资', '注意开源节流']
      },
      love: {
        score: 75,
        summary: '感情运势总体平稳，单身者有机会遇到心仪对象。有伴侣者要注意沟通。',
        tips: ['情人节前后桃花运旺', '多参加社交活动', '维护好现有关系']
      },
      health: {
        score: 70,
        summary: '要注意休息，避免过度劳累。建议定期体检，关注身体状况。',
        tips: ['春季注意感冒', '夏季防暑降温', '冬季保暖防寒']
      }
    }

    aiAnalysis.value = `
      <p>根据您的八字命盘分析，2026丙午年是火旺之年，对您来说是一个充满机遇的年份。</p>
      <p><strong>整体运势：</strong>火元素旺盛，意味着热情、活力和创造力。这个年份特别适合追求目标、展现才华。</p>
      <p><strong>注意事项：</strong>火旺可能导致情绪波动，建议多运动、多接触自然，保持情绪平衡。</p>
      <p><strong>开运建议：</strong>今年宜多穿红色、紫色等暖色调服饰，佩戴红玛瑙等饰品，有助于增强运势。</p>
    `

    ElMessage.success('解析成功！')
    
    // 扣除积分（实际应该在API成功后扣除）
    userPoints.value -= 50
    
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
  calendarType.value = 'solar'
  gender.value = 'male'
  result.value = null
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
        data: result.value
      })
    })
    const data = await response.json()

    if (data.code === 200) {
      aiAnalysis.value = data.data.analysis || data.data.content || ''
      ElMessage.success('AI分析完成')
      
      // 扣除积分
      userStore.points -= aiPointsCost.value
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
  padding: 20px 0;
}

.container {
  max-width: 1200px;
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

.calendar-type-switch {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.calendar-type-btn {
  flex: 1;
  text-align: center;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 14px;
}

.calendar-type-btn--active {
  background: #d4af37;
  color: white;
  border-color: #d4af37;
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
  padding: 16px;
  border: 2px solid #ddd;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 16px;
}

.gender-option--active {
  border-color: #d4af37;
  background: #fff7e6;
}

.form-actions {
  text-align: center;
  margin-top: 32px;
}

.btn-submit {
  width: 100%;
  max-width: 300px;
  padding: 16px 32px;
  font-size: 16px;
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