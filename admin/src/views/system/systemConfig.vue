<template>
  <div class="system-config-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>系统配置管理</span>
          <div class="header-actions">
            <el-button @click="handleExport" :disabled="!currentGroup">导出配置</el-button>
            <el-button type="primary" :loading="saving" @click="handleSave">保存配置</el-button>
          </div>
        </div>
      </template>

      <!-- 配置分组标签页 -->
      <el-tabs v-model="currentGroup" @tab-change="handleTabChange" class="config-tabs">
        <el-tab-pane label="支付配置" name="payment" />
        <el-tab-pane label="推送服务配置" name="push" />
        <el-tab-pane label="短信服务配置" name="sms" />
      </el-tabs>

      <!-- 配置表单 -->
      <div v-if="configs.length > 0" class="config-content">
        <!-- 支付配置 -->
        <template v-if="currentGroup === 'payment'">
          <el-divider content-position="left">微信支付</el-divider>
          <el-form :model="formData" label-width="180px" class="config-form">
            <el-form-item label="商户号">
              <el-input v-model="formData.wechat_mch_id" placeholder="请输入微信支付商户号" />
            </el-form-item>
            <el-form-item label="应用ID">
              <el-input v-model="formData.wechat_app_id" placeholder="请输入微信应用ID" />
            </el-form-item>
            <el-form-item label="API密钥">
              <el-input
                v-model="formData.wechat_api_key"
                type="password"
                placeholder="请输入微信支付API密钥"
                show-password
              />
            </el-form-item>
            <el-form-item label="支付证书">
              <el-input
                v-model="formData.wechat_api_cert"
                type="textarea"
                :rows="4"
                placeholder="请粘贴apiclient_cert.pem的内容"
              />
            </el-form-item>
            <el-form-item label="支付私钥">
              <el-input
                v-model="formData.wechat_api_key_pem"
                type="textarea"
                :rows="4"
                placeholder="请粘贴apiclient_key.pem的内容"
              />
            </el-form-item>
            <el-form-item label="回调通知URL">
              <el-input v-model="formData.wechat_notify_url" placeholder="https://taichu.chat/api/payment/notify" />
            </el-form-item>
            <el-form-item label="状态">
              <el-switch v-model="formData.wechat_is_enabled" active-text="启用" inactive-text="禁用" />
            </el-form-item>
          </el-form>
          <el-button type="info" @click="handleTestPayment('wechat')" :loading="testing">测试微信支付配置</el-button>

          <el-divider content-position="left">支付宝支付</el-divider>
          <el-form :model="formData" label-width="180px" class="config-form">
            <el-form-item label="应用ID">
              <el-input v-model="formData.alipay_app_id" placeholder="请输入支付宝应用ID" />
            </el-form-item>
            <el-form-item label="应用私钥">
              <el-input
                v-model="formData.alipay_private_key"
                type="password"
                placeholder="请输入支付宝应用私钥"
                show-password
              />
            </el-form-item>
            <el-form-item label="支付宝公钥">
              <el-input
                v-model="formData.alipay_public_key"
                type="textarea"
                :rows="4"
                placeholder="请粘贴支付宝公钥"
              />
            </el-form-item>
            <el-form-item label="异步通知URL">
              <el-input v-model="formData.alipay_notify_url" placeholder="https://taichu.chat/api/alipay/notify" />
            </el-form-item>
            <el-form-item label="同步跳转URL">
              <el-input v-model="formData.alipay_return_url" placeholder="https://taichu.chat/recharge" />
            </el-form-item>
            <el-form-item label="状态">
              <el-switch v-model="formData.alipay_is_enabled" active-text="启用" inactive-text="禁用" />
            </el-form-item>
          </el-form>
          <el-button type="info" @click="handleTestPayment('alipay')" :loading="testing">测试支付宝配置</el-button>
        </template>

        <!-- 推送服务配置 -->
        <template v-else-if="currentGroup === 'push'">
          <el-divider content-position="left">推送服务</el-divider>
          <el-form :model="formData" label-width="180px" class="config-form">
            <el-form-item label="推送服务商">
              <el-select v-model="formData.push_provider" placeholder="请选择推送服务商" style="width: 300px">
                <el-option label="不使用" value="" />
                <el-option label="极光推送" value="jpush" />
                <el-option label="Firebase Cloud Messaging" value="fcm" />
                <el-option label="自定义Webhook" value="webhook" />
              </el-select>
            </el-form-item>

            <template v-if="formData.push_provider === 'jpush'">
              <el-form-item label="AppKey">
                <el-input v-model="formData.jpush_app_key" placeholder="请输入极光推送AppKey" />
              </el-form-item>
              <el-form-item label="MasterSecret">
                <el-input
                  v-model="formData.jpush_master_secret"
                  type="password"
                  placeholder="请输入极光推送MasterSecret"
                  show-password
                />
              </el-form-item>
            </template>

            <template v-if="formData.push_provider === 'fcm'">
              <el-form-item label="服务器密钥">
                <el-input
                  v-model="formData.fcm_server_key"
                  type="password"
                  placeholder="请输入FCM服务端密钥"
                  show-password
                />
              </el-form-item>
            </template>

            <template v-if="formData.push_provider === 'webhook'">
              <el-form-item label="Webhook地址">
                <el-input v-model="formData.webhook_url" placeholder="https://example.com/webhook" />
              </el-form-item>
              <el-form-item label="Bearer Token">
                <el-input
                  v-model="formData.webhook_bearer"
                  type="password"
                  placeholder="请输入Bearer Token"
                  show-password
                />
              </el-form-item>
            </template>

            <el-form-item label="启用消息推送">
              <el-switch v-model="formData.push_is_enabled" />
            </el-form-item>
          </el-form>
        </template>

        <!-- 短信服务配置 -->
        <template v-else-if="currentGroup === 'sms'">
          <el-divider content-position="left">短信服务</el-divider>
          <el-form :model="formData" label-width="180px" class="config-form">
            <el-form-item label="测试模式">
              <el-switch v-model="formData.sms_test_mode" />
              <span class="form-tip">测试模式下使用固定验证码</span>
            </el-form-item>
            <el-form-item label="测试验证码" v-if="formData.sms_test_mode">
              <el-input v-model="formData.sms_test_code" placeholder="123456" />
              <span class="form-tip">仅在测试模式下有效</span>
            </el-form-item>

            <el-divider content-position="left">腾讯云短信</el-divider>
            <el-form-item label="SecretId">
              <el-input
                v-model="formData.tencent_secret_id"
                type="password"
                placeholder="请输入腾讯云SecretId"
                show-password
              />
            </el-form-item>
            <el-form-item label="SecretKey">
              <el-input
                v-model="formData.tencent_secret_key"
                type="password"
                placeholder="请输入腾讯云SecretKey"
                show-password
              />
            </el-form-item>
            <el-form-item label="SDKAppId">
              <el-input v-model="formData.tencent_sdk_app_id" placeholder="请输入腾讯云SDKAppId" />
            </el-form-item>
            <el-form-item label="短信签名">
              <el-input v-model="formData.tencent_sign_name" placeholder="请输入短信签名" />
            </el-form-item>
            <el-form-item label="短信模板ID">
              <el-input v-model="formData.tencent_template_code" placeholder="请输入短信模板ID" />
            </el-form-item>

            <el-form-item label="启用短信服务">
              <el-switch v-model="formData.sms_is_enabled" />
            </el-form-item>
          </el-form>
        </template>
      </div>

      <el-empty v-else description="暂无配置数据" />
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getSystemConfigs,
  saveSystemConfigs,
  testPaymentConfig,
  exportSystemConfig
} from '@/api/systemConfig'

const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const currentGroup = ref('payment')
const configs = ref([])
const formData = reactive({})

// 加载配置数据
const loadConfigs = async () => {
  loading.value = true
  try {
    const { data } = await getSystemConfigs({ group: currentGroup.value })
    if (data.success) {
      configs.value = data.data.configs || []

      // 填充表单数据
      Object.assign(formData, {})
      configs.value.forEach(config => {
        formData[config.config_key] = config.config_value
      })
    } else {
      ElMessage.error(data.message || '加载配置失败')
    }
  } catch (error) {
    console.error('加载配置失败:', error)
    ElMessage.error('加载配置失败')
  } finally {
    loading.value = false
  }
}

// 切换标签页
const handleTabChange = () => {
  loadConfigs()
}

// 保存配置
const handleSave = async () => {
  saving.value = true
  try {
    const { data } = await saveSystemConfigs({
      group: currentGroup.value,
      configs: formData
    })

    if (data.success) {
      ElMessage.success('配置保存成功')
    } else {
      ElMessage.error(data.message || '保存配置失败')
    }
  } catch (error) {
    console.error('保存配置失败:', error)
    ElMessage.error('保存配置失败')
  } finally {
    saving.value = false
  }
}

// 测试支付配置
const handleTestPayment = async (type) => {
  testing.value = true
  try {
    const { data } = await testPaymentConfig({ type })

    if (data.success) {
      ElMessage.success(data.data.message || '配置验证通过')
    } else {
      ElMessage.error(data.message || '配置验证失败')
    }
  } catch (error) {
    console.error('测试支付配置失败:', error)
    ElMessage.error('测试失败')
  } finally {
    testing.value = false
  }
}

// 测试支付配置
const handleExport = async () => {
  if (!currentGroup.value) {
    ElMessage.warning('请先选择配置分组')
    return
  }

  try {
    const response = await exportSystemConfig({ group: currentGroup.value })

    // 创建下载链接
    const blob = new Blob([response])
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `system_config_${currentGroup.value}_${new Date().getTime()}.json`
    link.click()
    window.URL.revokeObjectURL(url)

    ElMessage.success('配置导出成功')
  } catch (error) {
    console.error('导出配置失败:', error)
    ElMessage.error('导出配置失败')
  }
}

onMounted(() => {
  loadConfigs()
})
</script>

<style scoped lang="scss">
.system-config-container {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .header-actions {
      display: flex;
      gap: 12px;
    }
  }

  .config-tabs {
    margin-bottom: 24px;
  }

  .config-content {
    .el-divider {
      margin: 24px 0 16px;
    }

    .config-form {
      .el-form-item {
        margin-bottom: 18px;
      }

      .form-tip {
        margin-left: 12px;
        color: #909399;
        font-size: 13px;
      }

      .el-input,
      .el-textarea,
      .el-select {
        width: 400px;
      }

      .el-input-number {
        width: 200px;
      }
    }
  }
}
</style>
