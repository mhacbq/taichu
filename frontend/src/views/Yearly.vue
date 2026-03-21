<template>
  <div class="yearly-page">
    <div class="container">
      <PageHeroHeader
        title="流年运势"
        subtitle="洞察全年运势起伏，把握人生关键时机"
        :icon="Calendar"
      />

      <!-- 运势输入表单 -->
      <div class="yearly-form card card-hover">
        <h3>
          <el-icon><MagicStick /></el-icon>
          输入出生信息
        </h3>
        <el-form :model="form" label-width="100px">
          <el-form-item label="出生日期">
            <el-date-picker
              v-model="form.birthDate"
              type="date"
              placeholder="选择出生日期"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              :disabled-date="disabledDate"
            />
          </el-form-item>
          <el-form-item label="出生时间">
            <el-select v-model="form.birthHour" placeholder="选择出生时辰">
              <el-option label="子时 (23:00-01:00)" value="23" />
              <el-option label="丑时 (01:00-03:00)" value="1" />
              <el-option label="寅时 (03:00-05:00)" value="3" />
              <el-option label="卯时 (05:00-07:00)" value="5" />
              <el-option label="辰时 (07:00-09:00)" value="7" />
              <el-option label="巳时 (09:00-11:00)" value="9" />
              <el-option label="午时 (11:00-13:00)" value="11" />
              <el-option label="未时 (13:00-15:00)" value="13" />
              <el-option label="申时 (15:00-17:00)" value="15" />
              <el-option label="酉时 (17:00-19:00)" value="17" />
              <el-option label="戌时 (19:00-21:00)" value="19" />
              <el-option label="亥时 (21:00-23:00)" value="21" />
            </el-select>
          </el-form-item>
          <el-form-item label="性别">
            <el-radio-group v-model="form.gender">
              <el-radio label="male">男</el-radio>
              <el-radio label="female">女</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="流年年份">
            <el-date-picker
              v-model="form.year"
              type="year"
              placeholder="选择年份"
              format="YYYY"
              value-format="YYYY"
            />
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="calculateYearly" :loading="loading">
              <el-icon><MagicStick /></el-icon>
              开始分析
            </el-button>
          </el-form-item>
        </el-form>

        <!-- 积分消耗提示 -->
        <div v-if="pointsCost > 0" class="points-tip">
          <el-icon><Warning /></el-icon>
          消耗 {{ pointsCost }} 积分
        </div>
      </div>

      <!-- 运势结果 -->
      <AsyncState :status="status" :error="errorMessage" loadingText="正在为您推算流年运势..." @retry="calculateYearly">
        <template #loading>
          <div class="loading-state card card-hover">
            <el-skeleton :rows="15" animated />
          </div>
        </template>

        <div v-if="result" class="yearly-result">
          <!-- 整体运势 -->
          <div class="result-section card">
            <h3>
              <el-icon><Star /></el-icon>
              整体运势
            </h3>
            <div class="overall-fortune">
              <div class="score-circle" :class="getScoreClass(result.overall)">
                <div class="score-value">{{ result.overall }}</div>
                <div class="score-label">综合评分</div>
              </div>
              <div class="overall-summary">
                <p>{{ result.summary || '流年运势平稳，建议稳扎稳打' }}</p>
              </div>
            </div>
          </div>

          <!-- 各方面运势 -->
          <div class="aspects-grid">
            <div class="aspect-card card" v-for="aspect in result.aspects" :key="aspect.name">
              <div class="aspect-header">
                <span class="aspect-name">{{ aspect.name }}</span>
                <div class="aspect-stars">
                  <el-icon v-for="n in 5" :key="n" class="star" :class="{ filled: n <= aspect.score }">
                    <StarFilled />
                  </el-icon>
                </div>
              </div>
              <div class="aspect-content">
                <p>{{ aspect.description }}</p>
              </div>
            </div>
          </div>

          <!-- 月度运势趋势 -->
          <div class="trend-section card">
            <h3>
              <el-icon><TrendCharts /></el-icon>
              月度运势趋势
            </h3>
            <div class="trend-chart">
              <div class="trend-bar" v-for="(month, index) in result.monthlyTrend" :key="index">
                <div class="trend-label">{{ index + 1 }}月</div>
                <div class="trend-value" :class="getScoreClass(month)" :style="{ height: `${month}%` }">
                  {{ month }}
                </div>
              </div>
            </div>
          </div>

          <!-- AI深度分析 -->
          <div class="ai-analysis card">
            <h3>
              <el-icon><MagicStick /></el-icon>
              AI深度分析
            </h3>
            <div v-if="aiAnalysis" class="ai-content">
              <p>{{ aiAnalysis }}</p>
            </div>
            <div v-else class="ai-placeholder">
              <el-button type="primary" @click="getAiAnalysis" :loading="aiLoading">
                <el-icon><MagicStick /></el-icon>
                获取AI深度解读
              </el-button>
              <p class="tip">消耗 {{ aiPointsCost }} 积分</p>
            </div>
          </div>

          <!-- 运势建议 -->
          <div class="advice-section card">
            <h3>
              <el-icon><Guide /></el-icon>
              流年建议
            </h3>
            <div class="advice-list">
              <div class="advice-item" v-for="(advice, index) in result.advices" :key="index">
                <el-icon class="advice-icon"><CircleCheck /></el-icon>
                <p>{{ advice }}</p>
              </div>
            </div>
          </div>
        </div>
      </AsyncState>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Calendar, MagicStick, Star, StarFilled, TrendCharts, Guide, CircleCheck, Warning } from '@element-plus/icons-vue'
import PageHeroHeader from '@/components/PageHeroHeader.vue'
import AsyncState from '@/components/AsyncState.vue'
import { request } from '@/utils/request'

const form = reactive({
  birthDate: '',
  birthHour: '',
  gender: 'male',
  year: new Date().getFullYear().toString()
})

const loading = ref(false)
const status = ref('idle')
const errorMessage = ref('')
const result = ref(null)
const aiAnalysis = ref('')
const aiLoading = ref(false)
const pointsCost = ref(0)
const aiPointsCost = ref(0)

const disabledDate = (time: Date) => {
  return time.getTime() > Date.now()
}

const getScoreClass = (score: number) => {
  if (score >= 80) return 'excellent'
  if (score >= 60) return 'good'
  if (score >= 40) return 'average'
  return 'poor'
}

const calculateYearly = async () => {
  if (!form.birthDate || !form.birthHour || !form.year) {
    ElMessage.warning('请填写完整的出生信息')
    return
  }

  loading.value = true
  status.value = 'loading'
  errorMessage.value = ''

  try {
    const response = await request.post('/api/fortune/yearly', {
      birth_date: form.birthDate,
      birth_hour: parseInt(form.birthHour),
      gender: form.gender,
      year: parseInt(form.year)
    })

    if (response.code === 200) {
      result.value = response.data
      status.value = 'ready'
      ElMessage.success('流年运势计算成功')
    } else {
      throw new Error(response.message || '计算失败')
    }
  } catch (error: any) {
    status.value = 'error'
    errorMessage.value = error.message || '计算失败，请重试'
    ElMessage.error(errorMessage.value)
  } finally {
    loading.value = false
  }
}

const getAiAnalysis = async () => {
  if (!result.value) {
    ElMessage.warning('请先计算流年运势')
    return
  }

  aiLoading.value = true

  try {
    const response = await request.post('/api/ai/analyze', {
      type: 'yearly_fortune',
      data: result.value
    })

    if (response.code === 200) {
      aiAnalysis.value = response.data.analysis
      ElMessage.success('AI分析完成')
    } else {
      throw new Error(response.message || '分析失败')
    }
  } catch (error: any) {
    ElMessage.error(error.message || '分析失败，请重试')
  } finally {
    aiLoading.value = false
  }
}

const loadPointsCost = async () => {
  try {
    const response = await request.get('/api/fortune/points-cost', {
      params: { type: 'yearly' }
    })

    if (response.code === 200) {
      pointsCost.value = response.data.points_cost || 0
      aiPointsCost.value = response.data.ai_points_cost || 0
    }
  } catch (error) {
    console.error('Failed to load points cost:', error)
  }
}

onMounted(() => {
  loadPointsCost()
})
</script>

<style scoped>
.yearly-page {
  padding: 20px 0;
  min-height: 100vh;
}

.yearly-form {
  margin-bottom: 20px;
}

.yearly-form h3 {
  margin-bottom: 20px;
  color: #D4AF37;
  display: flex;
  align-items: center;
  gap: 10px;
}

.points-tip {
  margin-top: 15px;
  padding: 10px;
  background: rgba(212, 175, 55, 0.1);
  border-left: 3px solid #D4AF37;
  border-radius: 4px;
  color: #D4AF37;
  display: flex;
  align-items: center;
  gap: 8px;
}

.loading-state {
  min-height: 400px;
  padding: 30px;
}

.result-section {
  margin-bottom: 20px;
}

.result-section h3 {
  margin-bottom: 20px;
  color: #D4AF37;
  display: flex;
  align-items: center;
  gap: 10px;
}

.overall-fortune {
  display: flex;
  align-items: center;
  gap: 30px;
  padding: 20px;
}

.score-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 4px solid #D4AF37;
  flex-shrink: 0;
}

.score-circle.excellent {
  border-color: #67C23A;
  background: rgba(103, 194, 58, 0.1);
}

.score-circle.good {
  border-color: #409EFF;
  background: rgba(64, 158, 255, 0.1);
}

.score-circle.average {
  border-color: #E6A23C;
  background: rgba(230, 162, 60, 0.1);
}

.score-circle.poor {
  border-color: #F56C6C;
  background: rgba(245, 108, 108, 0.1);
}

.score-value {
  font-size: 36px;
  font-weight: bold;
  color: #D4AF37;
}

.score-label {
  font-size: 14px;
  color: #666;
}

.overall-summary {
  flex: 1;
}

.overall-summary p {
  font-size: 16px;
  line-height: 1.8;
  color: #333;
}

.aspects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.aspect-card {
  padding: 20px;
}

.aspect-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.aspect-name {
  font-size: 18px;
  font-weight: bold;
  color: #D4AF37;
}

.aspect-stars {
  display: flex;
  gap: 5px;
}

.aspect-stars .star {
  color: #ddd;
}

.aspect-stars .star.filled {
  color: #D4AF37;
}

.aspect-content p {
  font-size: 14px;
  line-height: 1.6;
  color: #666;
}

.trend-section {
  margin-bottom: 20px;
}

.trend-section h3 {
  margin-bottom: 20px;
  color: #D4AF37;
  display: flex;
  align-items: center;
  gap: 10px;
}

.trend-chart {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  height: 200px;
  padding: 20px 0;
  gap: 5px;
}

.trend-bar {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
}

.trend-label {
  font-size: 12px;
  color: #999;
}

.trend-value {
  width: 100%;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding-bottom: 5px;
  font-size: 12px;
  font-weight: bold;
  color: #fff;
  border-radius: 4px 4px 0 0;
  min-height: 20px;
  transition: height 0.3s ease;
}

.trend-value.excellent {
  background: linear-gradient(to top, #67C23A, #95D475);
}

.trend-value.good {
  background: linear-gradient(to top, #409EFF, #79BBFF);
}

.trend-value.average {
  background: linear-gradient(to top, #E6A23C, #F3D19E);
}

.trend-value.poor {
  background: linear-gradient(to top, #F56C6C, #FAB6B6);
}

.ai-analysis {
  margin-bottom: 20px;
}

.ai-analysis h3 {
  margin-bottom: 20px;
  color: #D4AF37;
  display: flex;
  align-items: center;
  gap: 10px;
}

.ai-content {
  padding: 20px;
  background: rgba(212, 175, 55, 0.05);
  border-radius: 8px;
  line-height: 1.8;
  color: #333;
}

.ai-placeholder {
  text-align: center;
  padding: 40px 20px;
}

.ai-placeholder .tip {
  margin-top: 15px;
  color: #999;
  font-size: 14px;
}

.advice-section {
  margin-bottom: 20px;
}

.advice-section h3 {
  margin-bottom: 20px;
  color: #D4AF37;
  display: flex;
  align-items: center;
  gap: 10px;
}

.advice-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.advice-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 15px;
  background: rgba(212, 175, 55, 0.05);
  border-radius: 8px;
}

.advice-icon {
  color: #D4AF37;
  font-size: 20px;
  flex-shrink: 0;
  margin-top: 2px;
}

.advice-item p {
  margin: 0;
  font-size: 15px;
  line-height: 1.6;
  color: #333;
}
</style>
