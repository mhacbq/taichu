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
                <p class="pricing-info-guarantee"><el-icon><SuccessFilled /></el-icon> 失败保障：若解锁失败或未生成完整报告，将自动退还积分。</p>
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
import { Unlock, Lock, Link, RefreshRight, Document, Collection, Present, Cpu, WarningFilled, Calendar, ArrowRight, StarFilled, CircleCheckFilled, Share, Check, SuccessFilled } from '@element-plus/icons-vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import ShareCard from '../../components/ShareCard.vue'
import WisdomText from '../../components/WisdomText.vue'

import { useHehun } from './useHehun'

const {
  // 表单与状态
  form,
  roleDisplayMode,
  hehunStrategyExpanded,
  hehunSubmitIssues,
  isLoading,
  exporting,
  freeResult,
  premiumResult,
  pricing,
  pricingLoading,
  pricingError,
  unlockLoading,
  unlockError,
  history,
  historyLoading,
  historyLoaded,
  historyError,
  activeHistoryId,
  historySectionRef,
  localFreePreview,
  showAllFreeSuggestions,

  // 常量
  birthPrecisionOptions,
  birthTimeRangeOptions,
  dimensionNames,

  // 计算属性
  precisionSummaryList,
  hasReducedPrecision,
  isFormValid,
  hehunStrategySummary,
  hehunStrategyDetails,
  hehunShareSummary,
  hehunShareTags,
  hehunSubmitSummaryText,
  normalizedPricing,
  pricingStatusText,
  pricingDisplayText,
  canExportReport,
  canUnlockPremium,
  freeResultRecordButtonText,
  freeSuggestionList,
  visibleFreeSuggestions,
  hasMoreFreeSuggestions,
  premiumUnlockMessage,
  hasUnsavedDraft,
  premiumAiAnalysisHtml,
  premiumAnalysisPresentation,
  localFreePreviewRecord,

  // 方法
  sanitizeHtml,
  getRoleLabel,
  getRolePanelTitle,
  getRoleBaziTitle,
  getRoleNamePlaceholder,
  resolveRoleIcon,
  getBirthPrecisionHint,
  getBirthFieldHelper,
  getBirthInputLabel,
  getBirthPickerType,
  getBirthPickerPlaceholder,
  getBirthPickerFormat,
  getBirthPickerValueFormat,
  getBirthPrecisionBadge,
  getBirthConfidenceCopy,
  handleBirthPrecisionChange,
  handleHehunIssue,
  submitForm,
  unlockPremium,
  resetForm,
  exportReport,
  loadHistory,
  loadHistoryDetail,
  formatDate,
  formatHistoryNames,
  restoreLocalFreePreview,
  scrollToHistorySection,
  handleFreeResultRecordAction,
  openHehunRecords,
  openRechargeCenter,
  openDailySuggestion,
  returnToForm,
} = useHehun()
</script>

<style scoped>
@import './style.css';
</style>
