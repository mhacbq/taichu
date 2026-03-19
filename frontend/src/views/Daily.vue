<template>
  <div class="daily-page">
    <div class="container">
      <PageHeroHeader
        title="每日运势"
        subtitle="先看今天的公共日运，再根据登录状态继续查看专属运势和签到入口，首屏不再让签到状态抢戏。"
        :icon="Calendar"
      />


      <div v-if="isLoading" class="date-display date-display--loading card card-hover">
        <div class="date-skeleton-block">
          <el-skeleton-item variant="text" class="date-skeleton-label" />
          <el-skeleton-item variant="text" class="date-skeleton-value" />
        </div>
        <div class="date-skeleton-block">
          <el-skeleton-item variant="text" class="date-skeleton-label" />
          <el-skeleton-item variant="text" class="date-skeleton-value" />
        </div>
      </div>

      <div v-else-if="fortune" class="fortune-content">
        <div class="date-display card card-hover">
          <div class="lunar-date">
            <span class="label">农历</span>
            <span class="value">{{ lunarDate }}</span>
          </div>
          <div class="solar-date">
            <span class="label">公历</span>
            <span class="value">{{ solarDate }}</span>
          </div>
        </div>

        <!-- 个性化运势卡片 -->
        <div v-if="dailyPersonalizedState === 'ready'" class="personalized-fortune card card-hover">
          <h2>
            <el-icon><MagicStick /></el-icon> 您的专属运势
            <el-tooltip content="基于您的八字日主计算的个性化运势分析" placement="top">
              <el-icon class="help-icon"><QuestionFilled /></el-icon>
            </el-tooltip>
          </h2>
          <div class="personal-content">
            <div class="master-info">
              <div class="master-card">
                <span class="label">您的日主</span>
                <span class="value">{{ personalizedFortune.dayMaster }}</span>
                <span class="wuxing-badge" :class="personalizedFortune.dayMasterWuxing">{{ personalizedFortune.dayMasterWuxing }}</span>
              </div>
              <div class="relation-arrow">
                <el-icon><Right /></el-icon>
              </div>
              <div class="today-card">
                <span class="label">今日干支</span>
                <span class="value">{{ personalizedFortune.todayGanZhi }}</span>
                <span class="wuxing-text">{{ personalizedFortune.todayWuxing }}</span>
              </div>
            </div>
            
            <div class="luck-indicator">
              <div class="luck-badge" :class="personalizedFortune.luckLevel">
                <span class="luck-relation-name">{{ personalizedFortune.relation }}</span>
                <span class="luck-relation-title">{{ personalizedRelationMeta.title }}</span>
                <span class="luck-level">今日偏{{ personalizedFortune.luckLevel }}</span>
              </div>
              <div class="luck-summary">
                <span class="luck-summary-label">这对你意味着什么</span>
                <p>{{ personalizedRelationMeta.description }}</p>
              </div>
              <div class="personal-score">
                <span class="score-label">综合评分</span>
                <span class="score-value" :class="getScoreClass(personalizedFortune.personalScore)">
                  {{ personalizedFortune.personalScore }}
                </span>
              </div>
            </div>
            
            <div class="personal-advice">
              <h4><el-icon><StarFilled /></el-icon> 今日建议</h4>
              <p>{{ personalizedFortune.advice }}</p>
            </div>
            
            <div class="personal-lucky-grid">
              <div class="personal-lucky-item personal-lucky-item--color card-hover">
                <div class="personal-lucky-head">
                  <span class="personal-lucky-icon">
                    <el-icon><MagicStick /></el-icon>
                  </span>
                  <div class="personal-lucky-meta">
                    <span class="personal-lucky-label">幸运色</span>
                    <span class="personal-lucky-caption">优先选择更顺势的穿搭与配色</span>
                  </div>
                </div>
                <div class="personal-lucky-values">
                  <span v-for="color in personalizedFortune.luckyColors" :key="color" class="lucky-tag color">
                    {{ color }}
                  </span>
                </div>
              </div>
              <div class="personal-lucky-item personal-lucky-item--direction card-hover">
                <div class="personal-lucky-head">
                  <span class="personal-lucky-icon">
                    <el-icon><Compass /></el-icon>
                  </span>
                  <div class="personal-lucky-meta">
                    <span class="personal-lucky-label">幸运方位</span>
                    <span class="personal-lucky-caption">安排会面、出行或重要决策时可优先参考</span>
                  </div>
                </div>
                <div class="personal-lucky-values">
                  <span v-for="dir in personalizedFortune.luckyDirections" :key="dir" class="lucky-tag direction">
                    {{ dir }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>
        
        <div v-else-if="dailyPersonalizedState === 'guest'" class="personalized-state-card card card-hover">
          <div class="state-content">
            <el-icon class="state-icon" :size="48"><UserFilled /></el-icon>
            <div class="state-body">
              <p class="state-title">登录后查看你的专属运势</p>
              <p class="state-copy">当前展示的是公共日运。登录后会基于你的八字日主补充个人提示、幸运色和幸运方位。</p>
            </div>
            <div class="state-actions">
              <router-link :to="dailyLoginRoute">
                <el-button type="primary" size="small">去登录</el-button>
              </router-link>
            </div>
          </div>
        </div>

        <div v-else-if="dailyPersonalizedState === 'no_bazi'" class="personalized-state-card personalized-state-card--empty card card-hover">
          <div class="state-content">
            <el-icon class="state-icon" :size="48"><Collection /></el-icon>
            <div class="state-body">
              <p class="state-title">完成八字排盘后再看专属部分</p>
              <p class="state-copy">你已登录，但还没有可用的八字档案。先完成一次排盘，页面就会自动补齐个人运势解释。</p>
            </div>
            <div class="state-actions">
              <router-link to="/bazi">
                <el-button type="primary" size="small">去排盘</el-button>
              </router-link>
            </div>
          </div>
        </div>

        <div v-else-if="dailyPersonalizedState === 'error'" class="personalized-state-card personalized-state-card--error card card-hover">
          <div class="state-content">
            <el-icon class="state-icon" :size="48"><WarningFilled /></el-icon>
            <div class="state-body">
              <p class="state-title">专属运势暂时没加载完整</p>
              <p class="state-copy state-copy--error">系统已识别到你的登录态，但个性化字段缺失或结构异常。你可以重试刷新，不必重新排盘。</p>
            </div>
            <div class="state-actions">
              <el-button type="primary" size="small" @click="loadDailyFortune({ userInitiated: true })">
                <el-icon><RefreshRight /></el-icon> 重新获取
              </el-button>
            </div>
          </div>
        </div>


        <div class="overall-score card card-hover">
          <h2>今日综合运势</h2>
          <div class="score-display">
            <div class="score-circle">
              <span class="score-number">{{ fortune.overallScore }}</span>
              <span class="score-label">分</span>
            </div>
            <div class="score-stars" :aria-label="`综合评分 ${fortune.overallScore} 分，对应 ${overallStarCount} 星评价`">
              <el-icon v-for="n in 5" :key="n" class="star" :class="{ filled: n <= overallStarCount }">
                <StarFilled />
              </el-icon>
            </div>
            <p class="score-rating">{{ overallStarLabel }}</p>

          </div>
          <p class="fortune-summary">{{ fortune.summary }}</p>
        </div>

        <div v-if="hasAspectCards" class="aspect-grid">
          <div class="aspect-card card card-hover" v-for="aspect in fortune.aspects" :key="aspect.name">
            <div class="aspect-icon">
              <el-icon>
                <component :is="getAspectIcon(aspect.name)" />
              </el-icon>
            </div>
            <h3>{{ aspect.name }}</h3>
            <div class="aspect-score">
              <el-progress :percentage="aspect.score" :color="getScoreColor(aspect.score)" />
            </div>
            <p class="aspect-desc">{{ aspect.description }}</p>
          </div>
        </div>
        <div v-else class="section-empty card card-hover">
          <h3>分项运势整理中</h3>
          <p>今日事业、财运、感情与健康的细分运势还在生成，稍后再来看看。</p>
        </div>

        <div class="lucky-section card card-hover">
          <h3>今日宜忌</h3>
          <div v-if="hasLuckySection" class="lucky-grid">
            <div v-if="hasYiItems" class="lucky-item good">
              <span class="lucky-label">宜</span>
              <div class="lucky-tags">
                <el-tag v-for="item in fortune.yi" :key="item" type="success">{{ item }}</el-tag>
              </div>
            </div>
            <div v-if="hasJiItems" class="lucky-item bad">
              <span class="lucky-label">忌</span>
              <div class="lucky-tags">
                <el-tag v-for="item in fortune.ji" :key="item" type="danger">{{ item }}</el-tag>
              </div>
            </div>
          </div>
          <div v-else class="section-empty section-empty--compact">
            <p>今日宜忌数据整理中，稍后再看。</p>
          </div>
        </div>

        <div class="details-section card card-hover">
          <h3>详细运势</h3>
          <el-collapse v-if="detailSections.length" v-model="activeNames">
            <el-collapse-item v-for="section in detailSections" :key="section.key" :title="section.title" :name="section.key">
              <p>{{ section.content }}</p>
            </el-collapse-item>
          </el-collapse>
          <div v-else class="section-empty section-empty--compact">
            <p>今日详细运势仍在整理中，稍后再看。</p>
          </div>
        </div>

        <div class="daily-action-zone">
          <CheckinCard v-if="isLoggedIn" />
          <div v-else class="personalized-state-card guest-checkin-card card card-hover">

            <div class="state-content">
              <el-icon class="state-icon" :size="48"><Present /></el-icon>
              <div class="state-body">
                <p class="state-title">登录后再签到领积分</p>
                <p class="state-copy">公共日运已经在上面完整展示。登录后可在这里完成每日签到、累计积分，并解锁与你八字相关的专属提示。</p>
              </div>
              <div class="state-actions">
                <router-link :to="dailyLoginRoute">
                  <el-button type="primary" size="small">登录后签到</el-button>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- 错误状态 -->
      <div v-else-if="error" class="error-state card card-hover">
        <el-icon class="error-icon" :size="48"><WarningFilled /></el-icon>
        <p class="error-message">{{ errorMessage }}</p>
        <el-button type="primary" @click="loadDailyFortune">重新加载</el-button>
      </div>

      <div v-else class="loading-state card card-hover">
        <el-skeleton :rows="10" animated />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { MagicStick, QuestionFilled, Collection, WarningFilled, StarFilled, Right, Compass, Briefcase, Money, Sunny, UserFilled, RefreshRight, Calendar, Present } from '@element-plus/icons-vue'
import { getDailyFortune } from '../api'
import CheckinCard from '../components/CheckinCard.vue'
import PageHeroHeader from '../components/PageHeroHeader.vue'


const solarDate = ref('')
const lunarDate = ref('')
const isLoading = ref(true)
const fortune = ref(null)
const isLoggedIn = ref(false)
const activeNames = ref([])
const error = ref(false)
const errorMessage = ref('')
const dailyLoginRoute = { path: '/login', query: { redirect: '/daily' } }

const personalizedRelationGuides = {
  比劫: {
    title: '同频助力，也有竞争',
    description: '适合找熟悉的人协作、互通信息，但也要避免被同辈节奏带着跑。',
  },
  印绶: {
    title: '贵人和恢复力更强',
    description: '今天适合学习、复盘和补充能量，遇到问题时更容易得到支持。',
  },
  食伤: {
    title: '表达力和创意更活跃',
    description: '适合沟通、提案和输出想法，但说话别太满，节奏稳一点更吃香。',
  },
  官杀: {
    title: '责任感和压力同步上升',
    description: '适合先处理关键任务与规则要求，别硬扛，把优先级排清楚会顺很多。',
  },
  财星: {
    title: '资源机会变多，也更考验判断',
    description: '适合关注合作、预算和收益，但别急着冲动决策，先算清账更稳。',
  },
}

const syncLoginState = () => {
  if (typeof window === 'undefined') {
    return
  }

  isLoggedIn.value = Boolean(window.localStorage.getItem('token'))
}

const personalizedFortune = computed(() => {
  const payload = fortune.value?.personalized
  return payload && typeof payload === 'object' ? payload : null
})

const personalizedRelationMeta = computed(() => {
  const relation = personalizedFortune.value?.relation || ''
  return personalizedRelationGuides[relation] || {
    title: '今日能量提示',
    description: '今日个性化运势已生成，可结合下方建议安排重点事项。',
  }
})

const isPersonalizedPayloadInvalid = computed(() => {
  if (!isLoggedIn.value || !personalizedFortune.value) {
    return false
  }

  if (personalizedFortune.value.hasBazi === false) {
    return false
  }

  const requiredFields = ['dayMaster', 'todayGanZhi', 'relation', 'luckLevel', 'advice']
  return personalizedFortune.value.hasBazi !== true || requiredFields.some((key) => {
    return typeof personalizedFortune.value[key] !== 'string' || personalizedFortune.value[key].trim() === ''
  })
})

const dailyPersonalizedState = computed(() => {
  if (!fortune.value) {
    return 'hidden'
  }

  if (!isLoggedIn.value) {
    return 'guest'
  }

  if (isPersonalizedPayloadInvalid.value) {
    return 'error'
  }

  if (personalizedFortune.value?.hasBazi) {
    return 'ready'
  }

  return 'no_bazi'
})

const overallStarCount = computed(() => {
  const score = Number(fortune.value?.overallScore ?? 0)
  if (!Number.isFinite(score) || score <= 0) {
    return 0
  }

  if (score >= 85) return 5
  if (score >= 70) return 4
  if (score >= 55) return 3
  if (score >= 40) return 2
  return 1
})

const overallStarLabel = computed(() => {
  if (overallStarCount.value <= 0) {
    return '评分整理中'
  }

  return `视觉评级：${overallStarCount.value} 星`
})

const getScoreColor = (score) => {

  if (score >= 80) return '#67c23a'
  if (score >= 60) return '#e6a23c'
  return '#f56c6c'
}

const getScoreClass = (score) => {
  if (score >= 80) return 'excellent'
  if (score >= 60) return 'good'
  return 'normal'
}

const getAspectIcon = (aspectName = '') => {
  if (aspectName.includes('事业')) return Briefcase
  if (aspectName.includes('财')) return Money
  if (aspectName.includes('感情') || aspectName.includes('爱情')) return StarFilled
  if (aspectName.includes('健康')) return Sunny
  return MagicStick
}

const hasAspectCards = computed(() => {
  return Array.isArray(fortune.value?.aspects) && fortune.value.aspects.some((aspect) => {
    return Boolean(aspect?.name || aspect?.description || Number.isFinite(Number(aspect?.score)))
  })
})

const hasYiItems = computed(() => Array.isArray(fortune.value?.yi) && fortune.value.yi.length > 0)
const hasJiItems = computed(() => Array.isArray(fortune.value?.ji) && fortune.value.ji.length > 0)
const hasLuckySection = computed(() => hasYiItems.value || hasJiItems.value)

const detailSections = computed(() => {
  const details = fortune.value?.details || {}
  return [
    { key: 'career', title: '事业运势', content: details.career },
    { key: 'wealth', title: '财运运势', content: details.wealth },
    { key: 'love', title: '感情运势', content: details.love },
    { key: 'health', title: '健康运势', content: details.health },
  ].filter((item) => typeof item.content === 'string' && item.content.trim())
})

const defaultExpandedDetailNames = computed(() => {
  return detailSections.value.length ? [detailSections.value[0].key] : []
})

const loadDailyFortune = async ({ userInitiated = false } = {}) => {

  isLoading.value = true
  error.value = false
  errorMessage.value = ''
  solarDate.value = ''
  lunarDate.value = ''

  try {
    const response = await getDailyFortune()
    if (response.code === 200) {
      const data = response.data || {}
      fortune.value = {
        ...data,
        yi: data.yi || [],
        ji: data.ji || [],
        aspects: data.aspects || [],
        details: data.details || {},
        personalized: data.personalized || null
      }
      solarDate.value = data.date || ''
      lunarDate.value = data.lunarDate || ''
      activeNames.value = defaultExpandedDetailNames.value
      error.value = false
    } else {
      fortune.value = null
      solarDate.value = ''
      lunarDate.value = ''
      activeNames.value = []
      error.value = true
      errorMessage.value = response.message || '获取运势失败'
      if (userInitiated) {
        ElMessage.error(errorMessage.value)
      }
    }
  } catch (err) {
    fortune.value = null
    solarDate.value = ''
    lunarDate.value = ''
    activeNames.value = []
    error.value = true
    errorMessage.value = '网络错误，请稍后重试'
    if (userInitiated) {
      ElMessage.error(errorMessage.value)
    }
    console.error(err)
  } finally {

    isLoading.value = false
  }
}



const handleVisibilityChange = () => {
  if (document.visibilityState === 'visible') {
    syncLoginState()
  }
}

onMounted(() => {
  syncLoginState()
  loadDailyFortune()
  window.addEventListener('storage', syncLoginState)
  document.addEventListener('visibilitychange', handleVisibilityChange)
})

onUnmounted(() => {
  window.removeEventListener('storage', syncLoginState)
  document.removeEventListener('visibilitychange', handleVisibilityChange)
})


</script>

<style scoped>
.daily-page {
  padding: 60px 0;
}

.date-display {

  max-width: 600px;
  margin: 0 auto 30px;
  display: flex;
  justify-content: space-around;
  padding: 20px;
  background: var(--bg-card);
  border-radius: var(--radius-card);
}

.date-display--loading {
  gap: 24px;
}

.date-skeleton-block {
  flex: 1;
}

.date-skeleton-label,
.date-skeleton-value {
  display: block;
  width: 100%;
}

.date-skeleton-label {
  max-width: 72px;
  margin: 0 auto 12px;
}

.date-skeleton-value {
  max-width: 180px;
  height: 28px;
  margin: 0 auto;
}


.lunar-date,
.solar-date {
  text-align: center;
}

.label {
  display: block;
  font-size: 14px;
  color: var(--text-tertiary);
  margin-bottom: 5px;
}

.value {
  font-size: 20px;
  color: var(--text-primary);
  font-weight: 500;
}

.fortune-content {
  max-width: 800px;
  margin: 0 auto;
}

.overall-score {
  text-align: center;
  margin-bottom: 30px;
}

.overall-score h2 {
  color: var(--text-primary);
  margin-bottom: 30px;
}

.score-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 20px;
}

.score-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: var(--primary-gradient);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  margin-bottom: 15px;
}

.score-number {
  font-size: 48px;
  font-weight: bold;
  color: var(--text-primary);
}

@media (max-width: 768px) {
  .score-number {
    font-size: 32px;
  }
}

.score-label {
  font-size: 14px;
  color: var(--text-secondary);
}

.score-stars {
  display: flex;
  gap: 5px;
}

.score-rating {
  margin: 10px 0 0;
  font-size: 13px;
  color: var(--text-tertiary);
}

.star {
  font-size: 20px;
  color: var(--text-tertiary);
}

.star.filled {
  color: var(--star-color);
}

.fortune-summary {

  color: var(--text-secondary);
  font-size: 16px;
  line-height: 1.6;
}

.section-empty {
  max-width: 800px;
  margin: 0 auto 30px;
  text-align: center;
}

.section-empty h3,
.section-empty p {
  margin: 0;
}

.section-empty h3 {
  color: var(--text-primary);
  margin-bottom: 10px;
}

.section-empty p {
  color: var(--text-secondary);
  line-height: 1.7;
}

.section-empty--compact {
  max-width: none;
  margin: 0;
  padding: 6px 0 0;
}

.aspect-grid {

  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 30px;
}

.aspect-card {
  text-align: center;
  border: 1px solid var(--border-light);
  background: linear-gradient(180deg, var(--bg-card), var(--surface-raised));
}

.aspect-icon {
  width: 56px;
  height: 56px;
  margin: 0 auto 14px;
  border-radius: var(--radius-round);
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-light-15), var(--primary-light-05));
  border: 1px solid var(--primary-light-20);
  color: var(--primary-light);
  font-size: 26px;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

.aspect-card h3 {
  color: var(--text-primary);
  margin-bottom: 15px;
  font-size: 18px;
}

.aspect-score {
  margin-bottom: 15px;
}

.aspect-desc {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.7;
}

.lucky-section {
  margin-bottom: 30px;
}

.lucky-section h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
  text-align: center;
}

.lucky-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
}

.lucky-item {
  display: flex;
  align-items: flex-start;
  gap: 15px;
}

.lucky-label {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: bold;
  flex-shrink: 0;
}

.lucky-item.good .lucky-label {
  background: var(--success-color);
  color: var(--text-primary);
}

.lucky-item.bad .lucky-label {
  background: var(--danger-color);
  color: var(--text-primary);
}

.lucky-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.details-section h3 {
  color: var(--text-primary);
  margin-bottom: 20px;
  text-align: center;
}

.details-section p {
  color: var(--text-secondary);
  line-height: 1.8;
}

.daily-action-zone {
  margin-top: 30px;
}

.guest-checkin-card {
  margin-bottom: 0;
}

.loading-state {

  max-width: 800px;
  margin: 0 auto;
}

/* 错误状态样式 */
.error-state {
  max-width: 600px;
  margin: 50px auto;
  text-align: center;
  padding: 50px 30px;
}

.error-icon {
  color: var(--danger-color);
  margin-bottom: 20px;
}

.error-message {
  color: var(--text-secondary);
  font-size: 16px;
  margin-bottom: 25px;
}

/* 个性化运势样式 */
.personalized-fortune {
  margin-bottom: 30px;
  background: linear-gradient(135deg, var(--primary-light-10), var(--primary-light-05));
  border: 1px solid var(--primary-light-30);
}

.personalized-fortune h2 {
  color: var(--text-primary);
  text-align: center;
  margin-bottom: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.help-icon {
  cursor: help;
  opacity: 0.7;
  font-size: 16px;
}

.master-info {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.master-card, .today-card {
  background: var(--bg-tertiary);
  border-radius: 16px;
  padding: 20px 30px;
  text-align: center;
  min-width: 120px;
}

.master-card .label, .today-card .label {
  display: block;
  font-size: 12px;
  color: var(--text-tertiary);
  margin-bottom: 8px;
}

.master-card .value, .today-card .value {
  display: block;
  font-size: 32px;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 8px;
}

.wuxing-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 12px;
}

.wuxing-badge.金 { background: var(--primary-light-15); color: var(--wuxing-jin); }
.wuxing-badge.木 { background: rgba(34, 139, 34, 0.15); color: var(--wuxing-mu); }
.wuxing-badge.水 { background: rgba(30, 144, 255, 0.15); color: var(--wuxing-shui); }
.wuxing-badge.火 { background: rgba(255, 69, 0, 0.15); color: var(--wuxing-huo); }
.wuxing-badge.土 { background: rgba(139, 69, 19, 0.15); color: var(--wuxing-tu); }

.wuxing-text {
  font-size: 12px;
  color: var(--text-tertiary);
}

.relation-arrow {
  font-size: 24px;
  color: var(--text-muted);
}

.luck-indicator {
  display: grid;
  grid-template-columns: minmax(180px, auto) minmax(0, 1fr) auto;
  align-items: stretch;
  gap: 16px;
  margin-bottom: 25px;
}

.luck-badge {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  gap: 6px;
  padding: 18px 22px;
  border-radius: 16px;
  color: var(--text-primary);
}

.luck-relation-name {
  font-size: 24px;
  font-weight: 700;
  line-height: 1.2;
}

.luck-relation-title {
  font-size: 14px;
  color: var(--text-secondary);
}

.luck-summary {
  min-width: 0;
  padding: 18px 20px;
  border-radius: 16px;
  border: 1px solid var(--border-light);
  background: linear-gradient(180deg, var(--bg-card), var(--surface-raised));
}

.luck-summary-label {
  display: block;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: var(--weight-semibold);
  color: var(--primary-light);
}

.luck-summary p {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.7;
  font-size: 14px;
}

.luck-badge.吉 {
  background: linear-gradient(135deg, var(--success-light), rgba(103, 194, 58, 0.1));
  border: 1px solid rgba(103, 194, 58, 0.4);
}

.luck-badge.凶 {
  background: linear-gradient(135deg, var(--danger-light), rgba(245, 108, 108, 0.1));
  border: 1px solid rgba(245, 108, 108, 0.4);
}

.luck-badge.平 {
  background: linear-gradient(135deg, rgba(144, 147, 153, 0.1), rgba(144, 147, 153, 0.05));
  border: 1px solid rgba(144, 147, 153, 0.2);
}

.luck-level {
  font-size: 14px;
  font-weight: normal;
  opacity: 0.8;
}

.personal-score {
  min-width: 110px;
  text-align: center;
  align-self: center;
}


.score-label {
  display: block;
  font-size: 12px;
  color: var(--text-tertiary);
  margin-bottom: 5px;
}

.score-value {
  font-size: 36px;
  font-weight: bold;
}

.score-value.excellent { color: var(--success-color); }
.score-value.good { color: var(--warning-color); }
.score-value.normal { color: var(--danger-color); }

.personal-advice {
  background: var(--bg-card);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 25px;
}

.personal-advice h4 {
  color: var(--primary-color);
  margin-bottom: 10px;
}

.personal-advice p {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 15px;
}

.personal-lucky-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.personal-lucky-item {
  min-height: 132px;
  background: linear-gradient(180deg, var(--bg-card), var(--surface-raised));
  border-radius: var(--radius-lg);
  padding: 18px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 16px;
  border: 1px solid var(--border-light);
}

.personal-lucky-item--color {
  box-shadow: inset 0 1px 0 rgba(var(--primary-rgb), 0.1);
}

.personal-lucky-item--direction {
  box-shadow: inset 0 1px 0 rgba(var(--primary-light-rgb), 0.08);
}

.personal-lucky-head {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.personal-lucky-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: var(--primary-light);
  background: linear-gradient(135deg, var(--primary-light-20), var(--primary-light-05));
  border: 1px solid var(--primary-light-20);
  font-size: 20px;
}

.personal-lucky-meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.personal-lucky-label {
  font-size: 15px;
  font-weight: var(--weight-semibold);
  color: var(--text-primary);
}

.personal-lucky-caption {
  font-size: var(--font-caption);
  color: var(--text-tertiary);
  line-height: 1.6;
}

.personal-lucky-values {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.lucky-tag {
  min-height: 36px;
  padding: 7px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: var(--weight-medium);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.lucky-tag.color {
  background: rgba(var(--primary-rgb), 0.14);
  border: 1px solid rgba(var(--primary-rgb), 0.18);
  color: var(--primary-light);
}

.lucky-tag.direction {
  background: rgba(var(--primary-light-rgb), 0.12);
  border: 1px solid rgba(var(--primary-light-rgb), 0.18);
  color: var(--text-primary);
}

/* 个性化状态提示 */
.personalized-state-card {
  margin-bottom: 30px;
  border: 1px solid var(--border-light);
  background: linear-gradient(180deg, var(--bg-card), var(--surface-raised));
}

.personalized-state-card--empty {
  box-shadow: inset 0 1px 0 rgba(var(--primary-rgb), 0.08);
}

.personalized-state-card--error {
  border-color: rgba(245, 108, 108, 0.24);
  background: linear-gradient(180deg, rgba(245, 108, 108, 0.06), var(--bg-card));
}

.state-content {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 24px 26px;
}

.state-icon {
  flex-shrink: 0;
  color: var(--primary-light);
}

.personalized-state-card--error .state-icon {
  color: var(--danger-color);
}

.state-body {
  flex: 1;
  min-width: 0;
}

.state-title {
  margin: 0 0 8px;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: var(--weight-semibold);
}

.state-copy {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.7;
}

.state-copy--error {
  color: var(--text-primary);
}

.state-actions {
  display: flex;
  align-items: center;
  justify-content: center;
}

.state-actions :deep(.el-button) {
  min-height: 44px;
  border-radius: 999px;
  padding-inline: 18px;
}


@media (max-width: 768px) {
  .aspect-grid {
    grid-template-columns: 1fr;
  }
  
  .lucky-grid {
    grid-template-columns: 1fr;
  }
  
  .date-display {
    flex-direction: column;
    gap: 15px;
  }
  
  .master-info {
    flex-direction: column;
  }
  
  .relation-arrow {
    transform: rotate(90deg);
  }
  
  .luck-indicator {
    grid-template-columns: 1fr;
  }

  .luck-badge,
  .luck-summary,
  .personal-score {
    width: 100%;
  }

  .personal-score {
    align-self: stretch;
  }
  
  .personal-lucky-grid {
    grid-template-columns: 1fr;
  }

  .personal-lucky-item {
    min-height: auto;
    padding: 16px;
  }

  .personal-lucky-head {
    align-items: center;
  }

  .personal-lucky-values {
    gap: 8px;
  }

  .lucky-tag {
    min-height: 34px;
  }

  .state-content {
    flex-direction: column;
    text-align: center;
    padding: 22px 18px;
  }

  .state-actions {
    width: 100%;
  }

  .state-actions :deep(.el-button) {
    width: 100%;
  }
}


/* 2026-03 UI polish: daily refresh */
.daily-page {
  padding: 10px 0 78px;
  background:
    radial-gradient(circle at top left, rgba(var(--primary-rgb), 0.12), transparent 30%),
    radial-gradient(circle at 90% 10%, rgba(245, 196, 103, 0.16), transparent 24%),
    linear-gradient(180deg, #fffdf8 0%, #fff9f2 48%, #fff7ef 100%);
}

.fortune-content {
  max-width: 920px;
  display: grid;
  gap: 24px;
}

.date-display,
.personalized-fortune,
.personalized-state-card,
.overall-score,
.aspect-card,
.lucky-section,
.details-section,
.section-empty,
.loading-state,
.error-state {
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08), 0 10px 28px rgba(var(--primary-rgb), 0.05);
}

.date-display,
.personalized-fortune,
.personalized-state-card,
.overall-score,
.lucky-section,
.details-section,
.section-empty,
.loading-state,
.error-state {
  border-radius: 28px;
}

.date-display {
  max-width: 920px;
  margin-bottom: 0;
  padding: 22px 24px;
  background: linear-gradient(135deg, rgba(255, 252, 246, 0.98), rgba(255, 245, 225, 0.94));
}

.lunar-date,
.solar-date {
  flex: 1;
  padding: 12px 18px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid rgba(var(--primary-rgb), 0.08);
}

.label {
  margin-bottom: 8px;
  letter-spacing: 0.04em;
}

.value {
  font-size: 24px;
  font-weight: 700;
}

.personalized-fortune,
.personalized-state-card,
.overall-score,
.lucky-section,
.details-section,
.section-empty {
  padding: 28px;
}

.personalized-fortune {
  margin-bottom: 0;
  background: linear-gradient(180deg, rgba(255, 253, 248, 0.98), rgba(255, 245, 226, 0.94));
}

.personalized-fortune h2,
.overall-score h2,
.lucky-section h3,
.details-section h3 {
  font-size: clamp(24px, 3vw, 30px);
  font-weight: 800;
  letter-spacing: -0.02em;
}

.master-card,
.today-card,
.luck-summary,
.personal-score,
.personal-lucky-item {
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  background: rgba(255, 255, 255, 0.92);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.74);
}

.master-card,
.today-card {
  min-width: 180px;
  padding: 22px 28px;
  border-radius: 22px;
}

.relation-arrow {
  width: 52px;
  height: 52px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #8f601b;
  background: rgba(var(--primary-rgb), 0.08);
  border: 1px solid rgba(var(--primary-rgb), 0.1);
}

.luck-indicator {
  display: grid;
  grid-template-columns: minmax(180px, 220px) 1fr auto;
  gap: 16px;
  align-items: stretch;
}

.luck-badge,
.luck-summary,
.personal-score {
  border-radius: 22px;
  padding: 20px;
}

.luck-badge {
  background: linear-gradient(180deg, rgba(var(--primary-rgb), 0.14), rgba(245, 196, 103, 0.16));
}

.luck-summary-label,
.score-label,
.personal-lucky-label,
.personal-lucky-caption {
  color: #6a5b47;
}

.personal-score {
  min-width: 150px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 10px;
}

.score-value {
  text-shadow: 0 10px 24px rgba(var(--primary-rgb), 0.14);
}

.personal-lucky-grid {
  gap: 16px;
}

.personal-lucky-item {
  border-radius: 22px;
  padding: 20px;
}

.personal-lucky-icon {
  width: 48px;
  height: 48px;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(var(--primary-rgb), 0.08);
  color: var(--primary-color);
}

.personalized-state-card {
  margin-bottom: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 240, 0.94));
}

.state-content {
  align-items: center;
  gap: 20px;
}

.state-icon {
  flex-shrink: 0;
  width: 72px;
  height: 72px;
  border-radius: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(var(--primary-rgb), 0.08);
  color: #95661a;
}

.state-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--text-primary);
}

.state-copy {
  color: #605545;
  line-height: 1.8;
}

.overall-score {
  margin-bottom: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 248, 236, 0.95));
}

.score-display {
  gap: 14px;
  margin-bottom: 24px;
}

.score-circle {
  width: 138px;
  height: 138px;
  box-shadow: 0 20px 36px rgba(var(--primary-rgb), 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.score-number {
  font-size: 52px;
}

.score-rating,
.fortune-summary,
.aspect-desc,
.details-section p {
  color: #605545;
  line-height: 1.8;
}

.aspect-grid {
  gap: 18px;
  margin-bottom: 0;
}

.aspect-card {
  padding: 24px 22px;
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 240, 0.94));
}

.aspect-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 26px 44px rgba(15, 23, 42, 0.1), 0 12px 28px rgba(var(--primary-rgb), 0.07);
}

.lucky-section,
.details-section {
  margin-bottom: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 241, 0.94));
}

.lucky-grid {
  gap: 18px;
}

.lucky-item {
  padding: 18px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.76);
  border: 1px solid rgba(var(--primary-rgb), 0.08);
}

.lucky-label {
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
}

:deep(.details-section .el-collapse) {
  border-top: none;
  border-bottom: none;
}

:deep(.details-section .el-collapse-item__header) {
  min-height: 60px;
  padding: 0 8px;
  font-weight: 700;
  color: var(--text-primary);
  background: transparent;
  border-bottom-color: rgba(var(--primary-rgb), 0.1);
}

:deep(.details-section .el-collapse-item__wrap) {
  background: transparent;
  border-bottom-color: rgba(var(--primary-rgb), 0.1);
}

:deep(.details-section .el-collapse-item__content) {
  padding: 8px 8px 18px;
}

.daily-action-zone {
  margin-top: 0;
}

@media (max-width: 900px) {
  .luck-indicator {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .daily-page {
    padding: 0 0 56px;
  }

  .fortune-content {
    gap: 18px;
  }

  .date-display,
  .personalized-fortune,
  .personalized-state-card,
  .overall-score,
  .lucky-section,
  .details-section,
  .section-empty,
  .loading-state,
  .error-state {
    padding: 20px 18px;
    border-radius: 24px;
  }

  .date-display {
    flex-direction: column;
    gap: 12px;
  }

  .master-card,
  .today-card,
  .personal-score {
    width: 100%;
    min-width: 0;
  }

  .state-content {
    align-items: flex-start;
  }

  .state-icon {
    width: 60px;
    height: 60px;
    border-radius: 20px;
  }

  .score-circle {
    width: 122px;
    height: 122px;
  }

  .score-number {
    font-size: 40px;
  }

  .aspect-grid,
  .lucky-grid,
  .personal-lucky-grid {
    grid-template-columns: 1fr;
  }
}
</style>

