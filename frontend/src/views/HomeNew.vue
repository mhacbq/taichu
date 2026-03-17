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
              <el-icon><StarFilled /></el-icon>
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
              <el-icon><DataLine /></el-icon>
              今日运势概览
            </h2>
            <div class="overview-cards">
              <div class="overview-card">
                <div class="card-icon"><el-icon><Aim /></el-icon></div>
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
                <div class="card-icon"><el-icon><Briefcase /></el-icon></div>
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
                <div class="card-icon"><el-icon><Money /></el-icon></div>
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
                <div class="card-icon"><el-icon><Star /></el-icon></div>
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
              <el-icon><Calendar /></el-icon>
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
            <el-icon><StarFilled /></el-icon>
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
              <div class="feature-icon">
                <el-icon v-if="feature.icon === 'aim'"><Aim /></el-icon>
                <el-icon v-else-if="feature.icon === 'magic'"><Magic /></el-icon>
                <el-icon v-else-if="feature.icon === 'cpu'"><Cpu /></el-icon>
                <el-icon v-else-if="feature.icon === 'cellphone'"><Cellphone /></el-icon>
                <el-icon v-else-if="feature.icon === 'lock'"><Lock /></el-icon>
                <el-icon v-else-if="feature.icon === 'present'"><Present /></el-icon>
              </div>
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
            <span class="badge"><el-icon><Lock /></el-icon> 数据安全</span>
            <span class="badge"><el-icon><CircleCheck /></el-icon> 专业准确</span>
            <span class="badge"><el-icon><StarFilled /></el-icon> 贴心服务</span>
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
import { StarFilled, DataLine, Aim, Magic, Cpu, Cellphone, Lock, Present, Calendar, Briefcase, Money, Star, CircleCheck } from '@element-plus/icons-vue'

const isLoggedIn = ref(false)

const features = [
  {
    icon: 'aim',
    title: '精准分析',
    description: '基于传统命理学，结合现代算法，为你提供精准的运势分析',
  },
  {
    icon: 'magic',
    title: '多元服务',
    description: '八字排盘、塔罗占卜、每日运势，满足你的各种需求',
  },
  {
    icon: 'cpu',
    title: 'AI 解读',
    description: '智能AI深度解读，让复杂的命理变得通俗易懂',
  },
  {
    icon: 'cellphone',
    title: '随时查看',
    description: '多端同步，随时随地查看你的运势和历史记录',
  },
  {
    icon: 'lock',
    title: '隐私保护',
    description: '严格的数据加密，保护你的个人信息安全',
  },
  {
    icon: 'present',
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
  background: 
    radial-gradient(circle at 50% 40%, rgba(184, 134, 11, 0.25) 0%, transparent 60%),
    radial-gradient(circle at 20% 30%, rgba(212, 175, 55, 0.12) 0%, transparent 40%),
    radial-gradient(circle at 80% 70%, rgba(184, 134, 11, 0.12) 0%, transparent 40%);
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
  color: var(--text-primary);
  line-height: 1.3;
}

.title-line.highlight {
  background: linear-gradient(135deg, #B8860B, #D4AF37, #F4E4C1);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-subtitle {
  font-size: 18px;
  color: var(--text-secondary);
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
  justify-content: center;
  gap: 8px;
  padding: 12px 32px;
  min-height: 44px;
  background: var(--primary-gradient);
  color: var(--text-primary);
  text-decoration: none;
  border-radius: var(--radius-btn);
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  font-size: 16px;
  box-shadow: var(--shadow-md);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(184, 134, 11, 0.4);
}

.btn-primary.large {
  padding: 14px 40px;
  min-height: 52px;
  font-size: 18px;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 32px;
  min-height: 44px;
  background: var(--bg-hover);
  color: var(--text-primary);
  text-decoration: none;
  border-radius: var(--radius-btn);
  font-weight: 500;
  transition: all 0.3s ease;
  border: 1px solid var(--border-color);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
  border-color: var(--primary-color);
}

.hero-stats {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 32px;
}

.stat-item {
  text-align: center;
  padding: 15px 25px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  transition: all 0.3s ease;
}

.stat-item:hover {
  background: rgba(255, 255, 255, 0.06);
  transform: translateY(-5px);
  border-color: var(--primary-light-30);
  box-shadow: var(--shadow-md);
}

.stat-number {
  display: block;
  font-size: 32px;
  font-weight: bold;
  color: var(--primary-light);
  margin-bottom: 4px;
}

.stat-label {
  font-size: 14px;
  color: var(--text-secondary);
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: var(--border-color);
}

/* Section Styles */
.section {
  padding: 80px 20px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.section-title {
  color: var(--text-primary);
  font-size: 28px;
  margin: 0 0 32px 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-title.center {
  justify-content: center;
}

/* Fortune Overview */
.fortune-overview {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-lg);
  padding: 30px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
}

.overview-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.overview-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  background: rgba(255, 255, 255, 0.03);
  border-radius: var(--radius-md);
  transition: all 0.3s ease;
  border: 1px solid var(--border-light);
}

.overview-card:hover {
  background: var(--bg-hover);
  transform: translateY(-2px);
  border-color: var(--primary-light-30);
}

.card-icon {
  font-size: 28px;
  color: var(--primary-color);
}

.card-info {
  flex: 1;
}

.card-label {
  display: block;
  font-size: 13px;
  color: var(--text-secondary);
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
  color: var(--text-primary);
}

.score-bar {
  flex: 1;
  height: 6px;
  background: var(--border-light);
  border-radius: 3px;
  overflow: hidden;
}

.score-fill {
  height: 100%;
  background: var(--primary-gradient);
  border-radius: 3px;
}

.score-fill.high {
  background: var(--success-gradient);
}

.score-fill.medium {
  background: var(--warning-gradient);
}

/* Two Columns */
.two-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
}

/* Calendar Section */
.calendar-section {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-lg);
  padding: 30px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
}

/* Features Section */
.features-section {
  background: linear-gradient(180deg, transparent, rgba(184, 134, 11, 0.05), transparent);
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
}

.feature-card {
  text-align: center;
  padding: 40px 30px;
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-6px);
  border-color: var(--primary-light-40);
  box-shadow: var(--shadow-xl);
  background: rgba(255, 255, 255, 0.08);
}

.feature-icon {
  font-size: 48px;
  margin-bottom: 20px;
  color: var(--primary-color);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  background: rgba(184, 134, 11, 0.1);
  border-radius: var(--radius-round);
}

.feature-title {
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 600;
  margin: 0 0 16px 0;
}

.feature-desc {
  color: var(--text-secondary);
  font-size: 15px;
  line-height: 1.7;
  margin: 0;
}

/* CTA Section */
.cta-section {
  padding: 100px 20px;
}

.cta-content {
  text-align: center;
  max-width: 800px;
  margin: 0 auto;
  padding: 60px;
  background: linear-gradient(135deg, rgba(184, 134, 11, 0.15), rgba(212, 175, 55, 0.1));
  border-radius: var(--radius-xl);
  border: 1px solid var(--primary-light-30);
  box-shadow: var(--shadow-xl);
}

.cta-title {
  color: var(--text-primary);
  font-size: 36px;
  font-weight: bold;
  margin: 0 0 20px 0;
}

.cta-desc {
  color: var(--text-secondary);
  font-size: 18px;
  margin: 0 0 40px 0;
}

.cta-actions {
  margin-bottom: 40px;
}

.trust-badges {
  display: flex;
  justify-content: center;
  gap: 30px;
}

.badge {
  color: var(--primary-light);
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

/* Responsive */
@media (max-width: 992px) {
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
    width: 100%;
    max-width: 300px;
    margin-left: auto;
    margin-right: auto;
  }
  
  .btn-primary, .btn-secondary {
    width: 100%;
  }
  
  .hero-stats {
    flex-direction: column;
    gap: 16px;
  }
  
  .stat-divider {
    display: none;
  }
  
  .stat-item {
    width: 100%;
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
    gap: 16px;
    align-items: center;
  }
  
  .cta-content {
    padding: 40px 20px;
  }
}
</style>
