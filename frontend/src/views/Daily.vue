<template>
  <div class="daily-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">每日运势</h1>
      </div>

      <!-- 签到卡片 -->
      <CheckinCard />

      <div class="date-display card card-hover">
        <div class="lunar-date">
          <span class="label">农历</span>
          <span class="value">{{ lunarDate }}</span>
        </div>
        <div class="solar-date">
          <span class="label">公历</span>
          <span class="value">{{ solarDate }}</span>
        </div>
      </div>

      <div v-if="fortune" class="fortune-content">
        <!-- 个性化运势卡片 -->
        <div v-if="fortune.personalized && fortune.personalized.hasBazi" class="personalized-fortune card card-hover">
          <h2>
            <el-icon><MagicStick /></el-icon> 您的专属运势
            <el-tooltip content="基于您的八字日主计算的个性化运势分析" placement="top">
              <el-icon class="help-icon"><QuestionFilled /></el-icon>
            </el-tooltip>
          </h2>
          <div class="personal-content">
            <div class="master-info">
              <div class="master-card">
                <span class="label">您的日主</span>
                <span class="value">{{ fortune.personalized.dayMaster }}</span>
                <span class="wuxing-badge" :class="fortune.personalized.dayMasterWuxing">{{ fortune.personalized.dayMasterWuxing }}</span>
              </div>
              <div class="relation-arrow">
                <el-icon><Right /></el-icon>
              </div>
              <div class="today-card">
                <span class="label">今日干支</span>
                <span class="value">{{ fortune.personalized.todayGanZhi }}</span>
                <span class="wuxing-text">{{ fortune.personalized.todayWuxing }}</span>
              </div>
            </div>
            
            <div class="luck-indicator">
              <div class="luck-badge" :class="fortune.personalized.luckLevel">
                {{ fortune.personalized.relation }}
                <span class="luck-level">{{ fortune.personalized.luckLevel }}</span>
              </div>
              <div class="personal-score">
                <span class="score-label">综合评分</span>
                <span class="score-value" :class="getScoreClass(fortune.personalized.personalScore)">
                  {{ fortune.personalized.personalScore }}
                </span>
              </div>
            </div>
            
            <div class="personal-advice">
              <h4><el-icon><StarFilled /></el-icon> 今日建议</h4>
              <p>{{ fortune.personalized.advice }}</p>
            </div>
            
            <div class="personal-lucky-grid">
              <div class="personal-lucky-item card-hover">
                <span class="personal-lucky-label">幸运色</span>
                <div class="personal-lucky-values">
                  <span v-for="color in fortune.personalized.luckyColors" :key="color" class="lucky-tag color">
                    {{ color }}
                  </span>
                </div>
              </div>
              <div class="personal-lucky-item card-hover">
                <span class="personal-lucky-label">幸运方位</span>
                <div class="personal-lucky-values">
                  <span v-for="dir in fortune.personalized.luckyDirections" :key="dir" class="lucky-tag direction">
                    {{ dir }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>
        
        <!-- 无八字时的提示 -->
        <div v-else class="no-bazi-hint card card-hover">
          <div class="hint-content">
            <el-icon class="hint-icon" :size="48"><Collection /></el-icon>
            <p>进行八字排盘即可获取您的个性化每日运势</p>
            <router-link to="/bazi">
              <el-button type="primary" size="small">去排盘</el-button>
            </router-link>
          </div>
        </div>

        <div class="overall-score card card-hover">
          <h2>今日综合运势</h2>
          <div class="score-display">
            <div class="score-circle">
              <span class="score-number">{{ fortune.overallScore }}</span>
              <span class="score-label">分</span>
            </div>
            <div class="score-stars">
              <el-icon v-for="n in 5" :key="n" class="star" :class="{ filled: n <= Math.round(fortune.overallScore / 20) }">
                <StarFilled />
              </el-icon>
            </div>
          </div>
          <p class="fortune-summary">{{ fortune.summary }}</p>
        </div>

        <div class="aspect-grid">
          <div class="aspect-card card card-hover" v-for="aspect in fortune.aspects" :key="aspect.name">
            <div class="aspect-icon">{{ aspect.icon }}</div>
            <h3>{{ aspect.name }}</h3>
            <div class="aspect-score">
              <el-progress :percentage="aspect.score" :color="getScoreColor(aspect.score)" />
            </div>
            <p class="aspect-desc">{{ aspect.description }}</p>
          </div>
        </div>

        <div class="lucky-section card card-hover">
          <h3>今日宜忌</h3>
          <div class="lucky-grid">
            <div class="lucky-item good">
              <span class="lucky-label">宜</span>
              <div class="lucky-tags">
                <el-tag v-for="item in fortune.yi" :key="item" type="success">{{ item }}</el-tag>
              </div>
            </div>
            <div class="lucky-item bad">
              <span class="lucky-label">忌</span>
              <div class="lucky-tags">
                <el-tag v-for="item in fortune.ji" :key="item" type="danger">{{ item }}</el-tag>
              </div>
            </div>
          </div>
        </div>

        <div class="details-section card card-hover">
          <h3>详细运势</h3>
          <el-collapse v-model="activeNames">
            <el-collapse-item title="事业运势" name="career">
              <p>{{ fortune.details.career }}</p>
            </el-collapse-item>
            <el-collapse-item title="财运运势" name="wealth">
              <p>{{ fortune.details.wealth }}</p>
            </el-collapse-item>
            <el-collapse-item title="感情运势" name="love">
              <p>{{ fortune.details.love }}</p>
            </el-collapse-item>
            <el-collapse-item title="健康运势" name="health">
              <p>{{ fortune.details.health }}</p>
            </el-collapse-item>
          </el-collapse>
        </div>
      </div>

      <!-- 错误状态 -->
      <div v-else-if="error" class="error-state card card-hover">
        <el-icon class="error-icon" :size="48"><WarningFilled /></el-icon>
        <p class="error-message">{{ errorMessage }}</p>
        <el-button type="primary" @click="loadDailyFortune">重新加载</el-button>
      </div>

      <div v-else class="loading-state card card-hover">
        <el-skeleton :rows="10" animated />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { MagicStick, QuestionFilled, Collection, WarningFilled, StarFilled, Right } from '@element-plus/icons-vue'
import { getDailyFortune } from '../api'
import CheckinCard from '../components/CheckinCard.vue'
import BackButton from '../components/BackButton.vue'

const solarDate = ref('')
const lunarDate = ref('')
const fortune = ref(null)
const activeNames = ref(['career', 'wealth'])
const error = ref(false)
const errorMessage = ref('')

const getScoreColor = (score) => {
  if (score >= 80) return '#67c23a'
  if (score >= 60) return '#e6a23c'
  return '#f56c6c'
}

const getScoreClass = (score) => {
  if (score >= 80) return 'excellent'
  if (score >= 60) return 'good'
  return 'normal'
}

const loadDailyFortune = async () => {
  error.value = false
  errorMessage.value = ''
  try {
    const response = await getDailyFortune()
    if (response.code === 200) {
      fortune.value = response.data.fortune
      solarDate.value = response.data.solarDate
      lunarDate.value = response.data.lunarDate
      error.value = false
    } else {
      error.value = true
      errorMessage.value = response.message || '获取运势失败'
      ElMessage.error(errorMessage.value)
    }
  } catch (err) {
    error.value = true
    errorMessage.value = '网络错误，请稍后重试'
    ElMessage.error(errorMessage.value)
    console.error(err)
  }
}

onMounted(() => {
  loadDailyFortune()
})
</script>

<style scoped>
.daily-page {
  padding: 60px 0;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 30px;
}

.page-header .section-title {
  margin: 0;
}

.date-display {
  max-width: 600px;
  margin: 0 auto 30px;
  display: flex;
  justify-content: space-around;
  padding: 20px;
  background: var(--bg-card);
  border-radius: var(--radius-card);
}

.lunar-date,
.solar-date {
  text-align: center;
}

.label {
  display: block;
  font-size: 14px;
  color: var(--text-tertiary);
  margin-bottom: 5px;
}

.value {
  font-size: 20px;
  color: var(--text-primary);
  font-weight: 500;
}

.fortune-content {
  max-width: 800px;
  margin: 0 auto;
}

.overall-score {
  text-align: center;
  margin-bottom: 30px;
}

.overall-score h2 {
  color: var(--text-primary);
  margin-bottom: 30px;
}

.score-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 20px;
}

.score-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: var(--primary-gradient);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  margin-bottom: 15px;
}

.score-number {
  font-size: 48px;
  font-weight: bold;
  color: var(--text-primary);
}

@media (max-width: 768px) {
  .score-number {
    font-size: 32px;
  }
}

.score-label {
  font-size: 14px;
  color: var(--text-secondary);
}

.score-stars {
  display: flex;
  gap: 5px;
}

.star {
  font-size: 20px;
  color: var(--text-tertiary);
}

.star.filled {
  color: var(--star-color);
}

.fortune-summary {
  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.6;
}

.aspect-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

.aspect-card {
  text-align: center;
}

.aspect-icon {
  font-size: 36px;
  margin-bottom: 10px;
}

.aspect-card h3 {
  color: var(--text-primary);
  margin-bottom: 15px;
  font-size: 18px;
}

.aspect-score {
  margin-bottom: 15px;
}

.aspect-desc {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

.lucky-section {
  margin-bottom: 30px;
}

.lucky-section h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
  text-align: center;
}

.lucky-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
}

.lucky-item {
  display: flex;
  align-items: flex-start;
  gap: 15px;
}

.lucky-label {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: bold;
  flex-shrink: 0;
}

.lucky-item.good .lucky-label {
  background: var(--success-color);
  color: var(--text-primary);
}

.lucky-item.bad .lucky-label {
  background: var(--danger-color);
  color: var(--text-primary);
}

.lucky-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.details-section h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
  text-align: center;
}

.details-section p {
  color: var(--text-secondary);
  line-height: 1.8;
}

.loading-state {
  max-width: 800px;
  margin: 0 auto;
}

/* 错误状态样式 */
.error-state {
  max-width: 600px;
  margin: 50px auto;
  text-align: center;
  padding: 50px 30px;
}

.error-icon {
  color: var(--danger-color);
  margin-bottom: 20px;
}

.error-message {
  color: var(--text-secondary);
  font-size: 16px;
  margin-bottom: 25px;
}

/* 个性化运势样式 */
.personalized-fortune {
  margin-bottom: 30px;
  background: linear-gradient(135deg, var(--primary-light-10), var(--primary-light-05));
  border: 1px solid var(--primary-light-30);
}

.personalized-fortune h2 {
  color: var(--text-primary);
  text-align: center;
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.help-icon {
  cursor: help;
  opacity: 0.7;
  font-size: 16px;
}

.master-info {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.master-card, .today-card {
  background: var(--bg-tertiary);
  border-radius: 16px;
  padding: 20px 30px;
  text-align: center;
  min-width: 120px;
}

.master-card .label, .today-card .label {
  display: block;
  font-size: 12px;
  color: var(--text-tertiary);
  margin-bottom: 8px;
}

.master-card .value, .today-card .value {
  display: block;
  font-size: 32px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.wuxing-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 12px;
}

.wuxing-badge.金 { background: var(--primary-light-15); color: var(--wuxing-jin); }
.wuxing-badge.木 { background: rgba(34, 139, 34, 0.15); color: var(--wuxing-mu); }
.wuxing-badge.水 { background: rgba(30, 144, 255, 0.15); color: var(--wuxing-shui); }
.wuxing-badge.火 { background: rgba(255, 69, 0, 0.15); color: var(--wuxing-huo); }
.wuxing-badge.土 { background: rgba(139, 69, 19, 0.15); color: var(--wuxing-tu); }

.wuxing-text {
  font-size: 12px;
  color: var(--text-tertiary);
}

.relation-arrow {
  font-size: 24px;
  color: var(--text-muted);
}

.luck-indicator {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 30px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.luck-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 15px 30px;
  border-radius: 16px;
  font-size: 20px;
  font-weight: bold;
  color: var(--text-primary);
}

.luck-badge.吉 {
  background: linear-gradient(135deg, var(--success-light), rgba(103, 194, 58, 0.1));
  border: 1px solid rgba(103, 194, 58, 0.4);
}

.luck-badge.凶 {
  background: linear-gradient(135deg, var(--danger-light), rgba(245, 108, 108, 0.1));
  border: 1px solid rgba(245, 108, 108, 0.4);
}

.luck-badge.平 {
  background: linear-gradient(135deg, rgba(144, 147, 153, 0.1), rgba(144, 147, 153, 0.05));
  border: 1px solid rgba(144, 147, 153, 0.2);
}

.luck-level {
  font-size: 14px;
  font-weight: normal;
  margin-top: 5px;
  opacity: 0.8;
}

.personal-score {
  text-align: center;
}

.score-label {
  display: block;
  font-size: 12px;
  color: var(--text-tertiary);
  margin-bottom: 5px;
}

.score-value {
  font-size: 36px;
  font-weight: bold;
}

.score-value.excellent { color: var(--success-color); }
.score-value.good { color: var(--warning-color); }
.score-value.normal { color: var(--danger-color); }

.personal-advice {
  background: var(--bg-card);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 25px;
}

.personal-advice h4 {
  color: var(--primary-color);
  margin-bottom: 10px;
}

.personal-advice p {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 15px;
}

.lucky-info {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.lucky-item {
  background: var(--bg-secondary);
  border-radius: 16px;
  padding: 15px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.lucky-label {
  font-size: 14px;
  color: var(--text-tertiary);
  white-space: nowrap;
}

.lucky-values {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.lucky-tag {
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 13px;
}

.lucky-tag.color {
  background: rgba(184, 134, 11, 0.1);
  color: var(--primary-color);
}

.lucky-tag.direction {
  background: rgba(212, 175, 55, 0.1);
  color: var(--primary-light);
}

/* 无八字提示 */
.no-bazi-hint {
  margin-bottom: 30px;
  text-align: center;
}

.hint-content {
  padding: 30px;
}

.hint-icon {
  font-size: 48px;
  display: block;
  margin-bottom: 15px;
  color: var(--primary-color);
}

.no-bazi-hint p {
  color: var(--text-secondary);
  margin-bottom: 15px;
}

@media (max-width: 768px) {
  .aspect-grid {
    grid-template-columns: 1fr;
  }
  
  .lucky-grid {
    grid-template-columns: 1fr;
  }
  
  .date-display {
    flex-direction: column;
    gap: 15px;
  }
  
  .master-info {
    flex-direction: column;
  }
  
  .relation-arrow {
    transform: rotate(90deg);
  }
  
  .luck-indicator {
    flex-direction: column;
  }
  
  .lucky-info {
    grid-template-columns: 1fr;
  }
}
</style>
