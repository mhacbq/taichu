<template>
  <div class="home">
    <GuideModal />
    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
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
            <span class="welcome-icon">👋</span>
            <div class="welcome-text">
              <h3>欢迎来到太初命理</h3>
              <p>登录即可体验专业命理分析服务</p>
            </div>
          </div>
          <div class="welcome-actions">
            <router-link to="/login" class="welcome-btn primary">立即登录</router-link>
            <router-link to="/login" class="welcome-btn secondary">注册领100积分</router-link>
          </div>
          <div class="welcome-features">
            <span class="feature-tag">🎁 新用户送100积分</span>
            <span class="feature-tag">✨ 免费每日运势</span>
            <span class="feature-tag">🔮 专业八字分析</span>
          </div>
        </div>
        
        <div class="hero-content">
          <h1 class="hero-title">探索命运的奥秘</h1>
          <p class="hero-subtitle">AI智能命理分析，为您提供专业的八字、塔罗、运势解读</p>
          <div class="hero-actions">
            <router-link to="/bazi" class="btn-primary">开始排盘</router-link>
            <router-link to="/tarot" class="btn-secondary">塔罗占卜</router-link>
          </div>
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
            <h3>八字排盘</h3>
            <p>精准的八字命理分析，解读您的人生轨迹、事业财运、婚姻感情</p>
            <router-link to="/bazi" class="feature-link">立即体验 →</router-link>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🎴</div>
            <h3>塔罗占卜</h3>
            <p>智能塔罗牌阵解读，为您的困惑指明方向，洞察未来可能</p>
            <router-link to="/tarot" class="feature-link">立即体验 →</router-link>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🌟</div>
            <h3>每日运势</h3>
            <p>基于生辰八字的每日运势分析，趋吉避凶，把握良机</p>
            <router-link to="/daily" class="feature-link">立即体验 →</router-link>
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
            <h2 class="section-title">关于太初命理</h2>
            <p>太初命理是一款结合传统命理学与人工智能技术的智能分析平台。我们致力于：</p>
            <ul class="about-list">
              <li>传承中华传统命理文化</li>
              <li>运用AI技术提供精准分析</li>
              <li>为用户提供个性化的命理指导</li>
              <li>让命理分析更加科学、便捷</li>
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
import { ref, onMounted } from 'vue'
import GuideModal from '../components/GuideModal.vue'
import { getHomeStats, getPointsBalance } from '../api'

const stats = ref([
  { number: '加载中...', label: '服务用户' },
  { number: '加载中...', label: '分析次数' },
  { number: '98%', label: '好评率' },
])

const isLoggedIn = ref(false)
const userPoints = ref(0)

// 用户评价数据
const testimonials = ref([
  {
    name: '张女士',
    avatar: '👩',
    rating: 5,
    content: '八字排盘非常详细，连藏干和十神都分析得很清楚。帮我更好地了解了自己的命理特点，对事业规划很有帮助！',
    service: '八字排盘'
  },
  {
    name: '李先生',
    avatar: '👨',
    rating: 5,
    content: '塔罗占卜给了我很准的指引，让我在做重要决定时更有信心了。界面设计也很精美，体验很好。',
    service: '塔罗占卜'
  },
  {
    name: '小王',
    avatar: '👦',
    rating: 5,
    content: '每天看运势已经成为习惯了，感觉比一般的星座运势更准。签到还能领积分，很良心！',
    service: '每日运势'
  },
  {
    name: '陈小姐',
    avatar: '👩‍💼',
    rating: 5,
    content: '作为命理爱好者，这个平台的算法很专业。纳音五行、十神分析都很到位，推荐给大家！',
    service: '八字排盘'
  },
  {
    name: '刘先生',
    avatar: '👨‍💻',
    rating: 4,
    content: '功能很齐全，操作也很简单。希望可以推出更多命理分析功能，比如合婚、择日等。',
    service: '八字排盘'
  },
  {
    name: '赵女士',
    avatar: '👩‍🎨',
    rating: 5,
    content: '塔罗牌的解读很细致，不是那种笼统的套话。每次占卜都能得到有价值的建议，爱了爱了！',
    service: '塔罗占卜'
  }
])

const loadStats = async () => {
  try {
    const response = await getHomeStats()
    if (response.code === 0) {
      stats.value = response.data.stats
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
  padding: 80px 0 100px;
  text-align: center;
  background: radial-gradient(ellipse at center, rgba(233, 69, 96, 0.15) 0%, transparent 70%);
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
}

.btn-secondary:hover {
  border-color: #e94560;
  background: rgba(233, 69, 96, 0.1);
}

.features {
  padding: 80px 0;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
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
