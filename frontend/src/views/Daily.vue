<template>
  <div class="daily-page">
    <div class="container">
      <PageHeroHeader
        title="每日运势"
        subtitle="开启美好一天，为您提供精准的运势分析和实用建议"
        :icon="Calendar"
      />

      <!-- 运势概览卡片 -->
      <AsyncState :status="dailyStatus" :error="errorMessage" loadingText="正在为您推算今日运势..." @retry="loadDailyFortune">
        <template #loading>
          <div class="fortune-overview fortune-overview--loading card card-hover">
            <div class="overview-skeleton">
              <el-skeleton-item variant="circle" class="score-skeleton" />
              <div class="skeleton-content">
                <el-skeleton-item variant="text" class="title-skeleton" />
                <el-skeleton-item variant="text" class="subtitle-skeleton" />
              </div>
            </div>
          </div>
          <div class="loading-state card card-hover">
            <el-skeleton :rows="10" animated />
          </div>
        </template>

        <div v-if="fortune" class="fortune-content">
          <!-- 运势概览卡片 -->
          <div class="fortune-overview card">
            <div class="overview-top">
              <div class="overview-date">
                <span class="overview-solar">{{ solarDate }}</span>
                <span class="overview-lunar">{{ lunarDate }}</span>
              </div>
              <el-tooltip content="刷新运势" placement="top">
                <button class="overview-refresh" :class="{ loading: isLoading }" @click="loadDailyFortune({ userInitiated: true })">
                  <el-icon><RefreshRight /></el-icon>
                </button>
              </el-tooltip>
            </div>
            <div class="overview-body">
              <div class="overview-score-wrap">
                <div class="overview-score-ring" :class="getScoreClass(fortune.overall)">
                  <svg viewBox="0 0 100 100" class="ring-svg">
                    <circle cx="50" cy="50" r="42" class="ring-track" />
                    <circle cx="50" cy="50" r="42" class="ring-fill" :style="{ strokeDashoffset: 264 - (264 * Math.min(fortune.overall, 100) / 100) }" />
                  </svg>
                  <div class="ring-inner">
                    <span class="ring-num">{{ fortune.overall }}</span>
                    <span class="ring-unit">分</span>
                  </div>
                </div>
                <div class="overview-stars">
                  <el-icon v-for="n in 5" :key="n" class="ov-star" :class="{ filled: n <= overallStarCount }"><StarFilled /></el-icon>
                  <span class="ov-star-label">{{ overallStarLabel }}</span>
                </div>
              </div>
              <div class="overview-summary">
                <p class="summary-text">{{ fortune.summary || '今日运势平稳，适合按部就班推进计划' }}</p>
              </div>
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




        <div v-if="hasAspectCards" class="aspect-grid">
          <div class="aspect-card card" v-for="aspect in fortune.aspects" :key="aspect.name">
            <div class="aspect-card__top">
              <div class="aspect-icon-wrap">
                <el-icon><component :is="getAspectIcon(aspect.name)" /></el-icon>
              </div>
              <div class="aspect-meta">
                <span class="aspect-name">{{ aspect.name }}</span>
                <span class="aspect-score-num" :style="{ color: getScoreColor(aspect.score) }">{{ aspect.score }}</span>
              </div>
            </div>
            <div class="aspect-bar-wrap">
              <div class="aspect-bar-track">
                <div class="aspect-bar-fill" :style="{ width: aspect.score + '%', background: getScoreColor(aspect.score) }"></div>
              </div>
            </div>
            <p class="aspect-desc">{{ aspect.description }}</p>
          </div>
        </div>
        <div v-else class="section-empty card">
          <h3>分项运势整理中</h3>
          <p>今日事业、财运、感情与健康的细分运势还在生成，稍后再来看看。</p>
        </div>

        <div class="lucky-section card">
          <div class="lucky-header">
            <h3>今日宜忌</h3>
            <button v-if="hasLuckySection" class="calendar-btn" @click="addToCalendar">
              <el-icon><Calendar /></el-icon> 添加到日历
            </button>
          </div>
          <div v-if="hasLuckySection" class="lucky-grid">
            <div v-if="hasYiItems" class="lucky-item lucky-item--yi">
              <div class="lucky-badge lucky-badge--yi">宜</div>
              <div class="lucky-tags">
                <span v-for="item in fortune.yi" :key="item" class="lucky-chip lucky-chip--yi">{{ item }}</span>
              </div>
            </div>
            <div v-if="hasJiItems" class="lucky-item lucky-item--ji">
              <div class="lucky-badge lucky-badge--ji">忌</div>
              <div class="lucky-tags">
                <span v-for="item in fortune.ji" :key="item" class="lucky-chip lucky-chip--ji">{{ item }}</span>
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

        <!-- 明日预告模块 -->
        <div v-if="showTomorrowPreview" class="tomorrow-preview-section card card-hover">
          <h3><el-icon><Sunrise /></el-icon> 明日预告</h3>
          <div class="tomorrow-content">
            <div class="tomorrow-score">
              <span class="score-label">明日综合运势</span>
              <div class="score-stars">
                <el-icon v-for="n in 5" :key="n" class="star" :class="{ filled: n <= tomorrowStarCount }">
                  <StarFilled />
                </el-icon>
              </div>
            </div>
            <p class="tomorrow-summary">{{ tomorrowSummary }}</p>
            <div class="tomorrow-action">
              <el-button type="primary" plain size="small" @click="loadTomorrowFortune">
                提前查看明日运势
              </el-button>
            </div>
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

        <!-- 深度引导区：将日运用户引向更多功能 -->
        <div class="daily-deepen-section">
          <p class="deepen-title">想了解更多？</p>
          <div class="deepen-cards">
            <router-link to="/bazi" class="deepen-card">
              <span class="deepen-symbol">☯</span>
              <div class="deepen-info">
                <strong>八字排盘</strong>
                <span>基于生辰的深度性格与运势分析</span>
              </div>
              <el-icon><ArrowRight /></el-icon>
            </router-link>
            <router-link to="/tarot" class="deepen-card">
              <span class="deepen-symbol">✴</span>
              <div class="deepen-info">
                <strong>塔罗占卜</strong>
                <span>聚焦当下困惑，获得具体指引</span>
              </div>
              <el-icon><ArrowRight /></el-icon>
            </router-link>
          </div>
        </div>
        </div>
      </AsyncState>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { MagicStick, QuestionFilled, Collection, WarningFilled, StarFilled, Right, Compass, Briefcase, Money, Sunny, UserFilled, RefreshRight, Calendar, Present, Sunrise, ArrowRight } from '@element-plus/icons-vue'
import { getDailyFortune } from '../api'
import CheckinCard from '../components/CheckinCard.vue'
import PageHeroHeader from '../components/PageHeroHeader.vue'
import AsyncState from '../components/AsyncState.vue'

const solarDate = ref('')
const lunarDate = ref('')
const isLoading = ref(true)
const fortune = ref(null)
const isLoggedIn = ref(false)
const activeNames = ref([])
const error = ref(false)
const errorMessage = ref('')
const dailyLoginRoute = { path: '/login', query: { redirect: '/daily' } }

const dailyStatus = computed(() => {
  if (isLoading.value) return 'loading'
  if (error.value) return 'error'
  if (fortune.value) return 'success'
  return 'empty'
})

// 明日预告相关状态
const showTomorrowPreview = ref(false)
const tomorrowStarCount = ref(0)
const tomorrowSummary = ref('')

const checkTomorrowPreview = () => {
  const now = new Date()
  const hour = now.getHours()
  // 18:00 后显示明日预告
  if (hour >= 18) {
    showTomorrowPreview.value = true
    // 模拟明日运势数据，实际应从后端获取
    tomorrowStarCount.value = Math.floor(Math.random() * 3) + 3 // 3-5星
    tomorrowSummary.value = '明日运势平稳，适合按部就班推进计划，注意劳逸结合。'
  } else {
    showTomorrowPreview.value = false
  }
}

const loadTomorrowFortune = () => {
  ElMessage.info('明日运势功能开发中，敬请期待')
}

const addToCalendar = () => {
  if (!fortune.value) return

  const title = `太初命理 - 今日运势 (${solarDate.value})`
  let description = `综合评分：${fortune.value.overallScore}分\n`
  description += `运势简评：${fortune.value.summary}\n\n`
  
  if (hasYiItems.value) {
    description += `宜：${fortune.value.yi.join('、')}\n`
  }
  if (hasJiItems.value) {
    description += `忌：${fortune.value.ji.join('、')}\n`
  }
  
  if (personalizedFortune.value?.advice) {
    description += `\n专属建议：${personalizedFortune.value.advice}`
  }

  // 生成 ICS 文件内容
  const now = new Date()
  const dtstamp = now.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z'
  
  // 设置为全天事件
  const dateStr = solarDate.value.replace(/-/g, '')
  const dtstart = dateStr
  
  // 结束日期为开始日期的下一天
  const nextDay = new Date(solarDate.value)
  nextDay.setDate(nextDay.getDate() + 1)
  const dtend = nextDay.toISOString().split('T')[0].replace(/-/g, '')

  const icsContent = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Taichu//Daily Fortune//CN',
    'BEGIN:VEVENT',
    `DTSTAMP:${dtstamp}`,
    `DTSTART;VALUE=DATE:${dtstart}`,
    `DTEND;VALUE=DATE:${dtend}`,
    `SUMMARY:${title}`,
    `DESCRIPTION:${description.replace(/\n/g, '\\n')}`,
    'END:VEVENT',
    'END:VCALENDAR'
  ].join('\r\n')

  const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' })
  const link = document.createElement('a')
  link.href = window.URL.createObjectURL(blob)
  link.download = `今日运势_${dateStr}.ics`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  
  ElMessage.success('已生成日历文件，请在下载后打开以添加到系统日历')
}

const personalizedRelationGuides = {
  '天合地合': {
    title: '新际遇有成',
    description: '你内心深处一直在等待某个时机，而今天，那种感觉终于对了。你不需要解释太多，事情会自然而然地朝你期待的方向走——这不是运气，而是你一直以来的积累在今天开花。',
  },
  '天合地会': {
    title: '守成之事，近亲相助',
    description: '你有一种让人感到安心的气质，今天这种特质会为你带来意想不到的助力。那些你以为不会开口的人，今天可能会主动向你靠近。',
  },
  '天合地刑': {
    title: '外合心不合',
    description: '你善于在别人面前保持体面，但今天你的内心可能比外表更复杂。那种说不清道不明的别扭感，其实是你在提醒自己：有些情绪需要被看见，而不是被压下去。',
  },
  '天比地合': {
    title: '以智取之',
    description: '你有一种别人不容易察觉的洞察力，今天这种能力会在关键时刻发挥作用。你不需要强迫任何人，只需要做你自己，合适的人自然会被你吸引过来。',
  },
  '天比地冲': {
    title: '外象平安，内实空虚',
    description: '你有时会用忙碌来掩盖内心的空洞感，今天这种感觉可能会更明显。表面的平静背后，你其实比任何人都清楚，有些事情还没有真正落地。',
  },
  '天比地刑': {
    title: '处于长期内争',
    description: '你对自己的要求很高，有时甚至有些苛刻。今天这种内在的张力可能会向外投射，让你觉得周围的人都不太对劲——但也许，需要和解的对象只是你自己。',
  },
  '天克地冲': {
    title: '创业性的碰钉子',
    description: '你不是那种轻易认输的人，但今天你可能会遇到一种特殊的阻力——它不来自外部，而是来自某种时机上的错位。越是用力，越是感到吃力，这不是你的问题，只是今天不是那个适合强攻的时机。',
  },
  '克天地冲': {
    title: '偿债式的说好话',
    description: '你其实比外表看起来更在意别人的看法，尽管你不常承认这一点。今天你可能会发现自己处于一种被动的位置，但这并不意味着你失去了主动权——只是换了一种方式在运作。',
  },
  '天生地冲': {
    title: '小有成就',
    description: '你有一种在混乱中找到秩序的天赋，今天这种能力会在不经意间为你带来收获。那些看似偶然的小事，背后往往有你自己都没意识到的努力在支撑。',
  },
  '生天地冲': {
    title: '因小祸而得福',
    description: '你有一种别人羡慕的韧性，总能在跌倒后重新站起来。今天可能会有一个小小的挫折，但你内心深处其实已经知道，这不过是更好的事情到来之前的一个小插曲。',
  },
  '天生地合': {
    title: '助他人之故而获利',
    description: '你是那种愿意为别人付出的人，而今天，这种善意会以一种你意想不到的方式回到你身上。你不需要刻意去追求什么，只需要做你一直在做的事。',
  },
  '生天地合': {
    title: '借他人之力而有成',
    description: '你身边一直有人在默默关注你，只是你可能还没有意识到。今天，某个人会以一种恰到好处的方式出现，而你需要做的，只是允许自己接受这份帮助。',
  },
  '天生地刑': {
    title: '自找麻烦',
    description: '你有一颗真诚想帮助别人的心，但今天你的好意可能会被误读，或者带来你没有预料到的复杂局面。有时候，最好的帮助是给对方空间，而不是亲自下场。',
  },
  '生天地刑': {
    title: '他人加于自己的麻烦',
    description: '你有一种容易被别人依赖的气场，这是你的魅力，但今天它可能会把你带入一个你本不需要参与的局面。你的冷静和理性，是今天最重要的保护。',
  },
  '地比天克': {
    title: '有信心中应付强对手',
    description: '你比自己以为的更有实力，只是有时候你自己不太相信这一点。今天你会遇到某种考验，而你内心深处其实早就准备好了——你只需要相信那个已经准备好的自己。',
  },
  '地比克天': {
    title: '渐失信心中坚持努力',
    description: '你是一个对自己有要求的人，正因如此，今天那种“怎么努力都差一口气”的感觉会格外刺痛。但这不是你的终点，只是一个让你重新审视方向的信号——守住已有的，比强行突破更明智。',
  },
  '天克地刑': {
    title: '以债养债',
    description: '你有时会用行动来回避思考，用忙碌来掩盖某种深层的不安。今天这种模式可能会让事情变得更复杂，而不是更简单。真正的解决，往往需要你先停下来，面对那个你一直在绕开的问题。',
  },
  '克天地刑': {
    title: '抵押偿债',
    description: '你习惯独自承担压力，不太愿意让别人看到你吃力的一面。今天这种压力可能会以一种具体的形式出现，提醒你：懂得保留余地，是一种智慧，不是退缩。',
  },
  '伏吟': {
    title: '多此一举',
    description: '你是那种不轻易放弃的人，但今天的努力可能会让你感到一种说不清的疲惫——不是因为你不够努力，而是方向需要重新校准。停下来，往往比继续走更需要勇气。',
  },
  '空亡': {
    title: '事倍而功半',
    description: '你付出的努力，并不总是能立刻看到回报——今天尤其如此。这不是你的失败，而是某种积累在悄悄发生。有时候，什么都不做，也是一种选择。',
  },
  '普通': {
    title: '运势平稳',
    description: '你是一个内心比外表更丰富的人，今天这种平静的表面下，其实有很多细腻的感受在流动。不是每一天都需要大起大落，有时候，平稳本身就是一种珍贵的状态。',
  }
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

const DAILY_CACHE_KEY = 'daily_fortune_cache'

const getTodayDateStr = () => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const readDailyCache = () => {
  try {
    const raw = localStorage.getItem(DAILY_CACHE_KEY)
    if (!raw) return null
    const cached = JSON.parse(raw)
    if (cached.date === getTodayDateStr()) return cached.data
    // 日期已过，清除旧缓存
    localStorage.removeItem(DAILY_CACHE_KEY)
    return null
  } catch {
    return null
  }
}

const writeDailyCache = (data) => {
  try {
    localStorage.setItem(DAILY_CACHE_KEY, JSON.stringify({ date: getTodayDateStr(), data }))
  } catch {
    // 缓存写入失败不影响主流程
  }
}

const loadDailyFortune = async ({ userInitiated = false } = {}) => {

  // 非用户主动刷新时，优先读取当天缓存
  if (!userInitiated) {
    const cached = readDailyCache()
    if (cached) {
      fortune.value = {
        ...cached,
        yi: cached.yi || [],
        ji: cached.ji || [],
        aspects: cached.aspects || [],
        details: cached.details || {},
        personalized: cached.personalized || null
      }
      solarDate.value = cached.date || ''
      lunarDate.value = cached.lunarDate || ''
      activeNames.value = defaultExpandedDetailNames.value
      error.value = false
      isLoading.value = false
      return
    }
  }

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
      // 成功后写入当天缓存
      writeDailyCache(data)
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
  checkTomorrowPreview()
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

.lucky-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.lucky-header h3 {
  color: var(--text-primary);
  margin: 0;
}

.add-calendar-btn {
  border-radius: 16px;
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

.luck-badge.大吉,
.luck-badge.吉,
.luck-badge.小吉 {
  background: linear-gradient(180deg, rgba(103, 194, 58, 0.14), rgba(103, 194, 58, 0.16));
}

.luck-badge.大凶,
.luck-badge.凶 {
  background: linear-gradient(180deg, rgba(245, 108, 108, 0.14), rgba(245, 108, 108, 0.16));
}

.luck-badge.平 {
  background: linear-gradient(180deg, rgba(var(--primary-rgb), 0.14), rgba(245, 196, 103, 0.16));
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

/* 运势概览卡片 */
.fortune-overview {
  margin-bottom: 30px;
  padding: 24px;
  background: linear-gradient(135deg, var(--bg-card), var(--surface-raised));
  border: 1px solid var(--border-light);
  border-radius: var(--radius-card);
}

.fortune-overview--loading {
  padding: 24px;
}

.overview-skeleton {
  display: flex;
  align-items: center;
  gap: 16px;
}

.score-skeleton {
  width: 80px;
  height: 80px;
  border-radius: 50%;
}

.skeleton-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.title-skeleton {
  height: 24px;
  max-width: 200px;
}

.subtitle-skeleton {
  height: 16px;
  max-width: 300px;
}

/* ===== 运势概览卡片 ===== */
.fortune-overview {
  padding: 24px 26px;
  background: linear-gradient(135deg, rgba(255, 252, 246, 0.98), rgba(255, 245, 225, 0.94));
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  border-radius: 24px;
}

.overview-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.overview-date {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.overview-solar {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
  letter-spacing: -0.01em;
}

.overview-lunar {
  font-size: 13px;
  color: var(--text-secondary);
}

.overview-refresh {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid rgba(var(--primary-rgb), 0.15);
  background: rgba(var(--primary-rgb), 0.05);
  color: var(--text-tertiary);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 15px;
}

.overview-refresh:hover {
  color: var(--primary-color);
  border-color: rgba(var(--primary-rgb), 0.3);
  background: rgba(var(--primary-rgb), 0.08);
}

.overview-refresh.loading {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.overview-body {
  display: flex;
  align-items: center;
  gap: 28px;
}

.overview-score-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.overview-score-ring {
  position: relative;
  width: 110px;
  height: 110px;
}

.ring-svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.ring-track {
  fill: none;
  stroke: rgba(var(--primary-rgb), 0.1);
  stroke-width: 8;
}

.ring-fill {
  fill: none;
  stroke: var(--primary-color);
  stroke-width: 8;
  stroke-linecap: round;
  stroke-dasharray: 264;
  transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

.overview-score-ring.excellent .ring-fill { stroke: #10b981; }
.overview-score-ring.good .ring-fill { stroke: #3b82f6; }
.overview-score-ring.normal .ring-fill { stroke: #f59e0b; }
.overview-score-ring.poor .ring-fill { stroke: #ef4444; }

.ring-inner {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1px;
}

.ring-num {
  font-size: 28px;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1;
}

.ring-unit {
  font-size: 12px;
  color: var(--text-secondary);
}

.overview-stars {
  display: flex;
  align-items: center;
  gap: 3px;
}

.ov-star {
  font-size: 14px;
  color: rgba(var(--primary-rgb), 0.2);
  transition: color 0.2s;
}

.ov-star.filled {
  color: var(--primary-color);
}

.ov-star-label {
  font-size: 12px;
  color: var(--text-secondary);
  margin-left: 4px;
}

.overview-summary {
  flex: 1;
  min-width: 0;
}

.summary-text {
  font-size: 15px;
  line-height: 1.75;
  color: var(--text-secondary);
  margin: 0;
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
  min-height: 36px;
  border-radius: 999px;
  padding-inline: 16px;
  font-size: 13px;
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

.daily-page :deep(.page-hero) {
  max-width: 920px;
  margin-left: auto;
  margin-right: auto;
}

.fortune-content {
  max-width: 920px;
  margin: 0 auto;
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

.luck-badge.大吉,
.luck-badge.吉,
.luck-badge.小吉 {
  background: linear-gradient(180deg, rgba(103, 194, 58, 0.14), rgba(103, 194, 58, 0.16));
}

.luck-badge.大凶,
.luck-badge.凶 {
  background: linear-gradient(180deg, rgba(245, 108, 108, 0.14), rgba(245, 108, 108, 0.16));
}

.luck-badge.平 {
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
  border-radius: var(--radius-xl);
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
  display: none; /* merged into fortune-overview */
}

.score-display {
  display: none;
}

.score-circle {
  display: none;
}

.score-number {
  display: none;
}

.score-rating,
.fortune-summary {
  color: #605545;
  line-height: 1.8;
}

/* 明日预告样式 */
.tomorrow-preview-section {
  margin-bottom: 30px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(245, 249, 255, 0.94));
  border: 1px solid rgba(59, 130, 246, 0.15);
}

.tomorrow-preview-section h3 {
  color: var(--primary-color);
  margin-bottom: 20px;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.tomorrow-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.tomorrow-score {
  display: flex;
  align-items: center;
  gap: 12px;
}

.tomorrow-score .score-label {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}

.tomorrow-summary {
  color: var(--text-secondary);
  text-align: center;
  line-height: 1.6;
  margin: 0;
}

.tomorrow-action {
  margin-top: 8px;
}

/* ===== 分项运势卡片 ===== */
.aspect-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 0;
}

.aspect-card {
  padding: 20px;
  border-radius: 20px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 240, 0.94));
  border: 1px solid rgba(var(--primary-rgb), 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.aspect-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 32px rgba(var(--primary-rgb), 0.1);
}

.aspect-card__top {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}

.aspect-icon-wrap {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(var(--primary-rgb), 0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: var(--primary-color);
  flex-shrink: 0;
}

.aspect-meta {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.aspect-name {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
}

.aspect-score-num {
  font-size: 22px;
  font-weight: 800;
  line-height: 1;
}

.aspect-bar-wrap {
  margin-bottom: 12px;
}

.aspect-bar-track {
  height: 6px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.08);
  overflow: hidden;
}

.aspect-bar-fill {
  height: 100%;
  border-radius: 999px;
  transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

.aspect-desc {
  font-size: 13px;
  line-height: 1.65;
  color: var(--text-secondary);
  margin: 0;
}

/* ===== 今日宜忌 ===== */
.lucky-section {
  padding: 24px 26px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 241, 0.94));
  border: 1px solid rgba(var(--primary-rgb), 0.1);
  border-radius: 24px;
}

.lucky-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.lucky-header h3 {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.calendar-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 14px;
  border-radius: 999px;
  border: 1px solid rgba(var(--primary-rgb), 0.2);
  background: rgba(var(--primary-rgb), 0.05);
  color: var(--primary-color);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.calendar-btn:hover {
  background: rgba(var(--primary-rgb), 0.1);
  border-color: rgba(var(--primary-rgb), 0.35);
}

.lucky-grid {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.lucky-item {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 16px 18px;
  border-radius: 16px;
}

.lucky-item--yi {
  background: rgba(103, 194, 58, 0.06);
  border: 1px solid rgba(103, 194, 58, 0.18);
}

.lucky-item--ji {
  background: rgba(245, 108, 108, 0.06);
  border: 1px solid rgba(245, 108, 108, 0.18);
}

.lucky-badge {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 800;
  flex-shrink: 0;
  letter-spacing: 0.02em;
}

.lucky-badge--yi {
  background: rgba(103, 194, 58, 0.15);
  color: #3d9a1a;
}

.lucky-badge--ji {
  background: rgba(245, 108, 108, 0.15);
  color: #c0392b;
}

.lucky-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding-top: 4px;
}

.lucky-chip {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 500;
}

.lucky-chip--yi {
  background: rgba(103, 194, 58, 0.1);
  color: #3d9a1a;
  border: 1px solid rgba(103, 194, 58, 0.2);
}

.lucky-chip--ji {
  background: rgba(245, 108, 108, 0.1);
  color: #c0392b;
  border: 1px solid rgba(245, 108, 108, 0.2);
}

:deep(.details-section .el-collapse) {
  border-top: none;
  border-bottom: none;
}

:deep(.details-section .el-collapse-item__header) {
  min-height: 56px;
  padding: 0 4px;
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  background: transparent;
  border-bottom-color: rgba(var(--primary-rgb), 0.08);
  letter-spacing: -0.01em;
}

:deep(.details-section .el-collapse-item__header.is-active) {
  color: var(--primary-color);
}

:deep(.details-section .el-collapse-item__wrap) {
  background: transparent;
  border-bottom-color: rgba(var(--primary-rgb), 0.08);
}

:deep(.details-section .el-collapse-item__content) {
  padding: 4px 4px 20px;
  font-size: 14px;
  line-height: 1.8;
  color: #605545;
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
    gap: 16px;
  }

  .fortune-overview,
  .personalized-fortune,
  .personalized-state-card,
  .lucky-section,
  .details-section,
  .section-empty,
  .loading-state,
  .error-state {
    padding: 18px 16px;
    border-radius: 18px;
  }

  .overview-body {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }

  .overview-score-wrap {
    flex-direction: row;
    align-items: center;
    gap: 16px;
  }

  .overview-score-ring {
    width: 88px;
    height: 88px;
  }

  .ring-num {
    font-size: 22px;
  }

  .aspect-grid {
    grid-template-columns: 1fr;
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

  .personal-lucky-grid {
    grid-template-columns: 1fr;
  }
}

/* 深度引导区 */
.daily-deepen-section {
  margin-top: 24px;
  padding: 24px;
  background: rgba(184, 134, 11, 0.05);
  border: 1px solid rgba(184, 134, 11, 0.12);
  border-radius: var(--radius-xl);
}

.deepen-title {
  font-size: 14px;
  color: rgba(184, 134, 11, 0.6);
  font-weight: 600;
  letter-spacing: 0.06em;
  text-align: center;
  margin-bottom: 16px;
}

.deepen-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.deepen-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: rgba(184, 134, 11, 0.06);
  border: 1px solid rgba(184, 134, 11, 0.15);
  border-radius: var(--radius-md);
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
}

.deepen-card:hover {
  border-color: rgba(212, 175, 55, 0.3);
  background: rgba(184, 134, 11, 0.08);
  transform: translateY(-2px);
}

.deepen-symbol {
  font-size: 22px;
  color: #D4AF37;
  flex-shrink: 0;
  width: 36px;
  text-align: center;
}

.deepen-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.deepen-info strong {
  font-size: 14px;
  font-weight: 600;
  color: rgba(240, 208, 96, 0.85);
}

.deepen-info span {
  font-size: 12px;
  color: rgba(200, 180, 140, 0.55);
}

@media (max-width: 600px) {
  .deepen-cards {
    grid-template-columns: 1fr;
  }
}
</style>

