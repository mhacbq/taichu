<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>通知配置</span>
          <el-button type="primary" :loading="saving" :disabled="readonlyMode" @click="handleSave">
            保存配置
          </el-button>
        </div>
      </template>

      <div v-if="pageError" class="page-state">
        <el-result icon="warning" :title="pageError.title" :sub-title="pageError.description">
          <template #extra>
            <el-button type="primary" :loading="loading" @click="loadPage">重新加载</el-button>
          </template>
        </el-result>
      </div>

      <template v-else>
        <el-alert
          title="这里维护的是站内通知默认开关与免打扰时段；测试通知会按指定用户ID定向发送。"
          type="info"
          show-icon
          class="mb-4"
        />

        <el-row :gutter="16" class="summary-row">
          <el-col :md="8" :sm="12" :xs="24">
            <div class="summary-item">
              <div class="summary-label">推送提供商</div>
              <div class="summary-value">{{ summary.provider || '未配置' }}</div>
            </div>
          </el-col>
          <el-col :md="8" :sm="12" :xs="24">
            <div class="summary-item">
              <div class="summary-label">活跃设备数</div>
              <div class="summary-value">{{ summary.active_device_count ?? 0 }}</div>
            </div>
          </el-col>
          <el-col :md="8" :sm="12" :xs="24">
            <div class="summary-item">
              <div class="summary-label">配置存储模式</div>
              <div class="summary-value">{{ formatTableMode(summary.table_mode) }}</div>
            </div>
          </el-col>
        </el-row>

        <el-form :model="form" :disabled="readonlyMode" label-width="150px" class="mt-4">
          <el-divider content-position="left">通知开关</el-divider>
          <el-form-item label="每日运势通知">
            <el-switch v-model="form.daily_fortune" />
          </el-form-item>
          <el-form-item label="系统公告通知">
            <el-switch v-model="form.system_notice" />
          </el-form-item>
          <el-form-item label="活动通知">
            <el-switch v-model="form.activity" />
          </el-form-item>
          <el-form-item label="充值通知">
            <el-switch v-model="form.recharge" />
          </el-form-item>
          <el-form-item label="积分变动通知">
            <el-switch v-model="form.points_change" />
          </el-form-item>
          <el-form-item label="设备推送总开关">
            <el-switch v-model="form.push_enabled" />
          </el-form-item>
          <el-form-item label="声音提醒">
            <el-switch v-model="form.sound_enabled" />
          </el-form-item>
          <el-form-item label="震动提醒">
            <el-switch v-model="form.vibration_enabled" />
          </el-form-item>

          <el-divider content-position="left">免打扰时段</el-divider>
          <el-form-item label="开始时间">
            <el-input v-model="form.quiet_hours_start" placeholder="22:00" style="width: 180px;" />
            <el-text type="info" size="small" class="ml-2">24 小时制，例如 22:00</el-text>
          </el-form-item>
          <el-form-item label="结束时间">
            <el-input v-model="form.quiet_hours_end" placeholder="08:00" style="width: 180px;" />
          </el-form-item>
        </el-form>
      </template>
    </el-card>

    <el-card class="mt-4" v-loading="sendingTest">
      <template #header>
        <div class="card-header">
          <span>测试通知</span>
          <el-button type="primary" :loading="sendingTest" :disabled="readonlyMode" @click="handleSendTest">
            发送测试通知
          </el-button>
        </div>
      </template>

      <el-form :model="testForm" :disabled="readonlyMode || sendingTest" label-width="120px">
        <el-form-item label="测试用户ID" required>
          <el-input-number v-model="testForm.user_id" :min="1" :max="99999999" />
          <el-text type="info" size="small" class="ml-2">请填写需要接收测试通知的真实用户 ID</el-text>
        </el-form-item>
        <el-form-item label="通知标题">
          <el-input v-model="testForm.title" maxlength="60" show-word-limit placeholder="后台测试通知" style="max-width: 420px;" />
        </el-form-item>
        <el-form-item label="通知内容">
          <el-input
            v-model="testForm.content"
            type="textarea"
            :rows="4"
            maxlength="200"
            show-word-limit
            placeholder="这是一条来自管理后台的测试通知，用于验证通知配置是否生效。"
            style="max-width: 520px;"
          />
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { createReadonlyErrorState } from '@/utils/page-error'
import { getNotificationConfig, saveNotificationConfig, sendNotificationTest } from '@/api/system'

const loading = ref(false)
const saving = ref(false)
const sendingTest = ref(false)
const pageError = ref(null)
const summary = ref({})
const form = ref(createDefaultForm())
const originalForm = ref(createDefaultForm())
const testForm = ref(createDefaultTestForm())

const readonlyMode = computed(() => Boolean(pageError.value))

function createDefaultForm() {
  return {
    daily_fortune: true,
    system_notice: true,
    activity: true,
    recharge: true,
    points_change: true,
    push_enabled: true,
    sound_enabled: true,
    vibration_enabled: true,
    quiet_hours_start: '22:00',
    quiet_hours_end: '08:00'
  }
}

function createDefaultTestForm() {
  return {
    user_id: 1,
    title: '后台测试通知',
    content: '这是一条来自管理后台的测试通知，用于验证通知配置是否生效。'
  }
}

function normalizeSettings(data = {}) {
  return {
    daily_fortune: Boolean(Number(data.daily_fortune ?? 1)),
    system_notice: Boolean(Number(data.system_notice ?? 1)),
    activity: Boolean(Number(data.activity ?? 1)),
    recharge: Boolean(Number(data.recharge ?? 1)),
    points_change: Boolean(Number(data.points_change ?? 1)),
    push_enabled: Boolean(Number(data.push_enabled ?? 1)),
    sound_enabled: Boolean(Number(data.sound_enabled ?? 1)),
    vibration_enabled: Boolean(Number(data.vibration_enabled ?? 1)),
    quiet_hours_start: String(data.quiet_hours_start || '22:00'),
    quiet_hours_end: String(data.quiet_hours_end || '08:00')
  }
}

function formatTableMode(mode) {
  if (mode === 'narrow') {
    return '按类型分行'
  }
  if (mode === 'wide') {
    return '宽表单行'
  }
  return '未知'
}

function isValidHourMinute(value) {
  return /^(?:[01]\d|2[0-3]):[0-5]\d$/.test(String(value || '').trim())
}

async function loadPage() {
  loading.value = true
  try {
    const { data } = await getNotificationConfig({ showErrorMessage: false })
    form.value = normalizeSettings(data?.settings)
    originalForm.value = { ...form.value }
    summary.value = data?.summary || {}
    pageError.value = null
  } catch (error) {
    form.value = createDefaultForm()
    originalForm.value = createDefaultForm()
    summary.value = {}
    pageError.value = createReadonlyErrorState(error, '通知配置', 'admin / operator')
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  if (readonlyMode.value) {
    ElMessage.warning('通知配置尚未成功加载，当前为只读保护状态')
    return
  }

  if (!isValidHourMinute(form.value.quiet_hours_start) || !isValidHourMinute(form.value.quiet_hours_end)) {
    ElMessage.warning('免打扰时间格式无效，请使用 HH:MM')
    return
  }

  saving.value = true
  try {
    const payload = {
      daily_fortune: form.value.daily_fortune ? 1 : 0,
      system_notice: form.value.system_notice ? 1 : 0,
      activity: form.value.activity ? 1 : 0,
      recharge: form.value.recharge ? 1 : 0,
      points_change: form.value.points_change ? 1 : 0,
      push_enabled: form.value.push_enabled ? 1 : 0,
      sound_enabled: form.value.sound_enabled ? 1 : 0,
      vibration_enabled: form.value.vibration_enabled ? 1 : 0,
      quiet_hours_start: form.value.quiet_hours_start.trim(),
      quiet_hours_end: form.value.quiet_hours_end.trim()
    }
    await saveNotificationConfig(payload, { showErrorMessage: false })
    ElMessage.success('通知配置已保存')
    await loadPage()
  } catch (error) {
    ElMessage.error(error.message || '保存通知配置失败')
  } finally {
    saving.value = false
  }
}

async function handleSendTest() {
  if (readonlyMode.value) {
    ElMessage.warning('通知配置尚未成功加载，暂时无法发送测试通知')
    return
  }

  const userId = Number(testForm.value.user_id || 0)
  if (!Number.isInteger(userId) || userId <= 0) {
    ElMessage.warning('请填写有效的测试用户ID')
    return
  }

  const title = String(testForm.value.title || '').trim() || '后台测试通知'
  const content = String(testForm.value.content || '').trim() || '这是一条来自管理后台的测试通知，用于验证通知配置是否生效。'

  sendingTest.value = true
  try {
    const res = await sendNotificationTest({
      user_id: userId,
      title,
      content
    }, { showErrorMessage: false })
    ElMessage.success(res.message || '测试通知发送成功')
  } catch (error) {
    ElMessage.error(error.message || '测试通知发送失败')
  } finally {
    sendingTest.value = false
  }
}

onMounted(() => {
  loadPage()
})
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.page-state {
  padding: 12px 0;
}

.summary-row {
  margin-top: 8px;
}

.summary-item {
  padding: 16px;
  border-radius: 8px;
  background: #f7f9fc;
  margin-bottom: 12px;
}

.summary-label {
  font-size: 13px;
  color: #909399;
}

.summary-value {
  margin-top: 8px;
  font-size: 24px;
  font-weight: 600;
  color: #303133;
}

.mt-4 {
  margin-top: 16px;
}

.mb-4 {
  margin-bottom: 16px;
}

.ml-2 {
  margin-left: 8px;
}
</style>
