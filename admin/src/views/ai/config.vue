<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'

const loading = ref(false)
const testing = ref(false)
const form = ref({
  ai_is_enabled: false,
  ai_api_url: '',
  ai_api_key: '',
  ai_model: 'gpt-3.5-turbo',
  ai_max_tokens: 2000,
  ai_temperature: 0.7,
  ai_timeout: 30,
  ai_retry_times: 3
})

onMounted(() => {
  fetchConfig()
})

async function fetchConfig() {
  loading.value = true
  try {
    const res = await window.$api.get('/api/maodou/ai/config')
    form.value = res.data
  } catch (error) {
    ElMessage.error('获取AI配置失败')
  } finally {
    loading.value = false
  }
}

async function handleSave() {
  try {
    await window.$api.post('/api/maodou/ai/config', form.value)
    ElMessage.success('保存成功')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

async function handleTest() {
  testing.value = true
  try {
    const res = await window.$api.post('/api/maodou/ai/test', {})
    if (res.data.status === 'success') {
      ElMessage.success('测试成功: ' + res.data.message)
    } else {
      ElMessage.error('测试失败: ' + res.data.message)
    }
  } catch (error) {
    ElMessage.error('测试失败: ' + error.message)
  } finally {
    testing.value = false
  }
}
</script>

<template>
  <div class="app-container">
    <el-card v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>AI配置</span>
          <div>
            <el-button type="primary" @click="handleTest" :loading="testing">测试连接</el-button>
            <el-button type="success" @click="handleSave">保存配置</el-button>
          </div>
        </div>
      </template>

      <el-form :model="form" label-width="180px">
        <el-form-item label="启用AI服务">
          <el-switch v-model="form.ai_is_enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>

        <el-form-item label="API地址" required>
          <el-input v-model="form.ai_api_url" placeholder="请输入AI服务API地址" />
        </el-form-item>

        <el-form-item label="API密钥" required>
          <el-input
            v-model="form.ai_api_key"
            type="password"
            placeholder="请输入API密钥"
            show-password
          />
        </el-form-item>

        <el-form-item label="模型名称">
          <el-input v-model="form.ai_model" placeholder="请输入AI模型名称" />
        </el-form-item>

        <el-form-item label="最大Token数">
          <el-input-number v-model="form.ai_max_tokens" :min="100" :max="8000" :step="100" />
        </el-form-item>

        <el-form-item label="温度参数">
          <el-slider v-model="form.ai_temperature" :min="0" :max="2" :step="0.1" show-input />
        </el-form-item>

        <el-form-item label="请求超时(秒)">
          <el-input-number v-model="form.ai_timeout" :min="5" :max="120" :step="5" />
        </el-form-item>

        <el-form-item label="失败重试次数">
          <el-input-number v-model="form.ai_retry_times" :min="0" :max="10" :step="1" />
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
