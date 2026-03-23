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

      <!-- 占卜结果 -->
      <div v-if="result" class="result-section">
        <div class="result-card">
          <div class="result-header">
            <h2>占卜结果</h2>
            <span v-if="result.is_first" class="first-free-badge">首次免费</span>
          </div>

          <!-- 核心结论摘要 -->
          <div v-if="summaryHighlights.length" class="summary-section">
            <div class="summary-card">
              <h4 class="summary-title">核心结论</h4>
              <div class="summary-list">
                <div v-for="(item, index) in summaryHighlights" :key="index" class="summary-item">
                  <span class="summary-icon">{{ item.icon }}</span>
                  <div class="summary-content">
                    <span class="summary-label">{{ item.label }}</span>
                    <span class="summary-value">{{ item.value }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-if="resultContextItems.length" class="result-context" aria-label="起卦上下文信息">
            <span v-for="item in resultContextItems" :key="item.label" class="context-chip" @click="showTermExplanation(item.label)">
              <span class="context-chip__label">{{ item.label }}</span>
              <span class="context-chip__value">{{ item.value }}</span>
              <el-icon class="context-chip__help"><QuestionFilled /></el-icon>
            </span>
          </div>

          <!-- 问题 -->
          <div class="question-box">

            <span class="label">占问：</span>
            <span class="question-text">{{ result.question }}</span>
          </div>

          <!-- 卦象展示 -->
          <div class="gua-display">
            <!-- 卦象SVG展示 -->
            <div class="gua-card gua-card--svg">
              <div class="gua-symbol-large">
                <svg v-if="guaSvgPath" viewBox="0 0 200 240" class="gua-svg">
                  <!-- 六爻展示，从上到下（六爻到初爻） -->
                  <g v-for="(yao, index) in result.yao_result" :key="index" :transform="`translate(0, ${20 + index * 35})`">
                    <!-- 阳爻：一条横线 -->
                    <line v-if="isYangYao(yao)"
                      x1="40"
                      y1="0"
                      x2="160"
                      y2="0"
                      :stroke="isMovingYao(yao) ? '#E74C3C' : '#2C3E50'"
                      stroke-width="8"
                      stroke-linecap="round"
                    />
                    <!-- 阴爻：两条横线 -->
                    <g v-else>
                      <line
                        x1="40"
                        y1="0"
                        x2="95"
                        y2="0"
                        :stroke="isMovingYao(yao) ? '#E74C3C' : '#2C3E50'"
                        stroke-width="8"
                        stroke-linecap="round"
                      />
                      <line
                        x1="105"
                        y1="0"
                        x2="160"
                        y2="0"
                        :stroke="isMovingYao(yao) ? '#E74C3C' : '#2C3E50'"
                        stroke-width="8"
                        stroke-linecap="round"
                      />
                    </g>
                    <!-- 动爻标识 -->
                    <circle v-if="isMovingYao(yao)" cx="100" cy="17" r="6" fill="#E74C3C" />
                    <!-- 爻位标签 -->
                    <text x="20" y="5" font-size="12" fill="#7F8C8D" text-anchor="end">{{ 6 - index }}</text>
                    <!-- 爻名标签 -->
                    <text x="180" y="5" font-size="12" fill="#7F8C8D" text-anchor="start">{{ getYaoName(yao) }}</text>
                  </g>
                </svg>
                <div v-else class="gua-symbol-large">{{ getGuaSymbol(result.gua.code) }}</div>
              </div>
              <div class="gua-details">
                <div class="gua-name-main">{{ result.gua.name }}</div>
                <div class="gua-code-tag">第{{ result.gua.code }}卦</div>
                <div class="gua-legend">
                  <span class="gua-legend-item">
                    <span class="gua-legend-symbol gua-legend-symbol--yang"></span>
                    <span class="gua-legend-text">阳爻</span>
                  </span>
                  <span class="gua-legend-item">
                    <span class="gua-legend-symbol gua-legend-symbol--yin"></span>
                    <span class="gua-legend-text">阴爻</span>
                  </span>
                  <span class="gua-legend-item">
                    <span class="gua-legend-symbol gua-legend-symbol--moving"></span>
                    <span class="gua-legend-text">动爻</span>
                  </span>
                </div>
              </div>
              <div class="gua-deco-line"></div>
            </div>

            <!-- 六爻详细信息 -->
            <div class="yao-wrapper">
              <div class="yao-section-title">
                <span class="yao-section-title__text">六爻详情</span>
                <span class="yao-section-title__line"></span>
              </div>
              <div class="yao-grid">
                <div
                  v-for="(yao, index) in result.yao_result"
                  :key="index"
                  class="yao-cell"
                  :class="{ moving: isMovingYao(yao), yang: isYangYao(yao), yin: !isYangYao(yao) }"
                >
                  <div class="yao-cell__position">{{ 6 - index }}爻</div>
                  <div class="yao-cell__symbol">
                    <span class="yao-symbol-mark">{{ isYangYao(yao) ? '—' : '— —' }}</span>
                    <span v-if="isMovingYao(yao)" class="yao-moving-icon">✦</span>
                  </div>
                  <div class="yao-cell__name">{{ result.yao_names[index] }}</div>
                  <div v-if="result.fushen && result.fushen[index]" class="yao-cell__fushen">
                    {{ result.fushen[index].name }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- AI深度分析 - 产品亮点，放在显眼位置 -->
          <div v-if="result.ai_analysis" class="ai-section ai-section--highlight">
            <div class="ai-section__header">
              <h3 class="ai-section__title">
                <el-icon class="ai-section__icon"><MagicStick /></el-icon>
                AI深度分析
                <span class="ai-section__badge">产品亮点</span>
              </h3>
            </div>
            <div class="ai-content ai-content--highlight">{{ result.ai_analysis.content }}</div>
          </div>

          <!-- AI解卦按钮（历史记录中没有AI分析时显示） -->
          <div v-else-if="result.is_history" class="ai-action-zone ai-action-zone--highlight">
            <div class="ai-action-card">
              <div class="ai-action-content">
                <h3 class="ai-action-title">
                  <el-icon><MagicStick /></el-icon>
                  AI深度解卦
                  <span class="ai-action-badge">产品亮点</span>
                </h3>
                <p class="ai-action-desc">基于AI专业知识库，为您提供更深入、更专业的占卜解读</p>
                <el-button
                  type="primary"
                  size="large"
                  :loading="aiAnalyzing"
                  :disabled="aiAnalyzing"
                  @click="startAiAnalysis"
                  class="ai-action-btn"
                >
                  <el-icon><MagicStick /></el-icon>
                  {{ aiAnalyzing ? 'AI正在深度分析...' : '开始AI解卦' }}
                </el-button>
              </div>
            </div>
          </div>

          <div v-if="structuredResultItems.length || result.line_details.length" class="structured-section">
            <h4>结构化排盘</h4>
            <div v-if="structuredResultItems.length" class="structured-summary">
              <span v-for="item in structuredResultItems" :key="item.label" class="structured-chip">
                <span class="structured-chip__label">{{ item.label }}</span>
                <span class="structured-chip__value">{{ item.value }}</span>
              </span>
            </div>

            <div v-if="result.moving_line_details.length" class="moving-line-list">
              <div v-for="line in result.moving_line_details" :key="`moving-${line.position}`" class="moving-line-card">
                <span class="moving-line-card__title">第{{ line.position }}爻 · {{ line.change_summary }}</span>
                <span v-if="formatMovingLineMeta(line)" class="moving-line-card__meta">{{ formatMovingLineMeta(line) }}</span>
              </div>
            </div>

            <div v-if="result.line_details.length" class="line-detail-list">
              <div v-for="line in result.line_details" :key="line.position" class="line-detail-card">
                <div class="line-detail-card__header">
                  <span class="line-detail-card__title">第{{ line.position }}爻 · {{ line.name }}</span>
                  <div class="line-detail-card__tags">
                    <span v-if="line.is_shi" class="line-tag line-tag--shi">世</span>
                    <span v-if="line.is_ying" class="line-tag line-tag--ying">应</span>
                    <span v-if="line.is_moving" class="line-tag line-tag--moving">动</span>
                  </div>
                </div>
                <div class="line-detail-card__meta">
                  <span>{{ line.yin_yang }} · {{ line.change_summary }}</span>
                  <span v-if="line.liuqin">六亲：{{ line.liuqin }}</span>
                  <span v-if="line.liushen">六神：{{ line.liushen }}</span>
                  <span v-if="line.di_zhi">纳甲：{{ line.di_zhi }}</span>
                  <span v-if="line.fushen?.name">伏神：{{ line.fushen.name }}<template v-if="line.fushen.ganzhi"> · {{ line.fushen.ganzhi }}</template></span>
                </div>
              </div>
            </div>

          </div>

          <!-- 卦辞 -->
          <div class="gua-ci-section">
            <h4>卦辞</h4>
            <p class="gua-ci">{{ result.gua.gua_ci }}</p>
          </div>

          <!-- 解读 -->
          <div class="interpretation-section">
            <h4>卦象解读</h4>
            <pre class="interpretation-text">{{ result.interpretation }}</pre>
          </div>

          <!-- 消耗信息 -->
          <div class="points-info">
            <span v-if="result.points_cost > 0">消耗 {{ result.points_cost }} 积分</span>
            <span v-else>本次免费</span>
            <span v-if="shouldShowRemainingPoints">剩余 {{ result.remaining_points }} 积分</span>
          </div>



          <!-- 操作按钮 -->
          <ResultNextSteps
            description="这次结果已经自动存进历史，下一步可以回看记录、补积分，或切到别的服务换个视角继续判断。"
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
                    <el-icon><Share /></el-icon> 分享摘要
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


          <div class="options-section">
            <el-checkbox v-model="form.useAi" label="使用AI深度分析（更准确、更详细）" />
          </div>

          <!-- 定价信息 -->
          <div class="pricing-info" v-if="pricingLoading || pricing || pricingError" data-liuyao-field="pricing">

            <div v-if="pricingLoading" class="pricing-loading">
              <span>正在同步当前占卜价格...</span>
            </div>
            <template v-else-if="pricing">
              <div class="pricing-info-content">
                <div class="pricing-info-main">
                  <div v-if="pricing.is_first_free" class="pricing-free">
                    <span><el-icon><Present /></el-icon> 首次占卜免费</span>
                  </div>
                  <div v-else-if="pricing.is_vip_free" class="pricing-vip">
                    <span><el-icon><Trophy /></el-icon> VIP免费</span>
                  </div>
                  <div v-else class="pricing-normal">
                    <span>本次消耗 {{ form.version === 'professional' ? pricing.professional_cost : pricing.basic_cost }} 积分</span>
                  </div>
                  <p v-if="pricing.reason" class="pricing-reason">{{ pricing.reason }}</p>
                </div>
                <div class="pricing-info-details">
                  <!-- 版本标签页切换 -->
                  <div class="version-tabs">
                    <button
                      type="button"
                      class="version-tab"
                      :class="{ 'version-tab--active': form.version === 'basic' }"
                      @click="form.version = 'basic'"
                    >
                      <span class="version-tab__icon">📊</span>
                      <span class="version-tab__label">简单版</span>
                      <span v-if="pricing" class="version-tab__price">{{ pricing.basic_cost }}积分</span>
                    </button>
                    <button
                      type="button"
                      class="version-tab"
                      :class="{ 'version-tab--active': form.version === 'professional' }"
                      @click="form.version = 'professional'"
                    >
                      <span class="version-tab__icon">✨</span>
                      <span class="version-tab__label">专业版</span>
                      <span v-if="pricing" class="version-tab__price">{{ pricing.professional_cost }}积分</span>
                      <span class="version-tab__badge">推荐</span>
                    </button>
                  </div>

                  <!-- 版本详情内容（标签页内容） -->
                  <div class="version-content">
                    <!-- 简单版内容 -->
                    <div v-show="form.version === 'basic'" class="version-detail version-detail--basic">
                      <div class="version-detail__header">
                        <span class="version-badge version-badge--basic">简单版</span>
                      </div>
                      <ul class="version-features">
                        <li><el-icon><Check /></el-icon> 完整的六爻卦象排盘</li>
                        <li><el-icon><Check /></el-icon> 基础的卦辞解析</li>
                        <li><el-icon><Check /></el-icon> 卦象SVG可视化展示</li>
                        <li><el-icon><Close /></el-icon> <span class="feature-disabled">AI深度综合分析</span></li>
                        <li><el-icon><Close /></el-icon> <span class="feature-disabled">专业的指导建议</span></li>
                        <li><el-icon><Check /></el-icon> 保存占卜历史记录</li>
                      </ul>
                    </div>

                    <!-- 专业版内容 -->
                    <div v-show="form.version === 'professional'" class="version-detail version-detail--professional">
                      <div class="version-detail__header">
                        <span class="version-badge version-badge--premium">专业版</span>
                        <span class="version-tag">推荐</span>
                      </div>
                      <ul class="version-features">
                        <li><el-icon><Check /></el-icon> <strong>完整的六爻卦象排盘</strong></li>
                        <li><el-icon><Check /></el-icon> <strong>详细的卦辞解析与针对性解读</strong></li>
                        <li><el-icon><Check /></el-icon> <strong>卦象SVG可视化展示</strong></li>
                        <li><el-icon><Check /></el-icon> <strong>AI深度综合分析报告</strong></li>
                        <li><el-icon><Check /></el-icon> <strong>多维度的运势分析</strong></li>
                        <li><el-icon><Check /></el-icon> <strong>专业的指导建议</strong></li>
                        <li><el-icon><Check /></el-icon> <strong>保存占卜历史记录</strong></li>
                      </ul>
                      <div class="ai-highlight">
                        <el-icon><MagicStick /></el-icon>
                        <span>AI基于六爻专业知识库，为您提供更深入、更专业的占卜解读</span>
                      </div>
                    </div>
                  </div>

                  <p class="pricing-info-title">{{ form.version === 'professional' ? '专业版您将获得：' : '简单版您将获得：' }}</p>
                  <ul class="pricing-info-list">
                    <li><el-icon><Check /></el-icon> 完整的六爻卦象排盘（本卦、变卦、互卦等）</li>
                    <li v-if="form.version === 'professional'"><el-icon><Check /></el-icon> <strong>AI深度综合分析报告</strong></li>
                    <li><el-icon><Check /></el-icon> {{ form.version === 'professional' ? '详细的' : '基础的' }}卦辞解析与针对性解读</li>
                    <li v-if="form.version === 'professional'"><el-icon><Check /></el-icon> <strong>多维度的运势分析</strong></li>
                    <li v-if="form.version === 'professional'"><el-icon><Check /></el-icon> <strong>专业的指导建议</strong></li>
                    <li><el-icon><Check /></el-icon> 永久保存在您的历史记录中，随时查看</li>
                  </ul>

<p class="pricing-info-guarantee"><el-icon><Lock /></el-icon> 失败保障：若占卜失败或未生成结果，将自动退还积分。</p>
                </div>
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
import { Delete, MagicStick, Present, Trophy, ArrowDown, ArrowUp, Share, QuestionFilled, Close, Check, Lock, ArrowRight, Edit, Refresh } from '@element-plus/icons-vue'
import ResultNextSteps from '../../components/ResultNextSteps.vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import ShareCard from '../../components/ShareCard.vue'
import WisdomText from '../../components/WisdomText.vue'

import { useLiuyao } from './useLiuyao'

const {
  // 常量
  methodOptions, questionTypeOptions, quickQuestions, yaoLineLabels, yaoResultLineLabels,
  yaoValueOptions, tianGanOptions, diZhiOptions, yaoNameMap, terminologyMap,

  // 状态
  form, isLoading, isSubmitting, result, pricing, pricingLoading, pricingError,
  history, historyLoading, historyLoaded, historyError, submitErrors,
  showHistory, showAdvancedSettings, historyListRef, currentBeijingTimestamp,
  aiAnalyzing,

  // 计算属性
  currentBeijingTime, currentMethodDescription, currentMethodAudience,
  submitSummaryText, submitButtonText, shouldShowRemainingPoints,
  savedStatusText, historyTriggerText, resultContextItems, structuredResultItems,
  summaryHighlights, guaSvgPath, shouldShowLiuyaoRechargeAction,
  liuyaoResultHighlights, liuyaoResultActions, liuyaoRelatedRecommendations,
  liuyaoShareSummary, liuyaoShareTags,

  // 方法
  createDefaultForm, clearSubmitErrors, focusLiuyaoField, handleSubmitIssue,
  buildSubmitIssues, showTermExplanation, openHistoryDialog,
  restoreHistoryTriggerFocus, focusHistoryDialogPrimaryAction,
  formatDateTime, reportUiError,
  isMovingYao, isYangYao, getYaoName, getYaoMark, getGuaSymbol,
  parseYaoResult, normalizeYaoCode, safeJsonParse, normalizeAiAnalysis,
  normalizeFushen, getYinYangLabel, describeLineChange,
  formatMovingLineMeta, normalizeLineDetail, normalizeResult,
  loadPricing, loadHistory, buildDivinationPayload,
  submitDivination, resetForm, startAiAnalysis,
  loadHistoryDetail, deleteRecord, formatDate,
} = useLiuyao()
</script>

<style scoped>
@import './style.css';
</style>
