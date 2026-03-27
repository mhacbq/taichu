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
        :true-solar-time-info="result.true_solar_time || null"
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

<style scoped>
/* =============================================
   八字结果页组件样式（BaziResult.vue）
   ============================================= */

/* 结果容器 */
.bazi-result {
  max-width: 920px;
  margin: 0 auto;
  padding: 30px;
  border-radius: 30px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 250, 241, 0.95));
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  box-shadow: 0 24px 52px rgba(15, 23, 42, 0.06), 0 12px 30px rgba(var(--primary-rgb), 0.04);
  animation: fadeInUp 0.8s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.bazi-result::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 200px;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(184, 134, 11, 0.5), transparent);
}

/* 结果页顶部英雄区域 */
.result-hero {
  text-align: center;
  padding: 36px 24px 28px;
  margin: -30px -30px 28px;
  border-radius: 30px 30px 0 0;
  background:
    radial-gradient(ellipse 80% 60% at 50% 0%, rgba(212, 175, 55, 0.18), transparent),
    linear-gradient(180deg, rgba(255, 248, 228, 0.98) 0%, rgba(255, 255, 255, 0) 100%);
  position: relative;
  overflow: hidden;
}

.result-hero__ornament {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 260px;
  height: 260px;
  border-radius: 50%;
  border: 1px solid rgba(212, 175, 55, 0.08);
  pointer-events: none;
}

.result-hero__ornament::before,
.result-hero__ornament::after {
  content: '';
  position: absolute;
  border-radius: 50%;
  border: 1px solid rgba(212, 175, 55, 0.06);
}

.result-hero__ornament::before { inset: -30px; }
.result-hero__ornament::after { inset: -60px; }

.result-hero__badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 18px;
  border-radius: 999px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.18), rgba(245, 196, 103, 0.12));
  border: 1px solid rgba(212, 175, 55, 0.2);
  color: #8d5f1c;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin-bottom: 14px;
  position: relative;
}

.result-hero__title {
  font-size: clamp(26px, 3.5vw, 36px);
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--text-primary);
  margin-bottom: 8px;
  background: linear-gradient(135deg, #3a2a10, #8d5f1c, #c8960c);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  position: relative;
}

.result-hero__subtitle {
  font-size: 15px;
  color: #8a7a62;
  font-weight: 500;
  letter-spacing: 0.15em;
  margin-bottom: 20px;
}

.result-meta {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px;
}

.meta-tag {
  min-height: 42px;
  padding: 8px 14px;
  border-radius: 999px;
  font-size: 12px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid transparent;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.meta-tag--success {
  background: rgba(var(--success-color-rgb), 0.2);
  color: var(--success-color);
}

.meta-tag--info {
  background: rgba(var(--primary-rgb), 0.12);
  border-color: rgba(var(--primary-rgb), 0.2);
  color: var(--primary-color);
}

.meta-tag--neutral {
  background: var(--bg-tertiary);
  border-color: var(--border-color);
  color: var(--text-secondary);
}

.meta-tag--warning {
  background: rgba(230, 162, 60, 0.14);
  border-color: rgba(230, 162, 60, 0.24);
  color: var(--warning-color);
}

/* 上下文说明 */
.result-context-note {
  margin: 0 0 24px;
  padding: 14px 16px;
  border-radius: 22px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 240, 0.92));
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
  color: var(--text-secondary);
  line-height: 1.7;
}

/* 当前流年速览 */
.current-fortune-brief {
  margin: 0 0 28px;
  padding: 20px 24px;
  border-radius: 20px;
  background: linear-gradient(135deg, rgba(255, 248, 228, 0.95), rgba(255, 243, 214, 0.85));
  border: 1px solid rgba(212, 175, 55, 0.18);
  box-shadow: 0 8px 24px rgba(149, 111, 45, 0.06);
}

.fortune-brief__header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  color: #6b4a12;
}

.fortune-brief__header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
}

.fortune-brief__header .el-icon {
  font-size: 20px;
  color: #d4af37;
}

.fortune-brief__content {
  display: flex;
  align-items: center;
  gap: 24px;
}

.fortune-brief__pillar {
  display: flex;
  gap: 6px;
}

.fortune-brief__pillar .gan,
.fortune-brief__pillar .zhi {
  font-size: 32px;
  font-weight: 800;
  color: #3a2a10;
}

.fortune-brief__info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.fortune-brief__nayin {
  font-size: 14px;
  color: #9a6612;
  font-weight: 600;
}

.fortune-brief__wuxing {
  display: flex;
  gap: 6px;
}

.fortune-brief__wuxing .badge {
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 8px;
  font-weight: 600;
}

.fortune-brief__wuxing .badge.金 { background: rgba(218, 165, 32, 0.2); color: #e8c56e; }
.fortune-brief__wuxing .badge.木 { background: rgba(34, 139, 34, 0.2); color: #5dba5d; }
.fortune-brief__wuxing .badge.水 { background: rgba(30, 144, 255, 0.2); color: #5aabf0; }
.fortune-brief__wuxing .badge.火 { background: rgba(220, 20, 60, 0.2); color: #f06080; }
.fortune-brief__wuxing .badge.土 { background: rgba(180, 120, 60, 0.2); color: #c8956a; }

/* 结果分区 */
.result-section {
  margin-bottom: 0;
}

.result-section + .result-section {
  margin-top: 8px;
}

.section-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--border-light), transparent);
  margin: 32px 0;
}

/* 分区标题 */
.result-section .pane-title,
.fortune-section-wrapper .pane-title,
.tools-section-wrapper .pane-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  padding: 14px 18px;
  border-radius: 12px;
  background: var(--bg-secondary);
  border-left: 3px solid var(--primary-color);
}

.pane-title .title-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.12), rgba(245, 196, 103, 0.06));
  color: #9a6612;
  font-size: 18px;
  box-shadow: none;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pane-title .title-text {
  font-size: 17px;
  font-weight: 700;
  color: var(--text-primary);
}

.pane-title .title-desc {
  font-size: 12px;
  color: var(--text-tertiary);
  margin-left: auto;
}

/* 操作按钮区域 */
.result-actions-wrap {
  margin-top: 36px;
}

.result-actions-heading {
  max-width: 720px;
  margin: 0 auto 20px;
  text-align: center;
}

.result-actions-heading__divider {
  width: 60px;
  height: 2px;
  margin: 0 auto 16px;
  background: linear-gradient(90deg, transparent, rgba(212, 175, 55, 0.4), transparent);
  border-radius: 1px;
}

.result-actions-heading__eyebrow {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 14px;
  border-radius: 999px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.12), rgba(245, 196, 103, 0.06));
  border: 1px solid rgba(212, 175, 55, 0.12);
  color: #8d5f1c;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin-bottom: 8px;
}

.result-actions-heading p {
  margin: 0;
  color: #8a7a62;
  font-size: 13px;
  line-height: 1.7;
}

.result-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-top: 0;
  flex-wrap: wrap;
}

/* 动画 */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

/* 响应式 */
@media (max-width: 768px) {
  .bazi-result {
    padding: 22px 18px;
    border-radius: var(--radius-xl);
  }

  .result-hero {
    margin: -22px -18px 22px;
    padding: 30px 18px 22px;
  }

  .result-meta {
    justify-content: center;
  }

  .fortune-brief__content {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .result-actions {
    flex-direction: column;
    width: 100%;
  }

  .result-actions .el-button {
    width: 100%;
    margin-left: 0 !important;
    margin-bottom: 10px;
  }
}
</style>