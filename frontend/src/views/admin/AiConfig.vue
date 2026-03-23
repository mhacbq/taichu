
<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import request from '@/api/request'

const loading = ref(false)
const saving = ref(false)
const testing = ref(false)

const form = ref({
  ai_is_enabled: 0,
  ai_api_url: '',
  ai_api_key: '',
  ai_model: '',
  ai_max_tokens: 2000,
  ai_temperature: 0.7,
  ai_timeout: 30,
  ai_retry_times: 3
})

// 预设模型
const modelPresets = [
  { name: 'DeepSeek-V3', model: 'deepseek-chat', provider: 'DeepSeek', icon: '🔮' },
  { name: 'GPT-4 Turbo', model: 'gpt-4-turbo', provider: 'OpenAI', icon: '⚡' },
  { name: 'GPT-4o', model: 'gpt-4o', provider: 'OpenAI', icon: '🧠' },
  { name: 'Claude 3.5 Sonnet', model: 'claude-3-5-sonnet-20241022', provider: 'Anthropic', icon: '🎭' },
  { name: '通义千问-Plus', model: 'qwen-plus', provider: '阿里云', icon: '🌟' },
  { name: '智谱GLM-4', model: 'glm-4', provider: '智谱AI', icon: '🎯' },
]

onMounted(() => {
  fetchConfig()
})

async function fetchConfig() {
  loading.value = true
  try {
    const res = await request.get('/api/maodou/ai/config')
    if (res.code === 0 || res.code === 200) {
      Object.assign(form.value, res.data)
    }
  } catch {
    // request.js 已统一处理错误提示
  } finally {
    loading.value = false
  }
}

function switchModel(modelName) {
  form.value.ai_model = modelName
}

async function handleSave() {
  if (!form.value.ai_api_url) {
    return ElMessage.warning('请填写API地址')
  }
  saving.value = true
  try {
    const res = await request.post('/api/maodou/ai/config', form.value)
    if (res.code === 0 || res.code === 200) {
      ElMessage.success('保存成功')
    }
  } catch {
    // 统一处理
  } finally {
    saving.value = false
  }
}

async function handleTest() {
  testing.value = true
  try {
    const res = await request.post('/api/maodou/ai/test')
    if (res.data?.status === 'success') {
      ElMessage.success('AI 服务连接成功')
    } else {
      ElMessage.error(res.data?.message || '连接失败')
    }
  } catch {
    // 统一处理
  } finally {
    testing.value = false
  }
}
</script>

<template>
  <div class="admin-ai-config" v-loading="loading">
    <!-- 模型快速切换 -->
    <el-card class="section-card">
      <template #header>
        <span>🤖 AI模型选择</span>
      </template>
      <div class="model-grid">
        <div
          v-for="preset in modelPresets"
          :key="preset.model"
          :class="['model-item', { active: form.ai_model === preset.model }]"
          @click="switchModel(preset.model)"
        >
          <div class="model-icon">{{ preset.icon }}</div>
          <div class="model-info">
            <div class="model-name">{{ preset.name }}</div>
            <div class="model-provider">{{ preset.provider }}</div>
          </div>
        </div>
      </div>
    </el-card>

    <!-- 详细配置 -->
    <el-card class="section-card">
      <template #header>
        <div class="card-header">
          <span>⚙️ 服务配置</span>
          <div class="header-actions">
            <el-button @click="handleTest" :loading="testing">测试连接</el-button>
            <el-button type="primary" @click="handleSave" :loading="saving">保存配置</el-button>
          </div>
        </div>
      </template>

      <el-form :model="form" label-width="140px" style="max-width: 700px;">
        <el-form-item label="启用AI服务">
          <el-switch v-model="form.ai_is_enabled" :active-value="1" :inactive-value="0" />
        </el-form-item>

        <el-form-item label="API地址" required>
          <el-input v-model="form.ai_api_url" placeholder="如 https://api.deepseek.com/v1/chat/completions" />
        </el-form-item>

        <el-form-item label="API密钥" required>
          <el-input v-model="form.ai_api_key" type="password" show-password placeholder="请输入API密钥" />
        </el-form-item>

        <el-form-item label="当前模型">
          <el-input v-model="form.ai_model" placeholder="模型名称，也可从上方快速选择" />
        </el-form-item>

        <el-form-item label="最大Token数">
          <el-input-number v-model="form.ai_max_tokens" :min="100" :max="8000" :step="100" />
        </el-form-item>

        <el-form-item label="温度参数">
          <el-slider v-model="form.ai_temperature" :min="0" :max="2" :step="0.1" show-input style="max-width: 400px;" />
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
.admin-ai-config {
  padding: 24px;
}

.section-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.model-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}

.model-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border: 2px solid #e4e7ed;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.model-item:hover {
  border-color: #409eff;
  background: #f0f7ff;
}

.model-item.active {
  border-color: #67c23a;
  background: #f0f9eb;
  box-shadow: 0 2px 8px rgba(103, 194, 58, 0.15);
}

.model-icon {
  font-size: 28px;
  flex-shrink: 0;
}

.model-info {
  flex: 1;
  min-width: 0;
}

.model-name {
  font-weight: 600;
  color: #303133;
  font-size: 14px;
}

.model-provider {
  font-size: 12px;
  color: #909399;
  margin-top: 2px;
}
</style>
