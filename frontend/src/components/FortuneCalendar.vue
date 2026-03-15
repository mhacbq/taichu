<template>
  <div class="fortune-calendar">
    <div class="calendar-header">
      <button class="nav-btn" @click="prevMonth">
        <span>‹</span>
      </button>
      <div class="current-month">
        <span class="year">{{ currentYear }}</span>
        <span class="month">{{ currentMonth + 1 }}月</span>
      </div>
      <button class="nav-btn" @click="nextMonth">
        <span>›</span>
      </button>
    </div>
    
    <div class="calendar-weekdays">
      <span v-for="day in weekdays" :key="day" class="weekday">{{ day }}</span>
    </div>
    
    <div class="calendar-days">
      <div
        v-for="(day, index) in calendarDays"
        :key="index"
        class="calendar-day"
        :class="{
          'other-month': !day.isCurrentMonth,
          'today': day.isToday,
          'selected': day.isSelected,
          'has-fortune': day.hasFortune,
          [`fortune-level-${day.fortuneLevel}`]: day.fortuneLevel,
        }"
        @click="selectDay(day)"
      >
        <span class="day-number">{{ day.date.getDate() }}</span>
        <div v-if="day.hasFortune" class="fortune-dot"></div>
        <div v-if="day.mood" class="mood-icon">{{ day.mood }}</div>
      </div>
    </div>
    
    <div class="calendar-legend">
      <div class="legend-item">
        <span class="dot level-3"></span>
        <span>大吉</span>
      </div>
      <div class="legend-item">
        <span class="dot level-2"></span>
        <span>吉</span>
      </div>
      <div class="legend-item">
        <span class="dot level-1"></span>
        <span>平</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  fortuneData: {
    type: Object,
    default: () => ({}),
  },
  moodData: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['select'])

const weekdays = ['日', '一', '二', '三', '四', '五', '六']
const currentDate = ref(new Date())
const selectedDate = ref(new Date())

const currentYear = computed(() => currentDate.value.getFullYear())
const currentMonth = computed(() => currentDate.value.getMonth())

const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const prevLastDay = new Date(year, month, 0)
  
  const days = []
  const startWeekday = firstDay.getDay()
  
  // 上月日期
  for (let i = startWeekday - 1; i >= 0; i--) {
    const date = new Date(year, month, -i)
    days.push(createDayObject(date, false))
  }
  
  // 当月日期
  for (let i = 1; i <= lastDay.getDate(); i++) {
    const date = new Date(year, month, i)
    days.push(createDayObject(date, true))
  }
  
  // 下月日期
  const remainingDays = 42 - days.length
  for (let i = 1; i <= remainingDays; i++) {
    const date = new Date(year, month + 1, i)
    days.push(createDayObject(date, false))
  }
  
  return days
})

const createDayObject = (date, isCurrentMonth) => {
  const dateStr = date.toISOString().split('T')[0]
  const today = new Date()
  
  return {
    date,
    isCurrentMonth,
    isToday: date.toDateString() === today.toDateString(),
    isSelected: date.toDateString() === selectedDate.value?.toDateString(),
    hasFortune: !!props.fortuneData[dateStr],
    fortuneLevel: props.fortuneData[dateStr]?.level || 0,
    mood: props.moodData[dateStr]?.mood || '',
  }
}

const prevMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1)
}

const nextMonth = () => {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1)
}

const selectDay = (day) => {
  if (!day.isCurrentMonth) {
    currentDate.value = new Date(day.date)
  }
  selectedDate.value = day.date
  emit('select', day.date)
}

watch(() => props.fortuneData, () => {
  // 重新计算日历数据
}, { deep: true })
</script>

<style scoped>
.fortune-calendar {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.nav-btn {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  font-size: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-btn:hover {
  background: rgba(233, 69, 96, 0.3);
  transform: scale(1.1);
}

.current-month {
  text-align: center;
  color: #fff;
}

.year {
  display: block;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 4px;
}

.month {
  font-size: 20px;
  font-weight: bold;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
  margin-bottom: 12px;
}

.weekday {
  text-align: center;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.5);
  padding: 8px 0;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 8px;
}

.calendar-day {
  aspect-ratio: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  cursor: pointer;
  position: relative;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.03);
}

.calendar-day:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-2px);
}

.calendar-day.other-month {
  opacity: 0.3;
}

.calendar-day.today {
  background: rgba(233, 69, 96, 0.2);
  border: 1px solid rgba(233, 69, 96, 0.5);
}

.calendar-day.selected {
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  box-shadow: 0 4px 15px rgba(233, 69, 96, 0.4);
}

.day-number {
  font-size: 14px;
  color: #fff;
  font-weight: 500;
}

.calendar-day.selected .day-number {
  font-weight: bold;
}

.fortune-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  position: absolute;
  bottom: 6px;
}

.fortune-level-3 .fortune-dot {
  background: #52c41a;
  box-shadow: 0 0 8px #52c41a;
}

.fortune-level-2 .fortune-dot {
  background: #faad14;
  box-shadow: 0 0 8px #faad14;
}

.fortune-level-1 .fortune-dot {
  background: #f5222d;
  box-shadow: 0 0 8px #f5222d;
}

.mood-icon {
  position: absolute;
  top: 2px;
  right: 2px;
  font-size: 12px;
}

.calendar-legend {
  display: flex;
  justify-content: center;
  gap: 24px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.dot.level-3 {
  background: #52c41a;
}

.dot.level-2 {
  background: #faad14;
}

.dot.level-1 {
  background: #f5222d;
}
</style>
