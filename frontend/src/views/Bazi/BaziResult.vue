<script setup lang="ts">
import { Grid, Present, Lightning, MagicStick, Calendar, QuestionFilled, Download, Document, Cpu, Share, RefreshRight } from '@element-plus/icons-vue'
import WisdomText from '../../components/WisdomText.vue'
import ShareCard from '../../components/ShareCard.vue'
import BaziPaipan from './BaziPaipan.vue'
import BaziInterpretation from './BaziInterpretation.vue'
import BaziFortuneTools from './BaziFortuneTools.vue'

const props = defineProps({
  result: { type: Object, required: true },
  birthTimeAccuracy: { type: String, default: 'exact' },
  birthTimeAccuracyLabel: { type: String, default: '' },
  locationContextLabel: { type: String, default: '' },
  resultContextNote: { type: String, default: '' },
  resultModeLabel: { type: String, default: '' },
  wuxingDistributionItems: { type: Array, default: () => [] },
  currentYearLiunian: { type: Object, default: null },
  saving: { type: Boolean, default: false },
  baziShareSummary: { type: String, default: '' },
  baziShareTags: { type: Array, default: () => [] },
  // 传递给子组件的方法和状态
  getShishenClass: { type: Function, default: () => '' },
  getScoreClass: { type: Function, default: () => '' },
  getScoreColor: { type: Function, default: () => '' },
  isCurrentDaYun: { type: Function, default: () => false },
  getFortuneToolTagText: { type: Function, default: () => '' },
  canUseFortuneTool: { type: Function, default: () => false },
  getFortuneToolActionText: { type: Function, default: () => '' },
  // 流年/大运状态
  selectedYear: { type: Number, default: () => new Date().getFullYear() },
  yearlyFortuneResult: { type: Object, default: null },
  yearlyFortuneLoading: { type: Boolean, default: false },
  isCompactViewport: { type: Boolean, default: false },
  selectedDayunIndex: { type: Number, default: 0 },
  dayunAnalysisResult: { type: Object, default: null },
  dayunAnalysisLoading: { type: Boolean, default: false },
  dayunScoring: { type: Boolean, default: false },
  dayunChartData: { type: Object, default: null },
  dayunChartLoading: { type: Boolean, default: false },
  // AI
  aiAnalysisResult: { type: Object, default: null },
  aiAnalyzing: { type: Boolean, default: false },
  aiLoadingHint: { type: String, default: '' },
  canStartAiAnalysis: { type: Boolean, default: false },
  aiActionText: { type: String, default: '' },
  aiAnalysisCost: { type: Number, default: 0 },
  aiNeedsAccountRecovery: { type: Boolean, default: false },
  aiRecoveryText: { type: String, default: '' },
})

const emit = defineEmits([
  'saveResult',
  'openBaziHistoryCenter',
  'continueBaziJourney',
  'resetCurrentResult',
  'update:selectedYear',
  'update:selectedDayunIndex',
  'showPointsConfirm',
  'startAiAnalysis',
  'clearAiResult',
  'loadPoints',
])
</script>

<template>
  <div class="bazi-result card">
    <!-- 结果页顶部 -->
    <div class="result-hero">
      <div class="result-hero__ornament"></div>
      <div class="result-hero__badge">命盘已成</div>
      <h2 class="result-hero__title">八字排盘结果</h2>
      <p class="result-hero__subtitle">天干地支 · 五行流转 · 命理格局</p>
      <div class="result-meta">
        <span class="meta-tag meta-tag--success" v-if="result.is_first_bazi"><el-icon><Present /></el-icon> 首次免费</span>
        <span class="meta-tag meta-tag--success" v-if="result.from_cache"><el-icon><Lightning /></el-icon> 智能缓存</span>
        <span class="meta-tag meta-tag--info"><el-icon><MagicStick /></el-icon> {{ resultModeLabel }}</span>
        <span class="meta-tag" :class="birthTimeAccuracy === 'estimated' ? 'meta-tag--warning' : 'meta-tag--neutral'">
          <el-icon><Calendar /></el-icon> {{ birthTimeAccuracyLabel }}
        </span>
        <span class="meta-tag meta-tag--neutral"><el-icon><QuestionFilled /></el-icon> {{ locationContextLabel }}</span>
      </div>
    </div>
    <p v-if="resultContextNote" class="result-context-note">{{ resultContextNote }}</p>

    <!-- 当前流年速览 -->
    <div class="current-fortune-brief" v-if="result.liunian && currentYearLiunian">
      <div class="fortune-brief__header">
        <el-icon><Calendar /></el-icon>
        <h3>{{ new Date().getFullYear() }}年流年速览</h3>
      </div>
      <div class="fortune-brief__content">
        <div class="fortune-brief__pillar">
          <span class="gan">{{ currentYearLiunian.gan }}</span>
          <span class="zhi">{{ currentYearLiunian.zhi }}</span>
        </div>
        <div class="fortune-brief__info">
          <span class="fortune-brief__nayin">{{ currentYearLiunian.nayin }}</span>
          <div class="fortune-brief__wuxing">
            <span class="badge" :class="currentYearLiunian.gan_wuxing">{{ currentYearLiunian.gan_wuxing }}</span>
            <span class="badge" :class="currentYearLiunian.zhi_wuxing">{{ currentYearLiunian.zhi_wuxing }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 命盘核心数据 -->
    <div class="result-section">
      <BaziPaipan
        :bazi="result.bazi"
        :wuxing-distribution-items="wuxingDistributionItems"
        :get-shishen-class="getShishenClass"
      />
    </div>

    <!-- 性格内观 + AI解盘 -->
    <div class="result-section">
      <BaziInterpretation
        :result="result"
        :ai-analysis-result="aiAnalysisResult"
        :ai-analyzing="aiAnalyzing"
        :ai-loading-hint="aiLoadingHint"
        :can-start-ai-analysis="canStartAiAnalysis"
        :ai-action-text="aiActionText"
        :ai-analysis-cost="aiAnalysisCost"
        :ai-needs-account-recovery="aiNeedsAccountRecovery"
        :ai-recovery-text="aiRecoveryText"
        @start-ai-analysis="emit('startAiAnalysis')"
        @clear-ai-result="emit('clearAiResult')"
        @load-points="emit('loadPoints')"
      />

      <!-- 大运流年 + 深度工具 -->
      <BaziFortuneTools
        :result="result"
        :selected-year="selectedYear"
        :yearly-fortune-result="yearlyFortuneResult"
        :yearly-fortune-loading="yearlyFortuneLoading"
        :is-compact-viewport="isCompactViewport"
        :selected-dayun-index="selectedDayunIndex"
        :dayun-analysis-result="dayunAnalysisResult"
        :dayun-analysis-loading="dayunAnalysisLoading"
        :dayun-scoring="dayunScoring"
        :dayun-chart-data="dayunChartData"
        :dayun-chart-loading="dayunChartLoading"
        :is-current-da-yun="isCurrentDaYun"
        :get-fortune-tool-tag-text="getFortuneToolTagText"
        :can-use-fortune-tool="canUseFortuneTool"
        :get-fortune-tool-action-text="getFortuneToolActionText"
        :get-score-class="getScoreClass"
        :get-score-color="getScoreColor"
        @update:selected-year="emit('update:selectedYear', $event)"
        @update:selected-dayun-index="emit('update:selectedDayunIndex', $event)"
        @show-points-confirm="emit('showPointsConfirm', $event)"
      />
    </div>

    <!-- 操作按钮 -->
    <div class="result-actions-wrap">
      <div class="result-actions-heading">
        <div class="result-actions-heading__divider"></div>
        <span class="result-actions-heading__eyebrow">下一步动作</span>
        <p>保存记录 · 查看历史 · 深入解读</p>
      </div>
      <div class="result-actions">
        <el-button type="primary" @click="emit('saveResult')" :loading="saving">
          <el-icon><Download /></el-icon> 保存到当前设备
        </el-button>
        <el-button @click="emit('openBaziHistoryCenter')">
          <el-icon><Document /></el-icon> 查看我的记录
        </el-button>
        <el-button @click="emit('continueBaziJourney')">
          <el-icon><Cpu /></el-icon> 继续深入解读
        </el-button>
        <ShareCard
          title="八字排盘"
          :summary="baziShareSummary"
          :tags="baziShareTags"
          :sharePath="`/bazi?id=${result.id}`"
          :wuxingItems="wuxingDistributionItems"
        >
          <template #trigger>
            <el-button><el-icon><Share /></el-icon> 分享摘要</el-button>
          </template>
        </ShareCard>
        <el-button @click="emit('resetCurrentResult')">
          <el-icon><RefreshRight /></el-icon> 重新排盘
        </el-button>
      </div>
    </div>
    <WisdomText />
  </div>
</template>
