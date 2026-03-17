<template>
  <div class="checkin-card card" :class="{ checked: hasCheckedIn }">
    <div class="checkin-header">
      <h3>每日签到</h3>
      <div class="consecutive-days" v-if="consecutiveDays > 0">
        <span class="fire-icon">🔥</span>
        <span>连续 {{ consecutiveDays }} 天</span>
      </div>
    </div>
    
    <div class="checkin-content">
      <div class="points-info">
        <div class="today-points">
          <span class="points-label">今日签到奖励</span>
          <span class="points-value">+{{ todayPoints }} 积分</span>
        </div>
        <div class="bonus-hint" v-if="nextBonus > 0">
          再签到 {{ nextBonusDays }} 天可额外获得 {{ nextBonus }} 积分！
        </div>
        <div v-if="statusUnavailable" class="unavailable-hint">
          {{ availabilityMessage }}
        </div>
      </div>
      
      <el-button
        :type="hasCheckedIn ? 'success' : 'primary'"
        size="large"
        :disabled="hasCheckedIn || statusUnavailable"
        :loading="loading"
        @click="handleCheckin"
        class="checkin-btn"
      >
        <span v-if="hasCheckedIn">✓ 今日已签到</span>
        <span v-else-if="statusUnavailable">签到暂不可用</span>
        <span v-else>立即签到</span>
      </el-button>

    </div>
    
    <!-- 签到日历 -->
    <div class="checkin-calendar">
      <div class="calendar-header">本月签到记录</div>
      <div class="calendar-grid">
        <div 
          v-for="day in calendarDays" 
          :key="day.date"
          class="calendar-day"
          :class="{ 
            checked: day.checked,
            today: day.isToday 
          }"
        >
          {{ day.day }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { dailyCheckin, getCheckinStatus } from '../api'

const loading = ref(false)
const hasCheckedIn = ref(false)
const consecutiveDays = ref(0)
const todayPoints = ref(5)
const monthCheckins = ref([])
const consecutiveBonus = ref({})
const statusUnavailable = ref(false)
const availabilityMessage = ref('签到功能暂时不可用，不影响查看今日运势')


// 计算下一个奖励
const nextBonus = computed(() => {
  const days = consecutiveDays.value + 1
  let nextBonusPoints = 0
  let minDays = Infinity
  
  for (const [bonusDays, bonus] of Object.entries(consecutiveBonus.value)) {
    if (days < bonusDays && bonusDays < minDays) {
      minDays = bonusDays
      nextBonusPoints = bonus
    }
  }
  
  return nextBonusPoints
})

const nextBonusDays = computed(() => {
  const days = consecutiveDays.value + 1
  let minDays = Infinity
  
  for (const bonusDays of Object.keys(consecutiveBonus.value)) {
    if (days < bonusDays && bonusDays < minDays) {
      minDays = bonusDays
    }
  }
  
  return minDays === Infinity ? 0 : minDays - days + 1
})

// 生成日历数据
const calendarDays = computed(() => {
  const days = []
  const today = new Date()
  const year = today.getFullYear()
  const month = today.getMonth()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  
  for (let i = 1; i <= daysInMonth; i++) {
    const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`
    days.push({
      day: i,
      date: date,
      checked: monthCheckins.value.includes(date),
      isToday: i === today.getDate()
    })
  }
  
  return days
})

const loadCheckinStatus = async () => {
  try {
    const response = await getCheckinStatus({ silent: true })
    if (response.code === 200) {
      statusUnavailable.value = false
      availabilityMessage.value = '签到功能暂时不可用，不影响查看今日运势'
      hasCheckedIn.value = Boolean(response.data?.checkedIn)
      consecutiveDays.value = response.data?.consecutiveDays ?? 0
      todayPoints.value = response.data?.todayPoints ?? 5
      monthCheckins.value = response.data?.monthCheckins || []
      consecutiveBonus.value = response.data?.consecutiveBonus || {}
      return
    }

    statusUnavailable.value = true
    availabilityMessage.value = response.message || '签到功能暂时不可用，不影响查看今日运势'
  } catch (error) {
    statusUnavailable.value = true
    availabilityMessage.value = '签到功能暂时不可用，不影响查看今日运势'
    console.error('加载签到状态失败:', error)
  }
}

const handleCheckin = async () => {
  if (hasCheckedIn.value) return
  if (statusUnavailable.value) {
    ElMessage.info('签到功能暂时不可用，请稍后再试')
    return
  }
  
  loading.value = true
  try {
    const response = await dailyCheckin()
    if (response.code === 200) {
      statusUnavailable.value = false
      hasCheckedIn.value = true
      consecutiveDays.value = response.data.consecutiveDays
      ElMessage.success(response.data.message)
      
      // 刷新状态
      await loadCheckinStatus()
      
      // 触发积分更新事件
      window.dispatchEvent(new Event('points-updated'))
    } else {
      ElMessage.error(response.message || '签到失败')
    }
  } catch (error) {
    ElMessage.error('签到失败，请稍后重试')
    console.error(error)
  } finally {
    loading.value = false
  }
}


onMounted(() => {
  loadCheckinStatus()
})
</script>

<style scoped>
.checkin-card {
  margin-bottom: 30px;
}

.checkin-card.checked {
  border: 1px solid rgba(16, 185, 129, 0.22);
  box-shadow: 0 16px 32px rgba(16, 185, 129, 0.1);
}

.checkin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.checkin-header h3 {
  color: var(--text-primary);
  margin: 0;
}

.consecutive-days {
  background: var(--primary-gradient);
  padding: 5px 12px;
  border-radius: 16px;
  font-size: 14px;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 5px;
}

.fire-icon {
  font-size: 16px;
}

.checkin-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  flex-wrap: wrap;
  gap: 15px;
}

.points-info {
  flex: 1;
}

.today-points {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.points-label {
  color: var(--text-secondary);
  font-size: 14px;
}

.points-value {
  color: var(--accent-color);
  font-size: 24px;
  font-weight: bold;
}

.bonus-hint {
  color: var(--success-color);
  font-size: 13px;
  margin-top: 8px;
}

.unavailable-hint {
  color: var(--warning-color);
  font-size: 13px;
  margin-top: 8px;
  line-height: 1.5;
}


.checkin-btn {
  min-width: 120px;
  height: 45px;
  font-size: 16px;
}

.checkin-calendar {
  border-top: 1px solid var(--border-light);
  padding-top: 20px;
}

.calendar-header {
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 15px;
  text-align: center;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
}

.calendar-day {
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-card-hover);
  border-radius: 16px;
  font-size: 14px;
  color: var(--text-secondary);
}

.calendar-day.checked {
  background: var(--success-gradient);
  color: #fff;
  font-weight: bold;
}

.calendar-day.today {
  border: 2px solid var(--primary-color);
}

.calendar-day.today.checked {
  border-color: var(--success-color);
}

@media (max-width: 480px) {
  .checkin-content {
    flex-direction: column;
    align-items: stretch;
  }

  .checkin-btn {
    width: 100%;
  }
}
</style>