<template>
  <div class="hehun-page">
    <div class="container">
      <PageHeroHeader
        title="八字合婚"
        subtitle="把双方信息、结果精度与解锁路径放在同一条清晰链路里，先看趋势，再决定是否继续深入。"
        :icon="Link"
      />


      <!-- 免费预览结果 -->
      <div v-if="freeResult" class="result-section">
        <div class="result-card card-hover">
          <div class="result-header">
            <h2>合婚基础分析</h2>
            <div class="score-display">
              <span class="score-number">{{ freeResult.hehun.score }}</span>
              <span class="score-label">匹配分</span>
            </div>
          </div>

          <div v-if="hasReducedPrecision" class="precision-summary-card precision-summary-card--result">
            <div class="precision-summary-header">
              <el-icon><WarningFilled /></el-icon>
              <div>
                <strong>本次结果包含低精度出生时刻</strong>
                <p>当前结论更适合参考整体关系趋势；如需更细的时柱判断，请补充准确出生时间后重新测算。</p>
              </div>
            </div>
          </div>
          
          <div class="result-level" :class="freeResult.hehun.level">
            {{ freeResult.hehun.level_text }}
          </div>
          
          <p class="result-comment">{{ freeResult.hehun.comment }}</p>

          <div v-if="freeResult.is_local_only" class="free-preview-status">
            <el-icon><WarningFilled /></el-icon>
            <div>
              <strong>本次免费预览暂未写入云端记录</strong>
              <p>已帮你临时保存在当前设备；稍后回到本页仍可继续查看，但个人中心暂不会出现这条记录。</p>
            </div>
          </div>
          
          <div class="bazi-compare">

            <div class="bazi-side">
              <h4>{{ getRoleBaziTitle('male') }}</h4>
              <div class="bazi-pillars">
                <span class="pillar">{{ freeResult.male_bazi.year }}</span>
                <span class="pillar">{{ freeResult.male_bazi.month }}</span>
                <span class="pillar">{{ freeResult.male_bazi.day }}</span>
                <span class="pillar">{{ freeResult.male_bazi.hour }}</span>
              </div>
              <p class="day-master">日主：{{ freeResult.male_bazi.day_master }}</p>
            </div>
            <div class="bazi-divider"><el-icon :size="24"><Link /></el-icon></div>
            <div class="bazi-side">
              <h4>{{ getRoleBaziTitle('female') }}</h4>
              <div class="bazi-pillars">
                <span class="pillar">{{ freeResult.female_bazi.year }}</span>
                <span class="pillar">{{ freeResult.female_bazi.month }}</span>
                <span class="pillar">{{ freeResult.female_bazi.day }}</span>
                <span class="pillar">{{ freeResult.female_bazi.hour }}</span>
              </div>
              <p class="day-master">日主：{{ freeResult.female_bazi.day_master }}</p>
            </div>
          </div>
          
          <div class="suggestion-box">
            <h4><el-icon><Collection /></el-icon> 建议</h4>
            <ul class="suggestion-list">
              <li v-for="(suggestion, idx) in visibleFreeSuggestions" :key="`free-suggestion-${idx}`">
                {{ suggestion }}
              </li>
            </ul>
            <el-button
              v-if="hasMoreFreeSuggestions"
              link
              type="primary"
              class="suggestion-toggle"
              @click="showAllFreeSuggestions = !showAllFreeSuggestions"
            >
              {{ showAllFreeSuggestions ? '收起额外建议' : `展开剩余 ${freeSuggestionList.length - visibleFreeSuggestions.length} 条建议` }}
            </el-button>
          </div>

          
          <div class="upgrade-prompt" :class="{ 'upgrade-prompt--busy': unlockLoading }" :aria-busy="unlockLoading">
            <p>{{ freeResult.preview_hint }}</p>
            <p class="upgrade-note">当前基础分析未启用 AI；若解锁完整版并勾选 AI，系统会优先调用 AI，若 AI 暂不可用则自动切换为规则解读并明确标注。</p>
            <p v-if="pricingStatusText" class="pricing-status" :class="{ 'pricing-status--error': Boolean(pricingError), 'pricing-status--loading': pricingLoading }">
              {{ pricingStatusText }}
            </p>
            <p v-if="unlockLoading" class="upgrade-status upgrade-status--loading">正在解锁详细报告，请稍候...</p>
            <p v-else-if="unlockError" class="upgrade-status upgrade-status--error">{{ unlockError }}</p>
            <el-button class="btn-upgrade" type="primary" :disabled="!canUnlockPremium" :loading="unlockLoading" @click="unlockPremium">
              <template v-if="!unlockLoading">
                <el-icon><Unlock /></el-icon>
              </template>
              <span>{{ unlockLoading ? '正在解锁详细报告...' : '解锁详细报告' }}</span>
              <span v-if="!unlockLoading" class="points-tag">{{ pricingDisplayText }}</span>
            </el-button>

          </div>

          <div class="action-buttons-wrap">
            <div class="action-buttons-heading">
              <span class="action-buttons-heading__eyebrow">下一步动作</span>
              <p>先回看记录，再决定是否继续解锁完整版；结果页和首页都按同一套“记录 / 深入 / 再来一次”节奏走。</p>
            </div>
            <div class="action-buttons action-buttons--free">
              <el-button plain @click="handleFreeResultRecordAction">{{ freeResultRecordButtonText }}</el-button>
              <el-button type="primary" :disabled="!canUnlockPremium" :loading="unlockLoading" @click="unlockPremium">继续深入解读</el-button>
              <el-button @click="openRechargeCenter">去充值</el-button>
              <el-button @click="resetForm">重新测算（清空）</el-button>
            </div>

          </div>

          <WisdomText />
        </div>
      </div>

      <!-- 付费详细结果 -->
      <div v-else-if="premiumResult" class="result-section">
        <div class="result-card premium card-hover">
          <div class="result-header">
            <h2>详细合婚报告</h2>
            <div class="premium-badge">完整版</div>
          </div>

          <div v-if="hasReducedPrecision" class="precision-summary-card precision-summary-card--result">
            <div class="precision-summary-header">
              <el-icon><WarningFilled /></el-icon>
              <div>
                <strong>本次详细报告包含低精度出生时刻</strong>
                <p>系统已尽量保留合婚核心趋势，但部分细分维度仍建议在补齐准确时辰后再复核一次。</p>
              </div>
            </div>
          </div>
          
          <!-- 综合评分 -->
          <div class="score-section">
            <div class="main-score">
              <span class="score-number">{{ premiumResult.hehun.score }}</span>
              <span class="score-label">综合匹配分</span>
              <span class="score-level" :class="premiumResult.hehun.level">
                {{ premiumResult.hehun.level_text }}
              </span>
            </div>
          </div>

          <!-- AI分析 -->
          <div class="ai-section" v-if="premiumResult.ai_analysis">
            <h3><el-icon><Cpu /></el-icon> {{ premiumAnalysisPresentation.title }}</h3>
            <p v-if="premiumAnalysisPresentation.note" class="analysis-engine-note">{{ premiumAnalysisPresentation.note }}</p>
            <div class="ai-content rich-content" v-html="premiumAiAnalysisHtml"></div>
          </div>
          
          <!-- 操作按钮 -->
          <div class="action-buttons-wrap">
            <div class="action-buttons-heading">
              <span class="action-buttons-heading__eyebrow">下一步动作</span>
              <p>详细报告出来后，优先回看记录、导出成果或继续后续服务，不再把主动作拆散在多个区域。</p>
            </div>
            <div class="action-buttons">
              <el-button plain @click="openHehunRecords">查看我的记录</el-button>
              <el-button type="primary" @click="exportReport" :disabled="exporting || !canExportReport">
                <el-icon><Document /></el-icon> {{ exporting ? '导出中...' : '导出报告' }}
              </el-button>
              <ShareCard
                title="八字合婚"
                :summary="hehunShareSummary"
                :tags="hehunShareTags"
                :sharePath="`/hehun?id=${premiumResult.id}`"
              >
                <template #trigger>
                  <el-button>
                    <el-icon><Share /></el-icon> 分享摘要
                  </el-button>
                </template>
              </ShareCard>
              <el-button @click="openDailySuggestion">看今日运势</el-button>
              <el-button @click="resetForm">
                <el-icon><RefreshRight /></el-icon> 重新测算（清空）
              </el-button>
            </div>
          </div>

          <WisdomText />
        </div>
      </div>

      <!-- 输入表单 -->
      <div v-else class="form-section">
        <div v-if="localFreePreviewRecord" class="local-preview-recovery card-hover">
          <div class="local-preview-recovery__body">
            <span class="local-preview-recovery__eyebrow">上次免费预览仍可回看</span>
            <strong>{{ formatHistoryNames(localFreePreviewRecord) }}</strong>
            <p>这条免费预览在 {{ formatDate(localFreePreviewRecord.created_at) }} 暂存到当前设备；云端历史暂未生成，但你仍可继续查看，再决定是否升级完整版。</p>
          </div>
          <div class="local-preview-recovery__actions">
            <el-button plain type="primary" @click="restoreLocalFreePreview">恢复上次结果</el-button>
            <el-button link type="primary" @click="scrollToHistorySection">查看暂存记录</el-button>
          </div>
        </div>

        <div class="form-card card-hover">

          <h2>输入双方出生信息</h2>
          <p class="form-intro">先各自补齐生日，再按记忆精度选择精确时分、大概时段或仅生日模式即可。</p>
          <div class="form-meta-list">
            <span class="form-meta-item">精确时分</span>
            <span class="form-meta-item">大概时段</span>
            <span class="form-meta-item">仅生日趋势</span>
          </div>

          <div class="strategy-summary-card">
            <div class="strategy-summary-card__header">
              <div class="strategy-summary-card__copy">
                <span class="strategy-summary-card__eyebrow">填写策略</span>
                <strong>{{ hehunStrategySummary }}</strong>
                <p>首屏只保留必要说明；想看精度、可信度和完整版路径，再展开细节就好。</p>
              </div>
              <el-button link type="primary" class="strategy-summary-card__toggle" @click="hehunStrategyExpanded = !hehunStrategyExpanded">
                {{ hehunStrategyExpanded ? '收起详情' : '查看详情' }}
              </el-button>
            </div>
            <div v-if="hehunStrategyExpanded" class="strategy-summary-card__details">
              <article v-for="item in hehunStrategyDetails" :key="item.key" class="strategy-detail-item">
                <strong>{{ item.title }}</strong>
                <p>{{ item.description }}</p>
              </article>
            </div>
          </div>

          <!-- 男方信息 -->

          <div class="person-section card-hover">
            <h3 class="person-title">
              <el-icon class="gender-icon"><component :is="resolveRoleIcon('male')" /></el-icon>
              {{ getRolePanelTitle('male') }}
            </h3>

            <p class="person-subtitle">先填生日，再补具体时间或选择时段估算即可。</p>
            <div class="form-row">
              <div class="form-group">
                <label>姓名（可选）</label>
                <el-input
                  v-model="form.maleName"
                  class="hehun-field-control"
                  :placeholder="getRoleNamePlaceholder('male')"

                  maxlength="10"
                  clearable
                  show-word-limit
                />
              </div>
            </div>
            <div class="birth-precision-panel">
              <label class="precision-heading">出生时刻精度</label>
              <el-radio-group
                v-model="form.maleBirthPrecision"
                class="precision-options precision-options--group premium-segment premium-segment--card"
                @change="(value) => handleBirthPrecisionChange('male', value)"
              >
                <el-radio-button
                  v-for="option in birthPrecisionOptions"
                  :key="`male-${option.value}`"
                  :label="option.value"
                  class="precision-option-button"
                >
                  <span class="precision-option-title">{{ option.label }}</span>
                  <span class="precision-option-desc">{{ option.desc }}</span>
                </el-radio-button>
              </el-radio-group>
              <p class="precision-helper">{{ getBirthPrecisionHint(form.maleBirthPrecision) }}</p>
            </div>
            <div class="form-row" data-hehun-field="male-birth">
              <div class="form-group">
                <label>{{ getBirthInputLabel(form.maleBirthPrecision) }} <span class="required">*</span></label>
                <el-date-picker
                  v-model="form.maleBirthDate"
                  class="hehun-field-control"
                  :type="getBirthPickerType(form.maleBirthPrecision)"
                  :placeholder="getBirthPickerPlaceholder(form.maleBirthPrecision)"
                  :format="getBirthPickerFormat(form.maleBirthPrecision)"
                  :value-format="getBirthPickerValueFormat(form.maleBirthPrecision)"
                  clearable
                />
                <p class="field-helper">{{ getBirthFieldHelper(form.maleBirthPrecision) }}</p>
              </div>
            </div>
            <div v-if="form.maleBirthPrecision === 'range'" class="time-range-panel" data-hehun-field="male-range">
              <label class="time-range-label">大概出生时段 <span class="required">*</span></label>
              <el-radio-group v-model="form.maleBirthTimeRange" class="time-range-options time-range-options--group premium-segment premium-segment--card">
                <el-radio-button
                  v-for="option in birthTimeRangeOptions"
                  :key="`male-range-${option.value}`"
                  :label="option.value"
                  class="time-range-chip-button"
                >
                  <span>{{ option.label }}</span>
                  <small>{{ option.hint }}</small>
                </el-radio-button>
              </el-radio-group>
              <p v-if="!form.maleBirthTimeRange" class="field-helper field-helper--warning">请选择{{ getRoleLabel('male') }}的大概出生时段后再开始分析。</p>
            </div>

            <div class="precision-confidence" :class="`precision-confidence--${form.maleBirthPrecision}`">
              <span class="confidence-badge">{{ getBirthPrecisionBadge(form.maleBirthPrecision) }}</span>
              <p>{{ getBirthConfidenceCopy(form.maleBirthPrecision, getRoleLabel('male')) }}</p>
            </div>

          </div>

          
          <!-- 女方信息 -->
          <div class="person-section">
            <h3 class="person-title">
              <el-icon class="gender-icon"><component :is="resolveRoleIcon('female')" /></el-icon>
              {{ getRolePanelTitle('female') }}
            </h3>

            <p class="person-subtitle">若只记得大概时段，也可以先按时段估算，后续再补精确时间。</p>
            <div class="form-row">
              <div class="form-group">
                <label>姓名（可选）</label>
                <el-input
                  v-model="form.femaleName"
                  class="hehun-field-control"
                  :placeholder="getRoleNamePlaceholder('female')"

                  maxlength="10"
                  clearable
                  show-word-limit
                />
              </div>
            </div>
            <div class="birth-precision-panel">
              <label class="precision-heading">出生时刻精度</label>
              <el-radio-group
                v-model="form.femaleBirthPrecision"
                class="precision-options precision-options--group premium-segment premium-segment--card"
                @change="(value) => handleBirthPrecisionChange('female', value)"
              >
                <el-radio-button
                  v-for="option in birthPrecisionOptions"
                  :key="`female-${option.value}`"
                  :label="option.value"
                  class="precision-option-button"
                >
                  <span class="precision-option-title">{{ option.label }}</span>
                  <span class="precision-option-desc">{{ option.desc }}</span>
                </el-radio-button>
              </el-radio-group>
              <p class="precision-helper">{{ getBirthPrecisionHint(form.femaleBirthPrecision) }}</p>
            </div>
            <div class="form-row" data-hehun-field="female-birth">
              <div class="form-group">
                <label>{{ getBirthInputLabel(form.femaleBirthPrecision) }} <span class="required">*</span></label>
                <el-date-picker
                  v-model="form.femaleBirthDate"
                  class="hehun-field-control"
                  :type="getBirthPickerType(form.femaleBirthPrecision)"
                  :placeholder="getBirthPickerPlaceholder(form.femaleBirthPrecision)"
                  :format="getBirthPickerFormat(form.femaleBirthPrecision)"
                  :value-format="getBirthPickerValueFormat(form.femaleBirthPrecision)"
                  clearable
                />
                <p class="field-helper">{{ getBirthFieldHelper(form.femaleBirthPrecision) }}</p>
              </div>
            </div>
            <div v-if="form.femaleBirthPrecision === 'range'" class="time-range-panel" data-hehun-field="female-range">
              <label class="time-range-label">大概出生时段 <span class="required">*</span></label>
              <el-radio-group v-model="form.femaleBirthTimeRange" class="time-range-options time-range-options--group premium-segment premium-segment--card">
                <el-radio-button
                  v-for="option in birthTimeRangeOptions"
                  :key="`female-range-${option.value}`"
                  :label="option.value"
                  class="time-range-chip-button"
                >
                  <span>{{ option.label }}</span>
                  <small>{{ option.hint }}</small>
                </el-radio-button>
              </el-radio-group>
              <p v-if="!form.femaleBirthTimeRange" class="field-helper field-helper--warning">请选择{{ getRoleLabel('female') }}的大概出生时段后再开始分析。</p>
            </div>
            <div class="precision-confidence" :class="`precision-confidence--${form.femaleBirthPrecision}`">
              <span class="confidence-badge">{{ getBirthPrecisionBadge(form.femaleBirthPrecision) }}</span>
              <p>{{ getBirthConfidenceCopy(form.femaleBirthPrecision, getRoleLabel('female')) }}</p>
            </div>

          </div>

          
          <!-- 选项 -->
          <div class="options-section">
            <h3 class="options-title">分析方案与解锁偏好</h3>
            <div class="option-plan-card">
              <div class="plan-badge-row">
                <span class="plan-badge plan-badge--free">免费预览</span>
                <span class="plan-badge plan-badge--premium">完整版</span>
              </div>
              <p class="plan-summary">先看免费趋势，再决定是否解锁完整版和 AI 深度分析。</p>
            </div>
            <div class="option-item" :class="{ active: form.useAi }">
              <el-checkbox v-model="form.useAi" class="option-checkbox">
                <span class="option-title">解锁完整版时启用 AI 深度分析</span>
                <span class="option-desc">当前免费预览固定不启用 AI；勾选后会优先使用 AI，若服务不可用则在结果页明确标注为规则解读。</span>
              </el-checkbox>
            </div>
          </div>

          
          <!-- 定价信息 -->
          <div class="pricing-info" v-if="pricingLoading || normalizedPricing || pricingError">
            <div class="pricing-info-content">
              <div class="pricing-info-main">
                <div class="pricing-row">
                  <span>本次消耗：</span>
                  <span class="points">{{ pricingDisplayText }}</span>
                  <span v-if="normalizedPricing?.discount > 0" class="discount">-{{ normalizedPricing.discount }}%</span>
                </div>
                <p v-if="pricingStatusText" class="pricing-reason">{{ pricingStatusText }}</p>
                <p v-else-if="normalizedPricing?.reason" class="pricing-reason">{{ normalizedPricing.reason }}</p>
              </div>
              <div class="pricing-info-details">
                <p class="pricing-info-title">解锁完整版您将获得：</p>
                <ul class="pricing-info-list">
                  <li><el-icon><Check /></el-icon> 双方八字命盘的详细对比与匹配度打分</li>
                  <li><el-icon><Check /></el-icon> 五大维度（性格、家庭、事业等）深度解析</li>
                  <li v-if="form.useAi"><el-icon><Check /></el-icon> AI 综合评估与专属化解建议</li>
                  <li><el-icon><Check /></el-icon> 永久保存在您的历史记录中，随时查看</li>
                </ul>
                <p class="pricing-info-guarantee"><el-icon><Shield /></el-icon> 失败保障：若解锁失败或未生成完整报告，将自动退还积分。</p>
              </div>
            </div>
          </div>

          <div v-if="hasReducedPrecision" class="precision-summary-card">
            <div class="precision-summary-header">
              <el-icon><WarningFilled /></el-icon>
              <div>
                <strong>当前为低精度合婚输入</strong>
                <p>可以先看关系趋势，但涉及时柱的细节判断会更保守，请尽量补充更准确的出生时间。</p>
              </div>
            </div>
            <div class="precision-summary-list">
              <div v-for="item in precisionSummaryList" :key="item.role" class="precision-summary-item">
                <span class="summary-role">{{ item.role }}</span>
                <div class="summary-copy">
                  <strong>{{ item.modeLabel }}</strong>
                  <span>{{ item.detail }}</span>
                </div>
                <span class="summary-trust">{{ item.confidence }}</span>
              </div>
            </div>
          </div>

          
          <!-- 提交按钮 -->
          <section v-if="hehunSubmitIssues.length" class="submit-summary-card" role="alert" aria-live="assertive">
            <div class="submit-summary-card__header">
              <div>
                <strong>开始分析前还差这几步</strong>
                <p>{{ hehunSubmitSummaryText }}</p>
              </div>
              <el-icon><WarningFilled /></el-icon>
            </div>
            <div class="submit-summary-card__actions">
              <button
                v-for="issue in hehunSubmitIssues"
                :key="issue.key"
                type="button"
                class="submit-summary-card__action"
                @click="handleHehunIssue(issue)"
              >
                <span>{{ issue.actionLabel }}</span>
                <small>{{ issue.message }}</small>
              </button>
            </div>
          </section>

          <el-button
            class="btn-submit"
            type="primary"
            :loading="isLoading"
            :disabled="isLoading"
            @click="submitForm"
          >
            <template v-if="!isLoading">
              <el-icon><Link /></el-icon>
            </template>
            <span>{{ isLoading ? '正在分析中...' : '开始合婚分析' }}</span>
          </el-button>

          
          <p class="form-hint">
            <el-icon><Collection /></el-icon> 首次查看基础分析免费；最近一次结果会先保存在当前设备，云端历史可用时也会同步展示，方便回看后再决定是否解锁完整版。
          </p>

        </div>
      </div>

      <!-- 历史记录 -->
      <div ref="historySectionRef" class="history-section" v-if="historyLoaded || historyLoading || historyError">
        <div class="history-header">
          <div>
            <h3>历史记录</h3>
            <p v-if="localFreePreviewRecord" class="history-header-note">含 1 条当前设备暂存的免费预览，避免离开页面后找不到刚出的结果。</p>
          </div>
          <el-button v-if="historyError" type="primary" link @click="loadHistory">重新加载</el-button>
        </div>
        <div v-if="historyLoading" class="history-state">
          <p>正在加载历史记录...</p>
          <span>最近的合婚分析会在这里展示。</span>
        </div>
        <div v-else-if="history.length === 0 && historyError" class="history-state history-state--error">
          <p>{{ historyError }}</p>
          <span>可以稍后重试，或重新做一次合婚分析生成新记录。</span>
        </div>
        <div v-else-if="history.length === 0" class="history-state">
          <p>还没有合婚记录</p>
          <span>完成一次分析后，这里会展示最近的 5 条记录。</span>
        </div>
        <div v-else>
          <div v-if="historyError" class="history-inline-warning">
            <p>{{ historyError }}</p>
            <span>云端历史暂时不可用，你仍可先继续查看当前设备暂存的免费预览。</span>
          </div>
          <div class="history-list">

          <button
            v-for="item in history"
            :key="item.id"
            type="button"
            class="history-item"
            :class="{ 'is-active': activeHistoryId === item.id }"
            @click="loadHistoryDetail(item)"
          >
            <div class="history-main">
              <div class="history-topline">
                <span class="history-names">{{ formatHistoryNames(item) }}</span>
                <div class="history-badges">
                  <span class="history-badge history-badge--tier" :class="`history-badge--${item.tier}`">
                    <el-icon><Lock v-if="item.is_premium" /><Unlock v-else /></el-icon>
                    {{ item.typeLabel }}
                  </span>
                  <span class="history-badge history-badge--ai" :class="[`history-badge--${item.analysisState}`, { 'history-badge--muted': item.analysisState === 'none' }]">
                    <el-icon><CircleCheckFilled v-if="item.analysisState === 'ai'" /><Cpu v-else /></el-icon>
                    {{ item.analysisBadgeText }}
                  </span>
                </div>
              </div>
              <div class="history-meta">
                <span><el-icon><Calendar /></el-icon>{{ formatDate(item.created_at) }}</span>
                <span><el-icon><StarFilled /></el-icon>{{ item.score }}分{{ item.level_text ? ` · ${item.level_text}` : '' }}</span>
                <span>{{ item.accessLabel }}</span>
              </div>
              <p class="history-summary">{{ item.summary }}</p>
            </div>
            <span class="history-action">
              {{ item.ctaLabel }}
              <el-icon><ArrowRight /></el-icon>
            </span>
          </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import DOMPurify from 'dompurify'
import { Male, Female, UserFilled, Unlock, Lock, Link, RefreshRight, Document, Collection, Present, Cpu, WarningFilled, Calendar, ArrowRight, StarFilled, CircleCheckFilled, Share } from '@element-plus/icons-vue'

import { getHehunPricing, calculateHehun, getHehunHistory, exportHehunReport } from '../api'
import BackButton from '../components/BackButton.vue'
import ShareCard from '../components/ShareCard.vue'
import WisdomText from '../components/WisdomText.vue'
import { trackPageView, trackEvent, trackSubmit, trackError } from '../utils/tracker'



/**
 * HTML净化函数 - 防止XSS攻击
 * 使用DOMPurify库进行专业清理
 */
const router = useRouter()
const HEHUN_LOCAL_FREE_PREVIEW_STORAGE_KEY = 'hehun_local_free_preview_v1'

const sanitizeHtml = (html) => {

  if (!html) return ''
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS: ['b', 'i', 'em', 'strong', 'u', 'p', 'br', 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li'],
    ALLOWED_ATTR: ['class', 'style']
  })
}


const birthPrecisionOptions = [
  { value: 'exact', label: '精确时分', desc: '已知具体出生时间，结果最完整' },
  { value: 'range', label: '大概时段', desc: '记得是早上或晚上，可先按时段估算' },
  { value: 'unknown', label: '未知时辰', desc: '只有生日，也能先看合婚趋势' },
]

const birthTimeRangeOptions = [
  { value: 'before-dawn', label: '凌晨', hint: '00:00-05:59', time: '03:30' },
  { value: 'morning', label: '早晨', hint: '06:00-08:59', time: '07:30' },
  { value: 'forenoon', label: '上午', hint: '09:00-11:59', time: '10:30' },
  { value: 'noon', label: '中午', hint: '12:00-13:59', time: '12:30' },
  { value: 'afternoon', label: '下午', hint: '14:00-17:59', time: '15:30' },
  { value: 'evening', label: '晚上', hint: '18:00-23:59', time: '19:30' },
]

const birthTimeRangeMap = birthTimeRangeOptions.reduce((acc, option) => {
  acc[option.value] = option
  return acc
}, {})

// 表单数据
const form = reactive({
  maleName: '',
  maleBirthDate: '',
  maleBirthPrecision: 'exact',
  maleBirthTimeRange: '',
  femaleName: '',
  femaleBirthDate: '',
  femaleBirthPrecision: 'exact',
  femaleBirthTimeRange: '',
  useAi: true,
})

const hehunStrategyExpanded = ref(false)
const hehunSubmitIssues = ref([])

const roleCopyMap = {
  male: {
    short: '男方',
    panel: '男方信息',
    bazi: '男方八字',
    namePlaceholder: '输入男方姓名',
  },
  female: {
    short: '女方',
    panel: '女方信息',
    bazi: '女方八字',
    namePlaceholder: '输入女方姓名',
  },
}

const getRoleCopy = (role) => roleCopyMap[role] || roleCopyMap.male
const getRoleLabel = (role) => getRoleCopy(role).short
const getRolePanelTitle = (role) => getRoleCopy(role).panel
const getRoleBaziTitle = (role) => getRoleCopy(role).bazi
const getRoleNamePlaceholder = (role) => getRoleCopy(role).namePlaceholder
const resolveRoleIcon = (role) => {
  return role === 'female' ? Female : Male
}


// 状态

const isLoading = ref(false)
const exporting = ref(false)
const freeResult = ref(null)
const premiumResult = ref(null)
const pricing = ref(null)
const pricingLoading = ref(true)
const pricingError = ref('')
const unlockLoading = ref(false)
const unlockError = ref(null)
const history = ref([])
const historyLoading = ref(false)
const historyLoaded = ref(false)
const historyError = ref('')
const activeHistoryId = ref(null)
const historySectionRef = ref(null)
const localFreePreview = ref(null)
const showAllFreeSuggestions = ref(false)


const historyTierCopy = {

  free: { label: '免费预览', cta: '查看基础预览' },
  premium: { label: '完整版', cta: '查看完整报告' },
  vip: { label: 'VIP完整版', cta: '查看会员报告' },
}

// 维度名称映射
const dimensionNames = {
  year: '生肖契合',
  day: '日柱关系',
  wuxing: '五行互补',
  hechong: '干支配合',
  nayin: '纳音互感',
  shensha: '神煞互补',
  traditional: '传统合婚',
}

const getBirthPrecisionLabel = (precision) => {
  if (precision === 'range') return '大概时段'
  if (precision === 'unknown') return '未知时辰'
  return '精确时分'
}

const getBirthPrecisionBadge = (precision) => {
  if (precision === 'range') return '中可信'
  if (precision === 'unknown') return '趋势参考'
  return '高可信'
}

const getBirthInputLabel = (precision) => (precision === 'exact' ? '出生日期与时间' : '出生日期')
const getBirthPickerType = (precision) => (precision === 'exact' ? 'datetime' : 'date')
const getBirthPickerPlaceholder = (precision) => (precision === 'exact' ? '选择出生日期时间（精确到分钟）' : '选择出生日期')
const getBirthPickerFormat = (precision) => (precision === 'exact' ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD')
const getBirthPickerValueFormat = (precision) => (precision === 'exact' ? 'YYYY-MM-DD HH:mm' : 'YYYY-MM-DD')


const getBirthPrecisionHint = (precision) => {
  if (precision === 'range') {
    return '若只记得大概是清晨、下午或晚上，可先选择时段；系统会用代表时刻估算时柱。'
  }
  if (precision === 'unknown') {
    return '若完全不清楚出生时辰，也可以只填生日，系统会按中午排盘并降低可信度提示。'
  }
  return '填写到分钟可获得更完整的时柱、流年和婚配细节判断。'
}

const getBirthFieldHelper = (precision) => {
  if (precision === 'range') {
    return '先选择生日，再显式选择一个大概出生时段。'
  }
  if (precision === 'unknown') {
    return '仅用生日先看趋势，涉及时柱的结论会保守处理。'
  }
  return '建议尽量填写准确时间，减少时柱偏差。'
}

const normalizeBirthInputValue = (value, nextPrecision) => {
  const trimmed = String(value || '').trim()
  if (!trimmed) {
    return ''
  }

  const match = trimmed.match(/^(\d{4}-\d{2}-\d{2})(?:[ T](\d{2}):(\d{2})(?::\d{2})?)?$/)
  if (!match) {
    return nextPrecision === 'exact' ? trimmed : trimmed.slice(0, 10)
  }

  const [, date, hour, minute] = match
  if (nextPrecision === 'exact') {
    return hour && minute ? `${date} ${hour}:${minute}` : ''
  }


  return date
}

const resolveStoredTimeRange = (birthValue = '', fallback = '') => {
  const trimmed = String(birthValue || '').trim()
  const match = trimmed.match(/^\d{4}-\d{2}-\d{2}[ T](\d{2}):(\d{2})/)
  if (!match) {
    return fallback
  }

  return resolveTimeRangeByClock(`${match[1]}:${match[2]}`)
}

const handleBirthPrecisionChange = (role, nextPrecision) => {
  const birthDateKey = `${role}BirthDate`
  const precisionKey = `${role}BirthPrecision`
  const timeRangeKey = `${role}BirthTimeRange`
  const currentValue = form[birthDateKey]

  form[precisionKey] = nextPrecision
  form[birthDateKey] = normalizeBirthInputValue(currentValue, nextPrecision)
  form[timeRangeKey] = ''
}


const getBirthConfidenceCopy = (precision, roleLabel) => {
  if (precision === 'range') {
    return `${roleLabel}当前按大概时段估算，适合先看关系趋势；涉及时柱的细项判断会保守处理。`
  }
  if (precision === 'unknown') {
    return `${roleLabel}当前只提供生日，系统会默认按中午排盘，结论更适合做方向参考。`
  }
  return `${roleLabel}已使用精确时间输入，合婚结果可信度最高。`
}

const resolveBirthDatePayload = (value, precision, timeRange) => {
  if (!value) {
    return ''
  }

  if (precision === 'exact') {
    return value.replace('T', ' ')
  }

  const dateOnly = value.slice(0, 10)
  if (precision === 'unknown') {
    return dateOnly
  }

  const matchedRange = birthTimeRangeMap[timeRange]
  return matchedRange ? `${dateOnly} ${matchedRange.time}` : ''
}


const resolveTimeRangeByClock = (clock = '') => {
  const [hour = '12'] = clock.split(':')
  const parsedHour = Number(hour)

  if (parsedHour < 6) return 'before-dawn'
  if (parsedHour < 9) return 'morning'
  if (parsedHour < 12) return 'forenoon'
  if (parsedHour < 14) return 'noon'
  if (parsedHour < 18) return 'afternoon'
  return 'evening'
}

const hydrateBirthState = (birthDate) => {
  if (!birthDate) {
    return {
      value: '',
      precision: 'exact',
      timeRange: '',
    }
  }

  const trimmed = String(birthDate).trim()

  if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
    return {
      value: trimmed,
      precision: 'unknown',
      timeRange: '',
    }
  }

  const match = trimmed.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})(?::\d{2})?$/)
  if (!match) {
    return {
      value: normalizeBirthInputValue(trimmed, 'exact'),
      precision: 'exact',
      timeRange: '',
    }
  }

  const [, date, hour, minute] = match
  return {
    value: `${date} ${hour}:${minute}`,
    precision: 'exact',
    timeRange: resolveTimeRangeByClock(`${hour}:${minute}`),
  }
}



const precisionSummaryList = computed(() => ([
  {
    role: getRoleLabel('male'),
    modeLabel: getBirthPrecisionLabel(form.maleBirthPrecision),
    confidence: getBirthPrecisionBadge(form.maleBirthPrecision),
    detail: getBirthConfidenceCopy(form.maleBirthPrecision, getRoleLabel('male')),
  },
  {
    role: getRoleLabel('female'),
    modeLabel: getBirthPrecisionLabel(form.femaleBirthPrecision),
    confidence: getBirthPrecisionBadge(form.femaleBirthPrecision),
    detail: getBirthConfidenceCopy(form.femaleBirthPrecision, getRoleLabel('female')),
  },
]))

const hasReducedPrecision = computed(() => {
  return form.maleBirthPrecision !== 'exact' || form.femaleBirthPrecision !== 'exact'
})

const buildHehunPayload = ({ tier, useAi }) => ({
  maleName: form.maleName || getRoleLabel('male'),
  maleBirthDate: resolveBirthDatePayload(form.maleBirthDate, form.maleBirthPrecision, form.maleBirthTimeRange),
  maleBirthPrecision: form.maleBirthPrecision,
  maleBirthTimeRange: form.maleBirthTimeRange,
  femaleName: form.femaleName || getRoleLabel('female'),
  femaleBirthDate: resolveBirthDatePayload(form.femaleBirthDate, form.femaleBirthPrecision, form.femaleBirthTimeRange),
  femaleBirthPrecision: form.femaleBirthPrecision,
  femaleBirthTimeRange: form.femaleBirthTimeRange,
  tier,
  useAi,
})

const normalizeFingerprintText = (value = '') => String(value || '').trim().toLowerCase()
const buildHehunFingerprint = ({ maleName = '', maleBirthDate = '', femaleName = '', femaleBirthDate = '', score = 0, level = '' } = {}) => ([
  normalizeFingerprintText(maleName),
  normalizeFingerprintText(maleBirthDate),
  normalizeFingerprintText(femaleName),
  normalizeFingerprintText(femaleBirthDate),
  Number(score || 0),
  normalizeFingerprintText(level),
].join('|'))

const readLocalFreePreview = () => {
  try {
    const rawValue = localStorage.getItem(HEHUN_LOCAL_FREE_PREVIEW_STORAGE_KEY)
    if (!rawValue) {
      return null
    }

    const parsedValue = JSON.parse(rawValue)
    const record = parsedValue?.record || parsedValue
    return record && typeof record === 'object' ? record : null
  } catch (error) {
    return null
  }
}

const persistLocalFreePreview = (record) => {
  localFreePreview.value = record

  try {
    localStorage.setItem(HEHUN_LOCAL_FREE_PREVIEW_STORAGE_KEY, JSON.stringify({
      version: 1,
      record,
    }))
  } catch (error) {
  }
}

const clearLocalFreePreview = () => {
  localFreePreview.value = null

  try {
    localStorage.removeItem(HEHUN_LOCAL_FREE_PREVIEW_STORAGE_KEY)
  } catch (error) {
  }
}

const buildLocalFreePreviewRecord = (freePayload) => {
  const maleBirthDate = resolveBirthDatePayload(form.maleBirthDate, form.maleBirthPrecision, form.maleBirthTimeRange)
  const femaleBirthDate = resolveBirthDatePayload(form.femaleBirthDate, form.femaleBirthPrecision, form.femaleBirthTimeRange)
  const score = Number(freePayload?.hehun?.score ?? 0)
  const level = freePayload?.hehun?.level || ''
  const createdAt = freePayload?.created_at || freePayload?.create_time || new Date().toISOString()

  return {
    id: freePayload?.id || `local-free-${Date.now()}`,
    tier: 'free',
    is_local_only: true,
    male_name: form.maleName || '',
    female_name: form.femaleName || '',
    male_birth_date: maleBirthDate,
    female_birth_date: femaleBirthDate,
    male_birth_precision: form.maleBirthPrecision,
    female_birth_precision: form.femaleBirthPrecision,
    male_birth_time_range: form.maleBirthTimeRange,
    female_birth_time_range: form.femaleBirthTimeRange,
    score,
    level,
    level_text: freePayload?.hehun?.level_text || '',
    result: freePayload?.hehun || {},
    male_bazi: freePayload?.male_bazi || {},
    female_bazi: freePayload?.female_bazi || {},
    pricing: freePayload?.pricing || null,
    created_at: createdAt,
    create_time: createdAt,
    fingerprint: buildHehunFingerprint({
      maleName: form.maleName || '',
      maleBirthDate,
      femaleName: form.femaleName || '',
      femaleBirthDate,
      score,
      level,
    }),
  }
}

const isBirthInputComplete = (role) => {

  const birthDateValue = form[`${role}BirthDate`]
  const precision = form[`${role}BirthPrecision`]

  if (!birthDateValue) {
    return false
  }

  if (precision === 'range') {
    return Boolean(form[`${role}BirthTimeRange`])
  }

  return true
}

// 表单验证
const isFormValid = computed(() => {
  return isBirthInputComplete('male') && isBirthInputComplete('female')
})

const hehunStrategySummary = computed(() => {
  const accuracyText = hasReducedPrecision.value ? '当前包含估算输入' : '当前为双精确输入'
  const unlockText = form.useAi ? '解锁时优先走 AI 深度分析' : '解锁时走规则版完整版'
  return `${accuracyText}，先看趋势，再决定是否继续解锁；${unlockText}。`
})

const hehunStrategyDetails = computed(() => ([
  {
    key: 'precision',
    title: '出生时间怎么填最合适',
    description: '知道具体出生时间请选择"精确时分"，结果最准确；只记得大概时段（如早晨、晚上）也可以，系统会自动标注可信度供参考。'
  },
  {
    key: 'flow',
    title: '如何选择分析方案',
    description: '免费预览先展示基础匹配趋势和简单建议，确认值得深入了解后，再解锁完整版获取详细的性格、家庭、事业等五维分析。'
  },
  {
    key: 'ai',
    title: 'AI 深度分析说明',
    description: form.useAi
      ? '已开启 AI 深度分析，解锁后将优先调用 AI 提供个性化解读；如 AI 服务暂不可用，系统会自动切换为传统规则解读并明确提示。'
      : '未开启 AI 深度分析，解锁后将提供传统规则版详细分析；如需更个性化的 AI 解读，可在解锁后重新开启。'
  }
]))

const hehunShareSummary = computed(() => {
  if (!premiumResult.value) return '我在太初命理测算了八字合婚，结果很准！'
  const score = premiumResult.value.hehun?.score || 0
  const level = premiumResult.value.hehun?.level_text || ''
  return `我们的合婚匹配度高达${score}分（${level}），快来看看你们的缘分吧！`
})

const hehunShareTags = computed(() => {
  if (!premiumResult.value) return []
  const tags = []
  if (premiumResult.value.hehun?.score) tags.push(`匹配度${premiumResult.value.hehun.score}分`)
  if (premiumResult.value.hehun?.level_text) tags.push(premiumResult.value.hehun.level_text)
  return tags
})

// 格式化日期时间

const hehunSubmitSummaryText = computed(() => {
  if (!hehunSubmitIssues.value.length) {
    return ''
  }

  return `已整理出 ${hehunSubmitIssues.value.length} 个待处理项，点一下即可直接定位。`
})

const normalizePricingData = (rawPricing) => {
  if (!rawPricing) {
    return null
  }

  if (typeof rawPricing.final === 'number') {
    return {
      final: rawPricing.final,
      original: rawPricing.original ?? rawPricing.final,
      discount: rawPricing.discount ?? 0,
      reason: rawPricing.reason || '',
      isVip: Boolean(rawPricing.is_vip),
    }
  }

  if (typeof rawPricing.unlock_points === 'number') {
    return {
      final: rawPricing.unlock_points,
      original: rawPricing.unlock_points,
      discount: rawPricing.discount_info?.percent ?? 0,
      reason: rawPricing.discount_info?.reason || '',
      isVip: Boolean(rawPricing.is_vip),
    }
  }

  const premiumTier = rawPricing.tier?.premium
  if (!premiumTier) {
    return null
  }

  return {
    final: Number(premiumTier.price ?? 0),
    original: Number(premiumTier.original_price ?? premiumTier.price ?? 0),
    discount: Number(premiumTier.discount?.percent ?? 0),
    reason: premiumTier.discount?.reason || '',
    isVip: Boolean(rawPricing.user_status?.is_vip),
  }
}

const normalizedPricing = computed(() => normalizePricingData(freeResult.value?.pricing || pricing.value))
const pricingStatusText = computed(() => {
  if (normalizedPricing.value) {
    return ''
  }

  if (pricingLoading.value) {
    return '完整版价格加载中，请稍候后再解锁。'
  }

  return pricingError.value || '完整版价格暂时不可用，请稍后重试。'
})

const pricingDisplayText = computed(() => {
  if (normalizedPricing.value) {
    return normalizedPricing.value.final > 0 ? `${normalizedPricing.value.final} 积分` : 'VIP 免费'
  }

  if (pricingLoading.value) {
    return '加载中...'
  }

  return '价格待确认'
})

const canExportReport = computed(() => Boolean(premiumResult.value?.id))
const canUnlockPremium = computed(() => Boolean(freeResult.value) && Boolean(normalizedPricing.value) && !isLoading.value && !unlockLoading.value)
const freeResultRecordButtonText = computed(() => freeResult.value?.is_local_only ? '查看本机暂存结果' : '查看我的记录')
const freeSuggestionList = computed(() => {

  const suggestions = freeResult.value?.hehun?.suggestions
  return Array.isArray(suggestions) ? suggestions.filter((item) => typeof item === 'string' && item.trim()) : []
})
const visibleFreeSuggestions = computed(() => {
  return showAllFreeSuggestions.value ? freeSuggestionList.value : freeSuggestionList.value.slice(0, 3)
})
const hasMoreFreeSuggestions = computed(() => freeSuggestionList.value.length > visibleFreeSuggestions.value.length)
const premiumUnlockMessage = computed(() => {


  const points = normalizedPricing.value?.final
  if (!Number.isFinite(points)) {
    return '完整版价格暂未确认，请稍后再试。'
  }

  if (points <= 0) {
    return form.useAi
      ? '您当前可免费解锁详细报告，并优先启用 AI 深度分析；若 AI 暂不可用，将自动切换为规则解读并明确标注，是否继续？'
      : '您当前可免费解锁详细报告，是否继续？'
  }

  return form.useAi
    ? `解锁详细报告将消耗 ${points} 积分，并优先启用 AI 深度分析；若 AI 暂不可用，将自动切换为规则解读并明确标注，是否继续？`
    : `解锁详细报告将消耗 ${points} 积分，是否继续？`
})

const clearUnlockFeedback = () => {
  unlockError.value = null
  unlockLoading.value = false
}

const hasUnsavedDraft = computed(() => {
  const hasFilledValue = [
    form.maleName,
    form.maleBirthDate,
    form.maleBirthTimeRange,
    form.femaleName,
    form.femaleBirthDate,
    form.femaleBirthTimeRange,
  ].some((value) => String(value || '').trim())

  return hasFilledValue || form.maleBirthPrecision !== 'exact' || form.femaleBirthPrecision !== 'exact'
})

const clearHehunSubmitIssues = () => {
  hehunSubmitIssues.value = []
}

const focusHehunField = async (selector) => {
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

const buildHehunSubmitIssues = () => {
  const issues = []

  if (!form.maleBirthDate) {
    issues.push({
      key: 'male-birth',
      actionLabel: `补充${getRoleLabel('male')}生日`,
      message: `${getRoleLabel('male')}还缺出生日期信息。`,
      selector: '[data-hehun-field="male-birth"]'
    })
  }

  if (form.maleBirthPrecision === 'range' && !form.maleBirthTimeRange) {
    issues.push({
      key: 'male-range',
      actionLabel: `选择${getRoleLabel('male')}时段`,
      message: `当前是大概时段模式，还需要明确选择${getRoleLabel('male')}的出生时段。`,
      selector: '[data-hehun-field="male-range"]'
    })
  }

  if (!form.femaleBirthDate) {
    issues.push({
      key: 'female-birth',
      actionLabel: `补充${getRoleLabel('female')}生日`,
      message: `${getRoleLabel('female')}还缺出生日期信息。`,
      selector: '[data-hehun-field="female-birth"]'
    })
  }

  if (form.femaleBirthPrecision === 'range' && !form.femaleBirthTimeRange) {
    issues.push({
      key: 'female-range',
      actionLabel: `选择${getRoleLabel('female')}时段`,
      message: `当前是大概时段模式，还需要明确选择${getRoleLabel('female')}的出生时段。`,
      selector: '[data-hehun-field="female-range"]'
    })
  }

  return issues
}

const handleHehunIssue = (issue) => {
  focusHehunField(issue?.selector)
}

const openHehunRecords = () => {
  router.push('/profile')
}

const openRechargeCenter = () => {
  router.push('/recharge')
}

const openDailySuggestion = () => {
  router.push('/daily')
}

const scrollToHistorySection = async () => {
  await nextTick()
  historySectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const restoreLocalFreePreview = async () => {
  if (!localFreePreviewRecord.value) {
    return
  }

  applyHistoryDetail(localFreePreviewRecord.value)
  await nextTick()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleFreeResultRecordAction = async () => {
  if (freeResult.value?.is_local_only) {
    await scrollToHistorySection()
    ElMessage.info('这条免费预览当前仅保存在本机，可在本页历史区继续查看。')
    return
  }

  openHehunRecords()
}

watch([

  () => form.maleBirthDate,
  () => form.maleBirthTimeRange,
  () => form.maleBirthPrecision,
  () => form.femaleBirthDate,
  () => form.femaleBirthTimeRange,
  () => form.femaleBirthPrecision,
  () => form.useAi,
  roleDisplayMode
], () => {
  if (hehunSubmitIssues.value.length) {
    clearHehunSubmitIssues()
  }
})

const escapeHtml = (value = '') => String(value)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;')
  .replace(/'/g, '&#39;')

const hasAiContent = (value) => {
  if (!value) {
    return false
  }

  if (typeof value === 'string') {
    return value.trim() !== ''
  }

  if (Array.isArray(value)) {
    return value.length > 0
  }

  if (typeof value === 'object') {
    return Object.keys(value).length > 0
  }

  return false
}

const normalizeObjectField = (value, fallback = {}) => {
  if (!value) {
    return fallback
  }

  if (Array.isArray(value)) {
    return value
  }

  if (typeof value === 'object') {
    return value
  }

  if (typeof value !== 'string') {
    return fallback
  }

  const trimmed = value.trim()
  if (!trimmed) {
    return fallback
  }

  try {
    const parsed = JSON.parse(trimmed)
    return parsed && typeof parsed === 'object' ? parsed : fallback
  } catch (error) {
    return fallback
  }
}

const normalizeAiField = (value) => {
  if (!value) {
    return null
  }

  if (typeof value === 'string') {
    const trimmed = value.trim()
    if (!trimmed) {
      return null
    }

    try {
      return JSON.parse(trimmed)
    } catch (error) {
      return trimmed
    }
  }

  if (Array.isArray(value) || typeof value === 'object') {
    return value
  }

  return null
}

const isTruthyFlag = (value) => value === true || value === 1 || value === '1'

const normalizeAnalysisMeta = (value, aiAnalysis = null) => {
  const rawMeta = normalizeObjectField(value, {})
  const normalizedAi = normalizeAiField(aiAnalysis)
  const aiObject = normalizedAi && typeof normalizedAi === 'object' && !Array.isArray(normalizedAi) ? normalizedAi : {}
  const requested = isTruthyFlag(rawMeta.ai_requested)
  const actualAi = isTruthyFlag(rawMeta.is_ai_generated) || isTruthyFlag(aiObject.is_ai_generated)
  let engine = typeof rawMeta.analysis_engine === 'string' ? rawMeta.analysis_engine.trim().toLowerCase() : ''

  if (!['none', 'ai', 'rules'].includes(engine)) {
    if (actualAi) {
      engine = 'ai'
    } else if (requested || normalizedAi) {
      engine = 'rules'
    } else {
      engine = 'none'
    }
  }

  return {
    ai_requested: requested,
    is_ai_generated: actualAi,
    analysis_engine: engine,
    provider: actualAi ? String(rawMeta.provider || aiObject.provider || '').trim() : '',
    fallback_note: !requested || actualAi ? '' : String(rawMeta.fallback_note || aiObject.note || '').trim(),
  }
}

const resolveAnalysisState = (meta = {}) => {
  if (meta.analysis_engine === 'ai') return 'ai'
  if (meta.analysis_engine === 'rules') return 'rules'
  return 'none'
}

const buildAnalysisPresentation = (meta = {}) => {
  const state = resolveAnalysisState(meta)

  if (state === 'ai') {
    return {
      state,
      title: 'AI深度解读',
      note: meta.provider ? `本次由 ${meta.provider} 模型生成。` : '本次由 AI 生成。',
      badgeText: 'AI解读',
      summaryText: '包含 AI 深度解读',
    }
  }

  if (state === 'rules') {
    return {
      state,
      title: '智能解读（规则引擎）',
      note: meta.fallback_note || 'AI 暂不可用，本次已自动切换为规则解读。',
      badgeText: '规则解读',
      summaryText: '本次为规则解读',
    }
  }

  return {
    state,
    title: '未启用 AI',
    note: '',
    badgeText: '未启用AI',
    summaryText: '未启用 AI 扩展',
  }
}

const formatAiAnalysisHtml = (analysis) => {
  const normalized = normalizeAiField(analysis)
  if (!normalized) {
    return ''
  }

  if (typeof normalized === 'string') {
    return `<p>${escapeHtml(normalized)}</p>`
  }

  if (Array.isArray(normalized)) {
    return normalized
      .filter(Boolean)
      .map((item) => `<p>${escapeHtml(item)}</p>`)
      .join('')
  }

  const sections = []

  if (normalized.note) {
    sections.push(`<p class="analysis-note">${escapeHtml(normalized.note)}</p>`)
  }

  if (normalized.summary) {
    sections.push(`<p>${escapeHtml(normalized.summary)}</p>`)
  }

  if (normalized.personality_match) {
    const personality = normalized.personality_match
    const personalityLines = [
      personality.male_personality,
      personality.female_personality,
      personality.match_analysis,
    ].filter(Boolean)

    if (personalityLines.length) {
      sections.push(`
        <h4>性格匹配</h4>
        <p>${escapeHtml(personalityLines.join(' '))}</p>
      `)
    }
  }

  if (normalized.marriage_prospect) {
    sections.push(`<h4>婚姻前景</h4><p>${escapeHtml(normalized.marriage_prospect)}</p>`)
  }

  if (normalized.career_wealth) {
    sections.push(`<h4>事业与财运</h4><p>${escapeHtml(normalized.career_wealth)}</p>`)
  }

  if (normalized.children_fate) {
    sections.push(`<h4>家庭与子女缘</h4><p>${escapeHtml(normalized.children_fate)}</p>`)
  }

  if (Array.isArray(normalized.suggestions) && normalized.suggestions.length) {
    sections.push(`
      <h4>AI建议</h4>
      <ul>${normalized.suggestions.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>
    `)
  }

  if (normalized.auspicious_info) {
    const auspiciousInfo = normalized.auspicious_info
    const lines = [
      Array.isArray(auspiciousInfo.best_years) && auspiciousInfo.best_years.length ? `更适合推进关系的年份：${auspiciousInfo.best_years.join('、')}` : '',
      Array.isArray(auspiciousInfo.auspicious_months) && auspiciousInfo.auspicious_months.length ? `顺势月份：${auspiciousInfo.auspicious_months.join('、')}` : '',
      auspiciousInfo.notes ? `提醒：${auspiciousInfo.notes}` : '',
    ].filter(Boolean)

    if (lines.length) {
      sections.push(`
        <h4>顺势提醒</h4>
        <ul>${lines.map((line) => `<li>${escapeHtml(line)}</li>`).join('')}</ul>
      `)
    }
  }

  if (!sections.length) {
    const fallbackLines = Object.entries(normalized)
      .filter(([, value]) => value !== null && value !== undefined && value !== '')
      .map(([key, value]) => `${key}：${Array.isArray(value) ? value.join('、') : typeof value === 'object' ? JSON.stringify(value) : value}`)

    return fallbackLines.map((line) => `<p>${escapeHtml(line)}</p>`).join('')
  }

  return sections.join('')
}

const hehunDetailSectionLabels = {
  year: '生肖契合',
  day: '日柱关系',
  wuxing: '五行互补',
  hechong: '干支配合',
  nayin: '纳音互感',
}

const buildHehunDetailHtml = (hehun) => {
  const sections = []

  if (hehun.comment) {
    sections.push(`<p>${escapeHtml(hehun.comment)}</p>`)
  }

  const detailEntries = Object.entries(normalizeObjectField(hehun.details, {})).filter(([, value]) => Boolean(value))
  if (detailEntries.length) {
    sections.push(`
      <h4>核心分析</h4>
      <ul>${detailEntries.map(([key, value]) => `<li><strong>${escapeHtml(hehunDetailSectionLabels[key] || key)}</strong>：${escapeHtml(value)}</li>`).join('')}</ul>
    `)
  }

  if (Array.isArray(hehun.highlights) && hehun.highlights.length) {
    sections.push(`
      <h4>关系亮点</h4>
      <ul>${hehun.highlights.map((item) => `<li>${escapeHtml(item?.text || item)}</li>`).join('')}</ul>
    `)
  }

  const traditionalRisk = normalizeObjectField(hehun.traditional_risk, {})
  if (traditionalRisk.warning) {
    sections.push(`
      <h4>传统风险提示</h4>
      <p>${escapeHtml(traditionalRisk.warning)}</p>
    `)
  }

  const traditionalMethods = normalizeObjectField(hehun.traditional_methods, {})
  const traditionalEntries = Object.entries(traditionalMethods).filter(([, value]) => value && typeof value === 'object')
  if (traditionalEntries.length) {
    sections.push(`
      <h4>传统合婚补充</h4>
      <ul>${traditionalEntries.map(([key, value]) => {
        const label = key === 'sanyuan' ? '三元宫位' : key === 'jiugong' ? '九宫关系' : key
        const summary = [value.grade, value.relation?.type || value.type || value.meaning, value.description, value.suggestion].filter(Boolean).join(' · ')
        return `<li><strong>${escapeHtml(label)}</strong>：${escapeHtml(summary || JSON.stringify(value))}</li>`
      }).join('')}</ul>
    `)
  }

  return sections.join('')
}

const normalizeHehunData = (hehun) => {
  const normalized = normalizeObjectField(hehun, {})
  const rawDimensions = normalizeObjectField(normalized.dimensions, {})
  const rawScores = normalizeObjectField(normalized.scores, {})
  const solutions = Array.isArray(normalized.solutions) && normalized.solutions.length
    ? normalized.solutions
    : Array.isArray(normalized.suggestions)
      ? normalized.suggestions
      : []

  return {
    ...normalized,
    dimensions: {
      year: Number(rawDimensions.year ?? rawScores.year ?? 0),
      day: Number(rawDimensions.day ?? rawScores.day ?? 0),
      wuxing: Number(rawDimensions.wuxing ?? rawScores.wuxing ?? 0),
      hechong: Number(rawDimensions.hechong ?? rawScores.hechong ?? 0),
      nayin: Number(rawDimensions.nayin ?? rawScores.nayin ?? 0),
      shensha: Number(rawDimensions.shensha ?? rawScores.shensha ?? 0),
      traditional: Number(rawDimensions.traditional ?? rawScores.traditional ?? 0),
    },
    detail_analysis: normalized.detail_analysis || buildHehunDetailHtml(normalized),
    solutions,
    suggestions: solutions,
  }
}

const normalizeFreeResultData = (payload = {}) => ({
  ...payload,
  tier: payload.tier || 'free',
  hehun: normalizeHehunData(payload.hehun),
  male_bazi: normalizeObjectField(payload.male_bazi, {}),
  female_bazi: normalizeObjectField(payload.female_bazi, {}),
})

const normalizePremiumResultData = (payload = {}) => {
  const aiAnalysis = normalizeAiField(payload.ai_analysis)
  return {
    ...payload,
    tier: payload.tier || 'premium',
    hehun: normalizeHehunData(payload.hehun),
    ai_analysis: aiAnalysis,
    analysis_meta: normalizeAnalysisMeta(payload.analysis_meta || payload.hehun?.analysis_meta, aiAnalysis),
    male_bazi: normalizeObjectField(payload.male_bazi, {}),
    female_bazi: normalizeObjectField(payload.female_bazi, {}),
  }
}

const premiumAiAnalysisHtml = computed(() => sanitizeHtml(formatAiAnalysisHtml(premiumResult.value?.ai_analysis)))
const premiumAnalysisPresentation = computed(() => buildAnalysisPresentation(premiumResult.value?.analysis_meta || {}))

const resolveHistoryTier = (item = {}) => {
  const explicitTier = typeof item.tier === 'string' ? item.tier.trim().toLowerCase() : ''
  if (['free', 'premium', 'vip'].includes(explicitTier)) {
    return explicitTier
  }

  const isPremium = item.is_premium === true || item.is_premium === 1 || item.is_premium === '1'
  const isFree = item.is_premium === false || item.is_premium === 0 || item.is_premium === '0'

  if (isFree) {
    return 'free'
  }

  const pointsCost = Number(item.points_cost ?? 0)
  if (isPremium || pointsCost > 0) {
    return pointsCost > 0 ? 'premium' : 'vip'
  }

  const resultData = normalizeObjectField(item.result, {})
  if (item.is_ai_analysis || hasAiContent(item.ai_analysis) || resultData.detail_analysis || resultData.details || resultData.solutions) {
    return 'vip'
  }

  return 'free'
}

const resolveHistoryAccessLabel = (tier, pointsCost) => {
  if (tier === 'vip') {
    return '会员权益解锁'
  }

  if (tier === 'premium') {
    return pointsCost > 0 ? `${pointsCost} 积分解锁` : '已解锁完整版'
  }

  return '可继续升级完整版'
}

const buildHistorySummary = (tier, analysisPresentation, pointsCost) => {
  if (tier === 'free') {
    return '保留基础匹配分与简评，可继续解锁完整版查看五维分析与化解建议。'
  }

  const accessCopy = tier === 'vip'
    ? '会员完整版记录'
    : pointsCost > 0
      ? `${pointsCost} 积分解锁记录`
      : '已解锁完整版记录'

  return `${accessCopy}，${analysisPresentation.summaryText}，点击可回看完整内容。`
}

const normalizeHistoryItem = (item = {}) => {
  const tier = resolveHistoryTier(item)
  const aiAnalysis = normalizeAiField(item.ai_analysis)
  const resultData = normalizeObjectField(item.result, {})
  const pointsCost = Number(item.points_cost ?? 0)
  const createdAt = item.create_time || item.created_at || ''
  const analysisMeta = normalizeAnalysisMeta(item.analysis_meta || resultData.analysis_meta, aiAnalysis)
  const analysisPresentation = buildAnalysisPresentation(analysisMeta)
  const inputMeta = resultData.input_meta || {}
  const score = Number(item.score ?? resultData.score ?? 0)
  const level = item.level || resultData.level || ''
  const isLocalOnly = Boolean(item.is_local_only)
  const maleBirthDate = item.male_birth_date || inputMeta.male_birth_date || ''
  const femaleBirthDate = item.female_birth_date || inputMeta.female_birth_date || ''
  const fingerprint = item.fingerprint || buildHehunFingerprint({
    maleName: item.male_name || inputMeta.male_name || '',
    maleBirthDate,
    femaleName: item.female_name || inputMeta.female_name || '',
    femaleBirthDate,
    score,
    level,
  })

  return {
    ...item,
    result: resultData,
    ai_analysis: aiAnalysis,
    analysis_meta: analysisMeta,
    male_bazi: normalizeObjectField(item.male_bazi, {}),
    female_bazi: normalizeObjectField(item.female_bazi, {}),
    male_birth_date: maleBirthDate,
    female_birth_date: femaleBirthDate,
    male_birth_precision: item.male_birth_precision || inputMeta.male_birth_precision || '',
    female_birth_precision: item.female_birth_precision || inputMeta.female_birth_precision || '',
    male_birth_time_range: item.male_birth_time_range || inputMeta.male_birth_time_range || '',
    female_birth_time_range: item.female_birth_time_range || inputMeta.female_birth_time_range || '',
    male_birth_time: item.male_birth_time || '',
    female_birth_time: item.female_birth_time || '',
    score,
    level,
    level_text: item.level_text || resultData.level_text || '',
    points_cost: pointsCost,
    tier,
    is_local_only: isLocalOnly,
    fingerprint,
    is_premium: isLocalOnly ? false : tier !== 'free',
    hasAiAnalysis: analysisPresentation.state === 'ai',
    analysisState: analysisPresentation.state,
    analysisBadgeText: isLocalOnly ? '暂存预览' : analysisPresentation.badgeText,
    typeLabel: isLocalOnly ? '本机暂存' : (historyTierCopy[tier]?.label || '历史记录'),
    ctaLabel: isLocalOnly ? '继续查看暂存结果' : (historyTierCopy[tier]?.cta || '查看记录'),
    accessLabel: isLocalOnly ? '仅当前设备暂存' : resolveHistoryAccessLabel(tier, pointsCost),
    summary: isLocalOnly
      ? '云端历史暂未生成，这条免费预览已临时保存在当前设备；点击后可继续查看或升级完整版。'
      : buildHistorySummary(tier, analysisPresentation, pointsCost),
    created_at: createdAt,
    create_time: createdAt,
  }
}

const localFreePreviewRecord = computed(() => {
  if (!localFreePreview.value) {
    return null
  }

  return normalizeHistoryItem(localFreePreview.value)
})

const mergeLocalFreePreviewIntoHistory = (items = []) => {
  if (!localFreePreviewRecord.value) {
    return items
  }

  const hasSyncedRecord = items.some((item) => item.fingerprint === localFreePreviewRecord.value.fingerprint && !item.is_local_only)
  if (hasSyncedRecord) {
    clearLocalFreePreview()
    return items
  }

  return [localFreePreviewRecord.value, ...items.filter((item) => item.id !== localFreePreviewRecord.value.id)]
}

const resolveHistoryList = (payload) => {
  if (Array.isArray(payload)) {
    return payload
  }

  if (Array.isArray(payload?.list)) {
    return payload.list
  }

  return []
}


// 获取定价信息
const loadPricing = async () => {
  pricingLoading.value = true
  pricingError.value = ''

  try {
    const response = await getHehunPricing()
    if (response.code === 200) {
      pricing.value = response.data
      return
    }

    pricing.value = null
    pricingError.value = response.message || '完整版价格加载失败，请稍后重试。'
  } catch (error) {
    pricing.value = null
    pricingError.value = '完整版价格加载失败，请稍后重试。'
  } finally {
    pricingLoading.value = false
  }
}

const syncHistorySelection = async (preferredId = null) => {
  await loadHistory()

  if (!history.value.length) {
    activeHistoryId.value = null
    return null
  }

  const matchedItem = preferredId ? history.value.find((item) => item.id === preferredId) : null
  const targetItem = matchedItem || history.value[0]
  activeHistoryId.value = targetItem?.id || null
  return targetItem || null
}

// 提交表单（免费预览）
const submitForm = async () => {
  clearHehunSubmitIssues()
  const issues = buildHehunSubmitIssues()

  if (issues.length) {
    hehunSubmitIssues.value = issues
    handleHehunIssue(issues[0])
    ElMessage.warning('提交前还有信息未完成，已帮你定位到第一个问题')
    return
  }

  isLoading.value = true
  try {
    const payload = buildHehunPayload({
      tier: 'free',
      useAi: false,
    })
    const response = await calculateHehun(payload)

    if (response.code === 200) {
      trackSubmit('hehun_calculate', true, { tier: 'free' })
      const normalizedFreeResult = normalizeFreeResultData(response.data)
      const localPreviewRecord = buildLocalFreePreviewRecord(normalizedFreeResult)
      persistLocalFreePreview(localPreviewRecord)

      premiumResult.value = null
      freeResult.value = buildHistoryFreeResult(localPreviewRecord, normalizedFreeResult.hehun, normalizedFreeResult.male_bazi, normalizedFreeResult.female_bazi)
      showAllFreeSuggestions.value = false

      clearUnlockFeedback()

      ElMessage.success('基础合婚分析完成')

      try {
        const preferredHistoryId = normalizedFreeResult.id || localPreviewRecord.id
        const matchedHistoryItem = await syncHistorySelection(preferredHistoryId)
        if (matchedHistoryItem) {
          applyHistoryDetail(matchedHistoryItem)
        }
      } catch (historyError) {
      }
    } else {
      trackSubmit('hehun_calculate', false, { tier: 'free', error: response.message })
      ElMessage.error(response.message)
    }

  } catch (error) {
    trackSubmit('hehun_calculate', false, { tier: 'free', error: error.message })
    trackError('hehun_calculate_error', error.message)
    ElMessage.error('合婚分析失败，请重试')
  } finally {
    isLoading.value = false
  }
}

// 解锁付费报告
const unlockPremium = async () => {
  if (!canUnlockPremium.value) {
    ElMessage.warning(pricingStatusText.value || '完整版价格暂未就绪，请稍后再试')
    return
  }

  // 积分不足前置拦截
  if (pricing.value && pricing.value.balance < pricing.value.cost) {
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

  unlockError.value = null

  try {
    await ElMessageBox.confirm(
      premiumUnlockMessage.value,
      '确认解锁',
      {
        confirmButtonText: '确认解锁',
        cancelButtonText: '取消',
        type: 'info',
      }
    )
    
    isLoading.value = true
    unlockLoading.value = true
    const payload = buildHehunPayload({
      tier: 'premium',
      useAi: form.useAi,
    })
    const response = await calculateHehun(payload)
    
    if (response.code === 200) {
      trackSubmit('hehun_calculate', true, { tier: 'premium' })
      const normalizedPremiumResult = normalizePremiumResultData(response.data)
      clearLocalFreePreview()
      freeResult.value = null
      premiumResult.value = normalizedPremiumResult
      window.dispatchEvent(new Event('points-updated'))


      try {
        await syncHistorySelection(normalizedPremiumResult.id)
      } catch (historyError) {
      }

      ElMessage.success('解锁成功！')
    } else {
      trackSubmit('hehun_calculate', false, { tier: 'premium', error: response.message })
      unlockError.value = response.code === 403
        ? '积分不足，请先充值后再解锁详细报告。'
        : (response.message || '解锁失败，请重试。')
      ElMessage.error(unlockError.value)
    }
  } catch (error) {
    if (error !== 'cancel') {
      trackSubmit('hehun_calculate', false, { tier: 'premium', error: error.message })
      trackError('hehun_calculate_error', error.message)
      unlockError.value = '解锁失败，请重试。'
      ElMessage.error(unlockError.value)
    }
  } finally {
    unlockLoading.value = false
    isLoading.value = false
  }
}


const returnToForm = () => {
  freeResult.value = null
  premiumResult.value = null
  showAllFreeSuggestions.value = false
  clearUnlockFeedback()
}


// 重置表单
const resetForm = () => {
  freeResult.value = null
  premiumResult.value = null
  activeHistoryId.value = null
  showAllFreeSuggestions.value = false
  clearUnlockFeedback()

  form.maleName = ''
  form.maleBirthDate = ''
  form.maleBirthPrecision = 'exact'
  form.maleBirthTimeRange = ''
  form.femaleName = ''
  form.femaleBirthDate = ''
  form.femaleBirthPrecision = 'exact'
  form.femaleBirthTimeRange = ''
}




// 导出报告
const exportReport = async () => {
  if (!premiumResult.value?.id) {
    ElMessage.warning('当前历史记录缺少导出标识，请重新加载后再试')
    return
  }
  
  exporting.value = true
  try {
    const response = await exportHehunReport({
      record_id: premiumResult.value.id,
    })

    
    if (response.code === 200) {
      // 下载PDF
      const link = document.createElement('a')
      link.href = response.data.download_url
      const exportMaleName = form.maleName || getRoleLabel('male')
      const exportFemaleName = form.femaleName || getRoleLabel('female')
      link.download = `合婚报告_${exportMaleName}_${exportFemaleName}.pdf`
      link.click()

      ElMessage.success('报告导出成功')
    } else {
      ElMessage.error(response.message)
    }
  } catch (error) {
    ElMessage.error('导出失败，请重试')
  } finally {
    exporting.value = false
  }
}

// 加载历史记录
const loadHistory = async () => {
  historyLoading.value = true
  historyError.value = ''

  try {
    const response = await getHehunHistory({ limit: 5 })
    if (response.code === 200) {
      const normalizedHistory = resolveHistoryList(response.data).map(normalizeHistoryItem)
      history.value = mergeLocalFreePreviewIntoHistory(normalizedHistory)
      if (activeHistoryId.value && !history.value.some((item) => item.id === activeHistoryId.value)) {
        activeHistoryId.value = null
      }
    } else {
      history.value = mergeLocalFreePreviewIntoHistory([])
      historyError.value = response.message || '历史记录加载失败，请稍后重试'
    }
  } catch (error) {
    history.value = mergeLocalFreePreviewIntoHistory([])
    historyError.value = '历史记录加载失败，请稍后重试'
  } finally {

    historyLoading.value = false
    historyLoaded.value = true
  }
}

const getHistoryBirthLabel = (birthDate) => {
  if (!birthDate) {
    return ''
  }

  const match = String(birthDate).trim().match(/^(\d{4}-\d{2}-\d{2})/)
  return match ? match[1] : String(birthDate).trim()
}

const getHistoryPersonLabel = (name, birthDate, roleLabel) => {
  const trimmedName = typeof name === 'string' ? name.trim() : ''
  if (trimmedName) {
    return trimmedName
  }

  const birthLabel = getHistoryBirthLabel(birthDate)
  return birthLabel ? `${roleLabel} ${birthLabel}` : roleLabel
}

const formatHistoryNames = (item = {}) => {
  const maleLabel = getHistoryPersonLabel(item.male_name, item.male_birth_date, getRoleLabel('male'))
  const femaleLabel = getHistoryPersonLabel(item.female_name, item.female_birth_date, getRoleLabel('female'))
  return `${maleLabel} & ${femaleLabel}`
}


const buildHistoryFreeResult = (item, hehunData, maleBaziData, femaleBaziData) => normalizeFreeResultData({
  ...item,
  tier: 'free',
  is_local_only: Boolean(item.is_local_only),
  hehun: {
    ...hehunData,
    suggestions: Array.isArray(hehunData.suggestions) && hehunData.suggestions.length
      ? hehunData.suggestions
      : ['可先查看基础匹配趋势，若需要五维分析和 AI 解读，可继续解锁完整版。'],
  },
  male_bazi: maleBaziData,
  female_bazi: femaleBaziData,
  pricing: item.pricing || null,
  preview_hint: item.is_local_only
    ? '云端历史暂未生成，这次免费预览已临时保存在当前设备；稍后回到本页仍可继续查看。'
    : '这是你之前保存的免费预览记录；如需五维分析和 AI 解读，请重新解锁完整版。',
})


const buildHistoryPremiumResult = (item, hehunData, aiAnalysisData, maleBaziData, femaleBaziData) => normalizePremiumResultData({
  id: item.id,
  tier: item.tier,
  hehun: hehunData,
  ai_analysis: aiAnalysisData,
  analysis_meta: item.analysis_meta,
  male_bazi: maleBaziData,
  female_bazi: femaleBaziData,
})

const toDatetimeLocalValue = (value = '') => {
  const trimmed = String(value || '').trim()
  const match = trimmed.match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})/)
  if (!match) {
    return trimmed
  }

  return `${match[1]} ${match[2]}:${match[3]}`
}

const resolveHistoryBirthState = (item, role) => {
  const birthValue = String(item?.[`${role}_birth_date`] || '').trim()
  const precision = item?.[`${role}_birth_precision`] || ''
  const storedTimeRange = item?.[`${role}_birth_time_range`] || ''

  if (precision === 'exact') {
    return {
      value: toDatetimeLocalValue(birthValue),
      precision: 'exact',
      timeRange: resolveStoredTimeRange(birthValue, storedTimeRange),
    }
  }

  if (precision === 'range') {
    return {
      value: birthValue.slice(0, 10),
      precision: 'range',
      timeRange: storedTimeRange || resolveStoredTimeRange(birthValue, ''),
    }
  }

  if (precision === 'unknown') {
    return {
      value: birthValue.slice(0, 10),
      precision: 'unknown',
      timeRange: '',
    }
  }

  return hydrateBirthState(birthValue)
}


// 加载历史记录详情
const applyHistoryDetail = (normalizedItem) => {
  activeHistoryId.value = normalizedItem.id
  showAllFreeSuggestions.value = false

  // 填充表单

  form.maleName = normalizedItem.male_name || ''
  form.femaleName = normalizedItem.female_name || ''

  const maleBirthState = resolveHistoryBirthState(normalizedItem, 'male')
  form.maleBirthDate = maleBirthState.value
  form.maleBirthPrecision = maleBirthState.precision
  form.maleBirthTimeRange = maleBirthState.timeRange

  const femaleBirthState = resolveHistoryBirthState(normalizedItem, 'female')
  form.femaleBirthDate = femaleBirthState.value
  form.femaleBirthPrecision = femaleBirthState.precision
  form.femaleBirthTimeRange = femaleBirthState.timeRange

  const hehunData = normalizeHehunData(normalizedItem.result)
  const aiAnalysisData = normalizeAiField(normalizedItem.ai_analysis)
  const maleBaziData = normalizeObjectField(normalizedItem.male_bazi, {})
  const femaleBaziData = normalizeObjectField(normalizedItem.female_bazi, {})

  if (!hehunData || Object.keys(hehunData).length === 0) {
    ElMessage.warning('合婚结果数据不完整')
    return
  }

  if (normalizedItem.tier === 'free' || !normalizedItem.is_premium) {
    freeResult.value = buildHistoryFreeResult(normalizedItem, hehunData, maleBaziData, femaleBaziData)
    premiumResult.value = null
    clearUnlockFeedback()
    return
  }

  premiumResult.value = buildHistoryPremiumResult(normalizedItem, hehunData, aiAnalysisData, maleBaziData, femaleBaziData)
  freeResult.value = null
  clearUnlockFeedback()
}

const loadHistoryDetail = async (item) => {
  const normalizedItem = normalizeHistoryItem(item)

  try {
    if (!freeResult.value && !premiumResult.value && hasUnsavedDraft.value && activeHistoryId.value !== normalizedItem.id) {
      await ElMessageBox.confirm(
        '当前正在填写的双方信息会被这条历史记录直接覆盖，是否继续载入？',
        '载入历史记录',
        {
          confirmButtonText: '载入历史记录',
          cancelButtonText: '继续填写',
          type: 'warning',
          distinguishCancelAndClose: true,
        }
      )
    }

    applyHistoryDetail(normalizedItem)
  } catch (error) {
    if (error === 'cancel' || error === 'close') {
      return
    }

    ElMessage.error('历史记录数据格式错误，无法加载')
  }
}


// 格式化日期
const formatDate = (dateStr) => {
  const rawValue = typeof dateStr === 'string' ? dateStr.trim() : ''
  if (!rawValue) {
    return '时间待补充'
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

// 初始化
onMounted(() => {
  trackPageView('hehun')
  localFreePreview.value = readLocalFreePreview()
  loadPricing()
  loadHistory()
})

</script>

<style scoped>
.hehun-page {
  padding: 40px 20px;
  min-height: 100vh;
}

.container {
  max-width: 800px;
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
  background: linear-gradient(180deg, var(--bg-card), rgba(var(--primary-rgb), 0.03));
  backdrop-filter: blur(10px);
  border-radius: var(--radius-lg);
  padding: 40px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
}

.local-preview-recovery {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 20px;
  padding: 20px 22px;
  border-radius: var(--radius-lg);
  border: 1px solid rgba(var(--primary-rgb), 0.16);
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), rgba(var(--primary-rgb), 0.03));
  box-shadow: var(--shadow-md);
}

.local-preview-recovery__body {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.local-preview-recovery__eyebrow {
  color: var(--text-tertiary);
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.local-preview-recovery__body strong {
  color: var(--text-primary);
  font-size: 18px;
}

.local-preview-recovery__body p {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.7;
}

.local-preview-recovery__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.form-card h2 {

  color: var(--text-primary);
  text-align: center;
  margin-bottom: 12px;
}

.form-intro {
  max-width: 620px;
  margin: 0 auto 16px;
  color: var(--text-secondary);
  text-align: center;
  font-size: 14px;
  line-height: 1.8;
}

.form-meta-list {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  margin: 0 0 28px;
}

.form-meta-item {
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.08);
  border: 1px solid var(--primary-light-20);
  color: var(--text-primary);
  font-size: 12px;
  font-weight: 600;
}

.strategy-summary-card,
.submit-summary-card {
  margin-bottom: 24px;
  padding: 18px 20px;
  border-radius: var(--radius-lg);
  border: 1px solid var(--primary-light-20);
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), rgba(255, 248, 232, 0.96));
  box-shadow: 0 14px 28px rgba(var(--primary-rgb), 0.08);
}

.strategy-summary-card__header,
.submit-summary-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.strategy-summary-card__copy,
.submit-summary-card__header div {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.strategy-summary-card__eyebrow {
  color: var(--text-tertiary);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.strategy-summary-card__copy strong,
.submit-summary-card__header strong {
  color: var(--text-primary);
  font-size: 16px;
}

.strategy-summary-card__copy p,
.submit-summary-card__header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.strategy-summary-card__toggle {
  flex-shrink: 0;
  min-height: 36px;
}

.strategy-summary-card__details,
.submit-summary-card__actions {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.strategy-detail-item {
  padding: 14px 16px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.78);
  border: 1px solid rgba(var(--primary-rgb), 0.12);
}

.strategy-detail-item strong {
  display: block;
  color: var(--text-primary);
  font-size: 14px;
  margin-bottom: 6px;
}

.strategy-detail-item p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.submit-summary-card {
  border-color: rgba(230, 162, 60, 0.24);
  background: linear-gradient(135deg, rgba(255, 250, 242, 0.98), rgba(255, 245, 228, 0.98));
}

.submit-summary-card__header > .el-icon {
  margin-top: 2px;
  color: var(--warning-color);
  font-size: 20px;
}

.submit-summary-card__action {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid rgba(230, 162, 60, 0.18);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.82);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  text-align: left;
  cursor: pointer;
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.submit-summary-card__action span {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 700;
}

.submit-summary-card__action small {
  color: var(--text-secondary);
  font-size: 12px;
  line-height: 1.6;
}

.submit-summary-card__action:hover {
  transform: translateY(-1px);
  border-color: rgba(230, 162, 60, 0.32);
  box-shadow: 0 12px 24px rgba(var(--primary-rgb), 0.08);
}

.role-mode-toggle {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 24px;
  padding: 18px 20px;
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), rgba(255, 248, 232, 0.94));
  border: 1px solid var(--primary-light-20);
  border-radius: var(--radius-lg);
  box-shadow: 0 14px 28px rgba(var(--primary-rgb), 0.08);
}

.role-mode-toggle__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.role-mode-toggle__copy {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.role-mode-toggle__eyebrow {
  color: var(--text-tertiary);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.role-mode-toggle strong {
  display: block;
  color: var(--text-primary);
  margin: 0;
  font-size: 16px;
}

.role-mode-toggle p {
  margin: 0;
  color: var(--text-tertiary);
  font-size: 13px;
  line-height: 1.6;
}

.role-mode-toggle__status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 12px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.1);
  border: 1px solid var(--primary-light-20);
  color: var(--primary-color);
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.role-mode-group {
  width: 100%;
}

.role-mode-group :deep(.el-radio-button),
.role-mode-group :deep(.el-radio-button__inner) {
  width: 100%;
}

.role-mode-group :deep(.el-radio-button__inner) {
  min-height: 78px;
  padding: 14px 16px;
}

.role-mode-group :deep(.el-radio-button:first-child .el-radio-button__inner),
.role-mode-group :deep(.el-radio-button:last-child .el-radio-button__inner) {
  border-radius: var(--radius-md);
}

.role-mode-group :deep(.el-radio-button__original-radio:focus-visible + .el-radio-button__inner) {
  box-shadow: inset 0 0 0 1px var(--primary-color), var(--focus-ring);
}

.role-mode-option {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
}

.role-mode-option__title {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 700;
}

.role-mode-option__desc {
  color: var(--text-tertiary);
  font-size: 12px;
  line-height: 1.55;
}

.role-mode-group :deep(.el-radio-button.is-active .role-mode-option__title) {
  color: var(--primary-color);
}

.role-mode-group :deep(.el-radio-button.is-active .role-mode-option__desc) {
  color: rgba(111, 74, 23, 0.84);
}

.person-section {
  margin-bottom: 30px;
  padding: 18px;
  background: linear-gradient(180deg, var(--surface-raised), rgba(var(--primary-rgb), 0.04));
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-sm);
}

.person-section:hover {
  background: linear-gradient(180deg, rgba(var(--primary-rgb), 0.08), rgba(var(--primary-rgb), 0.03));
  border-color: var(--primary-light-20);
}

.person-title {
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  font-size: 18px;
}

.person-subtitle {
  margin: 0 0 20px;
  color: var(--text-tertiary);
  font-size: 13px;
  line-height: 1.7;
}

.gender-icon {
  font-size: 24px;
  color: var(--primary-color);
}

.form-row {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  color: var(--text-secondary);
  margin-bottom: 8px;
  font-size: 14px;
}

.hehun-field-control {
  width: 100%;
}

.hehun-field-control :deep(.el-date-editor.el-input),
.hehun-field-control :deep(.el-date-editor) {
  width: 100%;
}

.hehun-field-control :deep(.el-input__wrapper) {
  min-height: 48px;
  padding: 0 14px;
  background: var(--bg-tertiary);
  border-radius: var(--radius-md);
  box-shadow: inset 0 0 0 1px var(--border-light);
  transition: box-shadow 0.3s ease, background-color 0.3s ease, transform 0.3s ease;
}

.hehun-field-control :deep(.el-input__inner) {
  color: var(--text-primary);
  font-size: 15px;
}

.hehun-field-control :deep(.el-input__count-inner),
.hehun-field-control :deep(.el-range-separator),
.hehun-field-control :deep(.el-input__icon),
.hehun-field-control :deep(.el-input__prefix-inner),
.hehun-field-control :deep(.el-input__suffix-inner) {
  color: var(--text-tertiary);
}

.hehun-field-control :deep(.el-input__wrapper.is-focus),
.hehun-field-control :deep(.el-input__wrapper:hover) {
  background: var(--bg-tertiary);
  transform: translateY(-1px);
  box-shadow: inset 0 0 0 1px var(--primary-color), var(--focus-ring);
}


.required {
  color: var(--primary-color);
}

.field-helper {
  margin-top: 8px;
  color: var(--text-tertiary);
  font-size: var(--font-caption);
  line-height: 1.6;
}

.field-helper--warning {
  color: var(--warning-color);
  font-weight: 600;
}


.birth-precision-panel {
  margin-bottom: 18px;
}

.precision-heading,
.time-range-label {
  display: block;
  margin-bottom: 10px;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.precision-options {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.precision-options--group {
  width: 100%;
}

.precision-options--group :deep(.el-radio-button),
.precision-options--group :deep(.el-radio-button__inner) {
  width: 100%;
}

.precision-options--group :deep(.el-radio-button__inner) {
  min-height: 88px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: var(--bg-secondary);
  color: var(--text-secondary);
  text-align: left;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
  white-space: normal;
  line-height: 1.5;
  box-shadow: none;
  transition: transform 0.25s ease, border-color 0.25s ease, background-color 0.25s ease, box-shadow 0.25s ease;
}

.precision-options--group :deep(.el-radio-button:first-child .el-radio-button__inner),
.precision-options--group :deep(.el-radio-button:last-child .el-radio-button__inner) {
  border-radius: var(--radius-md);
}

.precision-options--group :deep(.el-radio-button__original-radio:focus-visible + .el-radio-button__inner) {
  box-shadow: inset 0 0 0 1px var(--primary-color), var(--focus-ring);
}

.precision-options--group :deep(.el-radio-button__inner:hover),
.precision-options--group :deep(.el-radio-button.is-active .el-radio-button__inner) {
  transform: translateY(-2px);
  border-color: var(--primary-light-30);
  background: rgba(var(--primary-rgb), 0.08);
  box-shadow: var(--shadow-sm);
  color: var(--text-secondary);
}

.precision-options--group :deep(.el-radio-button.is-active .precision-option-title) {
  color: var(--primary-color);
}

.precision-option-title {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.precision-option-desc {
  color: var(--text-tertiary);
  font-size: 12px;
  line-height: 1.6;
}


.precision-helper {
  margin-top: 10px;
  color: var(--text-tertiary);
  font-size: 13px;
  line-height: 1.7;
}

.time-range-panel {
  margin-bottom: 18px;
}

.time-range-options {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.time-range-options--group {
  width: 100%;
}

.time-range-options--group :deep(.el-radio-button),
.time-range-options--group :deep(.el-radio-button__inner) {
  width: 100%;
}

.time-range-options--group :deep(.el-radio-button__inner) {
  min-height: 56px;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: var(--bg-secondary);
  color: var(--text-primary);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  gap: 4px;
  white-space: normal;
  line-height: 1.4;
  box-shadow: none;
  transition: transform 0.25s ease, border-color 0.25s ease, background-color 0.25s ease, box-shadow 0.25s ease;
}

.time-range-options--group :deep(.el-radio-button:first-child .el-radio-button__inner),
.time-range-options--group :deep(.el-radio-button:last-child .el-radio-button__inner) {
  border-radius: var(--radius-md);
}

.time-range-options--group :deep(.el-radio-button__original-radio:focus-visible + .el-radio-button__inner) {
  box-shadow: inset 0 0 0 1px var(--primary-color), var(--focus-ring);
}

.time-range-options--group :deep(.el-radio-button__inner small) {
  color: var(--text-tertiary);
  font-size: 11px;
}

.time-range-options--group :deep(.el-radio-button__inner:hover),
.time-range-options--group :deep(.el-radio-button.is-active .el-radio-button__inner) {
  transform: translateY(-2px);
  border-color: var(--primary-light-30);
  background: rgba(var(--primary-rgb), 0.08);
  box-shadow: var(--shadow-sm);
  color: var(--text-primary);
}


.precision-confidence {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: rgba(255, 255, 255, 0.02);
}

.precision-confidence--exact {
  border-color: rgba(103, 194, 58, 0.18);
  background: rgba(103, 194, 58, 0.08);
}

.precision-confidence--range {
  border-color: rgba(230, 162, 60, 0.2);
  background: rgba(230, 162, 60, 0.08);
}

.precision-confidence--unknown {
  border-color: rgba(245, 108, 108, 0.18);
  background: rgba(245, 108, 108, 0.08);
}

.precision-confidence p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.confidence-badge {
  min-width: 72px;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(var(--primary-rgb), 0.18);
  color: var(--text-primary);
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.options-section {
  margin: 20px 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.options-title {
  margin: 0;
  color: var(--text-primary);
  font-size: 16px;
}

.option-plan-card {
  padding: 16px 18px;
  border-radius: var(--radius-md);
  background: linear-gradient(135deg, var(--primary-light-10), rgba(var(--primary-rgb), 0.04));
  border: 1px solid var(--primary-light-20);
}

.plan-badge-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 10px;
}

.plan-badge {
  display: inline-flex;
  align-items: center;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.plan-badge--free {
  background: rgba(16, 185, 129, 0.14);
  color: var(--success-color);
}

.plan-badge--premium {
  background: rgba(var(--primary-rgb), 0.14);
  color: var(--primary-color);
}

.plan-summary {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.option-item {
  color: var(--text-secondary);
  padding: 0;
  border-radius: var(--radius-md);
  background: linear-gradient(180deg, var(--surface-raised), rgba(var(--primary-rgb), 0.03));
  border: 1px solid var(--border-light);
  box-shadow: var(--shadow-sm);
  transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease, background 0.3s ease;
}

.option-item.active {
  border-color: var(--primary-light-30);
  background: rgba(var(--primary-rgb), 0.08);
  box-shadow: var(--shadow-sm);
}

.option-checkbox {
  width: 100%;
  margin-right: 0;
  padding: 16px 18px;
  align-items: flex-start;
}

.option-checkbox :deep(.el-checkbox__label) {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 4px;
  white-space: normal;
  padding-left: 12px;
}

.option-checkbox :deep(.el-checkbox__input) {
  margin-top: 2px;
}

.option-checkbox :deep(.el-checkbox__inner) {
  width: 18px;
  height: 18px;
  border-color: var(--border-light);
  background: var(--bg-secondary);
}

.option-checkbox :deep(.el-checkbox__inner::after) {
  left: 5px;
}

.option-checkbox :deep(.el-checkbox__input.is-focus .el-checkbox__inner) {
  box-shadow: var(--focus-ring);
}

.option-title {
  color: var(--text-primary);
  font-weight: 600;
}

.option-desc {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.6;
}


.pricing-info {
  text-align: left;
  padding: 16px;
  background: var(--primary-light-10);
  border-radius: var(--radius-md);
  margin: 20px 0;
  border: 1px solid var(--primary-light-20);
}

.pricing-info-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pricing-info-main {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: center;
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

.pricing-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: var(--text-primary);
}

.pricing-row .points {
  font-size: 24px;
  font-weight: bold;
  color: var(--star-color);
}

.discount {
  background: var(--primary-color);
  color: var(--text-primary);
  padding: 4px 10px;
  border-radius: var(--radius-xl);
  font-size: 12px;
}

.pricing-reason {
  color: var(--text-tertiary);
  font-size: 13px;
  margin-top: 8px;
}

.precision-summary-card {
  padding: 18px 20px;
  border-radius: var(--radius-lg);
  border: 1px solid rgba(230, 162, 60, 0.22);
  background: linear-gradient(135deg, rgba(230, 162, 60, 0.12), rgba(184, 134, 11, 0.06));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
}

.precision-summary-card--result {
  margin-bottom: 20px;
}

.precision-summary-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.precision-summary-header .el-icon {
  margin-top: 2px;
  color: var(--warning-color);
  font-size: 18px;
  flex-shrink: 0;
}

.precision-summary-header strong {
  display: block;
  color: var(--text-primary);
  margin-bottom: 6px;
}

.precision-summary-header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.precision-summary-list {
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.precision-summary-item {
  display: grid;
  grid-template-columns: 56px minmax(0, 1fr) auto;
  gap: 12px;
  align-items: center;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: rgba(184, 134, 11, 0.06);
  border: 1px solid rgba(184, 134, 11, 0.14);
}

.summary-role {
  color: var(--text-primary);
  font-weight: 600;
}

.summary-copy {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.summary-copy strong {
  color: var(--text-primary);
  font-size: 14px;
}

.summary-copy span {
  color: var(--text-secondary);
  font-size: 12px;
  line-height: 1.6;
}

.summary-trust {
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(230, 162, 60, 0.18);
  color: var(--text-primary);
  font-size: 12px;
  font-weight: 700;
}

.btn-submit {
  width: 100%;
  min-height: 48px;
  border-radius: var(--radius-btn);
  border: 1px solid var(--primary-light-30);
  background: var(--primary-gradient);
  box-shadow: 0 12px 28px rgba(var(--primary-rgb), 0.24);
  transition: transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
}

.btn-submit :deep(span) {
  font-size: 16px;
  font-weight: 600;
}

.btn-submit:not(.is-disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(184, 134, 11, 0.4);
}

.btn-submit.is-disabled {
  opacity: 0.6;
  box-shadow: none;
}


.form-hint {
  text-align: center;
  color: var(--text-secondary);
  font-size: 13px;
  margin-top: 16px;
}

/* 结果卡片 */
.result-section {
  margin-bottom: 40px;
}

.result-card {
  background: var(--bg-card);
  backdrop-filter: blur(10px);
  border-radius: var(--radius-lg);
  padding: 32px;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-lg);
}

.result-card.premium {
  border-color: var(--primary-light-30);
  background: linear-gradient(135deg, var(--bg-card), rgba(184, 134, 11, 0.05));
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

.premium-badge {
  background: var(--primary-gradient);
  color: var(--text-primary);
  padding: 6px 16px;
  border-radius: var(--radius-xl);
  font-size: 12px;
  font-weight: 600;
}

.score-display {
  text-align: center;
}

.score-number {
  display: block;
  font-size: 48px;
  font-weight: bold;
  color: var(--star-color);
  line-height: 1;
}

.score-label {
  color: var(--text-tertiary);
  font-size: 14px;
}

.result-level {
  text-align: center;
  padding: 12px 24px;
  border-radius: var(--radius-xl);
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 20px;
}

.result-level.excellent {
  background: var(--success-light);
  color: var(--success-color);
}

.result-level.good {
  background: rgba(24, 144, 255, 0.2);
  color: #1890ff;
}

.result-level.average {
  background: var(--warning-light);
  color: var(--warning-color);
}

.result-level.poor {
  background: var(--danger-light);
  color: var(--danger-color);
}

.result-comment {
  color: var(--text-secondary);
  text-align: center;
  font-size: 16px;
  line-height: 1.6;
  margin-bottom: 24px;
}

.free-preview-status {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 24px;
  padding: 16px 18px;
  border-radius: var(--radius-md);
  border: 1px solid rgba(230, 162, 60, 0.28);
  background: rgba(230, 162, 60, 0.08);
  color: var(--text-primary);
}

.free-preview-status .el-icon {
  margin-top: 2px;
  font-size: 18px;
  color: var(--warning-color);
}

.free-preview-status strong {
  display: block;
  margin-bottom: 6px;
}

.free-preview-status p {
  margin: 0;
  color: var(--text-secondary);
  line-height: 1.7;
}

/* 八字对比 */

.bazi-compare {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 30px;
  padding: 24px;
  background: var(--bg-tertiary);
  border-radius: var(--radius-md);
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}

.bazi-side {
  text-align: center;
}

.bazi-side h4 {
  color: var(--text-tertiary);
  margin-bottom: 12px;
  font-size: 14px;
}

.bazi-pillars {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
}

.pillar {
  padding: 8px 12px;
  background: var(--bg-card);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-weight: 500;
  border: 1px solid var(--border-light);
}

.day-master {
  color: var(--primary-color);
  font-size: 13px;
}

.bazi-divider {
  font-size: 32px;
  color: var(--primary-light-60);
}

/* 建议框 */
.suggestion-box {
  background: var(--success-light);
  border-left: 4px solid var(--success-color);
  padding: 16px 20px;
  border-radius: 0 var(--radius-md) var(--radius-md) 0;
  margin-bottom: 24px;
}

.suggestion-box h4 {
  color: var(--success-color);
  margin-bottom: 10px;
}

.suggestion-list {
  margin: 0;
  padding-left: 20px;
  color: var(--text-secondary);
}

.suggestion-list li + li {
  margin-top: 8px;
}

.suggestion-list li {
  line-height: 1.7;
}

.suggestion-toggle {
  margin-top: 10px;
  padding: 0;
}


/* 升级提示 */
.upgrade-prompt {
  text-align: center;
  padding: 24px;
  background: linear-gradient(135deg, var(--primary-light-10), var(--primary-light-05));
  border-radius: var(--radius-md);
  border: 1px dashed var(--primary-light-30);
  transition: opacity 0.3s ease;
}

.upgrade-prompt--busy {
  opacity: 0.92;
}

.upgrade-prompt p {
  color: var(--text-secondary);
  margin-bottom: 16px;
}


.upgrade-note {
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: rgba(var(--primary-rgb), 0.08);
  border: 1px solid var(--primary-light-20);
  color: var(--text-primary);
  font-size: 13px;
  line-height: 1.6;
}

.pricing-status {
  margin: 0 0 16px;
  font-size: 13px;
  line-height: 1.6;
  color: var(--text-secondary);
}

.pricing-status--loading {
  color: var(--text-secondary);
}

.pricing-status--error {
  color: var(--danger-color);
}

.upgrade-status {
  margin: 0 0 16px;
  font-size: 13px;
  line-height: 1.6;
}

.upgrade-status--loading {
  color: var(--primary-color);
}

.upgrade-status--error {
  color: var(--danger-color);
}

.btn-upgrade {
  min-height: 48px;
  padding: 14px 32px;
  border-radius: var(--radius-btn);
  border: 1px solid var(--primary-light-30);
  background: var(--primary-gradient);
  box-shadow: 0 12px 28px rgba(var(--primary-rgb), 0.24);
  transition: transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
}

.btn-upgrade :deep(span) {
  font-size: 16px;
  font-weight: 600;
}

.btn-upgrade.is-disabled {
  opacity: 0.6;
  box-shadow: none;
}

.btn-upgrade:not(.is-disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px var(--primary-light-40);
}


.points-tag {
  background: var(--white-20);
  padding: 4px 10px;
  border-radius: var(--radius-xl);
  font-size: 12px;
}

/* 详细报告样式 */
.score-section {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 40px;
  padding: 24px;
  background: linear-gradient(180deg, var(--surface-raised), rgba(var(--primary-rgb), 0.06));
  border-radius: var(--radius-md);
  margin-bottom: 24px;
  border: 1px solid var(--border-light);
}


.main-score {
  text-align: center;
  padding: 20px;
  border-right: 1px solid var(--border-light);
}

.main-score .score-number {
  font-size: 64px;
}

.main-score .score-level {
  display: inline-block;
  padding: 8px 20px;
  border-radius: var(--radius-xl);
  font-size: 16px;
  font-weight: 600;
  margin-top: 12px;
}

.dimension-scores {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.dimension-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dim-name {
  width: 80px;
  color: var(--text-secondary);
  font-size: 14px;
}

.dim-bar {
  flex: 1;
  height: 8px;
  background: var(--bg-tertiary);
  border-radius: 4px;
  overflow: hidden;
}

.dim-fill {
  height: 100%;
  background: var(--primary-gradient);
  border-radius: 4px;
  transition: width 0.5s ease;
}

.dim-score {
  width: 50px;
  text-align: right;
  color: var(--text-primary);
  font-weight: 600;
}

.risk-alert {
  grid-column: 1 / -1;
  padding: 16px 18px;
  border-radius: var(--radius-md);
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.26);
  color: #92400e;
}

.risk-alert strong {
  display: block;
  margin-bottom: 8px;
}

.risk-alert p {
  margin: 0 0 6px;
  line-height: 1.7;
}

.risk-alert span {
  font-size: 13px;
  line-height: 1.6;
}

.risk-alert--high,
.risk-alert--critical {
  background: rgba(239, 68, 68, 0.12);
  border-color: rgba(239, 68, 68, 0.26);
  color: #991b1b;
}

.analysis-engine-note,
.analysis-note {
  margin: 0 0 16px;
  padding: 12px 14px;
  border-radius: var(--radius-md);
  background: rgba(var(--primary-rgb), 0.08);
  border: 1px solid var(--primary-light-20);
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

/* 分析内容 */
.analysis-section,
.ai-section,
.solution-section {
  margin-bottom: 24px;
}

.analysis-section h3,
.ai-section h3,
.solution-section h3 {
  color: var(--text-primary);
  margin-bottom: 16px;
  font-size: 18px;
}

.analysis-content,
.ai-content {
  color: var(--text-secondary);
  line-height: 1.8;
}

.rich-content {
  padding: 22px 24px;
  border-radius: 18px;
  border: 1px solid var(--border-light);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(184, 134, 11, 0.06));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04), 0 14px 30px rgba(0, 0, 0, 0.18);
}

.rich-content :deep(h1),
.rich-content :deep(h2),
.rich-content :deep(h3),
.rich-content :deep(h4),
.rich-content :deep(h5),
.rich-content :deep(h6) {
  margin: 1.6em 0 0.7em;
  color: var(--text-primary);
  font-weight: 700;
  line-height: 1.35;
}

.rich-content :deep(h1),
.rich-content :deep(h2) {
  font-size: 20px;
}

.rich-content :deep(h3),
.rich-content :deep(h4) {
  font-size: 17px;
}

.rich-content :deep(h1:first-child),
.rich-content :deep(h2:first-child),
.rich-content :deep(h3:first-child),
.rich-content :deep(h4:first-child),
.rich-content :deep(h5:first-child),
.rich-content :deep(h6:first-child),
.rich-content :deep(p:first-child) {
  margin-top: 0;
}

.rich-content :deep(p) {
  margin: 0 0 1em;
  color: var(--text-secondary);
  line-height: 1.9;
}

.rich-content :deep(strong) {
  color: var(--text-primary);
  font-weight: 700;
}

.rich-content :deep(ul),
.rich-content :deep(ol) {
  margin: 0 0 1.1em;
  padding-left: 1.4em;
}

.rich-content :deep(li) {
  margin-bottom: 0.7em;
  color: var(--text-secondary);
  line-height: 1.8;
}

.rich-content :deep(li:last-child),
.rich-content :deep(p:last-child) {
  margin-bottom: 0;
}


.solution-list {
  list-style: none;
  padding: 0;
}

.solution-list li {
  color: var(--text-secondary);
  padding: 12px 0;
  padding-left: 24px;
  position: relative;
  border-bottom: 1px solid var(--border-light);
}

.solution-list li:before {
  content: '';
  position: absolute;
  left: 0;
  top: 18px;
  width: 6px;
  height: 6px;
  background: var(--primary-color);
  border-radius: 1px;
}

/* 操作按钮 */
.action-buttons-wrap {
  margin-top: 28px;
}

.action-buttons-heading {
  max-width: 720px;
  margin: 0 auto 16px;
  text-align: center;
}

.action-buttons-heading__eyebrow {
  display: inline-block;
  margin-bottom: 8px;
  color: var(--text-tertiary);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.action-buttons-heading p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.action-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
  margin-top: 0;
}

.action-buttons :deep(.el-button) {
  min-height: 48px;
  padding: 12px 24px;
}

.action-buttons--free {
  margin-top: 0;
}


.btn-primary,
.btn-secondary {

  padding: 12px 32px;
  min-height: 48px;
  border-radius: var(--radius-btn);
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: all 0.3s;
  border: none;
}

.btn-primary {
  background: var(--primary-gradient);
  color: var(--text-accent-contrast);
  border: 1px solid var(--primary-light-30);
  box-shadow: 0 12px 28px rgba(var(--primary-rgb), 0.24);
}


.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px var(--primary-light-40);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background: var(--bg-tertiary);
  color: var(--text-primary);
  border: 1px solid var(--border-light);
}

.btn-secondary:hover {
  background: var(--bg-hover);
  border-color: var(--primary-color);
}

/* 历史记录 */
.history-section {
  margin-top: 40px;
}

.history-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.history-header-note {
  margin: 6px 0 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.6;
}

.history-section h3 {
  color: var(--text-primary);
  margin: 0;
}

.history-inline-warning {
  margin-bottom: 14px;
  padding: 14px 16px;
  border-radius: var(--radius-md);
  border: 1px solid rgba(230, 162, 60, 0.24);
  background: rgba(230, 162, 60, 0.08);
}

.history-inline-warning p {
  margin: 0 0 6px;
  color: var(--text-primary);
  font-weight: 600;
}

.history-inline-warning span {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.history-state {

  padding: 18px 20px;
  border-radius: var(--radius-md);
  border: 1px solid var(--border-light);
  background: var(--bg-card);
  box-shadow: var(--shadow-sm);
}

.history-state p {
  margin: 0 0 8px;
  color: var(--text-primary);
  font-weight: 600;
}

.history-state span {
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.history-state--error {
  border-color: rgba(245, 108, 108, 0.2);
  background: rgba(245, 108, 108, 0.08);
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.history-item {
  width: 100%;
  padding: 18px 20px;
  border-radius: var(--radius-lg);
  appearance: none;
  font: inherit;
  color: inherit;
  border: 1px solid var(--border-light);
  background: linear-gradient(135deg, var(--bg-card), rgba(var(--primary-rgb), 0.03));
  box-shadow: var(--shadow-sm);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  text-align: left;
  cursor: pointer;
  transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
}

.history-item:hover {
  transform: translateY(-2px);
  border-color: var(--primary-light-30);
  box-shadow: var(--shadow-md);
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.08), var(--bg-card));
}

.history-item.is-active {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 1px rgba(var(--primary-rgb), 0.16), var(--shadow-md);
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.14), var(--bg-card));
}

.history-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.history-topline {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.history-names {
  color: var(--text-primary);
  font-weight: 600;
  line-height: 1.6;
}

.history-badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.history-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 999px;
  border: 1px solid transparent;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.history-badge--tier {
  color: var(--text-primary);
}

.history-badge--free {
  background: rgba(var(--primary-rgb), 0.12);
  border-color: var(--primary-light-20);
}

.history-badge--premium {
  background: rgba(230, 162, 60, 0.14);
  border-color: rgba(230, 162, 60, 0.24);
}

.history-badge--vip {
  background: rgba(103, 194, 58, 0.14);
  border-color: rgba(103, 194, 58, 0.24);
}

.history-badge--ai {
  background: var(--bg-secondary);
  border-color: var(--border-light);
  color: var(--text-secondary);
}

.history-badge--rules {
  background: rgba(59, 130, 246, 0.12);
  border-color: rgba(59, 130, 246, 0.22);
  color: #1d4ed8;
}

.history-badge--ai:not(.history-badge--rules):not(.history-badge--muted) {
  background: rgba(16, 185, 129, 0.12);
  border-color: rgba(16, 185, 129, 0.22);
  color: #047857;
}

.history-badge--muted {
  opacity: 0.82;
}

.history-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 16px;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.6;
}

.history-meta span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.history-summary {
  margin: 0;
  color: var(--text-tertiary);
  font-size: 13px;
  line-height: 1.7;
}

.history-action {
  min-height: 44px;
  padding: 10px 14px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.08);
  color: var(--primary-color);
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  flex-shrink: 0;
  align-self: center;
}

/* 响应式 */
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 14px;
  }

  /* 修复移动端大表单输入键盘遮挡问题 */
  .hehun-form {
    padding-bottom: 30vh; /* 为键盘留出空间 */
  }

  .page-title {
    font-size: 28px;
  }

  .form-intro,
  .person-subtitle {
    text-align: left;
  }

  .form-meta-list {
    justify-content: flex-start;
  }

  .role-mode-toggle,
  .role-mode-toggle__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .role-mode-toggle__status {
    white-space: normal;
  }

  .role-mode-group :deep(.el-radio-button__inner) {
    min-height: 64px;
  }
  
  .form-card,


  .result-card {
    padding: 24px;
  }

  .precision-options,
  .time-range-options {
    grid-template-columns: 1fr;
  }

  .precision-options--group :deep(.el-radio-button__inner),
  .time-range-options--group :deep(.el-radio-button__inner) {
    min-height: 52px;
  }


  .precision-confidence,
  .precision-summary-item {
    grid-template-columns: 1fr;
    flex-direction: column;
    align-items: flex-start;
  }

  .precision-summary-item {
    display: flex;
  }

  .summary-trust {
    margin-left: 0;
  }

  .option-item {
    padding: 0;
  }

  .option-checkbox {
    padding: 14px 16px;
  }
  
  .bazi-compare {
    flex-direction: column;
    gap: 20px;
  }

  .bazi-pillars {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .bazi-divider {
    transform: rotate(90deg);
  }
  
  .score-section {
    grid-template-columns: 1fr;
    gap: 24px;
  }
  
  .main-score {
    border-right: none;
    border-bottom: 1px solid var(--white-10);
    padding-bottom: 24px;
  }

  .pricing-row {
    flex-wrap: wrap;
  }

  .btn-upgrade,
  .btn-primary,
  .btn-secondary {
    width: 100%;
  }

  .rich-content {
    padding: 18px 18px 20px;
  }

  .rich-content :deep(h1),
  .rich-content :deep(h2) {
    font-size: 18px;
  }

  .rich-content :deep(h3),
  .rich-content :deep(h4) {
    font-size: 16px;
  }

  .rich-content :deep(ul),
  .rich-content :deep(ol) {
    padding-left: 1.2em;
  }
  
  .action-buttons {
    flex-direction: column;
  }

  .local-preview-recovery {
    flex-direction: column;
    align-items: stretch;
  }

  .local-preview-recovery__actions {
    justify-content: flex-start;
  }

  .free-preview-status {
    padding: 14px 16px;
  }

  .history-header {
    align-items: flex-start;
  }

  .history-item,

  .history-item.is-active {
    padding: 16px;
  }

  .history-item {
    flex-direction: column;
    align-items: stretch;
  }

  .history-topline {
    flex-direction: column;
  }

  .history-badges {
    justify-content: flex-start;
  }

  .history-action {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .history-meta {
    flex-direction: column;
    gap: 8px;
  }

  .history-badge {
    width: 100%;
    justify-content: center;
  }
}


</style>
