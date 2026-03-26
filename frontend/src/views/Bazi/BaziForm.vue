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
