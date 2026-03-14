<template>
  <div class="home">
    <GuideModal />
    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
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
import { getHomeStats } from '../api'

const stats = ref([
  { number: '加载中...', label: '服务用户' },
  { number: '加载中...', label: '分析次数' },
  { number: '98%', label: '好评率' },
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

onMounted(() => {
  loadStats()
})
</script>

<style scoped>
.hero {
  padding: 120px 0 100px;
  text-align: center;
  background: radial-gradient(ellipse at center, rgba(233, 69, 96, 0.15) 0%, transparent 70%);
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

@media (max-width: 992px) {
  .features-grid {
    grid-template-columns: 1fr;
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
  
  .hero-actions {
    flex-direction: column;
    align-items: center;
  }
}
</style>
