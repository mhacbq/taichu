<template>
  <div class="home">
    <GuideModal />
    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
        <!-- 暖心问候语 - 已登录 -->
        <div v-if="isLoggedIn" class="warm-greeting">
          <div class="greeting-content">
            <span class="greeting-icon">
              <el-icon v-if="greetingIcon === 'morning'" :size="32"><Sunrise /></el-icon>
              <el-icon v-else-if="greetingIcon === 'afternoon'" :size="32"><Sunny /></el-icon>
              <el-icon v-else :size="32"><Moon /></el-icon>
            </span>
            <div class="greeting-text">
              <h3>{{ greetingText }}</h3>
              <p class="daily-quote">{{ dailyQuote }}</p>
            </div>
          </div>
        </div>
        
        <!-- 用户积分卡片 - 已登录 -->
        <div v-if="isLoggedIn" class="user-points-card">
          <div class="points-display">
            <el-icon class="points-icon" :size="32"><Coin /></el-icon>
            <div class="points-info">
              <span class="points-label">我的积分</span>
              <span class="points-value">{{ userPoints }}</span>
            </div>
          </div>
          <div class="points-actions">
            <router-link to="/profile" class="points-btn checkin">签到领积分</router-link>
            <router-link to="/bazi" class="points-btn">去排盘</router-link>
          </div>
        </div>
        
        <!-- 未登录引导卡片 -->
        <div v-else class="guest-welcome-card">
          <div class="welcome-content">
            <el-icon class="welcome-icon" :size="40"><Cherry /></el-icon>
            <div class="welcome-text">
              <h3>嗨，你好呀</h3>
              <p>迷茫的时候，来这里找找答案吧</p>
            </div>
          </div>
          <div class="welcome-actions">
            <router-link to="/login" class="welcome-btn primary">立即登录</router-link>
            <router-link to="/login" class="welcome-btn secondary">注册领100积分</router-link>
          </div>
          <div class="welcome-features">
            <span class="feature-tag"><el-icon><Present /></el-icon> 新用户送100积分</span>
            <span class="feature-tag"><el-icon><Star /></el-icon> 首次排盘免费</span>
            <span class="feature-tag"><el-icon><MagicStick /></el-icon> 八字塔罗每日运势</span>
          </div>
        </div>
        
        <div class="hero-content">
          <h1 class="hero-title">在迷茫中找到方向</h1>
          <p class="hero-subtitle">不是预测命运，而是帮你更懂自己<br>八字、塔罗、运势，为你的困惑寻找答案</p>
          <div class="hero-actions">
            <router-link to="/bazi" class="btn-primary">
              <el-icon class="btn-icon"><Calendar /></el-icon>
              开始排盘
              <span class="btn-badge">首测免费</span>
            </router-link>
            <router-link to="/tarot" class="btn-secondary">
              <el-icon class="btn-icon"><MagicStick /></el-icon>
              塔罗占卜
            </router-link>
          </div>
          <p class="hero-hint"><el-icon><Star /></el-icon> 已有 {{ userCount }}+ 用户在这里找到答案</p>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features">
      <div class="container">
        <h2 class="section-title">我们的服务</h2>
        <div class="features-grid">
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><Calendar /></el-icon></div>
            <h3>八字分析</h3>
            <p>基于传统文化的性格分析，了解您的个性特点、发展方向、人际关系</p>
            <router-link to="/bazi" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><MagicStick /></el-icon></div>
            <h3>塔罗测试</h3>
            <p>趣味塔罗牌阵探索，为您的困惑提供思考角度，发现内心可能</p>
            <router-link to="/tarot" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><Switch /></el-icon></div>
            <h3>六爻占卜</h3>
            <p>传统周易六爻问事，为您解答工作、感情、决策等各类疑惑</p>
            <router-link to="/liuyao" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><Link /></el-icon></div>
            <h3>八字合婚</h3>
            <p>通过双方八字分析婚姻匹配度，了解缘分深浅与相处之道</p>
            <router-link to="/hehun" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><Star /></el-icon></div>
            <h3>每日指南</h3>
            <p>基于出生日期的每日幸运指数，生活参考，娱乐消遣</p>
            <router-link to="/daily" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><Aim /></el-icon></div>
            <h3>更多功能</h3>
            <p>取名建议、吉日查询等更多命理功能，满足您的不同需求</p>
            <router-link to="/profile" class="feature-link">
              探索更多 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- 用户评价 Section -->
    <section class="testimonials">
      <div class="container">
        <h2 class="section-title">用户心声</h2>
        <div class="testimonials-grid">
          <div class="testimonial-card card-hover" v-for="(item, index) in testimonials" :key="index">
            <div class="testimonial-header">
              <div class="testimonial-avatar" :style="{ backgroundColor: item.avatarColor }">{{ item.avatar }}</div>
              <div class="testimonial-info">
                <h4>{{ item.name }}</h4>
                <div class="testimonial-rating">
                  <el-icon v-for="n in 5" :key="n" class="star" :class="{ filled: n <= item.rating }">
                    <StarFilled />
                  </el-icon>
                </div>
              </div>
            </div>
            <p class="testimonial-content">{{ item.content }}</p>
            <div class="testimonial-service">
              <span class="service-tag">{{ item.service }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section class="about">
      <div class="container">
        <div class="about-content">
          <div class="about-text">
            <h2 class="section-title">关于太初文化</h2>
            <p>太初文化是一款结合传统文化与人工智能技术的趣味探索平台。我们致力于：</p>
            <ul class="about-list">
              <li><el-icon class="about-icon"><Check /></el-icon> 传承中华传统历法文化</li>
              <li><el-icon class="about-icon"><Check /></el-icon> 运用AI技术提供趣味分析</li>
              <li><el-icon class="about-icon"><Check /></el-icon> 为用户提供个性化的性格参考</li>
              <li><el-icon class="about-icon"><Check /></el-icon> 让传统文化探索更加有趣、便捷</li>
            </ul>
          </div>
          <div class="about-stats">
            <div class="stat-item card-hover" v-for="stat in stats" :key="stat.label">
              <div class="stat-icon-wrapper">
                <el-icon class="stat-icon"><component :is="stat.icon" /></el-icon>
              </div>
              <span class="stat-number">{{ stat.number }}</span>
              <span class="stat-label">{{ stat.label }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import GuideModal from '../components/GuideModal.vue'
import { getHomeStats, getPointsBalance } from '../api'
import { Sunrise, Sunny, Moon, Coin, Cherry, Calendar, MagicStick, Star, Aim, Present, Switch, Link, Check, UserFilled, DataLine, ChatLineRound } from '@element-plus/icons-vue'

const statIconMap = {
  UserFilled,
  DataLine,
  ChatLineRound,
}

const defaultStats = [
  { number: '加载中...', label: '服务用户', icon: UserFilled },
  { number: '加载中...', label: '分析次数', icon: DataLine },
  { number: '98%', label: '好评率', icon: ChatLineRound },
]

const resolveStatIcon = (icon, fallbackIcon = UserFilled) => {
  if (typeof icon === 'object' || typeof icon === 'function') {
    return icon
  }

  return statIconMap[icon] || fallbackIcon
}

const stats = ref(defaultStats)


const isLoggedIn = ref(false)
const userPoints = ref(0)
const userCount = ref(12000)

// 问候语数据
const hour = new Date().getHours()
const greetingIcon = computed(() => {
  if (hour < 12) return 'morning'
  if (hour < 18) return 'afternoon'
  return 'evening'
})

const greetingText = computed(() => {
  if (hour < 12) return '早上好，愿你今天充满希望'
  if (hour < 18) return '下午好，愿你的努力都有收获'
  return '晚上好，愿你今夜好梦'
})

// 每日暖心语录
const quotes = [
  '"迷茫不是软弱，而是你在认真思考人生"',
  '"每一次困惑，都是重新认识自己的机会"',
  '"相信自己的直觉，你比想象中更有力量"',
  '"生活不会辜负每一个认真的人"',
  '"今天的迷茫，是为了明天更坚定的选择"',
  '"慢慢来，没关系，每个人都在自己的时区"',
]

const dailyQuote = computed(() => {
  const dayOfYear = Math.floor((new Date() - new Date(new Date().getFullYear(), 0, 0)) / (1000 * 60 * 60 * 24))
  return quotes[dayOfYear % quotes.length]
})

// 用户评价数据 - 更贴近迷茫年轻人的真实感受
const testimonials = ref([
  {
    name: '小雨',
    avatar: '雨',
    avatarColor: 'rgba(184, 134, 11, 0.2)',
    rating: 5,
    content: '毕业后一直很迷茫，不知道自己适合什么工作。排盘后看到我的喜用神和适合的发展方向，突然有了方向感，现在已经在准备转行了！',
    service: '八字排盘'
  },
  {
    name: '阿杰',
    avatar: '杰',
    avatarColor: 'rgba(212, 175, 55, 0.2)',
    rating: 5,
    content: '感情遇到瓶颈期，塔罗给了我很大的启发。不是告诉我该怎么做，而是帮我理清了自己真正想要的是什么。现在已经和女友和好了。',
    service: '塔罗占卜'
  },
  {
    name: '小陈',
    avatar: '陈',
    avatarColor: 'rgba(103, 194, 58, 0.2)',
    rating: 5,
    content: '工作压力很大的时候，每天早上的运势推送成了我的精神支柱。有时候看到"今天适合休息"就会给自己放个假，感觉被理解了。',
    service: '每日运势'
  },
  {
    name: '琳琳',
    avatar: '琳',
    avatarColor: 'rgba(230, 162, 60, 0.2)',
    rating: 5,
    content: '作为INFJ，常常陷入自我怀疑。八字分析让我更接纳自己的性格特点，原来我生来就是这样，不是我有问题。',
    service: '八字排盘'
  },
  {
    name: '大鹏',
    avatar: '鹏',
    avatarColor: 'rgba(144, 147, 153, 0.2)',
    rating: 5,
    content: '一直纠结要不要跳槽，塔罗占卜给了我很中肯的建议。现在的新工作虽然累但是很开心，很感谢当时的指引。',
    service: '塔罗占卜'
  },
  {
    name: '思思',
    avatar: '思',
    avatarColor: 'rgba(184, 130, 240, 0.2)',
    rating: 5,
    content: '第一次用的时候还半信半疑，但结果真的挺准的。尤其是大运分析，让我知道未来几年需要注意什么，心里有底多了。',
    service: '八字排盘'
  }
])

const loadStats = async () => {
  try {
    const response = await getHomeStats()
    if (response.code === 200) {
      const incomingStats = response.data.stats || []
      stats.value = incomingStats.length
        ? incomingStats.map((item, index) => ({
            ...item,
            icon: resolveStatIcon(item.icon, defaultStats[index]?.icon || UserFilled),
          }))
        : defaultStats
      userCount.value = response.data.userCount || 12000
    }
  } catch (error) {
    console.error('加载统计数据失败:', error)
    stats.value = defaultStats
  }
}


const loadUserPoints = async () => {
  const token = localStorage.getItem('token')
  if (!token) {
    isLoggedIn.value = false
    return
  }
  
  isLoggedIn.value = true
  try {
    const response = await getPointsBalance()
    if (response.code === 200) {
      userPoints.value = response.data.balance
    }
  } catch (error) {
    console.error('加载积分失败:', error)
  }
}

onMounted(() => {
  loadStats()
  loadUserPoints()
})
</script>

<style scoped>
.hero {
  padding: 60px 0 100px;
  text-align: center;
  background: radial-gradient(ellipse at center, rgba(212, 175, 55, 0.15) 0%, transparent 70%);
}

/* 暖心问候 */
.warm-greeting {
  max-width: 600px;
  margin: 0 auto 20px;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.1), rgba(133, 206, 97, 0.05));
  border: 1px solid rgba(103, 194, 58, 0.2);
  border-radius: var(--radius-card);
  padding: 20px 25px;
  backdrop-filter: blur(10px);
  animation: fadeInDown 0.6s ease;
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.greeting-content {
  display: flex;
  align-items: center;
  gap: 15px;
}

.greeting-icon {
  font-size: 36px;
  animation: gentlePulse 2s ease-in-out infinite;
}

@keyframes gentlePulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.greeting-text {
  text-align: left;
}

.greeting-text h3 {
  color: var(--text-primary);
  font-size: 18px;
  margin-bottom: 5px;
  font-weight: 500;
}

.daily-quote {
  color: var(--text-secondary);
  font-size: 14px;
  font-style: italic;
}

/* 用户积分卡片 */
.user-points-card {
  max-width: 400px;
  margin: 0 auto 40px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(184, 134, 11, 0.15));
  border: 1px solid rgba(212, 175, 55, 0.3);
  border-radius: var(--radius-xl);
  padding: 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  backdrop-filter: blur(10px);
}

.points-display {
  display: flex;
  align-items: center;
  gap: 15px;
}

.points-icon {
  font-size: 36px;
}

.points-info {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.points-label {
  font-size: 12px;
  color: var(--text-secondary);
}

.points-info .points-value {
  font-size: 28px;
  font-weight: bold;
  color: var(--primary-color);
}

.points-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.points-btn {
  padding: 8px 16px;
  border-radius: var(--radius-xl);
  text-decoration: none;
  font-size: 13px;
  transition: all 0.3s ease;
  text-align: center;
}

.points-btn.checkin {
  background: var(--primary-gradient);
  color: var(--text-primary);
}

.points-btn:not(.checkin) {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
}

.points-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

/* 未登录欢迎卡片 */
.guest-welcome-card {
  max-width: 600px;
  margin: 0 auto 40px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.12), rgba(184, 134, 11, 0.12));
  border: 1px solid rgba(212, 175, 55, 0.25);
  border-radius: var(--radius-xl);
  padding: 30px;
  backdrop-filter: blur(10px);
}

.welcome-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  margin-bottom: 20px;
}

.welcome-icon {
  font-size: 48px;
}

.welcome-text {
  text-align: left;
}

.welcome-text h3 {
  color: var(--text-primary);
  font-size: 22px;
  margin-bottom: 5px;
}

.welcome-text p {
  color: var(--text-secondary);
  font-size: 14px;
}

.welcome-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
  margin-bottom: 20px;
}

.welcome-btn {
  padding: 10px 30px;
  height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-btn);
  text-decoration: none;
  font-size: 16px;
  transition: all 0.3s ease;
  box-sizing: border-box;
}


.welcome-btn.primary {
  background: var(--primary-gradient);
  color: var(--text-primary);
}

.welcome-btn.secondary {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
}

.welcome-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
}

.welcome-features {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
}

.feature-tag {
  background: var(--bg-secondary);
  padding: 6px 12px;
  border-radius: var(--radius-xl);
  font-size: 13px;
  color: var(--text-secondary);
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.hero-title {
  font-size: clamp(42px, 7vw, 60px);
  font-weight: var(--weight-black);
  line-height: 1.1;
  letter-spacing: var(--tracking-tight);
  margin-bottom: 20px;
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-subtitle {
  font-size: var(--font-body-lg);
  color: var(--text-secondary);
  margin-bottom: 40px;
  max-width: 640px;
  margin-left: auto;
  margin-right: auto;
  line-height: var(--line-height-base);
}

.hero-actions {
  display: flex;
  gap: 20px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-primary,
.btn-secondary {
  min-width: 190px;
}

.btn-icon {
  font-size: 18px;
}

.btn-badge {
  background: var(--success-gradient);
  color: var(--text-inverse);
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: var(--weight-semibold);
  margin-left: 5px;
}

.hero-hint {
  color: var(--text-tertiary);
  font-size: var(--font-small);
  margin-top: 20px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  line-height: var(--line-height-base);
}

.features {
  padding: 80px 0;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.feature-card {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-xl);
  padding: 40px 30px;
  text-align: center;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-card);
  transition: all 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-8px);
  background: var(--bg-card);
  border-color: var(--primary-light-30);
  box-shadow: var(--shadow-hover);
}

.feature-icon {
  font-size: 48px;
  margin-bottom: 20px;
}

.feature-card h3 {
  font-size: var(--font-h3);
  font-weight: var(--weight-bold);
  margin-bottom: 12px;
  color: var(--text-primary);
}

.feature-card p {
  color: var(--text-secondary);
  font-size: var(--font-body);
  line-height: var(--line-height-base);
  margin-bottom: 20px;
}

.feature-link {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: var(--weight-semibold);
  transition: all 0.3s ease;
}

.feature-link:hover {
  color: var(--primary-light);
}

.about {
  padding: 80px 0;
  background: var(--bg-secondary);
}

.about-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
}

.about-text p {
  color: var(--text-secondary);
  line-height: 1.8;
  margin-bottom: 20px;
}

.about-list {
  list-style: none;
}

.about-list li {
  color: var(--text-primary);
  padding: 10px 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.about-icon {
  color: var(--primary-color);
  font-weight: bold;
}

.about-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
}

.stat-item {
  text-align: center;
  padding: 30px 20px;
  background: var(--bg-card);
  border-radius: var(--radius-card);
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-card);
  transition: all 0.3s ease;
  perspective: 1000px;
}

.stat-icon-wrapper {
  width: 60px;
  height: 60px;
  background: rgba(var(--primary-rgb), 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  color: var(--primary-color);
  font-size: 28px;
  transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
  -webkit-transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
  perspective: 1000px;
  -webkit-perspective: 1000px;
}

.stat-item:hover .stat-icon-wrapper {
  background: var(--primary-color);
  color: var(--text-inverse);
  transform: rotateY(360deg) scale(1.1);
  -webkit-transform: rotateY(360deg) scale(1.1);
  /* 针对不支持 rotateY 的旧版浏览器的降级方案 */
  box-shadow: 0 0 15px rgba(var(--primary-rgb), 0.3);
}

.stat-number {
  display: block;
  font-size: clamp(30px, 4vw, 38px);
  font-weight: var(--weight-black);
  line-height: 1.1;
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 10px;
}

.stat-label {
  color: var(--text-secondary);
  font-size: var(--font-small);
  line-height: var(--line-height-base);
}

/* 用户评价区域 */
.testimonials {
  padding: 80px 0;
  background: var(--bg-secondary);
}

.testimonials .section-title {
  text-align: center;
  margin-bottom: 50px;
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
}

.testimonial-card {
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-xl);
  padding: 25px;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-card);
}

.testimonial-card:hover {
  transform: translateY(-5px);
  background: var(--bg-card);
  border-color: var(--primary-light-30);
  box-shadow: var(--shadow-hover);
}

.testimonial-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}

.testimonial-avatar {
  width: 50px;
  height: 50px;
  border-radius: var(--radius-round);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: var(--primary-light);
  font-weight: var(--weight-bold);
  border: 2px solid var(--primary-light-20);
}

.testimonial-info h4 {
  color: var(--text-primary);
  font-size: var(--font-body);
  font-weight: var(--weight-semibold);
  margin-bottom: 5px;
}

.testimonial-rating {
  display: flex;
  gap: 3px;
}

.testimonial-rating .star {
  color: var(--border-color);
  font-size: 14px;
}

.testimonial-rating .star.filled {
  color: var(--star-color);
}

.testimonial-content {
  color: var(--text-secondary);
  line-height: var(--line-height-base);
  font-size: var(--font-small);
  margin-bottom: 15px;
}

.testimonial-service {
  display: flex;
  justify-content: flex-end;
}

.service-tag {
  background: rgba(var(--primary-rgb), 0.12);
  color: var(--primary-color);
  padding: 4px 12px;
  border-radius: var(--radius-xl);
  font-size: 12px;
}

@media (prefers-reduced-motion: reduce) {
  .warm-greeting,
  .greeting-icon,
  .points-btn,
  .welcome-btn,
  .feature-card,
  .testimonial-card,
  .stat-icon-wrapper {
    animation: none !important;
    transition: none !important;
  }

  .points-btn:hover,
  .welcome-btn:hover,
  .feature-card:hover,
  .testimonial-card:hover,
  .stat-item:hover .stat-icon-wrapper {
    transform: none !important;
  }

  .stat-item:hover .stat-icon-wrapper {
    box-shadow: none;
  }
}

@media (max-width: 992px) {

  .features-grid {
    grid-template-columns: 1fr;
  }
  
  .testimonials-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .about-content {
    grid-template-columns: 1fr;
  }
  
  .hero-title {
    font-size: 40px;
  }
}

@media (max-width: 768px) {
  .about-stats {
    grid-template-columns: 1fr;
  }
  
  .testimonials-grid {
    grid-template-columns: 1fr;
  }
  
  .hero-actions {
    flex-direction: column;
    align-items: center;
  }
  
  .welcome-actions {
    flex-direction: column;
  }
  
  .welcome-btn {
    width: 100%;
    text-align: center;
  }
}
</style>
