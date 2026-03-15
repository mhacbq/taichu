<template>
  <div class="app-container">
    <el-card shadow="never">
      <template #header>
        <div class="card-header">
          <span>腾讯云短信配置</span>
          <el-button type="primary" @click="handleSave" :loading="saving">
            <el-icon><Check /></el-icon>保存配置
          </el-button>
        </div>
      </template>

      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="160px"
        class="config-form"
      >
        <el-divider>基础配置</el-divider>
        
        <el-form-item label="启用短信服务" prop="is_enabled">
          <el-switch
            v-model="form.is_enabled"
            active-text="启用"
            inactive-text="禁用"
          />
        </el-form-item>

        <el-form-item label="SecretId" prop="secret_id">
          <el-input
            v-model="form.secret_id"
            placeholder="请输入腾讯云SecretId"
            clearable
          />
          <div class="form-tip">腾讯云CAM控制台获取的SecretId</div>
        </el-form-item>

        <el-form-item label="SecretKey" prop="secret_key">
          <el-input
            v-model="form.secret_key"
            type="password"
            placeholder="请输入腾讯云SecretKey"
            show-password
            clearable
          />
          <div class="form-tip">腾讯云CAM控制台获取的SecretKey</div>
        </el-form-item>

        <el-form-item label="SDK AppId" prop="sdk_app_id">
          <el-input
            v-model="form.sdk_app_id"
            placeholder="请输入短信应用SDK AppId"
            clearable
          />
          <div class="form-tip">短信控制台-应用管理-应用列表中的应用SDK AppID</div>
        </el-form-item>

        <el-form-item label="短信签名" prop="sign_name">
          <el-input
            v-model="form.sign_name"
            placeholder="请输入短信签名"
            clearable
          />
          <div class="form-tip">短信控制台-国内短信-签名管理中的签名内容</div>
        </el-form-item>

        <el-divider>模板配置</el-divider>

        <el-form-item label="验证码模板ID" prop="template_code">
          <el-input
            v-model="form.template_code"
            placeholder="请输入验证码短信模板ID"
            clearable
          />
          <div class="form-tip">短信控制台-国内短信-正文模板管理中的模板ID</div>
        </el-form-item>

        <el-form-item label="注册通知模板ID" prop="template_register">
          <el-input
            v-model="form.template_register"
            placeholder="请输入注册通知模板ID（可选）"
            clearable
          />
          <div class="form-tip">用户注册成功后的通知短信模板ID（可选）</div>
        </el-form-item>

        <el-form-item label="密码重置模板ID" prop="template_reset">
          <el-input
            v-model="form.template_reset"
            placeholder="请输入密码重置模板ID（可选）"
            clearable
          />
          <div class="form-tip">密码重置时的短信模板ID（可选）</div>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 测试区域 -->
    <el-card shadow="never" style="margin-top: 20px;">
      <template #header>
        <div class="card-header">
          <span>发送测试</span>
          <el-button @click="handleTest" :loading="testing">
            <el-icon><Message /></el-icon>发送测试短信
          </el-button>
        </div>
      </template>
      <el-form :model="testForm" inline>
        <el-form-item label="测试手机号">
          <el-input
            v-model="testForm.phone"
            placeholder="请输入测试手机号"
            maxlength="11"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleTest" :loading="testing">
            发送验证码
          </el-button>
        </el-form-item>
      </el-form>
      <div class="test-info">
        <p>测试发送将使用验证码模板发送一条短信，用于验证配置是否正确。</p>
        <p>注意：测试发送会消耗腾讯云短信配额。</p>
      </div>
    </el-card>

    <!-- 使用说明 -->
    <el-card shadow="never" style="margin-top: 20px;">
      <template #header>
        <div class="card-header">
          <span>配置说明</span>
        </div>
      </template>
      <div class="help-content">
        <h4>1. 开通腾讯云短信服务</h4>
        <p>访问 <el-link href="https://console.cloud.tencent.com/sms" target="_blank">腾讯云短信控制台</el-link> 开通短信服务</p>
        
        <h4>2. 创建短信应用</h4>
        <p>在「应用管理」-「应用列表」中创建应用，获取 SDK AppId</p>
        
        <h4>3. 申请短信签名</h4>
        <p>在「国内短信」-「签名管理」中申请签名，审核通过后即可使用</p>
        
        <h4>4. 申请短信模板</h4>
        <p>在「国内短信」-「正文模板管理」中申请验证码模板，内容建议：<br>
        <code>{1}为您的验证码，请于{2}分钟内填写。如非本人操作，请忽略本短信。</code></p>
        
        <h4>5. 获取API密钥</h4>
        <p>在 <el-link href="https://console.cloud.tencent.com/cam/capi" target="_blank">访问管理-API密钥管理</el-link> 中获取 SecretId 和 SecretKey</p>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Check, Message } from '@element-plus/icons-vue'
import { getSmsConfig, saveSmsConfig, testSmsSend } from '@/api/sms'

const formRef = ref(null)
const saving = ref(false)
const testing = ref(false)

const form = reactive({
  is_enabled: false,
  secret_id: '',
  secret_key: '',
  sdk_app_id: '',
  sign_name: '',
  template_code: '',
  template_register: '',
  template_reset: ''
})

const testForm = reactive({
  phone: ''
})

const rules = {
  secret_id: [
    { required: true, message: '请输入SecretId', trigger: 'blur' }
  ],
  sdk_app_id: [
    { required: true, message: '请输入SDK AppId', trigger: 'blur' }
  ],
  sign_name: [
    { required: true, message: '请输入短信签名', trigger: 'blur' }
  ],
  template_code: [
    { required: true, message: '请输入验证码模板ID', trigger: 'blur' }
  ]
}

onMounted(() => {
  loadConfig()
})

async function loadConfig() {
  try {
    const { data } = await getSmsConfig()
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
    await saveSmsConfig(form)
    ElMessage.success('配置保存成功')
    loadConfig()
  } catch (error) {
    ElMessage.error(error.message || '保存失败')
  } finally {
    saving.value = false
  }
}

async function handleTest() {
  if (!testForm.phone) {
    ElMessage.warning('请输入测试手机号')
    return
  }
  
  if (!/^1[3-9]\d{9}$/.test(testForm.phone)) {
    ElMessage.warning('手机号格式不正确')
    return
  }

  testing.value = true
  try {
    await testSmsSend({ phone: testForm.phone })
    ElMessage.success('测试短信已发送，请注意查收')
  } catch (error) {
    ElMessage.error(error.message || '发送失败')
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
  max-width: 600px;
}

.form-tip {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
}

.test-info {
  color: #606266;
  line-height: 1.8;
  margin-top: 15px;
}

.help-content {
  color: #606266;
  line-height: 2;
  
  h4 {
    margin: 20px 0 10px;
    color: #303133;
    font-weight: 600;
    
    &:first-child {
      margin-top: 0;
    }
  }
  
  p {
    margin: 5px 0;
  }
  
  code {
    background: #f5f7fa;
    padding: 2px 8px;
    border-radius: 4px;
    font-family: monospace;
    color: #e6a23c;
  }
}
</style>
