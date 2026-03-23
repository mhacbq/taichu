<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import {
  getSmsConfig,
  saveSmsConfig,
  testSms,
  getSmsStats
} from '../../api/admin'

const loading = ref(false)
const config = ref({
  provider: 'aliyun',
  access_key: '',
  secret_key: '',
  sign_name: '',
  template_code: '',
  template_param: ''
})
const testForm = ref({
  phone: '',
  code: ''
})
const stats = ref({
  total_sent: 0,
  success_count: 0,
  fail_count: 0,
  today_sent: 0
})

const loadConfig = async () => {
  loading.value = true
  try {
    const response = await getSmsConfig()
    if (response.code === 200) {
      config.value = response.data
    } else {
      ElMessage.error(response.message || '加载失败')
    }
  } catch (error) {
    console.error('加载配置失败:', error)
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await getSmsStats()
    if (response.code === 200) {
      stats.value = response.data
    }
  } catch (error) {
    console.error('加载统计失败:', error)
  }
}

const handleSave = async () => {
  if (!config.value.access_key || !config.value.secret_key) {
    ElMessage.warning('请填写完整配置')
    return
  }

  loading.value = true
  try {
    const response = await saveSmsConfig(config.value)
    if (response.code === 200) {
      ElMessage.success('保存成功')
    } else {
      ElMessage.error(response.message || '保存失败')
    }
  } catch (error) {
    console.error('保存失败:', error)
    ElMessage.error('保存失败')
  } finally {
    loading.value = false
  }
}

const handleTest = async () => {
  if (!testForm.value.phone || !testForm.value.code) {
    ElMessage.warning('请填写手机号和验证码')
    return
  }

  loading.value = true
  try {
    const response = await testSms(testForm.value)
    if (response.code === 200) {
      ElMessage.success('发送成功')
    } else {
      ElMessage.error(response.message || '发送失败')
    }
  } catch (error) {
    console.error('发送失败:', error)
    ElMessage.error('发送失败')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadConfig()
  loadStats()
})
</script>

<template>
  <div class="admin-sms-config" v-loading="loading">
    <div class="page-header">
      <h2>短信配置</h2>
      <el-button type="primary" @click="handleSave" :loading="loading">保存配置</el-button>
    </div>

    <!-- 统计信息 -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">总发送量</div>
        <div class="stat-value">{{ stats.total_sent }}</div>
      </div>
      <div class="stat-card success">
        <div class="stat-label">成功数</div>
        <div class="stat-value">{{ stats.success_count }}</div>
      </div>
      <div class="stat-card fail">
        <div class="stat-label">失败数</div>
        <div class="stat-value">{{ stats.fail_count }}</div>
      </div>
      <div class="stat-card today">
        <div class="stat-label">今日发送</div>
        <div class="stat-value">{{ stats.today_sent }}</div>
      </div>
    </div>

    <!-- 配置表单 -->
    <el-card class="config-card">
      <template #header>
        <span>服务商配置</span>
      </template>
      <el-form :model="config" label-width="120px">
        <el-form-item label="服务商">
          <el-select v-model="config.provider" placeholder="请选择">
            <el-option label="阿里云" value="aliyun" />
            <el-option label="腾讯云" value="tencent" />
          </el-select>
        </el-form-item>
        <el-form-item label="AccessKey">
          <el-input v-model="config.access_key" placeholder="请输入AccessKey" show-password />
        </el-form-item>
        <el-form-item label="SecretKey">
          <el-input v-model="config.secret_key" placeholder="请输入SecretKey" show-password />
        </el-form-item>
        <el-form-item label="签名名称">
          <el-input v-model="config.sign_name" placeholder="请输入签名名称" />
        </el-form-item>
        <el-form-item label="模板代码">
          <el-input v-model="config.template_code" placeholder="请输入模板代码" />
        </el-form-item>
        <el-form-item label="模板变量">
          <el-input v-model="config.template_param" placeholder="请输入模板变量，如：code" />
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 测试发送 -->
    <el-card class="config-card">
      <template #header>
        <span>测试发送</span>
      </template>
      <el-form :model="testForm" label-width="120px">
        <el-form-item label="手机号">
          <el-input v-model="testForm.phone" placeholder="请输入手机号" />
        </el-form-item>
        <el-form-item label="验证码">
          <el-input v-model="testForm.code" placeholder="请输入验证码" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleTest" :loading="loading">发送测试</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<style scoped>
.admin-sms-config {
  padding: 24px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.stat-card.success { border-left: 4px solid #67c23a; }
.stat-card.fail { border-left: 4px solid #f56c6c; }
.stat-card.today { border-left: 4px solid #409eff; }

.stat-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 10px;
}

.stat-value {
  font-size: 28px;
  font-weight: bold;
  color: #333;
}

.config-card {
  margin-bottom: 20px;
}
</style>
