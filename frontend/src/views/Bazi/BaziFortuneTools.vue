<script setup lang="ts">
import {
  TrendCharts, Calendar, StarFilled, QuestionFilled, Loading,
  Aim, Briefcase, Money, UserFilled, Warning
} from '@element-plus/icons-vue'

const props = defineProps({
  result: { type: Object, default: null },
  // 流年
  selectedYear: { type: Number, default: () => new Date().getFullYear() },
  yearlyFortuneResult: { type: Object, default: null },
  yearlyFortuneLoading: { type: Boolean, default: false },
  isCompactViewport: { type: Boolean, default: false },
  // 大运
  selectedDayunIndex: { type: Number, default: 0 },
  dayunAnalysisResult: { type: Object, default: null },
  dayunAnalysisLoading: { type: Boolean, default: false },
  dayunScoring: { type: Boolean, default: false },
  // K线
  dayunChartData: { type: Object, default: null },
  dayunChartLoading: { type: Boolean, default: false },
  // 工具方法
  isCurrentDaYun: { type: Function, default: () => false },
  getFortuneToolTagText: { type: Function, default: () => '' },
  canUseFortuneTool: { type: Function, default: () => false },
  getFortuneToolActionText: { type: Function, default: () => '' },
  getScoreClass: { type: Function, default: () => '' },
  getScoreColor: { type: Function, default: () => '' },
})

const emit = defineEmits([
  'update:selectedYear',
  'update:selectedDayunIndex',
  'showPointsConfirm',
])
</script>

<template>
  <!-- 大运与流年走势 -->
  <div class="fortune-section-wrapper">
    <div class="section-divider"></div>
    <div class="pane-title">
      <el-icon class="title-icon"><TrendCharts /></el-icon>
      <span class="title-text">大运与流年走势</span>
      <span class="title-desc">十年大运、逐年流年参考</span>
    </div>

    <!-- 大运分析 -->
    <div class="dayun-section" v-if="result?.dayun?.length > 0">
      <div class="section-title-with-tip">
        <h3>大运走势</h3>
        <el-tooltip content="大运是每10年一个阶段的人生运势，就像人生的"季节"，每个阶段都有不同的机遇和挑战" placement="top">
          <span class="help-icon"><el-icon><QuestionFilled /></el-icon></span>
        </el-tooltip>
      </div>
      <div v-if="dayunScoring" class="dayun-scoring-tip">
        <el-icon class="is-loading"><Loading /></el-icon>
        <span>AI 正在根据你的八字分析大运评分中…</span>
      </div>
      <div class="dayun-timeline">
        <div
          v-for="(yun, index) in result.dayun"
          :key="index"
          class="dayun-item"
          :class="{ 'current': isCurrentDaYun(yun), [`level-${yun.trend_level || 'neutral'}`]: true }"
        >
          <div class="dayun-score-icon" :class="`score-${yun.trend_level || 'neutral'}`">
            <template v-if="dayunScoring">
              <el-icon class="is-loading score-star"><Loading /></el-icon>
            </template>
            <template v-else>
              <el-icon class="score-star"><StarFilled /></el-icon>
              <el-icon class="score-star" v-if="yun.score >= 60"><StarFilled /></el-icon>
              <el-icon class="score-star" v-if="yun.score >= 75"><StarFilled /></el-icon>
              <span class="score-num" v-if="yun.score">{{ yun.score }}</span>
            </template>
          </div>
          <div class="dayun-age">{{ yun.age_start }}-{{ yun.age_end }}岁</div>
          <div class="dayun-pillar">
            <span class="gan">{{ yun.gan }}</span>
            <span class="zhi">{{ yun.zhi }}</span>
          </div>
          <div class="dayun-shishen">{{ yun.shishen }}</div>
          <div class="dayun-luck" :class="yun.trend_level || yun.luck">{{ yun.luck }}</div>
          <div class="dayun-desc">{{ yun.luck_desc }}</div>
          <div class="dayun-nayin">{{ yun.nayin }}</div>
        </div>
      </div>
    </div>

    <!-- 流年分析 -->
    <div class="liunian-section" v-if="result?.liunian?.length > 0">
      <div class="section-title-with-tip">
        <h3>流年运势</h3>
        <el-tooltip content="流年是每年的运势参考，结合大运提供年度生活建议" placement="top">
          <span class="help-icon"><el-icon><QuestionFilled /></el-icon></span>
        </el-tooltip>
      </div>
      <div class="liunian-grid">
        <div
          v-for="(year, index) in result.liunian"
          :key="index"
          class="liunian-item"
          :class="{ 'current': year.is_current }"
        >
          <div class="liunian-year">{{ year.year }}年</div>
          <div class="liunian-pillar">
            <span class="gan">{{ year.gan }}</span>
            <span class="zhi">{{ year.zhi }}</span>
          </div>
          <div class="liunian-wuxing">
            <span class="badge" :class="year.gan_wuxing">{{ year.gan_wuxing }}</span>
            <span class="badge" :class="year.zhi_wuxing">{{ year.zhi_wuxing }}</span>
          </div>
          <div class="liunian-nayin">{{ year.nayin }}</div>
        </div>
      </div>
    </div>
  </div>

  <!-- 深度预测工具 -->
  <div class="tools-section-wrapper">
    <div class="section-divider"></div>
    <div class="pane-title">
      <el-icon class="title-icon"><Aim /></el-icon>
      <span class="title-text">深度预测工具</span>
      <span class="title-desc">流年深度分析、大运评分、运势K线</span>
    </div>

    <!-- 流年运势分析 -->
    <div class="yearly-fortune-section" v-if="result?.bazi">
      <div class="section-title-with-tag">
        <h3>流年运势深度分析</h3>
        <el-tag type="warning" size="small">{{ getFortuneToolTagText('yearly') }}</el-tag>
      </div>
      <div class="year-selector">
        <div class="year-selector__header">
          <div class="year-selector__meta">
            <span class="selector-label">{{ isCompactViewport ? '年份' : '选择年份' }}</span>
            <span class="selector-hint">拖动滑块切换当前流年分析年份</span>
          </div>
          <span class="selected-year">{{ selectedYear }}年</span>
        </div>
        <el-slider
          :model-value="selectedYear"
          @update:model-value="emit('update:selectedYear', $event)"
          :min="new Date().getFullYear() - 3"
          :max="new Date().getFullYear() + 7"
          :step="1"
          :show-stops="!isCompactViewport"
          class="year-slider"
        />
      </div>

      <div v-if="yearlyFortuneResult" class="yearly-result">
        <div class="yearly-header">
          <div class="year-info">
            <span class="year-number">{{ yearlyFortuneResult.year }}</span>
            <span class="year-ganzhi">{{ yearlyFortuneResult.ganzhi }}年</span>
            <span class="year-nayin">{{ yearlyFortuneResult.nayin }}</span>
          </div>
          <div class="score-display">
            <div class="score-circle" :class="getScoreClass(yearlyFortuneResult.score)">
              <span class="score-value">{{ yearlyFortuneResult.score }}</span>
              <span class="score-label">运势评分</span>
            </div>
            <div class="rating-badge" :class="getScoreClass(yearlyFortuneResult.score)">{{ yearlyFortuneResult.rating }}</div>
          </div>
        </div>
        <div class="yearly-analysis">
          <div class="analysis-card overall">
            <h4><el-icon><Aim /></el-icon> 整体运势</h4><p>{{ yearlyFortuneResult.overall }}</p>
          </div>
          <div class="analysis-grid">
            <div class="analysis-card"><h4><el-icon><Briefcase /></el-icon> 事业运势</h4><p>{{ yearlyFortuneResult.career }}</p></div>
            <div class="analysis-card"><h4><el-icon><Money /></el-icon> 财富运势</h4><p>{{ yearlyFortuneResult.wealth }}</p></div>
            <div class="analysis-card"><h4><el-icon><UserFilled /></el-icon> 感情运势</h4><p>{{ yearlyFortuneResult.relationship }}</p></div>
            <div class="analysis-card"><h4><el-icon><Warning /></el-icon> 健康提醒</h4><p>{{ yearlyFortuneResult.health }}</p></div>
          </div>
          <div class="analysis-card advice">
            <h4><el-icon><StarFilled /></el-icon> 开运建议</h4><p>{{ yearlyFortuneResult.advice }}</p>
          </div>
          <div class="lucky-info">
            <div class="lucky-section">
              <h5>幸运月份</h5>
              <div class="lucky-tags">
                <span v-for="month in yearlyFortuneResult.lucky_months" :key="month" class="lucky-tag good">{{ month }}月</span>
              </div>
            </div>
            <div class="lucky-section">
              <h5>注意月份</h5>
              <div class="lucky-tags">
                <span v-for="month in yearlyFortuneResult.unlucky_months" :key="month" class="lucky-tag bad">{{ month }}月</span>
              </div>
            </div>
            <div class="lucky-section">
              <h5>幸运颜色</h5>
              <div class="lucky-tags">
                <span v-for="color in yearlyFortuneResult.lucky_colors" :key="color" class="lucky-tag color">{{ color }}</span>
              </div>
            </div>
            <div class="lucky-section">
              <h5>幸运数字</h5>
              <div class="lucky-tags">
                <span v-for="num in yearlyFortuneResult.lucky_numbers" :key="num" class="lucky-tag number">{{ num }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="analysis-actions">
        <p class="analysis-desc">基于你的八字，AI为你深度分析流年运势</p>
        <el-button type="warning" size="large" :loading="yearlyFortuneLoading" :disabled="!canUseFortuneTool('yearly')" @click="emit('showPointsConfirm', 'yearly')">
          <el-icon class="btn-icon"><StarFilled /></el-icon>
          {{ getFortuneToolActionText('yearly', '开始流年分析') }}
        </el-button>
      </div>
    </div>

    <!-- 大运运势分析 -->
    <div class="dayun-fortune-section" v-if="result?.dayun?.length > 0">
      <div class="section-title-with-tag">
        <h3>大运运势评分</h3>
        <el-tag type="warning" size="small">{{ getFortuneToolTagText('dayun') }}</el-tag>
      </div>
      <div class="dayun-selector">
        <span class="selector-label">选择大运：</span>
        <el-radio-group
          :model-value="selectedDayunIndex"
          @update:model-value="emit('update:selectedDayunIndex', $event)"
          size="small"
          class="premium-segment premium-segment--scroll"
        >
          <el-radio-button v-for="(yun, index) in result.dayun" :key="index" :label="index">
            {{ yun.gan }}{{ yun.zhi }} ({{ yun.age_start }}-{{ yun.age_end }}岁)
          </el-radio-button>
        </el-radio-group>
      </div>

      <div v-if="dayunAnalysisResult" class="dayun-analysis-result">
        <div class="dayun-header">
          <div class="dayun-info">
            <span class="dayun-name">{{ dayunAnalysisResult.dayun.gan }}{{ dayunAnalysisResult.dayun.zhi }}</span>
            <span class="dayun-shishen">{{ dayunAnalysisResult.dayun.shishen }}</span>
            <span class="dayun-age">{{ dayunAnalysisResult.dayun.start_age }}-{{ dayunAnalysisResult.dayun.end_age }}岁</span>
          </div>
          <div class="dayun-level-badge" :class="getScoreClass(dayunAnalysisResult.overall_score)">{{ dayunAnalysisResult.fortune_level }}</div>
        </div>
        <div class="dayun-scores">
          <div v-for="(key, label) in { overall: '综合', career: '事业', wealth: '财运', relationship: '感情', health: '健康' }" :key="key" class="score-item">
            <span class="score-name">{{ label }}</span>
            <el-progress :percentage="dayunAnalysisResult.scores[key]" :color="getScoreColor(dayunAnalysisResult.scores[key])" :stroke-width="key === 'overall' ? 12 : 10" class="score-progress" />
            <span class="score-value">{{ dayunAnalysisResult.scores[key] }}</span>
          </div>
        </div>
        <div class="dayun-analysis-text">
          <div class="text-card" v-for="(text, key) in dayunAnalysisResult.analysis" :key="key"><p>{{ text }}</p></div>
        </div>
        <div class="key-suggestions">
          <h4><el-icon><StarFilled /></el-icon> 关键建议</h4>
          <ul>
            <li v-for="(suggestion, index) in dayunAnalysisResult.key_suggestions" :key="index">{{ suggestion }}</li>
          </ul>
        </div>
      </div>
      <div v-else class="analysis-actions">
        <p class="analysis-desc">深度分析此大运的各方面运势评分</p>
        <el-button type="primary" size="large" :loading="dayunAnalysisLoading" :disabled="!canUseFortuneTool('dayun')" @click="emit('showPointsConfirm', 'dayun')">
          <el-icon class="btn-icon"><TrendCharts /></el-icon>
          {{ getFortuneToolActionText('dayun', '开始大运评分') }}
        </el-button>
      </div>
    </div>

    <!-- 运势K线图 -->
    <div class="fortune-chart-section" v-if="result?.dayun?.length > 0">
      <div class="section-title-with-tag">
        <h3>运势K线图</h3>
        <el-tag type="warning" size="small">{{ getFortuneToolTagText('chart') }}</el-tag>
      </div>

      <div v-if="dayunChartData" class="chart-result">
        <div class="chart-summary">
          <p>{{ dayunChartData.summary }}</p>
          <div v-if="dayunChartData.best_period" class="best-period">
            <span class="best-label">最佳时期：</span>
            <span class="best-value">
              {{ dayunChartData.best_period.dayun_name }}运
              ({{ dayunChartData.best_period.age_range }})
              评分{{ dayunChartData.best_period.dayun_score }}分
            </span>
          </div>
        </div>
        <div class="chart-container">
          <div v-for="(dayun, index) in dayunChartData.chart_data" :key="index" class="chart-dayun">
            <div class="chart-dayun-header">
              <span class="dayun-title">{{ dayun.dayun_name }}运</span>
              <span class="dayun-score" :class="getScoreClass(dayun.overall_score)">{{ dayun.overall_score }}分</span>
              <span class="dayun-trend">{{ dayun.trend }}</span>
            </div>
            <div class="chart-years">
              <div
                v-for="year in dayun.years"
                :key="year.year"
                class="chart-year-bar"
                :class="{ 'current': year.is_current }"
                :style="{ height: year.score + '%' }"
                :title="`${year.year}年 (${year.age}岁): ${year.score}分`"
              >
                <span class="year-label">{{ year.year }}</span>
                <span class="year-score">{{ year.score }}</span>
              </div>
            </div>
            <div class="chart-legend">
              <span>{{ dayun.start_age }}-{{ dayun.end_age }}岁</span>
              <span :class="getScoreClass(dayun.overall_score)">{{ dayun.fortune_level }}</span>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="analysis-actions">
        <p class="analysis-desc">可视化展示你一生的大运走势，找到最佳发展时期</p>
        <el-button type="success" size="large" :loading="dayunChartLoading" :disabled="!canUseFortuneTool('chart')" @click="emit('showPointsConfirm', 'chart')">
          <el-icon><TrendCharts /></el-icon>
          {{ getFortuneToolActionText('chart', '生成运势K线图') }}
        </el-button>
      </div>
    </div>
  </div>
</template>
