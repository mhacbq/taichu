<template>
  <div class="home">
    <GuideModal />
    <!-- Hero Section -->
    <section class="hero">
      <div class="container hero-shell">
        <div class="hero-main">
          <span class="hero-kicker">传统文化探索 · 年轻人的人生参考</span>
          <h1 class="hero-title">在迷茫中找到方向</h1>
          <p class="hero-subtitle">不是替你决定命运，而是帮你看清自己。<br>从八字、塔罗到每日运势，把困惑拆成更容易行动的下一步。</p>
          <div class="hero-actions">
            <router-link to="/bazi" class="btn-primary">
              <el-icon class="btn-icon"><Calendar /></el-icon>
              八字排盘
            </router-link>
            <router-link to="/tarot" class="btn-secondary">
              <el-icon class="btn-icon"><Star /></el-icon>
              塔罗占卜
            </router-link>
          </div>

          <!-- 积分说明条 -->
          <div class="hero-points-strip">
            <span class="points-strip-item">🎁 注册即送积分</span>
            <span class="points-strip-sep">·</span>
            <span class="points-strip-item">📅 每日签到领积分</span>
            <span class="points-strip-sep">·</span>
            <span class="points-strip-item">⚡ 积分解锁所有功能</span>
          </div>
        </div>

        <aside class="hero-side">
          <div v-if="isLoggedIn" class="hero-status-card card">
            <div class="hero-status-head">
              <span class="hero-status-icon">
                <el-icon v-if="greetingIcon === 'morning'" :size="24"><Sunrise /></el-icon>
                <el-icon v-else-if="greetingIcon === 'afternoon'" :size="24"><Sunny /></el-icon>
                <el-icon v-else :size="24"><Moon /></el-icon>
              </span>
              <div class="hero-status-copy">
                <p class="hero-status-eyebrow">今日状态</p>
                <h3>{{ greetingText }}</h3>
              </div>
              <span class="hero-status-badge">已登录</span>
            </div>
            <p class="hero-status-quote">{{ dailyQuote }}</p>
            <div class="hero-points-panel">
              <div class="hero-points-display">
                <el-icon class="hero-points-icon" :size="28"><Coin /></el-icon>
                <div>
                  <span class="hero-points-label">我的积分</span>
                  <strong class="hero-points-value">{{ formattedUserPoints }}</strong>
                </div>
              </div>
              <p class="hero-points-note">{{ heroPointsCardNote }}</p>
            </div>
            <div class="hero-panel-actions">
              <router-link to="/profile" class="hero-panel-btn hero-panel-btn--primary">签到领积分</router-link>
              <router-link to="/bazi" class="hero-panel-btn hero-panel-btn--secondary">去排盘</router-link>
            </div>
          </div>

          <div v-else class="hero-status-card card">
            <div class="hero-status-head">
              <span class="hero-status-icon hero-status-icon--guest">
                <el-icon :size="24"><Cherry /></el-icon>
              </span>
              <div class="hero-status-copy">
                <p class="hero-status-eyebrow">新用户欢迎</p>
                <h3>先领积分，再慢慢探索</h3>
              </div>
              <span class="hero-status-badge hero-status-badge--soft">未登录</span>
            </div>
            <p class="hero-status-quote hero-status-quote--guest">先登录领取体验积分，再从八字、塔罗或每日运势里挑一个最想解决的问题开始。</p>
            <div class="hero-benefits">
              <article class="hero-benefit" v-for="item in guestBenefits" :key="item.key">
                <el-icon><component :is="item.icon" /></el-icon>
                <div class="hero-benefit-copy">
                  <strong>{{ item.title }}</strong>
                  <span>{{ item.description }}</span>
                </div>
              </article>
            </div>
            <div class="hero-panel-actions">
              <router-link to="/login" class="hero-panel-btn hero-panel-btn--primary">立即登录</router-link>
              <router-link :to="registerIntentRoute" class="hero-panel-btn hero-panel-btn--secondary">注册领积分</router-link>
            </div>
          </div>
        </aside>
      </div>
    </section>
    <section class="feedback">
      <div class="container">
        <div class="hero-highlights" :class="{ 'hero-highlights--muted': statsLoading || statsError }">
          <article class="hero-highlight" v-for="item in heroProofItems" :key="item.key">
                <span class="hero-highlight-icon">
                  <el-icon><component :is="item.icon" /></el-icon>
                </span>
            <div class="hero-highlight-copy">
              <strong>{{ item.label }}</strong>
              <span>{{ item.description }}</span>
            </div>
          </article>
        </div>

        <div class="hero-highlights">
          <article class="hero-highlight" v-for="item in heroAccessItems" :key="item.key">
                <span class="hero-highlight-icon">
                  <el-icon><component :is="item.icon" /></el-icon>
                </span>
            <div class="hero-highlight-copy">
              <strong>{{ item.title }}</strong>
              <span>{{ item.detail }}</span>
            </div>
          </article>
        </div>
      </div>

    </section>
    <!-- Features Section -->
    <section class="features">
      <div class="container">
        <h2 class="section-title">选择你想探索的方向</h2>
        <p class="section-subtitle">核心功能消耗积分，注册即送，每日签到可持续领取</p>

        <!-- 时效性引流位：年度运程 -->
        <div class="yearly-banner card-hover" @click="$router.push('/yearly-fortune')">
          <div class="yearly-banner-content">
            <div class="yearly-banner-badge">{{ yearlyBannerBadge }}</div>
            <h3 class="yearly-banner-title">{{ yearlyBannerTitle }}</h3>
            <p class="yearly-banner-desc">提前布局，把握先机。结合个人八字，AI 深度解析 {{ currentYear }} 年的事业、财富、感情、健康四大运势，提供专属开运建议与每月吉凶提醒。</p>
            <div class="yearly-banner-action">
              <router-link to="/yearly-fortune" class="yearly-banner-btn">
                立即测算 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
          </div>
          <div class="yearly-banner-bg">
            <el-icon class="yearly-banner-icon"><Calendar /></el-icon>
          </div>
        </div>

        <div class="features-grid">
          <!-- 主功能 3个 -->
          <div class="feature-card feature-card--primary card-hover" data-type="bazi">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">☯</span>
            </div>
            <h3>八字排盘</h3>
            <p>基于传统四柱信息，帮助你梳理性格节奏、发展方向与长期规划参考</p>
            <router-link to="/bazi" class="feature-link">
              立即测算 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card feature-card--primary card-hover" data-type="tarot">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">✴</span>
            </div>
            <h3>塔罗占卜</h3>
            <p>通过牌阵与问题模板梳理关系、工作与决策困惑，获得更聚焦的思路</p>
            <router-link to="/tarot" class="feature-link">
              立即测算 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card feature-card--primary card-hover" data-type="daily">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">◉</span>
            </div>
            <h3>每日运势</h3>
            <p>查看今日宜忌、幸运提示与节奏建议，作为轻量的日常状态参考</p>
            <router-link to="/daily" class="feature-link">
              立即查看 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>

          <!-- 次要功能 6个 -->
          <div class="feature-card card-hover" data-type="liuyao">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">☰</span>
            </div>
            <h3>六爻占卜</h3>
            <p>传统周易六爻问事，为您解答工作、感情、决策等各类疑惑</p>
            <div style="text-align: center; margin-top: 16px;">
              <router-link to="/liuyao" class="feature-link feature-link--sm">
                立即测算 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
          </div>
          <div class="feature-card card-hover" data-type="hehun">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">◎</span>
            </div>
            <h3>八字合婚</h3>
            <p>通过双方八字分析婚姻匹配度，了解缘分深浅与相处之道</p>
            <div style="text-align: center; margin-top: 16px;">
              <router-link to="/hehun" class="feature-link feature-link--sm">
                立即测算 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
          </div>
          <div class="feature-card card-hover" data-type="profile">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">★</span>
            </div>
            <h3>个人中心</h3>
            <p>查看历史记录、每日签到领积分，管理你的命理体验进度</p>
            <div style="text-align: center; margin-top: 16px;">
              <router-link to="/profile" class="feature-link feature-link--sm">
                进入个人中心 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
          </div>

          <!-- 取名建议 -->
          <div class="feature-card card-hover" data-type="qiming">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">✍️</span>
            </div>
            <h3>取名建议</h3>
            <p>结合生辰八字与五行，由AI为新生儿推荐寓意美好的名字</p>
            <div style="text-align: center; margin-top: 16px;">
              <router-link to="/qiming" class="feature-link feature-link--sm">
                立即测算 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
          </div>

          <!-- 流年运势 -->
          <div class="feature-card card-hover" data-type="yearly">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">🔮</span>
            </div>
            <h3>流年运势</h3>
            <p>结合个人八字，深度解析全年运势，提供每月吉凶提醒与开运建议</p>
            <div style="text-align: center; margin-top: 16px;">
              <router-link to="/yearly-fortune" class="feature-link feature-link--sm">
                立即测算 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
          </div>

          <!-- VIP 会员 -->
          <div class="feature-card card-hover" data-type="vip">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">👑</span>
            </div>
            <h3>VIP 会员</h3>
            <p>开通 VIP 享专属权益，海量积分、深度解读、专属牌阵一步到位</p>
            <div style="text-align: center; margin-top: 16px;">
              <router-link to="/vip" class="feature-link feature-link--sm">
                立即开通 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
          </div>

          <!-- 帮助中心 -->
          <div class="feature-card card-hover" data-type="help">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">❓</span>
            </div>
            <h3>帮助中心</h3>
            <p>了解如何使用各项功能，解答常见问题</p>
            <div style="text-align: center; margin-top: 16px;">
              <router-link to="/help" class="feature-link feature-link--sm">
                立即查看 <el-icon><ArrowRight /></el-icon>
              </router-link>
            </div>
          </div>

          <!-- 吉日查询（即将推出） -->
          <div class="feature-card feature-card--coming card-hover" data-type="jiri">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">📅</span>
            </div>
            <div class="coming-badge">即将推出</div>
            <h3>吉日查询</h3>
            <p>结婚、开业、搬家……根据黄历与个人八字，挑选最宜之日</p>
            <el-button type="primary" plain size="small" class="feature-link feature-link--sm" @click="handleReserve('jiri')">
              感兴趣？点击预约
            </el-button>
          </div>
        </div>
      </div>
    </section>

    <!-- 用户评价 Section -->
    <section class="testimonials">
      <div class="container">
        <div class="section-heading section-heading--center">
          <h2 class="section-title">体验案例</h2>
          <p class="section-description">真实场景整理，看看哪类困惑更适合哪种服务</p>
        </div>
        <div class="testimonials-grid">
          <article class="testimonial-card card-hover" v-for="(item, index) in testimonials" :key="index">
            <div class="testimonial-topline">
              <span class="service-tag">{{ item.service }}</span>
            </div>
            <div class="testimonial-header">
              <div class="testimonial-scene">
                <span class="testimonial-scene-label">适用场景</span>
                <h4>{{ item.persona }}</h4>
              </div>
            </div>
            <p class="testimonial-content">{{ item.content }}</p>
            <div class="testimonial-footer">
              <span class="testimonial-outcome">{{ item.outcome }}</span>
              <span class="testimonial-service-copy">适合先从 {{ item.service }} 入手</span>
            </div>
          </article>
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
          <div class="about-stats" :class="{ 'about-stats--loading': statsLoading, 'about-stats--error': statsError }">
            <div class="stat-item card-hover" v-for="stat in stats" :key="stat.label">
              <div class="stat-icon-wrapper">
                <el-icon class="stat-icon"><component :is="stat.icon" /></el-icon>
              </div>
              <span class="stat-number" :class="{ 'stat-number--placeholder': !stat.isLive }">{{ stat.number }}</span>
              <span class="stat-label">{{ stat.label }}</span>
              <span v-if="stat.caption" class="stat-caption">{{ stat.caption }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import GuideModal from '../../components/GuideModal.vue'
import {
  Sunrise,
  Sunny,
  Moon,
  Coin,
  Cherry,
  Calendar,
  Star,
  Check,
  UserFilled,
  DataLine,
  ChatLineRound, MagicStick, Present, ArrowRight
} from '@element-plus/icons-vue'

import { useHome } from './useHome'

const {
  // 状态
  stats,
  statsLoading,
  statsError,
  isLoggedIn,
  userPoints,
  userCount,
  isFirstBaziEligible,
  registerPoints,
  qimingCost,
  testimonials,
  currentHour,

  // 计算属性
  currentYear,
  yearlyBannerBadge,
  yearlyBannerTitle,
  baziOfferState,
  heroPrimaryBadge,
  baziFeatureBadge,
  heroHintText,
  formattedUserPoints,
  registerIntentRoute,
  heroPointsDetail,
  heroPointsCardNote,
  heroProofItems,
  heroAccessItems,
  heroTrustItems,
  guestBenefits,
  greetingIcon,
  greetingText,
  dailyQuote,

  // 方法
  loadStats,
  handleReserve,
} = useHome()
</script>

<style scoped>
@import './style.css';
</style>
