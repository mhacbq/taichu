<template>
  <div class="home-page">
    <!-- 星空背景 -->
    <StarBackground />
    
    <!-- Hero 区域 -->
    <section class="hero-section">
      <ScrollReveal animation="fade-up">
        <div class="hero-content">
          <h1 class="hero-title">
            <span class="title-line">探索命运的</span>
            <span class="title-line highlight">奥秘之旅</span>
          </h1>
          <p class="hero-subtitle">
            通过八字排盘、塔罗占卜，发现属于你的运势密码
          </p>
          <div class="hero-actions">
            <router-link to="/bazi" class="btn-primary">
              <span>✨</span>
              开始探索
            </router-link>
            <router-link to="/daily" class="btn-secondary">
              查看今日运势
            </router-link>
          </div>
          
          <!-- 数据统计 -->
          <div class="hero-stats">
            <div class="stat-item">
              <span class="stat-number">10万+</span>
              <span class="stat-label">用户信赖</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <span class="stat-number">50万+</span>
              <span class="stat-label">排盘次数</span>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-item">
              <span class="stat-number">98%</span>
              <span class="stat-label">好评率</span>
            </div>
          </div>
        </div>
      </ScrollReveal>
    </section>

    <!-- 快捷入口 -->
    <section class="section">
      <div class="container">
        <ScrollReveal animation="fade-up" :delay="100">
          <QuickActions />
        </ScrollReveal>
      </div>
    </section>

    <!-- 今日运势概览 -->
    <section class="section" v-if="isLoggedIn">
      <div class="container">
        <ScrollReveal animation="fade-up" :delay="200">
          <div class="fortune-overview">
            <h2 class="section-title">
              <span>📊</span>
              今日运势概览
            </h2>
            <div class="overview-cards">
              <div class="overview-card">
                <div class="card-icon">🎯</div>
                <div class="card-info">
                  <span class="card-label">综合运势</span>
                  <div class="card-score">
                    <span class="score">8.5</span>
                    <div class="score-bar">
                      <div class="score-fill" style="width: 85%"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="overview-card">
                <div class="card-icon">💼</div>
                <div class="card-info">
                  <span class="card-label">事业运</span>
                  <div class="card-score">
                    <span class="score">9.0</span>
                    <div class="score-bar">
                      <div class="score-fill high" style="width: 90%"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="overview-card">
                <div class="card-icon">💰</div>
                <div class="card-info">
                  <span class="card-label">财运</span>
                  <div class="card-score">
                    <span class="score">7.5</span>
                    <div class="score-bar">
                      <div class="score-fill medium" style="width: 75%"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="overview-card">
                <div class="card-icon">💕</div>
                <div class="card-info">
                  <span class="card-label">感情运</span>
                  <div class="card-score">
                    <span class="score">8.0</span>
                    <div class="score-bar">
                      <div class="score-fill" style="width: 80%"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </ScrollReveal>
      </div>
    </section>

    <!-- 心情日记 & 成就中心 -->
    <section class="section" v-if="isLoggedIn">
      <div class="container">
        <div class="two-columns">
          <ScrollReveal animation="fade-left" :delay="100">
            <MoodTracker />
          </ScrollReveal>
          <ScrollReveal animation="fade-right" :delay="200">
            <AchievementSystem />
          </ScrollReveal>
        </div>
      </div>
    </section>

    <!-- 运势日历 -->
    <section class="section" v-if="isLoggedIn">
      <div class="container">
        <ScrollReveal animation="fade-up">
          <div class="calendar-section">
            <h2 class="section-title">
              <span>📅</span>
              运势日历
            </h2>
            <FortuneCalendar />
          </div>
        </ScrollReveal>
      </div>
    </section>

    <!-- 功能特色 -->
    <section class="section features-section">
      <div class="container">
        <ScrollReveal animation="fade-up">
          <h2 class="section-title center">
            <span>✨</span>
            为什么选择太初命理
          </h2>
        </ScrollReveal>
        
        <div class="features-grid">
          <ScrollReveal
            v-for="(feature, index) in features"
            :key="feature.title"
            animation="fade-up"
            :delay="100 + index * 100"
          >
            <div class="feature-card">
              <div class="feature-icon">{{ feature.icon }}</div>
              <h3 class="feature-title">{{ feature.title }}</h3>
              <p class="feature-desc">{{ feature.description }}</p>
            </div>
          </ScrollReveal>
        </div>
      </div>
    </section>

    <!-- CTA 区域 -->
    <section class="section cta-section">
      <ScrollReveal animation="zoom">
        <div class="cta-content">
          <h2 class="cta-title">开启你的命理之旅</h2>
          <p class="cta-desc">立即注册，免费获取你的第一份八字分析报告</p>
          <div class="cta-actions">
            <router-link to="/login" class="btn-primary large">
              免费注册
            </router-link>
          </div>
          <div class="trust-badges">
            <span class="badge">🔒 数据安全</span>
            <span class="badge">✓ 专业准确</span>
            <span class="badge">💝 贴心服务</span>
          </div>
        </div>
      </ScrollReveal>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import StarBackground from '../components/StarBackground.vue'
import ScrollReveal from '../components/ScrollReveal.vue'
import QuickActions from '../components/QuickActions.vue'
import MoodTracker from '../components/MoodTracker.vue'
import AchievementSystem from '../components/AchievementSystem.vue'
import FortuneCalendar from '../components/FortuneCalendar.vue'

const isLoggedIn = ref(false)

const features = [
  {
    icon: '🎯',
    title: '精准分析',
    description: '基于传统命理学，结合现代算法，为你提供精准的运势分析',
  },
  {
    icon: '🔮',
    title: '多元服务',
    description: '八字排盘、塔罗占卜、每日运势，满足你的各种需求',
  },
  {
    icon: '🤖',
    title: 'AI 解读',
    description: '智能AI深度解读，让复杂的命理变得通俗易懂',
  },
  {
    icon: '📱',
    title: '随时查看',
    description: '多端同步，随时随地查看你的运势和历史记录',
  },
  {
    icon: '🔒',
    title: '隐私保护',
    description: '严格的数据加密，保护你的个人信息安全',
  },
  {
    icon: '🎁',
    title: '免费体验',
    description: '新用户注册即送积分，免费体验多项功能',
  },
]

onMounted(() => {
  const token = localStorage.getItem('token')
  isLoggedIn.value = !!token
})
</script>

<style scoped>
.home-page {
  min-height: 100vh;
}

/* Hero Section */
.hero-section {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 20px;
  position: relative;
}

.hero-content {
  text-align: center;
  max-width: 700px;
}

.hero-title {
  margin: 0 0 24px 0;
}

.title-line {
  display: block;
  font-size: 48px;
  font-weight: bold;
  color: #fff;
  line-height: 1.3;
}

.title-line.highlight {
  background: linear-gradient(135deg, #e94560, #ff6b6b, #feca57);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-subtitle {
  font-size: 18px;
  color: rgba(255, 255, 255, 0.7);
  margin: 0 0 32px 0;
  line-height: 1.6;
}

.hero-actions {
  display: flex;
  gap: 16px;
  justify-content: center;
  margin-bottom: 48px;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 16px 32px;
  background: linear-gradient(135deg, #e94560, #ff6b6b);
  color: #fff;
  text-decoration: none;
  border-radius: 30px;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  font-size: 16px;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(184, 134, 11, 0.4);
}

.btn-primary.large {
  padding: 18px 40px;
  font-size: 18px;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  padding: 16px 32px;
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  text-decoration: none;
  border-radius: 30px;
  font-weight: 500;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.hero-stats {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 32px;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 28px;
  font-weight: bold;
  color: #fff;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.5);
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: rgba(255, 255, 255, 0.1);
}

/* Section Styles */
.section {
  padding: 60px 20px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.section-title {
  color: #fff;
  font-size: 24px;
  margin: 0 0 24px 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-title.center {
  justify-content: center;
}

/* Fortune Overview */
.fortune-overview {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.overview-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.overview-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  transition: all 0.3s ease;
}

.overview-card:hover {
  background: rgba(255, 255, 255, 0.08);
  transform: translateY(-2px);
}

.card-icon {
  font-size: 28px;
}

.card-info {
  flex: 1;
}

.card-label {
  display: block;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.5);
  margin-bottom: 6px;
}

.card-score {
  display: flex;
  align-items: center;
  gap: 8px;
}

.score {
  font-size: 20px;
  font-weight: bold;
  color: #fff;
}

.score-bar {
  flex: 1;
  height: 4px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
  overflow: hidden;
}

.score-fill {
  height: 100%;
  background: linear-gradient(90deg, #e94560, #ff6b6b);
  border-radius: 2px;
}

.score-fill.high {
  background: linear-gradient(90deg, #52c41a, #73d13d);
}

.score-fill.medium {
  background: linear-gradient(90deg, #faad14, #ffc53d);
}

/* Two Columns */
.two-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
}

/* Calendar Section */
.calendar-section {
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Features Section */
.features-section {
  background: linear-gradient(180deg, transparent, rgba(233, 69, 96, 0.05), transparent);
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.feature-card {
  text-align: center;
  padding: 32px 24px;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-4px);
  border-color: rgba(233, 69, 96, 0.3);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.feature-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.feature-title {
  color: #fff;
  font-size: 18px;
  margin: 0 0 12px 0;
}

.feature-desc {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
}

/* CTA Section */
.cta-section {
  padding: 100px 20px;
}

.cta-content {
  text-align: center;
  max-width: 600px;
  margin: 0 auto;
  padding: 48px;
  background: linear-gradient(135deg, rgba(233, 69, 96, 0.1), rgba(107, 70, 193, 0.1));
  border-radius: 24px;
  border: 1px solid rgba(233, 69, 96, 0.2);
}

.cta-title {
  color: #fff;
  font-size: 32px;
  margin: 0 0 16px 0;
}

.cta-desc {
  color: rgba(255, 255, 255, 0.7);
  font-size: 16px;
  margin: 0 0 32px 0;
}

.cta-actions {
  margin-bottom: 32px;
}

.trust-badges {
  display: flex;
  justify-content: center;
  gap: 24px;
}

.badge {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
}

/* Responsive */
@media (max-width: 1024px) {
  .overview-cards {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .features-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .title-line {
    font-size: 36px;
  }
  
  .hero-actions {
    flex-direction: column;
    align-items: center;
  }
  
  .hero-stats {
    flex-direction: column;
    gap: 16px;
  }
  
  .stat-divider {
    width: 80px;
    height: 1px;
  }
  
  .overview-cards {
    grid-template-columns: 1fr;
  }
  
  .two-columns {
    grid-template-columns: 1fr;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
  
  .trust-badges {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
