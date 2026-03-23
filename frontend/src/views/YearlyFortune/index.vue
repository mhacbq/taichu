<template>
  <div class="yearly-fortune-page">
    <div class="container">
      <PageHeroHeader
        title="2026 流年运势深度解析"
        subtitle="结合个人八字，AI深度解析事业、财富、感情、健康四大运势，提供专属开运建议与每月吉凶提醒。"
        :icon="Calendar"
      />

      <!-- 暖心提示 -->
      <div class="warm-tip card" v-if="!result">
        <el-icon class="tip-icon"><StarFilled /></el-icon>
        <div class="tip-content">
          <p class="tip-title">流年运势解析能帮你了解什么？</p>
          <p class="tip-desc">全年运势总览 · 每月吉凶提醒 · 事业发展建议 · 财运时机把握 · 感情关系指引 · 健康养生提示</p>
        </div>
      </div>

      <!-- 八字表单 -->
      <div class="bazi-form card" v-if="!result">
        <div class="form-group form-group--time" data-bazi-field="birth-time">
          <div class="form-group__header">
            <label>出生日期与时间</label>
          </div>

          <!-- 出生日期时间选择 -->
          <el-date-picker
            v-model="birthDateTime"
            type="datetime"
            placeholder="请选择出生日期和时间"
            format="YYYY-MM-DD HH:mm"
            value-format="YYYY-MM-DD HH:mm"
            class="form-input--datetime"
            size="default"
            :disabled-date="disabledDate"
          />
        </div>

        <!-- 性别选择 -->
        <div class="form-group" data-bazi-field="gender">
          <label>性别</label>
          <div class="gender-options">
            <div
              class="gender-option"
              :class="{ 'gender-option--active': gender === 'male' }"
              @click="gender = 'male'"
            >
              <el-icon><Male /></el-icon>
              <span>男</span>
            </div>
            <div
              class="gender-option"
              :class="{ 'gender-option--active': gender === 'female' }"
              @click="gender = 'female'"
            >
              <el-icon><Female /></el-icon>
              <span>女</span>
            </div>
          </div>
        </div>

        <!-- 提交按钮 -->
        <div class="form-actions">
          <el-button
            type="primary"
            size="default"
            @click="handleCalculate"
            :loading="calculating"
            class="btn-submit"
          >
            <el-icon v-if="!calculating"><MagicStick /></el-icon>
            {{ calculating ? '正在分析...' : '开始解析流年运势' }}
          </el-button>
          <p class="points-notice">消耗 {{ pointsCost }} 积分</p>
        </div>
      </div>

      <!-- 结果展示 -->
      <div class="result-section" v-if="result">
        <div class="result-header card">
          <h2 class="result-title">2026 丙午年流年运势解析</h2>
          <p class="result-subtitle">基于您的八字命盘，AI 深度分析全年运势</p>
        </div>

        <!-- 四大运势 -->
        <div class="fortune-categories">
          <div class="category-card card" v-for="category in fortuneCategories" :key="category.key">
            <div class="category-icon" :class="`category-icon--${category.key}`">
              <el-icon :size="32"><component :is="category.icon" /></el-icon>
            </div>
            <h3 class="category-title">{{ category.title }}</h3>
            <div class="category-score">
              <span class="score-label">综合评分</span>
              <span class="score-value">{{ result[category.key]?.score || 75 }}</span>
              <span class="score-max">/100</span>
            </div>
            <p class="category-desc">{{ result[category.key]?.summary || category.defaultSummary }}</p>
            <ul class="category-tips" v-if="result[category.key]?.tips">
              <li v-for="(tip, index) in result[category.key]?.tips" :key="index">
                {{ tip }}
              </li>
            </ul>
          </div>
        </div>

        <!-- 每月运势 -->
        <div class="monthly-fortune card" v-if="monthlyFortune && monthlyFortune.length > 0">
          <h3 class="section-title">每月运势详情</h3>
          <div class="months-grid">
            <div
              class="month-card"
              v-for="month in monthlyFortune"
              :key="month.month"
              :class="`month-card--${month.level}`"
            >
              <span class="month-number">{{ month.month }}月</span>
              <span class="month-level">{{ month.levelText }}</span>
              <p class="month-desc">{{ month.description }}</p>
            </div>
          </div>
        </div>

        <!-- AI 深度分析 -->
        <div class="ai-analysis card" v-if="aiAnalysis">
          <h3 class="section-title">
            <el-icon><ChatLineRound /></el-icon>
            AI 深度解读
          </h3>
          <div class="analysis-content" v-html="aiAnalysis"></div>
        </div>

        <!-- AI 分析按钮 -->
        <div class="ai-action card" v-if="result && !aiAnalysis">
          <el-button type="primary" @click="getAiAnalysis" :loading="aiLoading" size="default">
            <el-icon><MagicStick /></el-icon>
            获取AI深度解读
          </el-button>
          <p class="ai-tip">消耗 {{ aiPointsCost }} 积分</p>
        </div>

        <!-- 重新计算按钮 -->
        <div class="result-actions">
          <el-button @click="resetForm" size="default">重新测算</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Calendar, MagicStick, Male, Female, StarFilled, ChatLineRound } from '@element-plus/icons-vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'

import { useYearlyFortune } from './useYearlyFortune'

const {
  // 状态数据
  birthDateTime, gender, calculating, result,
  monthlyFortune, aiAnalysis, aiLoading,
  pointsCost, aiPointsCost, fortuneCategories,

  // 方法
  disabledDate, handleCalculate, resetForm, getAiAnalysis,
} = useYearlyFortune()
</script>

<style scoped>
@import './style.css';
</style>
