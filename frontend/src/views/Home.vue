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
              <span v-if="!isLoggedIn" class="btn-badge btn-badge--free">首测免费</span>
            </router-link>
            <router-link to="/tarot" class="btn-secondary">
              <el-icon class="btn-icon"><Star /></el-icon>
              塔罗占卜
              <span class="btn-badge btn-badge--free">5积分/次</span>
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
          <p class="hero-hint" :class="{ 'hero-hint--muted': statsLoading || statsError }"><el-icon><Star /></el-icon> {{ heroHintText }}</p>

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

          <div class="hero-access-list">
            <article class="hero-access-item" v-for="item in heroAccessItems" :key="item.key">
              <span class="hero-access-icon">
                <el-icon><component :is="item.icon" /></el-icon>
              </span>
              <div class="hero-access-copy">
                <strong>{{ item.title }}</strong>
                <span>{{ item.detail }}</span>
              </div>
            </article>
          </div>

          <div class="hero-trust-list">
            <span class="hero-trust-pill" v-for="item in heroTrustItems" :key="item.key">
              <el-icon><component :is="item.icon" /></el-icon>
              {{ item.text }}
            </span>
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

    <!-- Features Section -->
    <section class="features">
      <div class="container">
        <h2 class="section-title">选择你想探索的方向</h2>
        <p class="section-subtitle">核心功能消耗积分，注册即送，每日签到可持续领取</p>
        <div class="features-grid">
          <!-- 主功能 3个 -->
          <div class="feature-card feature-card--primary card-hover" data-type="bazi">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">☯</span>
            </div>
            <h3>八字排盘</h3>
            <p>基于传统四柱信息，帮助你梳理性格节奏、发展方向与长期规划参考</p>
            <div class="feature-meta">
              <span class="feature-cost">⚡ 5积分/次</span>
              <span class="feature-access">{{ isLoggedIn ? '立即使用' : '注册免费首测' }}</span>
            </div>
            <router-link to="/bazi" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card feature-card--primary card-hover" data-type="tarot">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">✴</span>
            </div>
            <h3>塔罗占卜</h3>
            <p>通过牌阵与问题模板梳理关系、工作与决策困惑，获得更聚焦的思路</p>
            <div class="feature-meta">
              <span class="feature-cost">⚡ 5积分/次</span>
              <span class="feature-access">直接体验</span>
            </div>
            <router-link to="/tarot" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card feature-card--primary card-hover" data-type="daily">
            <div class="feature-icon-wrap">
              <span class="feature-symbol">◉</span>
            </div>
            <h3>每日运势</h3>
            <p>查看今日宜忌、幸运提示与节奏建议，作为轻量的日常状态参考</p>
            <div class="feature-meta">
              <span class="feature-cost feature-cost--free">✨ 完全免费</span>
              <span class="feature-access feature-access--free">无需登录</span>
            </div>
            <router-link to="/daily" class="feature-link">
              立即查看 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <!-- 次要功能 3个 -->
          <div class="feature-card feature-card--secondary card-hover" data-type="liuyao">
            <div class="feature-icon-wrap feature-icon-wrap--sm">
              <span class="feature-symbol">☰</span>
            </div>
            <h3>六爻占卜</h3>
            <p>传统周易六爻问事，为您解答工作、感情、决策等各类疑惑</p>
            <div class="feature-meta">
              <span class="feature-cost">⚡ 3积分/次</span>
            </div>
            <router-link to="/liuyao" class="feature-link feature-link--sm">
              体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card feature-card--secondary card-hover" data-type="hehun">
            <div class="feature-icon-wrap feature-icon-wrap--sm">
              <span class="feature-symbol">◎</span>
            </div>
            <h3>八字合婚</h3>
            <p>通过双方八字分析婚姻匹配度，了解缘分深浅与相处之道</p>
            <div class="feature-meta">
              <span class="feature-cost">⚡ 5积分/次</span>
            </div>
            <router-link to="/hehun" class="feature-link feature-link--sm">
              体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card feature-card--secondary card-hover" data-type="profile">
            <div class="feature-icon-wrap feature-icon-wrap--sm">
              <span class="feature-symbol">★</span>
            </div>
            <h3>个人中心</h3>
            <p>查看历史记录、每日签到领积分，管理你的命理体验进度</p>
            <div class="feature-meta">
              <span class="feature-cost feature-cost--free">📅 签到领积分</span>
            </div>
            <router-link to="/profile" class="feature-link feature-link--sm">
              进入 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>

          <!-- 取名建议（即将推出） -->
          <div class="feature-card feature-card--coming card-hover" data-type="qiming">
            <div class="feature-icon-wrap feature-icon-wrap--sm">
              <span class="feature-symbol">✍️</span>
            </div>
            <div class="coming-badge">即将推出</div>
            <h3>取名建议</h3>
            <p>结合生辰八字与五行，由AI为新生儿推荐寓意美好的名字</p>
            <div class="feature-meta">
              <span class="feature-cost">✨ 五行取名</span>
            </div>
            <el-button type="primary" plain size="small" class="feature-link feature-link--sm" @click="handleReserve('qiming')">
              感兴趣？点击预约
            </el-button>
          </div>

          <!-- 吉日查询（即将推出） -->
          <div class="feature-card feature-card--coming card-hover" data-type="jiri">
            <div class="feature-icon-wrap feature-icon-wrap--sm">
              <span class="feature-symbol">📅</span>
            </div>
            <div class="coming-badge">即将推出</div>
            <h3>吉日查询</h3>
            <p>结婚、开业、搬家……根据黄历与个人八字，挑选最宜之日</p>
            <div class="feature-meta">
              <span class="feature-cost">📆 择日黄历</span>
            </div>
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
        <div class="section-heading">
          <div>
            <p class="section-eyebrow">体验故事</p>
            <h2 class="section-title">体验案例</h2>
            <p class="section-description">以下内容为整理后的体验案例，用来展示不同服务更适合帮助梳理哪类困惑，并不代表对个人结果的直接承诺。</p>
          </div>
          <div class="testimonials-summary card">
            <span class="testimonials-summary-label">说明</span>
            <p>示例内容按服务场景整理展示，不使用实时评分、具体昵称或头像式背书，重点只放在“这类问题更适合怎么用”。</p>
          </div>
        </div>
        <div class="testimonials-grid">
          <article class="testimonial-card card-hover" v-for="(item, index) in testimonials" :key="index">
            <div class="testimonial-topline">
              <span class="testimonial-badge">{{ item.storyTag }}</span>
              <span class="service-tag">{{ item.service }}</span>
            </div>
            <div class="testimonial-header">
              <div class="testimonial-scene">
                <span class="testimonial-scene-label">适用场景</span>
                <h4>{{ item.persona }}</h4>
              </div>
              <span class="testimonial-note">{{ item.note }}</span>
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
          <div v-if="statsError" class="stats-feedback">
            <p>统计数据暂时不可用，先体验核心功能也不耽误。</p>
            <button class="stats-retry" type="button" @click="loadStats">刷新统计</button>
          </div>

        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import GuideModal from '../components/GuideModal.vue'
import { getHomeStats, getPointsBalance } from '../api'
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
  ChatLineRound, MagicStick, Present
} from '@element-plus/icons-vue'

const statIconMap = {
  UserFilled,
  DataLine,
  ChatLineRound,
}

const createFallbackStats = (caption = '数据更新中') => [
  { number: '--', label: '服务用户', icon: UserFilled, caption, isLive: false },
  { number: '--', label: '分析次数', icon: DataLine, caption, isLive: false },
  { number: '--', label: '好评率', icon: ChatLineRound, caption, isLive: false },
]

const hasDisplayValue = (value) => value !== undefined && value !== null && `${value}`.trim() !== ''

const formatDisplayValue = (value) => {
  if (!hasDisplayValue(value)) {
    return '--'
  }

  if (typeof value === 'number') {
    return value.toLocaleString('zh-CN')
  }

  const numericValue = Number(value)
  return Number.isFinite(numericValue) ? numericValue.toLocaleString('zh-CN') : value
}

const resolveStatIcon = (icon, fallbackIcon = UserFilled) => {
  if (typeof icon === 'object' || typeof icon === 'function') {
    return icon
  }

  return statIconMap[icon] || fallbackIcon
}

const buildStats = (incomingStats = []) => {
  const fallbackStats = createFallbackStats()

  return fallbackStats.map((fallback, index) => {
    const item = incomingStats[index]

    if (!item) {
      return fallback
    }

    return {
      ...fallback,
      ...item,
      number: hasDisplayValue(item.number) ? formatDisplayValue(item.number) : '--',
      label: item.label || fallback.label,
      icon: resolveStatIcon(item.icon, fallback.icon),
      caption: hasDisplayValue(item.number) ? '' : fallback.caption,
      isLive: hasDisplayValue(item.number),
    }
  })
}

const stats = ref(createFallbackStats('统计同步中'))
const statsLoading = ref(true)
const statsError = ref(false)

const isLoggedIn = ref(false)
const userPoints = ref(null)
const userCount = ref(null)
const isFirstBaziEligible = ref(null)

const baziOfferState = computed(() => {
  if (!isLoggedIn.value) {
    return 'guest'
  }

  if (isFirstBaziEligible.value == null) {
    return 'loading'
  }

  return isFirstBaziEligible.value ? 'free' : 'priced'
})

const heroPrimaryBadge = computed(() => {
  if (baziOfferState.value === 'guest') {
    return {
      text: '登录后首测免费',
      className: 'btn-badge--free'
    }
  }

  if (baziOfferState.value === 'free') {
    return {
      text: '首测免费',
      className: 'btn-badge--free'
    }
  }

  if (baziOfferState.value === 'priced') {
    return {
      text: '查看当前价格',
      className: 'btn-badge--pricing'
    }
  }

  return {
    text: '权益确认中',
    className: 'btn-badge--muted'
  }
})

const baziFeatureBadge = computed(() => {
  if (baziOfferState.value === 'guest') {
    return {
      text: '登录后首测免费',
      className: 'feature-note--free'
    }
  }

  if (baziOfferState.value === 'free') {
    return {
      text: '首测免费',
      className: 'feature-note--free'
    }
  }

  if (baziOfferState.value === 'priced') {
    return {
      text: '查看当前价格',
      className: 'feature-note--price'
    }
  }

  return {
    text: '权益确认中',
    className: 'feature-note--muted'
  }
})

const baziAccessDetail = computed(() => {
  if (baziOfferState.value === 'guest') {
    return '登录后可保存体验进度，并确认你是否还保留八字首测免费资格。'
  }

  if (baziOfferState.value === 'free') {
    return '八字、塔罗、六爻与合婚现在都能直接进入，你的八字首测资格也还在。'
  }

  if (baziOfferState.value === 'priced') {
    return '八字、塔罗、六爻与合婚现在都能直接进入；八字首测资格已使用，可直接查看当前价格。'
  }

  return '八字、塔罗、六爻与合婚现在都能直接进入，八字权益正在同步。'
})

const heroHintText = computed(() => {
  if (statsLoading.value) {
    return '站内数据正在同步中，请稍候'
  }

  if (statsError.value) {
    return '统计数据暂时不可用，先体验核心功能也不耽误'
  }

  if (hasDisplayValue(userCount.value)) {
    return `已有 ${formatDisplayValue(userCount.value)} 位用户在这里找到答案`
  }

  return '站内数据每日更新，欢迎先体验核心功能'
})

const formattedUserPoints = computed(() => formatDisplayValue(userPoints.value))
const registerIntentRoute = { path: '/login', query: { intent: 'register' } }

const heroPointsDetail = computed(() => {
  if (!isLoggedIn.value) {
    return '登录后可领取 100 积分，用更轻量的方式开始第一次体验。'
  }

  if (!hasDisplayValue(userPoints.value)) {
    return '积分正在同步中，也可以先去个人中心确认今日签到状态。'
  }

  if (baziOfferState.value === 'free') {
    return `当前可用 ${formattedUserPoints.value} 积分，你的八字首测资格仍在，适合先从一次免费排盘开始。`
  }

  if (baziOfferState.value === 'priced') {
    return `当前可用 ${formattedUserPoints.value} 积分，八字首测资格已使用，可先查看当前价格再决定是否继续深入解读。`
  }

  return `当前可用 ${formattedUserPoints.value} 积分，可继续用于排盘、占卜与后续深入解读。`
})

const heroPointsCardNote = computed(() => {
  if (!isLoggedIn.value) {
    return '登录后即可同步积分权益与八字首测资格。'
  }

  if (!hasDisplayValue(userPoints.value)) {
    return '积分正在同步中，也可以先去个人中心确认今日签到状态。'
  }

  if (baziOfferState.value === 'free') {
    return `当前可用 ${formattedUserPoints.value} 积分，你的八字首测资格仍在，适合先从一次免费排盘开始。`
  }

  if (baziOfferState.value === 'priced') {
    return `当前可用 ${formattedUserPoints.value} 积分，八字首测资格已使用；想补充额度时，可去签到页领取今日积分。`
  }

  return `当前可用 ${formattedUserPoints.value} 积分，可继续用于排盘、占卜与后续深入解读。`
})


const heroProofItems = computed(() => [
  {
    key: 'users',
    icon: UserFilled,
    label: hasDisplayValue(userCount.value) ? `${formatDisplayValue(userCount.value)}+ 用户正在体验` : '持续为用户提供参考',
    description: '把人生阶段、当下困惑和下一步行动拆开来看，先理解自己再做决定。'
  },
  {
    key: 'services',
    icon: MagicStick,
    label: '5 类核心服务一站体验',
    description: '八字、塔罗、六爻、合婚与每日运势统一从首页进入，路径更清楚。'
  },
  {
    key: 'clarity',
    icon: ChatLineRound,
    label: statsError.value ? '体验说明已单独呈现' : '权益与说明更透明',
    description: '登录门槛、示例反馈和积分权益分开展示，避免把提示误读成结果承诺。'
  }
])

const heroAccessItems = computed(() => [
  {
    key: 'entry',
    icon: isLoggedIn.value ? Check : Present,
    title: isLoggedIn.value ? '完整入口已为你解锁' : '登录后开启完整体验',
    detail: baziAccessDetail.value
  },
  {
    key: 'daily',
    icon: Star,
    title: '每日运势可随时浏览',
    detail: '今日宜忌、节奏提醒和轻量建议无需登录即可查看。'
  },
  {
    key: 'points',
    icon: Coin,
    title: isLoggedIn.value ? '账户积分状态' : '新用户积分权益',
    detail: heroPointsDetail.value
  }
])

const heroTrustItems = computed(() => [
  {
    key: 'clarity',
    icon: Check,
    text: '先用一句话说清每项服务能帮你解决什么问题'
  },
  {
    key: 'benefits',
    icon: Present,
    text: isLoggedIn.value ? '积分、入口与下一步操作收在同一张状态卡里' : '新用户福利、登录门槛和入口路径一次说明白'
  },
  {
    key: 'rhythm',
    icon: Star,
    text: '先告诉你能做什么，再决定要不要深入探索'
  }
])

const guestBenefits = [
  {
    key: 'points',
    icon: Present,
    title: '登录即领 100 积分',
    description: '先拿到体验额度，再决定最想优先探索哪一项服务。'
  },
  {
    key: 'free',
    icon: Star,
    title: '八字首测免费',
    description: '第一次排盘零门槛，适合先看看自己的整体节奏。'
  },
  {
    key: 'services',
    icon: MagicStick,
    title: '多种方式探索自己',
    description: '八字、塔罗与每日运势等入口现在都从首页顺着往下走。'
  }
]

// 问候语数据
const currentHour = ref(new Date().getHours())
let greetingRefreshTimer = null

const syncCurrentHour = () => {
  currentHour.value = new Date().getHours()
}

const scheduleGreetingRefresh = () => {
  syncCurrentHour()

  if (greetingRefreshTimer) {
    window.clearTimeout(greetingRefreshTimer)
  }

  const now = new Date()
  const nextHour = new Date(now)
  nextHour.setHours(now.getHours() + 1, 0, 0, 0)
  greetingRefreshTimer = window.setTimeout(() => {
    scheduleGreetingRefresh()
  }, Math.max(1000, nextHour.getTime() - now.getTime() + 1000))
}

const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    scheduleGreetingRefresh()
  }
}

const greetingIcon = computed(() => {
  if (currentHour.value < 12) return 'morning'
  if (currentHour.value < 18) return 'afternoon'
  return 'evening'
})

const greetingText = computed(() => {
  if (currentHour.value < 12) return '早上好，愿你今天充满希望'
  if (currentHour.value < 18) return '下午好，愿你的努力都有收获'
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

// 用户心声示例 - 以体验故事形式展示，避免与实时评价混淆
const testimonials = ref([
  {
    name: '小雨',
    avatar: '雨',
    avatarColor: 'var(--primary-light-20)',
    rating: 5,
    ratingLabel: '4.9 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '毕业转行期 · 职业方向迷茫',
    content: '毕业后一直很迷茫，不知道自己适合什么工作。排盘后看到自己的优势节奏和适合的发展方向，至少先知道下一步该往哪走。',
    outcome: '更适合用来梳理职业方向',
    note: '示例反馈',
    service: '八字排盘'
  },
  {
    name: '阿杰',
    avatar: '杰',
    avatarColor: 'var(--warning-light)',
    rating: 5,
    ratingLabel: '4.8 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '关系调整期 · 想看清真实需求',
    content: '感情遇到瓶颈期时，塔罗没有替我做决定，而是帮我把真正纠结的点拆开来看，最后更清楚自己到底在意什么。',
    outcome: '更适合梳理关系里的优先级',
    note: '示例反馈',
    service: '塔罗占卜'
  },
  {
    name: '小陈',
    avatar: '陈',
    avatarColor: 'var(--success-light)',
    rating: 4,
    ratingLabel: '4.7 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '高压上班族 · 需要每日提醒',
    content: '工作压力大的时候，我更在意有没有一个轻量提醒告诉我今天该冲还是该缓。每日运势给我的价值，是让我在忙乱里停一下。',
    outcome: '适合做日常节奏提醒',
    note: '示例反馈',
    service: '每日运势'
  },
  {
    name: '琳琳',
    avatar: '琳',
    avatarColor: 'var(--primary-light-15)',
    rating: 5,
    ratingLabel: '4.9 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '自我探索期 · 容易反复内耗',
    content: '以前总觉得自己想太多，八字分析反而让我先理解自己的性格底色。被看见之后，很多自我怀疑就没那么重了。',
    outcome: '适合建立更稳定的自我认知',
    note: '示例反馈',
    service: '八字排盘'
  },
  {
    name: '大鹏',
    avatar: '鹏',
    avatarColor: 'var(--info-light)',
    rating: 4,
    ratingLabel: '4.7 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '跳槽决策期 · 需要理清取舍',
    content: '一直纠结要不要换工作，塔罗最大的帮助不是“准不准”，而是把风险、期待和顾虑都摆到了明面上，决策时没那么乱。',
    outcome: '更适合辅助做阶段性判断',
    note: '示例反馈',
    service: '塔罗占卜'
  },
  {
    name: '思思',
    avatar: '思',
    avatarColor: 'var(--warning-light)',
    rating: 5,
    ratingLabel: '4.8 / 5 · 示例反馈',
    storyTag: '体验故事',
    persona: '长期规划期 · 想看未来节奏',
    content: '第一次接触时本来只是抱着试试看的心态，但长周期分析给我的感觉是：至少能把未来几年要留意的节点先放进心里。',
    outcome: '适合做长期规划参考',
    note: '示例反馈',
    service: '八字排盘'
  }
])


const loadStats = async () => {
  statsLoading.value = true
  statsError.value = false

  try {
    const response = await getHomeStats()

    if (response.code !== 200) {
      throw new Error(response.message || '加载统计数据失败')
    }

    const incomingStats = Array.isArray(response.data?.stats) ? response.data.stats : []
    stats.value = buildStats(incomingStats)
    userCount.value = hasDisplayValue(response.data?.userCount) ? response.data.userCount : null

    if (!incomingStats.length && !hasDisplayValue(userCount.value)) {
      statsError.value = true
      stats.value = createFallbackStats('数据更新中')
    }
  } catch (error) {
    console.error('加载统计数据失败:', error)
    stats.value = createFallbackStats('数据更新中')
    userCount.value = null
    statsError.value = true
  } finally {
    statsLoading.value = false
  }
}



const loadUserPoints = async () => {
  const token = localStorage.getItem('token')
  if (!token) {
    isLoggedIn.value = false
    userPoints.value = null
    isFirstBaziEligible.value = null
    return
  }
  
  isLoggedIn.value = true
  try {
    const response = await getPointsBalance()
    if (response.code === 200) {
      userPoints.value = response.data.balance
      isFirstBaziEligible.value = resolveFirstBaziFlag(response.data?.first_bazi)
    } else {
      userPoints.value = null
      isFirstBaziEligible.value = null
    }
  } catch (error) {
    console.error('加载积分失败:', error)
    userPoints.value = null
    isFirstBaziEligible.value = null
  }
}

const refreshHomeAccountState = () => {
  loadUserPoints()
}

const handleReserve = (type) => {
  if (!isLoggedIn.value) {
    ElMessage.warning('请先登录后再预约')
    return
  }
  
  const featureName = type === 'qiming' ? '取名建议' : '吉日查询'
  ElMessage.success(`已成功预约「${featureName}」功能，上线后将第一时间通知您！`)
}

onMounted(() => {
  loadStats()
  loadUserPoints()
  scheduleGreetingRefresh()
  window.addEventListener('points-updated', refreshHomeAccountState)
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

onUnmounted(() => {
  window.removeEventListener('points-updated', refreshHomeAccountState)
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  if (greetingRefreshTimer) {
    window.clearTimeout(greetingRefreshTimer)
    greetingRefreshTimer = null
  }
})
</script>

<style scoped>
.hero {
  padding: 56px 0 88px;
  background:
    radial-gradient(circle at top left, rgba(245, 197, 110, 0.24), transparent 42%),
    radial-gradient(circle at right top, rgba(250, 224, 173, 0.35), transparent 34%),
    linear-gradient(180deg, #fffefb 0%, #fffaf1 56%, #fff7e9 100%);
}

.hero-shell {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(320px, 420px);
  gap: 36px;
  align-items: center;
}

.hero-main {
  text-align: left;
  color: #2f2a22;
}

.hero-kicker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 36px;
  padding: 6px 14px;
  margin-bottom: 18px;
  border-radius: 999px;
  background: rgba(245, 196, 103, 0.16);
  border: 1px solid rgba(212, 156, 62, 0.34);
  color: #8a6121;
  font-size: var(--font-caption);
  font-weight: var(--weight-semibold);
  letter-spacing: 0.08em;
}

.hero-title {
  font-size: clamp(42px, 7vw, 60px);
  font-weight: var(--weight-black);
  line-height: 1.05;
  letter-spacing: var(--tracking-tight);
  margin-bottom: 18px;
  max-width: 11ch;
  color: #5e4318;
  text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
}

.hero-subtitle {
  font-size: var(--font-body-lg);
  color: #4a4236;
  margin-bottom: 30px;
  max-width: 620px;
  line-height: var(--line-height-base);
}

.hero-actions {
  display: flex;
  gap: 16px;
  justify-content: flex-start;
  flex-wrap: wrap;
}

.btn-primary,
.btn-secondary {
  min-width: 204px;
  min-height: 52px;
}

.btn-primary,
.btn-secondary {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 22px;
  border-radius: 14px;
  text-decoration: none;
  font-size: var(--font-btn);
  font-weight: var(--weight-semibold);
  letter-spacing: 0.01em;
  border: 1px solid transparent;
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease, background 0.25s ease, color 0.25s ease;
  overflow: hidden;
}

.btn-primary::after,
.btn-secondary::after,
.hero-panel-btn::after,
.stats-retry::after,
.feature-link::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, transparent 20%, rgba(255, 255, 255, 0.38) 50%, transparent 80%);
  transform: translateX(-130%);
  transition: transform 0.55s ease;
  pointer-events: none;
}

.btn-primary {
  color: #5f4317;
  background: linear-gradient(135deg, #e3b254 0%, #f6d484 52%, #ffe5aa 100%);
  border-color: rgba(203, 149, 55, 0.48);
  box-shadow: 0 12px 24px rgba(186, 134, 39, 0.24), inset 0 1px 0 rgba(255, 255, 255, 0.65);
}

.btn-secondary {
  color: #6f4a17;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 247, 228, 0.98));
  border-color: rgba(203, 149, 55, 0.34);
  box-shadow: 0 10px 20px rgba(149, 111, 45, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.75);
}

.btn-primary:hover,
.btn-secondary:hover {
  transform: translateY(-2px);
}

.btn-primary:hover {
  box-shadow: 0 16px 30px rgba(186, 134, 39, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

.btn-secondary:hover {
  border-color: rgba(203, 149, 55, 0.44);
  box-shadow: 0 14px 26px rgba(149, 111, 45, 0.16), inset 0 1px 0 rgba(255, 255, 255, 0.82);
}

.btn-primary:hover::after,
.btn-secondary:hover::after,
.hero-panel-btn:hover::after,
.stats-retry:hover::after,
.feature-link:hover::after {
  transform: translateX(130%);
}

.btn-primary:active,
.btn-secondary:active,
.hero-panel-btn:active,
.stats-retry:active,
.feature-link:active {
  transform: translateY(0);
}

.btn-primary:focus-visible,
.btn-secondary:focus-visible,
.hero-panel-btn:focus-visible,
.stats-retry:focus-visible,
.feature-link:focus-visible {
  outline: 2px solid rgba(186, 134, 39, 0.45);
  outline-offset: 2px;
}

.btn-icon {
  font-size: 18px;
}

.btn-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 24px;
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: var(--weight-semibold);
  margin-left: 6px;
  line-height: 1;
}

.btn-badge--login {
  background: rgba(0, 0, 0, 0.08);
  color: currentColor;
}

.btn-badge--free {
  background: var(--success-gradient);
  color: var(--text-inverse);
}

.btn-badge--pricing {
  background: rgba(245, 158, 11, 0.16);
  border: 1px solid rgba(245, 158, 11, 0.22);
  color: #f59e0b;
}

.btn-badge--muted {
  background: rgba(148, 163, 184, 0.18);
  border: 1px solid rgba(148, 163, 184, 0.24);
  color: var(--text-secondary);
}

.btn-badge--outline {
  background: rgba(var(--primary-rgb), 0.12);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  color: var(--primary-color);
}


.hero-hint {
  color: #6b6254;
  font-size: var(--font-small);
  margin-top: 20px;
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  line-height: var(--line-height-base);
}

.hero-hint--muted {
  color: #7c7264;
}

/* 积分说明条 */
.hero-points-strip {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 20px;
  padding: 10px 18px;
  background: rgba(184, 134, 11, 0.07);
  border: 1px solid rgba(184, 134, 11, 0.15);
  border-radius: 999px;
  width: fit-content;
}

.points-strip-item {
  font-size: 13px;
  color: rgba(184, 134, 11, 0.8);
  white-space: nowrap;
}

.points-strip-sep {
  color: rgba(184, 134, 11, 0.35);
  font-size: 12px;
}

.hero-highlights {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-top: 28px;
}

.hero-highlight {
  min-height: 108px;
  padding: 18px;
  border-radius: var(--radius-lg);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 250, 238, 0.98));
  border: 1px solid rgba(227, 184, 104, 0.34);
  box-shadow: 0 12px 26px rgba(132, 96, 35, 0.08);
  display: flex;
  align-items: flex-start;
  gap: 14px;
}

.hero-highlights--muted .hero-highlight {
  border-color: var(--border-light);
}

.hero-highlight-icon,
.hero-access-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: rgba(245, 196, 103, 0.2);
  color: #9b6a20;
}

.hero-highlight-copy,
.hero-access-copy,
.hero-benefit-copy {
  display: grid;
  gap: 4px;
}

.hero-highlight-copy strong,
.hero-access-copy strong,
.hero-benefit-copy strong {
  color: var(--text-primary);
  font-size: var(--font-small);
  font-weight: var(--weight-semibold);
  line-height: 1.5;
}

.hero-highlight-copy span,
.hero-access-copy span,
.hero-benefit-copy span {
  color: #6f6657;
  font-size: var(--font-caption);
  line-height: 1.7;
}

.hero-access-list {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 18px;
}

.hero-access-item {
  min-height: 96px;
  padding: 16px;
  border-radius: var(--radius-lg);
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(227, 184, 104, 0.3);
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.hero-trust-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 20px;
}

.hero-trust-pill {
  min-height: 44px;
  padding: 10px 16px;
  border-radius: 999px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 248, 231, 0.98));
  border: 1px solid rgba(227, 184, 104, 0.36);
  color: #5a4e3e;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: var(--font-caption);
  box-shadow: 0 10px 22px rgba(149, 112, 45, 0.12);
}

.hero-trust-pill .el-icon {
  color: #ae7420;
  flex-shrink: 0;
}

.hero-side {
  display: flex;
  justify-content: flex-end;
}


.hero-status-card {
  width: 100%;
  padding: 28px;
  border-radius: var(--radius-card);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 236, 0.98));
  border: 1px solid rgba(227, 184, 104, 0.36);
  box-shadow: 0 18px 42px rgba(148, 109, 45, 0.16);
  backdrop-filter: blur(16px);
  display: grid;
  gap: 18px;
}


.hero-status-head {
  display: flex;
  align-items: flex-start;
  gap: 14px;
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

.hero-panel-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 0;
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

.hero-panel-btn:focus-visible,
.hero-access-item:focus-within,
.hero-highlight:focus-within {
  outline: none;
  box-shadow: var(--focus-ring), var(--shadow-hover);
}

.features {
  padding: 80px 0;
  background: linear-gradient(180deg, #fffaf1 0%, #fff7ee 100%);
}

.features .section-subtitle {
  text-align: center;
  color: var(--text-tertiary);
  font-size: var(--font-small);
  margin-top: -12px;
  margin-bottom: 32px;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

/* 主功能卡片：第一行 3个，更突出 */
.feature-card {
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 236, 0.95));
  border-radius: var(--radius-xl);
  padding: 32px 24px;
  text-align: center;
  border: 1px solid rgba(227, 184, 104, 0.32);
  box-shadow: 0 12px 32px rgba(145, 103, 34, 0.1);
  transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
  overflow: hidden;
}

/* 卡片顶部金色装饰线 */
.feature-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 15%;
  right: 15%;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.4), transparent);
}

.feature-card:hover {
  transform: translateY(-6px);
  border-color: rgba(212, 175, 55, 0.5);
  box-shadow: 0 20px 48px rgba(145, 103, 34, 0.16), 0 0 0 1px rgba(212, 175, 55, 0.1);
}

/* 次要功能卡片：视觉退后 */
.feature-card--secondary {
  background: rgba(255, 253, 246, 0.9);
  border-color: rgba(227, 184, 104, 0.2);
  padding: 24px 20px;
}

.feature-card--secondary:hover {
  border-color: rgba(212, 175, 55, 0.35);
}

/* 次要功能卡片：视觉退后 */
.feature-card--secondary {
  background: rgba(255, 253, 246, 0.9);
  border-color: rgba(227, 184, 104, 0.2);
  padding: 24px 20px;
}

.feature-card--secondary:hover {
  border-color: rgba(212, 175, 55, 0.35);
}

/* 即将推出卡片 */
.feature-card--coming {
  background: rgba(248, 248, 248, 0.7);
  border-color: rgba(200, 200, 200, 0.3);
  padding: 24px 20px;
  opacity: 0.75;
  position: relative;
}

.feature-card--coming:hover {
  opacity: 0.9;
  border-color: rgba(212, 175, 55, 0.25);
}

.coming-badge {
  display: inline-block;
  font-size: 11px;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(135deg, #B8860B, #D4AF37);
  border-radius: 20px;
  padding: 2px 10px;
  margin-bottom: 8px;
  letter-spacing: 0.5px;
}

.feature-link--disabled {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  font-weight: 600;
  color: #bbb;
  cursor: default;
  pointer-events: none;
  margin-top: auto;
}

.feature-icon-wrap--gold {
  width: 64px;
  height: 64px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  background: rgba(184, 134, 11, 0.1);
  border: 1px solid rgba(184, 134, 11, 0.2);
}

.feature-icon-wrap--sm {
  width: 48px;
  height: 48px;
  margin-bottom: 14px;
  border-radius: 12px;
}

.feature-symbol {
  font-size: 28px;
  color: #D4AF37;
  line-height: 1;
  filter: drop-shadow(0 0 6px rgba(212, 175, 55, 0.4));
}

.feature-icon-wrap--sm .feature-symbol {
  font-size: 22px;
}

/* 不同功能的图标颜色 */
[data-type="daily"] .feature-icon-wrap { background: rgba(76, 175, 130, 0.1); border-color: rgba(76, 175, 130, 0.2); }
[data-type="daily"] .feature-symbol { color: #4CAF82; filter: drop-shadow(0 0 6px rgba(76, 175, 130, 0.4)); }
[data-type="tarot"] .feature-icon-wrap { background: rgba(155, 127, 212, 0.1); border-color: rgba(155, 127, 212, 0.2); }
[data-type="tarot"] .feature-symbol { color: #A090E0; filter: drop-shadow(0 0 6px rgba(155, 127, 212, 0.4)); }

.feature-card h3 {
  font-size: var(--font-h4);
  font-weight: var(--weight-bold);
  margin-bottom: 10px;
  color: #3d3428;
  letter-spacing: 0.04em;
}

.feature-card--secondary h3 {
  font-size: var(--font-body);
  color: #5f5446;
}

.feature-card p {
  color: #6b6254;
  font-size: var(--font-small);
  line-height: 1.7;
  margin-bottom: 16px;
}

/* 积分/权限信息行 */
.feature-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 18px;
  flex-wrap: wrap;
}

.feature-cost {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 999px;
  background: rgba(184, 134, 11, 0.1);
  border: 1px solid rgba(184, 134, 11, 0.18);
  color: #9b6a20;
  font-size: 12px;
  font-weight: 600;
}

.feature-cost--free {
  background: rgba(76, 175, 130, 0.12);
  border-color: rgba(76, 175, 130, 0.2);
  color: rgba(76, 175, 130, 0.9);
}

.feature-access {
  font-size: 12px;
  color: #8a7a68;
}

.feature-access--free {
  color: #2d8a5e;
}

.feature-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #D4AF37;
  text-decoration: none;
  font-size: var(--font-small);
  font-weight: 600;
  transition: gap 0.2s ease, opacity 0.2s ease;
}

.feature-link--sm {
  font-size: 13px;
  color: rgba(212, 175, 55, 0.7);
}

.feature-link:hover {
  gap: 10px;
  opacity: 0.85;
}

.feature-note--free {
  background: rgba(103, 194, 58, 0.14);
  border-color: rgba(103, 194, 58, 0.18);
  color: var(--success-color);
}

.feature-note--price {
  background: rgba(230, 162, 60, 0.12);
  border-color: rgba(230, 162, 60, 0.2);
  color: var(--warning-color);
}

.feature-note--muted {
  background: rgba(148, 163, 184, 0.12);
  border-color: rgba(148, 163, 184, 0.18);
  color: var(--text-secondary);
}


.feature-link {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 42px;
  padding: 0 16px;
  border-radius: 12px;
  border: 1px solid rgba(210, 154, 64, 0.3);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(255, 247, 229, 0.96));
  box-shadow: 0 8px 18px rgba(149, 111, 45, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.85);
  color: #9b6a20;
  text-decoration: none;
  font-weight: var(--weight-semibold);
  transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease, color 0.25s ease;
}

.feature-link:hover {
  transform: translateY(-2px);
  border-color: rgba(210, 154, 64, 0.42);
  box-shadow: 0 12px 24px rgba(149, 111, 45, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.95);
  color: #7f5415;
}

.about {
  padding: 80px 0;
  background: linear-gradient(180deg, #fffaf1 0%, #fffdf8 100%);
}

.about-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  align-items: center;
}

.about-text p {
  color: #554a3d;
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
  color: #a77020;
  font-weight: bold;
}

.about-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
}

.about-stats--loading .stat-item,
.about-stats--error .stat-item {
  background: linear-gradient(180deg, rgba(245, 196, 103, 0.1), rgba(255, 255, 255, 0.95));
}

.stat-item {
  text-align: center;
  padding: 30px 20px;
  background: rgba(255, 255, 255, 0.98);
  border-radius: var(--radius-card);
  border: 1px solid rgba(227, 184, 104, 0.28);
  box-shadow: 0 14px 32px rgba(145, 103, 34, 0.1);
  transition: all 0.3s ease;
  perspective: 1000px;
}

.stat-icon-wrapper {
  width: 60px;
  height: 60px;
  background: rgba(245, 196, 103, 0.16);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  color: #9b6a20;
  font-size: 28px;
  transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
  -webkit-transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
  perspective: 1000px;
  -webkit-perspective: 1000px;
}

.stat-item:hover .stat-icon-wrapper {
  background: linear-gradient(135deg, #e2af4f 0%, #f4cc75 100%);
  color: #5e4318;
  transform: rotateY(360deg) scale(1.1);
  -webkit-transform: rotateY(360deg) scale(1.1);
  /* 针对不支持 rotateY 的旧版浏览器的降级方案 */
  box-shadow: 0 0 15px rgba(186, 135, 41, 0.32);
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

.stat-number--placeholder {
  background: none;
  -webkit-text-fill-color: currentColor;
  color: var(--text-secondary);
}

.stat-label {
  color: #5f5446;
  font-size: var(--font-small);
  line-height: var(--line-height-base);
}

.stat-caption {
  display: block;
  margin-top: 8px;
  color: #7b6e5b;
  font-size: 12px;
  line-height: 1.6;
}

.stats-feedback {
  margin-top: 18px;
  padding: 16px 18px;
  border-radius: var(--radius-card);
  border: 1px solid rgba(210, 154, 64, 0.28);
  background: rgba(255, 246, 228, 0.9);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.stats-feedback p {
  margin: 0;
  color: #5f5446;
  font-size: 14px;
  line-height: 1.6;
}

.stats-retry {
  position: relative;
  overflow: hidden;
  min-height: 44px;
  padding: 10px 18px;
  border-radius: 999px;
  border: none;
  background: linear-gradient(135deg, #e2af4f 0%, #f4cc75 100%);
  color: #5e4318;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-md);
}

.stats-retry:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 26px rgba(186, 135, 41, 0.32), inset 0 1px 0 rgba(255, 255, 255, 0.5);
}

.stats-retry:focus-visible {
  outline: 2px solid rgba(186, 135, 41, 0.34);
  outline-offset: 2px;
}


/* 用户评价区域 */
.testimonials {
  padding: 88px 0;
  background: linear-gradient(180deg, #fffdf8 0%, #fff9ee 100%);
}

.section-heading {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(280px, 360px);
  gap: 24px;
  align-items: end;
  margin-bottom: 40px;
}

.section-eyebrow {
  margin: 0 0 10px;
  color: #a56f1f;
  font-size: var(--font-caption);
  font-weight: var(--weight-semibold);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.testimonials .section-title {
  margin-bottom: 0;
}

.section-description {
  margin-top: 12px;
  max-width: 680px;
  color: #5b4f41;
  font-size: var(--font-body);
  line-height: var(--line-height-base);
}

.testimonials-summary {
  padding: 20px;
  border-radius: var(--radius-card);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 248, 232, 0.98));
  border: 1px solid rgba(227, 184, 104, 0.32);
  box-shadow: 0 12px 28px rgba(145, 103, 34, 0.1);
}

.testimonials-summary-label {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 4px 12px;
  border-radius: 999px;
  background: rgba(245, 196, 103, 0.18);
  color: #8d611f;
  font-size: var(--font-tiny);
  font-weight: var(--weight-semibold);
}

.testimonials-summary p {
  margin: 12px 0 0;
  color: #5e5345;
  font-size: var(--font-small);
  line-height: 1.7;
}

.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.testimonial-card {
  height: 100%;
  padding: 28px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 236, 0.98));
  border: 1px solid rgba(227, 184, 104, 0.28);
  border-radius: var(--radius-card);
  box-shadow: 0 12px 30px rgba(145, 103, 34, 0.1);
}

.testimonial-card:hover {
  transform: translateY(-5px);
  border-color: rgba(210, 154, 64, 0.42);
  box-shadow: 0 16px 34px rgba(145, 103, 34, 0.16);
}

.testimonial-topline {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.testimonial-badge {
  min-height: 30px;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(227, 184, 104, 0.3);
  color: #665a4b;
  font-size: var(--font-tiny);
  font-weight: var(--weight-semibold);
}

.testimonial-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.testimonial-scene {
  display: grid;
  gap: 6px;
}

.testimonial-scene-label {
  color: var(--text-tertiary);
  font-size: var(--font-tiny);
  letter-spacing: 0.04em;
}

.testimonial-scene h4 {
  margin: 0;
  color: var(--text-primary);
  font-size: var(--font-body);
  font-weight: var(--weight-semibold);
}

.testimonial-content {

  margin: 0;
  color: #5d5143;
  line-height: var(--line-height-base);
  font-size: var(--font-body);
  flex: 1;
}

.testimonial-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.testimonial-outcome {
  color: var(--text-primary);
  font-size: var(--font-caption);
  font-weight: var(--weight-medium);
}

.testimonial-note {
  min-height: 28px;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(227, 184, 104, 0.3);
  color: #7a6e5b;
  font-size: var(--font-tiny);
  white-space: nowrap;
}

.testimonial-service-copy {
  color: #a36d1e;
  font-size: var(--font-tiny);
}

.service-tag {

  min-height: 30px;
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid rgba(210, 154, 64, 0.32);
  background: rgba(245, 196, 103, 0.18);
  color: #916018;
  font-size: var(--font-tiny);
  font-weight: var(--weight-semibold);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}


@media (prefers-reduced-motion: reduce) {
  .hero-status-card,
  .hero-panel-btn,
  .feature-card,
  .testimonial-card,
  .stat-icon-wrapper {
    animation: none !important;
    transition: none !important;
  }

  .hero-panel-btn:hover,
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
  .hero-shell {
    grid-template-columns: 1fr;
  }

  .hero-main {
    text-align: center;
  }

  .hero-title {
    font-size: 40px;
    max-width: none;
    margin-left: auto;
    margin-right: auto;
  }

  .hero-subtitle {
    margin-left: auto;
    margin-right: auto;
  }

  .hero-highlights,
  .hero-access-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .hero-actions,
  .hero-trust-list {
    justify-content: center;
  }

  .hero-side {
    justify-content: center;
  }

  .section-heading {
    grid-template-columns: 1fr;
  }

  .features-grid {
    grid-template-columns: 1fr;
  }
  
  .testimonials-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .about-content {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .hero {
    padding: 48px 0 72px;
  }

  .hero-kicker {
    width: 100%;
  }

  .hero-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-primary,
  .btn-secondary {
    width: 100%;
    min-width: 0;
  }

  .hero-hint {
    justify-content: center;
  }

  .hero-highlights,
  .hero-access-list,
  .hero-trust-list {
    grid-template-columns: 1fr;
  }

  .hero-trust-list {
    display: grid;
  }

  .hero-highlight,
  .hero-access-item {
    min-height: 0;
    padding: 16px;
  }

  .hero-highlight-icon,
  .hero-access-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
  }

  .hero-trust-pill {
    width: 100%;
    justify-content: flex-start;
  }

  .hero-status-card {
    padding: 22px;
  }

  .hero-status-head {
    flex-wrap: wrap;
  }

  .hero-status-badge {
    order: 3;
  }

  .hero-points-display {
    align-items: flex-start;
  }

  .hero-panel-actions {
    grid-template-columns: 1fr;
  }

  .hero-panel-btn {
    width: 100%;
  }

  .hero-benefit {
    min-height: 0;
  }

  .about-stats {
    grid-template-columns: 1fr;
  }
  
  .stats-feedback {
    flex-direction: column;
    align-items: stretch;
  }

  .stats-retry {
    width: 100%;
  }

  .testimonials-grid {
    grid-template-columns: 1fr;
  }

  .testimonial-card {
    padding: 24px;
  }
}





/* 2026-03 UI polish: home refresh */
.hero {
  padding: 68px 0 96px;
  background:
    radial-gradient(circle at top left, rgba(var(--primary-rgb), 0.18), transparent 42%),
    radial-gradient(circle at 82% 12%, rgba(245, 196, 103, 0.22), transparent 28%),
    linear-gradient(180deg, #fffefb 0%, #fffaf1 48%, #fff7ee 100%);
}

.hero-shell {
  gap: 48px;
  align-items: stretch;
}

.hero-main {
  color: var(--text-primary);
}

.hero-kicker {
  background: rgba(var(--primary-rgb), 0.12);
  border-color: rgba(var(--primary-rgb), 0.18);
  color: #8b5f1c;
}

.hero-title {
  max-width: 10ch;
  margin-bottom: 20px;
  text-shadow: 0 12px 28px rgba(var(--primary-rgb), 0.1);
}

.hero-subtitle {
  max-width: 640px;
  color: #4e473d;
  line-height: 1.8;
}

.hero-actions {
  gap: 14px;
}

.btn-primary,
.btn-secondary {
  min-width: 216px;
  border-radius: 18px;
}

.btn-primary {
  box-shadow: var(--shadow-btn-primary);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.88);
  box-shadow: var(--shadow-btn-secondary);
}

.hero-highlight,
.hero-access-item,
.hero-trust-pill,
.hero-status-card,
.hero-benefit,
.hero-points-panel,
.hero-status-quote,
.feature-card,
.testimonials-summary,
.testimonial-card {
  box-shadow: var(--shadow-card);
}

.hero-highlight,
.hero-access-item,
.hero-benefit,
.hero-status-quote,
.hero-points-panel,
.testimonials-summary,
.testimonial-card {
  background: rgba(255, 255, 255, 0.94);
  border-color: rgba(var(--primary-rgb), 0.12);
}

.hero-status-card {
  position: relative;
  overflow: hidden;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.97), rgba(255, 248, 236, 0.96));
}

.hero-status-card::after {
  content: '';
  position: absolute;
  inset: auto auto -72px -36px;
  width: 180px;
  height: 180px;
  border-radius: 999px;
  background: radial-gradient(circle, rgba(var(--primary-rgb), 0.12) 0%, rgba(var(--primary-rgb), 0) 72%);
  pointer-events: none;
}

.hero-status-head,
.hero-points-panel,
.hero-benefits,
.hero-panel-actions {
  position: relative;
  z-index: 1;
}

.section-title {
  font-size: clamp(28px, 4vw, 36px);
  font-weight: 800;
  letter-spacing: var(--tracking-tight);
  color: var(--text-primary);
}

.features {
  padding: 88px 0;
  background: linear-gradient(180deg, #fffaf1 0%, #fff7ee 100%);
}

.features .section-title,
.about-text .section-title {
  margin-bottom: 16px;
}

.feature-card {
  height: 100%;
  border: 1px solid rgba(227, 184, 104, 0.28);
  box-shadow: 0 12px 32px rgba(145, 103, 34, 0.1);
}

.feature-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 24px 48px rgba(145, 103, 34, 0.16), 0 12px 28px rgba(212, 175, 55, 0.08);
}

.feature-link {
  border-radius: 999px;
}

.section-heading {
  align-items: start;
  margin-bottom: 30px;
}

.section-description {
  max-width: 720px;
  color: #5b5145;
  line-height: 1.8;
}

.testimonials {
  padding: 96px 0;
  background: linear-gradient(180deg, #fffaf1 0%, #fff7ee 100%);
}

.testimonial-card {
  border: 1px solid rgba(var(--primary-rgb), 0.12);
}

.testimonial-card:hover {
  transform: translateY(-8px);
}

@media (max-width: 992px) {
  .hero-title {
    max-width: none;
  }
}

@media (max-width: 768px) {
  .hero {
    padding: 50px 0 76px;
  }

  .hero-shell {
    gap: 28px;
  }

  .btn-primary,
  .btn-secondary {
    min-width: 0;
    border-radius: 16px;
  }

  .section-title {
    font-size: 28px;
  }

  .feature-card,
  .testimonial-card {
    padding-left: 22px;
    padding-right: 22px;
  }
}
</style>