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
      <div class="calendar-header">
        <span>本月签到记录</span>
        <span class="calendar-month">{{ calendarMonthLabel }}</span>
      </div>
      <div class="calendar-weekdays">
        <span v-for="weekday in weekdayHeaders" :key="weekday" class="calendar-weekday">{{ weekday }}</span>
      </div>
      <div class="calendar-grid">
        <div 
          v-for="day in calendarCells" 
          :key="day.key"
          class="calendar-day"
          :class="{ 
            'calendar-day--placeholder': day.isPlaceholder,
            checked: day.checked,
            today: day.isToday 
          }"
        >
          <template v-if="!day.isPlaceholder">
            <span class="calendar-day__number">{{ day.day }}</span>
            <span v-if="day.checked" class="calendar-day__dot"></span>
          </template>
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
const availabilityMessage = ref('签到功能暂时不可用，不影响查看日常参考')

const truncateCheckinErrorMessage = (message) => {
  const normalized = typeof message === 'string' ? message.trim() : ''
  if (!normalized) {
    return 'unknown'
  }

  return normalized.length > 160 ? `${normalized.slice(0, 157)}...` : normalized
}

const reportCheckinError = (action, error, extra = {}) => {
  if (!import.meta.env.DEV) {
    return
  }

  console.error('[CheckinCard]', {
    action,
    error_type: error?.name || typeof error,
    message: truncateCheckinErrorMessage(error?.message || String(error ?? '')),
    ...extra
  })
}

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

const weekdayHeaders = ['一', '二', '三', '四', '五', '六', '日']

const calendarMonthLabel = computed(() => {
  const today = new Date()
  return `${today.getFullYear()}年${today.getMonth() + 1}月`
})

// 生成日历数据
const calendarCells = computed(() => {
  const cells = []
  const today = new Date()
  const year = today.getFullYear()
  const month = today.getMonth()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const firstDayOffset = (new Date(year, month, 1).getDay() + 6) % 7

  for (let i = 0; i < firstDayOffset; i++) {
    cells.push({
      key: `placeholder-${i}`,
      isPlaceholder: true,
      checked: false,
      isToday: false,
      day: '',
    })
  }
  
  for (let i = 1; i <= daysInMonth; i++) {
    const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`
    cells.push({
      key: date,
      day: i,
      date,
      isPlaceholder: false,
      checked: monthCheckins.value.includes(date),
      isToday: i === today.getDate()
    })
  }
  
  return cells
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
    reportCheckinError('load_checkin_status_failed', error, {
      status_unavailable: true
    })
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
    reportCheckinError('submit_checkin_failed', error, {
      has_checked_in: hasCheckedIn.value,
      status_unavailable: statusUnavailable.value
    })
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

.checkin-btn__content {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}


.checkin-calendar {
  border-top: 1px solid var(--border-light);
  padding-top: 20px;
}

.calendar-header {
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.calendar-month {
  font-size: 12px;
  color: var(--text-tertiary);
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
  margin-bottom: 8px;
}

.calendar-weekday {
  text-align: center;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-tertiary);
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
  border: 1px solid transparent;
}

.calendar-day--placeholder {
  background: transparent;
  border-color: transparent;
  pointer-events: none;
}

.calendar-day.checked {
  background: var(--success-gradient);
  color: #fff;
  font-weight: bold;
  box-shadow: 0 10px 18px rgba(16, 185, 129, 0.18);
}

.calendar-day.today {
  border-color: var(--primary-color);
  color: var(--text-primary);
  font-weight: 700;
}

.calendar-day.today.checked {
  border-color: rgba(255, 255, 255, 0.75);
  color: #fff;
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