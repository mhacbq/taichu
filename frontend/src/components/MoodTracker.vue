<template>
  <div class="mood-tracker">
    <div class="mood-header">
      <h3 class="title">
        <span class="icon">😊</span>
        心情日记
      </h3>
      <span class="subtitle">记录每一天的情绪变化</span>
    </div>
    
    <div class="mood-selector">
      <p class="mood-question">今天感觉怎么样？</p>
      <div class="mood-options">
        <button
          v-for="mood in moods"
          :key="mood.value"
          class="mood-btn"
          :class="{ selected: selectedMood === mood.value }"
          @click="selectMood(mood.value)"
        >
          <span class="mood-emoji">{{ mood.emoji }}</span>
          <span class="mood-label">{{ mood.label }}</span>
        </button>
      </div>
    </div>
    
    <div class="mood-note" v-if="selectedMood">
      <textarea
        v-model="moodNote"
        placeholder="写下今天的心情...（可选）"
        rows="3"
      ></textarea>
      <button class="save-btn" @click="saveMood" :disabled="saving">
        <span v-if="saving">保存中...</span>
        <span v-else>记录心情</span>
      </button>
    </div>
    
    <div class="mood-stats" v-if="moodHistory.length > 0">
      <h4 class="stats-title">本周心情趋势</h4>
      <div class="mood-chart">
        <div
          v-for="(day, index) in weekMoods"
          :key="index"
          class="chart-bar"
          :class="{ 'has-data': day.mood }"
        >
          <div class="bar-fill" :style="{ height: getMoodHeight(day.mood) + '%' }">
            <span class="bar-emoji" v-if="day.mood">{{ getMoodEmoji(day.mood) }}</span>
          </div>
          <span class="bar-label">{{ day.label }}</span>
        </div>
      </div>
      
      <div class="mood-summary">
        <div class="summary-item">
          <span class="summary-label">本周平均心情</span>
          <span class="summary-value">{{ averageMood }}</span>
        </div>
        <div class="summary-item">
          <span class="summary-label">最常出现</span>
          <span class="summary-value">{{ mostCommonMood }}</span>
        </div>
      </div>
    </div>
    
    <div class="mood-fortune-link" v-if="todayFortune">
      <div class="link-card">
        <span class="link-icon">✨</span>
        <div class="link-content">
          <p class="link-text">根据今日运势，你的心情与运势相呼应</p>
          <router-link to="/daily" class="link-btn">查看运势详情 →</router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const moods = [
  { value: 5, emoji: '😄', label: '超开心', color: '#52c41a' },
  { value: 4, emoji: '😊', label: '开心', color: '#73d13d' },
  { value: 3, emoji: '😐', label: '一般', color: '#faad14' },
  { value: 2, emoji: '😔', label: '低落', color: '#ff7a45' },
  { value: 1, emoji: '😢', label: '难过', color: '#f5222d' },
]

const selectedMood = ref(null)
const moodNote = ref('')
const saving = ref(false)
const todayFortune = ref(true)

// 示例心情历史数据
const moodHistory = ref([
  { date: '2024-01-20', mood: 4, note: '今天工作顺利，心情不错' },
  { date: '2024-01-19', mood: 5, note: '周末啦！超级开心' },
  { date: '2024-01-18', mood: 3, note: '有点累，但还行' },
  { date: '2024-01-17', mood: 4, note: '和朋友聚餐，很开心' },
  { date: '2024-01-16', mood: 2, note: '工作压力有点大' },
  { date: '2024-01-15', mood: 4, note: '新的一周，充满活力' },
  { date: '2024-01-14', mood: 5, note: '周日休息，心情超棒' },
])

const weekMoods = computed(() => {
  const days = ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
  return days.map((label, index) => {
    const historyItem = moodHistory.value[index]
    return {
      label,
      mood: historyItem?.mood || null,
    }
  }).reverse()
})

const averageMood = computed(() => {
  if (moodHistory.value.length === 0) return '-'
  const sum = moodHistory.value.reduce((acc, item) => acc + item.mood, 0)
  const avg = sum / moodHistory.value.length
  const mood = moods.find(m => m.value === Math.round(avg))
  return mood?.emoji || '😐'
})

const mostCommonMood = computed(() => {
  if (moodHistory.value.length === 0) return '-'
  const counts = {}
  moodHistory.value.forEach(item => {
    counts[item.mood] = (counts[item.mood] || 0) + 1
  })
  const maxMood = Object.entries(counts).sort((a, b) => b[1] - a[1])[0]
  const mood = moods.find(m => m.value === parseInt(maxMood[0]))
  return mood?.emoji || '😐'
})

const selectMood = (value) => {
  selectedMood.value = value
}

const saveMood = async () => {
  saving.value = true
  // 模拟API调用
  await new Promise(resolve => setTimeout(resolve, 500))
  
  const today = new Date().toISOString().split('T')[0]
  moodHistory.value.unshift({
    date: today,
    mood: selectedMood.value,
    note: moodNote.value,
  })
  
  selectedMood.value = null
  moodNote.value = ''
  saving.value = false
}

const getMoodHeight = (mood) => {
  if (!mood) return 0
  return (mood / 5) * 100
}

const getMoodEmoji = (mood) => {
  const moodItem = moods.find(m => m.value === mood)
  return moodItem?.emoji || ''
}
</script>

<style scoped>
.mood-tracker {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 24px;
  border: 1px solid var(--border-color);
}

.mood-header {
  margin-bottom: 20px;
}

.title {
  color: var(--text-primary);
  font-size: 18px;
  margin: 0 0 4px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.icon {
  font-size: 24px;
}

.subtitle {
  color: var(--text-tertiary);
  font-size: 13px;
}

.mood-question {
  color: var(--text-primary);
  font-size: 15px;
  margin-bottom: 16px;
}

.mood-options {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 20px;
}

.mood-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 8px;
  border: 2px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  flex: 1;
}

.mood-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-2px);
}

.mood-btn.selected {
  border-color: #ffd700;
  background: rgba(255, 215, 0, 0.1);
}

.mood-emoji {
  font-size: 28px;
  transition: transform 0.3s ease;
}

.mood-btn:hover .mood-emoji,
.mood-btn.selected .mood-emoji {
  transform: scale(1.2);
}

.mood-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.7);
}

.mood-note {
  margin-bottom: 24px;
}

.mood-note textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  color: #fff;
  font-size: 14px;
  resize: none;
  margin-bottom: 12px;
  font-family: inherit;
}

.mood-note textarea::placeholder {
  color: rgba(255, 255, 255, 0.3);
}

.mood-note textarea:focus {
  outline: none;
  border-color: rgba(255, 215, 0, 0.5);
}

.save-btn {
  width: 100%;
  padding: 12px;
  background: linear-gradient(135deg, #ffd700, #ffc107);
  border: none;
  border-radius: 12px;
  color: #fff;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.save-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
}

.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.mood-stats {
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  padding-top: 20px;
}

.stats-title {
  color: #fff;
  font-size: 14px;
  margin: 0 0 16px 0;
}

.mood-chart {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  height: 120px;
  padding-bottom: 24px;
  margin-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.chart-bar {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.bar-fill {
  width: 36px;
  background: linear-gradient(to top, #ffd700, #ffc107);
  border-radius: 8px 8px 0 0;
  min-height: 4px;
  position: relative;
  transition: height 0.5s ease;
}

.bar-fill:not(.has-data) {
  background: rgba(255, 255, 255, 0.1);
  height: 4px !important;
}

.bar-emoji {
  position: absolute;
  top: -24px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 16px;
}

.bar-label {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.5);
}

.mood-summary {
  display: flex;
  gap: 24px;
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.summary-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
}

.summary-value {
  font-size: 24px;
}

.mood-fortune-link {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.link-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 193, 7, 0.1));
  border-radius: 12px;
  border: 1px solid rgba(255, 215, 0, 0.2);
}

.link-icon {
  font-size: 24px;
}

.link-content {
  flex: 1;
}

.link-text {
  color: rgba(255, 255, 255, 0.8);
  font-size: 13px;
  margin: 0 0 8px 0;
}

.link-btn {
  color: #ffd700;
  font-size: 13px;
  text-decoration: none;
  font-weight: 500;
}

.link-btn:hover {
  text-decoration: underline;
}

@media (max-width: 768px) {
  .mood-options {
    gap: 4px;
  }
  
  .mood-btn {
    padding: 10px 4px;
  }
  
  .mood-emoji {
    font-size: 22px;
  }
  
  .mood-label {
    font-size: 11px;
  }
}
</style>
