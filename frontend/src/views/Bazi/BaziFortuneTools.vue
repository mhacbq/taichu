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
/* =============================================
   大运流年工具组件样式（BaziFortuneTools.vue）
   ============================================= */

/* 分区标题 */
.pane-title {
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

.section-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--border-light), transparent);
  margin: 32px 0;
}

.section-title-with-tip,
.section-title-with-tag {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
}

.help-icon {
  cursor: help;
  color: var(--primary-color);
  opacity: 0.8;
}

/* 大运区域 */
.dayun-section {
  margin-top: 28px;
  background: linear-gradient(180deg, rgba(255, 253, 248, 0.98), rgba(255, 249, 237, 0.95));
  border-radius: 20px;
  padding: 26px;
  border: 1px solid rgba(212, 175, 55, 0.1);
  box-shadow: 0 8px 24px rgba(149, 111, 45, 0.04);
}

.dayun-scoring-tip {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  margin-bottom: 16px;
  background: linear-gradient(90deg, rgba(212, 175, 55, 0.08), rgba(212, 175, 55, 0.04));
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 10px;
  color: #b8860b;
  font-size: 13px;
}

.dayun-scoring-tip .el-icon {
  font-size: 16px;
  color: #D4AF37;
}

.dayun-timeline {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
}

.dayun-item {
  background: rgba(255, 250, 241, 0.5);
  border-radius: 12px;
  padding: 15px;
  text-align: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(212, 175, 55, 0.08);
  position: relative;
  overflow: hidden;
  opacity: 0;
  transform: translateY(20px);
  animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.dayun-item:nth-child(1) { animation-delay: 0.1s; }
.dayun-item:nth-child(2) { animation-delay: 0.15s; }
.dayun-item:nth-child(3) { animation-delay: 0.2s; }
.dayun-item:nth-child(4) { animation-delay: 0.25s; }
.dayun-item:nth-child(5) { animation-delay: 0.3s; }
.dayun-item:nth-child(6) { animation-delay: 0.35s; }
.dayun-item:nth-child(7) { animation-delay: 0.4s; }
.dayun-item:nth-child(8) { animation-delay: 0.45s; }

.dayun-item:hover {
  background: rgba(255, 248, 230, 0.7);
  border-color: rgba(212, 175, 55, 0.15);
  box-shadow: 0 8px 24px rgba(149, 111, 45, 0.08);
}

.dayun-item.current {
  border-color: rgba(212, 175, 55, 0.3);
  background: linear-gradient(135deg, rgba(255, 248, 228, 0.9), rgba(255, 243, 214, 0.7));
  box-shadow: 0 6px 20px rgba(212, 175, 55, 0.12);
  animation: pulse 2s ease-in-out infinite;
}

.dayun-item.current::after {
  content: '当前';
  position: absolute;
  top: 0;
  right: 0;
  background: var(--primary-color);
  color: #000;
  font-size: 10px;
  padding: 2px 8px;
  font-weight: bold;
  border-bottom-left-radius: 8px;
}

.dayun-item.level-positive {
  border-color: rgba(82, 196, 26, 0.2);
}

.dayun-item.level-cautious {
  border-color: rgba(250, 140, 22, 0.2);
}

.dayun-score-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
  margin-bottom: 8px;
}

.dayun-score-icon .score-star {
  font-size: 14px;
  line-height: 1;
}

.dayun-score-icon .score-num {
  font-size: 13px;
  font-weight: 700;
  line-height: 1;
  margin-left: 4px;
}

.dayun-score-icon.score-positive .score-star,
.dayun-score-icon.score-positive .score-num { color: #52c41a; }
.dayun-score-icon.score-neutral .score-star,
.dayun-score-icon.score-neutral .score-num { color: #d4af37; }
.dayun-score-icon.score-cautious .score-star,
.dayun-score-icon.score-cautious .score-num { color: #fa8c16; }

.dayun-age {
  font-size: 14px;
  color: #5a4a38;
  margin-bottom: 10px;
  font-weight: 500;
}

.dayun-pillar {
  display: flex;
  justify-content: center;
  gap: 5px;
  margin-bottom: 8px;
}

.dayun-pillar .gan,
.dayun-pillar .zhi {
  font-size: 26px;
  font-weight: 800;
  color: var(--text-primary);
  text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.dayun-shishen {
  font-size: 13px;
  color: var(--primary-light);
  margin-bottom: 8px;
  font-weight: 600;
}

.dayun-luck {
  display: inline-block;
  padding: 4px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: bold;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.dayun-luck.吉,
.dayun-luck.positive { background: var(--success-gradient); color: #fff; box-shadow: 0 2px 8px rgba(103, 194, 58, 0.4); }
.dayun-luck.凶,
.dayun-luck.cautious { background: var(--danger-gradient); color: #fff; box-shadow: 0 2px 8px rgba(245, 108, 108, 0.4); }
.dayun-luck.平,
.dayun-luck.neutral { background: var(--white-20); color: #3a2a10; }

.dayun-desc {
  font-size: 12px;
  color: #5f5548;
  line-height: 1.5;
  margin-bottom: 10px;
  padding: 0 5px;
}

.dayun-nayin {
  font-size: 11px;
  color: var(--primary-light);
  font-style: italic;
  opacity: 0.9;
}

/* 流年区域 */
.liunian-section {
  margin-top: 36px;
  background: linear-gradient(180deg, rgba(255, 252, 244, 0.98), rgba(255, 248, 232, 0.95));
  border-radius: 22px;
  padding: 28px;
  border: 1px solid rgba(212, 175, 55, 0.06);
  box-shadow: 0 8px 24px rgba(149, 111, 45, 0.04);
}

.liunian-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 15px;
}

.liunian-item {
  background: rgba(255, 250, 241, 0.5);
  border-radius: 14px;
  padding: 18px 12px;
  text-align: center;
  transition: all 0.3s ease;
  border: 1px solid rgba(212, 175, 55, 0.08);
  position: relative;
}

.liunian-item:hover {
  background: rgba(255, 248, 230, 0.7);
  border-color: rgba(212, 175, 55, 0.12);
  transform: scale(1.02);
}

.liunian-item.current {
  border-color: rgba(212, 175, 55, 0.25);
  background: linear-gradient(135deg, rgba(255, 248, 228, 0.9), rgba(255, 243, 214, 0.7));
  box-shadow: 0 4px 16px rgba(212, 175, 55, 0.1);
  z-index: 1;
  animation: pulse 2s ease-in-out infinite;
}

.liunian-item.current::before {
  content: '今年';
  position: absolute;
  top: -10px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--primary-gradient);
  color: #000;
  font-size: 10px;
  padding: 2px 10px;
  border-radius: 10px;
  font-weight: bold;
  box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

.liunian-year {
  font-size: 15px;
  color: #3a2a10;
  margin-bottom: 12px;
  font-weight: 700;
}

.liunian-pillar {
  display: flex;
  justify-content: center;
  gap: 4px;
  margin-bottom: 12px;
}

.liunian-pillar .gan,
.liunian-pillar .zhi {
  font-size: 24px;
  font-weight: 800;
  color: var(--text-primary);
}

.liunian-wuxing {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-bottom: 10px;
}

.liunian-wuxing .badge {
  font-size: 10px;
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: bold;
}

.liunian-wuxing .badge.金 { background: rgba(218, 165, 32, 0.2); color: #e8c56e; }
.liunian-wuxing .badge.木 { background: rgba(34, 139, 34, 0.2); color: #5dba5d; }
.liunian-wuxing .badge.水 { background: rgba(30, 144, 255, 0.2); color: #5aabf0; }
.liunian-wuxing .badge.火 { background: rgba(220, 20, 60, 0.2); color: #f06080; }
.liunian-wuxing .badge.土 { background: rgba(180, 120, 60, 0.2); color: #c8956a; }

.liunian-nayin {
  font-size: 11px;
  color: var(--primary-light);
  opacity: 0.8;
}

/* 流年运势分析 */
.yearly-fortune-section {
  margin-top: 28px;
  background: linear-gradient(135deg, rgba(255, 248, 228, 0.7), rgba(255, 243, 214, 0.4));
  border: 1px solid rgba(212, 175, 55, 0.15);
  border-radius: 22px;
  padding: 28px;
}

.year-selector {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 25px;
  padding: 20px;
  background: rgba(255, 253, 248, 0.9);
  border-radius: 16px;
  border: 1px solid rgba(212, 175, 55, 0.08);
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
  gap: 6px;
  min-width: 0;
}

.selector-label {
  color: var(--text-secondary);
  font-size: 14px;
  white-space: nowrap;
}

.selector-hint {
  color: var(--text-tertiary);
  font-size: 12px;
  line-height: 1.6;
}

.year-slider {
  width: 100%;
}

.selected-year {
  color: var(--primary-color);
  font-size: 18px;
  font-weight: bold;
  min-width: 70px;
  text-align: right;
}

/* 流年分析结果 */
.yearly-result {
  animation: fadeInUp 0.5s ease;
}

.yearly-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding: 20px;
  background: rgba(255, 250, 241, 0.9);
  border-radius: 18px;
  border: 1px solid rgba(212, 175, 55, 0.08);
}

.year-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.year-number {
  font-size: 36px;
  font-weight: bold;
  color: var(--primary-color);
}

.year-ganzhi {
  font-size: 20px;
  color: var(--text-primary);
}

.year-nayin {
  font-size: 14px;
  color: var(--primary-light);
}

.score-display {
  display: flex;
  align-items: center;
  gap: 15px;
}

.score-circle {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 3px solid;
  background: rgba(255, 250, 241, 0.6);
}

.score-circle.excellent { border-color: var(--success-color); }
.score-circle.good { border-color: var(--warning-color); }
.score-circle.average { border-color: var(--danger-color); }
.score-circle.poor { border-color: var(--info-color); }

.score-value {
  font-size: 28px;
  font-weight: bold;
  color: var(--text-primary);
}

.score-label {
  font-size: 12px;
  color: #5f5548;
}

.rating-badge {
  padding: 8px 20px;
  border-radius: 20px;
  font-size: 18px;
  font-weight: bold;
  color: var(--text-primary);
}

.rating-badge.excellent { background: var(--success-gradient); }
.rating-badge.good { background: var(--warning-gradient); }
.rating-badge.average { background: var(--danger-gradient); }
.rating-badge.poor { background: linear-gradient(135deg, var(--info-color), #a6a9ad); }

/* 分析卡片 */
.yearly-analysis {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.analysis-card {
  background: rgba(255, 253, 248, 0.9);
  border-radius: 16px;
  padding: 20px;
  border: 1px solid rgba(212, 175, 55, 0.06);
}

.analysis-card h4 {
  color: var(--text-primary);
  font-size: 16px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.analysis-card p {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 14px;
}

.analysis-card.overall {
  background: linear-gradient(135deg, rgba(255, 248, 228, 0.7), rgba(255, 243, 214, 0.5));
  border: 1px solid rgba(212, 175, 55, 0.12);
}

.analysis-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;
}

.analysis-card.advice {
  background: linear-gradient(135deg, rgba(240, 255, 240, 0.6), rgba(230, 250, 230, 0.4));
  border: 1px solid rgba(34, 139, 34, 0.1);
}

/* 幸运信息 */
.lucky-info {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-top: 15px;
  padding: 20px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(248, 249, 250, 0.98));
  border-radius: 20px;
  border: 1px solid rgba(184, 134, 11, 0.2);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(184, 134, 11, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.8);
}

.lucky-section h5 {
  color: var(--text-primary);
  font-size: 15px;
  margin-bottom: 12px;
  font-weight: 600;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.lucky-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.lucky-tag {
  padding: 6px 14px;
  border-radius: 18px;
  font-size: 13px;
  font-weight: 500;
  border: 1px solid transparent;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.lucky-tag:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.lucky-tag.good { background: linear-gradient(135deg, rgba(103, 194, 58, 0.4), rgba(103, 194, 58, 0.2)); color: #67c23a; border-color: rgba(103, 194, 58, 0.3); }
.lucky-tag.bad { background: linear-gradient(135deg, rgba(245, 108, 108, 0.4), rgba(245, 108, 108, 0.2)); color: #f56c6c; border-color: rgba(245, 108, 108, 0.3); }
.lucky-tag.color { background: linear-gradient(135deg, rgba(255, 215, 0, 0.4), rgba(255, 215, 0, 0.2)); color: var(--star-color); border-color: rgba(255, 215, 0, 0.3); }
.lucky-tag.number { background: linear-gradient(135deg, rgba(64, 158, 255, 0.4), rgba(64, 158, 255, 0.2)); color: #409eff; border-color: rgba(64, 158, 255, 0.3); }

/* 大运运势分析 */
.dayun-fortune-section {
  margin-top: 28px;
  background: linear-gradient(135deg, rgba(240, 247, 255, 0.7), rgba(230, 240, 255, 0.4));
  border: 1px solid rgba(64, 158, 255, 0.12);
  border-radius: 22px;
  padding: 28px;
}

.dayun-selector {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 25px;
  padding: 15px 20px;
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  flex-wrap: wrap;
}

.dayun-analysis-result {
  animation: fadeInUp 0.5s ease;
}

.dayun-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding: 20px;
  background: rgba(255, 250, 241, 0.9);
  border-radius: 16px;
}

.dayun-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.dayun-name {
  font-size: 32px;
  font-weight: bold;
  color: #409eff;
}

.dayun-level-badge {
  padding: 10px 25px;
  border-radius: 25px;
  font-size: 20px;
  font-weight: bold;
  color: var(--text-primary);
}

.dayun-level-badge.excellent { background: var(--success-gradient); }
.dayun-level-badge.good { background: var(--warning-gradient); }
.dayun-level-badge.average { background: var(--danger-gradient); }
.dayun-level-badge.poor { background: linear-gradient(135deg, var(--info-color), #a6a9ad); }

.dayun-scores {
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
}

.score-item {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}

.score-item:last-child {
  margin-bottom: 0;
}

.score-name {
  width: 50px;
  color: #5a4a38;
  font-size: 14px;
}

.score-progress {
  flex: 1;
}

.dayun-analysis-text {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 25px;
}

.text-card {
  background: rgba(255, 250, 241, 0.5);
  border-radius: 10px;
  padding: 15px;
}

.text-card p {
  color: #4a3c2e;
  line-height: 1.8;
  font-size: 14px;
}

.key-suggestions {
  background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 193, 7, 0.05));
  border: 1px solid rgba(255, 215, 0, 0.3);
  border-radius: 12px;
  padding: 20px;
}

.key-suggestions h4 {
  color: var(--star-color);
  margin-bottom: 15px;
  font-size: 16px;
}

.key-suggestions ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.key-suggestions li {
  color: #4a3c2e;
  padding: 8px 0;
  padding-left: 20px;
  position: relative;
  font-size: 14px;
}

.key-suggestions li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 14px;
  width: 6px;
  height: 6px;
  background: var(--primary-color);
  border-radius: 50%;
}

/* 运势K线图 */
.fortune-chart-section {
  margin-top: 28px;
  background: linear-gradient(135deg, rgba(240, 255, 240, 0.7), rgba(230, 250, 230, 0.4));
  border: 1px solid rgba(103, 194, 58, 0.1);
  border-radius: 22px;
  padding: 28px;
}

.chart-result {
  animation: fadeInUp 0.5s ease;
}

.chart-summary {
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
}

.chart-summary p {
  color: #4a3c2e;
  margin-bottom: 10px;
}

.best-period {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-top: 15px;
  border-top: 1px solid rgba(212, 175, 55, 0.08);
}

.best-label {
  color: #5f5548;
}

.best-value {
  color: #67c23a;
  font-weight: bold;
  font-size: 16px;
}

.chart-container {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.chart-dayun {
  background: rgba(255, 250, 241, 0.8);
  border-radius: 12px;
  padding: 20px;
}

.chart-dayun-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}

.dayun-title {
  font-size: 18px;
  font-weight: bold;
  color: var(--text-primary);
}

.dayun-score {
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 14px;
  font-weight: bold;
}

.dayun-score.excellent { background: var(--success-light); color: var(--success-color); }
.dayun-score.good { background: var(--warning-light); color: var(--warning-color); }
.dayun-score.average { background: var(--danger-light); color: var(--danger-color); }
.dayun-score.poor { background: rgba(144, 147, 153, 0.2); color: var(--info-color); }

.dayun-trend {
  margin-left: auto;
  color: #5f5548;
  font-size: 14px;
}

.chart-years {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  height: 150px;
  padding: 10px 0;
  margin-bottom: 10px;
}

.chart-year-bar {
  flex: 1;
  min-width: 20px;
  background: var(--success-gradient);
  border-radius: 4px 4px 0 0;
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  padding-bottom: 5px;
  transition: all 0.3s ease;
  cursor: pointer;
}

.chart-year-bar:hover {
  opacity: 0.8;
  transform: scaleX(1.1);
}

.chart-year-bar.current {
  background: linear-gradient(to top, var(--star-color), #ffec8b);
  box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
}

.year-label {
  position: absolute;
  bottom: -20px;
  font-size: 10px;
  color: #5f5548;
  white-space: nowrap;
}

.year-score {
  font-size: 10px;
  color: rgba(0, 0, 0, 0.7);
  font-weight: bold;
}

.chart-legend {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 25px;
  border-top: 1px solid rgba(212, 175, 55, 0.08);
  font-size: 14px;
  color: #5f5548;
}

/* 分析按钮区域 */
.analysis-actions {
  text-align: center;
  padding: 30px;
}

.analysis-desc {
  color: #6b5a3e;
  margin-bottom: 20px;
  font-size: 14px;
}

/* 动画 */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

/* 响应式 */
@media (max-width: 768px) {
  .dayun-timeline {
    display: flex;
    overflow-x: auto;
    gap: 15px;
    padding: 10px 5px 20px;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
  }

  .dayun-timeline::-webkit-scrollbar {
    height: 4px;
  }

  .dayun-timeline::-webkit-scrollbar-thumb {
    background: var(--primary-light-30);
    border-radius: 2px;
  }

  .dayun-item {
    flex: 0 0 220px;
    scroll-snap-align: start;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    gap: 10px;
    padding: 16px;
  }

  .dayun-pillar {
    justify-content: flex-start;
  }

  .dayun-pillar .gan, .dayun-pillar .zhi {
    font-size: 20px;
  }

  .liunian-grid {
    display: flex;
    overflow-x: auto;
    gap: 12px;
    padding: 10px 5px 20px;
    scroll-snap-type: x mandatory;
  }

  .liunian-grid::-webkit-scrollbar {
    height: 4px;
  }

  .liunian-grid::-webkit-scrollbar-thumb {
    background: var(--primary-light-30);
    border-radius: 2px;
  }

  .liunian-item {
    flex: 0 0 100px;
    scroll-snap-align: start;
    padding: 12px;
  }

  .liunian-year {
    font-size: 12px;
    margin-bottom: 5px;
  }

  .liunian-pillar .gan, .liunian-pillar .zhi {
    font-size: 18px;
  }

  .year-selector__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .selected-year {
    text-align: left;
    min-width: auto;
  }

  .selector-hint {
    font-size: 11px;
  }

  .lucky-info {
    grid-template-columns: repeat(2, 1fr);
  }

  .analysis-grid {
    grid-template-columns: 1fr;
  }
}
</style>