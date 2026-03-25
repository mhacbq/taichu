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
