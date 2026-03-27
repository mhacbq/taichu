<script setup lang="ts">
import {
  UserFilled, Briefcase, Money, Aim, StarFilled, Calendar, TrendCharts,
  Lightning, InfoFilled, Check, MagicStick, Loading
} from '@element-plus/icons-vue'

const props = defineProps({
  result: { type: Object, default: null },
  aiAnalysisResult: { type: Object, default: null },
  aiAnalyzing: { type: Boolean, default: false },
  aiLoadingHint: { type: String, default: '' },
  canStartAiAnalysis: { type: Boolean, default: false },
  aiActionText: { type: String, default: '' },
  aiAnalysisCost: { type: Number, default: 0 },
  aiNeedsAccountRecovery: { type: Boolean, default: false },
  aiRecoveryText: { type: String, default: '' },
})

const emit = defineEmits(['startAiAnalysis', 'clearAiResult', 'loadPoints'])
</script>

<template>
  <!-- 性格内观 -->
  <div class="tab-pane-content">
    <div class="pane-title">
      <el-icon class="title-icon"><UserFilled /></el-icon>
      <span class="title-text">性格内观</span>
    </div>

    <!-- 专业解读卡片 -->
    <div class="professional-reading" v-if="result?.fullInterpretation">
      <div class="section-subtitle-wrapper">
        <span class="section-badge">专业版</span>
      </div>

      <!-- 日主信息卡片 -->
      <div class="day-master-detail" v-if="result.fullInterpretation.basic">
        <div class="dm-header">
          <div class="dm-symbol">{{ result.fullInterpretation.basic.day_master_symbol }}</div>
          <div class="dm-title">
            <h4>{{ result.fullInterpretation.basic.day_master }}日主 · {{ result.fullInterpretation.basic.day_master_nature }}</h4>
            <p class="dm-traits">
              <span v-for="(trait, idx) in result.fullInterpretation.basic.traits" :key="idx" class="trait-tag">{{ trait }}</span>
            </p>
          </div>
        </div>
        <div class="dm-content">
          <div class="dm-section"><h5>核心优势</h5><p>{{ result.fullInterpretation.basic.strengths }}</p></div>
          <div class="dm-section"><h5>需要注意</h5><p>{{ result.fullInterpretation.basic.weaknesses }}</p></div>
        </div>
      </div>

      <!-- 喜用神分析 -->
      <div class="yongshen-section" v-if="result.fullInterpretation.yongshen">
        <div class="ys-header">
          <el-icon class="ys-icon"><StarFilled /></el-icon>
          <div class="ys-info">
            <h4>喜用神：{{ result.fullInterpretation.yongshen.shen }}、{{ result.fullInterpretation.yongshen.xi }}</h4>
            <span class="ys-type">{{ result.fullInterpretation.yongshen.type }}格</span>
          </div>
        </div>
        <p class="ys-desc">{{ result.fullInterpretation.yongshen.desc }}</p>
      </div>

      <!-- 解读卡片网格 -->
      <div class="reading-cards-grid">
        <div class="reading-card card-hover" v-if="result.fullInterpretation.personality">
          <div class="rc-header"><el-icon class="rc-icon"><UserFilled /></el-icon><h4>性格详解</h4></div>
          <p class="rc-content">{{ result.fullInterpretation.personality }}</p>
        </div>
        <div class="reading-card card-hover" v-if="result.fullInterpretation.career">
          <div class="rc-header"><el-icon class="rc-icon"><Briefcase /></el-icon><h4>事业财运</h4></div>
          <p class="rc-content">{{ result.fullInterpretation.career }}</p>
        </div>
        <div class="reading-card card-hover" v-if="result.fullInterpretation.wealth">
          <div class="rc-header"><el-icon class="rc-icon"><Money /></el-icon><h4>财富分析</h4></div>
          <p class="rc-content">{{ result.fullInterpretation.wealth }}</p>
        </div>
        <div class="reading-card card-hover" v-if="result.fullInterpretation.relationship">
          <div class="rc-header"><el-icon class="rc-icon"><UserFilled /></el-icon><h4>感情婚姻</h4></div>
          <p class="rc-content">{{ result.fullInterpretation.relationship }}</p>
        </div>
        <div class="reading-card card-hover" v-if="result.fullInterpretation.health">
          <div class="rc-header"><el-icon class="rc-icon"><Aim /></el-icon><h4>健康提醒</h4></div>
          <p class="rc-content">{{ result.fullInterpretation.health }}</p>
        </div>
        <div class="reading-card advice-card card-hover" v-if="result.fullInterpretation.advice">
          <div class="rc-header"><el-icon class="rc-icon"><StarFilled /></el-icon><h4>开运建议</h4></div>
          <p class="rc-content">{{ result.fullInterpretation.advice }}</p>
        </div>

        <!-- 盲派铁口直断 -->
        <div class="reading-card tieko-card card-hover" v-if="result.tiekoDingyu?.length > 0">
          <div class="rc-header tieko-header">
            <el-icon class="rc-icon tieko-icon"><Lightning /></el-icon>
            <div class="tieko-title-group">
              <h4>盲派铁口直断</h4>
              <div class="tieko-match-info">
                <span class="match-count">匹配{{ result.tiekoMatchCount || 0 }}项</span>
                <span class="match-level" :class="result.tiekoMatchLevel">
                  {{ result.tiekoMatchLevel === 'high' ? '高准确度' : result.tiekoMatchLevel === 'medium' ? '中等准确度' : '较低准确度' }}
                </span>
                <span class="match-accuracy">{{ result.tiekoAccuracy || 0 }}%置信度</span>
              </div>
            </div>
          </div>
          <div class="tieko-dingyu-list">
            <div v-for="(item, index) in result.tiekoDingyu" :key="index" class="tieko-item">
              <div class="tieko-item-tags">
                <span v-for="(tag, tagIdx) in item.tags || []" :key="tagIdx" class="tieko-tag" :class="tag">{{ tag }}</span>
              </div>
              <p class="tieko-item-content">{{ item.content }}</p>
              <div class="tieko-item-score">
                <el-rate v-model="item.score" disabled show-score text-color="#D4AF37"></el-rate>
              </div>
            </div>
          </div>
          <div class="tieko-hint">
            <el-icon><InfoFilled /></el-icon>
            <span>铁口直断基于盲派命理理论，条件匹配越多，定语准确度越高。仅供参考，命运掌握在自己手中。</span>
          </div>
        </div>

        <!-- 10年大运周期 -->
        <div class="reading-card card-hover fortune-card" v-if="result.fullInterpretation?.fortune">
          <div class="rc-header"><el-icon class="rc-icon"><Calendar /></el-icon><h4>10年大运周期</h4></div>
          <div class="fortune-timeline">
            <div v-for="(period, index) in result.fullInterpretation.fortune.periods" :key="index" class="fortune-period">
              <div class="period-header">
                <span class="period-years">{{ period.years }}</span>
                <span class="period-status" :class="period.status">{{ period.statusText }}</span>
              </div>
              <p class="period-desc">{{ period.description }}</p>
            </div>
          </div>
        </div>

        <!-- 当前流年重点 -->
        <div class="reading-card card-hover fortune-card" v-if="result.fullInterpretation?.fortune">
          <div class="rc-header"><el-icon class="rc-icon"><TrendCharts /></el-icon><h4>当前流年重点</h4></div>
          <div class="current-fortune">
            <div class="fortune-year">{{ result.fullInterpretation.fortune.currentYear }}年运势</div>
            <div class="fortune-highlights">
              <div v-for="(highlight, index) in result.fullInterpretation.fortune.highlights" :key="index" class="highlight-item">
                <span class="highlight-type">{{ highlight.type }}</span>
                <span class="highlight-desc">{{ highlight.description }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bazi-analysis">
      <h3>详细命理分析</h3>
      <div class="analysis-content">{{ result?.analysis }}</div>
    </div>
  </div>

  <!-- AI 智能解盘 -->
  <div class="ai-section-wrapper" v-if="result?.bazi">
    <div class="section-divider"></div>
    <div class="pane-title">
      <el-icon class="title-icon"><MagicStick /></el-icon>
      <span class="title-text">AI 智能解盘</span>
      <span class="title-desc">结合四柱大运，深度解读命局走势</span>
    </div>

    <!-- 已有结果 -->
    <div v-if="aiAnalysisResult && !aiAnalyzing" class="ai-result-panel">
      <div v-if="aiAnalysisResult.summary" class="ai-block ai-block--summary">
        <p class="ai-summary-text">{{ aiAnalysisResult.summary }}</p>
      </div>
      <div v-if="aiAnalysisResult.riyuan_analysis" class="ai-block">
        <h4 class="ai-block-title">日主分析</h4><p>{{ aiAnalysisResult.riyuan_analysis }}</p>
      </div>
      <div v-if="aiAnalysisResult.personality" class="ai-block">
        <h4 class="ai-block-title">性格特质</h4><p>{{ aiAnalysisResult.personality }}</p>
      </div>
      <div v-if="aiAnalysisResult.career_wealth" class="ai-block">
        <h4 class="ai-block-title">事业财运</h4><p>{{ aiAnalysisResult.career_wealth }}</p>
      </div>
      <div v-if="aiAnalysisResult.relationship" class="ai-block">
        <h4 class="ai-block-title">感情婚姻</h4><p>{{ aiAnalysisResult.relationship }}</p>
      </div>
      <div v-if="aiAnalysisResult.health" class="ai-block">
        <h4 class="ai-block-title">健康提醒</h4><p>{{ aiAnalysisResult.health }}</p>
      </div>
      <div v-if="aiAnalysisResult.dayun_advice" class="ai-block ai-block--highlight">
        <h4 class="ai-block-title">大运流年</h4><p>{{ aiAnalysisResult.dayun_advice }}</p>
      </div>
      <div v-if="aiAnalysisResult.suggestion" class="ai-block ai-block--suggestion">
        <h4 class="ai-block-title">综合建议</h4><p>{{ aiAnalysisResult.suggestion }}</p>
      </div>
      <div class="ai-result-actions">
        <el-button size="small" @click="emit('clearAiResult')">重新解盘</el-button>
      </div>
    </div>

    <!-- 正在分析中 -->
    <div v-else-if="aiAnalyzing" class="ai-analyzing-state">
      <el-icon class="is-loading ai-loading-icon"><Loading /></el-icon>
      <p class="ai-analyzing-text">{{ aiLoadingHint }}</p>
      <p class="ai-analyzing-hint">通常需要 10-20 秒</p>
    </div>

    <!-- 未触发 -->
    <div v-else class="ai-entry-panel">
      <div class="ai-entry-features">
        <div class="ai-entry-feature"><el-icon><Check /></el-icon><span>日主强弱与喜忌分析</span></div>
        <div class="ai-entry-feature"><el-icon><Check /></el-icon><span>事业财运感情健康全面解读</span></div>
        <div class="ai-entry-feature"><el-icon><Check /></el-icon><span>结合大运给出人生阶段建议</span></div>
      </div>
      <div v-if="aiNeedsAccountRecovery" class="ai-recovery-tip">
        <p>{{ aiRecoveryText }}</p>
        <el-button size="small" @click="emit('loadPoints')">重新获取</el-button>
      </div>
      <el-button
        v-else
        type="primary"
        size="large"
        :loading="aiAnalyzing"
        :disabled="!canStartAiAnalysis"
        @click="emit('startAiAnalysis')"
        class="ai-start-btn"
      >
        <el-icon class="btn-icon"><MagicStick /></el-icon>
        {{ aiActionText }}
        <span class="ai-cost-badge">{{ aiAnalysisCost }} 积分</span>
      </el-button>
    </div>
  </div>
</template>

<style scoped>
/* =============================================
   八字解读组件样式（BaziInterpretation.vue）
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

/* 专业解读区域 */
.professional-reading {
  margin: 28px 0;
  background: linear-gradient(180deg, rgba(255, 253, 248, 0.98), rgba(255, 249, 237, 0.95));
  border: 1px solid rgba(212, 175, 55, 0.12);
  border-radius: 24px;
  padding: 28px;
  box-shadow: 0 12px 32px rgba(149, 111, 45, 0.05);
}

.section-subtitle-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 20px;
  margin-top: 10px;
}

.section-badge {
  background: var(--primary-gradient);
  color: var(--text-primary);
  padding: 12px 24px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 500;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
}

/* 日主详情卡片 */
.day-master-detail {
  background: linear-gradient(135deg, rgba(255, 253, 248, 0.98), rgba(255, 249, 237, 0.95));
  border-radius: 20px;
  padding: 26px;
  margin-bottom: 24px;
  border: 1px solid rgba(212, 175, 55, 0.1);
  box-shadow: 0 8px 24px rgba(149, 111, 45, 0.04);
}

.dm-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 20px;
  padding-bottom: 18px;
  border-bottom: 1px solid rgba(212, 175, 55, 0.1);
}

.dm-symbol {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(245, 196, 103, 0.08));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  border: 2px solid rgba(212, 175, 55, 0.2);
  color: #6b4a12;
}

.dm-title h4 {
  color: #3a2a10;
  font-size: 19px;
  margin-bottom: 10px;
  font-weight: 700;
}

.dm-traits {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.trait-tag {
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(245, 196, 103, 0.06));
  color: #6b4a12;
  padding: 8px 18px;
  border-radius: 20px;
  font-size: 13px;
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  border: 1px solid rgba(212, 175, 55, 0.08);
  font-weight: 600;
}

.dm-content {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.dm-section h5 {
  color: var(--text-tertiary);
  font-size: 14px;
  margin-bottom: 8px;
}

.dm-section p {
  color: var(--text-secondary);
  line-height: 1.7;
  font-size: 14px;
}

/* 喜用神区域 */
.yongshen-section {
  background: linear-gradient(135deg, rgba(255, 248, 228, 0.8), rgba(255, 243, 214, 0.5));
  border: 1px solid rgba(212, 175, 55, 0.15);
  border-radius: 18px;
  padding: 22px 24px;
  margin-bottom: 24px;
}

.ys-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 12px;
}

.ys-icon {
  font-size: 26px;
  color: #d4af37;
}

.ys-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.ys-info h4 {
  color: #6b4a12;
  font-size: 17px;
  font-weight: 700;
}

.ys-type {
  background: rgba(212, 175, 55, 0.15);
  color: #9a6612;
  padding: 4px 12px;
  border-radius: 10px;
  font-size: 12px;
  min-height: 24px;
  display: inline-flex;
  align-items: center;
  font-weight: 600;
}

.ys-desc {
  color: #5f5548;
  font-size: 14px;
  line-height: 1.75;
  padding-left: 40px;
}

/* 解读卡片网格 */
.reading-cards-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.reading-card {
  background: var(--bg-primary);
  border-radius: 14px;
  padding: 20px;
  border: 1px solid var(--border-light);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--shadow-sm);
  opacity: 0;
  transform: translateX(-20px);
  animation: slideInRight 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.reading-card:nth-child(1) { animation-delay: 0.2s; }
.reading-card:nth-child(2) { animation-delay: 0.3s; }
.reading-card:nth-child(3) { animation-delay: 0.4s; }
.reading-card:nth-child(4) { animation-delay: 0.5s; }
.reading-card:nth-child(5) { animation-delay: 0.6s; }
.reading-card:nth-child(6) { animation-delay: 0.7s; }

.reading-card:hover {
  border-color: rgba(212, 175, 55, 0.25);
  box-shadow: var(--shadow-md);
  transform: translateY(-4px);
}

.reading-card.advice-card {
  grid-column: span 3;
  background: linear-gradient(135deg, rgba(103, 194, 58, 0.08), rgba(133, 206, 97, 0.04));
  border-color: rgba(103, 194, 58, 0.15);
}

.reading-card.tieko-card {
  grid-column: span 3;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.08), rgba(245, 196, 103, 0.04));
  border-color: rgba(212, 175, 55, 0.15);
}

.tieko-header {
  justify-content: space-between;
}

.tieko-icon {
  font-size: 28px;
  color: #D4AF37;
}

.tieko-title-group {
  flex: 1;
}

.tieko-title-group h4 {
  margin: 0;
  font-size: 17px;
  color: #D4AF37;
}

.tieko-match-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 6px;
  flex-wrap: wrap;
}

.match-count {
  font-size: 12px;
  color: var(--text-secondary);
  background: rgba(0, 0, 0, 0.05);
  padding: 3px 8px;
  border-radius: 4px;
}

.match-level {
  font-size: 12px;
  padding: 3px 8px;
  border-radius: 4px;
  font-weight: 500;
}

.match-level.high { background: rgba(103, 194, 58, 0.2); color: #67c23a; }
.match-level.medium { background: rgba(230, 162, 60, 0.2); color: #e6a23c; }
.match-level.low { background: rgba(245, 108, 108, 0.2); color: #f56c6c; }

.match-accuracy {
  font-size: 12px;
  color: #D4AF37;
  font-weight: 600;
}

.tieko-dingyu-list {
  margin-top: 18px;
}

.tieko-item {
  padding: 12px;
  margin-bottom: 10px;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 8px;
  border-left: 3px solid #D4AF37;
}

.tieko-item:last-child {
  margin-bottom: 0;
}

.tieko-item-tags {
  display: flex;
  gap: 6px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.tieko-tag {
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 3px;
  background: rgba(212, 175, 55, 0.15);
  color: #D4AF37;
}

.tieko-item-content {
  color: var(--text-primary);
  font-size: 14px;
  line-height: 1.6;
  margin: 8px 0;
}

.tieko-item-score {
  display: flex;
  align-items: center;
}

.tieko-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 15px;
  padding-top: 12px;
  border-top: 1px solid rgba(212, 175, 55, 0.2);
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.tieko-hint .el-icon {
  color: #D4AF37;
}

.rc-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.rc-icon {
  font-size: 22px;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.12), rgba(245, 196, 103, 0.06));
  color: #9a6612;
}

.rc-header h4 {
  color: #3a2a10;
  font-size: 16px;
  font-weight: 700;
}

.rc-content {
  color: #5f5548;
  font-size: 14px;
  line-height: 1.8;
}

/* 10年大运周期 */
.fortune-card {
  grid-column: span 3;
}

.fortune-timeline {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.fortune-period {
  padding: 12px;
  border-radius: 10px;
  background: rgba(255, 250, 241, 0.6);
  border: 1px solid rgba(212, 175, 55, 0.08);
}

.period-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 6px;
}

.period-years {
  font-size: 14px;
  font-weight: 700;
  color: var(--text-primary);
}

.period-status {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 6px;
  font-weight: 600;
}

.period-desc {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
  margin: 0;
}

.current-fortune {
  padding: 16px;
  background: rgba(255, 250, 241, 0.6);
  border-radius: 12px;
}

.fortune-year {
  font-size: 16px;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: 12px;
}

.fortune-highlights {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.highlight-item {
  display: flex;
  gap: 10px;
  font-size: 13px;
}

.highlight-type {
  font-weight: 700;
  color: var(--text-primary);
  white-space: nowrap;
}

.highlight-desc {
  color: var(--text-secondary);
  line-height: 1.6;
}

/* 命理分析 */
.bazi-analysis {
  background: var(--bg-secondary) !important;
  border: 1px solid var(--border-light) !important;
  border-radius: 14px !important;
  padding: 28px;
}

.bazi-analysis h3 {
  margin-bottom: 20px;
  color: #3a2a10;
  text-align: center;
  font-weight: 700;
}

.analysis-content {
  color: #5f5548;
  line-height: 1.85;
  white-space: pre-line;
  font-size: 14px;
}

/* AI 智能解盘区域 */
.ai-section-wrapper {
  margin-top: 24px;
}

.ai-result-panel {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.ai-block {
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(212, 160, 62, 0.15);
  border-left: 3px solid rgba(212, 160, 62, 0.4);
  border-radius: 12px;
  padding: 16px 20px;
}

.ai-block--summary {
  background: linear-gradient(135deg, rgba(212, 160, 62, 0.08), rgba(255, 254, 251, 0.95));
  border-color: rgba(212, 160, 62, 0.25);
}

.ai-block--highlight {
  border-left: 3px solid #d4a03e;
}

.ai-block--suggestion {
  background: rgba(255, 248, 235, 0.9);
  border-color: rgba(212, 160, 62, 0.3);
}

.ai-block-title {
  font-size: 14px;
  font-weight: 600;
  color: #5e4318;
  margin: 0 0 8px;
}

.ai-block p {
  font-size: 14px;
  line-height: 1.8;
  color: #6b5a3e;
  margin: 0;
}

.ai-summary-text {
  font-size: 15px;
  line-height: 1.8;
  color: #5e4318;
  font-weight: 500;
  margin: 0;
}

.ai-result-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 4px;
}

/* 正在分析中 */
.ai-analyzing-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 20px;
  gap: 12px;
}

.ai-loading-icon {
  font-size: 36px;
  color: #d4a03e;
}

.ai-analyzing-text {
  font-size: 16px;
  color: #5e4318;
  font-weight: 500;
  margin: 0;
  transition: opacity 0.4s ease;
  min-height: 1.5em;
}

.ai-analyzing-hint {
  font-size: 13px;
  color: #8b7355;
  margin: 0;
}

/* 入口面板 */
.ai-entry-panel {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 24px;
  background: linear-gradient(135deg, rgba(212, 160, 62, 0.06) 0%, rgba(255, 254, 251, 0.95) 100%);
  border: 1.5px solid rgba(212, 160, 62, 0.25);
  border-radius: 18px;
  position: relative;
  overflow: hidden;
}

.ai-entry-panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(212, 160, 62, 0.7), transparent);
}

.ai-entry-panel::after {
  content: '✦';
  position: absolute;
  top: 16px;
  right: 20px;
  font-size: 28px;
  color: rgba(212, 160, 62, 0.12);
  line-height: 1;
  pointer-events: none;
}

.ai-entry-features {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.7);
  border-radius: 12px;
  border: 1px solid rgba(212, 160, 62, 0.12);
}

.ai-entry-feature {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: #5e4318;
  font-weight: 500;
}

.ai-entry-feature .el-icon {
  color: #d4a03e;
  font-size: 16px;
  flex-shrink: 0;
  background: rgba(212, 160, 62, 0.1);
  border-radius: 50%;
  padding: 3px;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.ai-start-btn {
  width: 100%;
  height: 48px;
  font-size: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.ai-cost-badge {
  font-size: 12px;
  background: rgba(255, 255, 255, 0.25);
  padding: 2px 8px;
  border-radius: 10px;
  margin-left: 4px;
}

.ai-recovery-tip {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(255, 248, 235, 0.9);
  border-radius: 10px;
  font-size: 13px;
  color: #8b7355;
}

.ai-recovery-tip p {
  flex: 1;
  margin: 0;
}

/* 动画 */
@keyframes slideInRight {
  0% { opacity: 0; transform: translateX(-20px); }
  100% { opacity: 1; transform: translateX(0); }
}

/* 响应式 */
@media (max-width: 992px) {
  .reading-cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .reading-card.advice-card,
  .reading-card.tieko-card,
  .fortune-card {
    grid-column: span 2;
  }
}

@media (max-width: 768px) {
  .dm-header {
    flex-direction: column;
    text-align: center;
    gap: 15px;
  }

  .dm-content {
    grid-template-columns: 1fr;
  }

  .reading-cards-grid {
    grid-template-columns: 1fr;
  }

  .reading-card.advice-card,
  .reading-card.tieko-card,
  .fortune-card {
    grid-column: span 1;
  }

  .reading-card {
    padding: 15px;
  }

  .professional-reading {
    padding: 20px 15px;
  }
}
</style>