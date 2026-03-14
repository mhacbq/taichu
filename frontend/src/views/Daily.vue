<template>
  <div class="daily-page">
    <div class="container">
      <div class="page-header">
        <BackButton />
        <h1 class="section-title">每日运势</h1>
      </div>

      <!-- 签到卡片 -->
      <CheckinCard />

      <div class="date-display card">
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
        <div class="overall-score card">
          <h2>今日综合运势</h2>
          <div class="score-display">
            <div class="score-circle">
              <span class="score-number">{{ fortune.overallScore }}</span>
              <span class="score-label">分</span>
            </div>
            <div class="score-stars">
              <span v-for="n in 5" :key="n" class="star" :class="{ filled: n <= Math.round(fortune.overallScore / 20) }">★</span>
            </div>
          </div>
          <p class="fortune-summary">{{ fortune.summary }}</p>
        </div>

        <div class="aspect-grid">
          <div class="aspect-card card" v-for="aspect in fortune.aspects" :key="aspect.name">
            <div class="aspect-icon">{{ aspect.icon }}</div>
            <h3>{{ aspect.name }}</h3>
            <div class="aspect-score">
              <el-progress :percentage="aspect.score" :color="getScoreColor(aspect.score)" />
            </div>
            <p class="aspect-desc">{{ aspect.description }}</p>
          </div>
        </div>

        <div class="lucky-section card">
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

        <div class="details-section card">
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

      <div v-else class="loading-state card">
        <el-skeleton :rows="10" animated />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { getDailyFortune } from '../api'
import CheckinCard from '../components/CheckinCard.vue'
import BackButton from '../components/BackButton.vue'

const solarDate = ref('')
const lunarDate = ref('')
const fortune = ref(null)
const activeNames = ref(['career', 'wealth'])

const getScoreColor = (score) => {
  if (score >= 80) return '#67C23A'
  if (score >= 60) return '#E6A23C'
  return '#F56C6C'
}

const loadDailyFortune = async () => {
  try {
    const response = await getDailyFortune()
    if (response.code === 0) {
      fortune.value = response.data.fortune
      solarDate.value = response.data.solarDate
      lunarDate.value = response.data.lunarDate
    } else {
      ElMessage.error(response.message || '获取运势失败')
    }
  } catch (error) {
    ElMessage.error('网络错误，请稍后重试')
    console.error(error)
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
}

.lunar-date,
.solar-date {
  text-align: center;
}

.label {
  display: block;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 5px;
}

.value {
  font-size: 20px;
  color: #fff;
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
  color: #fff;
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
  background: linear-gradient(135deg, #e94560 0%, #ff6b6b 100%);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  margin-bottom: 15px;
}

.score-number {
  font-size: 48px;
  font-weight: bold;
  color: #fff;
}

.score-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
}

.score-stars {
  display: flex;
  gap: 5px;
}

.star {
  font-size: 24px;
  color: rgba(255, 255, 255, 0.3);
}

.star.filled {
  color: #ffd700;
}

.fortune-summary {
  color: rgba(255, 255, 255, 0.8);
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
  color: #fff;
  margin-bottom: 15px;
  font-size: 18px;
}

.aspect-score {
  margin-bottom: 15px;
}

.aspect-desc {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
  line-height: 1.5;
}

.lucky-section {
  margin-bottom: 30px;
}

.lucky-section h3 {
  color: #fff;
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
  background: #67C23A;
  color: #fff;
}

.lucky-item.bad .lucky-label {
  background: #F56C6C;
  color: #fff;
}

.lucky-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.details-section h3 {
  color: #fff;
  margin-bottom: 20px;
  text-align: center;
}

.details-section p {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.8;
}

.loading-state {
  max-width: 800px;
  margin: 0 auto;
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
}
</style>
