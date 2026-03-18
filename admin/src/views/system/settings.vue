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

      <el-form v-else :model="form" :disabled="settingsReadonly" label-width="150px">
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

    <el-card class="mt-4" v-loading="loadingAi">
      <template #header>
        <div class="card-header">
          <span>AI 解盘配置</span>
          <div class="action-group">
            <el-button type="primary" :loading="savingAi" :disabled="aiReadonly" @click="handleSaveAiConfig">保存 AI 配置</el-button>
            <el-button :loading="testingAi" :disabled="aiReadonly" @click="handleTestAiConnection">测试连接</el-button>
          </div>
        </div>
      </template>

      <div v-if="aiError" class="page-state">
        <el-result icon="warning" :title="aiError.title" :sub-title="aiError.description">
          <template #extra>
            <el-button @click="loadAiConfigData" :loading="loadingAi">重新加载</el-button>
          </template>
        </el-result>
      </div>

      <el-form v-else :model="aiForm" :disabled="aiReadonly" label-width="150px">
        <el-alert
          title="AI 解盘功能需要配置兼容 OpenAI 的外部模型服务"
          description="保存后后台会立即刷新缓存，前台新请求会直接读取最新配置。"
          type="info"
          show-icon
          class="mb-4"
        />

        <el-form-item label="启用 AI 解盘">
          <el-switch v-model="aiForm.enable_bazi_analysis" />
        </el-form-item>

        <el-form-item label="API 地址">
          <el-input
            v-model="aiForm.api_url"
            placeholder="例如：https://aiping.cn/api/v1/chat/completions"
            style="width: 420px"
          />
        </el-form-item>

        <el-form-item label="API 密钥">
          <el-input
            v-model="aiForm.api_key"
            type="password"
            show-password
            :placeholder="apiKeyPlaceholder"
            style="width: 420px"
          />
          <el-text type="info" size="small" class="ml-2">
            {{ isApiKeyMasked ? '密钥已配置，留空则保持原值' : '密钥将由后端脱敏存储' }}
          </el-text>
        </el-form-item>

        <el-form-item label="模型名称">
          <el-input
            v-model="aiForm.model"
            placeholder="例如：DeepSeek-V3.2"
            style="width: 320px"
          />
        </el-form-item>

        <el-form-item label="启用流式输出">
          <el-switch v-model="aiForm.enable_streaming" />
        </el-form-item>

        <el-form-item label="启用思考过程">
          <el-switch v-model="aiForm.enable_thinking" />
        </el-form-item>

        <el-form-item label="积分消耗">
          <el-input-number v-model="aiForm.cost_points" :min="0" :max="100" />
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { getSettings, saveSettings } from '@/api/system'
import { getAiConfig, saveAiConfig, testAiConnection } from '@/api/ai'
import { useUserStore } from '@/stores/user'
import { createReadonlyErrorState } from '@/utils/page-error'

const userStore = useUserStore()
const loadingSettings = ref(false)
const loadingAi = ref(false)
const savingSettings = ref(false)
const savingAi = ref(false)
const testingAi = ref(false)
const settingsError = ref(null)
const aiError = ref(null)
const isApiKeyMasked = ref(false)
const apiKeyPlaceholder = ref('请输入 API 密钥')

const settingsReadonly = computed(() => Boolean(settingsError.value))
const aiReadonly = computed(() => Boolean(aiError.value))

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

function createDefaultAiSettings() {
  return {
    enable_bazi_analysis: false,
    api_url: '',
    api_key: '',
    model: '',
    enable_streaming: false,
    enable_thinking: false,
    cost_points: 0
  }
}

const form = ref(createDefaultSettings())
const originalForm = ref(createDefaultSettings())
const aiForm = ref(createDefaultAiSettings())
const originalAiForm = ref(createDefaultAiSettings())

const uploadAction = '/api/upload/image'
const uploadHeaders = computed(() => ({
  Authorization: userStore.token ? `Bearer ${userStore.token}` : ''
}))

onMounted(() => {
  loadSettingsData()
  loadAiConfigData()
})

async function loadSettingsData() {
  loadingSettings.value = true
  try {
    const { data } = await getSettings({ showErrorMessage: false })
    form.value = {
      ...createDefaultSettings(),
      ...data
    }
    originalForm.value = { ...form.value }
    settingsError.value = null
  } catch (error) {
    settingsError.value = createReadonlyErrorState(error, '系统基础配置', 'config_manage')
  } finally {
    loadingSettings.value = false
  }
}

async function loadAiConfigData() {
  loadingAi.value = true
  try {
    const res = await getAiConfig({ showErrorMessage: false })
    const nextForm = {
      ...createDefaultAiSettings(),
      ...(res.data || {})
    }

    if (nextForm.api_key && String(nextForm.api_key).includes('****')) {
      isApiKeyMasked.value = true
      apiKeyPlaceholder.value = nextForm.api_key
      nextForm.api_key = ''
    } else {
      isApiKeyMasked.value = false
      apiKeyPlaceholder.value = '请输入 API 密钥'
    }

    aiForm.value = nextForm
    originalAiForm.value = { ...nextForm }
    aiError.value = null
  } catch (error) {
    aiForm.value = createDefaultAiSettings()
    originalAiForm.value = createDefaultAiSettings()
    isApiKeyMasked.value = false
    apiKeyPlaceholder.value = '请输入 API 密钥'
    aiError.value = createReadonlyErrorState(error, 'AI 解盘配置', 'admin / config_manage')
  } finally {
    loadingAi.value = false
  }
}

function handleLogoSuccess(response) {
  if (response?.code === 200 && response?.data?.url) {
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

  savingSettings.value = true
  try {
    await saveSettings(form.value, { showErrorMessage: false })
    ElMessage.success('系统设置已保存')
    await loadSettingsData()
  } catch (error) {
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

  form.value = { ...originalForm.value }
}

async function handleSaveAiConfig() {
  if (aiReadonly.value) {
    ElMessage.warning('AI 配置尚未成功加载，当前为只读保护状态')
    return
  }

  savingAi.value = true
  try {
    const payload = { ...aiForm.value }
    if (!payload.api_key && isApiKeyMasked.value) {
      delete payload.api_key
    }

    await saveAiConfig(payload, { showErrorMessage: false })
    ElMessage.success('AI 配置已保存')
    await loadAiConfigData()
  } catch (error) {
    ElMessage.error(error.message || '保存 AI 配置失败')
  } finally {
    savingAi.value = false
  }
}

async function handleTestAiConnection() {
  if (aiReadonly.value) {
    ElMessage.warning('AI 配置尚未成功加载，暂时无法测试连接')
    return
  }

  testingAi.value = true
  try {
    const payload = { ...aiForm.value }
    if (!payload.api_key && isApiKeyMasked.value) {
      delete payload.api_key
    }

    const res = await testAiConnection(payload, { showErrorMessage: false })
    ElMessage.success(`连接成功：${res.data?.model || '模型可用'}`)
  } catch (error) {
    ElMessage.error(error.message || '测试连接失败')
  } finally {
    testingAi.value = false
  }
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
