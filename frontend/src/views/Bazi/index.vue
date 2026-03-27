<template>
  <div class="bazi-page">
    <div class="container">
      <PageHeroHeader
        title="八字排盘"
        subtitle="支持精确出生时间、大概时段与未知时辰三种口径，结果页会同步标注当前精度，避免把估算时刻误当成精确排盘。"
        :icon="Grid"
      />

      <!-- 表单区 -->
      <BaziForm
        v-if="!result"
        v-model:birthTimeAccuracy="birthTimeAccuracy"
        v-model:exactBirthDate="exactBirthDate"
        v-model:estimatedBirthDate="estimatedBirthDate"
        v-model:estimatedTimeSlot="estimatedTimeSlot"
        v-model:gender="gender"
        v-model:birthCity="birthCity"
        v-model:confirmVisible="confirmVisible"
        v-model:pointsConfirmVisible="pointsConfirmVisible"
        :estimatedTimeOptions="estimatedTimeOptions"
        :estimatedModeHint="estimatedModeHint"
        :cityOptions="CITY_LONGITUDE_BY_PROVINCE"
        :baziSubmitIssues="baziSubmitIssues"
        :baziSubmitSummaryText="baziSubmitSummaryText"
        :currentPoints="currentPoints"
        :isFirstBazi="isFirstBazi"
        :isAccountReady="isAccountReady"
        :accountStatus="accountStatus"
        :loading="loading"
        :confirmDialogConfig="confirmDialogConfig"
        :startBaziButtonText="startBaziButtonText"
        :BAZI_BASE_COST="BAZI_BASE_COST"
        :pointsConfirmType="pointsConfirmType"
        :getPointsConfirmTitle="getPointsConfirmTitle"
        :getPointsConfirmCost="getPointsConfirmCost"
        @handleBaziIssue="handleBaziIssue"
        @showConfirm="showConfirm"
        @confirmCalculate="confirmCalculate"
        @confirmPointsConsume="confirmPointsConsume"
      />

      <!-- 加载状态 -->
      <div v-if="loading" class="loading-state card">
        <div class="loading-animation">
          <div class="loading-taiji"></div>
        </div>
        <h3>正在为你排盘...</h3>
        <p class="loading-text">计算天干地支 · 分析五行配置 · 生成命理解读</p>
        <div class="loading-steps">
          <div class="step" :class="{ active: loadingStep >= 1, done: loadingStep > 1 }">
            <span class="step-icon"><el-icon v-if="loadingStep > 1"><Check /></el-icon><template v-else>1</template></span>
            <span class="step-text">排八字</span>
          </div>
          <div class="step-line" :class="{ active: loadingStep >= 2 }"></div>
          <div class="step" :class="{ active: loadingStep >= 2, done: loadingStep > 2 }">
            <span class="step-icon"><el-icon v-if="loadingStep > 2"><Check /></el-icon><template v-else>2</template></span>
            <span class="step-text">算五行</span>
          </div>
          <div class="step-line" :class="{ active: loadingStep >= 3 }"></div>
          <div class="step" :class="{ active: loadingStep >= 3, done: loadingStep > 3 }">
            <span class="step-icon"><el-icon v-if="loadingStep > 3"><Check /></el-icon><template v-else>3</template></span>
            <span class="step-text">析命理</span>
          </div>
          <div class="step-line" :class="{ active: loadingStep >= 4 }"></div>
          <div class="step" :class="{ active: loadingStep >= 4 }">
            <span class="step-icon">4</span>
            <span class="step-text">出结果</span>
          </div>
        </div>
      </div>

      <!-- 结果区 -->
      <BaziResult
        v-else-if="result"
        :result="result"
        :birthTimeAccuracy="birthTimeAccuracy"
        :birthTimeAccuracyLabel="birthTimeAccuracyLabel"
        :locationContextLabel="locationContextLabel"
        :resultContextNote="resultContextNote"
        :resultModeLabel="resultModeLabel"
        :wuxingDistributionItems="wuxingDistributionItems"
        :currentYearLiunian="currentYearLiunian"
        :saving="saving"
        :baziShareSummary="baziShareSummary"
        :baziShareTags="baziShareTags"
        :getShishenClass="getShishenClass"
        :getScoreClass="getScoreClass"
        :getScoreColor="getScoreColor"
        :isCurrentDaYun="isCurrentDaYun"
        :getFortuneToolTagText="getFortuneToolTagText"
        :canUseFortuneTool="canUseFortuneTool"
        :getFortuneToolActionText="getFortuneToolActionText"
        :selectedYear="selectedYear"
        :yearlyFortuneResult="yearlyFortuneResult"
        :yearlyFortuneLoading="yearlyFortuneLoading"
        :isCompactViewport="isCompactViewport"
        :selectedDayunIndex="selectedDayunIndex"
        :dayunAnalysisResult="dayunAnalysisResult"
        :dayunAnalysisLoading="dayunAnalysisLoading"
        :dayunScoring="dayunScoring"
        :dayunChartData="dayunChartData"
        :dayunChartLoading="dayunChartLoading"
        :aiAnalysisResult="aiAnalysisResult"
        :aiAnalyzing="aiAnalyzing"
        :aiLoadingHint="aiLoadingHint"
        :canStartAiAnalysis="canStartAiAnalysis"
        :aiActionText="aiActionText"
        :aiAnalysisCost="aiAnalysisCost"
        :aiNeedsAccountRecovery="aiNeedsAccountRecovery"
        :aiRecoveryText="aiRecoveryText"
        @saveResult="saveResult"
        @openBaziHistoryCenter="openBaziHistoryCenter"
        @continueBaziJourney="continueBaziJourney"
        @resetCurrentResult="resetCurrentResult"
        @update:selectedYear="selectedYear = $event"
        @update:selectedDayunIndex="selectedDayunIndex = $event"
        @showPointsConfirm="showPointsConfirm"
        @startAiAnalysis="startAiAnalysis"
        @clearAiResult="clearAiResult"
        @loadPoints="loadPoints"
      />
    </div>
  </div>
</template>

<script setup>
import { Grid, Check } from '@element-plus/icons-vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import BaziForm from './BaziForm.vue'
import BaziResult from './BaziResult.vue'
import { useBazi } from './useBazi'

const {
  birthTimeAccuracy, exactBirthDate, estimatedBirthDate, estimatedTimeSlot,
  estimatedTimeOptions, estimatedModeHint, baziSubmitIssues, baziSubmitSummaryText,
  gender, loading, result, currentPoints, accountStatus, aiAnalysisCost,
  confirmVisible, saving, isFirstBazi, loadingStep,
  birthCity, CITY_LONGITUDE_BY_PROVINCE,
  fortunePointsCost, selectedYear, yearlyFortuneResult, yearlyFortuneLoading,
  isCompactViewport, selectedDayunIndex, dayunAnalysisResult, dayunAnalysisLoading,
  dayunChartData, dayunChartLoading, dayunScoring, pointsConfirmVisible, pointsConfirmType,
  aiAnalyzing, aiAnalysisResult, aiLoadingHint, canStartAiAnalysis, aiActionText,
  aiNeedsAccountRecovery, aiRecoveryText,
  resultModeLabel, birthTimeAccuracyLabel, locationContextLabel, resultContextNote,
  wuxingDistributionItems, isAccountReady, confirmDialogConfig, startBaziButtonText,
  baziShareSummary, baziShareTags, BAZI_BASE_COST,
  resetCurrentResult, handleBaziIssue, openBaziHistoryCenter, continueBaziJourney, loadPoints,
  getPointsConfirmTitle, getPointsConfirmCost, getFortuneToolTagText, canUseFortuneTool,
  getFortuneToolActionText, showPointsConfirm, confirmPointsConsume,
  getScoreColor, getScoreClass, showConfirm, confirmCalculate, saveResult,
  isCurrentDaYun, startAiAnalysis, clearAiResult, getShishenClass, currentYearLiunian,
} = useBazi()
</script>

<style scoped>
/* =============================================
   八字排盘页 - 页面级样式（index.vue）
   ============================================= */

/* 页面容器 */
.bazi-page {
  padding: 10px 0 78px;
  animation: fadeInUp 0.6s ease;
  background:
    radial-gradient(circle at 0% 0%, rgba(var(--primary-rgb), 0.14), transparent 34%),
    radial-gradient(circle at 100% 12%, rgba(245, 196, 103, 0.18), transparent 26%),
    linear-gradient(180deg, #fffdf8 0%, #fff9f1 46%, #fff7ee 100%);
}

/* 加载状态 */
.loading-state {
  max-width: 600px;
  margin: 0 auto;
  text-align: center;
  padding: 60px 40px;
  background: var(--bg-card);
  border-radius: 20px;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-lg);
}

.loading-animation {
  margin-bottom: 30px;
}

/* 太极图加载动画 */
.loading-taiji {
  width: 80px;
  height: 80px;
  margin: 0 auto;
  border-radius: 50%;
  background: linear-gradient(to bottom, #fff 50%, #000 50%);
  position: relative;
  animation: yinYangRotate 2s linear infinite;
  box-shadow: 0 0 30px rgba(184, 134, 11, 0.3);
}

.loading-taiji::before,
.loading-taiji::after {
  content: '';
  position: absolute;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  left: 50%;
  transform: translateX(-50%);
}

.loading-taiji::before {
  background: #fff;
  top: 0;
  box-shadow: 0 0 0 12px #000 inset;
}

.loading-taiji::after {
  background: #000;
  bottom: 0;
  box-shadow: 0 0 0 12px #fff inset;
}

.loading-state h3 {
  color: var(--text-primary);
  font-size: 24px;
  margin-bottom: 10px;
}

.loading-text {
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 40px;
}

/* 加载步骤 */
.loading-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  opacity: 0.4;
  transition: all 0.3s ease;
}

.step.active {
  opacity: 1;
}

.step.done {
  opacity: 0.7;
}

.step-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--bg-tertiary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: var(--text-secondary);
  border: 2px solid var(--border-color);
  transition: all 0.3s ease;
}

.step.active .step-icon {
  background: rgba(184, 134, 11, 0.1);
  border-color: var(--primary-color);
  color: var(--primary-color);
  box-shadow: 0 0 15px rgba(184, 134, 11, 0.3);
}

.step.done .step-icon {
  background: rgba(103, 194, 58, 0.3);
  border-color: #67c23a;
  color: #67c23a;
}

.step-text {
  font-size: 12px;
  color: #5f5548;
  transition: all 0.3s ease;
}

.step.active .step-text {
  color: var(--primary-color);
  font-weight: 500;
}

.step-line {
  width: 40px;
  height: 2px;
  background: var(--border-color);
  transition: all 0.3s ease;
}

.step-line.active {
  background: linear-gradient(90deg, #67c23a, #D4AF37);
}

/* 动画 */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes yinYangRotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .bazi-page {
    padding: 0 0 56px;
  }
}
</style>