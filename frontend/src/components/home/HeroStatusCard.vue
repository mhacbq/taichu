<script setup>
import { computed } from 'vue'

const props = defineProps({
  isLoggedIn: Boolean,
  userPoints: Number,
  formattedUserPoints: String,
  baziOfferState: String,
  registerIntentRoute: Object,
  heroPointsDetail: String,
  heroPointsCardNote: String
})

const guestBenefits = [
  {
    key: 'points',
    icon: 'Present',
    title: '登录即领 100 积分',
    description: '先拿到体验额度，再决定最想优先探索哪一项服务。'
  },
  {
    key: 'free',
    icon: 'Star',
    title: '八字首测免费',
    description: '第一次排盘零门槛，适合先看看自己的整体节奏。'
  },
  {
    key: 'services',
    icon: 'MagicStick',
    title: '多种方式探索自己',
    description: '八字、塔罗与每日运势等入口现在都从首页顺着往下走。'
  }
]
</script>

<template>
  <div class="hero-status-card card">
    <div class="hero-status-head">
      <span class="hero-status-icon">
        <el-icon :size="24"><Sunrise /></el-icon>
      </span>
      <div class="hero-status-copy">
        <p class="hero-status-eyebrow">{{ isLoggedIn ? '今日状态' : '新用户欢迎' }}</p>
        <h3>{{ isLoggedIn ? '探索你的命理世界' : '先领积分，再慢慢探索' }}</h3>
      </div>
      <span 
        class="hero-status-badge"
        :class="isLoggedIn ? '' : 'hero-status-badge--soft'"
      >
        {{ isLoggedIn ? '已登录' : '未登录' }}
      </span>
    </div>
    
    <p class="hero-status-quote" :class="{ 'hero-status-quote--guest': !isLoggedIn }">
      <span v-if="isLoggedIn">{{ heroPointsDetail }}</span>
      <span v-else>先登录领取体验积分，再从八字、塔罗或每日运势里挑一个最想解决的问题开始。</span>
    </p>
    
    <div v-if="isLoggedIn" class="hero-points-panel">
      <div class="hero-points-display">
        <el-icon class="hero-points-icon" :size="28"><Coin /></el-icon>
        <div>
          <span class="hero-points-label">我的积分</span>
          <strong class="hero-points-value">{{ formattedUserPoints }}</strong>
        </div>
      </div>
      <p class="hero-points-note">{{ heroPointsCardNote }}</p>
    </div>
    
    <div v-else class="hero-benefits">
      <article class="hero-benefit" v-for="item in guestBenefits" :key="item.key">
        <el-icon><component :is="item.icon" /></el-icon>
        <div class="hero-benefit-copy">
          <strong>{{ item.title }}</strong>
          <span>{{ item.description }}</span>
        </div>
      </article>
    </div>
    
    <div class="hero-panel-actions">
      <router-link 
        :to="isLoggedIn ? '/profile' : '/login'" 
        class="hero-panel-btn hero-panel-btn--primary"
      >
        {{ isLoggedIn ? '签到领积分' : '立即登录' }}
      </router-link>
      <router-link 
        :to="isLoggedIn ? '/bazi' : registerIntentRoute" 
        class="hero-panel-btn hero-panel-btn--secondary"
      >
        {{ isLoggedIn ? '去排盘' : '注册领积分' }}
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.hero-status-card {
  width: 100%;
  padding: 28px;
  border-radius: var(--radius-card);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.97), rgba(255, 248, 236, 0.96));
  border: 1px solid rgba(227, 184, 104, 0.36);
  box-shadow: 0 18px 42px rgba(148, 109, 45, 0.16);
  backdrop-filter: blur(16px);
  display: grid;
  gap: 18px;
  align-items: start;
  position: relative;
  overflow: hidden;
}

.hero-status-card::after {
  content: '';
  position: absolute;
  inset: auto auto -72px -36px;
  width: 180px;
  height: 180px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(245, 196, 103, 0.12) 0%, rgba(245, 196, 103, 0) 72%);
  pointer-events: none;
}

.hero-status-head {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  position: relative;
  z-index: 1;
}

.hero-status-icon {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-lg);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(245, 196, 103, 0.2);
  color: #99661d;
}

.hero-status-icon--guest {
  background: rgba(245, 196, 103, 0.16);
}

.hero-status-copy {
  flex: 1;
}

.hero-status-eyebrow {
  margin: 0 0 4px;
  color: #7f7361;
  font-size: var(--font-tiny);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hero-status-copy h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: var(--font-h4);
  line-height: var(--line-height-tight);
}

.hero-status-badge {
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  background: linear-gradient(135deg, #e1ac4b 0%, #f3ca74 100%);
  color: #5e4318;
  font-size: var(--font-tiny);
  font-weight: var(--weight-semibold);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.hero-status-badge--soft {
  background: rgba(245, 196, 103, 0.16);
  border: 1px solid rgba(212, 156, 62, 0.32);
  color: #8d611f;
}

.hero-status-quote {
  margin: 0;
  padding: 14px 16px;
  border-radius: var(--radius-lg);
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(227, 184, 104, 0.28);
  color: #554a3c;
  font-size: var(--font-small);
  line-height: var(--line-height-base);
  position: relative;
  z-index: 1;
}

.hero-status-quote--guest {
  margin-bottom: 0;
}

.hero-points-panel {
  margin-top: 0;
  padding: 18px;
  border-radius: var(--radius-lg);
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(227, 184, 104, 0.28);
  position: relative;
  z-index: 1;
}

.hero-points-display {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 12px;
}

.hero-points-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #5e4318;
  background: linear-gradient(135deg, #e2af4f 0%, #f5cf79 100%);
  box-shadow: 0 10px 20px rgba(188, 136, 41, 0.24);
}

.hero-points-label {
  display: block;
  margin-bottom: 4px;
  color: #7a6e5c;
  font-size: var(--font-tiny);
}

.hero-points-value {
  display: block;
  color: #99661d;
  font-size: clamp(28px, 4vw, 34px);
  font-weight: var(--weight-black);
  line-height: 1;
}

.hero-points-note {
  margin: 0;
  color: #5f5446;
  font-size: var(--font-caption);
  line-height: 1.7;
}

.hero-benefits {
  display: grid;
  gap: 10px;
  margin-top: 0;
  position: relative;
  z-index: 1;
}

.hero-benefit {
  min-height: 64px;
  padding: 14px 16px;
  border-radius: var(--radius-lg);
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(227, 184, 104, 0.28);
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.hero-benefit .el-icon {
  color: #aa7221;
  margin-top: 2px;
  flex-shrink: 0;
}

.hero-benefit-copy {
  display: grid;
  gap: 4px;
}

.hero-benefit-copy strong {
  color: var(--text-primary);
  font-size: var(--font-small);
  font-weight: var(--weight-semibold);
  line-height: 1.5;
}

.hero-benefit-copy span {
  color: #6f6657;
  font-size: var(--font-caption);
  line-height: 1.7;
}

.hero-panel-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 0;
  position: relative;
  z-index: 1;
}

.hero-panel-btn {
  position: relative;
  min-height: 48px;
  padding: 0 18px;
  border-radius: var(--radius-btn);
  text-decoration: none;
  font-size: var(--font-btn);
  font-weight: var(--weight-semibold);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease, background-color 0.28s ease;
}

.hero-panel-btn--primary {
  background: linear-gradient(135deg, #e2af4f 0%, #f3c86f 100%);
  color: #5a3f17;
  border: 1px solid rgba(194, 140, 49, 0.4);
  box-shadow: 0 12px 24px rgba(186, 135, 41, 0.24), inset 0 1px 0 rgba(255, 255, 255, 0.6);
}

.hero-panel-btn--secondary {
  background: rgba(255, 255, 255, 0.98);
  color: #3d3428;
  border: 1px solid rgba(227, 184, 104, 0.32);
  box-shadow: 0 10px 22px rgba(149, 111, 45, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.hero-panel-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 30px rgba(149, 111, 45, 0.2);
}
</style>
