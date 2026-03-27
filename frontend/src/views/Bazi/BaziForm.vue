<script setup lang="ts">
import { Grid, Cpu, Warning, StarFilled, Check, Present, Coin } from '@element-plus/icons-vue'

const props = defineProps({
  birthTimeAccuracy: { type: String, required: true },
  exactBirthDate: { type: String, default: '' },
  estimatedBirthDate: { type: String, default: '' },
  estimatedTimeSlot: { type: String, default: '' },
  estimatedTimeOptions: { type: Array, default: () => [] },
  estimatedModeHint: { type: String, default: '' },
  gender: { type: String, required: true },
  birthCity: { type: String, default: '' },
  cityOptions: { type: Array, default: () => [] },
  baziSubmitIssues: { type: Array, default: () => [] },
  baziSubmitSummaryText: { type: String, default: '' },
  currentPoints: { type: Number, default: 0 },
  isFirstBazi: { type: Boolean, default: true },
  isAccountReady: { type: Boolean, default: false },
  accountStatus: { type: String, default: 'loading' },
  loading: { type: Boolean, default: false },
  confirmVisible: { type: Boolean, default: false },
  pointsConfirmVisible: { type: Boolean, default: false },
  pointsConfirmType: { type: String, default: '' },
  confirmDialogConfig: { type: Object, default: () => ({}) },
  startBaziButtonText: { type: String, default: '' },
  BAZI_BASE_COST: { type: [Number, Object], default: 10 },
  getPointsConfirmTitle: { type: Function, default: () => '' },
  getPointsConfirmCost: { type: Function, default: () => 0 },
})

const emit = defineEmits([
  'update:birthTimeAccuracy',
  'update:exactBirthDate',
  'update:estimatedBirthDate',
  'update:estimatedTimeSlot',
  'update:gender',
  'update:birthCity',
  'update:confirmVisible',
  'update:pointsConfirmVisible',
  'handleBaziIssue',
  'showConfirm',
  'confirmCalculate',
  'confirmPointsConsume',
])

const baziCost = typeof props.BAZI_BASE_COST === 'object' ? props.BAZI_BASE_COST : { value: props.BAZI_BASE_COST }
</script>

<template>
  <!-- 暖心提示 -->
  <div class="warm-tip card">
    <el-icon class="tip-icon"><StarFilled /></el-icon>
    <div class="tip-content">
      <p class="tip-title">八字排盘能帮你了解什么？</p>
      <p class="tip-desc">你的性格优势 · 适合的发展方向 · 未来运势起伏 · 人际关系建议</p>
    </div>
  </div>

  <div class="bazi-form card">
    <!-- 版本标识 -->
    <div class="version-select-section">
      <div class="version-badge-pro">
        <div class="version-badge-pro__icon"><el-icon><Cpu /></el-icon></div>
        <div class="version-badge-pro__info">
          <span class="version-badge-pro__name">AI 专业分析版</span>
          <span class="version-badge-pro__desc">完整八字命盘 + AI 深度解读 + 流年大运走势</span>
        </div>
        <span class="version-badge-pro__pts">{{ typeof BAZI_BASE_COST === 'object' ? BAZI_BASE_COST.value : BAZI_BASE_COST }} pts</span>
      </div>
    </div>

    <!-- 出生时间 -->
    <div class="form-group form-group--time" data-bazi-field="birth-time">
      <div class="form-group__header form-group__header--time">
        <label>出生日期与时间</label>
        <span class="form-group__status">{{ birthTimeAccuracy === 'exact' ? '精确排盘' : '估算模式' }}</span>
      </div>

      <div class="time-accuracy-switch">
        <div class="time-accuracy-switch__copy">
          <span class="switch-label">时间确认度</span>
          <p class="time-accuracy-switch__hint">先选你记忆的准确程度，再填写对应时间信息。</p>
        </div>
        <el-radio-group
          :model-value="birthTimeAccuracy"
          @update:model-value="emit('update:birthTimeAccuracy', $event)"
          size="small"
          class="time-accuracy-group premium-segment premium-segment--card"
        >
          <el-radio-button label="exact">
            <span class="precision-option">
              <span class="precision-option__title">精确到分钟</span>
              <small class="precision-option__desc">适合已知完整出生时刻</small>
            </span>
          </el-radio-button>
          <el-radio-button label="estimated">
            <span class="precision-option">
              <span class="precision-option__title">大概时段 / 未知时辰</span>
              <small class="precision-option__desc">只记得白天、晚上或完全不记得</small>
            </span>
          </el-radio-button>
        </el-radio-group>
      </div>

      <div class="time-entry-panel" :class="`time-entry-panel--${birthTimeAccuracy}`">
        <div class="time-entry-panel__header">
          <span class="time-entry-panel__badge">{{ birthTimeAccuracy === 'exact' ? '精确填写' : '估算填写' }}</span>
          <p class="time-entry-panel__title">{{ birthTimeAccuracy === 'exact' ? '请选择出生时间' : '请选择出生日期与大概时段' }}</p>
        </div>

        <template v-if="birthTimeAccuracy === 'exact'">
          <el-date-picker
            :model-value="exactBirthDate"
            @update:model-value="emit('update:exactBirthDate', $event)"
            type="datetime"
            placeholder="选择出生日期时间（精确到分钟）"
            format="YYYY-MM-DD HH:mm"
            value-format="YYYY-MM-DD HH:mm:ss"
            class="full-width time-entry-panel__control"
          />
          <p class="form-hint time-entry-panel__hint">精确到分钟时，命盘细节最完整；若记不清，可先切到估算模式。</p>
        </template>

        <template v-else>
          <div class="estimate-birth-grid time-entry-panel__grid">
            <el-date-picker
              :model-value="estimatedBirthDate"
              @update:model-value="emit('update:estimatedBirthDate', $event)"
              type="date"
              placeholder="选择出生日期"
              format="YYYY-MM-DD"
              value-format="YYYY-MM-DD"
              class="full-width time-entry-panel__control"
            />
            <el-select
              :model-value="estimatedTimeSlot"
              @update:model-value="emit('update:estimatedTimeSlot', $event)"
              placeholder="选择大概时段或未知时辰"
              class="full-width time-entry-panel__control"
              clearable
            >
              <el-option
                v-for="option in estimatedTimeOptions"
                :key="option.value"
                :label="option.label"
                :value="option.value"
              />
            </el-select>
          </div>
          <p class="form-hint form-hint--precision time-entry-panel__hint">
            <el-icon><Warning /></el-icon> {{ estimatedModeHint }}
          </p>
        </template>
      </div>

      <!-- 出生地（真太阳时修正） -->
      <div class="birth-city-row">
        <span class="birth-city-label">出生地</span>
        <el-select
          :model-value="birthCity"
          @update:model-value="emit('update:birthCity', $event)"
          placeholder="选择城市（可空，用于真太阳时修正）"
          clearable
          filterable
          class="birth-city-select"
        >
        <el-option-group
            v-for="group in cityOptions"
            :key="group.label"
            :label="group.label"
          >
            <el-option
              v-for="city in group.options"
              :key="city.value"
              :label="city.label"
              :value="city.value"
            />
          </el-option-group>
        </el-select>
        <span class="birth-city-hint">{{ birthCity ? '已启用真太阳时修正' : '不填则使用北京时间' }}</span>
      </div>

      <!-- 性别选择 -->
      <div class="gender-inline" data-bazi-field="gender">
        <span class="gender-inline__label">性别</span>
        <div class="gender-selector">
          <button type="button" class="gender-option" :class="{ active: gender === 'male' }" @click="emit('update:gender', 'male')">
            <span class="gender-option__icon">♂</span>
            <span class="gender-option__text">男</span>
          </button>
          <button type="button" class="gender-option" :class="{ active: gender === 'female' }" @click="emit('update:gender', 'female')">
            <span class="gender-option__icon">♀</span>
            <span class="gender-option__text">女</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 提交前校验提示 -->
    <section v-if="baziSubmitIssues.length" class="submit-summary-card" role="alert" aria-live="assertive">
      <div class="submit-summary-card__header">
        <div>
          <strong>提交前还差这几步</strong>
          <p>{{ baziSubmitSummaryText }}</p>
        </div>
        <el-icon><Warning /></el-icon>
      </div>
      <div class="submit-summary-card__actions">
        <button
          v-for="issue in baziSubmitIssues"
          :key="issue.key"
          type="button"
          class="submit-summary-card__action"
          @click="emit('handleBaziIssue', issue)"
        >
          <span>{{ issue.actionLabel }}</span>
          <small>{{ issue.message }}</small>
        </button>
      </div>
    </section>

    <!-- 费用与权益确认 -->
    <div class="cost-confirm-section">
      <div class="cost-confirm-section__header">
        <h3 class="cost-confirm-section__title">费用与权益确认</h3>
        <div class="cost-confirm-section__points">
          <span>当前积分：<strong>{{ currentPoints }}</strong></span>
          <router-link to="/profile" class="cost-confirm-section__add">+</router-link>
        </div>
      </div>
      <p class="cost-confirm-section__cost">
        本次排盘将消耗：<strong>{{ isFirstBazi ? '免费' : `${typeof BAZI_BASE_COST === 'object' ? BAZI_BASE_COST.value : BAZI_BASE_COST} 积分` }}</strong>
      </p>
      <div class="cost-confirm-section__benefits">
        <div class="cost-confirm-benefit"><el-icon><Check /></el-icon> 完整的八字命盘数据（天干地支、五行、十神等）</div>
        <div class="cost-confirm-benefit"><el-icon><Check /></el-icon> 专属的性格内观与事业财运分析</div>
        <div class="cost-confirm-benefit"><el-icon><Check /></el-icon> 失败保护：若排盘失败或未完成，将自动退还积分</div>
      </div>
      <el-button
        type="primary"
        size="large"
        class="cost-confirm-section__btn"
        @click="emit('showConfirm')"
        :loading="loading"
        :disabled="loading"
      >
        <el-icon v-if="isAccountReady && isFirstBazi"><Present /></el-icon>
        {{ startBaziButtonText }}
      </el-button>
      <div v-if="accountStatus === 'ready' && !isFirstBazi && currentPoints < (typeof BAZI_BASE_COST === 'object' ? BAZI_BASE_COST.value : BAZI_BASE_COST)" class="insufficient-points">
        <el-icon><StarFilled /></el-icon>
        积分不足，请先 <router-link to="/profile">签到领取积分</router-link>
      </div>
    </div>
  </div>

  <!-- 确认对话框 -->
  <el-dialog
    :model-value="confirmVisible"
    @update:model-value="emit('update:confirmVisible', $event)"
    :title="confirmDialogConfig.title"
    width="400px"
    class="confirm-dialog"
  >
    <div class="confirm-content" :class="{ 'confirm-content--free': isFirstBazi }">
      <p class="confirm-title">
        <el-icon v-if="isFirstBazi"><Present /></el-icon>
        <template v-if="isFirstBazi">本次为您的首次排盘，不会扣除积分</template>
        <template v-else>本次排盘将消耗 <strong>{{ typeof BAZI_BASE_COST === 'object' ? BAZI_BASE_COST.value : BAZI_BASE_COST }} 积分</strong></template>
      </p>
      <p>排盘后可在个人中心查看历史记录</p>
      <p class="confirm-note">规则说明：首次排盘免费，后续每次排盘消耗 {{ typeof BAZI_BASE_COST === 'object' ? BAZI_BASE_COST.value : BAZI_BASE_COST }} 积分。</p>
    </div>
    <template #footer>
      <el-button @click="emit('update:confirmVisible', false)">取消</el-button>
      <el-button type="primary" @click="emit('confirmCalculate')">{{ confirmDialogConfig.actionText }}</el-button>
    </template>
  </el-dialog>

  <!-- 积分消耗确认对话框 -->
  <el-dialog
    :model-value="pointsConfirmVisible"
    @update:model-value="emit('update:pointsConfirmVisible', $event)"
    title="确认使用积分"
    width="400px"
    class="points-confirm-dialog"
  >
    <div class="points-confirm-content">
      <div class="points-icon"><el-icon :size="48"><Coin /></el-icon></div>
      <p class="points-title">{{ getPointsConfirmTitle(pointsConfirmType) }}</p>
      <p class="points-desc">此功能将消耗 <strong>{{ getPointsConfirmCost(pointsConfirmType) }} 积分</strong></p>
      <p class="points-balance">当前积分: {{ currentPoints }}</p>
    </div>
    <template #footer>
      <el-button @click="emit('update:pointsConfirmVisible', false)">取消</el-button>
      <el-button type="primary" @click="emit('confirmPointsConsume')">确认使用</el-button>
    </template>
  </el-dialog>
</template>

<style scoped>
/* =============================================
   八字表单组件样式（BaziForm.vue）
   ============================================= */

/* 暖心提示 */
.warm-tip {
  max-width: 920px;
  margin: 0 auto 22px;
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 18px;
  align-items: center;
  padding: 22px 24px;
  border-radius: 26px;
  background: linear-gradient(135deg, rgba(255, 250, 239, 0.98), rgba(255, 255, 255, 0.96));
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08), 0 10px 28px rgba(var(--primary-rgb), 0.05);
}

.tip-icon {
  width: 52px;
  height: 52px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: #9a6612;
  background: linear-gradient(135deg, rgba(245, 196, 103, 0.28), rgba(255, 243, 214, 0.92));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.76);
}

.tip-content {
  text-align: left;
}

.tip-title {
  margin-bottom: 6px;
  font-size: 18px;
  font-weight: 700;
  color: var(--text-primary);
}

.tip-desc {
  color: #5c5143;
  line-height: 1.7;
}

/* 表单卡片 */
.bazi-form {
  max-width: 920px;
  margin: 0 auto 40px;
  padding: 30px;
  border-radius: 30px;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  box-shadow: 0 22px 48px rgba(15, 23, 42, 0.08), 0 10px 28px rgba(var(--primary-rgb), 0.05);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

/* 版本标识 */
.version-select-section {
  margin-bottom: 28px;
}

.version-badge-pro {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 18px;
  border-radius: 14px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-light);
  position: relative;
  overflow: hidden;
}

.version-badge-pro__icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(245, 196, 103, 0.08));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  color: var(--primary-color);
  flex-shrink: 0;
}

.version-badge-pro__info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.version-badge-pro__name {
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  letter-spacing: 0.01em;
}

.version-badge-pro__desc {
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.5;
}

.version-badge-pro__pts {
  font-size: 14px;
  font-weight: 700;
  color: #8a5c16;
  white-space: nowrap;
  flex-shrink: 0;
  padding: 4px 12px;
  border-radius: 999px;
  background: rgba(212, 175, 55, 0.1);
  border: 1px solid rgba(212, 175, 55, 0.2);
}

/* 表单组 */
.form-group {
  margin-bottom: 30px;
  padding: 22px 22px 20px;
  border-radius: 22px;
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 240, 0.92));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

.form-group label {
  display: block;
  margin-bottom: 12px;
  color: var(--text-primary);
  font-size: 15px;
  font-weight: 500;
}

.form-group--time {
  margin-bottom: 34px;
}

.form-group__header--time {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 12px;
}

.form-group__header--time label {
  margin-bottom: 0;
}

.form-group__status {
  display: inline-flex;
  align-items: center;
  min-height: 38px;
  padding: 0 14px;
  border-radius: 999px;
  background: rgba(255, 248, 232, 0.92);
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  color: #8f611c;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

/* 时间精度切换 */
.time-accuracy-switch {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 16px;
  padding: 18px;
  border-radius: 22px;
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 249, 240, 0.92));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

.time-accuracy-switch__copy {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-width: 240px;
}

.switch-label {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.1);
  color: #8d5f1c;
  font-weight: 700;
  letter-spacing: 0.04em;
}

.time-accuracy-switch__hint {
  margin: 0;
  color: #5f5548;
  font-size: 13px;
  line-height: 1.75;
}

.time-accuracy-group {
  flex-wrap: wrap;
  justify-content: flex-end;
}

.precision-option {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
}

.precision-option__title {
  font-weight: 700;
}

.precision-option__desc {
  font-size: 12px;
  line-height: 1.45;
}

/* 时间输入面板 */
.time-entry-panel {
  margin-top: 16px;
  padding: 18px;
  border-radius: 22px;
  border: 1px solid rgba(var(--primary-rgb), 0.12);
  background: linear-gradient(180deg, rgba(255, 253, 248, 0.98), rgba(255, 246, 232, 0.94));
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
}

.time-entry-panel--exact {
  border-color: rgba(64, 158, 255, 0.22);
  background: rgba(248, 251, 255, 0.98);
}

.time-entry-panel--estimated {
  border-color: rgba(212, 175, 55, 0.2);
  background: rgba(255, 252, 245, 0.98);
}

.time-entry-panel__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.time-entry-panel__badge {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  background: rgba(var(--primary-rgb), 0.1);
  color: #8d5f1c;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.time-entry-panel__title {
  margin: 0;
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.time-entry-panel__grid {
  margin-top: 0;
}

.time-entry-panel__hint {
  margin-top: 12px;
  color: #5f5548;
  line-height: 1.75;
}

.estimate-birth-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.full-width {
  width: 100%;
}

.form-hint {
  color: var(--text-secondary);
  font-size: 13px;
  margin-top: 10px;
  display: flex;
  align-items: center;
  gap: 5px;
  line-height: 1.6;
}

.form-hint--precision {
  color: var(--warning-color);
  font-weight: 600;
}

/* Element Plus 深度样式 */
:deep(.time-entry-panel__control .el-input__wrapper),
:deep(.form-group .el-input__wrapper),
:deep(.form-group .el-select__wrapper),
:deep(.form-group .el-textarea__inner) {
  border-radius: 10px;
  box-shadow: none;
  background: var(--bg-primary);
  border: 1px solid var(--border-light);
  min-height: 44px;
}

:deep(.time-entry-panel__control .el-input__wrapper:hover),
:deep(.form-group .el-input__wrapper:hover),
:deep(.form-group .el-select__wrapper:hover) {
  border-color: rgba(212, 175, 55, 0.4);
}

:deep(.time-accuracy-group .el-radio-button) {
  flex: 1 1 180px;
}

:deep(.time-accuracy-group .el-radio-button__inner) {
  width: 100%;
  min-height: 74px;
  padding: 14px 18px;
  border-radius: 18px !important;
  border: 1px solid rgba(var(--primary-rgb), 0.12) !important;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: none !important;
}

:deep(.time-accuracy-group .el-radio-button:first-child .el-radio-button__inner),
:deep(.time-accuracy-group .el-radio-button:last-child .el-radio-button__inner) {
  border-radius: 18px !important;
}

:deep(.time-accuracy-group .el-radio-button__original-radio:checked + .el-radio-button__inner) {
  background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.14), rgba(245, 196, 103, 0.18));
  border-color: rgba(var(--primary-rgb), 0.24) !important;
  color: var(--text-primary);
  box-shadow: 0 12px 26px rgba(var(--primary-rgb), 0.14) !important;
}

/* 出生地 */
.birth-city-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 12px;
  padding: 10px 16px;
  border-radius: 12px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-light);
  flex-wrap: wrap;
}

.birth-city-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
}

.birth-city-select {
  flex: 1;
  min-width: 160px;
}

.birth-city-hint {
  font-size: 12px;
  color: var(--text-tertiary, #999);
  white-space: nowrap;
}

/* 性别选择 */
.gender-inline {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 16px;
  padding: 12px 16px;
  border-radius: 12px;
  background: var(--bg-secondary);
  border: 1px solid var(--border-light);
}

.gender-inline__label {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
}

.gender-selector {
  display: flex;
  gap: 8px;
}

.gender-option {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 18px;
  border-radius: 999px;
  border: 1.5px solid var(--border-light);
  background: var(--bg-primary);
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  color: var(--text-secondary);
  transition: all 0.2s ease;
  outline: none;
}

.gender-option:hover {
  border-color: rgba(212, 175, 55, 0.4);
  color: var(--text-primary);
}

.gender-option.active {
  border-color: var(--primary-color);
  background: linear-gradient(135deg, rgba(212, 175, 55, 0.1), rgba(245, 196, 103, 0.06));
  color: #8a5c16;
  box-shadow: 0 2px 8px rgba(212, 175, 55, 0.15);
}

.gender-option__icon {
  font-size: 16px;
  line-height: 1;
}

.gender-option__text {
  font-size: 14px;
  font-weight: 700;
}

/* 提交校验卡片 */
.submit-summary-card {
  max-width: 920px;
  margin: 0 auto 24px;
  padding: 18px 20px;
  border-radius: 18px;
  border: 1px solid rgba(230, 162, 60, 0.24);
  background: linear-gradient(135deg, rgba(255, 250, 242, 0.98), rgba(255, 245, 228, 0.98));
  box-shadow: 0 14px 28px rgba(149, 111, 45, 0.08);
}

.submit-summary-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.submit-summary-card__header div {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.submit-summary-card__header strong {
  color: var(--text-primary);
  font-size: 16px;
}

.submit-summary-card__header p {
  margin: 0;
  color: var(--text-secondary);
  font-size: 13px;
  line-height: 1.7;
}

.submit-summary-card__header > .el-icon {
  margin-top: 2px;
  color: var(--warning-color);
  font-size: 20px;
}

.submit-summary-card__actions {
  display: grid;
  gap: 10px;
  margin-top: 16px;
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
  box-shadow: 0 12px 24px rgba(149, 111, 45, 0.08);
}

/* 费用确认区域 */
.cost-confirm-section {
  margin-top: 28px;
  padding: 20px;
  border-radius: 14px;
  border: 1px solid var(--border-light);
  background: var(--bg-secondary);
}

.cost-confirm-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.cost-confirm-section__title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
  letter-spacing: 0.01em;
}

.cost-confirm-section__points {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px 5px 10px;
  border-radius: 999px;
  background: rgba(184, 134, 11, 0.1);
  border: 1px solid rgba(184, 134, 11, 0.22);
  font-size: 13px;
  color: #8c641f;
  font-weight: 600;
}

.cost-confirm-section__add {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: var(--primary-color);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  line-height: 1;
  transition: opacity 0.15s;
}

.cost-confirm-section__add:hover {
  opacity: 0.82;
}

.cost-confirm-section__cost {
  font-size: 14px;
  color: var(--text-primary);
  font-weight: 700;
  margin: 0 0 14px;
}

.cost-confirm-section__cost strong {
  color: #8c641f;
}

.cost-confirm-section__benefits {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 9px 20px;
  margin-bottom: 22px;
}

.cost-confirm-benefit {
  display: flex;
  align-items: flex-start;
  gap: 7px;
  font-size: 13px;
  color: var(--text-secondary);
  line-height: 1.55;
}

.cost-confirm-benefit .el-icon {
  color: #52c41a;
  flex-shrink: 0;
  margin-top: 2px;
}

.cost-confirm-section__btn {
  width: 100%;
  height: 54px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 14px;
  background: linear-gradient(135deg, #c8960c, #d4af37) !important;
  border: none !important;
  color: #fff !important;
  letter-spacing: 0.06em;
  box-shadow: 0 4px 16px rgba(184, 134, 11, 0.28);
  transition: opacity 0.18s, box-shadow 0.18s;
}

.cost-confirm-section__btn:hover:not(:disabled) {
  opacity: 0.92;
  box-shadow: 0 6px 22px rgba(184, 134, 11, 0.36);
}

/* 积分不足 */
.insufficient-points {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 18px;
  padding: 12px;
  font-size: 13px;
  color: var(--text-secondary);
  border-radius: 18px;
  border: 1px solid rgba(217, 119, 6, 0.18);
  background: rgba(255, 247, 237, 0.92);
}

.insufficient-points .el-icon {
  color: var(--primary-color);
  flex-shrink: 0;
}

.insufficient-points a {
  color: var(--primary-color);
  font-weight: 600;
  text-decoration: none;
}

.insufficient-points a:hover {
  text-decoration: underline;
}

/* 确认对话框 */
.confirm-dialog .confirm-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 8px 4px;
  color: var(--text-secondary);
  line-height: 1.7;
}

.confirm-dialog .confirm-content p {
  margin: 0;
}

.confirm-dialog .confirm-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-primary);
  font-size: 16px;
  font-weight: 600;
}

.confirm-dialog .confirm-title strong {
  color: var(--star-color);
}

.confirm-dialog .confirm-note {
  padding: 10px 12px;
  border-radius: 12px;
  background: var(--primary-light-05);
  border: 1px solid var(--primary-light-20);
  color: var(--text-secondary);
  font-size: 13px;
}

.confirm-dialog .confirm-content--free .confirm-title {
  color: var(--success-color);
}

/* 积分确认对话框 */
.points-confirm-dialog .points-confirm-content {
  text-align: center;
  padding: 20px;
}

.points-confirm-dialog .points-icon {
  font-size: 48px;
  margin-bottom: 15px;
}

.points-confirm-dialog .points-title {
  font-size: 20px;
  color: var(--text-primary);
  font-weight: bold;
  margin-bottom: 10px;
}

.points-confirm-dialog .points-desc {
  font-size: 16px;
  color: #5a4a38;
  margin-bottom: 10px;
}

.points-confirm-dialog .points-desc strong {
  color: var(--star-color);
  font-size: 20px;
}

.points-confirm-dialog .points-balance {
  font-size: 14px;
  color: #5f5548;
}

/* 响应式 */
@media (max-width: 768px) {
  .warm-tip {
    grid-template-columns: 1fr;
    padding: 18px;
    gap: 14px;
  }

  .bazi-form {
    padding: 22px 18px;
    border-radius: var(--radius-xl);
    padding-bottom: 30vh;
  }

  .form-group,
  .time-entry-panel {
    padding: 18px 16px;
  }

  .form-group__header--time,
  .time-accuracy-switch,
  .time-entry-panel__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .form-group__status,
  .time-entry-panel__badge {
    white-space: normal;
    width: fit-content;
  }

  .time-accuracy-switch__copy,
  .time-accuracy-group,
  .estimate-birth-grid {
    width: 100%;
    max-width: none;
  }

  .estimate-birth-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  :deep(.time-accuracy-group .el-radio-button__inner) {
    min-height: 66px;
    padding: 12px 14px;
  }

  .version-badge-pro {
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 18px;
  }
}
</style>