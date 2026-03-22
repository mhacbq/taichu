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

          <!-- 版本选择 -->
          <div class="version-selection" data-liuyao-field="version">
            <div class="version-label">选择占卜版本：</div>
            <div class="version-options">
              <div
                class="version-option"
                :class="{ 'version-option--active': form.version === 'basic' }"
                @click="form.version = 'basic'"
              >
                <div class="version-option__icon">📊</div>
                <div class="version-option__info">
                  <div class="version-option__title">简单版</div>
                  <div class="version-option__desc">基础排盘功能</div>
                </div>
                <div class="version-option__price" v-if="pricing">
                  <span class="price-value">{{ pricing.basic_cost }}积分</span>
                </div>
              </div>
              <div
                class="version-option"
                :class="{ 'version-option--active': form.version === 'professional' }"
                @click="form.version = 'professional'"
              >
                <div class="version-option__icon">✨</div>
                <div class="version-option__info">
                  <div class="version-option__title">专业版</div>
                  <div class="version-option__desc">AI深度解读</div>
                </div>
                <div class="version-option__price" v-if="pricing">
                  <span class="price-value">{{ pricing.professional_cost }}积分</span>
                </div>
                <div class="version-option__badge">推荐</div>
              </div>
            </div>
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
                  <!-- 版本功能对比 -->
                  <div class="version-compare">
                    <div class="compare-card" :class="{ 'compare-card--selected': form.version === 'basic' }">
                      <div class="compare-header">
                        <span class="compare-badge compare-badge--basic">简单版</span>
                      </div>
                      <ul class="compare-features">
                        <li><el-icon><Check /></el-icon> 完整的六爻卦象排盘</li>
                        <li><el-icon><Check /></el-icon> 基础的卦辞解析</li>
                        <li><el-icon><Check /></el-icon> 卦象SVG可视化展示</li>
                        <li><el-icon><Close /></el-icon> <span class="feature-disabled">AI深度综合分析</span></li>
                        <li><el-icon><Close /></el-icon> <span class="feature-disabled">专业的指导建议</span></li>
                        <li><el-icon><Check /></el-icon> 保存占卜历史记录</li>
                      </ul>
                    </div>

                    <div class="compare-card" :class="{ 'compare-card--selected': form.version === 'professional' }">
                      <div class="compare-header">
                        <span class="compare-badge compare-badge--premium">专业版</span>
                        <span class="compare-tag">推荐</span>
                      </div>
                      <ul class="compare-features">
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
import { ref, reactive, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { getLiuyaoPricing, liuyaoDivination, getLiuyaoHistory, deleteLiuyaoRecord, analyzeLiuyaoAi } from '../api'
import { Delete, MagicStick, Present, Trophy, ArrowDown, ArrowUp, Share, QuestionFilled, Close, Check, Lock, ArrowRight, Edit, Refresh } from '@element-plus/icons-vue'
import guaData from '../utils/liuyao.json'

import ResultNextSteps from '../components/ResultNextSteps.vue'
import PageHeroHeader from '../components/PageHeroHeader.vue'
import ShareCard from '../components/ShareCard.vue'
import WisdomText from '../components/WisdomText.vue'
import { trackPageView, trackEvent, trackSubmit, trackError, trackLiuyaoMethodChange, trackLiuyaoSubmitStart, trackLiuyaoSubmitSuccess, trackLiuyaoSubmitFail, trackLiuyaoAiToggle, trackLiuyaoHistoryView, trackLiuyaoHistoryDelete, trackLiuyaoShare, trackLiuyaoResultView, trackLiuyaoPricingView, trackLiuyaoPricingError } from '../utils/tracker'





const methodOptions = [
  { label: '时间起卦', value: 'time', description: '按当前北京时间起卦，适合快速问事。', recommend: true, audience: '新手推荐·快速问事', highlight: '最便捷' },
  { label: '数字起卦', value: 'number', description: '通过数字拆分上下卦，适合已有灵感数字时使用。', audience: '有灵感数字时', highlight: '更有针对性' },
  { label: '手动摇卦', value: 'manual', description: '录入 6 次摇卦结果，满足标准六爻问卦流程。', audience: '传统方式·问卦严谨', highlight: '最传统' },
]

const questionTypeOptions = ['求财', '感情', '事业', '健康', '学业', '出行', '其他']
const quickQuestions = ['我的事业发展如何？', '近期感情运势怎么样？', '这笔投资能成功吗？', '身体健康有什么需要注意吗？', '学业考试能顺利通过吗？']
const yaoLineLabels = ['初爻（下）', '二爻', '三爻', '四爻', '五爻', '上爻（上）']
const yaoResultLineLabels = ['初爻', '二爻', '三爻', '四爻', '五爻', '上爻']
const yaoValueOptions = [

  { label: '老阴（6）', value: 6 },
  { label: '少阳（7）', value: 7 },
  { label: '少阴（8）', value: 8 },
  { label: '老阳（9）', value: 9 },
]
const tianGanOptions = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸']
const diZhiOptions = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']
const yaoNameMap = ['老阴', '少阳', '少阴', '老阳']
const router = useRouter()

const createDefaultForm = () => ({
  question: '',
  useAi: true,
  method: 'time',
  questionType: '其他',
  gender: '男',
  numbers: [null, null],
  yaoResults: [null, null, null, null, null, null],
  riGan: '',
  riZhi: '',
  version: 'basic', // 版本选择：basic（简单版）/ professional（专业版）
})

// 表单数据
const form = reactive(createDefaultForm())

// 状态
const isLoading = ref(false)
const isSubmitting = ref(false)
const result = ref(null)
const pricing = ref(null)
const pricingLoading = ref(true)
const pricingError = ref('')
const history = ref([])
const historyLoading = ref(false)
const historyLoaded = ref(false)
const historyError = ref('')
const submitErrors = ref([])
const showHistory = ref(false)
const showAdvancedSettings = ref(false)
const historyListRef = ref(null)
const currentBeijingTimestamp = ref(Date.now())

// AI分析相关状态
const aiAnalyzing = ref(false)

// 监听起卦方式变化
watch(() => form.method, (newMethod) => {
  trackLiuyaoMethodChange(newMethod)
})

let beijingTimer = null
let historyTriggerEl = null



const currentBeijingTime = computed(() => new Intl.DateTimeFormat('zh-CN', {
  timeZone: 'Asia/Shanghai',
  year: 'numeric',
  month: '2-digit',
  day: '2-digit',
  hour: '2-digit',
  minute: '2-digit',
  second: '2-digit',
  hour12: false,
}).format(new Date(currentBeijingTimestamp.value)))


const currentMethodDescription = computed(() => {
  return methodOptions.find((item) => item.value === form.method)?.description || ''
})

const currentMethodAudience = computed(() => {
  return methodOptions.find((item) => item.value === form.method)?.audience || ''
})

const clearSubmitErrors = () => {
  submitErrors.value = []
}

const focusLiuyaoField = async (selector) => {
  if (!selector) {
    return
  }

  await nextTick()
  const target = document.querySelector(selector)
  if (!(target instanceof HTMLElement)) {
    return
  }

  target.scrollIntoView({ behavior: 'smooth', block: 'center' })
  const focusable = target.querySelector('input, textarea, button, [tabindex]:not([tabindex="-1"])')
  if (focusable instanceof HTMLElement) {
    focusable.focus({ preventScroll: true })
  }
}

const handleSubmitIssue = (issue) => {
  if (issue?.handler) {
    issue.handler()
    return
  }

  if (issue?.route) {
    router.push(issue.route)
    return
  }

  focusLiuyaoField(issue?.selector)
}

const buildSubmitIssues = () => {
  const issues = []
  const trimmedQuestion = form.question.trim()

  if (pricingLoading.value) {
    issues.push({
      key: 'pricing-loading',
      actionLabel: '等待价格同步',
      message: '正在同步当前占卜价格，等价格卡片刷新后再提交更稳。',
      selector: '[data-liuyao-field="pricing"]'
    })
  } else if (pricingError.value || !pricing.value) {
    issues.push({
      key: 'pricing-error',
      actionLabel: '重新获取价格',
      message: pricingError.value || '当前价格信息还没同步成功，先刷新后再提交。',
      handler: () => loadPricing(),
      selector: '[data-liuyao-field="pricing"]'
    })
  }

  if (!trimmedQuestion) {
    issues.push({
      key: 'question-empty',
      actionLabel: '先写下你的问题',
      message: '六爻更适合问一件具体的事，先把问题补充完整。',
      selector: '[data-liuyao-field="question"]'
    })
  } else if (trimmedQuestion.length < 2) {
    issues.push({
      key: 'question-short',
      actionLabel: '把问题写具体一点',
      message: '问题至少写到 2 个字，越具体越容易得到可判断的结果。',
      selector: '[data-liuyao-field="question"]'
    })
  }

  if (form.method === 'number' && !Number.isFinite(form.numbers[0])) {
    issues.push({
      key: 'number-method',
      actionLabel: '补第一个数字',
      message: '数字起卦至少需要先填写第一个 1-999 的数字。',
      selector: '[data-liuyao-field="number-method"]'
    })
  }

  if (form.method === 'manual' && form.yaoResults.some((item) => !Number.isFinite(item))) {
    issues.push({
      key: 'manual-method',
      actionLabel: '补齐 6 次摇卦结果',
      message: '手动摇卦需要从初爻到上爻依次填满 6 次结果。',
      selector: '[data-liuyao-field="manual-method"]'
    })
  }

  return issues
}

const submitSummaryText = computed(() => {
  if (!submitErrors.value.length) {
    return ''
  }

  return `已整理出 ${submitErrors.value.length} 个待处理项，点一下即可直接定位。`
})


const submitButtonText = computed(() => {
  if (pricingLoading.value) {
    return '正在同步价格...'
  }

  if (pricingError.value || !pricing.value) {
    return '请先重新获取价格'
  }

  if (pricing.value.is_first_free) {
    return '首次免费起卦'
  }

  if (pricing.value.is_vip_free) {
    return 'VIP免费起卦'
  }

  const cost = Number(pricing.value.cost)
  if (Number.isFinite(cost) && cost > 0) {
    return `确认并消耗${cost}积分`
  }

  return '开始占卜'
})


const shouldShowRemainingPoints = computed(() => {

  if (!result.value || result.value.is_history) {
    return false
  }

  return result.value.remaining_points !== null && result.value.remaining_points !== undefined
})

// 术语解释数据
const terminologyMap = {
  '日辰': '起卦当天的干支，如"甲子日"，代表时间基准',
  '月建': '起卦当月的地支，如"寅月"，代表月份影响',
  '旬空': '空亡地支，表示该位置力量空虚，可能影响判断',
  '本卦': '原始卦象，反映事物的初始状态',
  '变卦': '动爻变化后的卦象，反映事物的发展结果',
  '互卦': '由本卦中间四爻组成，反映事物的内在过程',
  '所属宫位': '卦象所属的八宫，决定整体性质',
  '世应': '世爻代表"我"，应爻代表"他人"或"对方"',
  '动爻': '发生变化的爻，代表事物变动的因素',
  '用神': '根据所问之事选定的关键爻，如问事看官鬼，问财看妻财',
  '六亲': '父母、兄弟、子孙、妻财、官鬼，代表不同事物的关系',
  '六神': '青龙、白虎、朱雀、玄武、勾陈、螣蛇，代表吉凶趋向',
  '纳甲': '将地支配入卦中六爻，如"甲子"，用于精确判断',
  '阳爻': '实线，代表刚强、积极、男性性质',
  '阴爻': '虚线，代表柔弱、消极、女性性质'
}

// 显示术语解释
const showTermExplanation = (term) => {
  const explanation = terminologyMap[term]
  if (explanation) {
    ElMessage.info({
      message: `${term}：${explanation}`,
      duration: 5000,
      showClose: true
    })
  }
}

const savedStatusText = computed(() => (result.value?.is_history ? '来自历史记录' : '已自动保存到历史记录'))
const historyTriggerText = computed(() => (
  history.value.length > 0 ? `查看历史记录 (${history.value.length}条)` : '查看历史记录'
))

const openHistoryDialog = (event) => {
  historyTriggerEl = event?.currentTarget instanceof HTMLElement ? event.currentTarget : null
  showHistory.value = true
}

const restoreHistoryTriggerFocus = () => {
  if (historyTriggerEl instanceof HTMLElement) {
    historyTriggerEl.focus()
  }
  historyTriggerEl = null
}

const focusHistoryDialogPrimaryAction = async () => {
  await nextTick()
  const target = historyListRef.value?.querySelector('.history-select, .delete-btn, .el-button')
  if (target instanceof HTMLElement) {
    target.focus()
  }
}

watch(showHistory, (visible) => {
  if (visible) {
    focusHistoryDialogPrimaryAction()
  }
})

watch([
  () => form.question,
  () => form.method,
  () => form.numbers[0],
  () => form.numbers[1],
  () => form.yaoResults.join(','),
  pricingLoading,
  pricingError
], () => {
  if (submitErrors.value.length) {
    clearSubmitErrors()
  }
})

const formatDateTime = (dateStr) => {


  const rawValue = typeof dateStr === 'string' ? dateStr.trim() : ''
  if (!rawValue) {
    return ''
  }

  const normalizedValue = rawValue.includes('T') ? rawValue : rawValue.replace(' ', 'T')
  const date = new Date(normalizedValue)
  if (Number.isNaN(date.getTime())) {
    return rawValue
  }

  return new Intl.DateTimeFormat('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const resultContextItems = computed(() => {
  if (!result.value) {
    return []
  }

  const timeInfo = result.value.time_info || {}
  const xunkong = Array.isArray(timeInfo.xunkong)
    ? timeInfo.xunkong.filter(Boolean).join('、')
    : String(timeInfo.xunkong || '').trim()

  return [
    { label: '起卦方式', value: result.value.method_label || '' },
    { label: '起卦时间', value: formatDateTime(timeInfo.divination_at || result.value.created_at || '') },
    { label: '日辰', value: String(timeInfo.ri_chen || '').trim() },
    { label: '月建', value: String(timeInfo.yue_jian || '').trim() },
    { label: '旬空', value: xunkong },
  ].filter((item) => item.value)
})

const structuredResultItems = computed(() => {
  if (!result.value) {
    return []
  }

  const shiYing = result.value.shi_ying || {}
  const yongShen = result.value.yong_shen || {}
  const yongShenText = [yongShen.liuqin, yongShen.description]
    .filter((item) => item && String(item).trim())
    .join('｜')
  const dongYao = Array.isArray(result.value.bian_gua?.dong_yao)
    ? result.value.bian_gua.dong_yao.filter(Boolean).join('、')
    : ''
  const shiYingText = [
    shiYing.shi ? `世爻：第${shiYing.shi}爻` : '',
    shiYing.ying ? `应爻：第${shiYing.ying}爻` : '',
  ].filter(Boolean).join('｜')

  return [
    { label: '本卦', value: result.value.gua?.name || '' },
    { label: '变卦', value: result.value.bian_gua?.name || '' },
    { label: '互卦', value: result.value.hu_gua?.name || '' },
    { label: '所属宫位', value: result.value.gong || '' },
    { label: '世应', value: shiYingText },
    { label: '动爻', value: dongYao },
    { label: '用神', value: yongShenText },
  ].filter((item) => item.value)
})

// 核心结论摘要
const summaryHighlights = computed(() => {
  if (!result.value) {
    return []
  }

  const highlights = []

  // 本卦与变卦
  const gua = result.value.gua?.name || ''
  const bianGua = result.value.bian_gua?.name || ''
  if (gua && bianGua) {
    highlights.push({
      icon: '🎯',
      label: '卦象变化',
      value: `${gua} → ${bianGua}`
    })
  }

  // 动爻信息
  const dongYao = Array.isArray(result.value.bian_gua?.dong_yao)
    ? result.value.bian_gua.dong_yao.filter(Boolean)
    : []
  if (dongYao.length > 0) {
    highlights.push({
      icon: '⚡',
      label: '动爻',
      value: dongYao.length > 3 ? `共${dongYao.length}爻变动` : dongYao.join('、')
    })
  }

  // 用神信息
  const yongShen = result.value.yong_shen || {}
  if (yongShen.liuqin || yongShen.description) {
    highlights.push({
      icon: '🎭',
      label: '用神',
      value: yongShen.description || yongShen.liuqin || ''
    })
  }

  // 世应关系
  const shiYing = result.value.shi_ying || {}
  if (shiYing.shi && shiYing.ying) {
    highlights.push({
      icon: '👤',
      label: '世应',
      value: `世爻第${shiYing.shi} · 应爻第${shiYing.ying}`
    })
  }

  return highlights
})

const reportUiError = (action, error, userMessage = '') => {


  if (userMessage) {
    ElMessage.error(userMessage)
  }
}

const isMovingYao = (yao) => Number(yao) === 0 || Number(yao) === 3
const isYangYao = (yao) => Number(yao) === 1 || Number(yao) === 3

const getYaoName = (yao) => {
  const value = Number(yao)
  return yaoNameMap[value] || '未知'
}

// 爻标记
const getYaoMark = (yao) => {
  const value = Number(yao)
  if (value === 0) return '×' // 老阴
  if (value === 3) return '○' // 老阳
  return '' // 少阴少阳
}

// 获取卦象符号
const getGuaSymbol = (guaCode) => {
  if (!guaCode) return ''
  const gua = guaData.find(item => item.symbol === guaCode || item.name.includes(guaCode))
  return gua ? gua.symbol : ''
}

const parseYaoResult = (value, fallback = '') => {
  if (Array.isArray(value)) {
    return value.map((item) => normalizeYaoCode(item))
  }

  if (typeof value === 'string' && value.trim()) {
    const trimmed = value.trim()
    const parsed = safeJsonParse(trimmed, null)
    if (Array.isArray(parsed)) {
      return parsed.map((item) => normalizeYaoCode(item))
    }

    if (trimmed.includes(',')) {
      return trimmed.split(',').map((item) => normalizeYaoCode(item))
    }

    if (/^[0-3]{6}$/.test(trimmed) || /^[6-9]{6}$/.test(trimmed)) {
      return trimmed.split('').map((item) => normalizeYaoCode(item))
    }
  }

  if (typeof fallback === 'string' && /^[0-3]{6}$/.test(fallback)) {
    return fallback.split('').map((item) => normalizeYaoCode(item))
  }

  return []
}

const normalizeYaoCode = (value) => {
  const numeric = Number(value)
  if (Number.isNaN(numeric)) {
    return 1
  }

  if (numeric >= 0 && numeric <= 3) {
    return numeric
  }

  return ({ 6: 0, 7: 1, 8: 2, 9: 3 })[numeric] ?? 1
}

const safeJsonParse = (value, fallback = null) => {
  if (typeof value !== 'string') {
    return fallback
  }

  try {
    return JSON.parse(value)
  } catch {
    return fallback
  }
}

const normalizeAiAnalysis = (value) => {
  if (!value) {
    return null
  }

  if (typeof value === 'string') {
    return { content: value }
  }

  if (typeof value === 'object' && value.content) {
    return value
  }

  return null
}

const normalizeFushen = (value) => {
  if (!value || typeof value !== 'object') {
    return null
  }

  const name = String(value.name || '').trim()
  if (!name) {
    return null
  }

  return {
    name,
    element: value.element || '',
    relation: value.relation || '',
    status: value.status || ''
  }
}

// 计算卦象SVG路径
const guaSvgPath = computed(() => {
  if (!result.value || !result.value.yao_result) {
    return null
  }
  // SVG路径已在模板中动态生成，此处返回true表示启用SVG模式
  return true
})

const getYinYangLabel = (value) => (isYangYao(value) ? '阳爻' : '阴爻')

const describeLineChange = (line = {}) => {
  const fromName = line.name || getYaoName(line.value)
  const toName = line.bian_name || getYaoName(line.bian_value ?? line.value)
  return line.is_moving ? `${fromName} → ${toName}` : '静爻不变'
}

const formatMovingLineMeta = (line = {}) => {
  return [
    line.liuqin ? `六亲：${line.liuqin}` : '',
    line.liushen ? `六神：${line.liushen}` : '',
    line.di_zhi ? `纳甲：${line.di_zhi}` : '',
  ].filter(Boolean).join(' ｜ ')
}

const normalizeLineDetail = (line = {}, index = 0, fallbackValue = 1, liuqinMap = {}, liushenMap = {}, shiYing = {}, movingPositions = []) => {
  const position = Number(line.position || index + 1)
  const value = normalizeYaoCode(line.value ?? fallbackValue)
  const bianValue = normalizeYaoCode(line.bian_value ?? value)
  const isMoving = line.is_moving !== undefined ? Boolean(line.is_moving) : movingPositions.includes(position)
  const normalized = {
    position,
    value,
    name: line.name || getYaoName(value),
    yin_yang: line.yin_yang || getYinYangLabel(value),
    is_yang: line.is_yang !== undefined ? Boolean(line.is_yang) : isYangYao(value),
    liuqin: line.liuqin || liuqinMap[String(position)] || liuqinMap[position] || '',
    liushen: line.liushen || liushenMap[String(position)] || liushenMap[position] || '',
    di_zhi: line.di_zhi || '',
    bian_value: bianValue,
    bian_name: line.bian_name || getYaoName(bianValue),
    bian_yin_yang: line.bian_yin_yang || getYinYangLabel(bianValue),
    bian_is_yang: line.bian_is_yang !== undefined ? Boolean(line.bian_is_yang) : isYangYao(bianValue),
    change_summary: line.change_summary || '',
    is_moving: isMoving,
    is_shi: line.is_shi !== undefined ? Boolean(line.is_shi) : Number(shiYing.shi || 0) === position,
    is_ying: line.is_ying !== undefined ? Boolean(line.is_ying) : Number(shiYing.ying || 0) === position,
    fushen: normalizeFushen(line.fushen),
  }
  normalized.change_summary = normalized.change_summary || describeLineChange(normalized)
  return normalized
}

const normalizeResult = (data = {}, isHistory = false) => {

  const gua = data.gua || {}
  const bianGua = data.bian_gua || {}
  const huGua = data.hu_gua || {}
  const yaoResult = parseYaoResult(data.yao_result ?? data.yao_results, data.yao_code || gua.code || '')
  const liuqinMap = (data.liuqin && typeof data.liuqin === 'object') ? data.liuqin : {}
  const liushenMap = (data.liushen && typeof data.liushen === 'object') ? data.liushen : {}
  const shiYing = (data.shi_ying && typeof data.shi_ying === 'object') ? data.shi_ying : {}
  const dongYao = Array.isArray(bianGua.dong_yao) ? bianGua.dong_yao.map((item) => Number(item)) : []
  const lineDetails = Array.isArray(data.line_details) && data.line_details.length
    ? data.line_details.map((line, index) => normalizeLineDetail(line, index, yaoResult[index] ?? 1, liuqinMap, liushenMap, shiYing, dongYao))
    : yaoResult.map((item, index) => normalizeLineDetail({ value: item }, index, item, liuqinMap, liushenMap, shiYing, dongYao))
  const movingLineDetails = Array.isArray(data.moving_line_details) && data.moving_line_details.length
    ? data.moving_line_details.map((line, index) => normalizeLineDetail(line, index, line.value ?? yaoResult[index] ?? 1, liuqinMap, liushenMap, shiYing, dongYao))
    : lineDetails.filter((line) => line.is_moving)

  return {

    id: data.id,
    question: data.question || '',
    method: data.method || '',
    method_label: data.method_label || '',
    time_info: data.time_info || null,
    created_at: data.created_at || data.createdAt || '',
    yao_result: yaoResult,
    yao_names: Array.isArray(data.yao_names) && data.yao_names.length === yaoResult.length
      ? data.yao_names
      : yaoResult.map((item) => getYaoName(item)),
    gua: {
      name: gua.name || data.gua_name || data.main_gua || '',
      code: gua.code || data.gua_code || data.clean_gua_code || data.yao_code || '',
      gua_ci: gua.gua_ci || data.gua_ci || data.gua_info?.main?.gua_ci || data.gua_info?.main?.general_meaning || '',
    },
    bian_gua: {
      name: bianGua.name || data.bian_gua_name || '',
      code: bianGua.code || data.bian_gua_code || '',
      dong_yao: dongYao.filter((item) => Number.isFinite(item) && item > 0),
    },
    hu_gua: {
      name: huGua.name || data.hu_gua_name || '',
      code: huGua.code || data.hu_gua_code || '',
    },
    gong: data.gong || '',
    shi_ying: shiYing,
    liuqin: liuqinMap,
    liushen: liushenMap,
    yong_shen: (data.yong_shen && typeof data.yong_shen === 'object')
      ? data.yong_shen
      : { liuqin: data.yongshen || '' },
    line_details: lineDetails,
    moving_line_details: movingLineDetails,
    interpretation: data.interpretation || '',

    ai_analysis: normalizeAiAnalysis(data.ai_analysis || data.ai_interpretation),
    points_cost: Number(data.points_cost ?? data.consumed_points ?? 0) || 0,
    remaining_points: data.remaining_points ?? null,
    is_first: Boolean(data.is_first),
    is_history: isHistory,
    fushen: data.fushen || null,
  }
}

// 获取定价
const loadPricing = async () => {
  pricingLoading.value = true
  pricingError.value = ''

  try {
    const response = await getLiuyaoPricing()
    if (response.code === 200) {
      pricing.value = response.data || null
      return
    }

    pricing.value = null
    pricingError.value = response.message || '获取占卜价格失败，请稍后重试'
  } catch (error) {
    pricing.value = null
    pricingError.value = '获取占卜价格失败，请稍后重试'
    reportUiError('获取定价失败', error)
  } finally {
    pricingLoading.value = false
  }
}

// 加载历史记录
const loadHistory = async () => {
  historyLoading.value = true
  historyError.value = ''

  try {
    const response = await getLiuyaoHistory({ page: 1, page_size: 50 })
    if (response.code === 200) {
      history.value = (response.data.list || []).map((item) => normalizeResult(item, true))
      historyLoaded.value = true
      return
    }

    history.value = []
    historyLoaded.value = false
    historyError.value = response.message || '获取历史记录失败，请稍后重试。'
  } catch (error) {
    history.value = []
    historyLoaded.value = false
    historyError.value = '获取历史记录失败，请稍后重试。'
    reportUiError('获取历史记录失败', error)
  } finally {
    historyLoading.value = false
  }
}


const buildDivinationPayload = () => {
  const payload = {
    question: form.question.trim(),
    useAi: form.useAi,
    method: form.method,
    question_type: form.questionType,
    gender: form.gender,
    version: form.version, // 添加版本选择：basic（简单版）/ professional（专业版）
  }

  if (form.riGan) {
    payload.ri_gan = form.riGan
  }

  if (form.riZhi) {
    payload.ri_zhi = form.riZhi
  }

  if (form.method === 'number') {
    payload.numbers = form.numbers.filter((item) => Number.isFinite(item))
  }

  if (form.method === 'manual') {
    payload.yao_results = [...form.yaoResults]
  }

  return payload
}

// 提交占卜
const submitDivination = async () => {
  clearSubmitErrors()
  const issues = buildSubmitIssues()

  if (issues.length) {
    submitErrors.value = issues
    handleSubmitIssue(issues[0])
    return
  }

  // 积分不足前置拦截
  if (pricing.value && !pricing.value.is_free && pricing.value.balance < pricing.value.cost) {
    ElMessageBox.confirm(
      '当前积分不足，是否前往签到或充值获取积分？',
      '积分不足',
      {
        confirmButtonText: '去获取积分',
        cancelButtonText: '取消',
        type: 'warning',
      }
    ).then(() => {
      router.push('/profile')
    }).catch(() => {})
    return
  }

  isLoading.value = true
  isSubmitting.value = true
  
  // 埋点：提交开始
  const payload = buildDivinationPayload()
  trackLiuyaoSubmitStart({
    method: payload.method,
    useAi: payload.use_ai,
    questionType: payload.question_type
  })

  try {
    const response = await liuyaoDivination(payload)

    if (response.code === 200) {
      trackSubmit('liuyao_divination', true, { method: payload.method })
      trackLiuyaoSubmitSuccess({
        method: payload.method,
        useAi: payload.use_ai,
        hasAiAnalysis: !!response.data.ai_analysis
      })
      clearSubmitErrors()
      result.value = normalizeResult(response.data, false)
      await loadHistory()
      await loadPricing()
      trackLiuyaoResultView(!!response.data.ai_analysis)
    } else {
      trackSubmit('liuyao_divination', false, { method: payload.method, error: response.message })
      trackLiuyaoSubmitFail({
        method: payload.method,
        useAi: payload.use_ai,
        error: response.message
      })
      ElMessage.error(response.message || '占卜失败，请重试')
    }
  } catch (error) {
    trackSubmit('liuyao_divination', false, { error: error.message })
    trackError('liuyao_divination_error', error.message)
    trackLiuyaoSubmitFail({
      method: payload.method,
      useAi: payload.use_ai,
      error: error.message
    })
    reportUiError('提交六爻占卜失败', error, '占卜失败，请重试')
  } finally {
    isLoading.value = false
    isSubmitting.value = false
  }
}


// 重置表单
const resetForm = () => {
  clearSubmitErrors()
  Object.assign(form, createDefaultForm())
  result.value = null
  loadPricing()
}

// 开始AI分析
const startAiAnalysis = async () => {
  if (!result.value?.id) {
    ElMessage.error('无效的占卜记录')
    return
  }

  // 检查积分
  if (pricing.value && !pricing.value.is_free && pricing.value.balance < pricing.value.cost) {
    ElMessageBox.confirm(
      '当前积分不足，是否前往签到或充值获取积分？',
      '积分不足',
      {
        confirmButtonText: '去获取积分',
        cancelButtonText: '取消',
        type: 'warning',
      }
    ).then(() => {
      router.push('/profile')
    }).catch(() => {})
    return
  }

  aiAnalyzing.value = true

  try {
    const response = await analyzeLiuyaoAi({
      divination_id: result.value.id
    })

    if (response.code === 200) {
      // 更新result中的AI分析结果
      if (response.data?.ai_analysis) {
        result.value.ai_analysis = response.data.ai_analysis
        ElMessage.success('AI解卦成功')
      }
      
      // 更新积分
      if (response.data?.remaining_points !== undefined) {
        if (pricing.value) {
          pricing.value.balance = response.data.remaining_points
        }
      }
      
      // 重新加载定价信息
      await loadPricing()
    } else {
      ElMessage.error(response.message || 'AI解卦失败，请重试')
    }
  } catch (error) {
    console.error('AI解卦错误:', error)
    ElMessage.error('AI解卦服务暂时不可用，请稍后重试')
  } finally {
    aiAnalyzing.value = false
  }
}


const shouldShowLiuyaoRechargeAction = computed(() => {
  const remaining = Number(result.value?.remaining_points)
  const cost = Number(pricing.value?.cost ?? 0)
  return Number.isFinite(remaining) && Number.isFinite(cost) && cost > 0 && remaining < cost
})

const liuyaoResultHighlights = computed(() => {
  const highlights = [
    {
      key: 'saved-status',
      label: savedStatusText.value,
      tone: result.value?.is_history ? '' : 'success',
    },
    {
      key: 'cost',
      label: result.value?.points_cost > 0 ? `本次消耗 ${result.value.points_cost} 积分` : '本次免费',
      tone: result.value?.points_cost > 0 ? 'warning' : '',
    },
  ]

  if (shouldShowRemainingPoints.value) {
    highlights.push({
      key: 'remaining',
      label: `剩余 ${result.value.remaining_points} 积分`,
      tone: shouldShowLiuyaoRechargeAction.value ? 'danger' : '',
    })
  }

  if (result.value?.ai_analysis) {
    highlights.push({
      key: 'ai',
      label: '含 AI 深度分析',
    })
  }

  return highlights
})

const liuyaoResultActions = computed(() => {
  return [
    historyLoaded.value || historyLoading.value || historyError.value
      ? {
          key: 'history',
          label: historyTriggerText.value,
          type: 'primary',
          onClick: () => openHistoryDialog(),
        }
      : null,
    {
      key: 'profile',
      label: '查看我的积分',
      to: '/profile',
    },
    shouldShowLiuyaoRechargeAction.value
      ? {
          key: 'recharge',
          label: '去充值 / 补积分',
          plain: true,
          to: '/recharge',
        }
      : null,
    {
      key: 'retry',
      label: '再次占卜',
      plain: true,
      onClick: resetForm,
    },
  ].filter(Boolean)
})

const liuyaoRelatedRecommendations = computed(() => {
  return [
    {
      key: 'tarot',
      title: '换成塔罗再看一层',
      description: '如果你想把当前问事换成更偏情绪与关系的视角，可以顺手切到塔罗继续问。',
      to: '/tarot',
      badge: '相关推荐',
    },
    {
      key: 'daily',
      title: '看看今日运势',
      description: '把六爻判断和当天整体节奏放一起看，方便决定是马上行动还是先等等。',
      to: '/daily',
      badge: '继续承接',
    },
  ]
})

const liuyaoShareSummary = computed(() => {
  if (!result.value) return '我在太初命理测算了六爻，结果很准！'
  const guaName = result.value.gua?.name || ''
  const bianGuaName = result.value.bian_gua?.name || ''
  if (bianGuaName) {
    return `我卜得【${guaName}】变【${bianGuaName}】，快来看看你的运势吧！`
  }
  return `我卜得【${guaName}】，快来看看你的运势吧！`
})

const liuyaoShareTags = computed(() => {
  if (!result.value) return []
  const tags = []
  if (result.value.gua?.name) tags.push(`本卦${result.value.gua.name}`)
  if (result.value.bian_gua?.name) tags.push(`变卦${result.value.bian_gua.name}`)
  return tags
})

// 加载历史记录详情

const loadHistoryDetail = (item) => {
  result.value = normalizeResult(item, true)
  showHistory.value = false
}

// 删除记录
const deleteRecord = async (id) => {
  try {
    await ElMessageBox.confirm('确定要删除这条记录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })

    const response = await deleteLiuyaoRecord({ id })
    if (response.code === 200) {
      ElMessage.success('删除成功')
      await loadHistory()
      if (result.value?.id === id) {
        result.value = null
      }
      await loadPricing()
    } else {
      ElMessage.error(response.message)
    }
  } catch (error) {
    if (error !== 'cancel') {
      reportUiError('删除六爻历史记录失败', error, '删除失败')
    }
  }
}

// 格式化日期
const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  if (Number.isNaN(date.getTime())) {
    return dateStr || '--'
  }
  return date.toLocaleDateString('zh-CN')
}

// 初始化
onMounted(() => {
  trackPageView('liuyao')
  beijingTimer = window.setInterval(() => {
    currentBeijingTimestamp.value = Date.now()
  }, 1000)
  loadPricing()
  loadHistory()
})

onUnmounted(() => {
  if (beijingTimer) {
    clearInterval(beijingTimer)
    beijingTimer = null
  }
})
</script>

<style scoped>
.liuyao-page {
  padding: 40px 20px;
  min-height: 100vh;
}

.container {
  max-width: 700px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 40px;
}

.page-header-content {
  flex: 1;
}

.page-title {
  font-size: 36px;
  color: var(--text-primary);
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12px;
}

.title-icon {
  font-size: 42px;
}

.page-subtitle {
  color: var(--text-secondary);
  font-size: 16px;
  margin: 0;
}

/* 表单样式 */
.form-card {
  background: linear-gradient(135deg, var(--bg-card), var(--surface-raised));
  backdrop-filter: blur(10px);
  border-radius: 24px;
  padding: 40px 32px;
  border: 2px solid var(--border-light);
  box-shadow: var(--shadow-lg);
}

.form-card h2 {
  color: var(--text-primary);
  text-align: center;
  margin-bottom: 8px;
  font-size: 28px;
  font-weight: 700;
}

.form-tip {
  color: var(--text-secondary);
  text-align: center;
  font-size: 15px;
  margin-bottom: 32px;
  line-height: 1.6;
}

.form-group {
  margin-bottom: 28px;
}

.form-group label {
  display: block;
  color: var(--text-secondary);
  margin-bottom: 12px;
  font-size: 15px;
  font-weight: 600;
}

.form-group label .required {
  color: var(--primary-color);
  margin-left: 2px;
}

.form-group textarea {
  width: 100%;
  padding: 18px 20px;
  background: var(--bg-secondary);
  border: 2px solid var(--border-light);
  border-radius: 16px;
  color: var(--text-primary);
  font-size: 16px;
  line-height: 1.7;
  resize: vertical;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: inherit;
}

.form-group textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.08);
}

.form-group textarea::placeholder {
  color: var(--text-muted);
  font-size: 14px;
}

/* 快捷问题按钮 */
.quick-questions {
  margin-top: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

.quick-questions-label {
  font-size: 14px;
  color: var(--text-secondary);
  margin-right: 6px;
  font-weight: 500;
}

.quick-question-btn {
  padding: 8px 16px;
  font-size: 14px;
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), rgba(var(--primary-rgb), 0.03));
  border: 2px solid rgba(var(--primary-rgb), 0.15);
  border-radius: 20px;
  color: var(--primary-color);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  font-weight: 500;
}

.quick-question-btn:hover {
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.15), rgba(var(--primary-rgb), 0.08));
  border-color: var(--primary-color);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.12);
}

.quick-question-btn:active {
  transform: translateY(0);
  box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.1);
}

.char-count {
  display: block;
  text-align: right;
  color: var(--text-secondary);
  font-size: 12px;
  margin-top: 6px;
}

.full-width {
  width: 100%;
}

/* 起卦方式推荐标签 */
.method-label {
  font-size: 14px;
  font-weight: 500;
}

.method-tag {
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 4px;
  margin-left: 6px;
  font-weight: 600;
  white-space: nowrap;
}

.method-tag--recommend {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
  color: var(--white);
}

.method-tag--highlight {
  background: var(--success-light);
  color: var(--success-color);
  border: 1px solid var(--success-color);
}

.method-audience {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
  font-size: 12px;
  color: var(--text-secondary);
}

.method-audience .el-icon {
  font-size: 12px;
  color: var(--primary-color);
}

:deep(.el-radio-button.is-recommend .el-radio-button__inner) {
  background: linear-gradient(135deg, var(--primary-light-05) 0%, var(--white) 100%);
  border-color: var(--primary-color);
}

.method-group {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.form-helper {
  margin: 12px 0 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.7;
}

.helper-card,
.advanced-card {
  padding: 24px 28px;
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
  border: 2px solid var(--border-light);
  border-radius: 20px;
  margin-bottom: 24px;
}

.helper-card__title {
  margin: 0 0 12px;
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
}

.helper-card__desc,
.advanced-card__header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.7;
}

.advanced-toggle {
  width: 100%;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 0;
  border: none;
  background: transparent;
  color: inherit;
  text-align: left;
  cursor: pointer;
}

.advanced-toggle__action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--primary-color);
  font-size: 14px;
  font-weight: 600;
  flex-shrink: 0;
  margin-top: 4px;
}

.advanced-card__header {
  margin-bottom: 0;
}

.advanced-card__header h3 {
  margin: 0 0 8px;
  color: var(--text-primary);
  font-size: 20px;
  font-weight: 700;
}

.input-grid,
.advanced-grid,
.manual-grid {
  display: grid;
  gap: 20px;
}

.input-grid--double,
.advanced-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.advanced-grid {
  margin-top: 20px;
}

/* 修复 advanced-grid 中选择器错位问题 */
.advanced-grid .form-group {
  margin-bottom: 0;
  display: flex;
  flex-direction: column;
}

.advanced-grid .form-group label {
  margin-bottom: 8px;
}

.advanced-grid :deep(.el-select),
.advanced-grid :deep(.el-radio-group) {
  width: 100%;
}

.advanced-grid :deep(.el-select .el-input__wrapper) {
  width: 100%;
}

.advanced-grid :deep(.el-radio-group .el-radio-button) {
  flex: 1;
}

.input-grid__item label,
.manual-grid__item label {
  display: block;
  margin-bottom: 12px;
  color: var(--text-secondary);
  font-size: 15px;
  font-weight: 600;
}

/* 修复 manual-grid 布局 */
.manual-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin-top: 20px;
}

.manual-grid__item {
  display: flex;
  flex-direction: column;
}

.manual-grid__item :deep(.el-select) {
  width: 100%;
}

/* 版本选择样式 */
.version-selection {
  margin-bottom: 28px;
}

.version-label {
  display: block;
  color: var(--text-secondary);
  margin-bottom: 12px;
  font-size: 15px;
  font-weight: 600;
}

.version-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.version-option {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: var(--bg-secondary);
  border: 2px solid var(--border-light);
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

.version-option:hover {
  border-color: rgba(var(--primary-rgb), 0.3);
  background: rgba(var(--primary-rgb), 0.04);
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.version-option--active {
  border-color: var(--primary-color);
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), rgba(var(--primary-rgb), 0.03));
  box-shadow: 0 4px 16px rgba(var(--primary-rgb), 0.15);
}

.version-option__icon {
  font-size: 28px;
  flex-shrink: 0;
  line-height: 1;
}

.version-option__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.version-option__title {
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 700;
  line-height: 1.4;
}

.version-option__desc {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.5;
}

.version-option__price {
  flex-shrink: 0;
  margin-left: auto;
}

.price-value {
  display: inline-block;
  padding: 6px 12px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  color: var(--white);
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
}

.version-option--active .price-value {
  background: linear-gradient(135deg, var(--primary-color), #F4D03F);
  box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.3);
}

.version-option__badge {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 4px 8px;
  background: linear-gradient(135deg, #FF6B6B, #FF8E53);
  color: var(--white);
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
}

.options-section {
  margin-bottom: 20px;
}

.pricing-info {
  text-align: left;
  padding: 16px;
  background: var(--bg-secondary);
  border-radius: 16px;
  margin: 20px 0;
  border: 1px solid var(--border-light);
}

.pricing-info-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pricing-info-main {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pricing-info-details {
  background: rgba(255, 255, 255, 0.5);
  padding: 12px;
  border-radius: 8px;
  border: 1px solid var(--border-light);
}

.pricing-info-title {
  font-weight: bold;
  color: var(--text-primary);
  margin: 0 0 8px 0;
  font-size: 14px;
}

.pricing-info-list {
  list-style: none;
  padding: 0;
  margin: 0 0 12px 0;
}

.pricing-info-list li {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  color: var(--text-secondary);
  font-size: 13px;
}

.pricing-info-list li .el-icon {
  color: var(--success-color);
}

.pricing-info-guarantee {
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--warning-color);
  font-size: 12px;
  margin: 0;
}

/* 基础占卜 vs AI深度分析对比 */
.ai-value-compare {
  display: flex;
  gap: 16px;
  margin-bottom: 16px;
  flex-direction: column;
}

@media (min-width: 768px) {
  .ai-value-compare {
    flex-direction: row;
  }
}

.compare-card {
  flex: 1;
  border-radius: 12px;
  padding: 16px;
  border: 1px solid var(--border-base);
  transition: all 0.3s;
}

.basic-card {
  background: var(--bg-secondary);
  border-color: var(--border-light);
}

.ai-card {
  background: linear-gradient(135deg, var(--primary-light-05) 0%, var(--bg-secondary) 100%);
  border-color: var(--primary-color);
  box-shadow: 0 4px 16px rgba(212, 175, 55, 0.15);
}

.ai-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(212, 175, 55, 0.25);
}

.compare-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--border-light);
}

.compare-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.basic-card .compare-badge {
  background: var(--bg-tertiary);
  color: var(--text-secondary);
}

.ai-card .compare-badge.premium {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
  color: var(--white);
}

.compare-tag {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  background: var(--error);
  color: var(--white);
  font-size: 11px;
  font-weight: 600;
}

.compare-features {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.compare-features li {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.basic-card .compare-features .el-icon {
  color: var(--error);
}

.ai-card .compare-features .el-icon {
  color: var(--success-color);
  font-size: 16px;
}

.ai-card .compare-features strong {
  color: var(--text-primary);
  font-weight: 600;
}

.ai-highlight {
  margin-top: 12px;
  padding: 10px 12px;
  background: var(--white);
  border-radius: 8px;
  border: 1px solid var(--primary-light-20);
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.ai-highlight .el-icon {
  color: var(--primary-color);
  font-size: 16px;
  flex-shrink: 0;
}

.pricing-reason {
  margin: 10px 0 0;
  color: var(--text-secondary);
  font-size: 13px;
}

.pricing-free,
.pricing-vip {
  color: var(--success-color);
  font-size: 18px;
  font-weight: 600;
}

.pricing-normal {
  color: var(--text-primary);
  font-size: 16px;
}

.btn-submit {
  width: 100%;
  padding: 18px;
  background: var(--primary-gradient);
  color: var(--text-primary);
  border: none;
  border-radius: 16px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-submit:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px var(--primary-light-40);
}

.btn-icon {
  font-size: 24px;
}

.loading {
  width: 24px;
  height: 24px;
  border: 2px solid var(--border-color);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.history-link {
  text-align: center;
  margin-top: 20px;
}

.history-link__button {
  appearance: none;
  border: none;
  background: none;
  padding: 0;
  color: var(--text-secondary);
  cursor: pointer;
  text-decoration: underline;
  font-size: 14px;
  font: inherit;
}

.history-link__button:hover {
  color: var(--primary-color);
}

.history-link__button:focus-visible {
  outline: 2px solid var(--primary-light);
  outline-offset: 4px;
  border-radius: 6px;
}


/* 结果卡片 */
.result-section {
  margin-bottom: 40px;
}

.result-card {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 32px;
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-lg);
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.result-header h2 {
  color: var(--text-primary);
  margin: 0;
}

.first-free-badge {
  background: var(--success-gradient);
  color: var(--text-primary);
  padding: 12px 24px;
  border-radius: 20px;
  font-size: 14px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
}

.result-context {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 18px;
}

/* 核心结论摘要 */
.summary-section {
  margin-bottom: 24px;
}

.summary-card {
  background: linear-gradient(135deg, var(--primary-light-05) 0%, var(--bg-secondary) 100%);
  border: 1px solid var(--primary-light-20);
  border-radius: 16px;
  padding: 20px;
}

.summary-title {
  color: var(--primary-color);
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 16px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.summary-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: var(--white-03);
  border-radius: 12px;
  border: 1px solid var(--white-08);
  transition: all 0.3s;
}

.summary-item:hover {
  background: var(--white-05);
  border-color: var(--primary-light-20);
  transform: translateX(4px);
}

.summary-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.summary-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
  min-width: 0;
}

.summary-label {
  font-size: 12px;
  color: var(--text-tertiary);
}

.summary-value {
  font-size: 15px;
  color: var(--text-primary);
  font-weight: 600;
  word-break: break-word;
}

.context-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 40px;
  padding: 8px 14px;
  border-radius: 999px;
  border: 1px solid var(--border-light);
  background: linear-gradient(180deg, var(--bg-secondary), var(--bg-card));
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}

.context-chip:hover {
  border-color: var(--primary-color);
  background: linear-gradient(180deg, var(--primary-light-05), var(--bg-secondary));
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.context-chip__label {
  font-size: 12px;
  color: var(--text-tertiary);
}

.context-chip__value {
  font-size: 13px;
  color: var(--text-primary);
  font-weight: 600;
}

.context-chip__help {
  font-size: 14px;
  color: var(--primary-color);
  opacity: 0.7;
  transition: opacity 0.3s;
}

.context-chip:hover .context-chip__help {
  opacity: 1;
}

.question-box {

  padding: 16px 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.question-box .label {
  color: var(--primary-color);
  font-weight: 600;
}

.question-box .question-text {
  color: var(--text-primary);
  font-size: 16px;
}

/* 卦象展示 */
.gua-display {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 32px;
  padding: 36px;
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
  border-radius: 24px;
  margin-bottom: 32px;
  border: 2px solid var(--border-light);
  position: relative;
  overflow: hidden;
  box-shadow: var(--shadow-lg);
}

.gua-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 24px;
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), rgba(var(--primary-rgb), 0.03));
  border-radius: 20px;
  border: 2px solid rgba(var(--primary-rgb), 0.15);
  position: relative;
}

.gua-symbol-large {
  font-size: 72px;
  line-height: 1;
  margin-bottom: 20px;
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  filter: drop-shadow(0 4px 8px rgba(var(--primary-rgb), 0.2));
}

.gua-details {
  text-align: center;
}

.gua-name-main {
  color: var(--text-primary);
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 8px;
  letter-spacing: 2px;
}

.gua-code-tag {
  display: inline-block;
  padding: 6px 16px;
  background: rgba(var(--primary-rgb), 0.1);
  border: 1px solid rgba(var(--primary-rgb), 0.2);
  border-radius: 999px;
  color: var(--primary-color);
  font-size: 13px;
  font-weight: 600;
}

.gua-deco-line {
  position: absolute;
  top: 50%;
  right: -1px;
  width: 2px;
  height: 60%;
  background: linear-gradient(180deg, transparent, rgba(var(--primary-rgb), 0.3), transparent);
  transform: translateY(-50%);
}

.yao-wrapper {
  display: flex;
  flex-direction: column;
}

.yao-section-title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.yao-section-title__text {
  color: var(--text-primary);
  font-size: 18px;
  font-weight: 700;
  white-space: nowrap;
}

.yao-section-title__line {
  flex: 1;
  height: 2px;
  background: linear-gradient(90deg, var(--border-light), transparent);
}

.yao-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.yao-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 18px 14px;
  background: var(--bg-card);
  border-radius: 16px;
  border: 2px solid var(--border-light);
  transition: all 0.3s ease;
}

.yao-cell:hover {
  border-color: rgba(var(--primary-rgb), 0.3);
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.yao-cell.moving {
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1), rgba(var(--primary-rgb), 0.05));
  border-color: rgba(var(--primary-rgb), 0.4);
  animation: yao-cell-pulse 2s ease-in-out infinite;
}

@keyframes yao-cell-pulse {
  0%, 100% { box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.15); }
  50% { box-shadow: 0 8px 20px rgba(var(--primary-rgb), 0.25); }
}

.yao-cell__position {
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 600;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.yao-cell__symbol {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: rgba(var(--primary-rgb), 0.08);
  border-radius: 12px;
  margin-bottom: 10px;
  border: 2px solid rgba(var(--primary-rgb), 0.15);
}

.yao-symbol-mark {
  font-size: 28px;
  font-weight: 300;
  line-height: 1;
  color: var(--primary-color);
}

.yao-cell.yang .yao-symbol-mark {
  font-weight: 700;
}

.yao-cell.yin .yao-symbol-mark {
  letter-spacing: -2px;
}

.yao-moving-icon {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 20px;
  height: 20px;
  background: var(--primary-color);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 700;
}

.yao-cell__name {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
  text-align: center;
  margin-bottom: 4px;
}

.yao-cell__fushen {
  font-size: 11px;
  color: var(--primary-color);
  font-weight: 600;
  padding: 2px 8px;
  background: rgba(var(--primary-rgb), 0.1);
  border-radius: 8px;
}

.structured-section {
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.structured-section h4 {
  color: var(--primary-color);
  margin-bottom: 14px;
  font-size: 16px;
}

.structured-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 16px;
}

.structured-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  background: rgba(184, 134, 11, 0.08);
  border: 1px solid rgba(184, 134, 11, 0.2);
}

.structured-chip__label {
  color: var(--text-secondary);
  font-size: 12px;
}

.structured-chip__value {
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 600;
}

.moving-line-list {
  display: grid;
  gap: 10px;
  margin-bottom: 16px;
}

.moving-line-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 12px 14px;
  border-radius: 14px;
  background: rgba(212, 175, 55, 0.08);
  border: 1px solid rgba(212, 175, 55, 0.18);
}

.moving-line-card__title {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.moving-line-card__meta {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.6;
}

.line-detail-list {

  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.line-detail-card {
  padding: 14px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.66);
  border: 1px solid var(--border-light);
}

.line-detail-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 8px;
}

.line-detail-card__title {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 14px;
}

.line-detail-card__tags {
  display: inline-flex;
  gap: 6px;
  flex-wrap: wrap;
}

.line-tag {
  min-width: 24px;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
  text-align: center;
  color: #fff;
}

.line-tag--shi {
  background: #8b6914;
}

.line-tag--ying {
  background: #6f42c1;
}

.line-tag--moving {
  background: #d9534f;
}

.line-detail-card__meta {
  display: flex;
  flex-direction: column;
  gap: 4px;
  color: var(--text-secondary);
  font-size: 13px;
}

/* 卦辞 */
.gua-ci-section {
  padding: 32px 36px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.97), rgba(248, 249, 250, 0.99));
  border-radius: 24px;
  margin-bottom: 36px;
  border: 3px solid rgba(var(--primary-rgb), 0.25);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1),
              0 6px 20px rgba(var(--primary-rgb), 0.08),
              inset 0 2px 0 rgba(255, 255, 255, 0.9);
  position: relative;
  overflow: hidden;
}

.gua-ci-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(var(--primary-rgb), 0.5), transparent);
}

.gua-ci-section::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(var(--primary-rgb), 0.25), transparent);
}

.gua-ci-section h4 {
  color: var(--primary-color);
  margin-bottom: 20px;
  font-size: 22px;
  font-weight: 800;
  letter-spacing: 0.8px;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  position: relative;
}

.gua-ci-section h4::after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 0;
  width: 50px;
  height: 3px;
  background: linear-gradient(90deg, var(--primary-color), transparent);
  border-radius: 2px;
}

.gua-ci {
  color: var(--text-primary);
  line-height: 1.9;
  font-size: 17px;
  margin: 0;
  font-weight: 500;
  letter-spacing: 0.4px;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

/* 解读 */
.interpretation-section {
  margin-bottom: 24px;
}

.interpretation-section h4 {
  color: var(--text-primary);
  margin-bottom: 12px;
  font-size: 16px;
}

.interpretation-text {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 14px;
  white-space: pre-wrap;
  margin: 0;
  font-family: inherit;
}

/* AI分析 */
.ai-section {
  margin-bottom: 36px;
  padding: 32px 36px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.99), rgba(248, 249, 250, 0.97));
  border-radius: 24px;
  border-left: 6px solid var(--success-color);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08),
              0 6px 20px rgba(var(--success-color), 0.12),
              inset 0 2px 0 rgba(255, 255, 255, 0.9);
  position: relative;
  overflow: hidden;
}

.ai-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(var(--success-color), 0.5), transparent);
}

.ai-section h4 {
  color: var(--text-primary);
  margin-bottom: 20px;
  font-size: 22px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 800;
  letter-spacing: 0.6px;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.ai-section h4 .el-icon {
  color: var(--success-color);
  font-size: 24px;
  filter: drop-shadow(0 2px 6px rgba(var(--success-color), 0.35));
}

.ai-content {
  color: var(--text-primary);
  line-height: 1.9;
  font-size: 16px;
  white-space: pre-wrap;
  font-weight: 500;
  letter-spacing: 0.4px;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

/* AI解卦按钮区域 */
.ai-action-zone {
  margin-bottom: 36px;
}

.ai-action-card {
  padding: 32px 36px;
  background: linear-gradient(145deg, rgba(255, 255, 255, 0.99), rgba(248, 249, 250, 0.97));
  border-radius: 24px;
  border: 3px solid rgba(var(--primary-rgb), 0.15);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08),
              0 6px 20px rgba(var(--primary-rgb), 0.1),
              inset 0 2px 0 rgba(255, 255, 255, 0.9);
  position: relative;
  overflow: hidden;
}

.ai-action-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
}

.ai-action-content {
  position: relative;
  z-index: 1;
}

.ai-action-content h4 {
  color: var(--text-primary);
  margin-bottom: 16px;
  font-size: 22px;
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 800;
  letter-spacing: 0.6px;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.ai-action-content h4 .el-icon {
  color: var(--primary-color);
  font-size: 24px;
}

.ai-action-desc {
  color: var(--text-secondary);
  line-height: 1.8;
  font-size: 15px;
  margin-bottom: 24px;
  font-weight: 500;
}

.ai-action-content .el-button {
  padding: 16px 48px;
  font-size: 17px;
  font-weight: 700;
  border-radius: 25px;
  background: linear-gradient(135deg, var(--primary-color), rgba(var(--primary-rgb), 0.85));
  border: 2px solid var(--primary-color);
  box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.25);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.ai-action-content .el-button:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 35px rgba(var(--primary-rgb), 0.35);
}

.ai-action-content .el-button:disabled {
  background: var(--text-tertiary);
  border-color: var(--text-tertiary);
  box-shadow: none;
  cursor: not-allowed;
  opacity: 0.6;
}

.ai-action-content .el-button .el-icon {
  font-size: 18px;
}

/* 积分信息 */
.points-info {
  display: flex;
  justify-content: space-between;
  padding: 16px 20px;
  background: var(--bg-secondary);
  border-radius: 16px;
  color: var(--text-secondary);
  font-size: 14px;
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.history-points-note {
  color: var(--text-muted);
}

/* 操作按钮 */

.action-buttons {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.saved-status {
  flex: 1;
  min-height: 48px;
  padding: 12px 18px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(103, 194, 58, 0.12);
  border: 1px solid rgba(103, 194, 58, 0.24);
  color: var(--success-color);
  font-size: 14px;
  font-weight: 600;
}

.saved-status--history {
  background: rgba(var(--primary-rgb), 0.1);
  border-color: rgba(var(--primary-rgb), 0.18);
  color: var(--primary-color);
}


.btn-primary,

.btn-secondary {
  flex: 1;
  padding: 14px 24px;
  border-radius: 25px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s;
  border: none;
  min-height: 44px;
}

.btn-primary {
  background: var(--primary-gradient);
  color: var(--text-primary);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px var(--primary-light-40);
}

.btn-secondary {
  background: var(--bg-secondary);
  color: var(--text-primary);
  border: 1px solid var(--border-light);
}

.btn-secondary:hover {
  background: var(--bg-hover);
}

/* 历史对话框 */
.history-dialog :deep(.el-dialog) {
  border-radius: 20px;
  border: 1px solid var(--border-light);
  background: var(--bg-card);
  box-shadow: var(--shadow-xl);
}

.history-dialog :deep(.el-dialog__header) {
  padding: 20px 20px 16px;
  margin: 0;
  border-bottom: 1px solid var(--border-light);
}

.history-dialog :deep(.el-dialog__title) {
  color: var(--text-primary);
  font-weight: 700;
}

.history-dialog :deep(.el-dialog__body) {
  padding: 16px 20px 20px;
}

.history-dialog :deep(.el-dialog__headerbtn) {
  width: 44px;
  height: 44px;
  top: 12px;
  right: 14px;
}

.history-dialog :deep(.el-dialog__headerbtn:focus-visible) {
  outline: 2px solid var(--primary-light);
  outline-offset: 2px;
  border-radius: 999px;
}

.history-list {
  max-height: min(60vh, 520px);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.history-state {
  padding: 20px 16px;
  text-align: center;
  color: var(--text-secondary);
}

.history-state p {
  margin: 0 0 8px;
  color: var(--text-primary);
  font-weight: 600;
}

.history-state--error {
  border-radius: 14px;
  border: 1px solid rgba(245, 108, 108, 0.18);
  background: rgba(245, 108, 108, 0.06);
}

.history-item {
  display: flex;
  align-items: stretch;
  gap: 12px;
  padding: 6px;
  border-radius: 14px;
  border: 1px solid var(--border-light);
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05), var(--bg-card));
}

.history-select {
  appearance: none;
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent;
  padding: 10px 12px;
  border-radius: 10px;
  text-align: left;
  color: inherit;
  cursor: pointer;
  transition: background 0.2s ease, box-shadow 0.2s ease;
}

.history-select:hover {
  background: var(--bg-secondary);
}

.history-select:focus-visible {
  outline: 2px solid var(--primary-light);
  outline-offset: 2px;
}

.history-main {
  flex: 1;
  min-width: 0;
}

.history-question {
  color: var(--text-primary);
  margin: 0 0 6px 0;
  font-size: 14px;
  line-height: 1.6;
  min-height: calc(1.6em * 2);
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}


.history-gua {
  color: var(--text-secondary);
  margin: 0;
  font-size: 12px;
}

.delete-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  align-self: center;
  min-height: 40px;
  min-width: 40px;
  padding: 8px 12px;
  background: var(--error-bg);
  border: 1px solid rgba(239, 68, 68, 0.18);
  border-radius: 999px;
  color: var(--error-color);
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.3s;
}

.delete-btn:hover {
  background: rgba(239, 68, 68, 0.14);
  border-color: rgba(239, 68, 68, 0.3);
}

.delete-btn:focus-visible {
  outline: 2px solid rgba(239, 68, 68, 0.4);
  outline-offset: 2px;
}

.delete-label {
  line-height: 1;
}


@media (prefers-reduced-motion: reduce) {
  .loading,
  .gua-decoration,
  .yao-line,
  .yao-line.moving,
  .yao-line.moving,
  .btn-submit,
  .delete-btn {
    animation: none !important;
    transition: none !important;
  }

  .yao-line:hover,
  .btn-submit:not(:disabled):hover {
    transform: none !important;
  }
}

/* 响应式 */

@media (max-width: 768px) {
  /* 新增移动端安全区适配 */
  .liuyao-page {
    padding-top: env(safe-area-inset-top);
    padding-bottom: env(safe-area-inset-bottom);
    padding-left: env(safe-area-inset-left);
    padding-right: env(safe-area-inset-right);
  }

  .page-header {
    align-items: stretch;
  }

  .page-title {
    font-size: 28px;
  }

  .form-card,
  .result-card {
    padding: 24px;
  }

  .result-context {
    gap: 8px;
  }

  .context-chip {
    width: 100%;
    justify-content: space-between;
  }

  .advanced-toggle {
    flex-direction: column;
    align-items: flex-start;
  }

  .advanced-toggle__action {
    margin-top: 0;
  }

  .input-grid--double,

  .advanced-grid,
  .manual-grid {
    grid-template-columns: 1fr;
  }


  .gua-display {
    grid-template-columns: 1fr;
    gap: 24px;
    padding: 28px 20px;
  }

  .gua-card {
    padding: 24px 18px;
  }

  .gua-symbol-large {
    font-size: 56px;
    margin-bottom: 16px;
  }

  .gua-name-main {
    font-size: 18px;
    letter-spacing: 1px;
  }

  .gua-code-tag {
    font-size: 12px;
    padding: 4px 12px;
  }

  .gua-deco-line {
    display: none;
  }

  .yao-section-title__text {
    font-size: 16px;
  }

  .yao-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .yao-cell {
    padding: 14px 10px;
  }

  .yao-cell__symbol {
    width: 40px;
    height: 40px;
  }

  .yao-symbol-mark {
    font-size: 24px;
  }

  .yao-moving-icon {
    width: 16px;
    height: 16px;
    font-size: 9px;
  }

  .yao-cell__name {
    font-size: 13px;
  }

  .yao-cell__fushen {
    font-size: 10px;
    padding: 1px 6px;
  }


  .moving-badge {
    padding: 4px 8px;
    font-size: 11px;
  }

  .yao-name {
    font-size: 13px;
  }


  .history-item {
    flex-direction: column;
  }

  .delete-btn {
    align-self: stretch;
    width: 100%;
  }

  .action-buttons {
    flex-direction: column;
  }
}


</style>