<template>
  <div class="app-container">
    <el-card v-loading="loadingSettings">
      <template #header>
        <div class="card-header">
          <span>系统基础配置</span>
          <el-button type="primary" :loading="savingSettings" :disabled="settingsReadonly" @click="handleSave">
            保存配置
          </el-button>
        </div>
      </template>

      <div v-if="settingsError" class="page-state">
        <el-result icon="warning" :title="settingsError.title" :sub-title="settingsError.description">
          <template #extra>
            <el-button @click="loadSettingsData" :loading="loadingSettings">重新加载</el-button>
          </template>
        </el-result>
      </div>

      <el-alert
        v-else-if="settingsSaveFeedback"
        :title="settingsSaveFeedback.title"
        :type="settingsSaveFeedback.type"
        :closable="false"
        show-icon
        class="mb-4"
      >
        <template #default>
          <div class="settings-save-feedback">
            <p>{{ settingsSaveFeedback.description }}</p>
            <ul v-if="settingsSaveFeedback.mismatches?.length" class="settings-save-feedback__list">
              <li v-for="item in settingsSaveFeedback.mismatches" :key="item.key">
                {{ item.label }}：提交 {{ item.expectedText }}，回读 {{ item.actualText }}
              </li>
            </ul>
          </div>
        </template>
      </el-alert>

      <el-form v-if="!settingsError" :model="form" :disabled="settingsReadonly" label-width="150px">
        <el-divider content-position="left">网站信息</el-divider>

        <el-form-item label="网站名称">
          <el-input v-model="form.site_name" style="width: 320px" />
        </el-form-item>

        <el-form-item label="网站 Logo">
          <el-upload
            class="avatar-uploader"
            :action="uploadAction"
            :headers="uploadHeaders"
            :show-file-list="false"
            :disabled="settingsReadonly"
            :on-success="handleLogoSuccess"
            :on-error="handleLogoError"
          >
            <img v-if="form.logo" :src="form.logo" class="avatar" />
            <el-icon v-else class="avatar-uploader-icon"><Plus /></el-icon>
          </el-upload>
          <el-text type="info" size="small" class="ml-2">建议上传透明底 PNG，保存后前台即可读取新 Logo。</el-text>
        </el-form-item>

        <el-form-item label="网站描述">
          <el-input v-model="form.site_description" type="textarea" rows="3" style="width: 420px" />
        </el-form-item>

        <el-divider content-position="left">积分配置</el-divider>

        <el-form-item label="注册赠送积分">
          <el-input-number v-model="form.register_points" :min="0" />
        </el-form-item>

        <el-form-item label="每日签到积分">
          <el-input-number v-model="form.checkin_points" :min="0" />
        </el-form-item>

        <el-form-item label="八字排盘消耗">
          <el-input-number v-model="form.bazi_cost" :min="0" />
        </el-form-item>

        <el-form-item label="塔罗占卜消耗">
          <el-input-number v-model="form.tarot_cost" :min="0" />
        </el-form-item>

        <el-divider content-position="left">功能开关</el-divider>

        <el-form-item label="注册功能">
          <el-switch v-model="form.enable_register" />
        </el-form-item>

        <el-form-item label="每日运势">
          <el-switch v-model="form.enable_daily" />
        </el-form-item>

        <el-form-item label="用户反馈">
          <el-switch v-model="form.enable_feedback" />
        </el-form-item>

        <el-form-item label="AI 解盘">
          <el-switch v-model="form.enable_ai_analysis" />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" :loading="savingSettings" :disabled="settingsReadonly" @click="handleSave">保存配置</el-button>
          <el-button :disabled="settingsReadonly" @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card class="mt-4">
      <template #header>
        <div class="card-header">
          <span>AI 配置</span>
        </div>
      </template>
      <el-result
        icon="info"
        title="AI 配置已迁移至独立模块"
        sub-title="为避免配置分散，AI 相关配置（模型、API 密钥、提示词等）已统一至 AI 管理模块"
      >
        <template #extra>
          <el-button type="primary" @click="$router.push('/ai/config')">前往 AI 配置</el-button>
          <el-button @click="$router.push('/ai/prompts')">前往提示词管理</el-button>
        </template>
      </el-result>
    </el-card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { getSettings, saveSettings } from '@/api/system'
import { useUserStore } from '@/stores/user'
import { createReadonlyErrorState } from '@/utils/page-error'

const userStore = useUserStore()
const loadingSettings = ref(false)
const savingSettings = ref(false)
const settingsError = ref(null)
const settingsSaveFeedback = ref(null)

const settingsReadonly = computed(() => Boolean(settingsError.value))

function createDefaultSettings() {
  return {
    site_name: '',
    logo: '',
    site_description: '',
    register_points: 0,
    checkin_points: 0,
    bazi_cost: 0,
    tarot_cost: 0,
    enable_register: false,
    enable_daily: false,
    enable_feedback: false,
    enable_ai_analysis: false
  }
}

const SETTINGS_FIELDS = [
  { key: 'site_name', label: '网站名称', type: 'string' },
  { key: 'logo', label: '网站 Logo', type: 'string' },
  { key: 'site_description', label: '网站描述', type: 'string' },
  { key: 'register_points', label: '注册赠送积分', type: 'number' },
  { key: 'checkin_points', label: '每日签到积分', type: 'number' },
  { key: 'bazi_cost', label: '八字排盘消耗', type: 'number' },
  { key: 'tarot_cost', label: '塔罗占卜消耗', type: 'number' },
  { key: 'enable_register', label: '注册功能', type: 'boolean' },
  { key: 'enable_daily', label: '每日运势', type: 'boolean' },
  { key: 'enable_feedback', label: '用户反馈', type: 'boolean' },
  { key: 'enable_ai_analysis', label: 'AI 解盘', type: 'boolean' }
]

function normalizeSettingsValue(value, type) {
  if (type === 'number') {
    const nextValue = Number(value)
    return Number.isFinite(nextValue) ? nextValue : 0
  }

  if (type === 'boolean') {
    return Boolean(value)
  }

  return value == null ? '' : String(value)
}

function normalizeSettingsPayload(settings = {}) {
  const normalized = createDefaultSettings()

  SETTINGS_FIELDS.forEach(({ key, type }) => {
    normalized[key] = normalizeSettingsValue(settings[key], type)
  })

  return normalized
}

function applySettingsForm(settings = {}) {
  const nextForm = normalizeSettingsPayload(settings)
  form.value = nextForm
  originalForm.value = { ...nextForm }
  return nextForm
}

function formatSettingsValue(value, type) {
  if (type === 'boolean') {
    return value ? '开启' : '关闭'
  }

  if (type === 'number') {
    return String(value)
  }

  return value ? `“${value}”` : '（空）'
}

function getSettingsMismatches(expected, actual) {
  return SETTINGS_FIELDS.reduce((list, field) => {
    if (expected[field.key] === actual[field.key]) {
      return list
    }

    list.push({
      key: field.key,
      label: field.label,
      expectedText: formatSettingsValue(expected[field.key], field.type),
      actualText: formatSettingsValue(actual[field.key], field.type)
    })
    return list
  }, [])
}

function createSettingsFeedback(type, title, description, mismatches = []) {
  return {
    type,
    title,
    description,
    mismatches
  }
}


const form = ref(createDefaultSettings())
const originalForm = ref(createDefaultSettings())

const uploadAction = '/api/upload/image'
const uploadHeaders = computed(() => ({
  Authorization: userStore.token ? `Bearer ${userStore.token}` : ''
}))

onMounted(() => {
  loadSettingsData()
})

async function fetchSettingsSnapshot() {
  const { data } = await getSettings({ showErrorMessage: false })
  return normalizeSettingsPayload(data)
}

async function loadSettingsData() {
  loadingSettings.value = true
  try {
    const nextForm = await fetchSettingsSnapshot()
    applySettingsForm(nextForm)
    settingsError.value = null
  } catch (error) {
    settingsError.value = createReadonlyErrorState(error, '系统基础配置', 'config_manage')
  } finally {
    loadingSettings.value = false
  }
}



function handleLogoSuccess(response) {
  if (response?.code === 0 && response?.data?.url) {
    form.value.logo = response.data.url
    ElMessage.success('Logo 上传成功')
    return
  }

  ElMessage.error(response?.message || 'Logo 上传失败')
}

function handleLogoError() {
  ElMessage.error('Logo 上传失败，请检查登录状态或文件格式')
}

async function handleSave() {
  if (settingsReadonly.value) {
    ElMessage.warning('系统基础配置尚未成功加载，当前为只读保护状态')
    return
  }

  settingsSaveFeedback.value = null
  const payload = normalizeSettingsPayload(form.value)

  savingSettings.value = true
  try {
    await saveSettings(payload, { showErrorMessage: false })

    let latestSettings = null
    try {
      latestSettings = await fetchSettingsSnapshot()
    } catch (verifyError) {
      settingsSaveFeedback.value = createSettingsFeedback(
        'warning',
        '保存请求已提交，但暂时无法确认是否真正生效',
        verifyError.message || '重新读取系统设置失败，请稍后刷新页面或再次尝试保存。'
      )
      ElMessage.warning('保存请求已提交，但回读校验失败')
      return
    }

    applySettingsForm(latestSettings)
    settingsError.value = null

    const mismatches = getSettingsMismatches(payload, latestSettings)
    if (mismatches.length) {
      settingsSaveFeedback.value = createSettingsFeedback(
        'error',
        '保存请求已返回成功，但服务端回读与提交值不一致',
        '页面已刷新为服务端当前值，请先排查系统设置接口的读写口径，再继续操作。',
        mismatches
      )
      ElMessage.error('系统设置未真实生效，已刷新为服务端当前配置')
      return
    }

    settingsSaveFeedback.value = createSettingsFeedback(
      'success',
      '系统设置已保存并通过回读校验',
      '当前页面展示的就是服务端刚刚回读的最新配置。'
    )
    ElMessage.success('系统设置已保存并生效')
  } catch (error) {
    settingsSaveFeedback.value = createSettingsFeedback(
      'error',
      '系统设置保存失败',
      error.message || '请稍后重试。'
    )
    ElMessage.error(error.message || '保存系统设置失败')
  } finally {
    savingSettings.value = false
  }
}

function handleReset() {
  if (settingsReadonly.value) {
    ElMessage.warning('系统基础配置尚未成功加载，无法重置')
    return
  }

  settingsSaveFeedback.value = null
  form.value = { ...originalForm.value }
}



</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.action-group {
  display: flex;
  gap: 12px;
}

.page-state {
  padding: 12px 0;
}

.settings-save-feedback {
  font-size: 13px;
  line-height: 1.6;

  p {
    margin: 0;
  }
}

.settings-save-feedback__list {
  margin: 8px 0 0;
  padding-left: 18px;
}

.avatar-uploader {

  :deep(.el-upload) {
    border: 1px dashed var(--el-border-color);
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: var(--el-transition-duration-fast);

    &:hover {
      border-color: var(--el-color-primary);
    }
  }
}

.avatar-uploader-icon {
  width: 100px;
  height: 100px;
  font-size: 28px;
  color: #8c939d;
  text-align: center;
  line-height: 100px;
}

.avatar {
  width: 100px;
  height: 100px;
  display: block;
  object-fit: contain;
}
</style>
