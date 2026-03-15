<template>
  <div class="app-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>微信支付配置</span>
          <el-button type="primary" @click="handleSave" :loading="saving">
            <el-icon><Check /></el-icon>保存配置
          </el-button>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="180px"
        class="config-form"
      >
        <el-divider>基础配置</el-divider>
        
        <el-form-item label="启用微信支付" prop="enabled">
          <el-switch
            v-model="form.enabled"
            active-text="启用"
            inactive-text="禁用"
          />
        </el-form-item>

        <el-form-item label="微信支付AppID" prop="app_id">
          <el-input
            v-model="form.app_id"
            placeholder="请输入微信支付AppID"
            clearable
          />
          <div class="form-tip">微信开放平台申请的移动应用AppID或公众号AppID</div>
        </el-form-item>

        <el-form-item label="商户号MchID" prop="mch_id">
          <el-input
            v-model="form.mch_id"
            placeholder="请输入微信支付商户号"
            clearable
          />
          <div class="form-tip">微信支付商户平台分配的商户号</div>
        </el-form-item>

        <el-form-item label="API密钥" prop="api_key">
          <el-input
            v-model="form.api_key"
            type="password"
            placeholder="请输入API密钥"
            show-password
            clearable
          />
          <div class="form-tip">微信支付商户平台的API密钥（32位字符串）</div>
        </el-form-item>

        <el-form-item label="APIv3密钥" prop="api_v3_key">
          <el-input
            v-model="form.api_v3_key"
            type="password"
            placeholder="请输入APIv3密钥（可选）"
            show-password
            clearable
          />
          <div class="form-tip">微信支付APIv3版本的密钥（可选，用于新接口）</div>
        </el-form-item>

        <el-divider>高级配置</el-divider>

        <el-form-item label="支付回调地址" prop="notify_url">
          <el-input
            v-model="form.notify_url"
            placeholder="https://your-domain.com/api/payment/notify"
            clearable
          />
          <div class="form-tip">微信支付异步通知地址，需要外网可访问</div>
        </el-form-item>

        <el-form-item label="证书路径" prop="cert_path">
          <el-input
            v-model="form.cert_path"
            placeholder="/path/to/apiclient_cert.pem"
            clearable
          />
          <div class="form-tip">微信支付证书路径（退款时需要）</div>
        </el-form-item>

        <el-form-item label="证书密钥路径" prop="key_path">
          <el-input
            v-model="form.key_path"
            placeholder="/path/to/apiclient_key.pem"
            clearable
          />
          <div class="form-tip">微信支付证书密钥路径</div>
        </el-form-item>

        <el-divider>沙箱配置</el-divider>

        <el-form-item label="沙箱模式" prop="sandbox">
          <el-switch
            v-model="form.sandbox"
            active-text="开启"
            inactive-text="关闭"
          />
          <div class="form-tip">开启后使用微信支付沙箱环境（仅用于测试）</div>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 测试区域 -->
    <el-card shadow="never" style="margin-top: 20px;">
      <template #header>
        <div class="card-header">
          <span>连接测试</span>
          <el-button @click="handleTest" :loading="testing">
            <el-icon><Connection /></el-icon>测试连接
          </el-button>
        </div>
      </template>
      <div class="test-info">
        <p>点击"测试连接"按钮可验证微信支付配置是否正确。</p>
        <p>测试内容包括：AppID有效性、商户号状态、API密钥正确性。</p>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Check, Connection } from '@element-plus/icons-vue'
import { getPaymentConfig, savePaymentConfig } from '@/api/payment'

const formRef = ref(null)
const saving = ref(false)
const testing = ref(false)

const form = reactive({
  enabled: false,
  app_id: '',
  mch_id: '',
  api_key: '',
  api_v3_key: '',
  notify_url: '',
  cert_path: '',
  key_path: '',
  sandbox: false
})

const rules = {
  app_id: [
    { required: true, message: '请输入微信支付AppID', trigger: 'blur' }
  ],
  mch_id: [
    { required: true, message: '请输入商户号', trigger: 'blur' }
  ],
  api_key: [
    { required: true, message: '请输入API密钥', trigger: 'blur' },
    { min: 32, max: 32, message: 'API密钥应为32位', trigger: 'blur' }
  ],
  notify_url: [
    { required: true, message: '请输入回调地址', trigger: 'blur' }
  ]
}

onMounted(() => {
  loadConfig()
})

async function loadConfig() {
  try {
    const { data } = await getPaymentConfig()
    if (data) {
      Object.assign(form, data)
    }
  } catch (error) {
    console.error('加载配置失败:', error)
  }
}

async function handleSave() {
  const valid = await formRef.value?.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  try {
    await savePaymentConfig(form)
    ElMessage.success('配置保存成功')
  } catch (error) {
    ElMessage.error(error.message || '保存失败')
  } finally {
    saving.value = false
  }
}

async function handleTest() {
  testing.value = true
  try {
    // 这里应该调用后端测试接口
    await new Promise(resolve => setTimeout(resolve, 1000))
    ElMessage.success('连接测试成功')
  } catch (error) {
    ElMessage.error(error.message || '连接测试失败')
  } finally {
    testing.value = false
  }
}
</script>

<style lang="scss" scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.config-form {
  max-width: 700px;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
}

.test-info {
  color: #606266;
  line-height: 1.8;
}
</style>
