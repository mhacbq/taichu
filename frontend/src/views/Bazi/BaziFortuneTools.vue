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
        <el-tooltip content="大运是每10年一个阶段的人生运势，就像人生的季节，每个阶段都有不同的机遇和挑战" placement="top">
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

<style scoped>
/* ===== 大运流年工具组件（BaziFortuneTools.vue）===== */

/* 分区标题 */
.pane-title {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  padding: 14px 18px;
  border-radius: 14px;
  background: rgba(255, 250, 241, 0.9);
  border: 1px solid rgba(227, 184, 104, 0.2);
  border-left: 3px solid rgba(212, 175, 55, 0.6);
}

.pane-title .title-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: rgba(212, 175, 55, 0.12);
  color: #9a6612;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.pane-title .title-text {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}

.pane-title .title-desc {
  font-size: 12px;
  color: var(--text-tertiary);
  margin-left: auto;
}

.section-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(227, 184, 104, 0.3), transparent);
  margin: 28px 0;
}

.section-title-with-tip,
.section-title-with-tag {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.section-title-with-tip h3,
.section-title-with-tag h3 {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.help-icon {
  cursor: help;
  color: rgba(212, 175, 55, 0.7);
}

/* ===== 大运区域 ===== */
.dayun-section {
  margin-top: 20px;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid rgba(227, 184, 104, 0.2);
}

.dayun-scoring-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  margin-bottom: 14px;
  background: rgba(255, 250, 241, 0.9);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 10px;
  color: #8a6012;
  font-size: 13px;
}

.dayun-timeline {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.dayun-item {
  background: rgba(255, 250, 241, 0.6);
  border-radius: 12px;
  padding: 14px 10px;
  text-align: center;
  transition: all 0.25s ease;
  border: 1px solid rgba(227, 184, 104, 0.15);
  position: relative;
  overflow: hidden;
}

.dayun-item:hover {
  background: rgba(255, 248, 230, 0.9);
  border-color: rgba(212, 175, 55, 0.3);
  box-shadow: 0 4px 16px rgba(145, 103, 34, 0.08);
  transform: translateY(-2px);
}

.dayun-item.current {
  border-color: rgba(212, 175, 55, 0.4);
  background: linear-gradient(135deg, rgba(255, 248, 228, 0.95), rgba(255, 243, 214, 0.8));
  box-shadow: 0 4px 16px rgba(212, 175, 55, 0.15);
}

.dayun-item.current::after {
  content: '当前';
  position: absolute;
  top: 0;
  right: 0;
  background: linear-gradient(135deg, #e2af4f, #f3c86f);
  color: #5a3f17;
  font-size: 10px;
  padding: 2px 8px;
  font-weight: 700;
  border-bottom-left-radius: 8px;
}

.dayun-item.level-positive { border-color: rgba(82, 196, 26, 0.2); }
.dayun-item.level-cautious { border-color: rgba(250, 140, 22, 0.2); }

.dayun-score-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
  margin-bottom: 6px;
}

.dayun-score-icon .score-star { font-size: 13px; line-height: 1; }
.dayun-score-icon .score-num { font-size: 12px; font-weight: 700; margin-left: 3px; }

.dayun-score-icon.score-positive .score-star,
.dayun-score-icon.score-positive .score-num { color: #52c41a; }
.dayun-score-icon.score-neutral .score-star,
.dayun-score-icon.score-neutral .score-num { color: #d4af37; }
.dayun-score-icon.score-cautious .score-star,
.dayun-score-icon.score-cautious .score-num { color: #fa8c16; }

.dayun-age {
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 8px;
  font-weight: 500;
}

.dayun-pillar {
  display: flex;
  justify-content: center;
  gap: 4px;
  margin-bottom: 6px;
}

.dayun-pillar .gan,
.dayun-pillar .zhi {
  font-size: 22px;
  font-weight: 800;
  color: #5e4318;
}

.dayun-shishen {
  font-size: 12px;
  color: #9a6612;
  margin-bottom: 6px;
  font-weight: 600;
}

.dayun-luck {
  display: inline-block;
  padding: 3px 12px;
  border-radius: 16px;
  font-size: 11px;
  font-weight: 700;
  margin-bottom: 6px;
}

.dayun-luck.吉,
.dayun-luck.positive {
  background: rgba(82, 196, 26, 0.12);
  color: #3a8a0a;
  border: 1px solid rgba(82, 196, 26, 0.25);
}
.dayun-luck.凶,
.dayun-luck.cautious {
  background: rgba(245, 108, 108, 0.12);
  color: #c0392b;
  border: 1px solid rgba(245, 108, 108, 0.25);
}
.dayun-luck.平,
.dayun-luck.neutral {
  background: rgba(212, 175, 55, 0.1);
  color: #8a6012;
  border: 1px solid rgba(212, 175, 55, 0.2);
}

.dayun-desc {
  font-size: 11px;
  color: var(--text-secondary);
  line-height: 1.5;
  margin-bottom: 6px;
}

.dayun-nayin {
  font-size: 10px;
  color: #9a6612;
  opacity: 0.8;
}

/* ===== 流年区域 ===== */
.liunian-section {
  margin-top: 20px;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid rgba(227, 184, 104, 0.2);
}

.liunian-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 10px;
}

.liunian-item {
  background: rgba(255, 250, 241, 0.6);
  border-radius: 12px;
  padding: 14px 8px;
  text-align: center;
  transition: all 0.2s ease;
  border: 1px solid rgba(227, 184, 104, 0.15);
  position: relative;
}

.liunian-item:hover {
  background: rgba(255, 248, 230, 0.9);
  border-color: rgba(212, 175, 55, 0.3);
  transform: translateY(-2px);
}

.liunian-item.current {
  border-color: rgba(212, 175, 55, 0.4);
  background: linear-gradient(135deg, rgba(255, 248, 228, 0.95), rgba(255, 243, 214, 0.8));
  box-shadow: 0 4px 12px rgba(212, 175, 55, 0.12);
}

.liunian-item.current::before {
  content: '今年';
  position: absolute;
  top: -9px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #e2af4f, #f3c86f);
  color: #5a3f17;
  font-size: 10px;
  padding: 1px 8px;
  border-radius: 8px;
  font-weight: 700;
}

.liunian-year {
  font-size: 13px;
  color: #3a2a10;
  margin-bottom: 8px;
  font-weight: 700;
}

.liunian-pillar {
  display: flex;
  justify-content: center;
  gap: 3px;
  margin-bottom: 8px;
}

.liunian-pillar .gan,
.liunian-pillar .zhi {
  font-size: 20px;
  font-weight: 800;
  color: #5e4318;
}

.liunian-wuxing {
  display: flex;
  justify-content: center;
  gap: 4px;
  margin-bottom: 6px;
}

.liunian-wuxing .badge {
  font-size: 10px;
  padding: 1px 6px;
  border-radius: 5px;
  font-weight: 700;
}

.liunian-wuxing .badge.金 { background: rgba(218, 165, 32, 0.15); color: #b8860b; }
.liunian-wuxing .badge.木 { background: rgba(34, 139, 34, 0.15); color: #2e7d32; }
.liunian-wuxing .badge.水 { background: rgba(30, 144, 255, 0.15); color: #1565c0; }
.liunian-wuxing .badge.火 { background: rgba(220, 20, 60, 0.15); color: #c62828; }
.liunian-wuxing .badge.土 { background: rgba(180, 120, 60, 0.15); color: #8d5524; }

.liunian-nayin {
  font-size: 10px;
  color: #9a6612;
  opacity: 0.8;
}

/* ===== 流年运势分析 ===== */
.yearly-fortune-section {
  margin-top: 20px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(227, 184, 104, 0.2);
  border-radius: 16px;
  padding: 20px;
}

.year-selector {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 20px;
  padding: 16px;
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  border: 1px solid rgba(227, 184, 104, 0.15);
}

.year-selector__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.year-selector__meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.selector-label {
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 600;
}

.selector-hint {
  color: var(--text-tertiary);
  font-size: 12px;
}

.year-slider { width: 100%; }

.selected-year {
  color: #8a5c16;
  font-size: 18px;
  font-weight: 700;
  min-width: 64px;
  text-align: right;
}

/* 流年分析结果 */
.yearly-result { animation: fadeInUp 0.4s ease; }

.yearly-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 16px 20px;
  background: rgba(255, 250, 241, 0.9);
  border-radius: 14px;
  border: 1px solid rgba(227, 184, 104, 0.2);
}

.year-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.year-number {
  font-size: 32px;
  font-weight: 800;
  color: #8a5c16;
}

.year-ganzhi {
  font-size: 18px;
  color: var(--text-primary);
  font-weight: 600;
}

.year-nayin {
  font-size: 13px;
  color: var(--text-secondary);
}

.score-display {
  display: flex;
  align-items: center;
  gap: 12px;
}

.score-circle {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 2.5px solid;
  background: rgba(255, 250, 241, 0.8);
}

.score-circle.excellent { border-color: #52c41a; }
.score-circle.good { border-color: #e6a817; }
.score-circle.average { border-color: #fa8c16; }
.score-circle.poor { border-color: #909399; }

.score-value {
  font-size: 24px;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1;
}

.score-label {
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 2px;
}

.rating-badge {
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 15px;
  font-weight: 700;
  color: #fff;
}

.rating-badge.excellent { background: linear-gradient(135deg, #52c41a, #73d13d); }
.rating-badge.good { background: linear-gradient(135deg, #e2af4f, #f3c86f); color: #5a3f17; }
.rating-badge.average { background: linear-gradient(135deg, #fa8c16, #ffa940); }
.rating-badge.poor { background: linear-gradient(135deg, #909399, #b0b3b8); }

/* 分析卡片 */
.yearly-analysis {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.analysis-card {
  background: rgba(255, 250, 241, 0.7);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(227, 184, 104, 0.15);
}

.analysis-card h4 {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.analysis-card p {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 13px;
  margin: 0;
}

.analysis-card.overall {
  background: linear-gradient(135deg, rgba(255, 248, 228, 0.8), rgba(255, 243, 214, 0.6));
  border-color: rgba(212, 175, 55, 0.2);
}

.analysis-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.analysis-card.advice {
  background: rgba(240, 255, 240, 0.7);
  border-color: rgba(82, 196, 26, 0.15);
}

/* 幸运信息 */
.lucky-info {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  padding: 16px;
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  border: 1px solid rgba(227, 184, 104, 0.2);
}

.lucky-section h5 {
  color: #8a5c16;
  font-size: 13px;
  margin-bottom: 10px;
  font-weight: 700;
}

.lucky-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.lucky-tag {
  padding: 4px 10px;
  border-radius: 14px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid transparent;
  transition: transform 0.2s ease;
}

.lucky-tag:hover { transform: translateY(-1px); }

.lucky-tag.good { background: rgba(82, 196, 26, 0.1); color: #3a8a0a; border-color: rgba(82, 196, 26, 0.2); }
.lucky-tag.bad { background: rgba(245, 108, 108, 0.1); color: #c0392b; border-color: rgba(245, 108, 108, 0.2); }
.lucky-tag.color { background: rgba(212, 175, 55, 0.1); color: #8a5c16; border-color: rgba(212, 175, 55, 0.2); }
.lucky-tag.number { background: rgba(64, 158, 255, 0.1); color: #1565c0; border-color: rgba(64, 158, 255, 0.2); }

/* ===== 大运运势分析 ===== */
.dayun-fortune-section {
  margin-top: 20px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(227, 184, 104, 0.2);
  border-radius: 16px;
  padding: 20px;
}

.dayun-selector {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  padding: 12px 16px;
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  border: 1px solid rgba(227, 184, 104, 0.15);
  flex-wrap: wrap;
}

.selector-label {
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
}

.dayun-analysis-result { animation: fadeInUp 0.4s ease; }

.dayun-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 16px 20px;
  background: rgba(255, 250, 241, 0.9);
  border-radius: 14px;
  border: 1px solid rgba(227, 184, 104, 0.2);
}

.dayun-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dayun-name {
  font-size: 28px;
  font-weight: 800;
  color: #5e4318;
}

.dayun-shishen {
  font-size: 13px;
  color: #9a6612;
  font-weight: 600;
}

.dayun-age {
  font-size: 13px;
  color: var(--text-secondary);
}

.dayun-level-badge {
  padding: 6px 18px;
  border-radius: 20px;
  font-size: 15px;
  font-weight: 700;
}

.dayun-level-badge.excellent { background: rgba(82, 196, 26, 0.12); color: #3a8a0a; border: 1px solid rgba(82, 196, 26, 0.25); }
.dayun-level-badge.good { background: rgba(212, 175, 55, 0.12); color: #8a5c16; border: 1px solid rgba(212, 175, 55, 0.25); }
.dayun-level-badge.average { background: rgba(250, 140, 22, 0.12); color: #a05c00; border: 1px solid rgba(250, 140, 22, 0.25); }
.dayun-level-badge.poor { background: rgba(144, 147, 153, 0.12); color: #606266; border: 1px solid rgba(144, 147, 153, 0.25); }

.dayun-scores {
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 16px;
  border: 1px solid rgba(227, 184, 104, 0.15);
}

.score-item {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.score-item:last-child { margin-bottom: 0; }

.score-name {
  width: 44px;
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 600;
  flex-shrink: 0;
}

.score-progress { flex: 1; }

.score-value {
  font-size: 13px;
  font-weight: 700;
  color: #8a5c16;
  width: 28px;
  text-align: right;
}

.dayun-analysis-text {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 16px;
}

.text-card {
  background: rgba(255, 250, 241, 0.7);
  border-radius: 10px;
  padding: 14px;
  border: 1px solid rgba(227, 184, 104, 0.12);
}

.text-card p {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 13px;
  margin: 0;
}

.key-suggestions {
  background: rgba(255, 248, 228, 0.8);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 12px;
  padding: 16px;
}

.key-suggestions h4 {
  color: #8a5c16;
  margin-bottom: 12px;
  font-size: 14px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 6px;
}

.key-suggestions ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.key-suggestions li {
  color: var(--text-secondary);
  padding: 6px 0 6px 18px;
  position: relative;
  font-size: 13px;
  line-height: 1.6;
}

.key-suggestions li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 13px;
  width: 5px;
  height: 5px;
  background: rgba(212, 175, 55, 0.6);
  border-radius: 50%;
}

/* ===== 运势K线图 ===== */
.fortune-chart-section {
  margin-top: 20px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(227, 184, 104, 0.2);
  border-radius: 16px;
  padding: 20px;
}

.chart-result { animation: fadeInUp 0.4s ease; }

.chart-summary {
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 20px;
  border: 1px solid rgba(227, 184, 104, 0.15);
}

.chart-summary p {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
  margin-bottom: 10px;
}

.best-period {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid rgba(227, 184, 104, 0.15);
}

.best-label {
  color: var(--text-secondary);
  font-size: 13px;
}

.best-value {
  color: #3a8a0a;
  font-weight: 700;
  font-size: 14px;
}

.chart-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.chart-dayun {
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  padding: 16px;
  border: 1px solid rgba(227, 184, 104, 0.15);
}

.chart-dayun-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.dayun-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}

.dayun-score {
  padding: 3px 10px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 700;
}

.dayun-score.excellent { background: rgba(82, 196, 26, 0.1); color: #3a8a0a; }
.dayun-score.good { background: rgba(212, 175, 55, 0.1); color: #8a5c16; }
.dayun-score.average { background: rgba(250, 140, 22, 0.1); color: #a05c00; }
.dayun-score.poor { background: rgba(144, 147, 153, 0.1); color: #606266; }

.dayun-trend {
  margin-left: auto;
  color: var(--text-secondary);
  font-size: 13px;
}

.chart-years {
  display: flex;
  align-items: flex-end;
  gap: 3px;
  height: 120px;
  padding: 8px 0;
  margin-bottom: 8px;
}

.chart-year-bar {
  flex: 1;
  min-width: 18px;
  background: linear-gradient(to top, rgba(212, 175, 55, 0.5), rgba(212, 175, 55, 0.2));
  border-radius: 3px 3px 0 0;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  padding-bottom: 4px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.chart-year-bar:hover {
  background: linear-gradient(to top, rgba(212, 175, 55, 0.7), rgba(212, 175, 55, 0.4));
}

.chart-year-bar.current {
  background: linear-gradient(to top, #e2af4f, #f3c86f);
  box-shadow: 0 0 8px rgba(212, 175, 55, 0.4);
}

.year-label {
  position: absolute;
  bottom: -18px;
  font-size: 9px;
  color: var(--text-tertiary);
  white-space: nowrap;
}

.year-score {
  font-size: 9px;
  color: #5e4318;
  font-weight: 700;
}

.chart-legend {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 22px;
  border-top: 1px solid rgba(227, 184, 104, 0.15);
  font-size: 13px;
  color: var(--text-secondary);
}

/* ===== 分析按钮区域 ===== */
.analysis-actions {
  text-align: center;
  padding: 24px 16px;
}

.analysis-desc {
  color: var(--text-secondary);
  margin-bottom: 16px;
  font-size: 13px;
  line-height: 1.6;
}

/* ===== 动画 ===== */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ===== 响应式 ===== */
@media (max-width: 768px) {
  .dayun-timeline {
    display: flex;
    overflow-x: auto;
    gap: 10px;
    padding: 4px 2px 16px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
  }

  .dayun-timeline::-webkit-scrollbar { height: 3px; }
  .dayun-timeline::-webkit-scrollbar-thumb {
    background: rgba(212, 175, 55, 0.3);
    border-radius: 2px;
  }

  .dayun-item {
    flex: 0 0 160px;
    scroll-snap-align: start;
  }

  .liunian-grid {
    display: flex;
    overflow-x: auto;
    gap: 8px;
    padding: 4px 2px 16px;
    scroll-snap-type: x mandatory;
  }

  .liunian-grid::-webkit-scrollbar { height: 3px; }
  .liunian-grid::-webkit-scrollbar-thumb {
    background: rgba(212, 175, 55, 0.3);
    border-radius: 2px;
  }

  .liunian-item {
    flex: 0 0 90px;
    scroll-snap-align: start;
    padding: 10px 6px;
  }

  .year-selector__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .selected-year {
    text-align: left;
    min-width: auto;
  }

  .lucky-info {
    grid-template-columns: repeat(2, 1fr);
  }

  .analysis-grid {
    grid-template-columns: 1fr;
  }

  .yearly-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }
}
</style>