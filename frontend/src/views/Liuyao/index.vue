<template>
  <div class="liuyao-page">
    <div class="container">
      <!-- 页面标题 -->
      <PageHeroHeader
        title="六爻占卜"
        subtitle="传统周易六爻，以三枚铜钱摇出六爻，为您解答心中疑惑、指引行事方向。"
        :icon="MagicStick"
        fallback="/"
      />

      <!-- 占卜中：等待态覆盖层 -->
      <div v-if="isLoading" class="divination-loading">
        <div class="divination-loading__card">
          <!-- 动态爻线动画 -->
          <div class="divination-loading__yao-anim">
            <div v-for="i in 6" :key="i" class="dl-yao" :class="`dl-yao--${i}`">
              <div class="dl-yao__bar"></div>
            </div>
          </div>
          <!-- 分步文案 -->
          <div class="divination-loading__text">
            <p class="divination-loading__step" :key="loadingStep">
              <span v-if="loadingStep === 1">正在起卦...</span>
              <span v-else-if="loadingStep === 2">铜钱落定，卦象成形...</span>
              <span v-else-if="loadingStep === 3">AI 正在解读卦象...</span>
              <span v-else>正在占卜...</span>
            </p>
            <p class="divination-loading__hint">心诚则灵，稍候片刻</p>
          </div>
        </div>
      </div>

      <!-- 占卜结果 -->
      <div v-if="result" class="result-section">
        <div class="result-card">

          <!-- 问题标题 -->
          <div class="result-question-bar">
            <span v-if="result.is_first" class="result-badge result-badge--free">🎁 首次免费</span>
            <span v-else class="result-badge result-badge--done">✦ 解卦完成</span>
            <p class="result-question-text">{{ result.question }}</p>
          </div>

          <!-- 卦象主展示区：大图 + 卦名 + 卦辞 -->
          <div class="gua-hero">
            <!-- 卦象爻线图 -->
            <div class="gua-lines-wrap">
              <div class="gua-lines-inner">
                <div
                  v-for="(yao, index) in [...result.yao_result].reverse()"
                  :key="index"
                  class="gua-yao"
                  :class="{ 'gua-yao--moving': isMovingYao(yao) }"
                >
                  <!-- 阳爻：实线 -->
                  <div v-if="isYangYao(yao)" class="gua-yao__bar gua-yao__bar--yang"></div>
                  <!-- 阴爻：断线 -->
                  <div v-else class="gua-yao__bar gua-yao__bar--yin">
                    <div class="gua-yao__half"></div>
                    <div class="gua-yao__gap"></div>
                    <div class="gua-yao__half"></div>
                  </div>
                  <!-- 动爻标记 -->
                  <span v-if="isMovingYao(yao)" class="gua-yao__dot"></span>
                </div>
              </div>
            </div>

            <!-- 卦名 + 卦辞 -->
            <div class="gua-meta">
              <div class="gua-meta__names">
                <h2 class="gua-meta__main-name">{{ result.gua.name }}</h2>
                <div v-if="result.bian_gua?.name" class="gua-meta__change">
                  <span class="gua-meta__arrow">→</span>
                  <span class="gua-meta__bian-name">{{ result.bian_gua.name }}</span>
                </div>
              </div>
              <p v-if="result.gua.gua_ci" class="gua-meta__ci">"{{ result.gua.gua_ci }}"</p>
              <p v-if="result.interpretation" class="gua-meta__interp">{{ result.interpretation }}</p>
            </div>
          </div>

          <!-- AI 深度解卦 -->
          <div v-if="result.ai_analysis" class="ai-section">
            <div class="ai-section__label">
              <span class="ai-section__dot"></span>
              AI 解卦
            </div>
            <div class="ai-section__content">{{ result.ai_analysis.content }}</div>
          </div>

          <!-- 历史记录中无AI分析时的触发入口 -->
          <div v-else-if="result.is_history" class="ai-unlock">
            <div class="ai-unlock__left">
              <p class="ai-unlock__title">还没有 AI 解卦</p>
              <p class="ai-unlock__desc">让 AI 结合你的问题，给出具体分析和建议</p>
            </div>
            <el-button
              type="primary"
              :loading="aiAnalyzing"
              :disabled="aiAnalyzing"
              @click="startAiAnalysis"
              class="ai-unlock__btn"
            >
              {{ aiAnalyzing ? '解卦中...' : '立即解卦' }}
            </el-button>
          </div>

          <!-- 积分信息 -->
          <div class="result-points-bar">
            <span v-if="result.points_cost > 0">消耗 {{ result.points_cost }} 积分</span>
            <span v-else>本次免费</span>
            <span v-if="shouldShowRemainingPoints" class="result-points-bar__remain">剩余 {{ result.remaining_points }} 积分</span>
          </div>

          <!-- 操作按钮 -->
          <ResultNextSteps
            description="结果已自动保存，可以回看历史、换个角度继续问。"
            :highlights="liuyaoResultHighlights"
            :actions="liuyaoResultActions"
            :recommendations="liuyaoRelatedRecommendations"
          >
            <template #actions>
              <ShareCard
                title="六爻占卜"
                :summary="liuyaoShareSummary"
                :tags="liuyaoShareTags"
                :sharePath="`/liuyao?id=${result.id}`"
              >
                <template #trigger>
                  <el-button class="result-next-steps__action-btn">
                    <el-icon><Share /></el-icon> 分享
                  </el-button>
                </template>
              </ShareCard>
            </template>
          </ResultNextSteps>
          <WisdomText />

        </div>
      </div>

      <!-- 占卜表单 -->
      <div v-else class="form-section">
        <div class="form-card">
          <h2>心诚则灵</h2>
          <p class="form-tip">请静心思考您要询问的问题，问题越具体，占卜结果越准确</p>

          <div class="form-group" data-liuyao-field="question">
            <label>您的问题 <span class="required">*</span></label>
            <el-input
              v-model="form.question"
              type="textarea"
              :rows="4"
              placeholder="例如：
我最近的考试能通过吗？
这份工作适合我吗？
我和TA的感情发展如何？
这个项目能成功吗？"
              maxlength="100"
              show-word-limit
            />
            <!-- 快捷问题 -->
            <div class="quick-questions">
              <span class="quick-questions-label">常见问题：</span>
              <button
                v-for="q in quickQuestions"
                :key="q"
                class="quick-question-btn"
                @click="form.question = q"
              >
                {{ q }}
              </button>
            </div>
          </div>

          <div class="form-group">
            <label>起卦方式</label>
            <el-radio-group v-model="form.method" class="method-group premium-segment premium-segment--compact">
              <el-radio-button v-for="option in methodOptions" :key="option.value" :label="option.value" :class="{ 'is-recommend': option.recommend }">
                <span class="method-label">{{ option.label }}</span>
                <span v-if="option.recommend" class="method-tag method-tag--recommend">新手推荐</span>
                <span v-if="option.highlight" class="method-tag method-tag--highlight">{{ option.highlight }}</span>
              </el-radio-button>
            </el-radio-group>
            <p class="form-helper">{{ currentMethodDescription }}</p>
            <p v-if="currentMethodAudience" class="method-audience">
              <el-icon><ArrowRight /></el-icon>
              <span>{{ currentMethodAudience }}</span>
            </p>
          </div>

          <div v-if="form.method === 'time'" class="helper-card">
            <p class="helper-card__title">时间起卦</p>
            <p class="helper-card__desc">将按当前北京时间 {{ currentBeijingTime }} 自动起卦，无需额外输入数字或摇卦结果。</p>
          </div>

          <div v-else-if="form.method === 'number'" class="helper-card" data-liuyao-field="number-method">
            <p class="helper-card__title">数字起卦</p>
            <p class="helper-card__desc">请输入 1-999 的数字。单数字可只填第一个，双数字会按上下卦分别计算。</p>
            <div class="input-grid input-grid--double">
              <div class="input-grid__item">
                <label>第一个数字</label>
                <el-input-number v-model="form.numbers[0]" :min="1" :max="999" :step="1" :precision="0" controls-position="right" class="full-width" />
              </div>
              <div class="input-grid__item">
                <label>第二个数字（可选）</label>
                <el-input-number v-model="form.numbers[1]" :min="1" :max="999" :step="1" :precision="0" controls-position="right" class="full-width" />
              </div>
            </div>
          </div>

          <div v-else class="helper-card" data-liuyao-field="manual-method">
            <p class="helper-card__title">手动摇卦</p>
            <p class="helper-card__desc">请按从初爻到上爻的顺序，依次录入 6 次摇卦结果。</p>
            <div class="manual-grid">
              <div v-for="(label, index) in yaoLineLabels" :key="label" class="manual-grid__item">
                <label>{{ label }}</label>
                <el-select v-model="form.yaoResults[index]" placeholder="请选择爻象" class="full-width">
                  <el-option v-for="option in yaoValueOptions" :key="option.value" :label="option.label" :value="option.value" />
                </el-select>
              </div>
            </div>
          </div>

          <div class="advanced-card">
            <button
              type="button"
              class="advanced-toggle"
              :aria-expanded="showAdvancedSettings"
              aria-controls="liuyao-advanced-grid"
              @click="showAdvancedSettings = !showAdvancedSettings"
            >
              <div class="advanced-card__header">
                <h3>进阶设置</h3>
                <p>问事类型、性别与日辰信息可按需补充；第一次起卦也可以先跳过。</p>
              </div>
              <span class="advanced-toggle__action">
                {{ showAdvancedSettings ? '收起' : '展开' }}
                <el-icon>
                  <ArrowUp v-if="showAdvancedSettings" />
                  <ArrowDown v-else />
                </el-icon>
              </span>
            </button>
            <div v-show="showAdvancedSettings" id="liuyao-advanced-grid" class="advanced-grid">
              <div class="form-group">
                <label>问事类型</label>
                <el-select v-model="form.questionType" class="full-width">
                  <el-option v-for="option in questionTypeOptions" :key="option" :label="option" :value="option" />
                </el-select>
              </div>
              <div class="form-group">
                <label>求测者性别</label>
                <el-radio-group v-model="form.gender">
                  <el-radio-button label="男" />
                  <el-radio-button label="女" />
                </el-radio-group>
              </div>
              <div class="form-group">
                <label>日辰天干（可选）</label>
                <el-select v-model="form.riGan" clearable placeholder="默认自动推算" class="full-width">
                  <el-option v-for="option in tianGanOptions" :key="option" :label="option" :value="option" />
                </el-select>
              </div>
              <div class="form-group">
                <label>日辰地支（可选）</label>
                <el-select v-model="form.riZhi" clearable placeholder="默认自动推算" class="full-width">
                  <el-option v-for="option in diZhiOptions" :key="option" :label="option" :value="option" />
                </el-select>
              </div>
            </div>
          </div>




          <!-- 定价信息 -->
          <div class="pricing-info" v-if="pricingLoading || pricing || pricingError" data-liuyao-field="pricing">
            <div v-if="pricingLoading" class="pricing-loading">
              <span class="pricing-loading__dot"></span>
              <span>正在同步价格...</span>
            </div>
            <template v-else-if="pricing">
              <div class="pricing-card">
                <!-- 价格头部 -->
                <div class="pricing-card__header">
                  <div class="pricing-card__badge">
                    <el-icon><MagicStick /></el-icon>
                    <span>AI 深度解卦</span>
                  </div>
                  <div class="pricing-card__cost">
                    <template v-if="pricing.is_first_free">
                      <span class="pricing-card__free">🎁 首次免费</span>
                    </template>
                    <template v-else-if="pricing.is_vip_free">
                      <span class="pricing-card__free"><el-icon><Trophy /></el-icon> VIP 专属免费</span>
                    </template>
                    <template v-else>
                      <span class="pricing-card__points">{{ pricing.professional_cost || pricing.cost }}</span>
                      <span class="pricing-card__unit">积分</span>
                    </template>
                  </div>
                </div>
                <!-- 功能列表 -->
                <ul class="pricing-card__features">
                  <li><span class="feature-icon">✦</span> 六爻卦象完整排盘与可视化</li>
                  <li><span class="feature-icon">✦</span> 卦名含义与卦辞解读</li>
                  <li><span class="feature-icon">✦</span> AI 针对您的问题深度分析</li>
                  <li><span class="feature-icon">✦</span> 实用建议与行动指引</li>
                  <li><span class="feature-icon">✦</span> 永久保存，随时回看</li>
                </ul>
                <p v-if="pricing.reason" class="pricing-card__reason">{{ pricing.reason }}</p>
                <p class="pricing-card__guarantee"><el-icon><Lock /></el-icon> 占卜失败自动退还积分</p>
              </div>
            </template>
            <div v-else class="pricing-error">
              <p class="pricing-reason pricing-reason--error">{{ pricingError }}</p>
              <el-button type="primary" link @click="loadPricing">重新获取价格</el-button>
            </div>
          </div>

          <section v-if="submitErrors.length" class="submit-summary-card" role="alert" aria-live="assertive">
            <div class="submit-summary-card__header">
              <div>
                <strong>提交前还差这几步</strong>
                <p>{{ submitSummaryText }}</p>
              </div>
              <el-icon><MagicStick /></el-icon>
            </div>
            <div class="submit-summary-card__actions">
              <button
                v-for="issue in submitErrors"
                :key="issue.key"
                type="button"
                class="submit-summary-card__action"
                @click="handleSubmitIssue(issue)"
              >
                <span>{{ issue.actionLabel }}</span>
                <small>{{ issue.message }}</small>
              </button>
            </div>
          </section>

          <el-button
            type="primary"
            size="large"
            class="btn-submit"
            @click="submitDivination"
            :disabled="isLoading"
            :loading="isLoading"
          >
            <template #icon v-if="!isLoading">
              <el-icon class="btn-icon"><MagicStick /></el-icon>
            </template>
            {{ isLoading ? '正在占卜...' : submitButtonText }}
          </el-button>


          <div class="history-link" v-if="historyLoaded || historyLoading || historyError">
            <button type="button" class="history-link__button" @click="openHistoryDialog($event)">{{ historyTriggerText }}</button>
          </div>


        </div>
      </div>

      <!-- 历史记录弹窗 -->
      <el-dialog
        v-model="showHistory"
        title="历史记录"
        width="min(92vw, 560px)"
        append-to-body
        class="history-dialog"
        :close-on-click-modal="true"
        :close-on-press-escape="true"
        @closed="restoreHistoryTriggerFocus"
      >
        <div ref="historyListRef" class="history-list" aria-label="六爻历史记录列表">
          <div v-if="historyLoading" class="history-state" role="status" aria-live="polite">
            <p>历史记录加载中...</p>
            <span>最近的占卜记录会在这里出现。</span>
          </div>
          <div v-else-if="historyError" class="history-state history-state--error" role="alert">
            <p>历史记录暂时没加载出来</p>
            <span>{{ historyError }}</span>
            <el-button type="primary" link @click="loadHistory">重新加载</el-button>
          </div>
          <div v-else-if="historyLoaded && history.length === 0" class="history-state" role="status" aria-live="polite">
            <p>暂时还没有历史记录</p>
            <span>完成一次占卜后，这里会自动保存你的记录。</span>
          </div>
          <template v-else>
            <div v-for="item in history" :key="item.id" class="history-item">
              <button
                type="button"
                class="history-select"
                :title="item.question"
                @click="loadHistoryDetail(item)"
              >
                <div class="history-main">
                  <p class="history-question">{{ item.question }}</p>
                  <p class="history-gua">{{ item.gua?.name || '卦象待定' }} · {{ formatDate(item.created_at) }}</p>
                </div>
              </button>
              <button class="delete-btn" type="button" @click.stop="deleteRecord(item.id)">
                <el-icon><Delete /></el-icon>
                <span class="delete-label">删除</span>
              </button>
            </div>
          </template>
        </div>
      </el-dialog>

    </div>
  </div>
</template>


<script setup>
import { Delete, MagicStick, Trophy, ArrowDown, ArrowUp, Share, Lock, ArrowRight } from '@element-plus/icons-vue'
import ResultNextSteps from '../../components/ResultNextSteps.vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import ShareCard from '../../components/ShareCard.vue'
import WisdomText from '../../components/WisdomText.vue'

import { useLiuyao } from './useLiuyao'

const {
  // 常量
  methodOptions, questionTypeOptions, quickQuestions, yaoLineLabels,
  yaoValueOptions, tianGanOptions, diZhiOptions,

  // 状态
  form, isLoading, result, pricing, pricingLoading, pricingError,
  history, historyLoading, historyLoaded, historyError, submitErrors,
  showHistory, showAdvancedSettings, historyListRef, currentBeijingTimestamp,
  aiAnalyzing, loadingStep,

  // 计算属性
  currentBeijingTime, currentMethodDescription, currentMethodAudience,
  submitSummaryText, submitButtonText, shouldShowRemainingPoints,
  historyTriggerText, shouldShowLiuyaoRechargeAction,
  liuyaoResultHighlights, liuyaoResultActions, liuyaoRelatedRecommendations,
  liuyaoShareSummary, liuyaoShareTags,

  // 方法
  handleSubmitIssue, openHistoryDialog,
  restoreHistoryTriggerFocus,
  isMovingYao, isYangYao,
  loadPricing, loadHistory,
  submitDivination, resetForm, startAiAnalysis,
  loadHistoryDetail, deleteRecord, formatDate,
} = useLiuyao()
</script>

<style scoped>
@import './style.css';
</style>
