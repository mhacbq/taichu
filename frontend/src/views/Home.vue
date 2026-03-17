<template>
  <div class="home">
    <GuideModal />
    <!-- Hero Section -->
    <section class="hero">
      <div class="container hero-shell">
        <div class="hero-main">
          <span class="hero-kicker">传统文化探索 · 年轻人的人生参考</span>
          <h1 class="hero-title">在迷茫中找到方向</h1>
          <p class="hero-subtitle">不是预测命运，而是帮你更懂自己<br>八字、塔罗、运势，为你的困惑寻找答案</p>
          <div class="hero-actions">
            <router-link to="/bazi" class="btn-primary">
              <el-icon class="btn-icon"><Calendar /></el-icon>
              开始排盘
              <span v-if="!isLoggedIn" class="btn-badge btn-badge--login">需登录</span>
              <span class="btn-badge btn-badge--free">首测免费</span>
            </router-link>
            <router-link to="/tarot" class="btn-secondary">
              <el-icon class="btn-icon"><MagicStick /></el-icon>
              塔罗占卜
              <span v-if="!isLoggedIn" class="btn-badge btn-badge--login btn-badge--outline">需登录</span>
            </router-link>
          </div>
          <p class="hero-hint" :class="{ 'hero-hint--muted': statsLoading || statsError }"><el-icon><Star /></el-icon> {{ heroHintText }}</p>
          <p class="hero-gate-note">{{ isLoggedIn ? '你已登录，可直接体验八字、塔罗、六爻与合婚；每日运势可随时浏览。' : '八字、塔罗、六爻、合婚需登录后使用；每日运势可直接浏览。' }}</p>
          <div class="hero-trust-list">
            <span class="hero-trust-pill"><el-icon><Check /></el-icon> 首屏信息更聚焦</span>
            <span class="hero-trust-pill"><el-icon><Present /></el-icon> 新用户登录即可领积分</span>
            <span class="hero-trust-pill"><el-icon><Star /></el-icon> 服务入口与权益说明分层展示</span>
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
              <p class="hero-points-note">先签到补充积分，再去排盘或占卜，首页不会再被多张卡片挤压得头重脚轻。</p>
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
            <p class="hero-status-quote hero-status-quote--guest">登录后可领取新用户积分，并按你的节奏体验八字、塔罗与每日运势。</p>
            <div class="hero-benefits">
              <span class="hero-benefit"><el-icon><Present /></el-icon> 新用户送 100 积分</span>
              <span class="hero-benefit"><el-icon><Star /></el-icon> 八字首测免费</span>
              <span class="hero-benefit"><el-icon><MagicStick /></el-icon> 支持八字 / 塔罗 / 每日运势</span>
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
        <h2 class="section-title">我们的服务</h2>
        <div class="features-grid">
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><Calendar /></el-icon></div>
            <h3>八字排盘</h3>
            <p>基于传统四柱信息，帮助你梳理性格节奏、发展方向与长期规划参考</p>
            <div class="feature-access">
              <span class="feature-note">需登录</span>
              <span class="feature-note feature-note--free">首测免费</span>
            </div>
            <router-link to="/bazi" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><MagicStick /></el-icon></div>
            <h3>塔罗占卜</h3>
            <p>通过牌阵与问题模板梳理关系、工作与决策困惑，获得更聚焦的思路</p>
            <div class="feature-access">
              <span class="feature-note">需登录</span>
            </div>
            <router-link to="/tarot" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><Switch /></el-icon></div>
            <h3>六爻占卜</h3>
            <p>传统周易六爻问事，为您解答工作、感情、决策等各类疑惑</p>
            <div class="feature-access">
              <span class="feature-note">需登录</span>
            </div>
            <router-link to="/liuyao" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><Link /></el-icon></div>
            <h3>八字合婚</h3>
            <p>通过双方八字分析婚姻匹配度，了解缘分深浅与相处之道</p>
            <div class="feature-access">
              <span class="feature-note">需登录</span>
            </div>
            <router-link to="/hehun" class="feature-link">
              立即体验 <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
          <div class="feature-card card-hover">
            <div class="feature-icon"><el-icon :size="48"><Star /></el-icon></div>
            <h3>每日运势</h3>
            <p>查看今日宜忌、幸运提示与节奏建议，作为轻量的日常状态参考</p>
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
        <div class="section-heading">
          <div>
            <p class="section-eyebrow">体验故事</p>
            <h2 class="section-title">用户心声</h2>
            <p class="section-description">以下内容为整理后的体验故事示例，用来展示不同服务适合解决的困惑类型，并不代表对个人结果的直接承诺。</p>
          </div>
          <div class="testimonials-summary card">
            <span class="testimonials-summary-label">说明</span>
            <p>我们把示例反馈、服务类型与场景阶段拆开展示，避免把前端示例文案误读成实时真人评价。</p>
          </div>
        </div>
        <div class="testimonials-grid">
          <article class="testimonial-card card-hover" v-for="(item, index) in testimonials" :key="index">
            <div class="testimonial-topline">
              <span class="testimonial-badge">{{ item.storyTag }}</span>
              <span class="service-tag">{{ item.service }}</span>
            </div>
            <div class="testimonial-header">
              <div class="testimonial-avatar" :style="{ backgroundColor: item.avatarColor }">{{ item.avatar }}</div>
              <div class="testimonial-info">
                <h4>{{ item.name }}</h4>
                <p class="testimonial-persona">{{ item.persona }}</p>
                <div class="testimonial-rating">
                  <el-icon v-for="n in 5" :key="n" class="star" :class="{ filled: n <= item.rating }">
                    <StarFilled />
                  </el-icon>
                  <span class="testimonial-rating-text">{{ item.ratingLabel }}</span>
                </div>
              </div>
            </div>
            <p class="testimonial-content">{{ item.content }}</p>
            <div class="testimonial-footer">
              <span class="testimonial-outcome">{{ item.outcome }}</span>
              <span class="testimonial-note">{{ item.note }}</span>
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
import { ref, onMounted, computed } from 'vue'
import GuideModal from '../components/GuideModal.vue'
import { getHomeStats, getPointsBalance } from '../api'
import { Sunrise, Sunny, Moon, Coin, Cherry, Calendar, MagicStick, Star, Aim, Present, Switch, Link, Check, UserFilled, DataLine, ChatLineRound } from '@element-plus/icons-vue'

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
    return
  }
  
  isLoggedIn.value = true
  try {
    const response = await getPointsBalance()
    if (response.code === 200) {
      userPoints.value = response.data.balance
    } else {
      userPoints.value = null
    }
  } catch (error) {
    console.error('加载积分失败:', error)
    userPoints.value = null
  }
}


onMounted(() => {
  loadStats()
  loadUserPoints()
})
</script>

<style scoped>
.hero {
  padding: 56px 0 88px;
  background:
    radial-gradient(circle at top left, rgba(var(--primary-rgb), 0.18), transparent 34%),
    radial-gradient(circle at right top, var(--white-08), transparent 24%),
    linear-gradient(180deg, rgba(10, 10, 26, 0.96), rgba(10, 10, 26, 0.88));
}

.hero-shell {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(320px, 420px);
  gap: 36px;
  align-items: center;
}

.hero-main {
  text-align: left;
}

.hero-kicker {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 36px;
  padding: 6px 14px;
  margin-bottom: 18px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.12);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  color: var(--primary-light);
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
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-subtitle {
  font-size: var(--font-body-lg);
  color: var(--text-secondary);
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
  background: rgba(15, 23, 42, 0.2);
  color: currentColor;
}

.btn-badge--free {
  background: var(--success-gradient);
  color: var(--text-inverse);
}

.btn-badge--outline {
  background: rgba(var(--primary-rgb), 0.12);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  color: var(--primary-color);
}

.hero-hint {
  color: var(--text-tertiary);
  font-size: var(--font-small);
  margin-top: 20px;
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  line-height: var(--line-height-base);
}

.hero-hint--muted {
  color: var(--text-secondary);
}

.hero-gate-note {
  margin-top: 12px;
  max-width: 620px;
  color: var(--text-tertiary);
  font-size: var(--font-caption);
  line-height: 1.7;
}

.hero-trust-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 24px;
}

.hero-trust-pill {
  min-height: 40px;
  padding: 8px 14px;
  border-radius: 999px;
  background: var(--bg-card);
  border: 1px solid var(--border-light);
  color: var(--text-secondary);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: var(--font-caption);
}

.hero-trust-pill .el-icon {
  color: var(--primary-light);
}

.hero-side {
  display: flex;
  justify-content: flex-end;
}

.hero-status-card {
  width: 100%;
  padding: 28px;
  border-radius: var(--radius-card);
  background: linear-gradient(180deg, rgba(var(--primary-rgb), 0.12), var(--bg-card));
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  box-shadow: var(--shadow-hover);
  backdrop-filter: blur(16px);
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
  background: rgba(var(--primary-rgb), 0.16);
  color: var(--primary-light);
}

.hero-status-icon--guest {
  background: rgba(var(--primary-rgb), 0.12);
}

.hero-status-copy {
  flex: 1;
}

.hero-status-eyebrow {
  margin: 0 0 4px;
  color: var(--text-tertiary);
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
  background: var(--primary-gradient);
  color: var(--text-accent-contrast);
  font-size: var(--font-tiny);
  font-weight: var(--weight-semibold);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.hero-status-badge--soft {
  background: rgba(var(--primary-rgb), 0.1);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  color: var(--primary-light);
}

.hero-status-quote {
  margin: 18px 0 0;
  padding: 14px 16px;
  border-radius: var(--radius-lg);
  background: var(--white-05);
  border: 1px solid var(--border-light);
  color: var(--text-secondary);
  font-size: var(--font-small);
  line-height: var(--line-height-base);
}

.hero-status-quote--guest {
  margin-bottom: 18px;
}

.hero-points-panel {
  margin-top: 18px;
  padding: 18px;
  border-radius: var(--radius-lg);
  background: var(--white-05);
  border: 1px solid var(--border-light);
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
  color: var(--text-accent-contrast);
  background: var(--primary-gradient);
  box-shadow: 0 10px 20px rgba(var(--primary-rgb), 0.24);
}

.hero-points-label {
  display: block;
  margin-bottom: 4px;
  color: var(--text-tertiary);
  font-size: var(--font-tiny);
}

.hero-points-value {
  display: block;
  color: var(--primary-light);
  font-size: clamp(28px, 4vw, 34px);
  font-weight: var(--weight-black);
  line-height: 1;
}

.hero-points-note {
  margin: 0;
  color: var(--text-secondary);
  font-size: var(--font-caption);
  line-height: 1.7;
}

.hero-benefits {
  display: grid;
  gap: 10px;
  margin-top: 18px;
}

.hero-benefit {
  min-height: 44px;
  padding: 10px 14px;
  border-radius: var(--radius-lg);
  background: var(--white-05);
  border: 1px solid var(--border-light);
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--text-secondary);
  font-size: var(--font-small);
}

.hero-benefit .el-icon {
  color: var(--primary-light);
}

.hero-panel-actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 20px;
}

.hero-panel-btn {
  min-height: 44px;
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
  background: var(--primary-gradient);
  color: var(--text-accent-contrast);
  box-shadow: 0 10px 24px rgba(var(--primary-rgb), 0.2);
}

.hero-panel-btn--secondary {
  background: var(--white-05);
  color: var(--text-primary);
  border: 1px solid var(--border-light);
}

.hero-panel-btn:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-hover);
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
  margin-bottom: 16px;
}

.feature-access {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
  margin-bottom: 18px;
}

.feature-note {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 24px;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.12);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  color: var(--primary-color);
  font-size: 12px;
  font-weight: var(--weight-semibold);
}

.feature-note--free {
  background: rgba(103, 194, 58, 0.14);
  border-color: rgba(103, 194, 58, 0.18);
  color: var(--success-color);
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

.about-stats--loading .stat-item,
.about-stats--error .stat-item {
  background: linear-gradient(180deg, rgba(var(--primary-rgb), 0.08), var(--bg-card));
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

.stat-number--placeholder {
  background: none;
  -webkit-text-fill-color: currentColor;
  color: var(--text-secondary);
}

.stat-label {
  color: var(--text-secondary);
  font-size: var(--font-small);
  line-height: var(--line-height-base);
}

.stat-caption {
  display: block;
  margin-top: 8px;
  color: var(--text-tertiary);
  font-size: 12px;
  line-height: 1.6;
}

.stats-feedback {
  margin-top: 18px;
  padding: 16px 18px;
  border-radius: var(--radius-card);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  background: rgba(var(--primary-rgb), 0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.stats-feedback p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.6;
}

.stats-retry {
  min-height: 44px;
  padding: 10px 18px;
  border-radius: 999px;
  border: none;
  background: var(--primary-gradient);
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-md);
}

.stats-retry:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(var(--primary-rgb), 0.22);
}

.stats-retry:focus-visible {
  outline: 2px solid rgba(var(--primary-rgb), 0.28);
  outline-offset: 2px;
}


/* 用户评价区域 */
.testimonials {
  padding: 88px 0;
  background: linear-gradient(180deg, var(--bg-secondary), rgba(17, 17, 34, 0.94));
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
  color: var(--primary-light);
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
  color: var(--text-secondary);
  font-size: var(--font-body);
  line-height: var(--line-height-base);
}

.testimonials-summary {
  padding: 20px;
  border-radius: var(--radius-card);
  background: linear-gradient(180deg, rgba(var(--primary-rgb), 0.08), var(--bg-card));
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  box-shadow: var(--shadow-card);
}

.testimonials-summary-label {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 4px 12px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.12);
  color: var(--primary-light);
  font-size: var(--font-tiny);
  font-weight: var(--weight-semibold);
}

.testimonials-summary p {
  margin: 12px 0 0;
  color: var(--text-secondary);
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
  background: linear-gradient(180deg, rgba(var(--primary-rgb), 0.06), var(--bg-card));
  border: 1px solid var(--border-light);
  border-radius: var(--radius-card);
  box-shadow: var(--shadow-card);
}

.testimonial-card:hover {
  transform: translateY(-5px);
  border-color: var(--primary-light-30);
  box-shadow: var(--shadow-hover);
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
  background: var(--white-05);
  border: 1px solid var(--border-light);
  color: var(--text-secondary);
  font-size: var(--font-tiny);
  font-weight: var(--weight-semibold);
}

.testimonial-header {
  display: flex;
  align-items: center;
  gap: 14px;
}

.testimonial-avatar {
  width: 56px;
  height: 56px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: var(--primary-light);
  font-weight: var(--weight-bold);
  border: 1px solid var(--primary-light-20);
  flex-shrink: 0;
}

.testimonial-info {
  display: grid;
  gap: 6px;
}

.testimonial-info h4 {
  margin: 0;
  color: var(--text-primary);
  font-size: var(--font-body);
  font-weight: var(--weight-semibold);
}

.testimonial-persona {
  margin: 0;
  color: var(--text-tertiary);
  font-size: var(--font-caption);
}

.testimonial-rating {
  display: flex;
  align-items: center;
  gap: 3px;
  flex-wrap: wrap;
}

.testimonial-rating .star {
  color: var(--border-color);
  font-size: 14px;
}

.testimonial-rating .star.filled {
  color: var(--star-color);
}

.testimonial-rating-text {
  margin-left: 6px;
  color: var(--text-tertiary);
  font-size: var(--font-tiny);
}

.testimonial-content {
  margin: 0;
  color: var(--text-secondary);
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
  background: var(--white-05);
  border: 1px solid var(--border-light);
  color: var(--text-tertiary);
  font-size: var(--font-tiny);
}

.service-tag {
  min-height: 30px;
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  background: rgba(var(--primary-rgb), 0.12);
  color: var(--primary-light);
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

  .hero-subtitle,
  .hero-gate-note {
    margin-left: auto;
    margin-right: auto;
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

  .hero-benefit {
    align-items: flex-start;
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



</style>
