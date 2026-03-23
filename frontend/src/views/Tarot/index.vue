<template>
  <div class="tarot-page">
    <div class="container">
      <PageHeroHeader
        title="塔罗占卜"
        subtitle="先选牌阵，再聚焦你真正想问的问题；抽牌完成后会锁定上下文，确保保存、分享和复盘都对得上本次解读。"
        :icon="MagicStick"
      />


      <!-- 积分提示 -->
      <div class="points-hint card card-hover">
        <el-icon class="hint-icon"><Coin /></el-icon>
        <div class="points-hint-content">
          <div class="points-hint-main">
            <span>本次占卜将消耗 <strong>{{ tarotCost }} 积分</strong></span>
            <span class="current-points">当前积分: {{ pointsDisplayText }}</span>
          </div>
          <div class="points-hint-details">
            <p class="points-hint-title">本次占卜您将获得：</p>
            <ul class="points-hint-list">
              <li><el-icon><Check /></el-icon> 专属的塔罗牌阵抽取与牌面展示</li>
              <li><el-icon><Check /></el-icon> 结合您问题的深度牌面解读与建议</li>
              <li><el-icon><Check /></el-icon> 永久保存在您的历史记录中，随时查看</li>
            </ul>
            <p class="points-hint-guarantee"><el-icon><Shield /></el-icon> 失败保障：若抽牌失败或未生成解读，将自动退还积分。</p>
          </div>
        </div>
        <div v-if="pointsError" class="points-warning" role="status" aria-live="polite">
          <span>积分同步失败，请先重新获取后再继续占卜</span>
          <el-button link type="warning" class="points-retry" @click="loadPoints" :loading="pointsLoading">重新获取积分</el-button>
        </div>
      </div>

      <div v-if="currentPoints !== null && currentPoints < tarotCost" class="insufficient-points card card-hover">
        <div class="insufficient-header">
          <el-icon :size="28"><Warning /></el-icon>
          <p>积分不足（当前 {{ currentPoints ?? 0 }} 积分，需要 {{ tarotCost }} 积分）</p>
        </div>
        <div class="insufficient-actions">
          <router-link to="/profile" class="insufficient-btn insufficient-btn--primary">
            📅 每日签到 +积分
          </router-link>
          <router-link to="/recharge" class="insufficient-btn insufficient-btn--secondary">
            💰 充值积分
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
          <div v-if="interpretationState === 'ready'" class="interpretation-content">{{ interpretation }}</div>
          <p v-else-if="interpretationState === 'loading'" class="interpretation-placeholder interpretation-placeholder--loading">牌面已经抽出，正在结合你的问题生成解读，请稍候...</p>
          <p v-else-if="interpretationState === 'error'" class="interpretation-placeholder interpretation-placeholder--error">本次牌面已抽出，但解读暂时中断。你可以直接点击“重试解读”，无需重新抽牌。</p>
          <p v-else class="interpretation-placeholder">抽牌完成后，这里会展示与你问题对应的牌面解读。</p>
        </div>

        <!-- AI 深度分析 - 产品亮点功能 -->
        <div v-if="interpretationState === 'ready'" class="ai-analysis-section card card-hover">
          <div class="ai-analysis-header">
            <div class="ai-analysis-title">
              <el-icon class="ai-icon"><MagicStick /></el-icon>
              <h3>AI 深度分析</h3>
            </div>
            <span class="ai-badge">产品亮点</span>
          </div>
          <p class="ai-analysis-desc">基于你的牌阵、问题和个人情况，提供更深入的个性化解读和行动建议。</p>
          
          <div v-if="aiAnalysisResult" class="ai-analysis-result">
            <div class="ai-result-content">{{ aiAnalysisResult }}</div>
            <el-button @click="performAiAnalysis" :loading="aiAnalysisLoading" type="primary" plain>
              <el-icon><RefreshRight /></el-icon>
              重新分析
            </el-button>
          </div>
          
          <div v-else class="ai-analysis-action">
            <div class="ai-features">
              <div class="ai-feature-item">
                <el-icon><Check /></el-icon>
                <span>深度解读每张牌的象征意义</span>
              </div>
              <div class="ai-feature-item">
                <el-icon><Check /></el-icon>
                <span>结合你的问题提供个性化建议</span>
              </div>
              <div class="ai-feature-item">
                <el-icon><Check /></el-icon>
                <span>分析牌阵的整体能量流动</span>
              </div>
              <div class="ai-feature-item">
                <el-icon><Check /></el-icon>
                <span>提供具体的行动步骤</span>
              </div>
            </div>
            <el-button 
              @click="performAiAnalysis" 
              :loading="aiAnalysisLoading" 
              type="primary" 
              size="large"
              class="ai-analysis-btn"
            >
              <el-icon class="btn-icon"><MagicStick /></el-icon>
              开始 AI 深度分析
              <span class="ai-cost-badge">{{ aiAnalysisCost }} 积分</span>
            </el-button>
          </div>
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
import { Coin, MagicStick, ChatDotRound, Briefcase, StarFilled, UserFilled, QuestionFilled, Document, Download, RefreshRight, Select, Check } from '@element-plus/icons-vue'

import { useTarot } from './useTarot'

const {
  // 状态数据
  tarotCost, spreads, questionTopics, selectedSpread,
  question, loading, pointsLoading, pointsError,
  cards, interpretation, currentPoints,
  cardDetailVisible, selectedCard, selectedTopic,
  questionGuideExpanded, flowError,
  aiAnalysisLoading, aiAnalysisResult, aiAnalysisCost,

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
  loadPoints, performAiAnalysis, retryLastAction,
  resetTarot, showCardDetail,
  getCardDetailedMeaning, getCardAdvice,
} = useTarot()
</script>

<style scoped>
@import './style.css';
</style>
