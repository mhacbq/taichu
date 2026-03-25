<template>
  <div class="tarot-page">
    <div class="container">
      <PageHeroHeader
        title="塔罗占卜"
        subtitle="先选牌阵，再聚焦你真正想问的问题；抽牌完成后会锁定上下文，确保保存、分享和复盘都对得上本次解读。"
        :icon="MagicStick"
      />


      <!-- 积分信息栏 -->
      <div class="points-bar">
        <div class="points-bar__main">
          <div class="points-bar__cost">
            <el-icon class="points-bar__icon"><Coin /></el-icon>
            <span>本次占卜消耗 <strong>{{ tarotCost }}</strong> 积分</span>
          </div>
          <div class="points-bar__balance">
            <span class="points-bar__label">余额</span>
            <span class="points-bar__value">{{ pointsDisplayText }}</span>
          </div>
        </div>
        <div class="points-bar__features">
          <span class="points-bar__tag"><el-icon><Check /></el-icon> 牌阵抽取</span>
          <span class="points-bar__tag"><el-icon><Check /></el-icon> 深度解读</span>
          <span class="points-bar__tag"><el-icon><Check /></el-icon> 永久存档</span>
<span class="points-bar__tag points-bar__tag--guarantee"><el-icon><CircleCheckFilled /></el-icon> 失败退积分</span>
        </div>
        <div v-if="pointsError" class="points-bar__error" role="status" aria-live="polite">
          <span>积分同步失败</span>
          <el-button link type="warning" @click="loadPoints" :loading="pointsLoading">重新获取</el-button>
        </div>
      </div>

      <!-- 积分不足提示 -->
      <div v-if="currentPoints !== null && currentPoints < tarotCost" class="points-insufficient">
        <div class="points-insufficient__info">
          <el-icon :size="20"><Warning /></el-icon>
          <span>积分不足（需要 {{ tarotCost }}，当前 {{ currentPoints ?? 0 }}）</span>
        </div>
        <div class="points-insufficient__actions">
          <router-link to="/profile" class="points-insufficient__btn points-insufficient__btn--primary">
            📅 签到领积分
          </router-link>
          <router-link to="/recharge" class="points-insufficient__btn points-insufficient__btn--secondary">
            💰 充值
          </router-link>
        </div>
      </div>

      <div class="tarot-intro card card-hover">
        <h2>选择您的牌阵</h2>
        <div class="spread-options">
          <button
            v-for="spread in spreads"
            :key="spread.id"
            type="button"
            class="spread-card card-hover"
            :class="{ active: displayedSpread === spread.id }"
            :aria-pressed="displayedSpread === spread.id"
            :disabled="isTarotContextLocked"
            :aria-disabled="isTarotContextLocked"
            @click="selectSpread(spread.id)"
          >

            <div class="spread-icon">
              <el-icon v-if="spread.icon === 'card'"><Document /></el-icon>
              <el-icon v-else-if="spread.icon === 'cards'"><ChatDotRound /></el-icon>
              <el-icon v-else-if="spread.icon === 'magic'"><MagicStick /></el-icon>
            </div>
            <span v-if="selectedSpread === spread.id" class="spread-status-badge">
              <el-icon><Select /></el-icon>
            </span>
            <h3>{{ spread.name }}</h3>
            <p>{{ spread.description }}</p>
          </button>
        </div>
      </div>

      <!-- 问题引导区域 -->
      <div v-if="showQuestionGuide" class="question-guide card card-hover">
        <div class="question-guide__header">
          <h3>
            <el-icon class="guide-icon"><ChatDotRound /></el-icon>
            不知道问什么？选择一个你关心的话题
          </h3>
          <el-button
            v-if="question && cards.length === 0"
            link
            type="primary"
            class="guide-toggle"
            @click="toggleQuestionGuide"
          >
            {{ questionGuideExpanded ? '收起模板' : '继续查看模板' }}
          </el-button>
        </div>
        <div v-show="questionGuideExpanded || (!question && cards.length === 0)">
          <div class="topic-tabs">
            <button
              v-for="topic in questionTopics"
              :key="topic.id"
              type="button"
              class="topic-tab card-hover"
              :class="{ active: selectedTopic === topic.id }"
              :aria-pressed="selectedTopic === topic.id"
              @click="selectTopic(topic.id)"
            >
              <span class="topic-icon">
                <el-icon v-if="topic.icon === 'briefcase'"><Briefcase /></el-icon>
                <el-icon v-else-if="topic.icon === 'heart'"><StarFilled /></el-icon>
                <el-icon v-else-if="topic.icon === 'star'"><StarFilled /></el-icon>
                <el-icon v-else-if="topic.icon === 'question'"><QuestionFilled /></el-icon>
                <el-icon v-else-if="topic.icon === 'users'"><UserFilled /></el-icon>
              </span>
              <span class="topic-name">{{ topic.name }}</span>
            </button>
          </div>
          <div class="question-templates" v-if="selectedTopic">
            <p class="template-hint">点击选择一个问题，或以此为灵感输入你自己的问题：</p>
            <div class="template-list">
              <button
                v-for="(template, index) in currentTemplates"
                :key="index"
                type="button"
                class="template-item card-hover"
                @click="selectQuestion(template)"
              >
                <span class="template-bullet">•</span>
                <span class="template-text">{{ template }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>


      <div class="question-section card card-hover" :class="{ 'question-section--locked': isResultLocked }">
        <h3>您想咨询的问题</h3>
        <el-input
          v-model="question"
          type="textarea"
          :rows="3"
          :readonly="isTarotContextLocked"
          placeholder="描述你的困惑，越具体越好。比如：'我应该接受这份新工作吗？'"
        />
        <div class="question-hint" v-if="question && !isResultLocked">
          <el-icon class="hint-icon"><MagicStick /></el-icon>
          <span>好的问题通常是开放性的，以"我应该..."或"我该如何..."开头</span>
        </div>
        <div v-else-if="isResultLocked" class="question-lock-note" role="status" aria-live="polite">
          <el-icon class="hint-icon"><Select /></el-icon>
          <span>本次抽牌已锁定问题与牌阵，保存记录、分享与详情弹窗都会沿用当前上下文。想修改的话，直接点“重新占卜”。</span>
        </div>
        <el-button
          type="primary"
          size="large"
          @click="showConfirm"
          :loading="loading"
          :disabled="!canDrawTarot"
          class="draw-btn"
        >
          <el-icon class="btn-icon"><Document /></el-icon>
          开始抽牌
        </el-button>
      </div>


      <div v-if="flowError && cards.length === 0" class="flow-error card card-hover">
        <EmptyState
          type="error"
          size="small"
          :title="flowErrorTitle"
          :description="flowErrorDescription"
        >
          <template #extra>
            <div class="flow-error-actions">
              <el-button type="primary" @click="retryLastAction" :loading="loading">{{ flowErrorActionText }}</el-button>
              <el-button @click="resetTarot">重新整理问题</el-button>
            </div>
          </template>
        </EmptyState>
      </div>

      <div v-if="cards.length > 0" class="cards-result card card-hover" :class="`cards-result--${displayedSpread}`">
        <h3>您的牌阵</h3>
        <p class="cards-hint"><el-icon><MagicStick /></el-icon> 点击或按回车查看详细解读</p>
        <div v-if="submittedQuestionDisplay" class="result-context-summary" aria-label="本次塔罗结果上下文">
          <span class="result-context-chip">
            <strong>牌阵</strong>
            <span>{{ submittedSpreadName }}</span>
          </span>
          <span class="result-context-chip result-context-chip--question">
            <strong>问题</strong>
            <span>{{ submittedQuestionDisplay }}</span>
          </span>
        </div>
        <div class="cards-display">
          <div
            v-for="(card, index) in cards"
            :key="`${card.name}-${index}`"
            class="card-stack"
          >
            <button
              type="button"
              class="card-detail-trigger"
              :aria-label="getCardDetailAriaLabel(card, index)"
              aria-haspopup="dialog"
              @click="showCardDetail(card, index)"
            >
              <TarotCard
                :name="card.name"
                :emoji="card.emoji"
                :reversed="card.reversed"
                :element="card.element"
                :color="card.color"
                :index="index"
              />

              <span v-if="cards.length > 1" class="card-position">{{ getPositionLabel(displayedSpread, index) }}</span>
            </button>
          </div>
        </div>


        <div v-if="flowError && cards.length > 0" class="flow-error flow-error--inline card card-hover">
          <EmptyState
            type="error"
            size="small"
            inline
            :title="flowErrorTitle"
            :description="flowErrorDescription"
          >
            <template #extra>
              <div class="flow-error-actions">
                <el-button type="primary" @click="retryLastAction" :loading="loading">{{ flowErrorActionText }}</el-button>
                <el-button @click="resetTarot">重新占卜</el-button>
              </div>
            </template>
          </EmptyState>
        </div>

        <div class="interpretation" :class="{ 'interpretation--pending': interpretationState !== 'ready' }">
          <h3>牌面解读</h3>

          <!-- 结构化 AI 解读 -->
          <template v-if="interpretationState === 'ready' && aiAnalysisResult && typeof aiAnalysisResult === 'object'">
            <!-- 总论 -->
            <div v-if="aiAnalysisResult.summary" class="ai-section ai-section--summary">
              <p class="ai-summary">{{ aiAnalysisResult.summary }}</p>
            </div>

            <!-- 逐牌解读 -->
            <div v-if="aiAnalysisResult.card_readings && aiAnalysisResult.card_readings.length" class="ai-section">
              <h4 class="ai-section-title">逐牌解读</h4>
              <div
                v-for="(cr, idx) in aiAnalysisResult.card_readings"
                :key="idx"
                class="ai-card-reading"
              >
                <div class="ai-card-reading__header">
                  <span class="ai-card-reading__position">{{ cr.position }}</span>
                  <span class="ai-card-reading__name">{{ cr.card }}</span>
                  <span class="ai-card-reading__orientation">{{ cr.orientation }}</span>
                </div>
                <p class="ai-card-reading__meaning">{{ cr.meaning }}</p>
              </div>
            </div>

            <!-- 能量流动 -->
            <div v-if="aiAnalysisResult.energy_flow" class="ai-section">
              <h4 class="ai-section-title">能量流动</h4>
              <p>{{ aiAnalysisResult.energy_flow }}</p>
            </div>

            <!-- 核心信息 -->
            <div v-if="aiAnalysisResult.core_message" class="ai-section ai-section--highlight">
              <h4 class="ai-section-title">核心信息</h4>
              <p>{{ aiAnalysisResult.core_message }}</p>
            </div>

            <!-- 建议 -->
            <div v-if="aiAnalysisResult.suggestion" class="ai-section">
              <h4 class="ai-section-title">行动建议</h4>
              <p>{{ aiAnalysisResult.suggestion }}</p>
            </div>

            <!-- 注意事项 -->
            <div v-if="aiAnalysisResult.warning" class="ai-section ai-section--warning">
              <h4 class="ai-section-title">注意事项</h4>
              <p>{{ aiAnalysisResult.warning }}</p>
            </div>
          </template>

          <!-- 降级：纯文本解读 -->
          <div v-else-if="interpretationState === 'ready'" class="interpretation-content">{{ interpretation }}</div>

          <p v-else-if="interpretationState === 'loading'" class="interpretation-placeholder interpretation-placeholder--loading">牌面已经抽出，AI 正在结合你的问题生成解读，请稍候...</p>
          <p v-else-if="interpretationState === 'error'" class="interpretation-placeholder interpretation-placeholder--error">本次牌面已抽出，但解读暂时中断。你可以直接点击"重试解读"，无需重新抽牌。</p>
          <p v-else class="interpretation-placeholder">抽牌完成后，这里会展示与你问题对应的 AI 解读。</p>
        </div>

        
        <!-- 操作按钮 -->
        <ResultNextSteps
          description="先把这次牌阵存下来，再决定是分享给对方，还是顺手切到别的服务补一层视角。"
          :highlights="tarotResultHighlights"
          :actions="tarotResultActions"
          :recommendations="tarotRelatedRecommendations"
        />
        <WisdomText />

      </div>

      <!-- 单张牌详情弹窗 -->
      <el-dialog
        v-if="selectedCard"
        v-model="cardDetailVisible"
        :title="selectedCard.name + ' - ' + (selectedCard.reversed ? '逆位' : '正位')"
        width="min(92vw, 500px)"
        class="card-detail-dialog"
      >

        <div v-if="selectedCard" class="card-detail">
          <div class="detail-header-new">
            <TarotCard 
              :name="selectedCard.name"
              :emoji="selectedCard.emoji"
              :reversed="selectedCard.reversed"
              :element="selectedCard.element"
              :color="selectedCard.color"
              class="detail-card"
            />
          </div>
          <div v-if="selectedCard.positionLabel" class="detail-context">
            <span class="card-position card-position--detail">{{ selectedCard.positionLabel }}</span>
            <span v-if="cards.length > 1" class="card-position card-position--detail">第{{ selectedCard.positionIndex + 1 }}张</span>
            <span class="card-position card-position--detail">{{ selectedCard.spreadName }}</span>
          </div>
          <div class="detail-content">
            <h4>牌面含义</h4>
            <p class="detail-meaning">{{ selectedCard.meaning }}</p>

            
            <h4>详细解读</h4>
            <div class="detail-interpretation">
              <div class="interp-section">
                <h5>{{ selectedCard.reversed ? '逆位' : '正位' }}含义</h5>
                <p>{{ getCardDetailedMeaning(selectedCard) }}</p>
              </div>
              <div class="interp-section">
                <h5>关键启示</h5>
                <p>{{ getCardAdvice(selectedCard) }}</p>
              </div>
            </div>
          </div>
        </div>
      </el-dialog>
    </div>
  </div>
</template>


<script setup>
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import TarotCard from '../../components/TarotCard.vue'
import ResultNextSteps from '../../components/ResultNextSteps.vue'
import WisdomText from '../../components/WisdomText.vue'
import EmptyState from '../../components/EmptyState.vue'
import { Coin, MagicStick, ChatDotRound, Briefcase, StarFilled, UserFilled, QuestionFilled, Document, Download, Select, Check, Warning, CircleCheckFilled } from '@element-plus/icons-vue'

import { useTarot } from './useTarot'

const {
  // 状态数据
  tarotCost, spreads, questionTopics, selectedSpread,
  question, loading, pointsLoading, pointsError,
  cards, interpretation, currentPoints,
  cardDetailVisible, selectedCard, selectedTopic,
  questionGuideExpanded, flowError,
  aiAnalysisResult,

  // 计算属性
  isResultLocked, isTarotContextLocked,
  submittedQuestionDisplay, submittedSpreadName,
  pointsDisplayText, canDrawTarot,
  currentTemplates, showQuestionGuide,
  displayedSpread,
  flowErrorTitle, flowErrorDescription, flowErrorActionText,
  interpretationState,
  shouldShowTarotRechargeAction,
  tarotResultHighlights, tarotResultActions, tarotRelatedRecommendations,

  // 方法
  toggleQuestionGuide, selectSpread,
  getPositionLabel, selectTopic, selectQuestion,
  getCardDetailAriaLabel,
  loadPoints, retryLastAction,
  resetTarot, showCardDetail, showConfirm,
  getCardDetailedMeaning, getCardAdvice,
} = useTarot()
</script>

<style scoped>
@import './style.css';
</style>
