<template>
  <div class="hehun-page">
    <div class="container">
      <PageHeroHeader
        title="八字合婚"
        subtitle="输入双方姓名，AI 从多个维度深度解析你们的缘分与契合度。"
        :icon="Link"
      />

      <!-- 付费详细结果 -->
      <div v-if="premiumResult" class="result-section">
        <div class="result-card premium">
          <!-- 顶部标题区 -->
          <div class="premium-result-header">
            <div class="premium-result-title">
              <div class="premium-badge-wrap">
                <span class="premium-badge">✦ 完整版报告</span>
              </div>
              <h2>{{ formatHistoryNames({ male_name: form.maleName, female_name: form.femaleName }) }}</h2>
              <p class="result-subtitle">八字合婚深度分析</p>
            </div>
            <!-- 综合评分圆环 -->
            <div class="score-ring-wrap">
              <div class="score-ring">
                <svg viewBox="0 0 120 120" class="score-svg">
                  <circle cx="60" cy="60" r="50" class="score-track" />
                  <circle cx="60" cy="60" r="50" class="score-fill"
                    :style="{ strokeDashoffset: 314 - (314 * premiumResult.hehun.score / 100) }" />
                </svg>
                <div class="score-inner">
                  <span class="score-number">{{ premiumResult.hehun.score }}</span>
                  <span class="score-unit">分</span>
                </div>
              </div>
              <div class="score-level-tag" :class="premiumResult.hehun.level">
                {{ premiumResult.hehun.level_text }}
              </div>
            </div>
          </div>

          <!-- AI分析内容 -->
          <div class="ai-section" v-if="premiumResult.ai_analysis">
            <div class="ai-section-header">
              <el-icon><Cpu /></el-icon>
              <h3>{{ premiumAnalysisPresentation.title }}</h3>
              <span v-if="premiumAnalysisPresentation.note" class="ai-engine-badge">{{ premiumAnalysisPresentation.note }}</span>
            </div>
            <div class="ai-content rich-content" v-html="premiumAiAnalysisHtml"></div>
          </div>

          <!-- 操作按钮 -->
          <div class="action-buttons-wrap">
            <div class="action-buttons">
              <el-button plain @click="openHehunRecords">
                <el-icon><Collection /></el-icon> 查看我的记录
              </el-button>
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
              <el-button @click="resetForm">
                <el-icon><RefreshRight /></el-icon> 重新测算
              </el-button>
            </div>
          </div>

          <WisdomText />
        </div>
      </div>

      <!-- 免费预览结果 -->
      <div v-else-if="freeResult" class="result-section">
        <div class="result-card free-result-card">
          <!-- 顶部评分区 -->
          <div class="free-result-header">
            <div class="free-result-names">
              <span class="free-result-name male-name">{{ freeResult.male_bazi ? (form.maleName || '男方') : '男方' }}</span>
              <div class="heart-divider">
                <span class="heart-icon">♥</span>
              </div>
              <span class="free-result-name female-name">{{ freeResult.female_bazi ? (form.femaleName || '女方') : '女方' }}</span>
            </div>
            <div class="free-score-display">
              <div class="free-score-circle">
                <span class="free-score-num">{{ freeResult.hehun.score }}</span>
                <span class="free-score-label">匹配分</span>
              </div>
              <div class="free-level-tag" :class="freeResult.hehun.level">
                {{ freeResult.hehun.level_text }}
              </div>
            </div>
          </div>

          <!-- 简评 -->
          <p class="result-comment">{{ freeResult.hehun.comment }}</p>

          <!-- 八字对比 -->
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

          <!-- 建议 -->
          <div class="suggestion-box" v-if="freeSuggestionList.length">
            <h4><el-icon><Collection /></el-icon> 基础建议</h4>
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
              {{ showAllFreeSuggestions ? '收起' : `展开剩余 ${freeSuggestionList.length - visibleFreeSuggestions.length} 条` }}
            </el-button>
          </div>

          <!-- 解锁完整版 -->
          <div class="unlock-section">
            <div class="unlock-content">
              <div class="unlock-icon">🔮</div>
              <div class="unlock-text">
                <h4>解锁 AI 深度分析报告</h4>
                <p>从性格匹配、婚姻前景、事业财运、子女缘等多个维度，为你们量身定制专属分析</p>
              </div>
              <div class="unlock-price">
                <span class="price-num">{{ pricingDisplayText }}</span>
              </div>
            </div>
            <p v-if="unlockError" class="unlock-error">{{ unlockError }}</p>
            <el-button
              class="btn-unlock"
              type="primary"
              :disabled="!canUnlockPremium"
              :loading="unlockLoading"
              @click="unlockPremium"
            >
              <template v-if="!unlockLoading">
                <el-icon><Unlock /></el-icon>
              </template>
              <span>{{ unlockLoading ? '正在解锁...' : '解锁完整版报告' }}</span>
            </el-button>
          </div>

          <div class="action-buttons-wrap">
            <div class="action-buttons action-buttons--free">
              <el-button plain @click="handleFreeResultRecordAction">{{ freeResultRecordButtonText }}</el-button>
              <el-button @click="openRechargeCenter">去充值</el-button>
              <el-button @click="resetForm">重新测算</el-button>
            </div>
          </div>

          <WisdomText />
        </div>
      </div>

      <!-- 输入表单 -->
      <div v-else class="form-section">
        <div v-if="localFreePreviewRecord" class="local-preview-recovery">
          <div class="local-preview-recovery__body">
            <span class="local-preview-recovery__eyebrow">上次免费预览仍可回看</span>
            <strong>{{ formatHistoryNames(localFreePreviewRecord) }}</strong>
            <p>这条免费预览已暂存在当前设备，可继续查看或升级完整版。</p>
          </div>
          <div class="local-preview-recovery__actions">
            <el-button plain type="primary" @click="restoreLocalFreePreview">恢复上次结果</el-button>
            <el-button link type="primary" @click="scrollToHistorySection">查看暂存记录</el-button>
          </div>
        </div>

        <div class="form-card">
          <div class="form-header">
            <div class="form-header-icon">💑</div>
            <h2>输入双方信息</h2>
          <p class="form-intro">填写双方姓名、性别与出生时间，AI 将从多个维度为你们分析缘分</p>
          </div>

          <!-- 双方基本信息：姓名 + 性别 -->
          <div class="persons-row">
            <!-- 男方 -->
            <div class="person-card person-card--male">
              <div class="person-card-header">
                <div class="person-avatar male-avatar">♂</div>
                <h3>{{ form.maleGender === 'male' ? '男方' : '一方' }}</h3>
              </div>
              <div class="form-group">
                <label>姓名 <span class="required">*</span></label>
                <el-input
                  v-model="form.maleName"
                  placeholder="请输入姓名"
                  maxlength="10"
                  clearable
                  class="hehun-field-control"
                />
              </div>
              <div class="form-group">
                <label>性别</label>
                <el-radio-group v-model="form.maleGender" class="gender-radio-group">
                  <el-radio-button label="male">男</el-radio-button>
                  <el-radio-button label="female">女</el-radio-button>
                </el-radio-group>
              </div>
            </div>

            <!-- 中间连接 -->
            <div class="persons-divider">
              <div class="divider-heart">❤</div>
              <div class="divider-line"></div>
            </div>

            <!-- 女方 -->
            <div class="person-card person-card--female">
              <div class="person-card-header">
                <div class="person-avatar female-avatar">♀</div>
                <h3>{{ form.femaleGender === 'female' ? '女方' : '另一方' }}</h3>
              </div>
              <div class="form-group">
                <label>姓名 <span class="required">*</span></label>
                <el-input
                  v-model="form.femaleName"
                  placeholder="请输入姓名"
                  maxlength="10"
                  clearable
                  class="hehun-field-control"
                />
              </div>
              <div class="form-group">
                <label>性别</label>
                <el-radio-group v-model="form.femaleGender" class="gender-radio-group">
                  <el-radio-button label="male">男</el-radio-button>
                  <el-radio-button label="female">女</el-radio-button>
                </el-radio-group>
              </div>
            </div>
          </div>

          <!-- 出生时间：两人并排 -->
          <div class="birth-row">
            <!-- 男方出生时间 -->
            <div class="birth-block birth-block--male">
              <div class="birth-block-title">
                <span class="birth-block-dot male-dot"></span>
                {{ form.maleName || '一方' }} 的出生时间
              </div>
              <div class="form-group">
                <label>时间精度</label>
                <el-radio-group
                  v-model="form.maleBirthPrecision"
                  class="precision-radio-group"
                  @change="(val) => handleBirthPrecisionChange('male', val)"
                >
                  <el-radio-button
                    v-for="opt in birthPrecisionOptions"
                    :key="opt.value"
                    :label="opt.value"
                  >{{ opt.label }}</el-radio-button>
                </el-radio-group>
              </div>
              <div class="form-group" data-hehun-field="male-birth">
                <label>{{ getBirthInputLabel(form.maleBirthPrecision) }} <span class="required">*</span></label>
                <el-date-picker
                  v-model="form.maleBirthDate"
                  :type="getBirthPickerType(form.maleBirthPrecision)"
                  :placeholder="getBirthPickerPlaceholder(form.maleBirthPrecision)"
                  :format="getBirthPickerFormat(form.maleBirthPrecision)"
                  :value-format="getBirthPickerValueFormat(form.maleBirthPrecision)"
                  class="hehun-field-control"
                  style="width: 100%"
                />
              </div>
              <div class="form-group" v-if="form.maleBirthPrecision === 'range'" data-hehun-field="male-range">
                <label>出生时段 <span class="required">*</span></label>
                <el-select v-model="form.maleBirthTimeRange" placeholder="选择大概时段" class="hehun-field-control" style="width: 100%">
                  <el-option
                    v-for="opt in birthTimeRangeOptions"
                    :key="opt.value"
                    :label="opt.label + ' ' + opt.hint"
                    :value="opt.value"
                  />
                </el-select>
              </div>
            </div>

            <!-- 分隔线 -->
            <div class="birth-row-divider"></div>

            <!-- 女方出生时间 -->
            <div class="birth-block birth-block--female">
              <div class="birth-block-title">
                <span class="birth-block-dot female-dot"></span>
                {{ form.femaleName || '另一方' }} 的出生时间
              </div>
              <div class="form-group">
                <label>时间精度</label>
                <el-radio-group
                  v-model="form.femaleBirthPrecision"
                  class="precision-radio-group"
                  @change="(val) => handleBirthPrecisionChange('female', val)"
                >
                  <el-radio-button
                    v-for="opt in birthPrecisionOptions"
                    :key="opt.value"
                    :label="opt.value"
                  >{{ opt.label }}</el-radio-button>
                </el-radio-group>
              </div>
              <div class="form-group" data-hehun-field="female-birth">
                <label>{{ getBirthInputLabel(form.femaleBirthPrecision) }} <span class="required">*</span></label>
                <el-date-picker
                  v-model="form.femaleBirthDate"
                  :type="getBirthPickerType(form.femaleBirthPrecision)"
                  :placeholder="getBirthPickerPlaceholder(form.femaleBirthPrecision)"
                  :format="getBirthPickerFormat(form.femaleBirthPrecision)"
                  :value-format="getBirthPickerValueFormat(form.femaleBirthPrecision)"
                  class="hehun-field-control"
                  style="width: 100%"
                />
              </div>
              <div class="form-group" v-if="form.femaleBirthPrecision === 'range'" data-hehun-field="female-range">
                <label>出生时段 <span class="required">*</span></label>
                <el-select v-model="form.femaleBirthTimeRange" placeholder="选择大概时段" class="hehun-field-control" style="width: 100%">
                  <el-option
                    v-for="opt in birthTimeRangeOptions"
                    :key="opt.value"
                    :label="opt.label + ' ' + opt.hint"
                    :value="opt.value"
                  />
                </el-select>
              </div>
            </div>
          </div>

          <!-- 分析选项 -->
          <div class="options-section">
            <div class="option-item">
              <el-checkbox v-model="form.useAi" class="option-checkbox">
                <span class="option-title">启用 AI 深度分析</span>
                <span class="option-desc">解锁完整版时优先使用 AI，若服务不可用则自动切换为规则解读</span>
              </el-checkbox>
            </div>
          </div>

          <!-- 定价信息 -->
          <div class="pricing-info" v-if="pricingLoading || normalizedPricing || pricingError">
            <div class="pricing-row">
              <span class="pricing-label">解锁完整版：</span>
              <span class="pricing-value">{{ pricingDisplayText }}</span>
            </div>
            <p v-if="pricingStatusText" class="pricing-reason">{{ pricingStatusText }}</p>
          </div>

          <!-- 提交问题提示 -->
          <section v-if="hehunSubmitIssues.length" class="submit-summary-card" role="alert">
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
            @click="submitFormWithConfirm"
          >
            <template v-if="!isLoading">
              <el-icon><Link /></el-icon>
            </template>
            <span>{{ isLoading ? '正在分析中...' : '开始合婚分析' }}</span>
          </el-button>

          <p class="form-hint">
            <el-icon><Collection /></el-icon> 首次查看基础分析免费；解锁完整版后可获得 AI 深度分析报告
          </p>
        </div>
      </div>

      <!-- 历史记录 -->
      <div ref="historySectionRef" class="history-section" v-if="historyLoaded || historyLoading || historyError">
        <div class="history-header">
          <div>
            <h3>历史记录</h3>
          </div>
          <el-button v-if="historyError" type="primary" link @click="loadHistory">重新加载</el-button>
        </div>
        <div v-if="historyLoading" class="history-state">
          <p>正在加载历史记录...</p>
        </div>
        <div v-else-if="history.length === 0 && historyError" class="history-state history-state--error">
          <p>{{ historyError }}</p>
        </div>
        <div v-else-if="history.length === 0" class="history-state">
          <p>还没有合婚记录</p>
          <span>完成一次分析后，这里会展示最近的记录。</span>
        </div>
        <div v-else>
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
                  </div>
                </div>
                <div class="history-meta">
                  <span><el-icon><Calendar /></el-icon>{{ formatDate(item.created_at) }}</span>
                  <span><el-icon><StarFilled /></el-icon>{{ item.score }}分{{ item.level_text ? ` · ${item.level_text}` : '' }}</span>
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

    <!-- 确认弹窗 -->
    <el-dialog
      v-model="confirmDialogVisible"
      title="确认合婚信息"
      width="420px"
      class="confirm-dialog"
      :close-on-click-modal="false"
    >
      <div class="confirm-dialog-body">
        <div class="confirm-icon">💑</div>
        <p class="confirm-tip">请确认双方信息无误后再开始分析，分析后将扣除积分，无法退回</p>
        <div class="confirm-persons">
        <div class="confirm-person">
            <div class="confirm-person-avatar" :class="form.maleGender === 'male' ? 'male' : 'female'">
              {{ form.maleGender === 'male' ? '♂' : '♀' }}
            </div>
            <div class="confirm-person-info">
              <span class="confirm-person-name">{{ form.maleName || '（未填写）' }}</span>
              <span class="confirm-person-gender">{{ form.maleGender === 'male' ? '男' : '女' }}</span>
              <span class="confirm-person-birth">{{ form.maleBirthDate || '出生时间未填' }}</span>
            </div>
          </div>
          <div class="confirm-heart">❤</div>
          <div class="confirm-person">
            <div class="confirm-person-avatar" :class="form.femaleGender === 'female' ? 'female' : 'male'">
              {{ form.femaleGender === 'female' ? '♀' : '♂' }}
            </div>
            <div class="confirm-person-info">
              <span class="confirm-person-name">{{ form.femaleName || '（未填写）' }}</span>
              <span class="confirm-person-gender">{{ form.femaleGender === 'female' ? '女' : '男' }}</span>
              <span class="confirm-person-birth">{{ form.femaleBirthDate || '出生时间未填' }}</span>
            </div>
          </div>
        </div>
        <p class="confirm-note">确认信息无误后，系统将为你们进行合婚分析</p>
      </div>
      <template #footer>
        <div class="confirm-dialog-footer">
          <el-button @click="confirmDialogVisible = false">返回修改</el-button>
          <el-button type="primary" :loading="isLoading" @click="doSubmitForm">
            确认，开始分析
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>


<script setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Unlock, Lock, Link, RefreshRight, Document, Collection, Cpu, WarningFilled, Calendar, ArrowRight, StarFilled, Share } from '@element-plus/icons-vue'
import PageHeroHeader from '../../components/PageHeroHeader.vue'
import ShareCard from '../../components/ShareCard.vue'
import WisdomText from '../../components/WisdomText.vue'

import { useHehun } from './useHehun'

const {
  form,
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

  birthPrecisionOptions,
  birthTimeRangeOptions,
  dimensionNames,

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

// 确认弹窗
const confirmDialogVisible = ref(false)

const submitFormWithConfirm = () => {
  if (!form.maleName || !form.maleName.trim()) {
    ElMessage.warning('请填写一方的姓名')
    return
  }
  if (!form.femaleName || !form.femaleName.trim()) {
    ElMessage.warning('请填写另一方的姓名')
    return
  }
  if (!form.maleBirthDate) {
    ElMessage.warning('请填写一方的出生时间')
    return
  }
  if (!form.femaleBirthDate) {
    ElMessage.warning('请填写另一方的出生时间')
    return
  }
  confirmDialogVisible.value = true
}

const doSubmitForm = () => {
  confirmDialogVisible.value = false
  submitForm()
}
</script>

<style scoped>
@import './style.css';
</style>