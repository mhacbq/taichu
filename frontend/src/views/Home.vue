<template>
  <div class="home">
    <GuideModal />
    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
        <!-- 暖心问候语 - 已登录 -->
        <div v-if="isLoggedIn" class="warm-greeting">
          <div class="greeting-content">
            <span class="greeting-icon">{{ greetingIcon }}</span>
            <div class="greeting-text">
              <h3>{{ greetingText }}</h3>
              <p class="daily-quote">{{ dailyQuote }}</p>
            </div>
          </div>
        </div>
        
        <!-- 用户积分卡片 - 已登录 -->
        <div v-if="isLoggedIn" class="user-points-card">
          <div class="points-display">
            <span class="points-icon">💎</span>
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
            <span class="welcome-icon">🌸</span>
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
            <span class="feature-tag">🎁 新用户送100积分</span>
            <span class="feature-tag">✨ 首次排盘免费</span>
            <span class="feature-tag">🔮 八字塔罗每日运势</span>
          </div>
        </div>
        
        <div class="hero-content">
          <h1 class="hero-title">在迷茫中找到方向</h1>
          <p class="hero-subtitle">不是预测命运，而是帮你更懂自己<br>八字、塔罗、运势，为你的困惑寻找答案</p>
          <div class="hero-actions">
            <router-link to="/bazi" class="btn-primary">
              <span class="btn-icon">📅</span>
              开始排盘
              <span class="btn-badge">首测免费</span>
            </router-link>
            <router-link to="/tarot" class="btn-secondary">
              <span class="btn-icon">🎴</span>
              塔罗占卜
            </router-link>
          </div>
          <p class="hero-hint">💡 已有 {{ userCount }}+ 用户在这里找到答案</p>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features">
      <div class="container">
        <h2 class="section-title">我们的服务</h2>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">☯</div>
            <h3>八字分析</h3>
            <p>基于传统文化的性格分析，了解您的个性特点、发展方向、人际关系</p>
            <router-link to="/bazi" class="feature-link">立即体验 →</router-link>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🎴</div>
            <h3>塔罗测试</h3>
            <p>趣味塔罗牌阵探索，为您的困惑提供思考角度，发现内心可能</p>
            <router-link to="/tarot" class="feature-link">立即体验 →</router-link>
          </div>
          <div class="feature-card">
            <div class="feature-icon">☯</div>
            <h3>六爻占卜</h3>
            <p>传统周易六爻问事，为您解答工作、感情、决策等各类疑惑</p>
            <router-link to="/liuyao" class="feature-link">立即体验 →</router-link>
          </div>
          <div class="feature-card">
            <div class="feature-icon">💕</div>
            <h3>八字合婚</h3>
            <p>通过双方八字分析婚姻匹配度，了解缘分深浅与相处之道</p>
            <router-link to="/hehun" class="feature-link">立即体验 →</router-link>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🌟</div>
            <h3>每日指南</h3>
            <p>基于出生日期的每日幸运指数，生活参考，娱乐消遣</p>
            <router-link to="/daily" class="feature-link">立即体验 →</router-link>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3>更多功能</h3>
            <p>取名建议、吉日查询等更多命理功能，满足您的不同需求</p>
            <router-link to="/profile" class="feature-link">探索更多 →</router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- 用户评价 Section -->
    <section class="testimonials">
      <div class="container">
        <h2 class="section-title">用户心声</h2>
        <div class="testimonials-grid">
          <div class="testimonial-card" v-for="(item, index) in testimonials" :key="index">
            <div class="testimonial-header">
              <div class="testimonial-avatar">{{ item.avatar }}</div>
              <div class="testimonial-info">
                <h4>{{ item.name }}</h4>
                <div class="testimonial-rating">
                  <span v-for="n in 5" :key="n" class="star" :class="{ filled: n <= item.rating }">★</span>
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
              <li>传承中华传统历法文化</li>
              <li>运用AI技术提供趣味分析</li>
              <li>为用户提供个性化的性格参考</li>
              <li>让传统文化探索更加有趣、便捷</li>
            </ul>
          </div>
          <div class="about-stats">
            <div class="stat-item" v-for="stat in stats" :key="stat.label">
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

const stats = ref([
  { number: '加载中...', label: '服务用户' },
  { number: '加载中...', label: '分析次数' },
  { number: '98%', label: '好评率' },
])

const isLoggedIn = ref(false)
const userPoints = ref(0)
const userCount = ref(12000)

// 问候语数据
const hour = new Date().getHours()
const greetingIcon = computed(() => {
  if (hour < 12) return '🌅'
  if (hour < 18) return '☀️'
  return '🌙'
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
    avatar: '👩',
    rating: 5,
    content: '毕业后一直很迷茫，不知道自己适合什么工作。排盘后看到我的喜用神和适合的发展方向，突然有了方向感，现在已经在准备转行了！',
    service: '八字排盘'
  },
  {
    name: '阿杰',
    avatar: '👨',
    rating: 5,
    content: '感情遇到瓶颈期，塔罗给了我很大的启发。不是告诉我该怎么做，而是帮我理清了自己真正想要的是什么。现在已经和女友和好了。',
    service: '塔罗占卜'
  },
  {
    name: '小陈',
    avatar: '👦',
    rating: 5,
    content: '工作压力很大的时候，每天早上的运势推送成了我的精神支柱。有时候看到"今天适合休息"就会给自己放个假，感觉被理解了。',
    service: '每日运势'
  },
  {
    name: '琳琳',
    avatar: '👩‍💼',
    rating: 5,
    content: '作为INFJ，常常陷入自我怀疑。八字分析让我更接纳自己的性格特点，原来我生来就是这样，不是我有问题。',
    service: '八字排盘'
  },
  {
    name: '大鹏',
    avatar: '👨‍💻',
    rating: 5,
    content: '一直纠结要不要跳槽，塔罗占卜给了我很中肯的建议。现在的新工作虽然累但是很开心，很感谢当时的指引。',
    service: '塔罗占卜'
  },
  {
    name: '思思',
    avatar: '👩‍🎨',
    rating: 5,
    content: '第一次用的时候还半信半疑，但结果真的挺准的。尤其是大运分析，让我知道未来几年需要注意什么，心里有底多了。',
    service: '八字排盘'
  }
])

const loadStats = async () => {
  try {
    const response = await getHomeStats()
    if (response.code === 0) {
      stats.value = response.data.stats
      userCount.value = response.data.userCount || 12000
    }
  } catch (error) {
    console.error('加载统计数据失败:', error)
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
    if (response.code === 0) {
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
  background: radial-gradient(ellipse at center, rgba(233, 69, 96, 0.15) 0%, transparent 70%);
}

/* 暖心问候 */
.warm-greeting {
  max-width: 600px;
  margin: 0 auto 20px;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.15), rgba(133, 206, 97, 0.1));
  border: 1px solid rgba(103, 194, 58, 0.3);
  border-radius: 16px;
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
  color: #fff;
  font-size: 18px;
  margin-bottom: 5px;
  font-weight: 500;
}

.daily-quote {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
  font-style: italic;
}

/* 用户积分卡片 */
.user-points-card {
  max-width: 400px;
  margin: 0 auto 40px;
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.2), rgba(255, 107, 107, 0.2));
  border: 1px solid rgba(233, 69, 96, 0.3);
  border-radius: 20px;
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
  color: rgba(255, 255, 255, 0.6);
}

.points-value {
  font-size: 28px;
  font-weight: bold;
  color: #ffd700;
}

.points-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.points-btn {
  padding: 8px 16px;
  border-radius: 20px;
  text-decoration: none;
  font-size: 13px;
  transition: all 0.3s ease;
  text-align: center;
}

.points-btn.checkin {
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  color: #fff;
}

.points-btn:not(.checkin) {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.points-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

/* 未登录欢迎卡片 */
.guest-welcome-card {
  max-width: 600px;
  margin: 0 auto 40px;
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.15), rgba(255, 107, 107, 0.15));
  border: 1px solid rgba(233, 69, 96, 0.3);
  border-radius: 20px;
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
  color: #fff;
  font-size: 22px;
  margin-bottom: 5px;
}

.welcome-text p {
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
}

.welcome-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
  margin-bottom: 20px;
}

.welcome-btn {
  padding: 12px 30px;
  border-radius: 25px;
  text-decoration: none;
  font-size: 16px;
  transition: all 0.3s ease;
}

.welcome-btn.primary {
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  color: #fff;
}

.welcome-btn.secondary {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.welcome-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 20px rgba(233, 69, 96, 0.3);
}

.welcome-features {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
}

.feature-tag {
  background: rgba(255, 255, 255, 0.1);
  padding: 6px 12px;
  border-radius: 15px;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.8);
}

.hero-title {
  font-size: 56px;
  font-weight: bold;
  margin-bottom: 20px;
  background: linear-gradient(135deg, #fff 0%, #e94560 50%, #ffd700 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-subtitle {
  font-size: 20px;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 40px;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.hero-actions {
  display: flex;
  gap: 20px;
  justify-content: center;
}

.btn-secondary {
  background: transparent;
  border: 2px solid rgba(255, 255, 255, 0.3);
  padding: 12px 32px;
  border-radius: 25px;
  color: white;
  font-size: 16px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-secondary:hover {
  border-color: #e94560;
  background: rgba(233, 69, 96, 0.1);
}

.btn-icon {
  font-size: 18px;
}

.btn-badge {
  background: linear-gradient(135deg, #67c23a, #85ce61);
  color: #fff;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 11px;
  margin-left: 5px;
}

.hero-hint {
  color: rgba(255, 255, 255, 0.5);
  font-size: 14px;
  margin-top: 20px;
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
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 40px 30px;
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-10px);
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(233, 69, 96, 0.3);
}

.feature-icon {
  font-size: 48px;
  margin-bottom: 20px;
}

.feature-card h3 {
  font-size: 24px;
  margin-bottom: 15px;
  color: #fff;
}

.feature-card p {
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.6;
  margin-bottom: 20px;
}

.feature-link {
  color: #e94560;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.feature-link:hover {
  color: #ff6b6b;
}

.about {
  padding: 80px 0;
  background: rgba(0, 0, 0, 0.2);
}

.about-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
}

.about-text p {
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.8;
  margin-bottom: 20px;
}

.about-list {
  list-style: none;
}

.about-list li {
  color: rgba(255, 255, 255, 0.8);
  padding: 10px 0;
  padding-left: 25px;
  position: relative;
}

.about-list li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: #e94560;
  font-weight: bold;
}

.about-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
}

.stat-item {
  text-align: center;
  padding: 30px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 15px;
}

.stat-number {
  display: block;
  font-size: 36px;
  font-weight: bold;
  background: linear-gradient(135deg, #e94560, #ffd700);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 10px;
}

.stat-label {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

/* 用户评价区域 */
.testimonials {
  padding: 80px 0;
  background: rgba(0, 0, 0, 0.2);
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
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 25px;
  transition: all 0.3s ease;
}

.testimonial-card:hover {
  transform: translateY(-5px);
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(233, 69, 96, 0.3);
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
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.testimonial-info h4 {
  color: #fff;
  font-size: 16px;
  margin-bottom: 5px;
}

.testimonial-rating {
  display: flex;
  gap: 3px;
}

.testimonial-rating .star {
  color: rgba(255, 255, 255, 0.3);
  font-size: 14px;
}

.testimonial-rating .star.filled {
  color: #ffd700;
}

.testimonial-content {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.7;
  font-size: 14px;
  margin-bottom: 15px;
}

.testimonial-service {
  display: flex;
  justify-content: flex-end;
}

.service-tag {
  background: rgba(233, 69, 96, 0.2);
  color: #e94560;
  padding: 4px 12px;
  border-radius: 15px;
  font-size: 12px;
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

@media (max-width: 576px) {
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
