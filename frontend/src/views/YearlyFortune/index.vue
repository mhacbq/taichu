<template>
  <div class="yearly-fortune-page">
    <div class="container">
      <PageHeroHeader
        :title="`${currentYear} 流年运势深度解析`"
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

        <!-- 顶部年份标题卡 -->
        <div class="result-hero card">
          <div class="result-hero__bg"></div>
          <div class="result-hero__content">
            <div class="result-hero__year-badge">{{ currentYear }}</div>
            <h2 class="result-hero__title">{{ chineseYearName }} 流年运势</h2>
            <p class="result-hero__subtitle">基于您的八字命盘，AI 深度解析全年运势走向</p>
            <div class="result-hero__meta" v-if="result.ganzhi || result.nayin">
              <span class="meta-tag" v-if="result.ganzhi">{{ result.ganzhi }}年</span>
              <span class="meta-tag" v-if="result.nayin">纳音：{{ result.nayin }}</span>
              <span class="meta-tag meta-tag--score" v-if="result.score">综合评分 {{ result.score }}/100</span>
              <span class="meta-tag meta-tag--rating" v-if="result.rating">{{ result.rating }}</span>
            </div>
          </div>
        </div>

        <!-- 四大运势卡片 -->
        <div class="fortune-categories">
          <div class="category-card card" v-for="category in fortuneCategories" :key="category.key">
            <div class="category-card__header">
              <div class="category-icon" :class="`category-icon--${category.key}`">
                <el-icon :size="28"><component :is="category.icon" /></el-icon>
              </div>
              <div class="category-card__title-wrap">
                <h3 class="category-title">{{ category.title }}</h3>
                <div class="category-score-bar">
                  <div
                    class="score-bar-fill"
                    :class="`score-bar-fill--${category.key}`"
                    :style="{ width: (getCategoryScore(category.key)) + '%' }"
                  ></div>
                </div>
              </div>
              <div class="category-score-num">
                <span class="score-value">{{ getCategoryScore(category.key) }}</span>
                <span class="score-max">/100</span>
              </div>
            </div>
            <p class="category-desc">{{ getCategoryText(category.key) || category.defaultSummary }}</p>
          </div>
        </div>

        <!-- 幸运元素 -->
        <div class="lucky-elements card" v-if="result.lucky_colors?.length || result.lucky_numbers?.length || result.lucky_directions?.length">
          <h3 class="section-title">
            <el-icon><Star /></el-icon>
            专属幸运元素
          </h3>
          <div class="lucky-grid">
            <div class="lucky-item" v-if="result.lucky_colors?.length">
              <div class="lucky-item__label">幸运颜色</div>
              <div class="lucky-item__tags">
                <span
                  class="lucky-tag lucky-tag--color"
                  v-for="color in result.lucky_colors"
                  :key="color"
                >{{ color }}</span>
              </div>
            </div>
            <div class="lucky-item" v-if="result.lucky_numbers?.length">
              <div class="lucky-item__label">幸运数字</div>
              <div class="lucky-item__tags">
                <span
                  class="lucky-tag lucky-tag--number"
                  v-for="num in result.lucky_numbers"
                  :key="num"
                >{{ num }}</span>
              </div>
            </div>
            <div class="lucky-item" v-if="result.lucky_directions?.length">
              <div class="lucky-item__label">幸运方位</div>
              <div class="lucky-item__tags">
                <span
                  class="lucky-tag lucky-tag--direction"
                  v-for="dir in result.lucky_directions"
                  :key="dir"
                >{{ dir }}</span>
              </div>
            </div>
            <div class="lucky-item" v-if="result.lucky_months?.length">
              <div class="lucky-item__label">吉利月份</div>
              <div class="lucky-item__tags">
                <span
                  class="lucky-tag lucky-tag--month lucky-tag--good"
                  v-for="m in result.lucky_months"
                  :key="m"
                >{{ m }}月</span>
              </div>
            </div>
            <div class="lucky-item" v-if="result.unlucky_months?.length">
              <div class="lucky-item__label">需注意月份</div>
              <div class="lucky-item__tags">
                <span
                  class="lucky-tag lucky-tag--month lucky-tag--bad"
                  v-for="m in result.unlucky_months"
                  :key="m"
                >{{ m }}月</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 每月运势时间轴 -->
        <div class="monthly-fortune card" v-if="monthlyFortune && monthlyFortune.length > 0">
          <h3 class="section-title">
            <el-icon><Calendar /></el-icon>
            十二月运势详情
          </h3>
          <div class="months-timeline">
            <div
              class="month-item"
              v-for="month in monthlyFortune"
              :key="month.month"
            >
              <div class="month-item__head">
                <div class="month-item__dot" :class="`month-dot--${month.level}`"></div>
                <div class="month-item__num">{{ month.month }}月</div>
                <div class="month-item__level" :class="`month-level--${month.level}`">{{ month.levelText }}</div>
              </div>
              <div class="month-item__body">
                <p class="month-item__desc">{{ month.description }}</p>
                <p class="month-item__tip" v-if="month.tip">💡 {{ month.tip }}</p>
              </div>
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

        <!-- 重新计算按钮 -->
        <div class="result-actions">
          <el-button @click="resetForm" size="default">重新测算</el-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Calendar, MagicStick, Male, Female, StarFilled, ChatLineRound, Star } from '@element-plus/icons-vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import { useYearlyFortune } from './useYearlyFortune'

const {
  birthDateTime, gender, calculating, result,
  monthlyFortune, aiAnalysis,
  pointsCost, fortuneCategories,
  currentYear, chineseYearName,
  disabledDate, handleCalculate, resetForm,
  getCategoryText, getCategoryScore,
} = useYearlyFortune()
</script>

<style scoped>
@import './style.css';
</style>
