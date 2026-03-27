<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import request from '@/api/request'

const loading = ref(false)
const testing = ref(false)

// 预设模型列表
const modelPresets = [
  { name: 'GPT-3.5 Turbo',    model: 'gpt-3.5-turbo',              provider: 'OpenAI',   icon: '🤖' },
  { name: 'GPT-4',            model: 'gpt-4',                      provider: 'OpenAI',   icon: '🧠' },
  { name: 'GPT-4 Turbo',      model: 'gpt-4-turbo',                provider: 'OpenAI',   icon: '⚡' },
  { name: 'Claude 3 Sonnet',  model: 'claude-3-sonnet-20240229',   provider: 'Anthropic', icon: '🎭' },
  { name: 'Claude 3 Opus',    model: 'claude-3-opus-20240229',     provider: 'Anthropic', icon: '💎' },
  { name: '通义千问-Turbo',   model: 'qwen-turbo',                 provider: '阿里云',   icon: '☁️' },
  { name: '通义千问-Plus',    model: 'qwen-plus',                  provider: '阿里云',   icon: '🌟' },
  { name: '通义千问-Max',     model: 'qwen-max',                   provider: '阿里云',   icon: '🔥' },
  { name: 'DeepSeek-V3',      model: 'deepseek-chat',              provider: 'DeepSeek', icon: '🔮' },
  { name: 'DeepSeek-V3.2',    model: 'DeepSeek-V3.2',              provider: 'DeepSeek', icon: '🔮' },
  { name: 'DeepSeek-R1',      model: 'deepseek-reasoner',          provider: 'DeepSeek', icon: '🧩' },
  { name: '文心一言 4.0',     model: 'ernie-4.0-8k',               provider: '百度',     icon: '📝' },
  { name: '智谱GLM-4',        model: 'glm-4',                      provider: '智谱AI',   icon: '🎯' },
]

// 自定义模型列表
const customModels = ref<string[]>([])

const form = ref({
  ai_is_enabled: false,
  ai_api_url: '',
  ai_api_key: '',
  ai_model: 'DeepSeek-V3.2',
  ai_max_tokens: 4096,
  ai_temperature: 0.7,
  ai_timeout: 60,
  ai_retry_times: 3
})

const newModelName = ref('')

onMounted(() => {
  fetchConfig()
  loadCustomModels()
})

async function fetchConfig() {
  loading.value = true
  try {
    const res = await request({ url: '/ai/config', method: 'get' })
    form.value = { ...form.value, ...res.data }
  } catch (error) {
    ElMessage.error('获取AI配置失败')
  } finally {
    loading.value = false
  }
}

// 加载自定义模型
function loadCustomModels() {
  const saved = localStorage.getItem('ai-custom-models')
  if (saved) {
    customModels.value = JSON.parse(saved)
  }
}

// 保存自定义模型
function saveCustomModels() {
  localStorage.setItem('ai-custom-models', JSON.stringify(customModels.value))
}

// 切换模型
function switchModel(modelName: string) {
  form.value.ai_model = modelName
  ElMessage.success('已切换模型: ' + modelName)
}

// 添加自定义模型
function addCustomModel() {
  if (!newModelName.value.trim()) {
    ElMessage.warning('请输入模型名称')
    return
  }
  if (customModels.value.includes(newModelName.value)) {
    ElMessage.warning('该模型已存在')
    return
  }
  customModels.value.push(newModelName.value)
  saveCustomModels()
  form.value.ai_model = newModelName.value
  newModelName.value = ''
  ElMessage.success('添加成功')
}

// 删除自定义模型
function removeCustomModel(model: string) {
  customModels.value = customModels.value.filter(m => m !== model)
  saveCustomModels()
  if (form.value.ai_model === model) {
    form.value.ai_model = 'gpt-3.5-turbo'
  }
  ElMessage.success('已删除')
}

async function handleSave() {
  try {
    await request({ url: '/ai/config', method: 'post', data: form.value })
    ElMessage.success('保存成功')
  } catch (error) {
    ElMessage.error('保存失败')
  }
}

async function handleTest() {
  testing.value = true
  try {
    const res = await request({ url: '/ai/test', method: 'post', data: {} })
    if (res.data?.status === 'success') {
      ElMessage.success('测试成功: ' + res.data.message)
    } else {
      ElMessage.error('测试失败: ' + (res.data?.message || '未知错误'))
    }
  } catch (error: any) {
    ElMessage.error('测试失败: ' + (error?.message || '未知错误'))
  } finally {
    testing.value = false
  }
}
</script>

<template>
  <div class="app-container">
    <!-- 模型快速切换 -->
    <el-card class="model-section-card" v-loading="loading">
      <template #header>
        <span>🤖 AI模型切换</span>
      </template>

      <!-- 预设模型 -->
      <div class="section">
        <h4 class="section-title">预设模型</h4>
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
      </div>

      <!-- 自定义模型 -->
      <div class="section" v-if="customModels.length > 0">
        <h4 class="section-title">自定义模型</h4>
        <div class="model-grid">
          <div
            v-for="model in customModels"
            :key="model"
            :class="['model-item', 'custom', { active: form.ai_model === model }]"
          >
            <span @click="switchModel(model)">{{ model }}</span>
            <el-button
              type="danger"
              size="small"
              text
              @click.stop="removeCustomModel(model)"
            >
              删除
            </el-button>
          </div>
        </div>
      </div>

      <!-- 添加自定义模型 -->
      <div class="add-model">
        <el-input
          v-model="newModelName"
          placeholder="输入自定义模型名称"
          @keyup.enter="addCustomModel"
        >
          <template #append>
            <el-button @click="addCustomModel">添加</el-button>
          </template>
        </el-input>
      </div>
    </el-card>

    <!-- 详细配置 -->
    <el-card class="config-card" v-loading="loading">
      <template #header>
        <div class="card-header">
          <span>⚙️ 详细配置</span>
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

        <el-form-item label="当前模型" required>
          <el-input
            v-model="form.ai_model"
            placeholder="请输入模型名称，如 qwen-plus、DeepSeek-V3.2"
          >
            <template #prepend>model</template>
          </el-input>
          <div style="font-size:12px;color:#909399;margin-top:4px">点击上方预设模型可快速填入，也可手动输入自定义模型名称</div>
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

.model-section-card {
  margin-bottom: 20px;
}

.config-card {
  margin-top: 20px;
}

.section {
  margin-bottom: 24px;
}

.section-title {
  font-size: 14px;
  font-weight: 600;
  color: #303133;
  margin-bottom: 12px;
}

.model-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}

.model-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border: 2px solid #e4e7ed;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
}

.model-item:hover {
  border-color: #409eff;
  background: #f0f7ff;
}

.model-item.active {
  border-color: #67c23a;
  background: #f0f9ff;
  box-shadow: 0 2px 8px rgba(103, 194, 58, 0.2);
}

.model-item.custom {
  justify-content: space-between;
}

.model-icon {
  font-size: 32px;
  flex-shrink: 0;
}

.model-info {
  flex: 1;
}

.model-name {
  font-weight: 600;
  color: #303133;
  margin-bottom: 4px;
}

.model-provider {
  font-size: 12px;
  color: #909399;
}

.add-model {
  margin-top: 16px;
}
</style>