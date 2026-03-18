<template>
  <div class="app-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <div>
            <div class="title">微信支付配置</div>
            <div class="subtitle">这里维护充值订单与退款链路使用的微信商户参数。</div>
          </div>
          <el-button type="primary" @click="handleSave" :loading="saving">
            <el-icon><Check /></el-icon>保存配置
          </el-button>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="170px"
        class="config-form"
        v-loading="loading"
      >
        <el-divider>基础配置</el-divider>

        <el-form-item label="启用微信支付">
          <el-switch v-model="form.is_enabled" active-text="启用" inactive-text="禁用" />
        </el-form-item>

        <el-form-item label="公众号 / 应用 AppID" prop="app_id">
          <el-input v-model="form.app_id" placeholder="请输入微信支付 AppID" clearable />
        </el-form-item>

        <el-form-item label="商户号 MchID" prop="mch_id">
          <el-input v-model="form.mch_id" placeholder="请输入商户号" clearable />
        </el-form-item>

        <el-form-item label="API 密钥" prop="api_key">
          <el-input
            v-model="form.api_key"
            type="password"
            show-password
            clearable
            :placeholder="apiKeyPlaceholder"
          />
          <div class="form-tip">
            {{ form.api_key_masked ? '密钥已配置，留空则沿用旧值；输入新值即可覆盖。' : '请输入微信支付 v2 API 密钥。' }}
          </div>
        </el-form-item>

        <el-form-item label="支付回调地址" prop="notify_url">
          <el-input v-model="form.notify_url" placeholder="https://your-domain.com/api/payment/notify" clearable />
        </el-form-item>

        <el-divider>证书配置</el-divider>

        <el-alert
          title="证书内容仅在需要退款时使用"
          type="info"
          :closable="false"
          show-icon
          class="mb-4"
        />

        <el-form-item label="商户证书 PEM">
          <el-input
            v-model="form.api_cert"
            type="textarea"
            :rows="6"
            placeholder="粘贴 apiclient_cert.pem 内容；留空则保留已存在证书"
          />
          <div class="form-tip">当前状态：{{ form.has_cert ? '已存在证书' : '未配置证书' }}</div>
        </el-form-item>

        <el-form-item label="商户私钥 PEM">
          <el-input
            v-model="form.api_key_pem"
            type="textarea"
            :rows="6"
            placeholder="粘贴 apiclient_key.pem 内容；留空则保留已存在私钥"
          />
          <div class="form-tip">当前状态：{{ form.has_key_pem ? '已存在私钥' : '未配置私钥' }}</div>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never" class="mt-4">
      <template #header>
        <div class="card-header simple-header">
          <span>联调说明</span>
        </div>
      </template>
      <ul class="tips-list">
        <li>保存后即可用于充值订单、手动补单和后台退款。</li>
        <li>若 API 密钥或证书已存在，直接留空即可保留旧值。</li>
        <li>当前后台未开放独立的“测试连接”接口，建议通过沙箱或测试订单验证配置。</li>
      </ul>
    </el-card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Check } from '@element-plus/icons-vue'
import { getPaymentConfig, savePaymentConfig } from '@/api/payment'

const formRef = ref(null)
const loading = ref(false)
const saving = ref(false)
const apiKeyPlaceholder = ref('请输入 API 密钥')

const form = reactive({
  is_enabled: false,
  app_id: '',
  mch_id: '',
  api_key: '',
  api_key_masked: false,
  notify_url: '',
  api_cert: '',
  api_key_pem: '',
  has_cert: false,
  has_key_pem: false
})

const rules = {
  app_id: [{ required: true, message: '请输入微信支付 AppID', trigger: 'blur' }],
  mch_id: [{ required: true, message: '请输入商户号', trigger: 'blur' }],
  notify_url: [{ required: true, message: '请输入支付回调地址', trigger: 'blur' }],
  api_key: [{ validator: validateApiKey, trigger: 'blur' }]
}

function validateApiKey(rule, value, callback) {
  if (!value && form.api_key_masked) {
    callback()
    return
  }

  if (!value) {
    callback(new Error('请输入 API 密钥'))
    return
  }

  if (String(value).trim().length !== 32) {
    callback(new Error('API 密钥应为 32 位字符'))
    return
  }

  callback()
}

onMounted(() => {
  loadConfig()
})

async function loadConfig() {
  loading.value = true
  try {
    const { data } = await getPaymentConfig()
    Object.assign(form, {
      is_enabled: Boolean(data?.is_enabled),
      app_id: data?.app_id || '',
      mch_id: data?.mch_id || '',
      api_key: '',
      api_key_masked: Boolean(data?.api_key_masked),
      notify_url: data?.notify_url || '',
      api_cert: '',
      api_key_pem: '',
      has_cert: Boolean(data?.has_cert),
      has_key_pem: Boolean(data?.has_key_pem)
    })
    apiKeyPlaceholder.value = form.api_key_masked ? '密钥已配置，留空则不修改' : '请输入 API 密钥'
  } catch (error) {
    ElMessage.error(error.message || '加载支付配置失败')
  } finally {
    loading.value = false
  }
}

function buildSubmitPayload() {
  const payload = {
    is_enabled: form.is_enabled,
    app_id: form.app_id.trim(),
    mch_id: form.mch_id.trim(),
    notify_url: form.notify_url.trim(),
    api_key_masked: form.api_key_masked
  }

  if (form.api_key.trim()) {
    payload.api_key = form.api_key.trim()
  }

  if (form.api_cert.trim()) {
    payload.api_cert = form.api_cert.trim()
  }

  if (form.api_key_pem.trim()) {
    payload.api_key_pem = form.api_key_pem.trim()
  }

  return payload
}

async function handleSave() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) {
    return
  }

  saving.value = true
  try {
    await savePaymentConfig(buildSubmitPayload())
    ElMessage.success('支付配置保存成功')
    await loadConfig()
  } catch (error) {
    ElMessage.error(error.message || '保存失败')
  } finally {
    saving.value = false
  }
}
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.simple-header {
  justify-content: flex-start;
}

.title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.subtitle {
  margin-top: 6px;
  color: #909399;
  font-size: 13px;
}

.config-form {
  max-width: 860px;
}

.form-tip {
  margin-top: 6px;
  color: #909399;
  font-size: 12px;
  line-height: 1.6;
}

.tips-list {
  margin: 0;
  padding-left: 18px;
  color: #606266;
  line-height: 1.9;
}
</style>
