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
          <!-- 重新设计的运势概览卡片 -->
          <div class="fortune-overview card">
            <!-- 顶部日期和刷新 -->
            <div class="overview-header">
              <div class="date-display">
                <div class="date-main">
                  <span class="solar-date">{{ solarDate }}</span>
                  <span class="lunar-date">{{ lunarDate }}</span>
                </div>
                <div class="date-badge">
                  <span class="day-badge">今日</span>
                </div>
              </div>
              <el-tooltip content="刷新运势" placement="top">
                <button class="refresh-btn" :class="{ loading: isLoading }" @click="loadDailyFortune({ userInitiated: true })">
                  <el-icon><RefreshRight /></el-icon>
                </button>
              </el-tooltip>
            </div>

            <!-- 主体内容 -->
            <div class="overview-body">
              <!-- 左侧运势评分 -->
              <div class="score-section">
                <div class="score-ring" :class="getScoreClass(fortune.overallScore)">
                  <div class="ring-background"></div>
                  <div class="ring-progress" :style="{ transform: `rotate(${fortune.overallScore * 3.6}deg)` }"></div>
                  <div class="score-content">
                    <span class="score-number">{{ fortune.overallScore }}</span>
                    <span class="score-label">分</span>
                  </div>
                </div>
                <div class="score-rating">
                  <div class="stars">
                    <el-icon v-for="n in 5" :key="n" class="star" :class="{ filled: n <= overallStarCount }"><StarFilled /></el-icon>
                  </div>
                  <span class="rating-label">{{ overallStarLabel }}</span>
                </div>
              </div>

              <!-- 中间运势摘要 -->
              <div class="summary-section">
                <div class="summary-content">
                  <p class="summary-text">{{ fortune.summary || '今日运势平稳，适合按部就班推进计划' }}</p>
                </div>
                <div class="lucky-info">
                  <div class="lucky-item" v-if="fortune.luckyColor">
                    <el-icon><MagicStick /></el-icon>
                    <span class="lucky-label">幸运色</span>
                    <span class="lucky-value">{{ fortune.luckyColor || '暂无' }}</span>
                  </div>
                  <div class="lucky-item" v-if="fortune.luckyDirection">
                    <el-icon><Compass /></el-icon>
                    <span class="lucky-label">幸运方位</span>
                    <span class="lucky-value">{{ fortune.luckyDirection || '暂无' }}</span>
                  </div>
                </div>
              </div>

              <!-- 右侧宜忌标签 -->
              <div class="tags-section" v-if="fortune.yi?.length || fortune.ji?.length">
                <div class="tags-group" v-if="fortune.yi?.length">
                  <div class="group-header">
                    <span class="group-icon">✓</span>
                    <span class="group-title">宜</span>
                  </div>
                  <div class="tags-list">
                    <span v-for="item in fortune.yi.slice(0, 3)" :key="item" class="tag tag--yi">{{ item }}</span>
                  </div>
                </div>
                <div class="tags-group" v-if="fortune.ji?.length">
                  <div class="group-header">
                    <span class="group-icon">✗</span>
                    <span class="group-title">忌</span>
                  </div>
                  <div class="tags-list">
                    <span v-for="item in fortune.ji.slice(0, 3)" :key="item" class="tag tag--ji">{{ item }}</span>
                  </div>
                </div>
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
                <span class="wuxing-badge" :class="'wuxing-' + personalizedFortune.dayMasterWuxing">
                  <span class="wuxing-icon">{{ wuxingIconMap[personalizedFortune.dayMasterWuxing] || '☯' }}</span>
                  {{ personalizedFortune.dayMasterWuxing }}
                </span>
              </div>
              <div class="relation-arrow">
                <el-icon><Right /></el-icon>
              </div>
              <div class="today-card">
                <span class="label">今日干支</span>
                <span class="value">{{ personalizedFortune.todayGanZhi }}</span>
                <span class="wuxing-badge" :class="'wuxing-' + personalizedFortune.todayWuxing">
                  <span class="wuxing-icon">{{ wuxingIconMap[personalizedFortune.todayWuxing] || '☯' }}</span>
                  {{ personalizedFortune.todayWuxing }}
                </span>
              </div>
            </div>
            
            <div class="luck-indicator">
              <div class="luck-badge" :class="'luck-' + personalizedFortune.luckLevel">
                <span class="luck-emoji">{{ luckLevelConfig[personalizedFortune.luckLevel]?.icon || '☯' }}</span>
                <span class="luck-relation-name">{{ personalizedFortune.relation }}</span>
                <span class="luck-relation-title">{{ personalizedRelationMeta.title }}</span>
                <span class="luck-level">今日偏{{ personalizedFortune.luckLevel }}
                  <span class="luck-level-stars">
                    <span v-for="n in (luckLevelConfig[personalizedFortune.luckLevel]?.stars || 3)" :key="n">★</span>
                  </span>
                </span>
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
                <div class="personal-lucky-header-bar">
                  <span class="personal-lucky-icon personal-lucky-icon--color">
                    <el-icon><MagicStick /></el-icon>
                  </span>
                  <span class="personal-lucky-label">幸运色</span>
                </div>
                <p class="personal-lucky-caption">优先选择更顺势的穿搭与配色</p>
                <div class="personal-lucky-values">
                  <span v-for="color in personalizedFortune.luckyColors" :key="color" class="lucky-tag color">
                    <span class="color-preview" :style="{ backgroundColor: getColorCode(color) }"></span>
                    {{ color }}
                  </span>
                </div>
              </div>
              <div class="personal-lucky-item personal-lucky-item--direction card-hover">
                <div class="personal-lucky-header-bar">
                  <span class="personal-lucky-icon personal-lucky-icon--direction">
                    <el-icon><Compass /></el-icon>
                  </span>
                  <span class="personal-lucky-label">幸运方位</span>
                </div>
                <p class="personal-lucky-caption">安排会面、出行或重要决策时可优先参考</p>
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

        <!-- 今日黄历 -->
        <div v-if="hasAlmanac" class="almanac-section card card-hover">
          <h3>📅 今日黄历</h3>
          <div class="almanac-header">
            <div class="almanac-ganzhi">
              <span class="ganzhi-label">干支纪日</span>
              <span class="ganzhi-value">{{ ganzhiText }}</span>
            </div>
            <div class="almanac-meta">
              <div v-if="almanac.zhiri" class="almanac-zhiri">
                <el-tag :type="zhiriTagType(almanac.zhiri)" size="large" effect="dark" class="zhiri-tag">{{ almanac.zhiri }}日</el-tag>
                <span class="zhiri-desc">{{ zhiriDesc(almanac.zhiri) }}</span>
              </div>
              <div v-if="almanac.sha" class="almanac-sha">
                <span class="sha-label">煞方</span>
                <span class="sha-value">煞{{ almanac.sha }}</span>
              </div>
            </div>
          </div>

          <!-- 吉神凶煞 -->
          <div class="almanac-gods" v-if="(almanac.jishen?.length) || (almanac.xiongsha?.length)">
            <div v-if="almanac.jishen?.length" class="gods-group gods-group--ji">
              <span class="gods-label">吉神</span>
              <div class="gods-tags">
                <el-tag v-for="g in almanac.jishen" :key="g" type="success" size="small" class="god-tag">{{ g }}</el-tag>
              </div>
            </div>
            <div v-if="almanac.xiongsha?.length" class="gods-group gods-group--xiong">
              <span class="gods-label">凶煞</span>
              <div class="gods-tags">
                <el-tag v-for="g in almanac.xiongsha" :key="g" type="danger" size="small" class="god-tag">{{ g }}</el-tag>
              </div>
            </div>
          </div>

          <!-- 时辰宜忌 -->
          <div v-if="hasShichen" class="almanac-shichen">
            <h4 class="shichen-title">🕐 十二时辰宜忌</h4>
            <div class="shichen-grid">
              <div
                v-for="(sc, idx) in shichenList" :key="sc.name"
                class="shichen-item"
                :class="{ 'shichen-item--ji': sc.type === 'ji', 'shichen-item--xiong': sc.type === 'xiong', 'shichen-item--current': idx === currentShichenIndex }"
              >
                <div class="shichen-top">
                  <span class="shichen-name">{{ sc.name }}时</span>
                  <span v-if="idx === currentShichenIndex" class="shichen-now">当前</span>
                </div>
                <span class="shichen-time">{{ sc.time }}</span>
                <el-tag :type="sc.type === 'ji' ? 'success' : 'danger'" size="small" class="shichen-god-tag">{{ sc.god }}</el-tag>
                <span class="shichen-type" :class="sc.type === 'ji' ? 'type-ji' : 'type-xiong'">{{ sc.type === 'ji' ? '吉' : '凶' }}</span>
                <p class="shichen-yiji">{{ sc.yiji }}</p>
              </div>
            </div>
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

    <!-- 生日选择弹窗 -->
    <el-dialog
      v-model="showBirthdayDialog"
      title="设置出生日期"
      width="400px"
      :close-on-click-modal="false"
    >
      <div class="birthday-dialog-content">
        <p class="birthday-tip">请先设置您的出生日期，以便为您推送个性化的每日运势</p>
        <el-date-picker
          v-model="userBirthDate"
          type="date"
          placeholder="选择您的出生日期"
          format="YYYY年MM月DD日"
          value-format="YYYY-MM-DD"
          style="width: 100%"
          :disabled-date="(time) => time.getTime() > Date.now()"
        />
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="showBirthdayDialog = false">取消</el-button>
          <el-button type="primary" @click="saveBirthDate" :loading="birthdayLoading">
            确定
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import AsyncState from '../../components/AsyncState.vue'
import {
  MagicStick, QuestionFilled, Collection, WarningFilled, StarFilled,
  Right, Compass, Briefcase, Money, Sunny, UserFilled,
  RefreshRight, Calendar, Present, Sunrise, ArrowRight
} from '@element-plus/icons-vue'

import { useDaily } from './useDaily'

const {
  // 状态
  solarDate,
  lunarDate,
  isLoading,
  fortune,
  isLoggedIn,
  activeNames,
  error,
  errorMessage,
  dailyLoginRoute,
  showBirthdayDialog,
  userBirthDate,
  birthdayLoading,
  showTomorrowPreview,
  tomorrowStarCount,
  tomorrowSummary,

  // 计算属性
  dailyStatus,
  personalizedFortune,
  personalizedRelationMeta,
  dailyPersonalizedState,
  overallStarCount,
  overallStarLabel,
  hasAspectCards,
  hasYiItems,
  hasJiItems,
  hasLuckySection,
  detailSections,

  // 黄历数据
  almanac,
  hasAlmanac,
  ganzhiText,
  shichenList,
  hasShichen,
  currentShichenIndex,

  // 常量
  wuxingIconMap,
  luckLevelConfig,

  // 方法
  getScoreColor,
  getScoreClass,
  getAspectIcon,
  getColorCode,
  zhiriTagType,
  zhiriDesc,
  loadDailyFortune,
  loadTomorrowFortune,
  addToCalendar,
  saveBirthDate,
} = useDaily()
</script>

<style scoped>
@import './style.css';
</style>
