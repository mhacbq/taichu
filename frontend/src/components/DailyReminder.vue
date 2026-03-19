<template>
  <Transition name="reminder">
    <div v-if="show" class="daily-reminder">
      <div class="reminder-backdrop" @click="dismiss"></div>
      <div class="reminder-card">
        <div class="reminder-decoration">
          <div class="moon"></div>
          <div class="stars">
            <span v-for="n in 6" :key="n" class="star" :style="{ animationDelay: n * 0.2 + 's' }"></span>
          </div>
        </div>
        
        <div class="reminder-content">
          <h3 class="reminder-title">今日运势已更新</h3>
          <p class="reminder-desc">
            {{ greeting }}，{{ userName }}！<br>
            今日{{ chineseZodiac }}运势已为你准备好，快来看看今天的幸运指南吧～
          </p>
          
          <div class="reminder-fortune" v-if="todayFortune">
            <div class="fortune-item">
              <span class="fortune-label">综合运势</span>
              <div class="fortune-stars">
                <span v-for="n in 5" :key="n" class="star-icon" :class="{ active: n <= todayFortune.overall }">★</span>
              </div>
            </div>
            <div class="fortune-lucky" v-if="todayFortune.lucky">
              <span class="lucky-tag">💰 财运方位：{{ todayFortune.lucky.direction }}</span>
              <span class="lucky-tag">🎨 幸运色：{{ todayFortune.lucky.color }}</span>
            </div>
          </div>
          
          <div class="reminder-actions">
            <button class="btn btn-primary" @click="viewFortune">
              <svg viewBox="0 0 24 24" fill="none" class="btn-icon">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              查看今日运势
            </button>
            <button class="btn btn-text" @click="dismiss">稍后再看</button>
          </div>
        </div>
        
        <button class="reminder-close" @click="dismiss">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2"/>
          </svg>
        </button>
      </div>
    </div>
  </Transition>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'DailyReminder',
  props: {
    userName: {
      type: String,
      default: '缘主'
    },
    userZodiac: {
      type: String,
      default: ''
    }
  },
  emits: ['view', 'dismiss'],
  setup(props, { emit }) {
    const router = useRouter()
    const show = ref(false)
    const todayFortune = ref(null)
    
    const greeting = computed(() => {
      const hour = new Date().getHours()
      if (hour < 6) return '凌晨好'
      if (hour < 9) return '早上好'
      if (hour < 12) return '上午好'
      if (hour < 14) return '中午好'
      if (hour < 18) return '下午好'
      return '晚上好'
    })
    
    const chineseZodiac = computed(() => {
      const zodiacs = ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪']
      if (props.userZodiac) return props.userZodiac
      const year = new Date().getFullYear()
      return zodiacs[(year - 4) % 12]
    })
    
    const checkAndShow = async () => {
      // 检查今天是否已经显示过
      const lastShown = localStorage.getItem('dailyReminderLastShown')
      const today = new Date().toDateString()
      
      if (lastShown === today) return
      
      // 获取今日运势数据
      try {
        const response = await fetch('/api/daily/today')
        const data = await response.json()
        if (data.code === 200) {
          todayFortune.value = data.data
          show.value = true
          localStorage.setItem('dailyReminderLastShown', today)
        }
      } catch (error) {
        console.error('获取今日运势失败:', error)
      }
    }
    
    const viewFortune = () => {
      show.value = false
      emit('view')
      router.push('/daily')
    }
    
    const dismiss = () => {
      show.value = false
      emit('dismiss')
    }
    
    onMounted(() => {
      // 延迟显示，避免页面加载时弹出
      setTimeout(checkAndShow, 2000)
    })
    
    return {
      show,
      greeting,
      chineseZodiac,
      todayFortune,
      viewFortune,
      dismiss
    }
  }
}
</script>

<style scoped>
.daily-reminder {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.reminder-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(4px);
}

.reminder-card {
  position: relative;
  width: 100%;
  max-width: 420px;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.reminder-decoration {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 120px;
  overflow: hidden;
}

.moon {
  position: absolute;
  top: 20px;
  right: 30px;
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  border-radius: 50%;
  box-shadow: 0 0 40px rgba(251, 191, 36, 0.4);
}

.moon::after {
  content: '';
  position: absolute;
  top: 10px;
  right: 15px;
  width: 50px;
  height: 50px;
  background: #1a1a2e;
  border-radius: 50%;
}

.stars {
  position: absolute;
  inset: 0;
}

.star {
  position: absolute;
  width: 4px;
  height: 4px;
  background: white;
  border-radius: 50%;
  animation: twinkle 2s ease-in-out infinite;
}

.star:nth-child(1) { top: 20%; left: 10%; }
.star:nth-child(2) { top: 40%; left: 20%; }
.star:nth-child(3) { top: 15%; left: 40%; }
.star:nth-child(4) { top: 50%; left: 60%; }
.star:nth-child(5) { top: 30%; left: 80%; }
.star:nth-child(6) { top: 60%; left: 90%; }

@keyframes twinkle {
  0%, 100% { opacity: 0.3; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.2); }
}

.reminder-content {
  position: relative;
  padding: 140px 24px 24px;
  text-align: center;
  color: white;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(5px);
}

.reminder-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 12px;
  background: linear-gradient(135deg, #fbbf24, #f59e0b, #d97706);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.reminder-desc {
  font-size: 15px;
  color: var(--white-80);
  line-height: 1.6;
  margin: 0 0 24px;
}

.reminder-fortune {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.05));
  border-radius: 20px;
  padding: 20px;
  margin-bottom: 24px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.fortune-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.fortune-item:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.fortune-label {
  font-size: 15px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  min-width: 80px;
}

.fortune-stars {
  display: flex;
  gap: 6px;
}

.star-icon {
  font-size: 20px;
  color: rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
}

.star-icon.active {
  color: #fbbf24;
  text-shadow: 0 0 12px rgba(251, 191, 36, 0.8);
  transform: scale(1.1);
}

.fortune-lucky {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
}

.lucky-tag {
  font-size: 12px;
  padding: 6px 12px;
  background: rgba(139, 92, 246, 0.3);
  border-radius: 20px;
  color: var(--white-90);
}

.reminder-actions {
  display: flex;
  gap: 16px;
  justify-content: center;
  margin-top: 8px;
}

.btn {
  padding: 16px 32px;
  border-radius: 16px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 52px;
  position: relative;
  overflow: hidden;
}

.btn-primary {
  background: linear-gradient(135deg, #fbbf24, #f59e0b, #d97706);
  color: #1a1a2e;
  box-shadow: 0 6px 20px rgba(251, 191, 36, 0.4);
}

.btn-primary::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s ease;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(251, 191, 36, 0.6);
}

.btn-primary:hover::before {
  left: 100%;
}

.btn-text {
  background: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(10px);
}

.btn-text:hover {
  background: rgba(255, 255, 255, 0.15);
  color: rgba(255, 255, 255, 1);
  border-color: rgba(255, 255, 255, 0.4);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
}

.btn-icon {
  width: 18px;
  height: 18px;
}

.reminder-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 32px;
  height: 32px;
  padding: 0;
  border: none;
  background: var(--white-10);
  border-radius: 50%;
  color: var(--white-60);
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.reminder-close:hover {
  background: var(--white-20);
  color: white;
}

.reminder-close svg {
  width: 16px;
  height: 16px;
}

/* 动画 */
.reminder-enter-active .reminder-backdrop {
  animation: fadeIn 0.3s ease-out;
}

.reminder-leave-active .reminder-backdrop {
  animation: fadeOut 0.2s ease-in;
}

.reminder-enter-active .reminder-card {
  animation: cardIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.reminder-leave-active .reminder-card {
  animation: cardOut 0.3s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes fadeOut {
  from { opacity: 1; }
  to { opacity: 0; }
}

@keyframes cardIn {
  from {
    opacity: 0;
    transform: scale(0.8) translateY(20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes cardOut {
  from {
    opacity: 1;
    transform: scale(1);
  }
  to {
    opacity: 0;
    transform: scale(0.9);
  }
}
</style>
