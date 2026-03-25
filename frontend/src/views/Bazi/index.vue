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
        v-model:confirmVisible="confirmVisible"
        v-model:pointsConfirmVisible="pointsConfirmVisible"
        :estimatedTimeOptions="estimatedTimeOptions"
        :estimatedModeHint="estimatedModeHint"
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
@import './style.css';
</style>